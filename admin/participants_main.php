<?php
ob_start();

$menu__area="participants_main";
$title="participants";
include ("header.php");

// participants summary

	echo '	<center>
		<BR>
		<table border=1 width=90%>
			<TR>
				<TD bgcolor="'.$color['list_header_background'].'" colspan=2>
					<h3>'.$lang['participants'].'</h3>
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					'.participants__count_participants().' '.
					$lang['xxx_participants_registered'].'.<BR>
				</TD>
			</TR>
			<TR>
				<TD>&nbsp;&nbsp;&nbsp;</TD>
				<TD bgcolor="'.$color['list_list_background'].'">

				<TABLE border=0 width=100%>';

			$query="SELECT *
                		FROM ".table('experiment_types')." as texpt, 
						".table('lang')." as tlang
                		WHERE texpt.exptype_id=tlang.content_name
                		AND tlang.content_type='experiment_type'
                		AND texpt.enabled='y'
                		ORDER BY exptype_id";
			$result=mysql_query($query);
        		while ($line = mysql_fetch_assoc($result)) {
				$wstring="subscriptions LIKE '%".$line['exptype_name']."%'";
				echo '<TR>
					<TD>
						'.$lang['registered_for_xxx_experiments_xxx'].
						' '.$line[$lang['lang']].':
					</TD>
					<TD>
						'.participants__count_participants($wstring).'
					</TD>
					</TR>';
				}
				echo '
				<TR>
					<TD>';
						if (check_allow('participants_unconfirmed_edit')) echo '
							<A HREF="participants_unconfirmed.php">'.
								$lang['registered_but_not_confirmed_xxx'].':</A>';
						   else echo $lang['registered_but_not_confirmed_xxx'];
				echo '	</TD>
					<TD>
						'.participants__count_participants_temp().' 
					</TD>
				</TR>
				<TR>
					<TD>
						'.$lang['from_this_older_than_4_weeks_xxx'].':
					</TD>
					<TD>';
						$now=time();
						$before=$now-(60*60*24*7*4);
						$tstring="creation_time < ".$before;
					echo 	participants__count_participants_temp($tstring).'
					</TD>
				</TR>
				</table>

				</TD>
			</TR>
			<TR>
				<TD bgcolor="'.$color['list_options_background'].'" colspan=2>
					'.$lang['options'].':<BR><BR>
				</TD>
			</TR>';
			if (check_allow('participants_show')) echo '
			<TR>
				<TD>
                                        <A HREF="participants_show.php?new=true&deleted=n">'
                                        .$lang['edit_participants'].'</A><BR>
                                </TD>
				<TD>
					<A HREF="participants_show.php?new=true&deleted=y">'.
					$lang['edit_unsubscribed_participants'].'</A>
				</TD>
			</TR>';
	echo '		<TR>
				<TD>';
                                        if (check_allow('participants_edit')) echo '
                                                <A HREF="participants_edit.php">'.$lang['add_participant'].'</A><BR>';
        echo '                  </TD>
				<TD>
				</TD>
			</TR>
		</TABLE>
		</center>';

include ("footer.php");

?>
