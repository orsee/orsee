<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="rules";
$title="rules";
include ("header.php");
if ($proceed) {
    if ($settings['show_public_rules_page']!='y') redirect("public/");
}
if ($proceed) {
    echo '<center><BR>
            <TABLE class="or_formtable" style="width: 80%"><TR><TD>';
        echo content__get_content("rules");
        echo '
            </TD></TR></TABLE>

            </center>';

}
include ("footer.php");
?>