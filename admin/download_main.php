<?php
ob_start();

$menu__area="download";
$title="downloads";

include ("header.php");

	echo '<center>
		<BR><BR>
		<h4>'.$lang['downloads'].'</h4>
		<BR>';
	echo '  <TABLE width=80%>';

	if ($_REQUEST['experiment_id']) {

		$exp=orsee_db_load_array("experiments",$_REQUEST['experiment_id'],"experiment_id");

	        echo '<TR bgcolor="'.$color['list_header_background'].'"><TD colspan=2>';
                echo $exp['experiment_name'].' (';
                if ($exp['experiment_type']=="laboratory") {
                        echo $lang['from'].' '.sessions__get_first_date($exp['experiment_id']).' ';
                        echo $lang['to'].' '.sessions__get_last_date($exp['experiment_id']);
                }
                elseif ($exp['experiment_type']=="online-survey") {
                        echo $lang['from'].' '.survey__print_start_time($exp['experiment_id']).' ';
                        echo $lang['to'].' '.survey__print_stop_time($exp['experiment_id']);
                }
                echo ') by ';
                echo experiment__list_experimenters($exp['experimenter'],true,true);
                echo '</TD></TR>';

                echo '<TR><TD>&nbsp;&nbsp;&nbsp;</TD><TD>';
                downloads__list_files($exp['experiment_id'],true,true,true);
                echo '</TD></TR>
                        <TR><TD colspan=2>&nbsp;</TD></TR>';
	echo '<TR><TD colspan=2 align=center><A HREF="download_main.php">'.icon('back').' '.$lang['back'].'</A></TD></TR>';
		}
	   else {

                echo '  <TR bgcolor="'.$color['list_title_background'].'">
			<TD>
                                '.$lang['general_downloads'].'
                        </TD>
                        <TD align=right>';
				if (check_allow('download_general_upload'))
                                	echo '<A HREF="download_upload.php">'.$lang['upload_general_file'].'<A>';
                echo '  </TD>
                        </TR>
                        <TR><TD colspan=2>
                                <BR>';
                                downloads__list_files('0',true,true,true);
                echo '  </TD></TR>';

		echo '	<TR><TD bgcolor="'.$color['list_title_background'].'" colspan=2>
				'.$lang['downloads_for_experiments'].'
			</TD></TR>
			<TR><TD colspan=2><font size=-1>'.
				$lang['upload_experiment_files_in_exp_sec'].
				'</font></TD></TR>
			<TR><TD colspan=2><BR>';
				downloads__list_experiments(true,true,true);
		echo '		<BR><BR><BR>
			</TD></TR>';

		}
	echo '	</TABLE>';


include ("footer.php");

?>
