<?php

// Required packages.
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/vendor/autoload.php";
$confirmation_html = file_get_contents("/var/www/html/secure/confirmation-html.html");
$confirmation_text = file_get_contents("/var/www/html/secure/confirmation-text.txt");

use Hashids\Hashids;
use Postmark\PostmarkClient;

// Wrapper to send email on event.
function server_alert($message)
{

  // Define global variables.
  global $postmark_server_alert_stream_id;

  // Define email variables.
  $client = new PostmarkClient($postmark_server_alert_stream_id);
  $fromEmail = "-";
  $toEmail = "-";
  $subject = "Server Alert";
  $htmlBody = $message;
  $textBody = "Please view the HTML content of this email for the message alert.";
  $tag = "server-alerts";
  $trackOpens = true;
  $trackLinks = "None";
  $messageStream = "server-alerts";

  // Send an email:
  $sendResult = $client->sendEmail(
    $fromEmail,
    $toEmail,
    $subject,
    $htmlBody,
    $textBody,
    $tag,
    $trackOpens,
    NULL, // Reply To
    NULL, // CC
    NULL, // BCC
    NULL, // Header array
    NULL, // Attachment array
    $trackLinks,
    NULL, // Metadata array
    $messageStream
  );

}

// Validate internal API key.
function validate_internal_api_key($api_key)
{
  // Declare global variables.
  global $staff_db_cxn_str;

  // Wrap in try block.
  try {

    // Create connection to database.
    $db_cxn = pg_connect($staff_db_cxn_str);

    // Pull subscriber data depending on field.
    $db_query = "SELECT * FROM staff WHERE internal_api_key = $1";
    $db_result = pg_query_params($db_cxn, $db_query, array($api_key));

    // Close DB connection.
    pg_close($db_cxn);

    // If error, redirect to error page.
    if ($db_result == FALSE) {
      throw new Exception("Failed database lookup for api key " . $api_key);
    }

    // Make sure only one row for results.
    if (pg_num_rows($db_result) != 1) {
      $result = array(
        "status" => "failure",
        "data" => NULL,
        "info" => "API key does not exist"
      );
      return $result;
    }

    // Return final data.
    $result = array(
      "status" => "success",
      "data" => NULL,
      "info" => "API key validated successfully"
    );
    return $result;

  } catch (Exception $e) {

    // Send alert and return failure message.
    server_alert($e->getMessage());
    $result = array(
      "status" => "failure",
      "data" => NULL,
      "info" => "Error validating API key"
    );
    return $result;
  }

}

// Validate staff member.
function validate_staff($email)
{
  // Declare global variables.
  global $staff_db_cxn_str;

  // Wrap in try block.
  try {

    // Create connection to database.
    $db_cxn = pg_connect($staff_db_cxn_str);

    // Pull subscriber data depending on field.
    $db_query = "SELECT * FROM staff WHERE email = $1";
    $db_result = pg_query_params($db_cxn, $db_query, array($email));

    // Close DB connection.
    pg_close($db_cxn);

    // If error, redirect to error page.
    if ($db_result == FALSE) {
      throw new Exception("Failed database lookup for staff " . $email);
    }

    // Make sure only one row for results.
    if (pg_num_rows($db_result) != 1) {
      $result = array(
        "status" => "failure",
        "data" => NULL,
        "info" => "Staff does not exist"
      );
      return $result;
    }

    // Convert results to array.
    $db_array = pg_fetch_array($db_result, 0, PGSQL_ASSOC);

    // Return final data.
    $result = array(
      "status" => "success",
      "data" => $db_array,
      "info" => "User validated successfully"
    );
    return $result;

  } catch (Exception $e) {

    // Send alert and return failure message.
    server_alert($e->getMessage());
    $result = array(
      "status" => "failure",
      "data" => NULL,
      "info" => "Error validating staff"
    );
    return $result;
  }

}

// Generate OAuth token to interface with web tools.
function generate_oauth_token()
{

  // Define global variables.
  global $AUTH0_CLIENT_ID, $AUTH0_CLIENT_SECRET, $AUTH0_TOKEN_ENDPOINT, $AUTH0_AUDIENCE, $AUTH0_GRANT_TYPE;

  // Wrap in try block.
  try {

    // Generate authorization token.
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $AUTH0_TOKEN_ENDPOINT,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"client_id\":\"{$AUTH0_CLIENT_ID}\",\"client_secret\":\"{$AUTH0_CLIENT_SECRET}\",\"audience\":\"{$AUTH0_AUDIENCE}\",\"grant_type\":\"{$AUTH0_GRANT_TYPE}\"}",
      CURLOPT_HTTPHEADER => array(
        "content-type: application/json"
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      throw new Exception("Error generating token with Auth0.");
    } else {
      $json_response = json_decode($response, true);
      $token = $json_response["access_token"];
      return array("status" => "success", "token" => $token);
    }

  } catch (Exception $e) {

    // Provide details if there was an error.
    server_alert($e->getMessage());
    return json_encode(array("status" => "failure",
                             "message" => "Failed to get authorization token. See console for more details.",
                             "error" => $e->getMessage()));

  }
}

// Parse CURL headers.
function get_headers_from_curl_response($response)
{

  // Parse text from headers.
  $headers = array();
  $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

  // Format each individual line.
  foreach (explode("\r\n", $header_text) as $i => $line) {
    if ($i === 0) {
      $headers["http_code"] = $line;
    } else {
      list($key, $value) = explode(": ", $line);
      $headers[$key] = $value;
    }
  }

  // Return parsed headers.
  return $headers;
}

// Set URL based on query string.
function query_string_passthrough($url, $param_array = NULL)
{

  // Parse array to string.
  $query_string = http_build_query($param_array) ?? NULL;

  // Append to URL if not empty.
  if ((!empty($query_string)) && ($query_string != "")) {
    $url = $url . "&" . $query_string;
  }

  // Return url.
  return $url;
}

// Convert empty strings to nulls for database.
function empty_to_null($value)
{

  // Quick comparison.
  if (trim($value) === "") {
    return NULL;
  } else {
    return $value;
  }

}

// Generate token for interacting with tools.
function generate_interactive_token($user_info_array)
{

  // Extract elements, combine, and return.
  $sub = $user_info_array["sub"];
  $aud = $user_info_array["aud"];
  $nonce = $user_info_array["nonce"];
  $token = $sub . "." . $aud . "." . $nonce;
  return $token;

}

// Confirmation email for referrals.
function send_confirmation_email($subscriber_email, $referral_id, $referral = FALSE)
{

  // Define global variables.
  global $confirmation_html, $confirmation_text, $cm_api_key, $cm_client_id;

  // Replace variables.
  if ($referral) {
    $confirm_subscription_url = "https://www.sidelinesprint.com/referral-confirmation?id=" . $referral_id;
  } else {
    $confirm_subscription_url = "https://www.sidelinesprint.com/subscription-confirmation?id=" . $referral_id;
  }
  $confirmation_html = str_replace("{CONFIRM_SUBSCRIPTION_URL}", $confirm_subscription_url, $confirmation_html);
  $confirmation_text = str_replace("{CONFIRM_SUBSCRIPTION_URL}", $confirm_subscription_url, $confirmation_text);

  // Authenticate with Campaign Monitor.
  $cm_auth = array("api_key" => $cm_api_key);
  $cm_wrap = new CS_REST_Transactional_ClassicEmail($cm_auth, $cm_client_id);

  // Create message body.
  $confirmation_message = array(
    "From" => "Sideline Sprint <team@sidelinesprint.com>",
    "Subject" => "Confirm your subscription to Sideline Sprint",
    "To" => array($subscriber_email),
    "HTML" => $confirmation_html,
    "Text" => $confirmation_text
  );
  $group_name = "Confirmation Email";
  $consent_to_track = "unchanged";

  // Send message.
  $result = $cm_wrap->send($confirmation_message, $group_name, $consent_to_track);
}

// Sign up new user.
function subscribe($subscriber_email, $subscriber_signup_ip_address = NULL, $subscriber_signup_source = NULL, $subscriber_signup_params = NULL, $subscriber_referred_by = NULL)
{

  // Define global variables.
  global $referral_hash_salt, $check_referral_hash_salt, $cm_api_key, $main_subscriber_list_id, $subscriber_db_cxn_str;

  // Wrap in try block to catch errors.
  try {

    // Substring signup parameters.
    if (!empty($subscriber_signup_params)) {
      $subscriber_signup_params = substr($subscriber_signup_params, 0, 1024);
    }
    if ($subscriber_signup_params == "") {
      $subscriber_signup_params = NULL;
    }

    // Create hash functions for ID strings.
    $referral_hash = new Hashids($referral_hash_salt);
    $check_referral_hash = new Hashids($check_referral_hash_salt);

    // Authenticate Campaign Monitor API.
    $cm_auth = array("api_key" => $cm_api_key);
    $cm_wrap = new CS_REST_Subscribers($main_subscriber_list_id, $cm_auth);

    // Create connection to database.
    $db_cxn = pg_connect($subscriber_db_cxn_str);

    // Check if subscriber already exists.
    $subscriber_exists_query = "SELECT *
                                FROM main_newsletter
                                WHERE email = $1";
    $subscriber_exists_result = pg_query_params(
      $db_cxn,
      $subscriber_exists_query,
      array($subscriber_email)
    );

    // If error, throw exception.
    if ($subscriber_exists_result == FALSE) {
      $internal_status_code = 901;
      throw new Exception("Error checking database for subscriber.");
    }

    // Set default values for referrer.
    $subscriber_referred_by_id = NULL;
    $subscriber_referred_by_email = NULL;
    $update_referrer_flag = FALSE;

    // Update referrer values if set and valid.
    if ((!empty($subscriber_referred_by)) && ($subscriber_referred_by != "")) {

      // Check if referrer exists.
      $referrer_exists_query = "SELECT *
                                FROM main_newsletter
                                WHERE referral_id = $1";
      $referrer_exists_result = pg_query_params(
        $db_cxn,
        $referrer_exists_query,
        array($subscriber_referred_by)
      );

      // If error, throw exception.
      if ($referrer_exists_result == FALSE) {
        $internal_status_code = 902;
        throw new Exception("Error checking database for referrer.");
      }

      // Set update flag based on referrer status.
      if (pg_num_rows($referrer_exists_result) == 1) {
        $referrer_array = pg_fetch_array($referrer_exists_result, 0, PGSQL_ASSOC);
        if ($referrer_array["status"] == "Active") {
          $update_referrer_flag = TRUE;
          $subscriber_referred_by_id = $referrer_array["unique_id"];
          $subscriber_referred_by_email = $referrer_array["email"];
        }
      }
    }

    // Adjust flow based on whether subscriber already exists or not.
    if (pg_num_rows($subscriber_exists_result) == 0) {

      // NEW SUBSCRIBER FLOW
      // Create failsafe for while loop.
      $failsafe = 0;

      // Verify that IDs don't currently exist in the database.
      while (TRUE) {

        // If too many iterations, throw exception.
        if ($failsafe >= 10) {
          $internal_status_code = 903;
          throw new Exception("Unable to generate unique subscriber ID.");
        }

        // Generate unique ids for new subscriber.
        $subscriber_unique_id = intval(microtime(TRUE) * 1000) . rand();
        $subscriber_referral_id = $referral_hash->encode($subscriber_unique_id);
        $subscriber_check_referral_id = $check_referral_hash->encode($subscriber_unique_id);

        // Make sure IDs do not exist in database.
        $subscriber_id_query = "SELECT count(*)
                                FROM main_newsletter
                                WHERE unique_id = $1
                                OR referral_id = $2
                                OR check_referral_id = $3";
        $subscriber_id_result = pg_query_params(
          $db_cxn,
          $subscriber_id_query,
          array(
            $subscriber_unique_id,
            $subscriber_referral_id,
            $subscriber_check_referral_id
          )
        );

        // If error, throw exception.
        if ($subscriber_id_result == FALSE) {
          $internal_status_code = 904;
          throw new Exception("Error checking database for unique subscriber ID.");
        }

        // If IDs are unique, move on; otherwise, generate again.
        $subscriber_id_result_array = pg_fetch_array($subscriber_id_result, 0, PGSQL_ASSOC);
        if ($subscriber_id_result_array["count"] == 0) {
          break;
        }

        // Increment failsafe.
        ++$failsafe;
      }

      // Create remaining fields for subscriber.
      if ($update_referrer_flag) {
        $subscriber_status = "Unconfirmed";
      } else {
        $subscriber_status = "Active";
      }
      $subscriber_referral_link = "https://www.sidelinesprint.com/refer?id={$subscriber_referral_id}";
      $subscriber_referral_display_link = "sidelinesprint.com/refer?id={$subscriber_referral_id}";
      $subscriber_check_referral_link = "https://www.sidelinesprint.com/my-referrals?token={$subscriber_check_referral_id}";
      $subscriber_check_referral_display_link = "sidelinesprint.com/my-referrals?token={$subscriber_check_referral_id}";
      $subscriber_first_subscribed_timestamp_utc = gmdate("Y-m-d H:i:s");
      $subscriber_referral_count = 0;

      // Push subscriber data to Campaign Monitor via API.
      if ($update_referrer_flag) {
        // Do nothing. Will be done during confirmation.
      } else {
        $subscriber_cm_custom_fields = array(
          array("Key" => "unique_id", "Value" => $subscriber_unique_id),
          array("Key" => "referral_id", "Value" => $subscriber_referral_id),
          array("Key" => "check_referral_id", "Value" => $subscriber_check_referral_id),
          array("Key" => "referral_link", "Value" => $subscriber_referral_link),
          array("Key" => "referral_display_link", "Value" => $subscriber_referral_display_link),
          array("Key" => "check_referral_link", "Value" => $subscriber_check_referral_link),
          array("Key" => "check_referral_display_link", "Value" => $subscriber_check_referral_display_link),
          array("Key" => "signup_parameters", "Value" => $subscriber_signup_params),
          array("Key" => "signup_source", "Value" => $subscriber_signup_source),
          array("Key" => "referred_by_id", "Value" => $subscriber_referred_by_id),
          array("Key" => "referred_by_email", "Value" => $subscriber_referred_by_email),
          array("Key" => "referral_count", "Value" => $subscriber_referral_count),
          array("Key" => "signup_ip_address", "Value" => $subscriber_signup_ip_address)
        );
        $subscriber_cm_result = $cm_wrap->add(array(
          "EmailAddress" => $subscriber_email,
          "CustomFields" => $subscriber_cm_custom_fields,
          "ConsentToTrack" => "unchanged"
        ));

        // If unsuccessful, raise error.
        if (!($subscriber_cm_result->was_successful())) {
          $internal_status_code = 905;
          $cm_status_code = $subscriber_cm_result->http_status_code;
          throw new Exception("Error pushing data to Campaign Monitor with code " . $cm_status_code);
        }
      }

      // Push subscriber data to subscriber database.
      $subscriber_add_query = "INSERT INTO main_newsletter
                                (email, status, unique_id, referral_id, check_referral_id,
                                referral_link, referral_display_link, check_referral_link,
                                check_referral_display_link, signup_parameters, signup_source,
                                referred_by_id, referred_by_email, referral_count, first_subscribed_timestamp_utc,
                                signup_ip_address)
                                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)";
      $subscriber_add_result = pg_query_params(
        $db_cxn,
        $subscriber_add_query,
        array(
          $subscriber_email, $subscriber_status, $subscriber_unique_id, $subscriber_referral_id,
          $subscriber_check_referral_id, $subscriber_referral_link,
          $subscriber_referral_display_link, $subscriber_check_referral_link,
          $subscriber_check_referral_display_link, $subscriber_signup_params,
          $subscriber_signup_source, $subscriber_referred_by_id, $subscriber_referred_by_email,
          $subscriber_referral_count, $subscriber_first_subscribed_timestamp_utc, $subscriber_signup_ip_address
        )
      );

      // If error, throw exception.
      if ($subscriber_add_result == FALSE) {
        $internal_status_code = 906;
        throw new Exception("Error creating subscriber in database.");
      }

      // Only process if update referrer flag is true.
      if ($update_referrer_flag) {

        // Send transactional email to confirm subscription.
        send_confirmation_email($subscriber_email, $subscriber_referral_id, TRUE);

        // Update values for referrer.
        $referrer_email = $referrer_array["email"];
        $referrer_users_referred_id = trim($referrer_array["unconfirmed_users_referred_id"] . "," . $subscriber_unique_id, ",");
        $referrer_users_referred_email = trim($referrer_array["unconfirmed_users_referred_email"] . "," . $subscriber_email, ",");

        // Update referrer values in database.
        $referrer_update_query = "UPDATE main_newsletter
                                   SET unconfirmed_users_referred_id = $1,
                                   unconfirmed_users_referred_email = $2
                                   WHERE email = $3";
        $referrer_update_result = pg_query_params(
          $db_cxn,
          $referrer_update_query,
          array(
            $referrer_users_referred_id,
            $referrer_users_referred_email,
            $referrer_email
          )
        );

        // If error, throw exception.
        if ($referrer_update_result == FALSE) {
          $internal_status_code = 908;
          throw new Exception("Error updating referrer in database.");
        }
      }

      // Close DB connection.
      pg_close($db_cxn);

      // Return results.
      if ($update_referrer_flag) {
        $return_status = "unconfirmed";
      } else {
        $return_status = "success";
      }
      $internal_status_code = 1000;
      $http_status_code = 200;
      $subscribe_result = array(
        "status" => $return_status,
        "http_status_code" => $http_status_code,
        "internal_status_code" => $internal_status_code,
        "info" => "Successfully added subscriber",
        "email" => $subscriber_email,
        "unique_id" => $subscriber_unique_id,
        "referral_id" => $subscriber_referral_id,
        "check_referral_id" => $subscriber_check_referral_id
      );
      return $subscribe_result;
    } elseif (pg_num_rows($subscriber_exists_result) == 1) {

      // EXISTING SUBSCRIBER FLOW
      // Convert results to array.
      $subscriber_exists_array = pg_fetch_array($subscriber_exists_result, 0, PGSQL_ASSOC);
      $subscriber_unique_id = $subscriber_exists_array["unique_id"];
      $subscriber_referral_id = $subscriber_exists_array["referral_id"];
      $subscriber_check_referral_id = $subscriber_exists_array["check_referral_id"];

      // If they are currently active, adjust behavior.
      if ($subscriber_exists_array["status"] == "Active") {

        $http_status_code = 200;
        $internal_status_code = 601;
        $subscribe_result = array(
          "status" => "already_exists",
          "http_status_code" => $http_status_code,
          "internal_status_code" => $internal_status_code,
          "info" => "Subscriber already exists",
          "email" => $subscriber_email,
          "unique_id" => $subscriber_unique_id,
          "referral_id" => $subscriber_referral_id,
          "check_referral_id" => $subscriber_check_referral_id
        );
        return $subscribe_result;

        // If they are currently banned, adjust behavior.
      } elseif ($subscriber_exists_array["status"] == "Banned") {

        $http_status_code = 200;
        $internal_status_code = 602;
        $subscribe_result = array(
          "status" => "banned",
          "http_status_code" => $http_status_code,
          "internal_status_code" => $internal_status_code,
          "info" => "Subscriber is banned",
          "email" => $subscriber_email,
          "unique_id" => $subscriber_unique_id,
          "referral_id" => $subscriber_referral_id,
          "check_referral_id" => $subscriber_check_referral_id
        );
        return $subscribe_result;

        // If they are currently unconfirmed, adjust behavior.
      } elseif ($subscriber_exists_array["status"] == "Unconfirmed") {

        // Resend transactional email to confirm.
        $subscriber_referred_by_id = $subscriber_exists_array["referred_by_id"];
        if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {
          send_confirmation_email($subscriber_email, $subscriber_referral_id, TRUE);
        } else {
          send_confirmation_email($subscriber_email, $subscriber_referral_id, FALSE);
        }

        // Return results.
        $return_status = "unconfirmed";
        $internal_status_code = 1000;
        $http_status_code = 200;
        $subscribe_result = array(
          "status" => $return_status,
          "http_status_code" => $http_status_code,
          "internal_status_code" => $internal_status_code,
          "info" => "Successfully added subscriber",
          "email" => $subscriber_email,
          "unique_id" => $subscriber_unique_id,
          "referral_id" => $subscriber_referral_id,
          "check_referral_id" => $subscriber_check_referral_id
        );
        return $subscribe_result;
      } else {

        // Reactivate existing record.
        $subscriber_status = "Active";
        $subscriber_resubscribed_timestamp_utc = gmdate("Y-m-d H:i:s");

        // Reactivate subscriber with Campaign Monitor.
        $subscriber_cm_result = $cm_wrap->update(
          $subscriber_email,
          array(
            "Resubscribe" => TRUE,
            "ConsentToTrack" => "unchanged"
          )
        );

        // If successful, move on; otherwise, raise error.
        if (!($subscriber_cm_result->was_successful())) {
          $internal_status_code = 909;
          $cm_status_code = $subscriber_cm_result->http_status_code;
          throw new Exception("Error reactivating subscriber with Campaign Monitor with code " . $cm_status_code);
        }

        // Reactivate subscriber in database.
        $subscriber_resubscribe_query = "UPDATE main_newsletter
                                          SET status = $1,
                                          number_times_resubscribed = number_times_resubscribed + 1,
                                          resubscribed_timestamp_utc = $2
                                          WHERE email = $3";
        $subscriber_resubscribe_result = pg_query_params(
          $db_cxn,
          $subscriber_resubscribe_query,
          array(
            $subscriber_status,
            $subscriber_resubscribed_timestamp_utc,
            $subscriber_email
          )
        );

        // If error, throw exception.
        if ($subscriber_resubscribe_result == FALSE) {
          $internal_status_code = 910;
          throw new Exception("Error reactivating subscriber in database.");
        }

        // Close DB connection.
        pg_close($db_cxn);

        // Return results. Don't give any referral credit in this case.
        $http_status_code = 200;
        $internal_status_code = 1001;
        $subscribe_result = array(
          "status" => "success",
          "http_status_code" => $http_status_code,
          "internal_status_code" => $internal_status_code,
          "info" => "Successfully reactivated subscriber",
          "email" => $subscriber_email,
          "unique_id" => $subscriber_unique_id,
          "referral_id" => $subscriber_referral_id,
          "check_referral_id" => $subscriber_check_referral_id
        );
        return $subscribe_result;
      }
    } else {
      $internal_status_code = 911;
      throw new Exception("Irregular result returned from ID check.");
    }
  } catch (Exception $e) {

    server_alert($e->getMessage());
    $http_status_code = 500;
    $internal_status_code = 999;
    $subscribe_result = array(
      "status" => "failure",
      "http_status_code" => $http_status_code,
      "internal_status_code" => $internal_status_code,
      "info" => $e->getMessage(),
      "email" => $subscriber_email,
      "unique_id" => NULL,
      "referral_id" => NULL,
      "check_referral_id" => NULL
    );
    return $subscribe_result;
  }
}

// Confirm user added via referral.
function confirm($referral_id, $update_referrer = FALSE)
{

  // Define global variables.
  global $cm_api_key, $main_subscriber_list_id, $subscriber_db_cxn_str;

  // Wrap in try block to catch errors.
  try {

    // Authenticate Campaign Monitor API.
    $cm_auth = array("api_key" => $cm_api_key);
    $cm_wrap = new CS_REST_Subscribers($main_subscriber_list_id, $cm_auth);

    // Create connection to database.
    $db_cxn = pg_connect($subscriber_db_cxn_str);

    // Check if subscriber already exists.
    $subscriber_exists_query = "SELECT *
                                  FROM main_newsletter
                                  WHERE referral_id = $1";
    $subscriber_exists_result = pg_query_params(
      $db_cxn,
      $subscriber_exists_query,
      array($referral_id)
    );

    // If error, throw exception.
    if ($subscriber_exists_result == FALSE) {
      $internal_status_code = 901;
      throw new Exception("Error checking database for subscriber.");
    }

    // Parse fields for user.
    if (pg_num_rows($subscriber_exists_result) != 1) {
      $internal_status_code = 911;
      throw new Exception("Irregular result returned from ID check - 1.");
    }
    $subscriber_array = pg_fetch_array($subscriber_exists_result, 0, PGSQL_ASSOC);
    $subscriber_email = $subscriber_array["email"];
    $subscriber_unique_id = $subscriber_array["unique_id"];
    $subscriber_referral_id = $subscriber_array["referral_id"];
    $subscriber_check_referral_id = $subscriber_array["check_referral_id"];
    $subscriber_referral_link = $subscriber_array["referral_link"];
    $subscriber_referral_display_link = $subscriber_array["referral_display_link"];
    $subscriber_check_referral_link = $subscriber_array["check_referral_link"];
    $subscriber_check_referral_display_link = $subscriber_array["check_referral_display_link"];
    $subscriber_signup_params = $subscriber_array["signup_parameters"];
    $subscriber_signup_source = $subscriber_array["signup_source"];
    $subscriber_referred_by_id = $subscriber_array["referred_by_id"];
    $subscriber_referred_by_email = $subscriber_array["referred_by_email"];
    $subscriber_referral_count = $subscriber_array["referral_count"];
    $subscriber_signup_ip_address = $subscriber_array["signup_ip_address"];
    $subscriber_old_status = $subscriber_array["status"];

    // If user is not awaiting confirmation throw error.
    if ($subscriber_old_status != "Unconfirmed") {
      $internal_status_code = 992;
      throw new Exception("User is not awaiting confirmation.");
    }

    // Move new user to confirmed in db.
    $new_status = "Active";
    $confirmed_timestamp = gmdate("Y-m-d H:i:s");
    $subscriber_confirm_query = "UPDATE main_newsletter
                                  SET status = $1,
                                  confirmed_timestamp_utc = $2
                                  WHERE email = $3";
    $subscriber_confirm_result = pg_query_params(
      $db_cxn,
      $subscriber_confirm_query,
      array($new_status, $confirmed_timestamp, $subscriber_email)
    );

    // If error, throw exception.
    if ($subscriber_confirm_result == FALSE) {
      $internal_status_code = 980;
      throw new Exception("Error moving unconfirmed user to active in database.");
    }

    // Push new user to Campaign Monitor.
    $subscriber_cm_custom_fields = array(
      array("Key" => "unique_id", "Value" => $subscriber_unique_id),
      array("Key" => "referral_id", "Value" => $subscriber_referral_id),
      array("Key" => "check_referral_id", "Value" => $subscriber_check_referral_id),
      array("Key" => "referral_link", "Value" => $subscriber_referral_link),
      array("Key" => "referral_display_link", "Value" => $subscriber_referral_display_link),
      array("Key" => "check_referral_link", "Value" => $subscriber_check_referral_link),
      array("Key" => "check_referral_display_link", "Value" => $subscriber_check_referral_display_link),
      array("Key" => "signup_parameters", "Value" => $subscriber_signup_params),
      array("Key" => "signup_source", "Value" => $subscriber_signup_source),
      array("Key" => "referred_by_id", "Value" => $subscriber_referred_by_id),
      array("Key" => "referred_by_email", "Value" => $subscriber_referred_by_email),
      array("Key" => "referral_count", "Value" => $subscriber_referral_count),
      array("Key" => "signup_ip_address", "Value" => $subscriber_signup_ip_address)
    );
    $subscriber_cm_result = $cm_wrap->add(array(
      "EmailAddress" => $subscriber_email,
      "CustomFields" => $subscriber_cm_custom_fields,
      "Resubscribe" => TRUE,
      "ConsentToTrack" => "unchanged"
    ));

    // If unsuccessful, raise error.
    if (!($subscriber_cm_result->was_successful())) {
      $internal_status_code = 905;
      $cm_status_code = $subscriber_cm_result->http_status_code;
      throw new Exception("Error pushing data to Campaign Monitor with code " . $cm_status_code);
    }

    // Update referrer if flag set.
    if ($update_referrer) {

      // Make sure values actually set.
      if ((!empty($subscriber_referred_by_id)) && ($subscriber_referred_by_id != "")) {

        // Check if referrer exists.
        $referrer_exists_query = "SELECT *
                                    FROM main_newsletter
                                    WHERE unique_id = $1";
        $referrer_exists_result = pg_query_params(
          $db_cxn,
          $referrer_exists_query,
          array($subscriber_referred_by_id)
        );

        // If error, throw exception.
        if ($referrer_exists_result == FALSE) {
          $internal_status_code = 902;
          throw new Exception("Error checking database for referrer.");
        }

        // Parse fields for referrer.
        if (pg_num_rows($referrer_exists_result) != 1) {
          $internal_status_code = 911;
          throw new Exception("Irregular result returned from ID check - 2.");
        }
        $referrer_array = pg_fetch_array($referrer_exists_result, 0, PGSQL_ASSOC);
        $referrer_email = $referrer_array["email"];
        $referrer_referral_count = $referrer_array["referral_count"];
        $referrer_unconfirmed_ids = $referrer_array["unconfirmed_users_referred_id"];
        $referrer_unconfirmed_emails = $referrer_array["unconfirmed_users_referred_email"];
        $referrer_confirmed_ids = $referrer_array["users_referred_id"];
        $referrer_confirmed_emails = $referrer_array["users_referred_email"];

        // Move to confirmed referral for referrer in DB.
        $new_referral_count = $referrer_referral_count + 1;
        $new_unconfirmed_ids = str_replace($subscriber_unique_id, "", $referrer_unconfirmed_ids);
        $new_unconfirmed_ids = str_replace(",,", ",", $new_unconfirmed_ids);
        $new_unconfirmed_ids = empty_to_null($new_unconfirmed_ids);
        $new_unconfirmed_emails = str_replace($subscriber_email, "", $referrer_unconfirmed_emails);
        $new_unconfirmed_emails = str_replace(",,", ",", $new_unconfirmed_emails);
        $new_unconfirmed_emails = empty_to_null($new_unconfirmed_emails);
        $new_confirmed_ids = trim($referrer_confirmed_ids . "," . $subscriber_unique_id, ",");
        $new_confirmed_emails = trim($referrer_confirmed_emails . "," . $subscriber_email, ",");
        $referrer_update_query = "UPDATE main_newsletter
                                    SET referral_count = $1,
                                    users_referred_id = $2,
                                    users_referred_email = $3,
                                    unconfirmed_users_referred_id = $4,
                                    unconfirmed_users_referred_email = $5
                                    WHERE email = $6";
        $referrer_update_result = pg_query_params(
          $db_cxn,
          $referrer_update_query,
          array($new_referral_count, $new_confirmed_ids, $new_confirmed_emails, $new_unconfirmed_ids, $new_unconfirmed_emails, $referrer_email)
        );

        // If error, throw exception.
        if ($referrer_update_result == FALSE) {
          $internal_status_code = 981;
          throw new Exception("Error updating referrer values in database.");
        }

        // Push referrer updated values to Campaign Monitor.
        $referrer_cm_custom_fields = array(
          "CustomFields" => array(
            array("Key" => "referral_count", "Value" => $new_referral_count)
          ),
          "ConsentToTrack" => "unchanged"
        );
        $referrer_cm_result = $cm_wrap->update($referrer_email, $referrer_cm_custom_fields);

        // If successful, move on; otherwise, raise error.
        if (!($referrer_cm_result->was_successful())) {
          $internal_status_code = 907;
          $cm_status_code = $referrer_cm_result->http_status_code;
          throw new Exception("Error updating referrer with Campaign Monitor with code " . $cm_status_code);
        }
      }
    }

    // Return successful status.
    $http_status_code = 200;
    $internal_status_code = 1000;
    $confirm_result = array(
      "status" => "success",
      "http_status_code" => $http_status_code,
      "internal_status_code" => $internal_status_code,
      "info" => "Successfully confirmed subscriber",
      "email" => $subscriber_email,
      "unique_id" => $subscriber_unique_id,
      "referral_id" => $subscriber_referral_id,
      "check_referral_id" => $subscriber_check_referral_id
    );
    return $confirm_result;
  } catch (Exception $e) {

    server_alert($e->getMessage());
    $http_status_code = 500;
    $internal_status_code = 999;
    $confirm_result = array(
      "status" => "failure",
      "http_status_code" => $http_status_code,
      "internal_status_code" => $internal_status_code,
      "info" => $e->getMessage(),
      "email" => NULL,
      "unique_id" => NULL,
      "referral_id" => $referral_id,
      "check_referral_id" => NULL
    );
    return $confirm_result;
  }
}

// Clean email address
function clean_email($email)
{

  // Lowercase and remove characters.
  $email = strtolower(htmlspecialchars($email));

  // Remove spaces.
  $email = str_replace(" ", "+", $email);

  // Make sure actually an email address.
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $result = array(
      "is_valid" => TRUE,
      "email" => $email
    );
  } else {
    $result = array(
      "is_valid" => FALSE,
      "email" => $email
    );
  }

  // Return result.
  return $result;
}

// Validate user exists
function validate_user($id, $id_field, $extra_flag = NULL)
{

  // Declare global variables.
  global $subscriber_db_cxn_str;

  // Wrap in try block.
  try {

    // Create connection to database.
    $db_cxn = pg_connect($subscriber_db_cxn_str);

    // Pull subscriber data depending on field.
    if ($id_field == "email") {
      $clean_result = clean_email($id);
      if ($clean_result["is_valid"]) {
        $id = $clean_result["email"];
      } else {
        throw new Exception("Failed email validation for user " . $id);
      }
      $db_query = "SELECT * FROM main_newsletter WHERE email = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "referral_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE referral_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "check_referral_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE check_referral_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "unique_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE unique_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    }

    // Close DB connection.
    pg_close($db_cxn);

    // If error, redirect to error page.
    if ($db_result == FALSE) {
      throw new Exception("Failed database lookup for user " . $id);
    }

    // Make sure only one row for results.
    if (pg_num_rows($db_result) != 1) {
      $result = array(
        "status" => "redirect_home",
        "data" => NULL,
        "info" => "User does not exist"
      );
      return $result;
    }

    // Convert results to array.
    $db_array = pg_fetch_array($db_result, 0, PGSQL_ASSOC);

    // Check for unconfirmed status.
    if ($db_array["status"] == "Unconfirmed") {
      $result = array(
        "status" => "unconfirmed",
        "data" => NULL,
        "info" => "User is unconfirmed"
      );
      return $result;
    }

    // Verify active status.
    if ($db_array["status"] != "Active") {
      $result = array(
        "status" => "redirect_home",
        "data" => NULL,
        "info" => "User is not active"
      );
      return $result;
    }

    // Extra validation for college ambassadors.
    if (($extra_flag == "college_ambassador") && ($db_array["college_ambassador"] != 1)) {
      $result = array(
        "status" => "redirect_home",
        "data" => NULL,
        "info" => "User is not a college ambassador"
      );
      return $result;
    }

    // Return final data.
    $result = array(
      "status" => "success",
      "data" => $db_array,
      "info" => "User validated successfully"
    );
    return $result;
  } catch (Exception $e) {

    // Send alert and return failure message.
    server_alert($e->getMessage());
    $result = array(
      "status" => "failure",
      "data" => NULL,
      "info" => "Error validating user"
    );
    return $result;
  }
}


// Validate user exists
function get_user_info($id, $id_field)
{

  // Declare global variables.
  global $subscriber_db_cxn_str;

  // Wrap in try block.
  try {

    // Create connection to database.
    $db_cxn = pg_connect($subscriber_db_cxn_str);

    // Pull subscriber data depending on field.
    if ($id_field == "email") {
      $clean_result = clean_email($id);
      if ($clean_result["is_valid"]) {
        $id = $clean_result["email"];
      } else {
        throw new Exception("Failed email validation for user " . $id);
      }
      $db_query = "SELECT * FROM main_newsletter WHERE email = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "referral_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE referral_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "check_referral_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE check_referral_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    } elseif ($id_field == "unique_id") {
      $db_query = "SELECT * FROM main_newsletter WHERE unique_id = $1";
      $db_result = pg_query_params($db_cxn, $db_query, array($id));
    }

    // Close DB connection.
    pg_close($db_cxn);

    // If error, redirect to error page.
    if ($db_result == FALSE) {
      throw new Exception("Failed database lookup for user " . $id);
    }

    // Make sure only one row for results.
    if (pg_num_rows($db_result) != 1) {
      $result = array(
        "status" => "redirect_home",
        "data" => NULL,
        "info" => "User does not exist"
      );
      return $result;
    }

    // Convert results to array.
    $db_array = pg_fetch_array($db_result, 0, PGSQL_ASSOC);

    // Return final data.
    $result = array(
      "status" => "success",
      "data" => $db_array,
      "info" => "User validated successfully"
    );
    return $result;
  } catch (Exception $e) {

    // Send alert and return failure message.
    server_alert($e->getMessage());
    $result = array(
      "status" => "failure",
      "data" => NULL,
      "info" => "Error validating user"
    );
    return $result;
  }
}
