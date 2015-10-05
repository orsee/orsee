<?php
// part of orsee. see orsee.org
ob_start();
$suppress_html_header=true;
include ("header.php");
if ($proceed) {
    log__participant("logout",$participant['participant_id']);
    participant__logout();

    if (isset($_REQUEST['mobile']) && $_REQUEST['mobile']) redirect("public/participant_login_mob.php?logout=true");
    else redirect("public/participant_login.php?logout=true");
}
include ("footer.php");
?>
