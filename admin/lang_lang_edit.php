<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_language";
include ("header.php");
if ($proceed) {
    $allow=check_allow('lang_lang_edit','lang_main.php');
}

if ($proceed) {
    $languages=get_languages();

    if (isset($_REQUEST['elang']) && $_REQUEST['elang'] && in_array(trim($_REQUEST['elang']),$languages)) {
        $tlang=trim($_REQUEST['elang']);
        $tlang_name=load_language_symbol('lang_name',$tlang);
        $tlang_icon=load_language_symbol('lang_icon_base64',$tlang);
    } else redirect ("admin/lang_main.php");
}

if ($proceed) {

    if (isset($_REQUEST['add']) && $_REQUEST['add']) {

        // check for errors
        $continue=true;

        if (!$_REQUEST['lang_name']) {
            message(lang('error_no_language_name'));
            $continue=false;
        }

        // add language
        if ($continue) {
            $pars=array(':lang_name'=>$_REQUEST['lang_name']);
            $query="UPDATE ".table('lang')." SET ".$tlang."= :lang_name
                    WHERE content_type='lang' AND content_name='lang_name'";
            $done=or_query($query,$pars);

            $pars=array(':lang_icon_base64'=>$_REQUEST['lang_icon_base64']);
            $query="UPDATE ".table('lang')." SET ".$tlang."= :lang_icon_base64
                    WHERE content_type='lang' AND content_name='lang_icon_base64'";
            $done=or_query($query,$pars);

            message (lang('changes_saved'));
            log__admin("language_edit","language:".$tlang);
            redirect ("admin/lang_lang_edit.php?elang=".$tlang);
        }
        $tlang_name=$_REQUEST['lang_name'];
    }
}

if ($proceed) {
    show_message();

    echo '<center>';
    echo '<FORM action="lang_lang_edit.php">
        <INPUT type=hidden name="elang" value="'.$tlang.'">

        <TABLE class="or_formtable">
            <TR><TD colspan="3">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('edit_language').' '.$tlang_name.' ('.$tlang.')
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD align="right" valign="top">
                    '.lang('language_name_in_lang').':&nbsp;&nbsp;
                </TD>
                <TD>
                    <INPUT type=text name="lang_name" size=20 maxlength=50 value="'.$tlang_name.'">
                </TD>
            </TR>';

    echo    '<TR>
                <TD align="right" valign="top">
                    '.lang('language_icon_in_base64').':&nbsp;&nbsp;
                </TD>
                <TD>
                    <TEXTAREA type=text name="lang_icon_base64" cols=40 rows=10 wrap=virtual>'.$tlang_icon.'</TEXTAREA>
                </TD>
            </TR>';

    echo '      <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="add" value="'.lang('change').'">
                </TD>
            </TR>
        </TABLE>
        </FORM>';

    echo '<TABLE width="80%" border=0>
        <TR>
            <TD width="50%" align=center>';
                if (check_allow('lang_lang_export')) echo
                button_link('lang_lang_export.php?lang_id='.urlencode($tlang),
                                        lang('export_language'),'cloud-upload');

    echo '      </TD>
            <TD width=50% align=center>';
                if (check_allow('lang_lang_import')) echo
                button_link('lang_lang_import.php?lang_id='.urlencode($tlang),
                                        lang('import_language'),'cloud-download');
    echo '      </TD>
        </TR>
        </TABLE>';

    echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>