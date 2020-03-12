<?php
/**
 * Plugin Name: Facebook Feed
 * Description: A Facebook feed that respects privacy
 * Version: 1.0
 * Author: Philipp Zedler
 * Author URI: https://www.zedler.it
 */

function fb_feed_post_init() {
  $args = array(
    'public' => TRUE,
    'label'  => 'Facebook Feed',
    'supports' => ['title', 'thumbnail'],
    'show_in_menu' => TRUE,
  );
  register_post_type( 'fb_feed', $args );
}
add_action( 'init', 'fb_feed_post_init' );


function get_fb_feed() {
  $args = [
    'post_type' => 'fb_feed',
    'paged' => TRUE,
    'posts_per_page' => 3,
  ];
  ob_start();
  $query = new WP_Query($args);
  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      echo '<a class="fb-feed__image" href="' . get_the_title() . '" target="_blank">';
      the_post_thumbnail();
      echo '</a>';
      the_excerpt();
    }
  }
  wp_reset_postdata();
  $result = ob_get_contents();
  ob_end_clean();
  return $result;
}

function the_fb_feed() {
  echo get_fb_feed();
}

add_shortcode('fb_feed', 'get_fb_feed');


function fb_feed_excerpt_length($length) {
  global $post;
  if ($post->post_type == 'fb_feed')
    return 20;
  else
    return $length;
}
add_filter('excerpt_length', 'fb_feed_excerpt_length');

require_once(__DIR__ . '/cron.php');
