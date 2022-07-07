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

// Get authors from Ghost.
$authors_curl = curl_init();
curl_setopt_array($authors_curl, array(
  CURLOPT_URL => "-",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
));
$authors_response = curl_exec($authors_curl);
$authors_err = curl_error($authors_curl);
curl_close($authors_curl);
if ($authors_err) {
  $authors_loaded = false;
} else {
  $authors_loaded = true;
  $authors_data = json_decode($authors_response, true);
  $authors_list = $authors_data["authors"];
}

// Get documents from Bunny CDN.
$bunnyCDNStorage = new BunnyCDNStorage($bunny_storage_name, $bunny_storage_key, "ny");
$folder_year = date("Y");
$folder_month = date("m");
$base_link = "https://sidelinesprint-public.b-cdn.net/newsletters/html/" . $folder_year . "/" . $folder_month . "/";
$cdn_path = "/sidelinesprint/newsletters/html/" . $folder_year . "/" . $folder_month . "/";
$cdn_listing = $bunnyCDNStorage->getStorageObjects($cdn_path);
$table_array = array();
foreach ($cdn_listing as $item) {
  $item_name = $item->ObjectName;
  $item_created = $item->DateCreated;
  $item_link = $base_link . $item->ObjectName;
  $item_array = array("name" => $item_name, "created" => $item_created, "link" => $item_link);
  $table_array[] = $item_array;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Website Article Uploader | Sideline Sprint</title>
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
          <h2 class="green-text bold-text pt-15">Website Article Uploader</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="pb-15">
          <span class="bold-text">Most Recent Newsletters</span>
        </div>
        <table id="item_table" class="table table-striped table-bordered nowrap" width="100%">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Created</th>
              <th scope="col">Link</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($table_array as $table_entry) {
              echo "<tr>
                    <td>{$table_entry["name"]}</td>
                    <td>{$table_entry["created"]}</td>
                    <td><a href=\"{$table_entry["link"]}\" target=\"_blank\">Link</a></td>
                    </tr>";
            } ?>
          </tbody>
        </table>
        <form name="fetch-url-form" id="fetch-url-form">
          <div class="form-group pt-15">
            <label for="url"><span class="bold-text">Fetch HTML from URL (Optional)</span></label>
            <input id="url" name="url" type="text" class="form-control article-values" placeholder="URL">
          </div>
        </form>
        <div class="pb-15">
          <button class="btn btn-sprint" name="fetch-url-button" id="fetch-url-button" disabled><strong>Fetch</strong></button>
        </div>
        <form method="post" name="article-form" id="article-form">
          <div class="form-group pt-15 pb-15">
            <label for="article-html"><span class="bold-text">Article HTML</span></label>
            <textarea id="article-html" name="article-html" rows="6" cols="50" class="form-control article-values" required></textarea>
          </div>
          <div class="form-group pb-15">
            <label for="article-title"><span class="bold-text">Article Title</span></label>
            <textarea id="article-title" name="article-title" rows="2" cols="50" class="form-control article-values" required>Title</textarea>
          </div>
          <div class="form-group pb-15">
            <label for="article-meta-title"><span class="bold-text">Article Meta Title</span></label>
            <textarea id="article-meta-title" name="article-meta-title" rows="2" cols="50" class="form-control article-values" required>Title | Sideline Sprint - Your Daily Sports Email Newsletter</textarea>
            <div id="title-count" style="text-align:right;">
              <span id="title-current">60</span>
              <span id="title-maximum">/ 60</span>
            </div>
          </div>
          <div class="form-group pb-15">
            <label for="article-excerpt"><span class="bold-text">Article Excerpt</span></label>
            <textarea id="article-excerpt" name="article-excerpt" rows="2" cols="50" class="form-control article-values" required>In today's Sprint, CHANGE HERE. Brought to you by Sideline Sprint.</textarea>
            <div id="description-count" style="text-align:right;">
              <span id="description-current">78</span>
              <span id="description-maximum">/ 160</span>
            </div>
          </div>
          <div class="form-group pb-15">
            <label for="article-image"><span class="bold-text">Article Image URL (Optional)</span></label>
            <input id="article-image" name="article-image" class="form-control article-values">
          </div>
          <div class="form-group pb-15">
            <label for="article-authors"><span class="bold-text">Article Authors</span></label>
            <br>
            <select name="article-authors[]" id="article-authors" class="form-control article-values" multiple required>
              <?php
              foreach ($authors_list as $item) {
                $id = $item["id"];
                $name = $item["name"];
                if (strpos($name, "Blake") !== false) {
                  echo "<option value='$id' selected>$name</option>";
                } elseif (strpos($name, "Nathan") !== false) {
                  echo "<option value='$id' selected>$name</option>";
                } elseif (strpos($name, "Matt") !== false) {
                  echo "<option value='$id' selected>$name</option>";
                } else {
                  echo "<option value='$id'>$name</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class="form-group pb-15">
            <label for="article-date"><span class="bold-text">Article Publish Date</span></label>
            <input type="date" id="article-date" name="article-date" class="form-control article-values" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group pb-15">
            <label for="article-time"><span class="bold-text">Article Publish Time</span></label>
            <input type="time" id="article-time" name="article-time" class="form-control article-values" value="08:00" required>
          </div>
        </form>
        <button class="btn btn-sprint" name="create-article" id="create-article" data-toggle="modal" data-target="#create-article-modal" disabled><strong>Create Article</strong></button>
        <hr>
        <p class="footer"> &copy; Sideline Sprint 2021. All rights reserved.</p>
      </div>
    </div>
  </div>
  <div class="modal fade" id="create-article-modal" tabindex="-1" aria-labelledby="create-article-modal-label" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="create-article-modal-label">Confirm Submission</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Are you sure you want to create this article?
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
  <script src="https://cdn-tools.sidelinesprint.com/js/bootstrap-multiselect.js"></script>
  <link rel="stylesheet" href="https://cdn-tools.sidelinesprint.com/css/bootstrap-multiselect.css">
  <script src="https://cdn-tools.sidelinesprint.com/js/datatables.min.js"></script>
  <script>
    $(document).ready(function() {

      // Get variables from authentication.
      var user_email = "<?php echo $user_email; ?>";
      var user_name = "<?php echo $user_name; ?>";
      var user_profile_pic = "<?php echo $user_profile_pic; ?>";
      var user_internal_api_key = "<?php echo $user_internal_api_key; ?>";

      // Load nav.
      $("#internal-tools-link, #website-article-uploader-link").addClass("active");
      $("#internal-tools-link").html('<strong>' + $("#internal-tools-link").text() + '</strong>');
      $("#website-article-uploader-link").html('<strong class="white-font">' + $("#website-article-uploader-link").text() + '</strong> <span class="sr-only">(current)</span>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

      // Initialize multiselect for authors.
      $('#article-authors').multiselect();

      // Show status of loading authors.
      <?php echo "var authors_loaded = {$authors_loaded};"; ?>
      if (authors_loaded) {
        $('#status-toast-body').text('Authors successfully loaded from Ghost.');
        $('#status-toast').toast('show');
      } else {
        $('#status-toast-body').text('Error loading authors from Ghost.');
        $('#status-toast').toast('show');
      }

      // Initialize datatable.
      $('#item_table').DataTable({
        "scrollX": true,
        "pagingType": "full",
        "searching": false,
        "info": false,
        "order": [[ 1, "desc" ]]
      });

      // Disable confirm creation button until all required fields entered.
      $('#article-html, #article-title, #article-meta-title, #article-excerpt, #article-authors, #article-date, #article-time').change(function() {
        if ($("#article-html").val().length &&
          $("#article-title").val().length &&
          $("#article-meta-title").val().length &&
          $("#article-excerpt").val().length &&
          $("#article-authors").val().length &&
          $("#article-date").val().length &&
          $("#article-time").val().length) {
          $("#create-article").prop('disabled', false);
        } else {
          $("#create-article").prop('disabled', true);
        }
      });

      // Disable fetch until URL entered.
      $('#url').change(function() {
        if ($("#url").val().length) {
          $("#fetch-url-button").prop('disabled', false);
        } else {
          $("#fetch-url-button").prop('disabled', true);
        }
      });

      // Fetch HTML from url on click.
      $("#fetch-url-button").click(function() {
        var url = $("#url").val();
        $.get(url, function(data) {
          $("#article-html").val(data);
          var mainTitle = $(data).filter('title').text();
          var metaDescription = $(data).filter('meta[name=description]').attr('content');
          var metaDescription = "In today's Sprint, ".concat(metaDescription);
          var metaDescription = metaDescription.concat(". Brought to you by Sideline Sprint.")
          $("#article-title").val(mainTitle);
          $("#article-meta-title").val(mainTitle.concat(" | Sideline Sprint - Your Daily Sports Email Newsletter"));
          $("#article-excerpt").val(metaDescription);
          $('#status-toast-body').text("Successfully loaded HTML from URL!");
          $('#status-toast').toast('show');
        });
      });

      // Character counters for textareas.
      $('#article-meta-title').keyup(function() {
        var characterCount = $(this).val().length,
          current = $('#title-current'),
          maximum = 60,
          theCount = $('#title-count');
        current.text(characterCount);
        if (characterCount > maximum) {
          current.css('color', '#ff0000');
        } else {
          current.css('color', '#000000');
        }
      });
      $('#article-excerpt').keyup(function() {
        var characterCount = $(this).val().length,
          current = $('#description-current'),
          maximum = 160,
          theCount = $('#description-count');
        current.text(characterCount);
        if (characterCount > maximum) {
          current.css('color', '#ff0000');
        } else {
          current.css('color', '#000000');
        }
      });

      // Start submit in confirm creation.
      $("#confirm-create").click(function() {

        // Disable submit button.
        $("#create-article").prop('disabled', true);

        // Show message of submission.
        $('#status-toast-body').text("Creating article...");
        $('#status-toast').toast('show');

        // Get form variables.
        var article_html = $("#article-html").val();
        var article_title = $("#article-title").val();
        var article_meta_title = $("#article-meta-title").val();
        var article_excerpt = $("#article-excerpt").val();
        var article_image = $("#article-image").val();
        var article_authors = $("#article-authors").val();
        var article_date = $("#article-date").val();
        var article_time = $("#article-time").val();

        // Send the data using post
        var posting = $.post("https://tools.sidelinesprint.com/utilities/upload-article", {
          article_html: article_html,
          article_title: article_title,
          article_meta_title: article_meta_title,
          article_excerpt: article_excerpt,
          article_image: article_image,
          article_authors: article_authors,
          article_date: article_date,
          article_time: article_time,
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
            $('#article-form').trigger("reset");
            $('#fetch-url-form').trigger("reset");
            $('#article-authors').multiselect("refresh");

          } else if (json_data["status"] == "failure") {

            // Display status for error.
            $('#status-toast-body').text(json_data["message"]);
            $('#status-toast').toast('show');
            console.log(json_data["error"]);

          }

        });

      });

    });
  </script>
</body>

</html>
