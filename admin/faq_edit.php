<?php
// part of orsee. see orsee.org
ob_start();

if (isset($_REQUEST['faq_id'])) $faq_id=$_REQUEST['faq_id']; else $faq_id="";

$title="edit_faq";
$menu__area="options_main";
include ("header.php");
if ($proceed) {
    if ($faq_id) $allow=check_allow('faq_edit','faq_main.php');
    else $allow=check_allow('faq_add','faq_main.php');
}

if ($proceed) {
    // load faq question and answer from lang table
    if ($faq_id) {
        $faq=orsee_db_load_array("faqs",$faq_id,"faq_id");
        $question=faq__load_question($faq_id);
        $answer=faq__load_answer($faq_id);
    } else {
        $faq=array('evaluation'=>0);
        $question=array();
        $answer=array();
    }

    // load languages
    $languages=get_languages();

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        $rquestion=$_REQUEST['question'];
        $ranswer=$_REQUEST['answer'];

        foreach ($languages as $language) {
            if (!$rquestion[$language]) {
                message (lang('missing_question_in_language').": ".$language);
                $continue=false;
            }
            if (!$ranswer[$language]) {
                message (lang('missing_answer_in_language').": ".$language);
                $continue=false;
            }
        }

        foreach ($languages as $language) {
            $question[$language]=$rquestion[$language];
            $answer[$language]=$ranswer[$language];
        }

        if ($continue) {
            if (isset($_REQUEST['evaluation']) && $_REQUEST['evaluation']) {
                $faq['evaluation']=$_REQUEST['evaluation'];
            } else {
                $faq['evaluation']=0;
            }
            if (!$faq_id) {
                $new_faq_id=time();
                $faq['faq_id']=$new_faq_id;
                
                $done=orsee_db_save_array($faq,"faqs",$faq['faq_id'],"faq_id");
                $question['content_name']=$new_faq_id;
                $question['content_type']="faq_question";
                $done=lang__insert_to_lang($question);

                $answer['content_name']=$new_faq_id;
                $answer['content_type']="faq_answer";
                $done=lang__insert_to_lang($answer);

                log__admin("faq_create","faq_id:".$new_faq_id);
            } else {
                $faq['faq_id']=$faq_id;
                $done=orsee_db_save_array($faq,"faqs",$faq['faq_id'],"faq_id");
                $done=orsee_db_save_array($question,"lang",$question['lang_id'],"lang_id");
                $done=orsee_db_save_array($answer,"lang",$answer['lang_id'],"lang_id");
                log__admin("faq_edit","faq_id:".$faq_id);
            }

            message (lang('changes_saved'));
            redirect ('admin/faq_edit.php?faq_id='.$question['content_name']);
        }
    }
}

if ($proceed) {

    show_message();
    // form
    echo '<center>';
    echo '  <FORM action="faq_edit.php" METHOD=POST>
                <INPUT type=hidden name="faq_id" value="'.$faq_id.'">

                <TABLE class="or_formtable">
                    <TR><TD colspan="3">
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">';
    if ($faq_id) echo lang('edit_faq'); else echo lang('add_faq');
    echo '                      </TD>
                        </TR></TABLE>
            </TD></TR>
                    <TR>
                        <TD>'.lang('id').'</TD>
                        <TD>'.$faq_id.'</TD>
                    </TR>
                    <TR>
                    <TD>'.lang('this_faq_answered_questions_of_xxx').'</TD>
                    <TD><INPUT name="evaluation" type="text" size=5 maxlength=5 value="'.$faq['evaluation'].'"> '.lang('persons').'</TD>
                    </TR>
                    ';
    $shade=true;
    foreach ($languages as $language) {
        if (!isset($question[$language])) $question[$language]="";
        if (!isset($answer[$language])) $answer[$language]="";
        echo '  <tr>
                <TD colspan="2">
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$language.':
                        </TD>
                        </TR></TABLE>
                </TD>
            </TR>
            <tr>
                <TD>
                    '.lang('question_in_xxxlang').' '.$language.'
                </TD>
                <TD>
                    <textarea name="question['.$language.']" cols=40 rows=3 wrap=virtual>'.
                        stripslashes($question[$language]).'</textarea>
                </TD>
            </TR>
            <tr>
                <TD>
                    '.lang('answer_in_xxxlang').' '.$language.'
                </TD>
                <TD>
                    <textarea name="answer['.$language.']" cols=40 rows=20 wrap=virtual>'.
                        stripslashes($answer[$language]).'</textarea>
                </TD>
            </TR>';
    }

    echo '  <TR>
                                <TD COLSPAN=2 align=center>
                                    <INPUT class="button" name=edit type=submit value="';
    if ($faq_id) echo lang('change'); else echo lang('add');
    echo '">
                                </TD>
                        </TR>
                </table>
                </FORM>
                <BR>';

    if ($faq_id && check_allow('faq_delete')) {
        echo '<BR><BR>
              '.button_link('faq_delete.php?faq_id='.urlencode($faq_id),
                            lang('delete'),'trash-o');
    }
    echo '<BR><BR>
                <A href="faq_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>