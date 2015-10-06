<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="mainpage";
$title="welcome";

include("header.php");
if ($proceed) {
    echo '<center>';

    show_message();

    echo content__get_content("admin_mainpage");

    echo '</center><BR><BR>';

}
include("footer.php");

?>
