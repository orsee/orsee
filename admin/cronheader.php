<?php
// part of orsee. see orsee.org

include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

site__database_config();

$settings=load_settings();

$language=$settings['admin_standard_language'];

$lang=load_language($language);


?>
