<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="disabled";
$navigation_disabled=true;
include ("header.php");
    if ($proceed) {
    echo '<center>
            <BR>
            <TABLE width=80%><TR><TD>';
        echo content__get_content("error_temporary_disabled");
        echo '
            </TD></TR></TABLE>

            </center>';

}
include ("footer.php");
?>