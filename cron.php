<?php

require_once __DIR__ . '/vendor/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => FB_FEED_APP_ID,
  'app_secret' => FB_FEED_APP_SECRET,
  'default_graph_version' => 'v6.0',
]);

$response = $fb->get(FB_FEED_PAGE_ID . '/posts', FB_FEED_ACCESS_TOKEN);

print_r($response);