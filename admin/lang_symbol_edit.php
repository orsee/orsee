<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_symbol";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else $lang_id="";

    if ($lang_id) $allow=check_allow('lang_symbol_edit','lang_main.php');
    else $allow=check_allow('lang_symbol_add','lang_main.php');
}

if ($proceed) {
    $languages=get_languages();

    if (isset($_REQUEST['save']) && $_REQUEST['save']) {

        $continue=true;
        $_REQUEST['content_type']="lang";

        if ($lang_id) {
            $done=orsee_db_save_array($_REQUEST,"lang",$lang_id,"lang_id");
        } else {
            $lang_id=lang__insert_to_lang($_REQUEST);
        }
        message(lang('changes_saved'));

        log__admin("language_symbol_edit","lang_id:lang,".$_REQUEST['content_name']);
        redirect ("admin/lang_symbol_edit.php?lang_id=".$lang_id);
    }
}

if ($proceed) {
    // if lang id given, load data
    if ($lang_id) $content=orsee_db_load_array("lang",$lang_id,"lang_id"); else $content=array('content_name'=>'');
    if ($lang_id && (!isset($content['lang_id']))) redirect ("admin/lang_main.php");
}

if ($proceed) {
    echo '<center>';

    // form
    echo '  <FORM action="lang_symbol_edit.php" method=post>
        <INPUT type=hidden name="lang_id" value="'.$lang_id.'">

        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('edit_symbol');
    if ($lang_id) echo ' '.$content['content_name'];
    echo '
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD>
                    '.lang('symbol_name').':';
    if (check_allow('lang_symbol_add')) echo '<BR><FONT class="small">'.lang('symbol_name_comment').'</FONT>';
    echo '  </TD>
            <TD>';
    if (check_allow('lang_symbol_add')) echo '<INPUT type=text size=50 maxlength=200 name=content_name value="'.$content['content_name'].'">';
    else echo $content['content_name'];
    echo '  </TD>
            </TR>';
    foreach ($languages as $language) {
        if(!isset($content[$language])) $content[$language]='';
        echo '  <TR>
                <TD valign=top>
                    '.$language.':
                </TD>
                <TD>
                    <textarea name="'.$language.'" rows=2 cols=40 wrap=virtual>'.stripslashes($content[$language]).'</textarea>
                </TD>
            </TR>';
    }

    echo '      <TR>
                <TD align=center colspan=2>
                    <INPUT class="button" type="submit" name="save" value="';
    if ($lang_id) echo lang('change'); else echo lang('add');
    echo '">
                </TD>
            </TR>
        </TABLE>
        </FORM>';

    if ($lang_id && check_allow('lang_symbol_delete')) {
        echo '<BR><BR>
            '.button_link('lang_symbol_delete.php?lang_id='.urlencode($lang_id),
                            lang('delete'),'trash-o');
    }
    echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>