<?php
ob_start();

require "config/settings.php";
require "config/system.php";
require "site.php";

redirect("public/");

?>
