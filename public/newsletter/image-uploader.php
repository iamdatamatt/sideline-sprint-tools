<?php

// Load in packages.
session_start();
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/secure/bunnycdn-storage.php";
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

// Get recent images from Bunny CDN.
$bunnyCDNStorage = new BunnyCDNStorage($bunny_storage_name, $bunny_storage_key, "ny");
$primary_base = "https://public.sidelinesprint.com/images/";
$secondary_base = "https://sidelinesprint-public.b-cdn.net/images/";
$cdn_path = "/sidelinesprint/images/";
$cdn_listing = $bunnyCDNStorage->getStorageObjects($cdn_path);
$table_array = array();
foreach ($cdn_listing as $item) {
  $item_name = $item->ObjectName;
  $item_created = $item->DateCreated;
  $item_primary_link = $primary_base . $item->ObjectName;
  $item_secondary_link = $secondary_base . $item->ObjectName;
  $item_array = array("name" => $item_name, "created" => $item_created, "primary_link" => $item_primary_link, "secondary_link" => $item_secondary_link);
  $table_array[] = $item_array;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Image Uploader | Sideline Sprint</title>
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
  <style>
    .input-sprint-fill {
      background-color: #67ca88;
      border-radius: 5px;
      padding: 5px 5px 5px 5px
    }

    .input-sprint-adj {
      border-top-right-radius: 5px !important;
      border-bottom-right-radius: 5px !important
    }
  </style>
</head>

<body class="body-font">
  <?php include '/var/www/html/secure/navigation.php'; ?>
  <div class="jumbotron jumbotron-fluid jumbotron-sprint">
    <div class="container">
      <div class="row pb-1">
        <div class="col-lg-12 text-center pt-1">
          <img src="https://cdn-tools.sidelinesprint.com/img/logo_placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/main_logo.png" alt="The Sideline Sprint logo." width="500" height="45" class="img-fluid lazyload pb-15">
          <h2 class="green-text bold-text pt-15">Image Uploader</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <form name="upload-img-form" id="upload-img-form">
          <div class="form-group pt-15">
            <label for="img-file"><span class="bold-text">Select Image to Upload</span></label>
            <input id="img-file" name="img-file" type="file" accept="image/*,video/*" class="form-control article-values" style="border:0px;padding-left:0px;">
          </div>
        </form>
        <div class="pb-15">
          <button class="btn btn-sprint" name="upload-img-button" id="upload-img-button" disabled><strong>Upload</strong></button>
        </div>
        <div id="status-div" name="status-div" class="pb-15" style="border:1px solid #d3d3d3;border-radius:5px; padding:10px;background:#f5f3f2;display:none;">
          <p name="status-span" id="status-span" style="font-weight:700;"></p>
          <p name="primary-link-span" id="primary-link-span" style="display:none;">Primary Link:</p>
          <div id="copy-1-div" name="copy-1-div" style="display:none;">
            <div class="input-group mb-4 input-sprint-fill">
              <input id="primary-link-2" name="primary-link-2" type="text" class="form-control input-sprint-adj" value="" aria-label="Primary Link" readonly>
              <button type="submit" class="btn btn-primary btn-signup text-nowrap" id="button-1-copy" data-container="body" data-clipboard-target="#primary-link-2" style="padding-left:16px;">Copy</button>
            </div>
          </div>
          <p name="backup-link-span" id="backup-link-span" style="display:none;">Backup Link:</p>
          <div id="copy-2-div" name="copy-2-div" style="display:none;">
            <div class="input-group mb-4 input-sprint-fill">
              <input id="secondary-link-2" name="secondary-link-2" type="text" class="form-control input-sprint-adj" value="" aria-label="Secondary Link" readonly>
              <button type="submit" class="btn btn-primary btn-signup text-nowrap" id="button-2-copy" data-container="body" data-clipboard-target="#secondary-link-2" style="padding-left:16px;">Copy</button>
            </div>
          </div>
          <p name="preview-span" id="preview-span" style="display:none;">Preview:</p>
          <img src="https://cdn-tools.sidelinesprint.com/img/logo_placeholder.png" alt="Preview should appear here." name="preview-img" id="preview-img" class="img-fluid" style="display:none;">
        </div>
        <div class="pt-15 pb-15 mt-15">
          <span class="bold-text">Recently Uploaded Images</span>
        </div>
        <table id="item_table" class="table table-striped table-bordered nowrap" width="100%">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Created</th>
              <th scope="col">Primary Link</th>
              <th scope="col">Backup Link</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($table_array as $table_entry) {
              echo "<tr>
                    <td>{$table_entry["name"]}</td>
                    <td>{$table_entry["created"]}</td>
                    <td><a href=\"{$table_entry["primary_link"]}\" target=\"_blank\">Primary Link</a></td>
                    <td><a href=\"{$table_entry["secondary_link"]}\" target=\"_blank\">Backup Link</a></td>
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
  <script src="https://cdn-tools.sidelinesprint.com/js/bootstrap-multiselect.js"></script>
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap-multiselect.css">
  <script src="https://cdn-tools.sidelinesprint.com/js/datatables.min.js"></script>
  <script src="https://cdn.sidelinesprint.com/js/clipboard.min.js"></script>
  <script>
    function setTooltip1(t) {
      $("#button-1-copy").tooltip({
        container: "body"
      }), $("#button-1-copy").attr("data-original-title", t).tooltip("show")
    }

    function hideTooltip1() {
      setTimeout(function() {
        $("#button-1-copy").tooltip("hide")
      }, 1e3)
    }

    function setTooltip2(t) {
      $("#button-2-copy").tooltip({
        container: "body"
      }), $("#button-2-copy").attr("data-original-title", t).tooltip("show")
    }

    function hideTooltip2() {
      setTimeout(function() {
        $("#button-2-copy").tooltip("hide")
      }, 1e3)
    }
  </script>
  <script>
    $("#button-1-copy").tooltip({
      trigger: "click",
      placement: "top"
    });
    var clipboard = new ClipboardJS("#button-1-copy");
    clipboard.on("success", function(o) {
      setTooltip1("Copied!"), o.clearSelection(), hideTooltip1()
    }), clipboard.on("error", function(o) {
      setTooltip1("Failed!"), o.clearSelection(), hideTooltip1()
    });
    $("#button-2-copy").tooltip({
      trigger: "click",
      placement: "top"
    });
    var clipboard = new ClipboardJS("#button-2-copy");
    clipboard.on("success", function(o) {
      setTooltip2("Copied!"), o.clearSelection(), hideTooltip2()
    }), clipboard.on("error", function(o) {
      setTooltip2("Failed!"), o.clearSelection(), hideTooltip2()
    });
  </script>
  <script>
    $(document).ready(function() {

      // Get variables from authentication.
      var user_email = "<?php echo $user_email; ?>";
      var user_name = "<?php echo $user_name; ?>";
      var user_profile_pic = "<?php echo $user_profile_pic; ?>";
      var user_internal_api_key = "<?php echo $user_internal_api_key; ?>";

      // Load nav.
      $("#internal-tools-link, #image-uploader-link").addClass("active");
      $("#internal-tools-link").html('<strong>' + $("#internal-tools-link").text() + '</strong>');
      $("#image-uploader-link").html('<strong class="white-font">' + $("#image-uploader-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Initialize datatable.
      $('#item_table').DataTable({
        "scrollX": true,
        "pagingType": "full",
        "searching": true,
        "info": true,
        "order": [
          [1, "desc"]
        ]
      });

      // Disable confirm creation button until all required fields entered.
      $('#img-file').change(function() {
        if ($('#img-file').get(0).files.length === 0) {
          $("#upload-img-button").prop('disabled', true);
        } else {
          $("#upload-img-button").prop('disabled', false);
        }
      });

      // Start submit in confirm creation.
      $("#upload-img-button").click(function() {

        // Hide areas.
        $('#primary-link-span').css('display', 'none');
        $('#copy-1-div').css('display', 'none');
        $('#backup-link-span').css('display', 'none');
        $('#copy-2-div').css('display', 'none');
        $('#preview-span').css('display', 'none');
        $('#preview-img').css('display', 'none');

        // Prep filename.
        var filename = $('#img-file')[0].files[0]['name'];
        var arr = filename.split(".");
        var lastVal = arr.pop();
        var firstVal = arr.join(".");
        var timename = firstVal.concat("_", Date.now(), ".", lastVal);
        var encoded_filename = encodeURIComponent(timename);

        // Disable submit button.
        $("#upload-img-button").prop('disabled', true);
        $('#primary-link-2').val("");
        $('#secondary-link-2').val("");
        $("#preview-img").attr("src", "");

        // Show message of submission.
        $('#status-span').text("Uploading image...");

        // Show status area.
        $('#status-div').css('display', 'block');

        // Prep request.
        var file_data = $("#img-file").prop('files')[0];
        var form_data = new FormData();
        form_data.append('file', file_data);
        form_data.append('filename', encoded_filename);
        form_data.append('api_key', user_internal_api_key);

        // Send the data using post.
        $.ajax({
          url: 'https://tools.sidelinesprint.com/utilities/upload-image',
          dataType: 'text',
          cache: false,
          contentType: false,
          processData: false,
          data: form_data,
          type: 'post',
          success: function(data) {

            // Parse json.
            json_data = JSON.parse(data);

            if (json_data["status"] == "success") {

              // Display status for success.
              $('#status-span').text(json_data["message"].concat(" Image links will be displayed in the table below the next time you refresh the page."));
              $('#primary-link-2').val(json_data["primary_link"]);
              $('#secondary-link-2').val(json_data["secondary_link"]);
              $("#preview-img").attr("src", json_data["primary_link"]);
              $('#primary-link-span').css('display', 'block');
              $('#copy-1-div').css('display', 'block');
              $('#backup-link-span').css('display', 'block');
              $('#copy-2-div').css('display', 'block');
              $('#preview-span').css('display', 'block');
              $('#preview-img').css('display', 'block');

              // Reset form data.
              $('#upload-img-form').trigger("reset");

            } else if (json_data["status"] == "failure") {

              // Display status for error.
              $('#status-span').text(json_data["message"]);
              $("#upload-img-button").prop('disabled', false);
              console.log(json_data["error"]);

            }
          }
        });

      });

    });
  </script>
</body>

</html>
