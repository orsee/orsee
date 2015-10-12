<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="impressum";
$title="impressum";
include ("header.php");
if ($proceed) {
    if ($settings['show_public_legal_notice']!='y') redirect("public/");
}
if ($proceed) {

    echo '<center>
            <TABLE class="or_formtable" style="width: 80%"><TR><TD>';
        echo content__get_content("impressum");
        echo '
            </TD></TR></TABLE>

            </center>';

}
include ("footer.php");
?>