<?php

include ("cronheader.php");

if (thisdoc()) redirect("/");

$done=cron__run_cronjobs();


?>
