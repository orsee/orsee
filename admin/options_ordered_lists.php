<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="options";
$jquery=array('dropit','listtool');
include ("header.php");
if ($proceed) {
	if (isset($_REQUEST['list'])) $list=$_REQUEST['list']; else $list="";

	$allow=check_allow('pform_results_lists_edit','options_main.php');
}

if ($proceed) {
	$result_lists=array('result_table_search_active','result_table_search_all',
				'result_table_assign','result_table_search_duplicates',
				'experiment_assigned_list','session_participants_list','session_participants_list_pdf',
				'email_participant_guesses_list');
	$other_lists=array('result_table_search_unconfirmed','public_menu','admin_menu');
	if (!(in_array($list,$result_lists) || in_array($list,$other_lists))) redirect ('admin/options_participant_profile.php');
}

if ($proceed) { 
	if ($list=='result_table_search_active') {
		$header=lang('columns_in_search_results_table_for_active_participants');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='result_table_search_all') {
		$header=lang('columns_in_search_results_table_for_all_participants');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='result_table_assign') {
		$header=lang('columns_in_results_table_for_assign_query');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='result_table_search_duplicates') {
		$header=lang('columns_in_search_results_table_for_profile_duplicates');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='result_table_search_unconfirmed') {
		$header=lang('columns_in_search_results_table_for_unconfirmed_profiles');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='experiment_assigned_list') {
		$header=lang('columns_in_list_of_assigned_participants');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='session_participants_list') {
		$header=lang('columns_in_session_participants_list');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='session_participants_list_pdf') {
		$header=lang('columns_in_pdf_session_participants_list');
		$cols=participant__get_possible_participant_columns($list);
	} elseif ($list=='email_participant_guesses_list') {
		$header=lang('email_module_participant_guesses_list');
		$cols=participant__get_possible_participant_columns($list);
	} 
	if (!isset($cols)) redirect ('admin/options_participant_profile.php');
}

if ($proceed) { 
    if (isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
    	if(isset($_REQUEST['item_order']) && is_array($_REQUEST['item_order']) && count($_REQUEST['item_order'])>0) {
    		if (isset($_REQUEST['sortby']) && $_REQUEST['sortby']) $details=array(trim($_REQUEST['sortby'])=>array('default_sortby'=>1));
    		else $details=array();
 			$done=options__save_item_order($list,$_REQUEST['item_order'],$details);
 			message(lang('changes_saved'));
 			redirect('admin/options_ordered_lists.php?list='.urlencode($list));
    	}
    } 
}

if ($proceed) {
	$pars=array(':item_type'=>$list);
	$query="SELECT *
      		FROM ".table('objects')."
			WHERE item_type= :item_type 
      		ORDER BY order_number";
	$result=or_query($query,$pars);
	
	$rows=array();
	while ($line=pdo_fetch_assoc($result)) {
		$rows[$line['item_name']]=$line;
	}

	if (in_array($list,$result_lists))  {
		$listrows=options__ordered_lists_get_current($cols,$rows,true);
		$headers='<TD></TD><TD align="center">'.str_replace(" ","<BR>",lang('sort_table_by')).'</TD>';
	} else {
		$listrows=options__ordered_lists_get_current($cols,$rows,false);
		$headers='';
	}
	
	echo '<center>';
	echo '<form action="" method="POST">';
	echo '<TABLE class="or_formtable">
			<TR><TD>
				<TABLE width="100%" border=0 class="or_panel_title"><TR>
						<TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
							'.$header.'
						</TD>
				</TR></TABLE>
			</TD></TR>';
	echo '<TR><TD align="center">';
	echo formhelpers__orderlist("ordered_list", "item_order", $listrows, false, lang('add'),$headers);
	echo '<input class="button" style="display: block;" name="save_order" type="submit" value="'.lang('save_order').'">';
    echo '</TD></TR></TABLE>';    
	
	echo '</form>';
	
	echo '<BR><BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';
	
	echo '</CENTER>';

}
include ("footer.php");
?>