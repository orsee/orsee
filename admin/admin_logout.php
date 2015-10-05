<?php
// part of orsee. see orsee.org
ob_start();

include ("nonoutputheader.php");
if ($proceed) {

    log__admin("logout");
    admin__logout();

    redirect("admin/admin_login.php?logout=true");
    $proceed=false;

}
include ("footer.php");
?>
