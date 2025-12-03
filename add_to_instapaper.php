<?php

// Load Composer libraries
require __DIR__ . '/vendor/autoload.php';

// Grab values out of .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


// Set timezone
date_default_timezone_set("America/Vancouver");

// Reset counter. This will let us track the number of items processed. 
$i = 0;

// Instapaper Simple API URL:
$insta_url = "https://www.instapaper.com/api/add";

// Load RSS data (Feeds & last updated date)
if (!$rss_arr = json_decode(file_get_contents("feeds.json"))) {
  die("Can't load JSON file.");
}

// Grab the last updated time, so we can compare incoming feed items against 
// what's already in the system
$last_updated = !empty($rss_arr->last_updated) ? $rss_arr->last_updated : time() - 3600;

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
      if (strtotime($item->pubDate) <= $last_updated) {
        continue;
      }

      // var_dump($item->link[0]);

      // OK, we have a new item. Let's post it to Instapaper. 
      // Build a POST array
      $post = [
        "username" => $_ENV['INSTA_USER'],
        "password" => $_ENV['INSTA_PW'],
        "title" => (string) $item->title,
        "url" => (string) $item->link,
        "selection" => (string) strip_tags($item->description)
      ];
      
      // Send the request and catch any errors
      $ch = curl_init($insta_url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      if (!$output = curl_exec($ch)) {
        print "Curl error: " . curl_error($ch) . "\n";
      }

      print ($output . "\n");
      
      
      $i++;     
    }
  }
  // set the last_updated time once we've processed all feeds.
//  $rss_arr->last_updated = time();    
}

// Write back to feeds.json so we can store the last updated timestamp. 
if (!file_put_contents("feeds.json", json_encode($rss_arr))) {
  die("Error writing to JSON");
}
// Print a status report. 
print "Complete - processed {$i} items\n";






