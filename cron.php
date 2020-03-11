<?php

use Facebook\Facebook;

require_once __DIR__ . '/vendor/autoload.php';

function fb_feed_url_get_contents($url) {
  $curlSession = curl_init();
  curl_setopt($curlSession, CURLOPT_URL, $url);
  curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
  curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
  $data = curl_exec($curlSession);
  curl_close($curlSession);
  return $data;
}

function fb_feed_fetch_posts() {
  $fb = new Facebook([
    'app_id' => FB_FEED_APP_ID,
    'app_secret' => FB_FEED_APP_SECRET,
    'default_graph_version' => 'v6.0',
  ]);

  $response = $fb->get(
    FB_FEED_PAGE_ID . '/posts?fields=permalink_url,message,full_picture&limit=3',
    FB_FEED_ACCESS_TOKEN
  );

  $response_body = $response->getDecodedBody();
  $data = $response_body['data'];

  foreach(array_reverse($data) as $item) {
    if (!array_key_exists('permalink_url', $item)) {
      continue;
    }
    $url = $item['permalink_url'];
    $message = $item['message'] ?? NULL;
    $thumbnail_url = $item['full_picture'] ?? NULL;

    $existing_post = get_page_by_title($url, OBJECT, 'fb_feed');
    if ($existing_post !== NULL) {
      continue;
    }

    $post_id = wp_insert_post([
      'post_type' => 'fb_feed',
      'post_status' => 'publish',
      'post_title' => $url,
      'post_content' => $message,

    ]);

    $thumbnail_bits = fb_feed_url_get_contents($thumbnail_url);
    $thumbnail_name = substr($message, 0, 20) . '.jpg';
    $upload_file = wp_upload_bits($thumbnail_name, null, $thumbnail_bits);
    if (!$upload_file['error']) {
      $wp_filetype = wp_check_filetype($thumbnail_name, null );
      $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $thumbnail_name),
        'post_content' => '',
        'post_status' => 'inherit'
      );
      $attachment_id = wp_insert_attachment( $attachment, $upload_file['file']);
      if (!is_wp_error($attachment_id)) {
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
        wp_update_attachment_metadata( $attachment_id,  $attachment_data );

        set_post_thumbnail($post_id, $attachment_id);
      }
    }
    sleep(2);  // Create differing timestamps.
  }
}

add_filter( 'cron_schedules', 'fb_feed_schedule_cron' );
function fb_feed_schedule_cron( $schedules ) {
  $schedules['every_three_hours'] = array(
    'interval' => 60 * 60 * 3,
    //'interval' => 3,
    'display'  => esc_html__( 'Every three hours' ),
  );

  return $schedules;
}

add_action('wp', 'wp_feed_trigger_cron');
function wp_feed_trigger_cron() {
  if ( !wp_next_scheduled( 'fb_feed_cron_event' ) ) {
    wp_schedule_event( time(), 'every_three_hours', 'fb_feed_cron_event');
  }
}

add_action('fb_feed_cron_event', 'fb_feed_fetch_posts');
