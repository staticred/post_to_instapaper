<?php

$basedir = realpath(dirname(__FILE__));

// Load Composer libraries
require $basedir . '/vendor/autoload.php';

// Grab values out of .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


// Set timezone
date_default_timezone_set("America/Vancouver");

// Reset counter. This will let us track the number of items processed. 
$i = 0;


// Load RSS data (Feeds & last updated date)
if (!$rss_arr = json_decode(file_get_contents($basedir . "/feeds.json"))) {
  die("Can't load JSON file.");
}

// Grab the last updated time, so we can compare incoming feed items against 
// what's already in the system
$last_updated = !empty($rss_arr->last_updated) ? $rss_arr->last_updated : time() - 3600;

$articles = array();

// Loop through each of the feeds
foreach ($rss_arr->feeds as $rss) {
  
  // Grab the URL from the JSON array item and transform it from XML
  $rss_url = $rss->rss_url;
  
  // Try to load the feed and report an error if it doesn't load.
  if (!$rss_feed = simplexml_load_file($rss_url)) {
      echo "Failed to load RSS feed.";
      foreach(libxml_get_errors() as $error) {
          echo "\n", $error->message;
      }
      exit;
  }
  
  // Loop through the RSS feeds
  foreach ($rss_feed as $feed) {
    
    // Loop through each item in the feed
    foreach ($feed->item as $item) {
      // grab the publication date and convert to a timestamp
      $item_time = strtotime($item->pubDate);

      // Compare the publication date to the last time we ran this script. 
      // and if it's older, skip to the next one
      if ($item_time <= $last_updated) {
        continue;
      }
      $articles[$item_time] = $item;

    }
  }
  // set the last_updated time once we've processed all feeds.
  $rss_arr->last_updated = time();    
}

print sizeof($articles) . " articles processed.\n";

ksort($articles);

foreach ($articles as $item) {
  if (!post_to_instapaper($item)) {
    error_log("Could not post {$item->title}");
    return false;
  }
  $i++;
}



// Write back to feeds.json so we can store the last updated timestamp. 
if (!file_put_contents("feeds.json", json_encode($rss_arr))) {
  die("Error writing to JSON");
}
// Print a status report. 
print "Complete - processed {$i} items\n";

function post_to_instapaper($item) {
  
  // Instapaper Simple API URL:
  $insta_url = "https://www.instapaper.com/api/add";
  
  $post = [
    "username" => $_ENV['INSTA_USER'],
    "password" => $_ENV['INSTA_PW'],
    "title" => (string) $item->title,
    "url" => (string) $item->link,
    "selection" => (string) substr(strip_tags($item->description),0,255)
  ];
  
  // Send the request and catch any errors
  $ch = curl_init($insta_url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
  if (!$output = curl_exec($ch)) {
    print "Curl error: " . curl_error($ch) . "\n";
    return false;
  }

  return true;
  
  
}




