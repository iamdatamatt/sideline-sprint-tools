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

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Bulk Subscriber Search | Sideline Sprint</title>
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
  <!-- <link rel="preload" href="https://cdn-tools.sidelinesprint.com/css/datatables.min.css" as="style"> -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.11.3/b-2.1.1/b-colvis-2.1.1/b-html5-2.1.1/b-print-2.1.1/datatables.min.css"/>
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/tools.min.css">
  <script async src="https://cdn-tools.sidelinesprint.com/js/lazysizes.min.js"></script>
  <!-- <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/datatables.min.css"> -->
</head>

<body class="body-font">
  <?php include '/var/www/html/secure/navigation.php'; ?>
  <div class="jumbotron jumbotron-fluid jumbotron-sprint">
    <div class="container">
      <div class="row pb-1">
        <div class="col-lg-12 text-center pt-1">
          <img src="https://cdn-tools.sidelinesprint.com/img/logo_placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/main_logo.png" alt="The Sideline Sprint logo." width="500" height="45" class="img-fluid lazyload pb-15">
          <h2 class="green-text bold-text pt-15">Bulk Subscriber Search</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="alert alert-primary" role="alert">
          <span>All dates are in UTC time, so if uncertain of ranges add an extra day on each side as a buffer.</span>
        </div>
        <form name="get-info-form" id="get-info-form">
          <div class="form-group pb-15" id="lookup-start-date-div" name="lookup-start-date-div">
            <label for="lookup-start-date"><span class="bold-text">Signup Start Date</span></label>
            <input id="lookup-start-date" name="lookup-start-date" class="form-control" type="date"></input>
          </div>
          <div class="form-group pb-15" id="lookup-end-date-div" name="lookup-end-date-div">
            <label for="lookup-end-date"><span class="bold-text">Signup End Date</span></label>
            <input id="lookup-end-date" name="lookup-end-date" class="form-control" type="date"></input>
          </div>
        </form>
        <button class="btn btn-sprint" name="lookup-subscribers" id="lookup-subscribers" disabled><strong>Lookup
            Subscribers</strong></button>
        <div id="table-wrapper-div" name="table-wrapper-div" class="d-none" style="margin-top:50px;">
          <table id="ajax-load-table" class="table table-striped table-bordered nowrap" width="100%">
            <thead>
              <tr>
                <th scope="col">Email</th>
                <th scope="col">Signup Parameters</th>
                <th scope="col">Signup Source</th>
                <th scope="col">Signup Referrer Header</th>
                <th scope="col">Signup User Agent</th>
                <th scope="col">Signup Base URL</th>
                <th scope="col">Referred By (Email)</th>
                <th scope="col">First Subscribed Timestamp (UTC)</th>
              </tr>
            </thead>
          </table>
        </div>
        <hr>
        <p class="footer"> &copy; Sideline Sprint 2021. All rights reserved.</p>
      </div>
    </div>
  </div>
  <div class="position-fixed bottom-0 right-0 p-3" style="z-index: 5; right: 0; bottom: 0;">
    <div id="status-toast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
      <div class="toast-header">
        <img src="https://cdn-tools.sidelinesprint.com/img/icon-192x192.png" width="24" height="24" class="rounded mr-2" alt="...">
        <strong class="mr-auto">Sideline Sprint</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body" id="status-toast-body" name="status-toast-body">
        This is a default message.
      </div>
    </div>
  </div>
  <script src="https://cdn-tools.sidelinesprint.com/js/jquery-3.5.1.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/popper.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/bootstrap.min.js"></script>
  <!-- <script src="https://cdn-tools.sidelinesprint.com/js/datatables.min.js"></script> -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.11.3/b-2.1.1/b-colvis-2.1.1/b-html5-2.1.1/b-print-2.1.1/datatables.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/sprint-utils.min.js"></script>
  <script src="https://cdn-tools.sidelinesprint.com/js/bootstrap-multiselect.js"></script>
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap-multiselect.css">
  <script>
    $(document).ready(function() {

      // Get variables from authentication.
      var user_email = "<?php echo $user_email; ?>";
      var user_name = "<?php echo $user_name; ?>";
      var user_profile_pic = "<?php echo $user_profile_pic; ?>";
      var user_internal_api_key = "<?php echo $user_internal_api_key; ?>";

      // Load navigation.
      $("#internal-tools-link, #bulk-subscriber-management-link").addClass("active");
      $("#internal-tools-link").html('<strong>' + $("#internal-tools-link").text() + '</strong>');
      $("#bulk-subscriber-management-link").html('<strong class="white-font">' + $("#bulk-subscriber-management-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Initialize popovers.
      $('[data-toggle="popover"]').popover();

      // Initialize datatable.
      $('#ajax-load-table').DataTable({
        "scrollX": true,
        "pagingType": "full",
        "destroy": true,
        "dom": 'Bfrtip',
        "buttons": [
            'csv', 'excel'
        ]
      });

      // Disable search until field filled.
      $('#lookup-start-date, #lookup-end-date').change(function() {
          if ($("#lookup-start-date").val().length && $("#lookup-end-date").val().length) {
            $("#lookup-subscribers").prop('disabled', false);
          } else {
            $("#lookup-subscribers").prop('disabled', true);
          }
      });

      // Handle submission of subscriber lookup.
      $("#lookup-subscribers").click(function() {

        // Hide table.
        $('#table-wrapper-div').addClass('d-none');

        // Set POST variables.
        var start_datetime = $("#lookup-start-date").val() + " 00:00:00";
        var end_datetime = $("#lookup-end-date").val() + " 23:59:59";

        // Send the data using post
        var posting = $.post("https://tools.sidelinesprint.com/utilities/bulk-get-subscriber", {
          start_datetime: start_datetime,
          end_datetime: end_datetime,
          api_key: user_internal_api_key
        });

        // Put the results in a div
        posting.done(function(data) {

          // Parse json
          json_data = JSON.parse(data);

          if (json_data["status"] == "no_data_found") {

            // Display status for nothing found.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');

          } else if (json_data["status"] == "failure") {

            // Display status for error.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');
            console.log(json_data);

          } else if (json_data["status"] == "success") {

            // Display status for success.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');

            // Update values in form.
            bulk_data = json_data["data"];
            $('#ajax-load-table').dataTable( {
                "aaData": bulk_data,
                "destroy": true,
                "scrollX": true,
                "pagingType": "full",
                "dom": 'Bfrtip',
                "buttons": [
                    'csv', 'excel'
                ],
                "columns": [
                    { "data": "email" },
                    { "data": "signup_parameters" },
                    { "data": "signup_source" },
                    { "data": "signup_referrer_header" },
                    { "data": "signup_user_agent" },
                    { "data": "signup_base_url" },
                    { "data": "referred_by_email" },
                    { "data": "first_subscribed_timestamp_utc" }
                ]
            });

            // Show table.
            $('#table-wrapper-div').removeClass('d-none');

            // Adjust table width.
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();

          }
        });

      });

    });
  </script>
</body>

</html>
