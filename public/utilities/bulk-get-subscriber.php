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
$start_datetime = $_POST["start_datetime"];
$end_datetime = $_POST["end_datetime"];

// Pull user data.
try {

  // Create connection to database.
  $db_cxn = pg_connect($subscriber_db_cxn_str);

  // Pull subscriber data depending on field.
  $db_query = "SELECT email, signup_parameters, signup_source, signup_referrer_header, signup_user_agent, signup_base_url, referred_by_email, first_subscribed_timestamp_utc FROM main_newsletter WHERE first_subscribed_timestamp_utc >= $1 AND first_subscribed_timestamp_utc <= $2";
  $db_result = pg_query_params($db_cxn, $db_query, array($start_datetime, $end_datetime));

  // Close DB connection.
  pg_close($db_cxn);

  // If error, redirect to error page.
  if ($db_result == FALSE) {
    throw new Exception("Failed bulk database subscriber lookup.");
  }

  // Convert results to array.
  $db_array = pg_fetch_all($db_result, PGSQL_ASSOC);

  // Check length of resulting data.
  if (!empty($db_array)) {

    // Return final data.
    $result = array(
      "status" => "success",
      "data" => $db_array,
      "info" => "User validated successfully"
    );

  } else {

    // Return final data.
    $result = array(
      "status" => "no_data_found",
      "data" => NULL,
      "info" => "No data found"
    );

  }

} catch (Exception $e) {

  // Send alert and return failure message.
  server_alert($e->getMessage());
  $result = array(
    "status" => "failure",
    "data" => NULL,
    "info" => "Error validating user"
  );

}

// Adjust result based on status.
if ($result["status"] == "success") {

  echo json_encode(array("status" => "success",
                         "message" => "Successfully loaded subscriber data!",
                         "data" => $result["data"]));
  exit();

} elseif ($result["status"] == "no_data_found") {

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
