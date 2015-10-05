<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="privacy";
$title="privacy_policy";
include ("header.php");
if ($proceed) {
    if ($settings['show_public_privacy_policy']!='y') redirect("public/");
}
if ($proceed) {
    echo '<center><BR>
            <TABLE class="or_formtable" style="width: 80%"><TR><TD>';
        echo content__get_content("privacy_policy");
        echo '
            </TD></TR></TABLE>

            </center>';

}
include ("footer.php");
?>