<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="calendar";
$title="experiment_calendar";
include ("header.php");
if ($proceed) {
    if ($settings['show_public_calendar']!='y') redirect("public/");
}
if ($proceed) {
    $done=calendar__display_calendar(0);

}
include ("footer.php");
?>

