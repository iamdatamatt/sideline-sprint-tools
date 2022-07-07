<?php

/*
This should be scheduled using cron and run nightly. Example command is:
0 4 * * * /usr/bin/php /var/www/ghost/system/secure/nightly-id-check.php
*/

// Required packages.
require_once "/var/www/html/secure/sprint-configuration.php";
require_once "/var/www/html/secure/sprint-tools.php";
require_once "/var/www/html/vendor/autoload.php";

try {

  // Get count of IDs from database.
  $db_cxn = pg_connect($subscriber_db_cxn_str);
  $id_check_query = "SELECT * FROM
                      (SELECT unique_id, count(*) as id_count
                       FROM main_newsletter
                       GROUP BY unique_id) a
                    WHERE a.id_count > 1";
  $id_check_result = pg_query_params($db_cxn, $id_check_query, array());
  pg_close($db_cxn);
  if ($id_check_result == FALSE) {
    throw new Exception("Error getting ID counts from database in overnight batch processing.");
  }

  // If subscribers share an ID, send an alert.
  if (pg_num_rows($id_check_result) != 0) {
    server_alert("Multiple subscribers share the same unique IDs. Investigate ASAP.");
  }

} catch (Exception $e) {

  // Send alert if fatal error.
  server_alert($e->getMessage());
  die();

}
