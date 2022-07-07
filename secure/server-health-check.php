<?php

//
// This shoulb be scheduled using cron and run every 5 minutes. Example command is:
// */5 * * * * /usr/bin/php /var/www/html/secure/server-health-check.php
//

// Required packages.
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/vendor/autoload.php";

// Make HEAD request to check server status.
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.sidelinesprint.com");
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Sent alert if server is not up.
if ($code != 200) {
  server_alert("Main server returning HTTP code " . $code . ". Investigate ASAP.");
}
