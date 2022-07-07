<?php

# Auth0 configuration.
$AUTH0_DOMAIN = "-";
$AUTH0_CLIENT_ID = "-";
$AUTH0_CLIENT_SECRET = "-";
$AUTH0_REDIRECT_URI = "-";
$AUTH0_TOKEN_ENDPOINT = "-";
$AUTH0_AUDIENCE = "-";
$AUTH0_GRANT_TYPE = "-";

// Campaign Monitor information.
$main_subscriber_list_id = "-";
$cm_api_key = "-";
$cm_client_id = "-";

// DigitalOcean spaces config.
$spaces_token = "-";
$spaces_secret = "-";

// Bunny CDN config.
$bunny_api_key = "-";
$bunny_storage_key = "-";
$bunny_storage_name = "-";

// Postmark config.
$postmark_server_alert_stream_id = "-";

// Beehiiv config.
$beehiiv_external_embed_id = "-";
$beehiiv_publication_id = "-";

// Subscriber database information.
$main_subscriber_table = "-";
$subscriber_db_host = "-";
$subscriber_db_port = "-";
$subscriber_db_name = "-";
$subscriber_db_user = "-";
$subscriber_db_pass = "-";
$subscriber_db_cxn_str = "-";

// Staff database information.
$staff_table = "-";
$staff_db_host = "-";
$staff_db_port = "-";
$staff_db_name = "-";
$staff_db_user = "-";
$staff_db_pass = "-";
$staff_db_cxn_str = "-";

// ID generation information.
$referral_hash_salt = "-";
$check_referral_hash_salt = "-";

// Ghost login.
$ghost_username = "-";
$ghost_password = "-";

// API auth info.
$api_auth_array = array(
                        "-" => "-",
                        "-" => "-"
                       );

// Reference links.
$referral_base_link = "https://www.sidelinesprint.com/refer?id=";
$referral_display_base_link = "sidelinesprint.com/refer?id=";
$check_referral_base_link = "https://www.sidelinesprint.com/my-referrals?token=";
$check_referral_display_base_link = "sidelinesprint.com/my-referrals?token=";
$confirmed_base_link = "https://www.sidelinesprint.com/confirmed?id=";
$home_link = "https://www.sidelinesprint.com/";
$already_in_link = "https://www.sidelinesprint.com/already-in";
$error_link = "https://www.sidelinesprint.com/error";

// Sharing links.
$sms_base = "You should check out Sideline Sprint! It's a daily "
            . "sports newsletter delivered to your inbox. ";
$email_subject = "You Should Check Out Sideline Sprint!";
$email_base = "Hey, \n\n I love keeping up with the latest sports "
              . "news and I'm pretty sure you do too. That's why you "
              . "should check out Sideline Sprint. They're a sports "
              . "newsletter coming to your inbox five times a week - "
              . "completely free. Check it out today! \n\n ";
$twitter_base = "You should check out Sideline Sprint! It's a daily "
            . "sports newsletter delivered to your inbox. Join here:";
$facebook_base = "https://www.facebook.com/sharer.php?u=";
$twitter_base = "https://twitter.com/intent/tweet?text=" . rawurlencode($twitter_base) . "&via=SidelineSprint&url=";
$linkedin_base = "http://www.linkedin.com/shareArticle?url=";

?>
