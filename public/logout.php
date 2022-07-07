<?php

// Load packages.
session_start();
require '/var/www/html/secure/sprint-configuration.php';
require '/var/www/html/vendor/autoload.php';
use Auth0\SDK\Auth0;

// Instantiate the base Auth0 class.
$auth0 = new Auth0([
    'domain' => $AUTH0_DOMAIN,
    'client_id' =>  $AUTH0_CLIENT_ID,
    'client_secret' => $AUTH0_CLIENT_SECRET,
    'redirect_uri' => $AUTH0_REDIRECT_URI,
        ]);

$auth0->logout();
$return_to = 'https://tools.sidelinesprint.com';
$logout_url = sprintf('http://%s/v2/logout?client_id=%s&returnTo=%s', 'sidelinesprint.us.auth0.com', '-', $return_to);
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(),'',0,'/');
session_regenerate_id(true);
header('Location: ' . $logout_url);
die();