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

// Wrap in try block.
try {

  // Authenticate API.
  $cm_auth = array("api_key" => $cm_api_key);

  // Get IDs of lists.
  $cm_client_wrap = new CS_REST_Clients($cm_client_id, $cm_auth);
  $cm_client_result = $cm_client_wrap->get_lists();
  if ($cm_client_result->was_successful()) {
    $lists_loaded = true;
    $cm_lists = $cm_client_result->response;
  } else {
    $lists_loaded = false;
    throw new Exception("Error getting list IDs from Campaign Monitor.");
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
  <title>Manual Newsletter Uploader | Sideline Sprint</title>
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
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/tools.min.css">
  <script async src="https://cdn-tools.sidelinesprint.com/js/lazysizes.min.js"></script>
</head>

<body class="body-font">
  <?php include '/var/www/html/secure/navigation.php'; ?>
  <div class="jumbotron jumbotron-fluid jumbotron-sprint">
    <div class="container">
      <div class="row pb-1">
        <div class="col-lg-12 text-center pt-1">
          <img src="https://cdn-tools.sidelinesprint.com/img/logo_placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/main_logo.png" alt="The Sideline Sprint logo." width="500" height="45" class="img-fluid lazyload pb-15">
          <h2 class="green-text bold-text pt-15">Manual Newsletter Uploader</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="alert alert-danger" role="alert">
          <span>This tool is deprecated now that we have moved to Beehiiv. It is left up as a backup.</span>
        </div>
        <form method="post" name="newsletter-form" id="newsletter-form">
          <div class="form-group pb-15">
            <label for="newsletter-subject"><span class="bold-text">Subject Line</span></label>
            <textarea id="newsletter-subject" name="newsletter-subject" class="form-control" rows="2" cols="50" required>🏃 Title</textarea>
          </div>
          <div class="form-group pb-15">
            <label for="newsletter-preheader"><span class="bold-text">Preheader (Optional)</span></label>
            <textarea id="newsletter-preheader" name="newsletter-preheader" class="form-control" rows="2" cols="50"></textarea>
          </div>
          <div class="form-group pb-15">
            <label for="newsletter-group"><span class="bold-text">Send To</span></label>
            <br>
            <select name="newsletter-group" id="newsletter-group" class="form-control" required>
              <?php
              foreach ($cm_lists as $item) {
                $id = $item->ListID;
                $name = $item->Name;
                echo "<option value='$id'>$name</option>";
              }
              ?>
            </select>
          </div>
          <div class="form-group pb-15">
            <label for="newsletter-html"><span class="bold-text">Newsletter HTML</span></label>
            <textarea id="newsletter-html" name="newsletter-html" class="form-control" rows="2" cols="50" required></textarea>
          </div>
        </form>
        <button class="btn btn-sprint" name="create-newsletter" id="create-newsletter" data-toggle="modal" data-target="#create-newsletter-modal" disabled><strong>Create Newsletter</strong></button>
        <hr>
        <p class="footer"> &copy; Sideline Sprint 2021. All rights reserved.</p>
      </div>
    </div>
  </div>
  </div>
  <div class="modal fade" id="create-newsletter-modal" tabindex="-1" aria-labelledby="create-newsletter-modal-label" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="create-newsletter-modal-label">Confirm Submission</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Are you sure you want to create this newsletter? You will still have to schedule in Campaign Monitor.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><strong>Cancel</strong></button>
          <button type="button" name="confirm-create" id="confirm-create" class="btn btn-sprint" data-dismiss="modal"><strong>Create</strong></button>
        </div>
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
  <script>
    $(document).ready(function() {

      // Get variables from authentication.
      var user_email = "<?php echo $user_email; ?>";
      var user_name = "<?php echo $user_name; ?>";
      var user_profile_pic = "<?php echo $user_profile_pic; ?>";
      var user_internal_api_key = "<?php echo $user_internal_api_key; ?>";

      // Load navigation.
      $("#internal-insights-link, #manual-newsletter-uploader-link").addClass("active");
      $("#internal-insights-link").html('<strong>' + $("#internal-insights-link").text() + '</strong>');
      $("#manual-newsletter-uploader-link").html('<strong class="white-font">' + $("#manual-newsletter-uploader-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Show status of loading lists.
      <?php echo "var lists_loaded = {$lists_loaded};"; ?>
      if (lists_loaded) {
        $('#status-toast-body').text('Lists successfully loaded from Campaign Monitor.');
        $('#status-toast').toast('show');
      } else {
        $('#status-toast-body').text('Error loading lists from Campaign Monitor.');
        $('#status-toast').toast('show');
      }

      // Disable confirm creation button until all required fields entered.
      $('#newsletter-html, #newsletter-group, #newsletter-subject').change(function() {
        if ($("#newsletter-html").val().length &&
          $("#newsletter-group").val().length &&
          $("#newsletter-subject").val().length) {
          $("#create-newsletter").prop('disabled', false);
        } else {
          $("#create-newsletter").prop('disabled', true);
        }
      });

      // Start submit in confirm creation.
      $("#confirm-create").click(function() {

        // Disable submit button.
        $("#create-newsletter").prop('disabled', true);

        // Show message of submission.
        $('#status-toast-body').text("Creating newsletter...");
        $('#status-toast').toast('show');

        // Get form variables.
        var newsletter_html = $("#newsletter-html").val();
        var newsletter_group = $("#newsletter-group").val();
        var newsletter_subject = $("#newsletter-subject").val();
        var newsletter_preheader = $("#newsletter-preheader").val();

        // Send the data using post
        var posting = $.post("https://tools.sidelinesprint.com/utilities/create-newsletter", {
          newsletter_html: newsletter_html,
          newsletter_group: newsletter_group,
          newsletter_subject: newsletter_subject,
          newsletter_preheader: newsletter_preheader,
          api_key: user_internal_api_key
        });

        // Show alert based on result.
        posting.done(function(data) {

          // Hide submit toast.
          $('#status-toast').toast('dispose');

          // Parse json.
          json_data = JSON.parse(data);

          if (json_data["status"] == "success") {

            // Display status for success.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');

            // Reset form data.
            $('#newsletter-form').trigger("reset");

          } else if (json_data["status"] == "failure") {

            // Display status for error.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');
            console.log(json_data["error"]);

            // Re-enable submit button.
            $("#create-newsletter").prop('disabled', false);

          }

        });

      });

    });
  </script>
</body>

</html>
