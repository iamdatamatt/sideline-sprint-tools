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
  <title>Single Subscriber Search | Sideline Sprint</title>
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
          <h2 class="green-text bold-text pt-15">Single Subscriber Search</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <form name="get-info-form" id="get-info-form">
          <div class="form-group pb-15">
            <label for="lookup-field"><span class="bold-text">Field to Search</span></label>
            <br>
            <select name="lookup-field" id="lookup-field" class="form-control" required>
              <option value='lookup-email-div' selected>Email</option>
              <option value='lookup-unique-id-div'>Unique ID</option>
              <option value='lookup-referral-id-div'>Referral ID</option>
              <option value='lookup-check-referral-id-div'>Check Referral ID</option>
            </select>
          </div>
          <div class="form-group pb-15" id="lookup-email-div" name="lookup-email-div">
            <label for="lookup-email"><span class="bold-text">Email</span></label>
            <input id="lookup-email" name="lookup-email" class="form-control" type="email"></input>
          </div>
          <div class="form-group pb-15 d-none" id="lookup-unique-id-div" name="lookup-unique-id-div">
            <label for="lookup-unique-id"><span class="bold-text">Unique ID</span></label>
            <input id="lookup-unique-id" name="lookup-unique-id" class="form-control" type="text"></input>
          </div>
          <div class="form-group pb-15 d-none" id="lookup-referral-id-div" name="lookup-referral-id-div">
            <label for="lookup-referral-id"><span class="bold-text">Referral ID</span></label>
            <input id="lookup-referral-id" name="lookup-referral-id" class="form-control" type="text"></input>
          </div>
          <div class="form-group pb-15 d-none" id="lookup-check-referral-id-div" name="lookup-check-referral-id-div">
            <label for="lookup-check-referral-id"><span class="bold-text">Check Referral ID</span></label>
            <input id="lookup-check-referral-id" name="lookup-check-referral-id" class="form-control" type="text"></input>
          </div>
        </form>
        <button class="btn btn-sprint" name="lookup-subscriber" id="lookup-subscriber" disabled><strong>Lookup
            Subscriber</strong></button>
        <div id="update-div-toggle" name="update-div-toggle" class="d-none pt-15">
          <form name="update-info-form" id="update-info-form">
            <div class="form-group pt-15 pb-15">
              <label for="subscriber-email"><span class="bold-text">Email</span></label>
              <input id="subscriber-email" name="subscriber-email" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-status"><span class="bold-text">Status</span></label>
              <select name="subscriber-status" id="subscriber-status" class="form-control" disabled>
                <option value='Active'>Active</option>
                <option value='Unconfirmed'>Unconfirmed</option>
                <option value='Inactive'>Inactive (Unsubscribed)</option>
                <option value='Banned'>Banned</option>
              </select>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-unique-id"><span class="bold-text">Unique ID</span></label>
              <input id="subscriber-unique-id" name="subscriber-unique-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-unique-id"><span class="bold-text">Legacy Unique ID
                  (MailerLite)</span></label>
              <input id="subscriber-legacy-unique-id" name="subscriber-legacy-unique-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referral-id"><span class="bold-text">Referral ID</span></label>
              <input id="subscriber-referral-id" name="subscriber-referral-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-check-referral-id"><span class="bold-text">Check Referral ID</span></label>
              <input id="subscriber-check-referral-id" name="subscriber-check-referral-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referral-link"><span class="bold-text">Referral Link</span></label>
              <input id="subscriber-referral-link" name="subscriber-referral-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referral-display-link"><span class="bold-text">Referral Display Link</span></label>
              <input id="subscriber-referral-display-link" name="subscriber-referral-display-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-check-referral-link"><span class="bold-text">Check Referral Link</span></label>
              <input id="subscriber-check-referral-link" name="subscriber-check-referral-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-check-referral-display-link"><span class="bold-text">Check Referral Display
                  Link</span></label>
              <input id="subscriber-check-referral-display-link" name="subscriber-check-referral-display-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-referral-link"><span class="bold-text">Legacy Referral Link
                  (MailerLite)</span></label>
              <input id="subscriber-legacy-referral-link" name="subscriber-legacy-referral-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-referral-display-link"><span class="bold-text">Legacy Referral Display Link
                  (MailerLite)</span></label>
              <input id="subscriber-legacy-referral-display-link" name="subscriber-legacy-referral-display-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-check-referral-link"><span class="bold-text">Legacy Check Referral Link
                  (MailerLite)</span></label>
              <input id="subscriber-legacy-check-referral-link" name="subscriber-legacy-check-referral-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-check-referral-display-link"><span class="bold-text">Legacy Check Referral
                  Display Link (MailerLite)</span></label>
              <input id="subscriber-legacy-check-referral-display-link" name="subscriber-legacy-check-referral-display-link" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-signup-parameters"><span class="bold-text">Signup Parameters</span></label>
              <input id="subscriber-signup-parameters" name="subscriber-signup-parameters" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-signup-source"><span class="bold-text">Signup Source</span></label>
              <input id="subscriber-signup-source" name="subscriber-signup-source" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referred-by-id">
                <span class="bold-text">Referred By (ID)</span>
              </label>
              <input id="subscriber-referred-by-id" name="subscriber-referred-by-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referred-by-email">
                <span class="bold-text">Referred By (Email)</span>
              </label>
              <input id="subscriber-referred-by-email" name="subscriber-referred-by-email" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-referral-count"><span class="bold-text">Referral Count</span></label>
              <input id="subscriber-referral-count" name="subscriber-referral-count" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-users-referred-id">
                <span class="bold-text">Confirmed Users Referred (ID)</span>
                <button type="button" class="btn btn-sprint ml-2" data-toggle="popover" title="Format Help" data-content="The format for this field is: id1,id2,id3 (comma-separated IDs with no space between them). Example: 58091820983098,359824908509283094,308529083509238"><strong>Format
                    Help</strong></button>
              </label>
              <input id="subscriber-users-referred-id" name="subscriber-users-referred-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-users-referred-email">
                <span class="bold-text">Confirmed Users Referred (Email)</span>
                <button type="button" class="btn btn-sprint ml-2" data-toggle="popover" title="Format Help" data-content="The format for this field is: email1,email2,email3 (comma-separated emails with no space between them). Example: matt@sidelinesprint.com,nathan@sidelinesprint.com,admin@sidelinesprint.com"><strong>Format
                    Help</strong></button>
              </label>
              <input id="subscriber-users-referred-email" name="subscriber-users-referred-email" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-unconfirmed-users-referred-id">
                <span class="bold-text">Unconfirmed Users Referred (ID)</span>
                <button type="button" class="btn btn-sprint ml-2" data-toggle="popover" title="Format Help" data-content="The format for this field is: id1,id2,id3 (comma-separated IDs with no space between them). Example: 58091820983098,359824908509283094,308529083509238"><strong>Format
                    Help</strong></button>
              </label>
              <input id="subscriber-unconfirmed-users-referred-id" name="subscriber-unconfirmed-users-referred-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-unconfirmed-users-referred-email">
                <span class="bold-text">Unconfirmed Users Referred (Email)</span>
                <button type="button" class="btn btn-sprint ml-2" data-toggle="popover" title="Format Help" data-content="The format for this field is: email1,email2,email3 (comma-separated emails with no space between them). Example: matt@sidelinesprint.com,nathan@sidelinesprint.com,admin@sidelinesprint.com"><strong>Format
                    Help</strong></button>
              </label>
              <input id="subscriber-unconfirmed-users-referred-email" name="subscriber-unconfirmed-users-referred-email" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-referred-by-id"><span class="bold-text">Legacy Referred By
                  (ID)</span></label>
              <input id="subscriber-legacy-referred-by-id" name="subscriber-legacy-referred-by-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-legacy-users-referred-id"><span class="bold-text">Legacy Users Referred
                  (ID)</span></label>
              <input id="subscriber-legacy-users-referred-id" name="subscriber-legacy-users-referred-id" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-first-subscribed-timestamp-utc"><span class="bold-text">First Subscribed Timestamp
                  (UTC)</span></label>
              <input id="subscriber-first-subscribed-timestamp-utc" name="subscriber-first-subscribed-timestamp-utc" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-unsubscribed-timestamp-utc"><span class="bold-text">Unsubscribed Timestamp
                  (UTC)</span></label>
              <input id="subscriber-unsubscribed-timestamp-utc" name="subscriber-unsubscribed-timestamp-utc" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-resubscribed-timestamp-utc"><span class="bold-text">Resubscribed Timestamp
                  (UTC)</span></label>
              <input id="subscriber-resubscribed-timestamp-utc" name="subscriber-resubscribed-timestamp-utc" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-confirmed-timestamp-utc"><span class="bold-text">Confirmed Timestamp
                  (UTC)</span></label>
              <input id="subscriber-confirmed-timestamp-utc" name="subscriber-confirmed-timestamp-utc" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-number-times-unsubscribed"><span class="bold-text">Number of Times
                  Unsubscribed</span></label>
              <input id="subscriber-number-times-unsubscribed" name="subscriber-number-times-unsubscribed" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-number-times-resubscribed"><span class="bold-text">Number of Times
                  Resubscribed</span></label>
              <input id="subscriber-number-times-resubscribed" name="subscriber-number-times-resubscribed" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-signup-ip-address"><span class="bold-text">Signup IP Address</span></label>
              <input id="subscriber-signup-ip-address" name="subscriber-signup-ip-address" class="form-control" type="text" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-emails-sent"><span class="bold-text">Emails Sent (MailerLite)</span></label>
              <input id="subscriber-emails-sent" name="subscriber-emails-sent" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-emails-opened"><span class="bold-text">Emails Opened (MailerLite)</span></label>
              <input id="subscriber-emails-opened" name="subscriber-emails-opened" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-links-clicked"><span class="bold-text">Links Clicked (MailerLite)</span></label>
              <input id="subscriber-links-clicked" name="subscriber-links-clicked" class="form-control" type="number" disabled></input>
            </div>
            <div class="form-group pb-15">
              <label for="subscriber-college-ambassador"><span class="bold-text">College Ambassador</span></label>
              <select name="subscriber-college-ambassador" id="subscriber-college-ambassador" class="form-control" disabled>
                <option value='1'>Yes</option>
                <option value='0'>No</option>
              </select>
            </div>
          </form>
          <div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-labelledby="confirm-update-modal-label" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="confirm-update-modal-label">Confirm Update</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <p>Are you sure you want to update this subscriber? All changes are permanent.</p>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal"><strong>Cancel</strong></button>
                  <button type="button" class="btn btn-sprint" name="confirm-update-subscriber" id="confirm-update-subscriber" data-dismiss="modal"><strong>Update</strong></button>
                </div>
              </div>
            </div>
          </div>
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
      $("#internal-tools-link, #existing-subscriber-management-link").addClass("active");
      $("#internal-tools-link").html('<strong>' + $("#internal-tools-link").text() + '</strong>');
      $("#existing-subscriber-management-link").html('<strong class="white-font">' + $("#existing-subscriber-management-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Initialize popovers.
      $('[data-toggle="popover"]').popover();

      // Dynamically show search fields.
      $("#lookup-field").change(function() {

        // Reset display classes.
        $('#lookup-email-div').addClass('d-none');
        $('#lookup-unique-id-div').addClass('d-none');
        $('#lookup-referral-id-div').addClass('d-none');
        $('#lookup-check-referral-id-div').addClass('d-none');

        // Reset form.
        $('#lookup-email').val('');
        $('#lookup-unique-id').val('');
        $('#lookup-referral-id').val('');
        $('#lookup-check-referral-id').val('');
        $("#lookup-subscriber").prop('disabled', true);

        // Show selected field.
        var selected_option = $('#lookup-field').val();
        if (selected_option == 'lookup-email-div') {
          $('#lookup-email-div').removeClass('d-none');
        } else if (selected_option == 'lookup-unique-id-div') {
          $('#lookup-unique-id-div').removeClass('d-none');
        } else if (selected_option == 'lookup-referral-id-div') {
          $('#lookup-referral-id-div').removeClass('d-none');
        } else if (selected_option == 'lookup-check-referral-id-div') {
          $('#lookup-check-referral-id-div').removeClass('d-none');
        }

      });

      // Disable search until field filled.
      $('#lookup-email, #lookup-unique-id, #lookup-referral-id, #lookup-check-referral-id').change(function() {
        var selected_option = $('#lookup-field').val();
        if (selected_option == 'lookup-email-div') {
          if ($("#lookup-email").val().length && isEmail($("#lookup-email").val())) {
            $("#lookup-subscriber").prop('disabled', false);
          } else {
            $("#lookup-subscriber").prop('disabled', true);
          }
        } else if (selected_option == 'lookup-unique-id-div') {
          if ($("#lookup-unique-id").val().length) {
            $("#lookup-subscriber").prop('disabled', false);
          } else {
            $("#lookup-subscriber").prop('disabled', true);
          }
        } else if (selected_option == 'lookup-referral-id-div') {
          if ($("#lookup-referral-id").val().length) {
            $("#lookup-subscriber").prop('disabled', false);
          } else {
            $("#lookup-subscriber").prop('disabled', true);
          }
        } else if (selected_option == 'lookup-check-referral-id-div') {
          if ($("#lookup-check-referral-id").val().length) {
            $("#lookup-subscriber").prop('disabled', false);
          } else {
            $("#lookup-subscriber").prop('disabled', true);
          }
        }

      });

      // Handle submission of subscriber lookup.
      $("#lookup-subscriber").click(function() {

        // Add hidden class.
        $('#update-div-toggle').addClass('d-none');

        // Reset output form data.
        $('#update-info-form').trigger("reset");

        // Get some values from elements on the page:
        var lookup_field = $("#lookup-field").val();
        if (lookup_field == "lookup-email-div") {
          lookup_value = $("#lookup-email").val();
        } else if (lookup_field == "lookup-unique-id-div") {
          lookup_value = $("#lookup-unique-id").val();
        } else if (lookup_field == "lookup-referral-id-div") {
          lookup_value = $("#lookup-referral-id").val();
        } else if (lookup_field == "lookup-check-referral-id-div") {
          lookup_value = $("#lookup-check-referral-id").val();
        }
        lookup_field = lookup_field.replace("lookup-", "");
        lookup_field = lookup_field.replace("-div", "");
        lookup_field = lookup_field.replace(/-/g, "_");

        // Send the data using post
        var posting = $.post("https://tools.sidelinesprint.com/utilities/get-subscriber", {
          lookup_field: lookup_field,
          lookup_value: lookup_value,
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
            json_fields = json_data["data"];
            Object.keys(json_fields).forEach(function(k) {
              if (json_fields[k] != null) {
                field_name = "subscriber-" + k.replace(/_/g, "-");
                $("#" + field_name).val(json_fields[k]);
              }
            });

            // Remove hidden class.
            $('#update-div-toggle').removeClass('d-none');

            // Remove disabled attribute. Can only update these fields.
            // $("#subscriber-status").prop("disabled", false);
            // $("#subscriber-college-ambassador").prop("disabled", false);

          }
        });

      });

      // Handle submission of subscriber update.
      $("#confirm-update-subscriber").click(function() {

        // Set variables from form.
        subscriber_email = $("#subscriber-email").val();
        subscriber_status = $("#subscriber-status").val();
        subscriber_college_ambassador = $("#subscriber-college-ambassador").val();

        // Send the data using post
        var posting = $.post("https://tools.sidelinesprint.com/utilities/update-subscriber", {
          subscriber_email: subscriber_email,
          subscriber_status: subscriber_status,
          subscriber_college_ambassador: subscriber_college_ambassador,
          api_key: user_internal_api_key
        });

        // Put the results in a div
        posting.done(function(data) {

          // Parse json
          json_data = JSON.parse(data);

          if (json_data["status"] == "failure") {

            // Display status for error.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');
            console.log(json_data);

          } else if (json_data["status"] == "success") {

            // Display status for success.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');

          }
        });

      });

    });
  </script>
</body>

</html>
