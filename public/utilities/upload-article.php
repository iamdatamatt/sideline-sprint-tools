<?php

// Load in packages.
session_start();
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/vendor/autoload.php";

// Tell Google to get lost.
header("X-Robots-Tag: noindex, nofollow", true);

// Make sure only post requests are accepted.
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("Location: https://tools.sidelinesprint.com/", true, 303);
  exit();
}

// Check for ajax request.
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
  header("Location: https://tools.sidelinesprint.com/", true, 303);
  exit();
}

// Check for valid token.
$key_validation = validate_internal_api_key($_POST["api_key"]);
if ($key_validation["status"] != "success") {
  echo json_encode(array("status" => "failure",
                         "message" => "User is not authenticated."));
  exit();
}

// Wrap in try block.
try {

  // Login to get session cookie.
  $ghost_auth = ["username" => $ghost_username, "password" => $ghost_password];
  $ghost_auth = json_encode($ghost_auth);

  // Get parameters from POST.
  $article_html = $_POST["article_html"];
  $article_title = $_POST["article_title"];
  $article_meta_title = $_POST["article_meta_title"];
  $article_excerpt = $_POST["article_excerpt"];
  $article_authors = $_POST["article_authors"];
  $article_date = $_POST["article_date"];
  $article_time = $_POST["article_time"];
  $article_image = $_POST["article_image"] ?? "";

  // Convert campaign HTML to site HTML.
  $site_html = str_replace('{$url}', '#', $article_html);
  $site_html = str_replace('<webversion>', '<a href="#">', $site_html);
  $site_html = str_replace('</webversion>', '</a>', $site_html);
  $site_html = str_replace('{$unsubscribe}', '#', $site_html);
  $site_html = str_replace('<unsubscribe>', '<a href="#">', $site_html);
  $site_html = str_replace('</unsubscribe>', '</a>', $site_html);
  $site_html = str_replace('{$referral_display_link}', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('[referral_display_link,fallback=sidelinesprint.com]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('[check_referral_link,fallback=#]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/twitter-share?id=[referral_id,fallback=]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/facebook-share?id=[referral_id,fallback=]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/email-share?id=[referral_id,fallback=]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&feedback=yes', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&feedback=meh', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&feedback=no', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&amp;feedback=yes', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&amp;feedback=meh', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/newsletter-feedback?id=[referral_id,fallback=]&amp;feedback=no', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('[referral_count,fallback=0]', '0', $site_html);
  $site_html = str_replace('https://www.sidelinesprint.com/my-referrals?token=[check_referral_id,fallback=]', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('{$check_referrals_link}', 'https://www.sidelinesprint.com', $site_html);
  $site_html = str_replace('<title>', '<!--<title>', $site_html);
  $site_html = str_replace('</title>', '</title>-->', $site_html);
  $site_html = str_replace('<meta name', '<!--<meta name', $site_html);
  $site_html = str_replace('></head>', '>--></head>', $site_html);

  // Create array for authors.
  $authors_array = [];
  foreach ($article_authors as $author) {
      array_push($authors_array, ["id" => $author]);
  }

  // Set status based on post time. Add 4 hours for time zone conversion.
  $date_time = strtotime($article_date . " " . $article_time . ":00") + 14400;
  $published_time = date("Y-m-d\TH:i:s.000\Z", $date_time);

  // Set status based on current time.
  if (gmdate("Y-m-d\TH:i:s\Z") >= $published_time) {
    $status = "published";
  } else {
    $status = "scheduled";
  }

  // Create object for post.
  $post_params = ["posts" => [[
      "title" => $article_title,
      "html" => "<!--kg-card-begin: html-->" . $site_html . "<!--kg-card-end: html-->",
      "feature_image" => $article_image,
      "status" => $status,
      "published_at" => $published_time,
      "excerpt" => $article_excerpt,
      "custom_excerpt" => $article_excerpt,
      "tags" => ["Daily Newsletters"],
      "authors" => $authors_array,
      "meta_title" => $article_meta_title,
      "meta_description" => $article_excerpt
  ]]];
  $post_params = json_encode($post_params, JSON_UNESCAPED_SLASHES);

  # Get session cookie.
  $curl_get_cookie = curl_init();
  curl_setopt_array($curl_get_cookie, array(
      CURLOPT_URL => "-",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $ghost_auth,
      CURLOPT_HTTPHEADER => array(
          "content-type: application/json",
          "origin: https://www.sidelinesprint.com"
      ),
  ));
  $response_get_cookie = curl_exec($curl_get_cookie);
  $err_get_cookie = curl_error($curl_get_cookie);
  curl_close($curl_get_cookie);
  if ($err_get_cookie) {
      throw new Exception("Error getting cookie from Ghost.");
  }

  // Parse cookie to use for post creation.
  $headers = get_headers_from_curl_response($response_get_cookie);
  $ghost_cookie = $headers["Set-Cookie"];

  // Create post using cookie.
  $curl_create_post = curl_init();
  curl_setopt_array($curl_create_post, array(
      CURLOPT_URL => "-",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $post_params,
      CURLOPT_HTTPHEADER => array(
          "Cookie: {$ghost_cookie}",
          "content-type: application/json",
          "origin: https://www.sidelinesprint.com"
      ),
  ));
  $response_create_post = curl_exec($curl_create_post);
  $err_create_post = curl_error($curl_create_post);
  $status_create_post = curl_getinfo($curl_create_post);
  curl_close($curl_create_post);
  if ($err_create_post) {
    throw new Exception("Error creating post with Ghost - curl.");
  }
  $create_post_code = $status_create_post["http_code"];
  if ($create_post_code != 201) {
    throw new Exception("Error creating post with Ghost - status code " . $create_post_code);
  }

  // Alert based on status.
  echo json_encode(array("status" => "success",
                         "message" => "Post created successfully!"));

} catch (Exception $e) {

  // Provide details if there was an error.
  server_alert($e->getMessage());
  echo json_encode(array("status" => "failure",
                         "message" => "Post failed to create. See console for more details.",
                         "error" => $e));

}
