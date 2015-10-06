<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_symbol";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else $lang_id="";
    if (!$lang_id) redirect ("admin/lang_main.php");
}

if ($proceed) {
    $allow=check_allow('lang_symbol_delete','lang_symbol_edit.php?lang_id='.$lang_id);
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) redirect ('admin/lang_symbol_edit.php?lang_id='.$lang_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $symbol=orsee_db_load_array("lang",$lang_id,"lang_id");
    if (!isset($symbol['lang_id'])) redirect ("admin/lang_main.php");
}

if ($proceed) {

    if ($reallydelete) {
        $pars=array(':lang_id'=>$lang_id);
        $query="DELETE FROM ".table('lang')."
                WHERE lang_id= :lang_id";
        $result=or_query($query,$pars);

        message (lang('symbol_deleted'));
        log__admin("language_symbol_delete","lang_id:lang,".$symbol['content_name']);
        redirect ('admin/lang_edit.php');
    }
}

if ($proceed) {
    // form
    echo '<center>';
    echo '
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('delete_symbol').' '.$symbol['content_name'].'
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('do_you_really_want_to_delete').'
                    <BR><BR>';
                    dump_array($symbol); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                        '.button_link('lang_symbol_delete.php?lang_id='.urlencode($lang_id).'&reallydelete=true',
                                        lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                        '.button_link('lang_symbol_delete.php?lang_id='.urlencode($lang_id).'&betternot=true',
                                        lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>