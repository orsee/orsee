<?php
ob_start();

$menu__area="options";
$title="edit language";
include ("header.php");

	$allow=check_allow('lang_lang_edit','lang_main.php');

	if ($_REQUEST['elang']) {
		$tlang=$_REQUEST['elang'];
		$tlang_name=load_language_symbol('lang_name',$tlang);
		} else redirect ("admin/lang_main.php");

	echo '<center>
		<BR><BR>

			<H4>'.$lang['edit_language'].' '.$tlang.' ('.$tlang_name.')</H4>
		<BR>';


	

	if ($_REQUEST['add']) { 
		
		// check for errors
		$continue=true;

		if (!$_REQUEST['lang_name']) {
                        message($lang['error_no_language_name']);
                        $continue=false;
                        }

		// add language
		if ($continue) {

			$query="UPDATE ".table('lang')." SET ".$tlang."='".$_REQUEST['lang_name']."' 
                                WHERE content_type='lang' AND content_name='lang_name'";
			$done=mysql_query($query);

			message ($lang['changes_saved']);
			log__admin("language_edit","language:".$tlang);
			redirect ("admin/lang_lang_edit.php?elang=".$tlang);
			}
		$tlang_name=$_REQUEST['lang_name'];
		}

	show_message();

	echo '<FORM action="lang_lang_edit.php">
		<INPUT type=hidden name="elang" value="'.$tlang.'">

		<TABLE width=80%>
			<TR>
				<TD align=right>
					'.$lang['language_name_in_lang'].':&nbsp;&nbsp;
				</TD>
				<TD>
					<INPUT type=text name="lang_name" size=20 maxlength=50 value="'.$tlang_name.'">
				</TD>
			</TR>';

	echo '		<TR>
				<TD colspan=2 align=center>
					<INPUT type=submit name="add" value="'.$lang['change'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

	echo '<TABLE width="80%" border=0>
		<TR>
			<TD width="50%" align=center>';
				if (check_allow('lang_lang_export')) echo '
				<FORM action="lang_lang_export.php">
				<INPUT type=hidden name="lang_id" value="'.$tlang.'">
				<INPUT type=submit name="go" value="'.$lang['export_language'].'">
				</FORM>';
	echo '		</TD>
			<TD width=50% align=center>';
				if (check_allow('lang_lang_import')) echo '
                                <FORM action="lang_lang_import.php">
                                <INPUT type=hidden name="lang_id" value="'.$tlang.'">
                                <INPUT type=submit name="go" value="'.$lang['import_language'].'">
                                </FORM>';

	echo '		</TD>
		</TR>
		</TABLE>';

	echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';


include ("footer.php");

?>
