<?php
ob_start();

if (isset($_REQUEST['faq_id'])) $id=$_REQUEST['faq_id']; else $faq_id="";

$title="edit faq";
include ("header.php");

	if ($faq_id) $allow=check_allow('faq_edit','faq_main.php');
		else $allow=check_allow('faq_add','faq_main.php');

        echo '<center><BR>
                        <h4>';
			if ($faq_id) echo $lang['edit_faq']; else echo $lang['add_faq'];
		echo '	</h4><BR><BR>';

	// load faq question and answer from lang table
	if ($faq_id) {
		$question=faq__load_question($faq_id);
		$answer=faq__load_answer($faq_id);
		}	

        // load languages
        $languages=get_languages();

	$continue=true;

        if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

		$rquestion=$_REQUEST['question'];
		$ranswer=$_REQUEST['answer'];
 
                foreach ($languages as $language) {
                        if (!$rquestion[$language]) {
                                message ($lang['missing_question_in_language'].": ".$language);
                                $continue=false;
                                }
			if (!$ranswer[$language]) {
                                message ($lang['missing_answer_in_language'].": ".$language);
                                $continue=false;
                                }
                	}

                foreach ($languages as $language) {
                        $question[$language]=$rquestion[$language];
                        $answer[$language]=$ranswer[$language];
                        }

                if ($continue) {
                        if (!$faq_id) {
				$new_faq_id=time();
			
				$faq['faq_id']=$new_faq_id;
				$faq['evaluation']=0;
				$done=orsee_db_save_array($faq,"faqs",$faq['faq_id'],"faq_id");

				$question['content_name']=$new_faq_id;
				$question['content_type']="faq_question";
				$done=lang__insert_to_lang($question);


				$answer['content_name']=$new_faq_id;
                                $answer['content_type']="faq_answer";
				$done=lang__insert_to_lang($answer);

				log__admin("faq_create","faq_id:".$new_faq_id);
                                }
			   else {
				$done=orsee_db_save_array($question,"lang",$question['lang_id'],"lang_id");
				$done=orsee_db_save_array($answer,"lang",$answer['lang_id'],"lang_id");
				log__admin("faq_edit","faq_id:".$faq_id);
				}

                        message ($lang['changes_saved']);
                        redirect ('admin/faq_edit.php?faq_id='.$question['content_name']);
                        }
                }


	show_message();

        // form
        echo '  <FORM action="faq_edit.php" METHOD=POST>
                <INPUT type=hidden name="faq_id" value="'.$faq_id.'">

                <TABLE>
                        <TR>
                                <TD>
					'.$lang['id'].'
				</TD>
				<TD>
					'.$faq_id.'
				</TD>
			</TR>';
	$shade=true;
	foreach ($languages as $language) {
		echo '	<tr';
                        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
				<TD>
					'.$language.':
				</TD>
				<TD>
				</TD>
			</TR>
			<tr';
                        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
				else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
				<TD>
					'.$lang['question_in_xxxlang'].' '.$language.'
				</TD>
				<TD>
					<textarea name="question['.$language.']" cols=40 rows=3 wrap=virtual>'.
						stripslashes($question[$language]).'</textarea>
				</TD>
			</TR>
			<tr';
                        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
				else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
				<TD>
					'.$lang['answer_in_xxxlang'].' '.$language.'
				</TD>
				<TD>
					<textarea name="answer['.$language.']" cols=40 rows=20 wrap=virtual>'.
						stripslashes($answer[$language]).'</textarea>
				</TD>
			</TR>';
		if ($shade) $shade=false; else $shade=true;
		}

        echo '  </TABLE>
                <TABLE> 
                        <TR>
                                <TD COLSPAN=2 align=center>
                                        <INPUT name=edit type=submit value="';
					if ($faq_id) echo $lang['change']; else echo $lang['add'];
					echo '">
                                </TD>
                        </TR>
                </table>
                </FORM>
                <BR>';

        if ($id && check_allow('faq_delete')) {
                echo '<BR><BR><FORM action="faq_delete.php">
                        <INPUT type=hidden name="faq_id" value="'.$faq_id.'">
                        <INPUT type=submit name="submit" value="'.$lang['delete'].'">
                        </FORM>';
                }
        echo '<BR><BR>
                <A href="faq_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';

include ("footer.php");

?>
