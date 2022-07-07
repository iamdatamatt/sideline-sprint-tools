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
  echo json_encode(array(
    "status" => "failure",
    "message" => "User is not authenticated."
  ));
  exit();
}

// Wrap in try block.
try {

  // Parse variables.
  $email = empty_to_null($_POST["subscriber_email"]);
  $status = empty_to_null($_POST["subscriber_status"]);
  $college_ambassador = empty_to_null($_POST["subscriber_college_ambassador"]);

  // Create connection to database.
  $db_cxn = pg_connect($subscriber_db_cxn_str);

  // Authenticate Campaign Monitor API.
  $cm_auth = array("api_key" => $cm_api_key);
  $cm_wrap = new CS_REST_Subscribers($main_subscriber_list_id, $cm_auth);

  // Get current info for comparison.
  $data_query = "SELECT *
                 FROM main_newsletter
                 WHERE email = $1";
  $data_result = pg_query_params(
    $db_cxn,
    $data_query,
    array($email)
  );
  if (($data_result == false) || (pg_num_rows($data_result) != 1)) {
    throw new Exception("Error checking database for subscriber.");
  }

  // Grab data.
  $data_array = pg_fetch_array($data_result, 0, PGSQL_ASSOC);
  $current_status = $data_array["status"];

  // Send update to Campaign Monitor & database based on status.
  $cm_custom_fields = array(
    "CustomFields" => array(
      array("Key" => "college_ambassador", "Value" => $college_ambassador)
    ),
    "ConsentToTrack" => "unchanged",
    "Resubscribe" => false,
    "RestartSubscriptionBasedAutoResponders" => false
  );
  $cm_result = $cm_wrap->update($email, $cm_custom_fields);

  // If successful, move on; otherwise, raise error.
  if (!($cm_result->was_successful())) {
    $cm_status_code = $cm_result->http_status_code;
    // throw new Exception("Error updating subscriber fields with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    // fail quietly
  }

  // Update custom fields in database.
  $update_query = "UPDATE main_newsletter
                   SET college_ambassador = $1
                   WHERE email = $2";
  $update_result = pg_query_params(
    $db_cxn,
    $update_query,
    array(
      $college_ambassador,
      $email
    )
  );
  if ($update_result == false) {
    throw new Exception("Error updating subscriber fields in database.");
  }

  if ($current_status == $status) {

    // Do nothing

  } else if (($current_status == "Active") && ($status == "Banned")) {

    // Send update to database.
    $update_query = "UPDATE main_newsletter
                     SET status = $1
                     WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }

    // Unsubscribe subscriber with Campaign Monitor.
    $cm_result = $cm_wrap->unsubscribe($email);

    // If successful, move on; otherwise, raise error.
    if (!($cm_result->was_successful())) {
      $cm_status_code = $cm_result->http_status_code;
      throw new Exception("Error unsubscribing user with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    }
  } else if (($current_status == "Active") && ($status == "Inactive")) {

    // Unsubscribe subscriber with Campaign Monitor.
    // No need to update database as webhook will handle this.
    $cm_result = $cm_wrap->unsubscribe($email);

    // If successful, move on; otherwise, raise error.
    if (!($cm_result->was_successful())) {
      $cm_status_code = $cm_result->http_status_code;
      throw new Exception("Error unsubscribing user with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    }
  } else if (($current_status == "Banned") && ($status == "Active")) {

    // Reactivate subscriber with Campaign Monitor.
    $cm_result = $cm_wrap->update(
      $email,
      array(
        "Resubscribe" => true,
        "ConsentToTrack" => "unchanged",
        "RestartSubscriptionBasedAutoResponders" => false
      )
    );

    // If successful, move on; otherwise, raise error.
    if (!($cm_result->was_successful())) {
      $cm_status_code = $cm_result->http_status_code;
      throw new Exception("Error reactivating user with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    }

    // Reactivate subscriber in database.
    $resubscribed_timestamp_utc =  gmdate("Y-m-d H:i:s");
    $resubscribe_query = "UPDATE main_newsletter
                          SET status = $1,
                          number_times_resubscribed = number_times_resubscribed + 1,
                          resubscribed_timestamp_utc = $2
                          WHERE email = $3";
    $resubscribe_result = pg_query_params(
      $db_cxn,
      $resubscribe_query,
      array(
        $status,
        $resubscribed_timestamp_utc,
        $email
      )
    );
    if ($resubscribe_result == false) {
      throw new Exception("Error resubscribing user in database.");
    }
  } else if (($current_status == "Inactive") && ($status == "Active")) {

    // Reactivate subscriber with Campaign Monitor.
    $cm_result = $cm_wrap->update(
      $email,
      array(
        "Resubscribe" => true,
        "ConsentToTrack" => "unchanged",
        "RestartSubscriptionBasedAutoResponders" => false
      )
    );

    // If successful, move on; otherwise, raise error.
    if (!($cm_result->was_successful())) {
      $cm_status_code = $cm_result->http_status_code;
      throw new Exception("Error reactivating user with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    }

    // Reactivate subscriber in database.
    $resubscribed_timestamp_utc =  gmdate("Y-m-d H:i:s");
    $resubscribe_query = "UPDATE main_newsletter
                          SET status = $1,
                          number_times_resubscribed = number_times_resubscribed + 1,
                          resubscribed_timestamp_utc = $2
                          WHERE email = $3";
    $resubscribe_result = pg_query_params(
      $db_cxn,
      $resubscribe_query,
      array(
        $status,
        $resubscribed_timestamp_utc,
        $email
      )
    );
    if ($resubscribe_result == false) {
      throw new Exception("Error resubscribing user in database.");
    }
  } else if (($current_status == "Banned") && ($status == "Inactive")) {

    // Send update to database.
    $update_query = "UPDATE main_newsletter
                     SET status = $1
                     WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }
  } else if (($current_status == "Inactive") && ($status == "Banned")) {

    // Send update to database.
    $update_query = "UPDATE main_newsletter
                     SET status = $1
                     WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }
  } else if (($current_status == "Unconfirmed") && ($status == "Inactive")) {

    // Send update to database.
    $update_query = "UPDATE main_newsletter
                     SET status = $1
                     WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }
  } else if (($current_status == "Unconfirmed") && ($status == "Banned")) {

    // Send update to database.
    $update_query = "UPDATE main_newsletter
                     SET status = $1
                     WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }
  } else if (($current_status == "Unconfirmed") && ($status == "Active")) {

    // Run confirmation flow
    $referral_id = $data_array["referral_id"];
    $subscriber_referred_by_id = $data_array["referred_by_id"];
    if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {
      $confirm_result = confirm($referral_id, TRUE);
    } else {
      $confirm_result = confirm($referral_id, FALSE);
    }
    if ($confirm_result["status"] != "success") {
      throw new Exception("Failed to confirm user.");
    }
  } else if (($current_status == "Active") && ($status == "Unconfirmed")) {

    // Update DB
    $update_query = "UPDATE main_newsletter
    SET status = $1
    WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }

    // Unsubscribe subscriber with Campaign Monitor.
    $cm_result = $cm_wrap->unsubscribe($email);

    // If successful, move on; otherwise, raise error.
    if (!($cm_result->was_successful())) {
      $cm_status_code = $cm_result->http_status_code;
      throw new Exception("Error unsubscribing user with Campaign Monitor. HTTP Status Code - " . $cm_status_code);
    }

    // Send confirmation emial.
    $referral_id = $data_array["referral_id"];
    $subscriber_referred_by_id = $data_array["referred_by_id"];
    if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {
      send_confirmation_email($email, $referral_id, TRUE);
    } else {
      send_confirmation_email($email, $referral_id, FALSE);
    }
  } else if (($current_status == "Inactive") && ($status == "Unconfirmed")) {

    // Update DB
    $update_query = "UPDATE main_newsletter
                      SET status = $1
                      WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }

    // Send confirmation emial.
    $referral_id = $data_array["referral_id"];
    $subscriber_referred_by_id = $data_array["referred_by_id"];
    if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {
      send_confirmation_email($email, $referral_id, TRUE);
    } else {
      send_confirmation_email($email, $referral_id, FALSE);
    }
  } else if (($current_status == "Banned") && ($status == "Unconfirmed")) {

    // Update DB
    $update_query = "UPDATE main_newsletter
    SET status = $1
    WHERE email = $2";
    $update_result = pg_query_params(
      $db_cxn,
      $update_query,
      array(
        $status,
        $email
      )
    );
    if ($update_result == false) {
      throw new Exception("Error updating status in database.");
    }

    // Send confirmation emial.
    $referral_id = $data_array["referral_id"];
    $subscriber_referred_by_id = $data_array["referred_by_id"];
    if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {
      send_confirmation_email($email, $referral_id, TRUE);
    } else {
      send_confirmation_email($email, $referral_id, FALSE);
    }
  }

  // Close DB connection.
  pg_close($db_cxn);

  // Send status data.
  echo json_encode(array(
    "status" => "success",
    "message" => "Successfully updated subscriber!"
  ));
} catch (Exception $e) {

  // Redirect on error.
  server_alert($e->getMessage());
  echo json_encode(array(
    "status" => "failure",
    "message" => "Failed to update subscriber. Please check the console for more details.",
    "error" => $e->getMessage()
  ));
}
