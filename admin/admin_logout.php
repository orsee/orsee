<?php
ob_start();

include ("nonoutputheader.php");

log__admin("logout");
admin__logout();

redirect("admin/admin_login.php?logout=true");

include ("footer.php");

?>
