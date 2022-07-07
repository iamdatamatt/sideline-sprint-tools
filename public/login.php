<?php
// login.php

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

$auth0->login();

?>