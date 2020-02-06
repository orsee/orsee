<?php
// part of orsee. see orsee.org
ob_start();

$jquery=array('popup');
$title="mailqueue";
$menu__area="statistics";
include ("header.php");
if ($proceed) {
    if (!$_REQUEST['experiment_id']) redirect ("admin/");
        else $experiment_id=$_REQUEST['experiment_id'];
}

if ($proceed) {
    $allow=check_allow('mailqueue_show_experiment','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    echo '<center>';

        echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.$experiment['experiment_name'].'
            </TD>';
    echo '</TR></TABLE>';

    mailqueue__show_mailqueue($experiment_id);


    echo '<BR><BR><A href="experiment_show.php?experiment_id='.$experiment_id.'">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>
