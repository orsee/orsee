<?php
ob_start();

$menu__area="participants";
$title="show participants";
$query_modules=array("field","noshowups","nr_participations","subjectpool","gender",
			"study_start","field_of_studies","profession","experiment_classes",
			"experiment_participated_or","experiment_participated_and","experiment_assigned_or");

include ("header.php");

	$allow=check_allow('participants_show','participants_main.php');

	echo '	<center>
		<BR><BR>
			<h4>'.$lang['edit_participants'].'</h4>
		';

	$deleted= ($_REQUEST['deleted']) ? $_REQUEST['deleted'] : "n";

	if ($_REQUEST['show']) {

		$sort = ($_REQUEST['sort']) ? $_REQUEST['sort']:"lname,fname,email";

		if ($_REQUEST['new_query'] || !$_SESSION['assign_select_query']) {
			unset($_REQUEST['new_query']);
	       		$where_clause=query__where_clause($query_modules,
							  $_REQUEST['use'],
							  $_REQUEST['con']);

			if (!$where_clause) $where_clause=query__where_clause_module("all");

			$select_query="SELECT ".table('participants').".* 
                        	FROM ".table('participants')."  
				WHERE deleted='".$deleted."' ".
                        	$where_clause;

                	$_SESSION['assign_where_clause']=$where_clause;
			$_SESSION['assign_select_query']=$select_query;
			$_SESSION['assign_request']=$_REQUEST;
			}
		   else {
			$where_clause=$_SESSION['assign_where_clause'];
                        $select_query=$_SESSION['assign_select_query'];
			}

		//echo '<A HREF="participants_excel_import.php">'.$lang['excel_import'].'</A><BR>';

		$assign_ids=query_show_result($select_query,$sort,"edit");
		$_SESSION['plist_ids']=$assign_ids;

		if (check_allow('participants_bulk_mail')) {
			echo '<BR><BR><TABLE width=80% border=0><TR><TD>';
				experimentmail__bulk_mail_form();
			echo '</TD></TR></TABLE>';
			}

		}

	else 	{

		if ($_REQUEST['new']) $_SESSION['assign_request']=array();
			else {
				$new_req=array_merge($_SESSION['assign_request'],$_REQUEST);
				$_REQUEST=$new_req;
				$_SESSION['assign_request']=$_REQUEST;
				}
		$deleted= ($_REQUEST['deleted']) ? $_REQUEST['deleted'] : "n";

		echo participants__count_participants("deleted = 'n'");
		echo ' '.$lang['xxx_participants_registered'].'
			<BR><BR>
	
        		<FORM action="'.thisdoc().'" method="POST">
			<INPUT type=hidden name="new_query" value="true">';
			if ($deleted=='y') echo '<INPUT type=hidden name="deleted" value="y">';
        		query__form($query_modules);


        	echo '	</FORM>';

		}


	echo '</CENTER>';

include ("footer.php");

?>
