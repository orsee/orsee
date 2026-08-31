<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments";
$title="remove_participants_from_exp";
$js_modules=array('queryform','flatpickr');
include("header.php");

if ($proceed) {
    if ($_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/experiment_main.php");
    }
}

if ($proceed) {
    $allow=check_allow('experiment_assign_participants','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    show_message();
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('remove_participants_from_exp').': '.$experiment['experiment_name'].'</div></div>';
    echo '<div>';

    if ((isset($_REQUEST['dropselected']) && $_REQUEST['dropselected']) || (isset($_REQUEST['dropall']) && $_REQUEST['dropall'])) {
        if (!csrf__validate_request_message()) {
            redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id);
        }

        // data base queries for assign ...

        $assign_ids=$_SESSION['deassign_ids_'.$experiment_id];
        $totalcount=count($assign_ids);
        $selected='n';

        if (isset($_REQUEST['dropselected']) && $_REQUEST['dropselected']) {
            $selected_ids=array();
            $selected='y';
            $i=0;
            foreach ($assign_ids as $id) {
                if (isset($_REQUEST['sel'][$id]) && $_REQUEST['sel'][$id]) {
                    $selected_ids[]=$id;
                }
            }
            $assign_ids=$selected_ids;
            unset($selected_ids);
        }

        $instring=implode("','",$assign_ids);
        if (count($assign_ids)>0) {
            foreach ($assign_ids as $id) {
                $pars[]=array(':participant_id'=>$id,':experiment_id'=>$experiment_id);
            }
            $query="DELETE FROM ".table('participate_at')."
                    WHERE experiment_id= :experiment_id
                    AND session_id=0 AND pstatus_id = 0
                    AND participant_id= :participant_id";
            $done=or_query($query,$pars);
            $assigned_count=count($assign_ids);
            log__admin("experiment_delete_assigned_participants","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id'].", count:".$assigned_count);
            $done=query__save_query($_SESSION['lastquery_deassign_'.$experiment_id],'deassign',$experiment_id,array('assigned_count'=>$assigned_count,'selected'=>$selected,'totalcount'=>$totalcount));
        } else {
            $assigned_count=0;
        }

        $_SESSION['deassign_ids_'.$experiment_id]=array();
        message($assigned_count.' '.lang('xxx_participants_removed'));
        redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id);
    } elseif (isset($_REQUEST['search_submit']) || isset($_REQUEST['search_sort'])) {
        if (isset($_REQUEST['search_sort'])) {
            $posted_query_json=$_SESSION['lastquery_deassign_'.$experiment_id];
            $query_id=$_SESSION['lastqueryid_deassign_'.$experiment_id];
            $posted_query=json_decode($posted_query_json,true);
            $sort=query__get_sort('assign',$_REQUEST['search_sort']);  // sanitize sort
            $_REQUEST['search_sort']=$sort;
        } else {
            // store new query in session
            $query_id=time();
            if (isset($_REQUEST['form'])) {
                $posted_query=$_REQUEST['form'];
            } else {
                $posted_query=array('query'=>array());
            }
            $posted_query_json=json_encode($posted_query);
            $_SESSION['lastquery_deassign_'.$experiment_id] =  $posted_query_json;
            $_SESSION['lastqueryid_deassign_'.$experiment_id] =  $query_id;
            $sort=query__load_default_sort('assign',$experiment_id);
        }

        if (check_allow('participants_edit')) {
            echo javascript__edit_popup();
        }

        // show query in human-readable form
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,false);

        orsee_callout($pseudo_query_display,'note','Query');
        $query_array=query__get_query_array($posted_query['query']);

        $yetassigned_clause=array('query'=>"participant_id IN (SELECT participant_id FROM ".table('participate_at')." WHERE experiment_id= :experiment_id AND session_id=0 AND pstatus_id=0)",'pars'=>array(':experiment_id'=>$experiment_id));
        $additional_clauses=array($yetassigned_clause);

        $query=query__get_query($query_array,$query_id,$additional_clauses,$sort);

        //query__debug_sql_panel($query['query'],$query['pars'],'Query');

        echo  '<FORM name="part_list" method="POST" action="'.thisdoc().'">
                <INPUT type=hidden name=experiment_id value="'.$experiment_id.'">
                '.csrf__field().'
                ';

        // show list of results
        $assign_ids=query_show_query_result($query,"deassign");
        $_SESSION['deassign_ids_'.$experiment_id]=$assign_ids;

        echo '</FORM>';
    } else {
        if (!isset($_SESSION['lastquery_deassign_'.$experiment_id])) {
            $_SESSION['lastquery_deassign_'.$experiment_id]='';
        }
        $load_query=$_SESSION['lastquery_deassign_'.$experiment_id];
        if (!$load_query) {
            $load_query=query__load_default_query('deassign',$experiment_id);
        }
        $hide_modules=array('statusids','subscriptions');
        $status_query=participant_status__get_pquery_snippet("eligible_for_experiments");
        $saved_queries=query__load_saved_queries('deassign',$settings['queryform_experimentdeassign_savedqueries_numberofentries'],$experiment_id);

        echo experiment__count_participate_at($experiment_id).' '.
        lang('participants_assigned_to_this_experiment');

        echo '<div>';
        query__show_form($hide_modules,$experiment,$load_query,lang('search_and_show'),$saved_queries,$status_query);
        echo '</div>';
    }
}

if ($proceed) {
    echo '<div class="orsee-options-actions">'.button_back('experiment_show.php?experiment_id='.$experiment_id).'</div>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
