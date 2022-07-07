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

  // Authenticate API.
  $cm_auth = array("api_key" => $cm_api_key);

  // Get IDs of campaign.
  $cm_client_wrap = new CS_REST_Clients($cm_client_id, $cm_auth);
  $cm_client_result = $cm_client_wrap->get_campaigns();
  if ($cm_client_result->was_successful()) {
    $cm_client_response = $cm_client_result->response;
    $latest_campaign = $cm_client_response[0];
    $latest_campaign_id = $latest_campaign->CampaignID;
    $latest_campaign_subject = $latest_campaign->Subject;
  } else {
    throw new Exception("Error getting campaign IDs from Campaign Monitor.");
  }

  // Get info from campaign.
  $cm_campaign_wrap = new CS_REST_Campaigns($latest_campaign_id, $cm_auth);
  $cm_campaign_result = $cm_campaign_wrap->get_summary();
  if ($cm_campaign_result->was_successful()) {
    $cm_campaign_response = $cm_campaign_result->response;
    $campaign_recipients = $cm_campaign_response->Recipients;
    $campaign_unique_opened = $cm_campaign_response->UniqueOpened;
    $campaign_clicks = $cm_campaign_response->Clicks;
    $campaign_unsubscribes = $cm_campaign_response->Unsubscribed;
    $campaign_bounces = $cm_campaign_response->Bounced;
    $campaign_complaints = $cm_campaign_response->SpamComplaints;
    $campaign_total_opens = $cm_campaign_response->TotalOpened;
    $campaign_open_rate = round(100 * ($campaign_unique_opened / $campaign_recipients), 2);
  } else {
    throw new Exception("Error getting campaign summary from Campaign Monitor.");
  }

  // Get list stats.
  $cm_list_wrap = new CS_REST_Lists($main_subscriber_list_id, $cm_auth);
  $cm_list_result = $cm_list_wrap->get_stats();
  if ($cm_list_result->was_successful()) {
    $cm_list_response = $cm_list_result->response;
    $active_subscribers = $cm_list_response->TotalActiveSubscribers;
    $new_subscribers_day = $cm_list_response->NewActiveSubscribersToday;
    $new_subscribers_week = $cm_list_response->NewActiveSubscribersThisWeek;
    $new_subscribers_month = $cm_list_response->NewActiveSubscribersThisMonth;
    $unsubscribes_day = $cm_list_response->UnsubscribesToday;
    $unsubscribes_week = $cm_list_response->UnsubscribesThisWeek;
    $unsubscribes_month = $cm_list_response->UnsubscribesThisMonth;
  } else {
    throw new Exception("Error getting list info from Campaign Monitor.");
  }

  // Get info of last 100 subscribers.
  $db_cxn = pg_connect($subscriber_db_cxn_str);
  $recent_subscriber_query = "SELECT email, signup_source, signup_parameters, signup_referrer_header, signup_user_agent, signup_base_url, first_subscribed_timestamp_utc
                                  FROM main_newsletter
                                  WHERE status = 'Active'
                                  ORDER BY first_subscribed_timestamp_utc DESC
                                  LIMIT 100";
  $recent_subscriber_result = pg_query($db_cxn, $recent_subscriber_query);
  if ($recent_subscriber_result == FALSE) {
    throw new Exception("Error getting subscribers from databaase.");
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

  // Get info of last 100 unsubscribers.
  $recent_unsubscriber_query = "SELECT email, signup_source, signup_parameters, signup_referrer_header, signup_user_agent, signup_base_url, first_subscribed_timestamp_utc, unsubscribed_timestamp_utc
                                  FROM main_newsletter
                                  WHERE status = 'Inactive'
                                  ORDER BY unsubscribed_timestamp_utc DESC
                                  LIMIT 100";
  $recent_unsubscriber_result = pg_query($db_cxn, $recent_unsubscriber_query);
  if ($recent_unsubscriber_result == FALSE) {
    throw new Exception("Error getting unsubscribers from databaase.");
  } else {
    $recent_unsubscriber_array = pg_fetch_all($recent_unsubscriber_result, PGSQL_ASSOC);
    $inactive_table_array = array();
    foreach ($recent_unsubscriber_array as $unsubscriber) {
      $date_fix_1 = new DateTime($unsubscriber["first_subscribed_timestamp_utc"], new DateTimeZone('UTC'));
      $date_fix_1 -> setTimezone(new DateTimeZone('America/New_York'));
      $date_fix_2 = new DateTime($unsubscriber["unsubscribed_timestamp_utc"], new DateTimeZone('UTC'));
      $date_fix_2 -> setTimezone(new DateTimeZone('America/New_York'));
      $unsubscriber_array = array(
        "email" => $unsubscriber["email"],
        "signup_source" => $unsubscriber["signup_source"],
        "signup_parameters" => $unsubscriber["signup_parameters"],
        "signup_referrer_header" => $unsubscriber["signup_referrer_header"],
        "signup_user_agent" => $unsubscriber["signup_user_agent"],
        "signup_base_url" => $unsubscriber["signup_base_url"],
        "signup_timestamp" => $date_fix_1 -> format("M. j, Y g:i A"),
        "unsubscribe_timestamp" => $date_fix_2 -> format("M. j, Y g:i A")
      );
      $inactive_table_array[] = $unsubscriber_array;
    }
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
          "email" => $feedback["email"],
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
  <title>Overview Dashboard | Sideline Sprint</title>
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
          <h2 class="green-text bold-text pt-15">Overview Dashboard</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row text-center">
      <div class="col-lg-12">
        <div class="alert alert-danger" role="alert">
          <span>This tool is deprecated now that we have moved to Beehiiv. It is left up as a backup.</span>
        </div>
        <h3 class="green-text bold-text pb-15">Latest Campaign - <?php echo $latest_campaign_subject; ?></h3>
        <div class="row">
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Recipients</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_recipients); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Unique Opens</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_unique_opened); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Clicks</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_clicks); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Open Rate</strong></p>
              <h4 class="green-text bold-text"><?php echo $campaign_open_rate; ?>%</h4>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Bounces</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_bounces); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Unsubscribes</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_unsubscribes); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Spam Complaints</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_complaints); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Total Opens</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($campaign_total_opens); ?></h4>
            </div>
          </div>
        </div>
        <h3 class="green-text bold-text pt-15 pb-15">Subscriber Overview</h3>
        <div class="row">
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>Active Subscribers</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($active_subscribers); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>New Subscribers Today</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($new_subscribers_day); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>New Subscribers This Week</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($new_subscribers_week); ?></h4>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="stat-box">
              <p><strong>New Subscribers This Month</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($new_subscribers_month); ?></h4>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Unsubscribes Today</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($unsubscribes_day); ?></h4>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Unsubscribes This Week</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($unsubscribes_week); ?></h4>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="stat-box">
              <p><strong>Unsubscribes This Month</strong></p>
              <h4 class="green-text bold-text"><?php echo number_format($unsubscribes_month); ?></h4>
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
        <h3 class="green-text bold-text pt-15 pb-15">Most Recent Unsubscribers</h3>
        <table id="unsubscriber_table" class="table table-striped table-bordered nowrap" width="100%">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Email</th>
              <th scope="col">Source</th>
              <th scope="col">Parameters</th>
              <th scope="col">Referrer</th>
              <th scope="col">Base URL</th>
              <th scope="col">Unsubscribe Timestamp (ET)</th>
              <th scope="col">Subscribed Timestamp (ET)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $row_id = 0;
            foreach ($inactive_table_array as $table_entry) {
              ++$row_id;
              echo "<tr>
                    <th scope=\"row\">{$row_id}</th>
                    <td>{$table_entry["email"]}</td>
                    <td>{$table_entry["signup_source"]}</td>
                    <td>{$table_entry["signup_parameters"]}</td>
                    <td>{$table_entry["signup_referrer_header"]}</td>
                    <td>{$table_entry["signup_base_url"]}</td>
                    <td>{$table_entry["unsubscribe_timestamp"]}</td>
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
              <th scope="col">Email</th>
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
                    <td>{$table_entry["email"]}</td>
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
      $("#internal-insights-link, #overview-dashboard-link").addClass("active");
      $("#internal-insights-link").html('<strong>' + $("#internal-insights-link").text() + '</strong>');
      $("#overview-dashboard-link").html('<strong class="white-font">' + $("#overview-dashboard-link").text() + '</strong> <span class="sr-only">(current)</span>');
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
