<?php
ob_start();

$menu__area="options";
$title="delete language";
include("header.php");

	$allow=check_allow('lang_lang_delete','lang_main.php');

	if (isset($_REQUEST['elang']) && $_REQUEST['elang']) $tlang=$_REQUEST['elang'];
                        else $tlang="";

        if (isset($_REQUEST['nlang']) && $_REQUEST['nlang']) $slang=$_REQUEST['nlang'];
                        else $slang="";

	if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	if (isset($_REQUEST['delete']) && $_REQUEST['delete']) $delete=true;
                        else $delete=false;

	if ($delete || $reallydelete) {

        	if (!$tlang) redirect ("admin/lang_main.php");

		if (!$slang) redirect ("admin/lang_main.php");

		if ($tlang==$slang) {
			message ($lang['language_to_be_deleted_cannot_be_language_to_substitute']);
			redirect ('admin/lang_lang_delete.php?elang='.$tlang.'&nlang='.$slang);
			}

		if ($tlang==$lang['lang']) redirect ("admin/lang_main.php");

		if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
			redirect ('admin/lang_main.php');


		$tlang_name=load_language_symbol('lang_name',$tlang);

		echo '	<BR><BR>
			<center>
				<h4>'.$lang['delete_language'].' '.$tlang_name.' ('.$tlang.')</h4>
			</center>';


		if ($reallydelete) { 
			// update participants, participants_temp, participants_os and admin
			$tables=array('participants','participants_temp','participants_os','admin');
			foreach ($tables as $table) {
				$query="UPDATE ".table($table)." SET language='".$slang."' WHERE language='".$tlang."'";
				$done=mysql_query($query) or die("Database error: " . mysql_error());
				}
			message ($lang['updated_language_settings']);

			// delete language column
        		$query="ALTER TABLE ".table('lang')." 
         			DROP column ".$tlang;
			$done=mysql_query($query) or die("Database error: " . mysql_error());


			// bye, bye
        		message ($lang['language_deleted'].': '.$tlang);
			log__admin("language_delete","language:".$tlang);
			redirect ('admin/lang_main.php');
		}


		// confirmation form

		echo '	<CENTER>
			<FORM action="lang_lang_delete.php">
			<INPUT type=hidden name="elang" value="'.$tlang.'">
			<INPUT type=hidden name="nlang" value="'.$slang.'">

			<TABLE>
				<TR>
					<TD colspan=2>
						'.$lang['do_you_really_want_to_delete'].'
						<BR><BR>
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

		} else {

		// delete form

                echo '  <BR><BR>
                        <center>
                                <h4>'.$lang['delete_language'].'</h4>
                        </center>';

                echo '  <CENTER>
                        <FORM action="lang_lang_delete.php">

                        <TABLE>
                                <TR>
                                        <TD align=right>
                                                '.$lang['delete_language'].':
                                        </TD>
					<TD>';
						$languages=get_languages();
						$lang_names=lang__get_language_names();
                                        	echo '<SELECT name="elang">';
                                        	foreach ($languages as $language) {
							if ($language!=$lang['lang']) {
                                                		echo '<OPTION value="'.$language.'"';
								if ($language==$tlang) echo ' SELECTED';
								echo '>'.$lang_names[$language].' ('.$language.')</OPTION>';
								}
                                                	}
                                        	echo '</SELECT>

					</TD>
                                </TR>
				<TR>
                                        <TD align=right>
                                                '.$lang['copy_users_of_this_lang_to'].':
                                        </TD>
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
                                                <INPUT type=submit name=delete value="'.$lang['delete'].'">
                                        </TD>
                                </TR>
                        </TABLE>

                        </FORM>
                        </center>';


		}

include ("footer.php");

?>



