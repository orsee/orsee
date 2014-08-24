<?php
// part of orsee. see orsee.org

include ("cronheader.php");

if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) redirect("admin/");

$done=cron__run_cronjobs();


?>
