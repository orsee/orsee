<?php
ob_start();

$title="delete faq";
include ("header.php");

	if (isset($_REQUEST['faq_id'])) $id=$_REQUEST['faq_id']; else $faq_id="";

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/faq_edit.php?faq_id='.$faq_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('faq_delete','faq_edit.php?faq_id='.$faq_id);

        $question=faq__load_question($faq_id);
        $answer=faq__load_answer($faq_id);

        // load languages
        $languages=get_languages();

        echo '<center><BR>
                        <h4>'.$lang['delete_faq'].' "'.$question[$lang['lang']].'"</h4>';


        if ($reallydelete) {

                $query="DELETE FROM ".table('lang')."
                        WHERE content_type='faq_question'
			AND content_name='".$faq_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('lang')."
                        WHERE content_type='faq_answer'
                        AND content_name='".$faq_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('faqs')."
                        WHERE faq_id='".$faq_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                message ($lang['faq_deleted']);
		log__admin("faq_delete","space_id:".$faq_id);
                redirect ('admin/faq_main.php');
                }

        // form

        echo '  <CENTER>
                <FORM action="faq_delete.php">
                <INPUT type=hidden name="faq_id" value="'.$faq_id.'">

                <TABLE>
                        <TR>
                                <TD colspan=2>
                                        '.$lang['do_you_really_want_to_delete'].'
                                        <BR><BR>
					<TABLE>';
				foreach ($languages as $language) {
					echo '	<TR>
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
                                        <INPUT type=submit name=reallydelete value="'.$lang['yes_delete'].'">
                                </TD>
                                <TD align=right>
                                        <INPUT type=submit name=betternot value="'.$lang['no_sorry'].'">
                                </TD>
                        </TR>
                </TABLE>

                </FORM>
                </center>';

include ("footer.php");

?>
