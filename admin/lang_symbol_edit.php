<?php
ob_start();

$menu__area="options";
$title="add language symbol";
include ("header.php");

	echo '<center>
		<BR><BR>

		<H4>'.$lang['edit_language'].': '.$lang['edit_symbol'].'</H4>
		<BR>';


	$languages=get_languages();

	if ($_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else $lang_id="";

	if ($lang_id) $allow=check_allow('lang_symbol_edit','lang_main.php');
		else $allow=check_allow('lang_symbol_add','lang_main.php');

	if ($_REQUEST['save']) {

		$continue=true;

		$_REQUEST['content_type']="lang";

		if ($lang_id) {
			$done=orsee_db_save_array($_REQUEST,"lang",$lang_id,"lang_id");
			}
		   else {
			$lang_id=lang__insert_to_lang($_REQUEST);
			}

		message($lang['changes_saved']);

		log__admin("language_symbol_edit","lang_id:lang,".$_REQUEST['content_name']);
		// redirect
		redirect ("admin/lang_symbol_edit.php?lang_id=".$lang_id);

		}


	// if lang id given, load data
	if ($lang_id) $content=orsee_db_load_array("lang",$lang_id,"lang_id"); else $content="";


	// form
	echo '	<FORM action="lang_symbol_edit.php" method=post>
		<INPUT type=hidden name="lang_id" value="'.$lang_id.'">

		<TABLE with="80%">
			<TR>
				<TD>
					'.$lang['symbol_name'].':';
					if (check_allow('lang_symbol_add'))
						echo '<BR><FONT class="small">'.$lang['symbol_name_comment'].'</FONT>';
			echo '	</TD>
				<TD>';
				if (check_allow('lang_symbol_add'))
					echo '<INPUT type=text size=50 maxlength=200 name=content_name value="'.
							$content['content_name'].'">';
				   else echo $content['content_name'];
			echo '	</TD>
			</TR>';
	foreach ($languages as $language) {
		echo '	<TR>
				<TD valign=top>
					'.$language.':
				</TD>
				<TD>
					<textarea name="'.$language.'" rows=2 cols=40 wrap=virtual>'.stripslashes($content[$language]).'</textarea>
				</TD>
			</TR>';
		}

	echo '		<TR>
				<TD align=center colspan=2>
					<INPUT type=submit name=save value="';
						if ($lang_id) echo $lang['change']; else echo $lang['add'];
						echo '">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

	if ($lang_id && check_allow('lang_symbol_delete')) {
		echo '<BR><BR>
			<FORM action="lang_symbol_delete.php">
			<INPUT type=hidden name="lang_id" value="'.$lang_id.'">
			<INPUT type=submit name=delete value="'.$lang['delete'].'">
			</FORM>';


		}
	echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';

include ("footer.php");

?>
