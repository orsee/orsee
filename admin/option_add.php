<?php
ob_start();

$title="add option";
include ("header.php");

	$allow=check_allow('settings_option_add','options_main.php');

	echo '<center>
		<BR><BR>

		<H4>add option</H4>
		<BR>';


	$languages=get_languages();

	if ($_REQUEST['save']) {

		$continue=true;

		// save package
		$option_id=time();
		orsee_db_save_array($_REQUEST,"options",$option_id,"option_id");

		// redirect
		message("option ".$_REQUEST['option_name']." added");
		log__admin("option_add","option:".$_REQUEST['option_name']);
		redirect ("admin/options_main.php");
		}


	// form
	echo '	<FORM action="option_add.php" method=post>
		<TABLE with="80%">
			<TR>
				<TD>
					option name:<BR>
					<FONT class="small">'.$lang['symbol_name_comment'].'</FONT>
				</TD>
				<TD>
					<INPUT type=text size=50 maxlength=200 name="option_name" value="">
				</TD>
			</TR>';
		echo ' 	<TR>
				<TD>
					option type:
				</TD>
				<TD>
					<SELECT name="option_type">
					<OPTION value="general">general</OPTION>
					<OPTION value="default">default</OPTION>
					<OPTION value="color">color</OPTION>
					<OPTION value="cron">cron</OPTION>
					</SELECT>
				</TD>
			</TR>';
		echo '	<TR>
				<TD valign=top>
					value:
				</TD>
				<TD>
					<INPUT type=text size=50 maxlength=200 name="option_value" value="">
				</TD>
			</TR>';

	echo '		<TR>
				<TD align=center colspan=2>
					<INPUT type=submit name=save value="';
						echo $lang['add'];
						echo '">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

	echo '	</center>';

include ("footer.php");

?>
