<?php
ob_start();
include ("nonoutputheader.php");

	if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
                else redirect("admin/experiment_main.php");

	if ($_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
                else $session_id='';

	if ($_REQUEST['focus']) $focus=$_REQUEST['focus']; else $focus="registered";

	if ($_REQUEST['sort']) $sort=$_REQUEST['sort']; else $sort="";

	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);

        pdfoutput__make_part_list($experiment_id,$session_id,$focus,$sort);

	//echo '<pre>'; var_dump($expadmindata); echo '<BR><BR><BR>';
	//var_dump($_SESSION);
	//echo '</pre>';

        //echo experimentmail__send_calendar();

?>
