<?php
// part of orsee. see orsee.org
ob_start();
include ("nonoutputheader.php");
if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/experiment_main.php");
    }
}

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
        $session_id=$_REQUEST['session_id'];
    } else {
        $session_id='';
    }

    if (isset($_REQUEST['pstatus'])) {
        $pstatus=$_REQUEST['pstatus']; 
    } else {
        $pstatus='';
    }

    if (isset($_REQUEST['focus']) && $_REQUEST['focus']) {
        $focus=$_REQUEST['focus'];
    } else {
        $focus='';
    }

    if (isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']) {
        $sort=$_REQUEST['search_sort'];
    } else {
        $sort='';
    }

    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    csvoutput__make_part_list($experiment_id,$session_id,$pstatus,$focus,$sort);
}
?>