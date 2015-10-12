<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="files";
$title="files";
include ("header.php");
if ($proceed) {

    echo '<center>';

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
        if (!check_allow('experiment_restriction_override'))
            check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
        if ($proceed) {
            $exp=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
            if (!isset($exp['experiment_id'])) redirect('admin/download_main.php');
        }
        if ($proceed) {
            $experimenters=db_string_to_id_array($exp['experimenter']);
            if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_view_experiment_my'))
                    || check_allow('file_view_experiment_all')) ) {
                    redirect('admin/download_main.php');
            }
        }

        if ($proceed) {
            $thislist_sessions=sessions__get_sessions($experiment_id);
            $first_last=sessions__get_first_last_date($thislist_sessions);
            echo ' <TABLE class="or_panel">';
            echo '<TR><TD>
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
            echo lang('experiment').' '.$exp['experiment_name'].', ';
            echo lang('from').' '.$first_last['first'].' ';
            echo lang('to').' '.$first_last['last'];
            echo ', '.experiment__list_experimenters($exp['experimenter'],true,true);
            echo '</TD>';
            echo '</TR></TABLE>
                </TD></TR>';
            echo '<TR><TD>';
            echo downloads__list_files_experiment($exp['experiment_id'],true,true,true);
            echo '</TD></TR>';
            echo '  </TABLE>';
            echo '<BR><BR><a href="experiment_show.php?experiment_id='.$exp['experiment_id'].'">'.icon('back').' '.
            lang('mainpage_of_this_experiment').'</A>';
            echo '<BR><BR><A href="download_main.php">'.icon('back').' '.lang('back').'</A>';
        }
    } else {
        if (check_allow('file_download_general')) {
            echo ' <TABLE class="or_panel">';
            echo '<TR><TD>
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
            echo lang('general_downloads');
            echo '</TD>';
            if (check_allow('file_upload_general')) {
                echo '<TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
                echo button_link('download_upload.php',lang('upload_general_file'),'upload');
                echo '</TD>';
            }
            echo '</TR></TABLE>
                </TD></TR>';
            echo '<TR><TD>';
            echo downloads__list_files_general(true,true,true);
            echo '  </TD></TR>';
            echo '  </TABLE>';
        }
        if (check_allow('file_view_experiment_all') || check_allow('file_view_experiment_my')) {
            $list=downloads__list_experiments(true,true,true);
            if ($list) {
                echo '<BR><BR><TABLE class="or_panel">';
                echo '<TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
                echo lang('downloads_for_experiments');
                echo '</TD>';
                echo '</TR></TABLE>
                </TD></TR>';
                echo '<TR><TD>'.lang('upload_experiment_files_in_exp_sec').
                        '</font></TD></TR>
                    <TR><TD>';
                echo $list;
                echo '</TD></TR>';
                echo '  </TABLE>';
            }
        }

    }
    echo '  </center>';
}
include ("footer.php");

?>