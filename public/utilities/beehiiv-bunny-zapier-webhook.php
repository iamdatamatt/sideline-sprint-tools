<?php

// Load in packages.
session_start();
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/secure/bunnycdn-storage.php";
require_once "/var/www/html/vendor/autoload.php";

// Tell Google to get lost.
header("X-Robots-Tag: noindex, nofollow", true);

// Make sure only post requests are accepted.
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  http_response_code(405);
  exit();
}

// Check for API key.
if (!isset($_POST["api_key"])) {
  http_response_code(401);
  exit();
}

// Check for valid token.
if ($_POST["api_key"] != "-") {
  http_response_code(401);
  exit();
}

// Make sure HTML is sent.
if (!isset($_POST["newsletter_html"])) {
  http_response_code(400);
  exit();
}

// Make sure subject is sent.
if (!isset($_POST["newsletter_subject"])) {
  http_response_code(400);
  exit();
}

// Wrap in try block.
try {

  // Authenticate Bunny CDN storage spaces.
  $bunnyCDNStorage = new BunnyCDNStorage($bunny_storage_name, $bunny_storage_key, "ny");

  // Get data from POST request.
  $campaign_html = $_POST["newsletter_html"];
  /*

  Any processing of body HTML goes here.

  */
  $subject = $_POST["newsletter_subject"];
  $campaign_name = mb_convert_encoding($subject, "UTF-8");
  $campaign_name = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $campaign_name));
  $campaign_name = preg_replace("#[[:punct:]]#", "", $campaign_name);
  $campaign_name = $campaign_name . " " . time();

  // Save HTML to file.
  $folder_year = date("Y");
  $folder_month = date("m");
  $file_folder = "/var/www/html/public/temp";
  $html_file_name = urlencode($campaign_name . ".html");
  $html_file_path = $file_folder . "/" . $html_file_name;
  $html_file_url = "https://tools.sidelinesprint.com/temp/" . $html_file_name;

  // Make folders if they don't exist and save.
  if (!is_dir($file_folder)) {
    mkdir($file_folder, 0777, true);
  }
  file_put_contents($html_file_path, $campaign_html);
  chmod($html_file_path, 0777);

  // Push to Bunny CDN.
  $html_cdn_path = "/sidelinesprint/newsletters/html/" . $folder_year . "/" . $folder_month . "/" . $html_file_name;
  $bunnyCDNStorage->uploadFile($html_file_path, $html_cdn_path);

  // Delete file from server.
  unlink($html_file_path);

  // Alert based on status.
  http_response_code(200);
  exit();

} catch (Exception $e) {

  // Provide details if there was an error.
  server_alert($e->getMessage());
  if (file_exists($html_file_path)) {
        unlink($html_file_path);
  }
  http_response_code(500);
  exit();

}
