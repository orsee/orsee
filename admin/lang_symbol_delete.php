<?php
ob_start();

$menu__area="options";
$title="delete symbol";
include("header.php");

         if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id'];
                else redirect ("admin/");

	$allow=check_allow('lang_symbol_delete','lang_symbol_edit.php?lang_id='.$lang_id);

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/lang_symbol_edit.php?lang_id='.$lang_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$symbol=orsee_db_load_array("lang",$lang_id,"lang_id");

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_symbol'].' '.$symbol['content_name'].'</h4>
		</center>';


	if ($reallydelete) { 

        	$query="DELETE FROM ".table('lang')." 
         		WHERE lang_id='".$lang_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

        	message ($lang['symbol_deleted']);
		log__admin("language_symbol_delete","lang_id:lang,".$symbol['content_name']);
		redirect ('admin/lang_edit.php');
		}

	// form

	echo '	<CENTER>
		<FORM action="lang_symbol_delete.php">
		<INPUT type=hidden name="lang_id" value="'.$lang_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['do_you_really_want_to_delete'].'
					<BR><BR>';
					dump_array($symbol); echo '
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



