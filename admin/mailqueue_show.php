<?php
// part of orsee. see orsee.org
ob_start();

$jquery=array('popup');
$title="mailqueue";
$menu__area="statistics";
include ("header.php");
if ($proceed) {
    $allow=check_allow('mailqueue_show_all','statistics_main.php');
}

if ($proceed) {
    echo '<center>';

    mailqueue__show_mailqueue();

    echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>