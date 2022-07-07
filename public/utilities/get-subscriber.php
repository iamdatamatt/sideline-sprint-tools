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

// Get fields from request.
$lookup_field = $_POST["lookup_field"];
$lookup_value = $_POST["lookup_value"];

// Pull user data.
$result = get_user_info($lookup_value, $lookup_field);

// Adjust result based on status.
if ($result["status"] == "success") {

  echo json_encode(array("status" => "success",
                         "message" => "Successfully loaded subscriber data!",
                         "data" => $result["data"]));
  exit();

} elseif ($result["status"] == "redirect_home") {

  echo json_encode(array("status" => "no_data_found",
                         "message" => "No data found for search parameters."));
  exit();

} elseif ($result["status"] == "failure") {

  echo json_encode(array("status" => "failure",
                         "message" => "Failed to get subscriber."));
  exit();

} else {

  echo json_encode(array("status" => "failure",
                         "message" => "Unknown error"));
  exit();

}

?>
