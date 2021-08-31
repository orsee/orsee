<?php
ob_start();

require "config/settings.php";
require "config/system.php";
require "site.php";

$currentCookieParams = session_get_cookie_params();

// set cookie flags secure and http
session_set_cookie_params(
    $currentCookieParams['lifetime'],
    $currentCookieParams['path'],
    $currentCookieParams['domain'],
    true,
    true
);
header('X-Frame-Options: SAMEORIGIN');

error_reporting(0);  // shut down all error reporting

redirect("public/");

?>
