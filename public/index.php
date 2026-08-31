<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="mainpage_welcome";
$title="";
$lang_icons_prepare=true;
include "header.php";

if ($proceed) {
    $menu_item=(isset($GLOBALS['public__menu_page_item']) && is_array($GLOBALS['public__menu_page_item']) ? $GLOBALS['public__menu_page_item'] : false);
    $show_mainpage=(isset($GLOBALS['public__show_mainpage']) && $GLOBALS['public__show_mainpage']);
    if (!$show_mainpage && !is_array($menu_item)) {
        redirect('public/');
    }

    echo '<div id="orsee-public-mobile-screen">';
    echo '  <div class="orsee-public-faq-panel">';
    echo '    <div class="orsee-panel">';
    show_message();
    $content_name=(isset($menu_item['content_name']) && trim((string)$menu_item['content_name'])!=='' ? (string)$menu_item['content_name'] : 'mainpage_welcome');
    echo '<div class="orsee-richtext">'.content__get_content($content_name).'</div>';
    if ($show_mainpage) {
        $addp='';
        $sign='?';
        if (in_array($settings['subject_authentication'],array('token','migration'),true) && isset($_REQUEST['p']) && trim((string)$_REQUEST['p'])!=='') {
            $addp='?p='.urlencode((string)$_REQUEST['p']);
            $sign="&";
        }
        $langarray=lang__get_public_langs();
        $lang_names=lang__get_language_names();
        $hide_lang_flags=(isset($settings['hide_public_index_language_flags']) && $settings['hide_public_index_language_flags']==='y');
        if (lang__is_rtl()) {
            $langarray=array_reverse($langarray);
        }
        if (count($langarray) > 1) {
            echo '<div class="orsee-public-langswitch">';
            echo '<span class="orsee-public-langswitch-label">switch to</span>';
            echo '<span class="orsee-public-langswitch-links">';
            foreach ($langarray as $thislang) {
                if ($thislang != lang('lang')) {
                    echo '<A HREF="index.php'.$addp.$sign.'language='.$thislang.'" class="orsee-public-langswitch-link">';
                    if ($hide_lang_flags) {
                        echo '<span>';
                    } else {
                        echo '<span class="languageicon langicon-'.$thislang.'">';
                    }
                    if ($lang_names[$thislang]) {
                        echo $lang_names[$thislang];
                    } else {
                        echo $thislang;
                    }
                    echo '</span>';
                    echo '</A>';
                }
            }
            echo '</span>';
            echo '</div>';
        }
    }
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}
include("footer.php");

?>
