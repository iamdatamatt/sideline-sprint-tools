<?php

// Load packages.
session_start();
require '/var/www/html/secure/sprint-configuration.php';
require '/var/www/html/vendor/autoload.php';
use Auth0\SDK\Auth0;

// Handle errors sent back by Auth0.
if (! empty($_GET['error']) || ! empty($_GET['error_description'])) {
  printf('<h1>Error</h1><p>%s</p>', htmlspecialchars( $_GET['error_description']));
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
    header("Location: https://tools.sidelinesprint.com/login");
    exit();
} else {
  if (isset($_COOKIE["SPRINT-ORIGIN"])) {
    $redir_url = $_COOKIE["SPRINT-ORIGIN"];
    $get_params = http_build_query($_GET) ?? "";
    $redir_url = $redir_url . "?" . $get_params;
    setcookie("SPRINT-ORIGIN", "", time() - 3600);
    header("Location: {$redir_url}");
    exit();
  } else {
    header("Location: https://tools.sidelinesprint.com/");
    exit();
  }
}

?>