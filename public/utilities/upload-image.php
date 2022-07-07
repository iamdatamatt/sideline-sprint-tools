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
  echo json_encode(array(
    "status" => "failure",
    "message" => "User is not authenticated."
  ));
  exit();
}

// Wrap in try block.
try {

  // Check if errors in file.
  $error_codes = $_FILES['file']['error'];
  if ($error_codes > 0){
        throw new Exception("Error uploading image with PHP code " . $error_codes);
  } else {

    // Set path name.
    $path_name = '/var/www/html/public/temp/' . $_POST['filename'];
    $cdn_name = '/sidelinesprint/images/' . $_POST['filename'];

    // Temporarily save to server.
    move_uploaded_file($_FILES['file']['tmp_name'], $path_name);

    // Upload file to Bunny CDN.
    $bunnyCDNStorage = new BunnyCDNStorage($bunny_storage_name, $bunny_storage_key, "ny");
    $bunnyCDNStorage->uploadFile($path_name, $cdn_name);

    // Delete temp file.
    unlink($path_name);

  }

  // Send status data.
  echo json_encode(array(
    "status" => "success",
    "message" => "Successfully uploaded image!",
    "primary_link" => "https://public.sidelinesprint.com/images/" . $_POST['filename'],
    "secondary_link" => "https://sidelinesprint-public.b-cdn.net/images/" . $_POST['filename']
  ));

} catch (Exception $e) {

  // Redirect on error.
  server_alert($e->getMessage());
  echo json_encode(array(
    "status" => "failure",
    "message" => "Failed to upload image. Please check the console for more details.",
    "error" => $e->getMessage()
  ));
}
