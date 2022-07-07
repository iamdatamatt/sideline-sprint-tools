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

// Make sure only emails set.
if (!isset($_POST["email"])) {
  echo json_encode(array("status" => "failure",
                         "message" => "Missing email address."));
  exit();
}

// Get email from POST variables.
$cleaned_email = clean_email($_POST["email"]);
if ($cleaned_email["is_valid"]) {
  $signup_email = $cleaned_email["email"];
} else {
  echo json_encode(array("status" => "failure",
                         "message" => "Email address is not a valid format.",
                         "error" => $cleaned_email["email"]));
  exit();
}
$signup_ip_address = strval($_SERVER["REMOTE_ADDR"]) ?? NULL;
$signup_source = "internal-signup-tool";

// Subscribe user.
$result = subscribe($signup_email,
                    $signup_ip_address,
                    $signup_source);

// Set flow based on results.
if ($result["status"] == "success") {

  // Redirect to confirmed page.
  echo json_encode(array("status" => "success",
                         "message" => "Subscriber added successfully!"));
  exit();

} elseif ($result["status"] == "already_exists") {

  echo json_encode(array("status" => "already_active",
                         "message" => "Subscriber already exists."));
  exit();

} elseif ($result["status"] == "banned") {

  echo json_encode(array("status" => "banned",
                         "message" => "Subscriber is banned."));
  exit();

} elseif ($result["status"] == "failure") {

  echo json_encode(array("status" => "failure",
                         "message" => "Failed to add subscriber. Please check the console for more details.",
                         "error" => $result["info"]));
  exit();

} else {

  echo json_encode(array("status" => "failure",
                         "message" => "Failed to add subscriber. Please check the console for more details.",
                         "error" => "Unknown error"));
  exit();

}
