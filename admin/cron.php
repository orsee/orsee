<?php
// part of orsee. see orsee.org

include ("cronheader.php");

if (php_sapi_name() == "cli") $done=cron__run_cronjobs();
else redirect("admin/");

?>
