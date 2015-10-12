<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_language";
include("header.php");
if ($proceed) {
    $allow=check_allow('lang_lang_delete','lang_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['elang']) && $_REQUEST['elang']) $tlang=$_REQUEST['elang'];
    else $tlang="";

    if (isset($_REQUEST['nlang']) && $_REQUEST['nlang']) $slang=$_REQUEST['nlang'];
    else $slang="";

    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    if (isset($_REQUEST['delete']) && $_REQUEST['delete']) $delete=true;
    else $delete=false;

    $languages=get_languages();
    $lang_names=lang__get_language_names();

    if ($delete || $reallydelete) {

        if (!$tlang || !in_array($tlang,$languages)) redirect ("admin/lang_main.php");

        if ($proceed) {
            if (!$slang || !in_array($slang,$languages)) redirect ("admin/lang_main.php");
        }

        if ($proceed) {
            if ($tlang==$slang) {
                message (lang('language_to_be_deleted_cannot_be_language_to_substitute'));
                redirect ('admin/lang_lang_delete.php?elang='.$tlang.'&nlang='.$slang);
            }
        }

        if ($proceed) {
            if ($tlang==lang('lang')) redirect ("admin/lang_main.php");
        }

        if ($proceed) {
            if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) redirect ('admin/lang_main.php');
        }

        if ($proceed && $reallydelete) {
            // update participants and admin
            $tables=array('participants','admin');
            foreach ($tables as $table) {
                $pars=array(':slang'=>$slang,':tlang'=>$tlang);
                $query="UPDATE ".table($table)." SET language= :slang WHERE language= :tlang";
                $done=or_query($query,pars);
            }
            message(lang('updated_language_settings'));

            // delete language column
            $query="ALTER TABLE ".table('lang')."
                    DROP column ".$tlang;
            $done=or_query($query);

            // bye, bye
            message (lang('language_deleted').': '.$tlang);
            log__admin("language_delete","language:".$tlang);
            redirect ('admin/lang_main.php');
        }

        if ($proceed) {
            // confirmation form
            echo '<center>';


            echo '
                <FORM action="lang_lang_delete.php">
                <INPUT type=hidden name="elang" value="'.$tlang.'">
                <INPUT type=hidden name="nlang" value="'.$slang.'">

                <TABLE class="or_formtable">
                    <TR><TD colspan=2>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                '.lang('delete_language').' '.$lang_names[$tlang].' ('.$tlang.')
                            </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR>
                        <TD colspan=2>
                            '.lang('do_you_really_want_to_delete').'
                            <BR><BR>
                        </TD>
                    </TR>
                    <TR>
                        <TD align=left>
                            '.button_link('lang_lang_delete.php?elang='.urlencode($tlang).'&nlang='.urlencode($slang).'&reallydelete=true',
                                            lang('yes_delete'),'check-square biconred').'
                        </TD>
                        <TD align=right>
                            '.button_link('lang_lang_delete.php?elang='.urlencode($tlang).'&nlang='.urlencode($slang).'&betternot=true',
                                            lang('no_sorry'),'undo bicongreen').'
                        </TD>

                    </TR>
                </TABLE>

                </FORM>
                </center>';
        }

    } else {

    // delete form

        echo '  <BR><BR>
                <center>';
        echo '  <FORM action="lang_lang_delete.php">
                <TABLE class="or_formtable">
                    <TR>
                    <TD align=right>'.lang('delete_language').':</TD>
                    <TD>';

        echo '<SELECT name="elang">';
        foreach ($languages as $language) {
            if ($language!=lang('lang')) {
                echo '<OPTION value="'.$language.'"';
                if ($language==$tlang) echo ' SELECTED';
                echo '>'.$lang_names[$language].' ('.$language.')</OPTION>';
            }
        }
        echo '</SELECT>
                    </TD>
                </TR>
                <TR>
                    <TD align=right>'.lang('copy_users_of_this_lang_to').':</TD>
                    <TD>';
        echo '<SELECT name="nlang">';
        foreach ($languages as $language) {
            echo '<OPTION value="'.$language.'"';
            if ($language==$slang) echo ' SELECTED';
            echo '>'.$lang_names[$language].' ('.$language.')</OPTION>';
        }
        echo '</SELECT>
                    </TD>
                </TR>
                <TR>
                    <TD colspan=2 align=center>
                        <INPUT class="button" type="submit" name="delete" value="'.lang('delete').'">
                    </TD>
                </TR>
                </TABLE>
                </FORM>
                </center>';
    }

}
include ("footer.php");
?>