<?php

// Load in packages.
session_start();
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/vendor/autoload.php";

use Auth0\SDK\Auth0;

// Handle errors sent back by Auth0.
if (!empty($_GET['error']) || !empty($_GET['error_description'])) {
  printf('<h1>Error</h1><p>%s</p>', htmlspecialchars($_GET['error_description']));
  die();
}

// Instantiate the base Auth0 class.
$auth0 = new Auth0([
  'domain' => $AUTH0_DOMAIN,
  'client_id' =>  $AUTH0_CLIENT_ID,
  'client_secret' => $AUTH0_CLIENT_SECRET,
  'redirect_uri' => $AUTH0_REDIRECT_URI,
]);

// Check for user info.
try {
  $userInfo = $auth0->getUser();
} catch (Exception $e) {
  die($e->getMessage());
}

// Handle flow based on authentication.
if (!$userInfo) {
  $request_uri = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  setcookie("SPRINT-ORIGIN", $request_uri, time() + 60 * 60, "/", "tools.sidelinesprint.com", true);
  header("Location: https://tools.sidelinesprint.com/login");
  exit();
} else {
  if ((!isset($_SESSION["user_data"])) || (empty($_SESSION["user_data"]))) {
    $staff_validation = validate_staff($userInfo["email"]);
    if ($staff_validation["status"] == "success") {
      $_SESSION["user_data"] = $staff_validation["data"];
    } else {
      header("Location: https://tools.sidelinesprint.com/error");
      exit();
    }
  }
  $user_email = $_SESSION["user_data"]["email"];
  $user_name = $_SESSION["user_data"]["name"];
  $user_profile_pic = $_SESSION["user_data"]["profile_picture"];
  $user_role = $_SESSION["user_data"]["role"];
  $user_internal_api_key = $_SESSION["user_data"]["internal_api_key"];
}

// Set headers to force revalidation.
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// Wrap in try block.
try {

  // Create DB connection.
  $db_cxn = pg_connect($subscriber_db_cxn_str);

  // Get info of last 100 subscribers.
  $recent_subscriber_query = "SELECT email, signup_source, signup_parameters, signup_referrer_header, signup_user_agent, signup_base_url, first_subscribed_timestamp_utc
                                  FROM main_newsletter
                                  WHERE status = 'Active'
                                  ORDER BY first_subscribed_timestamp_utc DESC
                                  LIMIT 100";
  $recent_subscriber_result = pg_query($db_cxn, $recent_subscriber_query);
  if ($recent_subscriber_result == FALSE) {
    throw new Exception("Error getting subscribers from database.");
  } else {
    $recent_subscriber_array = pg_fetch_all($recent_subscriber_result, PGSQL_ASSOC);
    $table_array = array();
    foreach ($recent_subscriber_array as $subscriber) {
      $date_fix = new DateTime($subscriber["first_subscribed_timestamp_utc"], new DateTimeZone('UTC'));
      $date_fix -> setTimezone(new DateTimeZone('America/New_York'));
      $subscriber_array = array(
        "email" => $subscriber["email"],
        "signup_source" => $subscriber["signup_source"],
        "signup_parameters" => $subscriber["signup_parameters"],
        "signup_referrer_header" => $subscriber["signup_referrer_header"],
        "signup_user_agent" => $subscriber["signup_user_agent"],
        "signup_base_url" => $subscriber["signup_base_url"],
        "signup_timestamp" => $date_fix -> format("M. j, Y g:i A")
      );
      $table_array[] = $subscriber_array;
    }
  }

  // Get number of signups today.
  $today_datetime = new DateTime(null, new DateTimeZone('UTC'));
  $today_datetime->modify('-1 day');
  $today_date_string = $today_datetime->format('Y-m-d H:i:s');
  $today_query = "SELECT count(*)
                  FROM main_newsletter
                  WHERE first_subscribed_timestamp_utc >= $1";
  $today_result = pg_query_params($db_cxn, $today_query, array($today_date_string));
  if ($today_result == FALSE) {
    throw new Exception("Error getting count of today's subscribers from database.");
  } else {
    $today_array = pg_fetch_array($today_result, 0, PGSQL_ASSOC);
    $signups_today = $today_array["count"];
  }

  // Get number of signups this week.
  $week_datetime = new DateTime(null, new DateTimeZone('UTC'));
  $week_datetime->modify('-7 days');
  $week_date_string = $week_datetime->format('Y-m-d H:i:s');
  $week_query = "SELECT count(*)
                  FROM main_newsletter
                  WHERE first_subscribed_timestamp_utc >= $1";
  $week_result = pg_query_params($db_cxn, $week_query, array($week_date_string));
  if ($week_result == FALSE) {
    throw new Exception("Error getting count of week's subscribers from database.");
  } else {
    $week_array = pg_fetch_array($week_result, 0, PGSQL_ASSOC);
    $signups_week = $week_array["count"];
  }

  // Get number of signups this month.
  $month_datetime = new DateTime(null, new DateTimeZone('UTC'));
  $month_datetime->modify('-1 month');
  $month_date_string = $month_datetime->format('Y-m-d H:i:s');
  $month_query = "SELECT count(*)
                  FROM main_newsletter
                  WHERE first_subscribed_timestamp_utc >= $1";
  $month_result = pg_query_params($db_cxn, $month_query, array($month_date_string));
  if ($month_result == FALSE) {
    throw new Exception("Error getting count of month's subscribers from database.");
  } else {
    $month_array = pg_fetch_array($month_result, 0, PGSQL_ASSOC);
    $signups_month = $month_array["count"];
  }

  // Get info of last 100 feedback entries.
  $db_cxn = pg_connect($subscriber_db_cxn_str);
  $recent_feedback_query = "SELECT ip_address, campaign, liked_newsletter, timestamp_utc
                                  FROM newsletter_feedback
                                  ORDER BY timestamp_utc DESC
                                  LIMIT 100";
  $recent_feedback_result = pg_query($db_cxn, $recent_feedback_query);
  if ($recent_feedback_result == FALSE) {
    throw new Exception("Error getting feedback from databaase.");
  } else {
    $recent_feedback_array = pg_fetch_all($recent_feedback_result, PGSQL_ASSOC);
    $feedback_table_array = array();
    foreach ($recent_feedback_array as $feedback) {
      $date_fix = new DateTime($feedback["timestamp_utc"], new DateTimeZone('UTC'));
      $date_fix -> setTimezone(new DateTimeZone('America/New_York'));
      $feedback_array = array(
        "ip_address" => $feedback["ip_address"],
        "campaign" => $feedback["campaign"],
        "liked_newsletter" => $feedback["liked_newsletter"],
        "timestamp" => $date_fix -> format("M. j, Y g:i A")
      );
      $feedback_table_array[] = $feedback_array;
    }
  }

} catch (Exception $e) {

  // Redirect on error.
  server_alert($e->getMessage());
  header("Location: https://tools.sidelinesprint.com/", true, 303);
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Beehiiv Overview | Sideline Sprint</title>
  <meta name="description" content="For development purposes only.">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <link rel="apple-touch-icon" sizes="180x180" href="https://cdn-tools.sidelinesprint.com/img/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="https://cdn-tools.sidelinesprint.com/img/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://cdn-tools.sidelinesprint.com/img/favicon-16x16.png">
  <link rel="manifest" href="https://cdn-tools.sidelinesprint.com/misc/site.webmanifest">
  <link rel="mask-icon" href="https://cdn-tools.sidelinesprint.com/img/safari-pinned-tab.svg" color="#67ca88">
  <link rel="shortcut icon" href="https://cdn-tools.sidelinesprint.com/img/favicon.ico">
  <meta name="msapplication-TileColor" content="#484848">
  <meta name="theme-color" content="#484848">
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/fonts/1Ptug8zYS_SKggPNyCMIT4ttDfCmxA.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/fonts/1Ptug8zYS_SKggPNyC0IT4ttDfA.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/img/tools-header.png" as="image">
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/css/bootstrap.min.css" as="style">
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/css/tools.min.css" as="style">
  <link rel="preload" href="https://cdn-tools.sidelinesprint.com/css/datatables.min.css" as="style">
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/tools.min.css">
  <script async src="https://cdn-tools.sidelinesprint.com/js/lazysizes.min.js"></script>
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/datatables.min.css">
</head>

<body class="body-font">
  <?php include '/var/www/html/secure/navigation.php'; ?>
  <div class="jumbotron jumbotron-fluid jumbotron-sprint">
    <div class="container">
      <div class="row pb-1">
        <div class="col-lg-12 text-center pt-1">
          <img src="https://cdn-tools.sidelinesprint.com/img/logo_placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/main_logo.png" alt="The Sideline Sprint logo." width="500" height="45" class="img-fluid lazyload pb-15">
          <h2 class="green-text bold-text pt-15">Beehiiv Overview</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row text-center">
      <div class="col-lg-12">
        <h3 class="green-text bold-text pt-15 pb-15">Subscriber Overview</h3>
        <div class="row">
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Signups In Last 24 Hours</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($signups_today); ?></h4>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Signups In Last 7 Days</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($signups_week); ?></h4>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Signups In Last 30 Days</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($signups_month); ?></h4>
            </div>
          </div>
        </div>
        <h3 class="green-text bold-text pt-15 pb-15">Most Recent Subscribers</h3>
        <table id="subscriber_table" class="table table-striped table-bordered nowrap" width="100%">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Email</th>
              <th scope="col">Source</th>
              <th scope="col">Parameters</th>
              <th scope="col">Referrer</th>
              <th scope="col">Base URL</th>
              <th scope="col">Timestamp (ET)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $row_id = 0;
            foreach ($table_array as $table_entry) {
              ++$row_id;
              echo "<tr>
                    <th scope=\"row\">{$row_id}</th>
                    <td>{$table_entry["email"]}</td>
                    <td>{$table_entry["signup_source"]}</td>
                    <td>{$table_entry["signup_parameters"]}</td>
                    <td>{$table_entry["signup_referrer_header"]}</td>
                    <td>{$table_entry["signup_base_url"]}</td>
                    <td>{$table_entry["signup_timestamp"]}</td>
                    </tr>";
            } ?>
          </tbody>
        </table>
        <h3 class="green-text bold-text pt-15 pb-15">Most Recent Feedback</h3>
        <table id="feedback_table" class="table table-striped table-bordered nowrap" width="100%">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">IP Address</th>
              <th scope="col">Campaign</th>
              <th scope="col">Liked It?</th>
              <th scope="col">Timestamp (ET)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $row_id = 0;
            foreach ($feedback_table_array as $table_entry) {
              ++$row_id;
              echo "<tr>
                    <th scope=\"row\">{$row_id}</th>
                    <td>{$table_entry["ip_address"]}</td>
                    <td>{$table_entry["campaign"]}</td>
                    <td>{$table_entry["liked_newsletter"]}</td>
                    <td>{$table_entry["timestamp"]}</td>
                    </tr>";
            } ?>
          </tbody>
        </table>
        <hr>
        <p class="footer"> &copy; Sideline Sprint 2021. All rights reserved.</p>
      </div>
    </div>
  </div>
  <script src="https://cdn-tools.sidelinesprint.com/js/jquery-3.5.1.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/popper.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/bootstrap.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/datatables.min.js"></script>
  <script>
    $(document).ready(function() {

      // Get variables from authentication.
      var user_email = "<?php echo $user_email; ?>";
      var user_name = "<?php echo $user_name; ?>";
      var user_profile_pic = "<?php echo $user_profile_pic; ?>";

      // Load navigation.
      $("#internal-tools-link, #beehiiv-overview-link").addClass("active");
      $("#internal-tools-link").html('<strong>' + $("#internal-tools-link").text() + '</strong>');
      $("#beehiiv-overview-link").html('<strong class="white-font">' + $("#beehiiv-overview-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Initialize datatable.
      $('#subscriber_table, #unsubscriber_table, #feedback_table').DataTable({
        "scrollX": true,
        "pagingType": "full",
        "searching": false,
        "info": false
      });

    });
  </script>
</body>

</html>
