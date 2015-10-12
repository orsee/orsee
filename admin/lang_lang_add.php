<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="add_language";
include ("header.php");
if ($proceed) {
    $allow=check_allow('lang_lang_add','lang_main.php');
}

if ($proceed) {
    echo '<center>';

    // load languages
    $languages=get_languages();

    if (isset($_REQUEST['nlang_sc'])) $nlang_sc=strtolower(trim($_REQUEST['nlang_sc'])); else $nlang_sc="";
    if (isset($_REQUEST['nlang_name'])) $nlang_name=trim($_REQUEST['nlang_name']); else $nlang_name="";
    if (isset($_REQUEST['nlang_base'])) $nlang_base=trim($_REQUEST['nlang_base']); else $nlang_base="";

    if (isset($_REQUEST['add']) && $_REQUEST['add']) {

        // check for errors
        $continue=true;

        if (!$nlang_sc) {
            message(lang('error_no_language_shortcut'));
            $continue=false;
        }

        if (in_array($nlang_sc,$languages)) {
            message(lang('error_language_shortcut_exists'));
            $continue=false;
        }

        if (!preg_match("/^[a-z]{2}$/",$nlang_sc)) {
            message(lang('error_language_shortcut_must_be_two_latin_letters'));
            $continue=false;
        }

        if (!$nlang_name) {
            message(lang('error_no_language_name'));
            $continue=false;
        }

        if (!in_array($nlang_base,$languages)) {
            message(lang('error_base_language_does_not_exist'));
            $continue=false;
        }

        // add language
        if ($continue) {

            // as transaction?
            $query="ALTER TABLE ".table('lang')." ADD COLUMN ".$nlang_sc." text";
            $done=or_query($query);
            if ($done) message (lang('language_created').' '.$nlang_sc);

            $query="UPDATE ".table('lang')." SET ".$nlang_sc."=".$nlang_base." ";
            $done=or_query($query);
            if ($done) message (lang('language_items_copied_from_base_language').' '.$nlang_base);

            $pars=array(':nlang_sc'=>$nlang_sc);
            $query="UPDATE ".table('lang')." SET ".$nlang_sc."= :nlang_sc
                    WHERE content_type='lang' AND content_name='lang'";
            $done=or_query($query,$pars);

            $pars=array(':nlang_name'=>$nlang_name);
            $query="UPDATE ".table('lang')." SET ".$nlang_sc."= :nlang_name
                    WHERE content_type='lang' AND content_name='lang_name'";
            $done=or_query($query,$pars);
            log__admin("language_add","language:".$_REQUEST['nlang_sc']);
            redirect ("admin/lang_main.php");
        }

    }
}

if ($proceed) {
    show_message();

    echo '<FORM action="lang_lang_add.php">

        <TABLE class="or_formtable">
            <TR>
                <TD align=right>
                    '.lang('language_shortcut').':&nbsp;&nbsp;
                </TD>
                <TD>
                    <INPUT type=text name="nlang_sc" size=2 maxlength=2 value="'.$nlang_sc.'">
                </TD>
            </TR>';

    echo '      <TR>
                <TD align=right>
                    '.lang('language_name_in_lang').':&nbsp;&nbsp;
                </TD>
                <TD>
                    <INPUT type=text name="nlang_name" size=20 maxlength=50 value="'.$nlang_name.'">
                </TD>
            </TR>';

    echo '          <TR>
                                <TD align=right>
                                        '.lang('language_based_on').':&nbsp;&nbsp;
                                </TD>
                                <TD>';
    $lang_names=lang__get_language_names();

    if (!$nlang_base) $nlang_base=$settings['admin_standard_language'];
    echo '<SELECT name="nlang_base">';
    foreach ($languages as $language) {
        echo '<OPTION value="'.$language.'"';
        if ($language==$nlang_base) echo ' SELECTED';
        echo '>'.$lang_names[$language].'</OPTION>
                ';
    }
    echo '</SELECT>
                                </TD>
                        </TR>';


    echo '      <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="add" value="'.lang('add').'">
                </TD>
            </TR>
        </TABLE>
        </FORM>';

    echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>