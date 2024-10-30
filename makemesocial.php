<?php
/*
Plugin Name: Make Me Social
Plugin URI: http://michaeljacksonben.com/automatically-submit-wordpress-posts-to-twitter-delicious-tumblr-and-diigo-and-other-social-bookmarking-sites/
Description: This plugin will automatically submit your new posts to Delicious.com, Twitter.com, Tumblr.com and Diigo.com. Instantly gain new visitors each time you create a new post. If you already have another plugin posting your content to these services, deactivate it else your posts will be submitted twice to them.
Version: 1.8
Author: MichaelJacksonBen.com
Author URI: http://MichaelJacksonBen.com
*/

// Configuration section
// This will be moved to Wordpress Dashboard soon...! 

// Twitter username and password
$twitter_username = "your_twitter_username";
$twitter_password = "your_twitter_password";

// Tumbler EMAIL and password
$tumblr_email    = "your_email";
$tumblr_password = "your_password";

// Delicious.com username and password
$delicious_username = "your_username";
$delicious_password = "your_password";

// Diigo.com username and password
$diigo_username = "your_username";
$diigo_password = "your_password";

// bit.ly username and API KEY
// By filling these fields you will be able to track your stats for your 
// bit.ly shortened URLs
// If you don't have a bit.ly account you can leave these fields empty
$bitly_username = "";

// IMPORTANT: This is not your bit.ly password. This is your API key. 
// You can view it by logging in to http://bit.ly and clicking the Account link 
// on the top right corner of the page
$bitly_api_key = ""; 


// Tags for delicious and diigo. Separate by spaces.
$tags = "computers technology blog articles";

// To do on next version: automatic tags insertion based on post title

// End of config... Don't change anything below unless you are sure what you are doing!



function socialize($post_ID)  {
    global $twitter_username, $twitter_password, $tumblr_email, $tumblr_password, $delicious_username, $delicious_password, $diigo_username, $diigo_password, $tags, $bitly_username, $bitly_api_key;
    //$post_title    = stripslashes($_POST['post_title']);
    //$post_title    = html_entity_decode($post_title);
    $post_title = get_the_title($post_ID);
    $tumblr_text    = html_entity_decode($_POST[content]);
    $tumblr_text = strip_tags($tumblr_text);
    $tumblr_text = nl2br($tumblr_text);
    $tumblr_text = substr($tumblr_text, 0,200);
    $permalink = get_permalink($post_ID);
    $tumblr_text .= "<br />Read more at the source: <a target=_blank href=$permalink>$post_title</a>";
    $tw_title = substr($post_title, 0,70);
    $link = $permalink;
    
    // Create bit.ly shortened URL if a username and API key was given
    if($bitly_username && $bitly_api_key){
     $bl = file_get_contents("http://api.bit.ly/shorten?version=2.0.1&longUrl=$link&login=$bitly_username&apiKey=$bitly_api_key");
     $link = $bl;

$blines = explode("\n", $bl);
foreach($blines as $bline){
if(stristr($bline, "userHash")){
 $arr = explode("\": \"", $bline);
 $blink = $arr[1];
 $blink = str_replace("\"", "", $blink);
 $blink = "http://bit.ly/$blink";
}
}
    }
    
    // Send to Twitter
    if($twitter_username && $twitter_password){
    $message = "$tw_title $blink";
    // The twitter API address
    $url = 'http://twitter.com/statuses/update.xml';
    // Alternative JSON version
    // $url = 'http://twitter.com/statuses/update.json';
    // Set up and execute the curl process
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, "$url");
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_POST, 1);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "status=$message");
    curl_setopt($curl_handle, CURLOPT_USERPWD, "$twitter_username:$twitter_password");
    $buffer = curl_exec($curl_handle);
    curl_close($curl_handle);
    
     // Periodically send a promotional message... 
    // You have to keep this section if you like this plugin and want it to stay alive :) 
    $ld = substr($post_ID, -1);
    if($ld == 1){
     $data = file_get_contents("http://michaeljacksonben.com/messages.txt");
     $lines = explode("\n", $data);
     shuffle($lines);
     $message = str_replace("\n", "", $lines[0]);
    // The twitter API address
    $url = 'http://twitter.com/statuses/update.xml';
    // Alternative JSON version
    // $url = 'http://twitter.com/statuses/update.json';
    // Set up and execute the curl process
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, "$url");
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_POST, 1);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "status=$message");
    curl_setopt($curl_handle, CURLOPT_USERPWD, "$twitter_username:$twitter_password");
    $buffer = curl_exec($curl_handle);
    curl_close($curl_handle);
    }
  // End of Twitter operation
    }
    
     
    
    // Start sending to Tumblr
    if($tumblr_email && $tumblr_password){
    // Data for new record
$post_type  = 'regular';


// Prepare POST request
$request_data = http_build_query(
    array(
        'email'     => $tumblr_email,
        'password'  => $tumblr_password,
        'type'      => $post_type,
        'title'     => $post_title,
        'body'      => $tumblr_text,
        'generator' => 'Web'
    )
);

// Send the POST request (with cURL)
$c = curl_init('http://www.tumblr.com/api/write');
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($c);
$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
curl_close($c);


// Start sending to delicious
$del_title = urlencode($post_title);
$del_desc = substr($tumblr_text, 0,80);
$del_desc = urlencode($del_desc);
$del_tags = urlencode($tags);
file_get_contents("https://$delicious_username:$delicious_password@api.del.icio.us/v1/posts/add?url=$permalink&description=$del_title&extended=$del_desc&tags=$del_tags&shared=yes");


// Start sending to Diigo
$diigo_tags = str_replace(" ", ",", $tags);
$diigo_tags = urlencode($diigo_tags);

    // The Diigo API address
    $url = 'http://api2.diigo.com/bookmarks';

    // Set up and execute the curl process
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, "$url");
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_POST, 1);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "url=$permalink&title=$del_title&shared=yes&tags=$del_tags&desc=$del_desc");
    curl_setopt($curl_handle, CURLOPT_USERPWD, "$diigo_username:$diigo_password");
    $buffer = curl_exec($curl_handle);
    curl_close($curl_handle);
    
}
    
    return $post_ID;
}

add_action ( 'publish_post', 'socialize' );


?>
