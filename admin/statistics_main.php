<?php
ob_start();

$title="statistics";
$menu__area="statistics";
include ("header.php");

	echo '	<BR><BR>
		<center><h3>'.$lang['statistics'].'</h3>

		<TABLE width=80% border=0>
                <TR bgcolor="'.$color['list_title_background'].'">
                        <TD colspan=2>
                                '.$lang['statistics'].'
                        </TD>
                </TR>';

	if (check_allow('statistics_system_show')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="statistics_system.php">'.$lang['system_statistics'].'</A>
                        </TD>
                </TR>';

	if (check_allow('statistics_participants_show')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="statistics_participants.php">'.$lang['participant_statistics'].'</A>
                        </TD>
                </TR>';

	if (check_allow('statistics_server_usage_show')) echo '
                <TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="../usage">'.$lang['server_usage_statistics'].'</A>
                        </TD>
                </TR>';

        echo '  <TR>
                        <TD colspan=2>&nbsp;&nbsp;</TD>
                </TR>

		<TR bgcolor="'.$color['list_title_background'].'">
			<TD colspan=2>
				'.$lang['log_files'].'
			</TD>
		</TR>';

	if (check_allow('log_file_participant_actions_show')) echo '
		<TR>
			<TD>&nbsp;&nbsp;</TD>
			<TD>
				<A HREF="statistics_show_log.php?log=participant_actions">'.$lang['participant_actions'].'</A>
			</TD>
		</TR>';

	if (check_allow('log_file_experimenter_actions_show')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="statistics_show_log.php?log=experimenter_actions">'.$lang['experimenter_actions'].'</A>
                        </TD>
                </TR>';

	if (check_allow('log_file_regular_tasks_show')) echo '
		<TR>
                        <TD>&nbsp;&nbsp;</TD>
                        <TD>
                                <A HREF="statistics_show_log.php?log=regular_tasks">'.$lang['regular_tasks'].'</A>
                        </TD>
                </TR>';

	echo '	<TR>
                        <TD colspan=2>&nbsp;&nbsp;</TD>
                </TR>

		</TABLE>';

	echo '</center>';


include ("footer.php");

?>
