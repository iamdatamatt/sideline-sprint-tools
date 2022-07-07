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

  // Authenticate Campaign Monitor API.
  $cm_auth = array("api_key" => $cm_api_key);
  $cm_wrap = new CS_REST_Campaigns(NULL, $cm_auth);

  // Authenticate Bunny CDN storage spaces.
  $bunnyCDNStorage = new BunnyCDNStorage($bunny_storage_name, $bunny_storage_key, "ny");

  // Get data from POST request.
  $subject = $_POST["newsletter_subject"];
  $preheader = $_POST["newsletter_preheader"];
  $group_id = $_POST["newsletter_group"];
  $campaign_html = $_POST["newsletter_html"];
  $campaign_html = str_replace("{NEWSLETTER_TITLE}", $subject, $campaign_html);
  $campaign_html = str_replace("{PREHEADER_TEXT}", $preheader, $campaign_html);
  $campaign_html = str_replace("<!--{BEGIN_AMBASSADOR}-->", "[if:college_ambassador=1]", $campaign_html);
  $campaign_html = str_replace("<!--{END_AMBASSADOR}-->", "[endif]", $campaign_html);
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

  // Push to Campaign Monitor.
  $result = $cm_wrap -> create($cm_client_id, array(
                                                    "Subject" => $subject,
                                                    "Name" => $campaign_name,
                                                    "FromName" => "Sideline Sprint",
                                                    "FromEmail" => "team@sidelinesprint.com",
                                                    "ReplyTo" => "team@sidelinesprint.com",
                                                    "HtmlUrl" => $html_file_url,
                                                    "TextUrl" => "https://public.sidelinesprint.com/newsletters/text/web_version.txt",
                                                    "ListIDs" => array($group_id)
                                                  ));
  if ($result -> was_successful()) {

    // Delete file from server.
    unlink($html_file_path);

    // Alert based on status.
    echo json_encode(array("status" => "success",
                           "message" => "Newsletter created successfully!"));

  } else {

    // Exception based on issue.
    $cm_status_code = $result -> http_status_code;
    throw new Exception("Error creating campaign with Campaign Monitor. HTTP Status Code - " . $cm_status_code);

  }

} catch (Exception $e) {

  // Provide details if there was an error.
  server_alert($e->getMessage());
  if (file_exists($html_file_path)) {
        unlink($html_file_path);
  }
  echo json_encode(array("status" => "failure",
                         "message" => "Newsletter failed to create. See console for more details.",
                         "error" => $e));

}
