<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="mainpage";
$title="";
$lang_icons_prepare=true;
include "header.php";

if ($proceed) {
    echo "<center><BR>";
    show_message();
    echo content__get_content("mainpage_welcome");
    if (!isset($addp)) $addp="";
    if ($addp) $sign="&"; else $sign="?";
    $langarray=lang__get_public_langs();
    $lang_names=lang__get_language_names();
    if  (count($langarray) > 1) {
        echo '<BR><BR>
            switch to ';
        foreach ($langarray as $thislang) {
            if ($thislang != lang('lang')) {
                echo '<A HREF="index.php'.$addp.$sign.'language='.$thislang.'">';
                echo '<span class="languageicon langicon-'.$thislang.'">';
                if ($lang_names[$thislang]) echo $lang_names[$thislang]; else echo $thislang;
                echo '</span>';
                echo '</A>&nbsp;&nbsp;&nbsp;';
            }
        }
    }
    echo "</center>";
}
include("footer.php");
?>
