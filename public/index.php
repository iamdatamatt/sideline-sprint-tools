<?php

// Load packages.
session_start();
require '/var/www/html/secure/sprint-tools.php';
require '/var/www/html/secure/sprint-configuration.php';
require '/var/www/html/vendor/autoload.php';

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
  <title>Tools Directory | Sideline Sprint</title>
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
  <style>
    .bw-auto {
      min-width: 25% !important;
      max-width: 35% !important;
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
          <h2 class="green-text bold-text pt-15">Tools Directory</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12 text-center">
        <div class="alert alert-success" role="alert">
          <span>Select from the most used tools below or use the top navigation bar to view all tools!</span>
        </div>
      </div>
      <div class="col-lg-4 text-center">
        <div class="border-box">
          <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-512x512.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
          <h5 class="pt-1"><strong>Beehiiv Overview Dashboard</strong></h5>
          <div class="mh-48">
            <p>View recent signups and feedback on newsletters.</p>
          </div>
          <hr>
          <a href="https://tools.sidelinesprint.com/insights/beehiiv-overview"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
        </div>
      </div>
      <div class="col-lg-4 text-center">
        <div class="border-box">
          <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-512x512.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
          <h5 class="pt-1"><strong>Website Article Uploader</strong></h5>
          <div class="mh-48">
            <p>Upload newsletters to our site as posts.</p>
          </div>
          <hr>
          <a href="https://tools.sidelinesprint.com/website/website-article-uploader"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
        </div>
      </div>
      <div class="col-lg-4 text-center">
        <?php if ($user_role === "admin") { ?>
          <div class="border-box">
            <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-512x512.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
            <h5 class="pt-1"><strong>Image Uploader</strong></h5>
            <div class="mh-48">
              <p>Upload images to our content delivery network.</p>
            </div>
            <hr>
            <a href="https://tools.sidelinesprint.com/newsletter/image-uploader"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
          </div>
        <?php } ?>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-4 text-center">
        <?php if ($user_role === "admin") { ?>
          <div class="border-box">
            <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-512x512.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
            <h5 class="pt-1"><strong>Single Subscriber Search</strong></h5>
            <div class="mh-48">
              <p>View the details for an existing subscriber.</p>
            </div>
            <hr>
            <a href="https://tools.sidelinesprint.com/subscribers/single-subscriber-search"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
          </div>
        <?php } ?>
      </div>
      <div class="col-lg-4 text-center">
        <?php if ($user_role === "admin") { ?>
          <div class="border-box">
            <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-512x512.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
            <h5 class="pt-1"><strong>Bulk Subscriber Search</strong></h5>
            <div class="mh-48">
              <p>Pull details on a group of subscribers.</p>
            </div>
            <hr>
            <a href="https://tools.sidelinesprint.com/subscribers/bulk-subscriber-search"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
          </div>
        <?php } ?>
      </div>
      <div class="col-lg-4 text-center">
        <?php if ($user_role === "admin") { ?>
          <div class="border-box">
            <img src="https://cdn-tools.sidelinesprint.com/img/icon-placeholder.png" data-src="https://cdn-tools.sidelinesprint.com/img/beehiiv.png" height="128px" width="128px" class="img-fluid img-responsive lazyload">
            <h5 class="pt-1"><strong>Beehiiv</strong></h5>
            <div class="mh-48">
              <p>Visit our email sending portal.</p>
            </div>
            <hr>
            <a href="https://app.beehiiv.com/" target="_blank"><button type="button" class="btn btn-signup bw-auto"><strong>&rarr;</strong></button></a>
          </div>
        <?php } ?>
      </div>
    </div>
    <div class="row" class="pb-1">
      <div class="col-lg-12 text-center pb-1">
        <hr>
        <span class="font-off-white">&copy; Sideline Sprint 2021. All rights reserved.</span>
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

      // Load navigation.
      $("#directory-link").addClass("active");
      $("#directory-link").html('<strong>' + $("#directory-link").text() + '</strong>');
      $("#navigation-placeholder").removeAttr("style");
      $("#name-link").text(user_name);
      $("#email-link").text(user_email);
      $("#user-pic").attr("data-src", user_profile_pic);
      $("#user-pic").attr("src", user_profile_pic);

    });
  </script>
</body>

</html>
