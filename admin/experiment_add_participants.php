<?php
ob_start();

$menu__area="experiments";
$title="assign participants";
$query_modules=array("field","noshowups","nr_participations","subjectpool","gender",
			"study_start","field_of_studies","profession","experiment_classes",
			"experiment_participated_or","experiment_participated_and",
			"experiment_assigned_or","rand_subset");

include ("header.php");

	if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
                else redirect("admin/experiment_main.php");

	$allow=check_allow('experiment_assign_participants','experiment_show.php?experiment_id='.$experiment_id);

	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);

	echo '	<center>
		<BR><BR>
			<h4>'.$experiment['experiment_name'].'</h4>
			<h4>'.$lang['assign_subjects'].'</h4>
		';

	if ($_REQUEST['addselected'] || $_REQUEST['addall']) {

		// data base queries for assign ...

		$assign_ids=$_SESSION['assign_ids'];

		if ($_REQUEST['addall']) {

		$assigned_count=count($assign_ids);
		$instring=implode("','",$assign_ids);

			}
		elseif ($_REQUEST['addselected']) {
			$selected_ids=array();
			$i=0;
			foreach ($assign_ids as $id) {
				$i++;
				if ($_REQUEST['p'.$i]==$id) $selected_ids[]=$id;
				}
                	$assigned_count=count($selected_ids);
                	$instring=implode("','",$selected_ids);
			}

		$query="INSERT INTO ".table('participate_at')." (participant_id,experiment_id) 
                        SELECT participant_id, '".$experiment_id."' 
                        FROM ".table('participants')." 
			WHERE participant_id IN ('".$instring."') ";
		$done=mysql_query($query) or die("Database error: " . mysql_error());

                log__admin("experiment_assign_participants","experiment:".$experiment['experiment_name']);

		$_SESSION['assign_ids']=array();
		message($assigned_count.' '.$lang['xxx_participants_assigned']);
		redirect ('admin/'.thisdoc().'?experiment_id='.$experiment_id);

		}

	elseif ($_REQUEST['show']) {

		$sort = ($_REQUEST['sort']) ? $_REQUEST['sort']:"lname,fname,email";

		if ($_REQUEST['new_query'] || !$_SESSION['assign_select_query']) {
			unset($_REQUEST['new_query']);
	       		$where_clause=query__where_clause($query_modules,
							  $_REQUEST['use'],
							  $_REQUEST['con']);

			if (!$where_clause) $where_clause=query__where_clause_module("all");

			$join_phrase=query__join_assign($experiment,
							$query_modules,
							$_REQUEST['use']);

			$orderlimit=query__orderlimit($query_modules,$_REQUEST['use']);

			$select_query="SELECT ".table('participants').".* 
                        	FROM ".table('participants')." ".
				$join_phrase." ".
                        	$where_clause." ".
				$orderlimit;

                	$_SESSION['assign_where_clause']=$where_clause;
			$_SESSION['assign_select_query']=$select_query;
			$_SESSION['assign_request']=$_REQUEST;
			}
		   else {
			$where_clause=$_SESSION['assign_where_clause'];
                        $select_query=$_SESSION['assign_select_query'];
			}

		echo  '<FORM name="part_list" method=post action="'.thisdoc().'">
                <INPUT type=hidden name=experiment_id value="'.$experiment_id.'">';

		script__part_list_checkall();

		$assign_ids=query_show_result($select_query,$sort,"assign");
		$_SESSION['assign_ids']=$assign_ids;

		$count_results=count($assign_ids);

		echo '<TABLE border=0>
			<TR>
				<TD align=left>
                		<input type=button value="'.$lang['select_all'].
					'" onClick="checkAll(\'p\','.$count_results.')">
                		<br>
                		<input type=button value="'.$lang['select_none'].
					'" onClick="uncheckAll(\'p\','.$count_results.')">
                		</TD>
				<TD colspan=2></TD>
			</TR>
                	<TR>
				<TD></TD>
				<TD align=center>
                		<INPUT type=submit name="addselected" 
					value="'.$lang['assign_only_marked_participants'].'">
                		</TD>
                		<TD align=center>
                		<INPUT type=submit name="addall" 
					value="'.$lang['assign_all_participants_in_list'].'">
                		</TD>
			</TR>
		     </TABLE>';

		echo '</FORM>';

		}

	else 	{

		if ($_REQUEST['new']) $_SESSION['assign_request']=array();
			else {
				$new_req=array_merge($_SESSION['assign_request'],$_REQUEST);
				$_REQUEST=$new_req;
				$_SESSION['assign_request']=$_REQUEST;
				}

		$exptypes=load_external_experiment_type_names(false);
		$wstring="subscriptions LIKE '%".$experiment['experiment_ext_type']."%'";
		echo participants__count_participants($wstring);
		echo ' '.$lang['xxx_part_in_db_for_xxx_exp'].' ';
		echo $exptypes[$experiment['experiment_ext_type']];
		echo '<BR><BR>';
		echo experiment__count_participate_at($experiment_id).' '.
        		$lang['participants_assigned_to_this_experiment'];
		echo '
			<BR><BR>
	
        		<FORM action="'.thisdoc().'" method="POST">
			<INPUT type=hidden name="new_query" value="true">
			<INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">';
        		query__form($query_modules,$experiment);

        	echo '	</FORM>';

		}
	echo '	<A HREF="experiment_show.php?experiment_id='.$experiment_id.'">
			'.$lang['mainpage_of_this_experiment'].'</A><BR><BR>

		</CENTER>';

include ("footer.php");

?>
