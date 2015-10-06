<?php
// part of orsee. see orsee.org
ob_start();

$title="delete_faq";
$menu__area="options_main";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['faq_id'])) $faq_id=$_REQUEST['faq_id']; else $faq_id="";

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/faq_edit.php?faq_id='.$faq_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('faq_delete','faq_edit.php?faq_id='.$faq_id);
}

if ($proceed) {
    $question=faq__load_question($faq_id);
    $answer=faq__load_answer($faq_id);

    // load languages
    $languages=get_languages();

    if ($reallydelete) {

        $pars=array(':faq_id'=>$faq_id);
        $query="DELETE FROM ".table('lang')."
                WHERE content_type='faq_question'
                AND content_name= :faq_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('lang')."
                WHERE content_type='faq_answer'
                AND content_name= :faq_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('faqs')."
                WHERE faq_id= :faq_id";
        $result=or_query($query,$pars);

        message (lang('faq_deleted'));
        log__admin("faq_delete","faq_id:".$faq_id);
        redirect ('admin/faq_main.php');
    }
}

if ($proceed) {

     // form

    echo '     <center>
               <FORM action="faq_delete.php">
                <INPUT type=hidden name="faq_id" value="'.$faq_id.'">

                <TABLE class="or_formtable">
                    <TR><TD colspan="2">
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                    "'.$question[lang('lang')].'"
                                </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR>
                        <TD colspan="2">
                                        <B>'.lang('do_you_really_want_to_delete').'</B>
                                        <BR><BR>
                    <TABLE>';
    foreach ($languages as $language) {
        echo '  <TR>
                            <TD align=right>
                                '.$language.'
                            </TD>
                            <TD>&nbsp;&nbsp;</TD>
                            <TD>
                                '.stripslashes($question[$language]).'
                            </TD>
                            </TR>
                        <TR>
                            <TD colspan=2></TD>
                            <TD>
                                '.stripslashes($answer[$language]).'
                            </TD>
                        </TR>';
    }
    echo '
                    </TABLE>
                                </TD>
                        </TR>
                        <TR>
                                <TD align=left>
                                '.button_link('faq_delete.php?faq_id='.$faq_id.'&reallydelete=true',
                                lang('yes_delete'),'check-square biconred').'
                                </TD>
                                <TD align=right>
                                '.button_link('faq_delete.php?faq_id='.$faq_id.'&betternot=true',
                                lang('no_sorry'),'undo bicongreen').'
                                </TD>
                        </TR>
                </TABLE>

                </FORM>
                </center>';

}
include ("footer.php");
?>