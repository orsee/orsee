<?php
ob_start();

$title="options";
$menu__area="options_main";
include ("header.php");

	echo '	<BR><BR>
		<center><h3>'.$lang['options'].'</h3>

		<TABLE width=80% border=0>
                <TR bgcolor="'.$color['list_title_background'].'">
                        <TD colspan=2>
                                '.$lang['settings'].'
                        </TD>
                </TR>';

	if (check_allow('settings_edit')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="option_edit.php?otype=general">'.$lang['general_settings'].'</A>
                        </TD>
                </TR>
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="option_edit.php?otype=default">'.$lang['default_values'].'</A>
                        </TD>
                </TR>';

	if (check_allow('settings_option_add')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A href="option_add.php">Add option</A>
                        </TD>
                </TR>
                ';

	if (check_allow('regular_tasks_show')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="cronjob_main.php">'.$lang['regular_tasks'].'</A>
                        </TD>
                </TR>';

	if (check_allow('admin_edit')) {
        echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="admin_show.php">'.$lang['edit_administrators'].'</A>
                        </TD>
                </TR>
		';
		}


        echo '        <TR>
                        <TD colspan=2>&nbsp;&nbsp;</TD>
                </TR>

		<TR bgcolor="'.$color['list_title_background'].'">
			<TD colspan=2>
				'.$lang['my_profile'].'
			</TD>
		</TR>
		<TR>
			<TD>&nbsp;&nbsp;</TD>
			<TD>
				<A HREF="admin_edit.php">'.$lang['preferences'].'</A>
			</TD>
		</TR>
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
				<A HREF="admin_pw.php">'.$lang['change_my_password'].'</A>
                        </TD>
                </TR>
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="admin_rights.php">'.$lang['show_my_rights'].'</A>
                        </TD>
                </TR>
		<TR>
                        <TD colspan=2>&nbsp;&nbsp;</TD>
                </TR>

                <TR bgcolor="'.$color['list_title_background'].'">
                        <TD colspan=2>
                               Content and Items
                        </TD>
                </TR>
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
				<A HREF="lang_main.php">'.$lang['languages'].'</A>
                        </TD>
                </TR>';

	if (check_allow('mail_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=mail">'.$lang['default_mails'].'</A>
                        </TD>
                </TR>';

	if (check_allow('default_text_edit')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=default_text">'.$lang['default_texts'].'</A>
                        </TD>
                </TR>';

	if (check_allow('help_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=help">'.$lang['help'].'</A>
                        </TD>
                </TR>';

	echo '	<TR><TD colspan=2>&nbsp;</TD></TR>';

	if (check_allow('admin_type_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="admin_type_show.php">'.$lang['admin_types'].'</A>
                        </TD>
                </TR>';

	if (check_allow('laboratory_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=laboratory">'.$lang['laboratories'].'</A>
                        </TD>
                </TR>';

	if (check_allow('subjectpool_edit')) echo '
       		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="subpool_main.php">'.$lang['sub_subjectpools'].'</A>
                        </TD>
                </TR>';

	if (check_allow('experimenttype_edit')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="experiment_type_main.php">'.$lang['experiment_types'].'</A>
                        </TD>
                </TR>';

	if (check_allow('experimentclass_edit')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=experimentclass">'.$lang['experiment_classes'].'</A>
                        </TD>
                </TR>';

	if (check_allow('public_content_edit')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="lang_item_main.php?item=public_content">'.$lang['public_content'].'</A>
                        </TD>
                </TR>';

	echo '	<TR><TD colspan=2>&nbsp;</TD></TR>';

	if (check_allow('field_of_studies_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
				<A HREF="lang_item_main.php?item=field_of_studies">'.$lang['studies'].'</A>
                        </TD>
                </TR>';

	if (check_allow('profession_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
				<A HREF="lang_item_main.php?item=profession">'.$lang['professions'].'</A>
                        </TD>
                </TR>';

	if (check_allow('faq_edit')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
				<A HREF="faq_main.php">'.$lang['faqs'].'</A>
                        </TD>
                </TR>';
	echo '	<TR>
                        <TD colspan=2>&nbsp;&nbsp;</TD>
                </TR>';

	if (check_allow('import_data')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="import_data.php">'.$lang['import_data_from_old_versions'].'</A>
                        </TD>
                </TR>';

	echo '	</TABLE>';

//		<A HREF="database_main.php">'.$lang['database'].'</A><BR><BR>


	echo '</center>';


include ("footer.php");

?>
