<?php
ob_start();

$menu__area="options";
$title="option languages";
include ("header.php");

	// load languages
                $languages=get_languages();
		$lang_names=lang__get_language_names();

                $enabled_part=explode(",",$settings['language_enabled_participants']);
                $enabled_pub=explode(",",$settings['language_enabled_public']);


	if ($_REQUEST['change_def']) {
		$allow=check_allow('lang_avail_edit','lang_main.php');
		$parts=array();
		$pubs=array();

		foreach ($languages as $language) {
			if ($_REQUEST['enabled_public'][$language] || $language==$settings['public_standard_language']) 
				$pubs[]=$language;
			if ($_REQUEST['enabled_participants'][$language] || $language==$settings['public_standard_language'])
				$parts[]=$language;
			}
		$pubs_string=implode(",",$pubs);
		$parts_string=implode(",",$parts);

		$query="UPDATE ".table('options')." SET option_value='".$pubs_string."' 
			WHERE option_name='language_enabled_public'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());

		$query="UPDATE ".table('options')." SET option_value='".$parts_string."'
                        WHERE option_name='language_enabled_participants'";
                $done=mysql_query($query) or die("Database error: " . mysql_error());
		log__admin("language_availability_edit");
		message($lang['changes_saved']);
		redirect("admin/lang_main.php");
		}


	echo '<center>
	<BR><BR>

		<H4>'.$lang['languages'].'</H4>
	<BR>';

	echo '  <BR><BR>
		<TABLE border=0 width=80%>
			<TR>';
		if (check_allow('lang_symbol_add')) echo '
			<TD>
                		<FORM action="lang_symbol_edit.php">
                		<INPUT type=submit name=go value="'.$lang['add_symbol'].'">
                		</FORM>
			</TD>';
		if (check_allow('lang_lang_add')) echo '
			<TD>
                                <FORM action="lang_lang_add.php">
                                <INPUT type=submit name=go value="'.$lang['add_language'].'">
                                </FORM>
                        </TD>';
		if (check_allow('lang_lang_delete')) echo '
			<TD>
                                <FORM action="lang_lang_delete.php">
                                <INPUT type=submit name=go value="'.$lang['delete_language'].'">
                                </FORM>
                        </TD>';
	echo '		</TR>
		</TABLE><BR><BR>
		';


		// show languages

	echo '<FORM action="'.thisdoc().'">';
	echo '<TABLE border=0 width="80%">
		<TR bgcolor="'.$color['list_title_background'].'"><TD colspan=2>'.$lang['installed_languages'].'</TD>
		<TD>'.$lang['available_in_public_area'].'</TD><TD>'.$lang['available_for_participants'].'</TD>
		<TD></TD><TD></TD></TR>';
	foreach ($languages as $language) { 
		echo '<TR>
			<TD>'.$lang_names[$language].' - '.$language.'</TD>
			<TD>';
			if ($language==$settings['admin_standard_language']) echo '[default admin] ';
			if ($language==$settings['public_standard_language']) echo '[default public] ';
		echo '	</TD>
			<TD>
				<INPUT type=checkbox name="enabled_public['.$language.']" value="'.$language.'"';
					if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) 
						echo ' DISABLED';
					if (in_array($language,$enabled_pub)) echo ' CHECKED';
				echo '>
			</TD>
			<TD>
                                <INPUT type=checkbox name="enabled_participants['.$language.']" value="'.$language.'"';
                                        if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) 
						echo ' DISABLED';
                                        if (in_array($language,$enabled_part)) echo ' CHECKED';
                                echo '>
                        </TD>
			<TD>';
				if (check_allow('lang_lang_edit')) 
					echo '<A HREF="lang_lang_edit.php?elang='.$language.'">'.$lang['edit_basic_data'].'</A>';
		echo '	</TD>
			<TD>';
				if (check_allow('lang_symbol_edit'))
					echo '<A HREF="lang_edit.php?el='.$language.'">'.$lang['edit_words_for'].' "'.$language.'"</A>';
		echo '</TD>
			</TD>
			</TR>';
		}

	if (check_allow('lang_avail_edit'))
		echo '	<TR><TD colspan=2></TD><TD align=center colspan=2>
			<INPUT type=submit name="change_def" value="'.$lang['change'].'">
			</TD><TD></TD>
			</TR>';

	echo '</TABLE></FORM>';
	echo '</center>';

include ("footer.php");

?>
