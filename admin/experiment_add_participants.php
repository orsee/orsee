<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments";
$title="assign_subjects";
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
    echo '<div class="orsee-panel-title"><div>'.lang('assign_subjects').': '.$experiment['experiment_name'].'</div></div>';
    echo '<div>';


    if (isset($_REQUEST['make_permanent']) && $_REQUEST['make_permanent'] && $settings['allow_permanent_queries']=='y' && check_allow('experiment_assign_query_permanent_activate')) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            // get old query
            $posted_query_json=$_SESSION['lastquery_assign_'.$experiment_id];
            $done=query__save_query($posted_query_json,'assign',$experiment_id,array('permanent_start_time'=>time()),true);
            redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id);
        }
    } elseif ((isset($_REQUEST['addselected']) && $_REQUEST['addselected']) || (isset($_REQUEST['addall']) && $_REQUEST['addall'])) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            // data base queries for assign ...

            $assign_ids=$_SESSION['assign_ids_'.$experiment_id];
            $totalcount=count($assign_ids);
            $selected='n';

            if (isset($_REQUEST['addselected']) && $_REQUEST['addselected']) {
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

            if (count($assign_ids)>0) {
                $values_clause=array();
                $pars=array();
                foreach ($assign_ids as $id) {
                    $pars[]=array(':participant_id'=>$id,':experiment_id'=>$experiment_id);
                }
                $query="INSERT INTO ".table('participate_at')." (participant_id,experiment_id)
                    VALUES (:participant_id , :experiment_id)";
                $done=or_query($query,$pars);
                $assigned_count=count($assign_ids);
                log__admin("experiment_assign_participants","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id'].", count:".$assigned_count);
                $done=query__save_query($_SESSION['lastquery_assign_'.$experiment_id],'assign',$experiment_id,array('assigned_count'=>$assigned_count,'selected'=>$selected,'totalcount'=>$totalcount));
            } else {
                $assigned_count=0;
            }
            $_SESSION['assign_ids_'.$experiment_id]=array();
            message($assigned_count.' '.lang('xxx_participants_assigned'));
            redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id);
        }
    } elseif (isset($_REQUEST['search_submit']) || isset($_REQUEST['search_sort'])) {
        if (isset($_REQUEST['search_sort'])) {
            $posted_query_json=$_SESSION['lastquery_assign_'.$experiment_id];
            $query_id=$_SESSION['lastqueryid_assign_'.$experiment_id];
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
            $_SESSION['lastquery_assign_'.$experiment_id] =  $posted_query_json;
            $_SESSION['lastqueryid_assign_'.$experiment_id] =  $query_id;
            $sort=query__load_default_sort('assign',$experiment_id);
        }

        if (check_allow('participants_edit')) {
            echo javascript__edit_popup();
        }

        // show query in human-readable form
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,true);

        echo '<div class="orsee-form-row-grid orsee-form-row-grid--2" style="align-items: start;">';
        echo '<div class="orsee-form-row-col">';
        orsee_callout($pseudo_query_display,'note','Query');
        echo '</div>';
        echo '<div class="orsee-form-row-col has-text-centered">';

        // permanent query button
        if ($settings['allow_permanent_queries']=='y' && check_allow('experiment_assign_query_permanent_activate')) {
            $cgivars=array();
            $cgivars[]="make_permanent=true";
            if ($sort) {
                $cgivars[]='search_sort='.urlencode($sort);
            }
            $cgivars[]='experiment_id='.$experiment_id;
            $cgivars[]='csrf_token='.urlencode(csrf__get_token());
            $link=thisdoc();
            if (count($cgivars)>0) {
                $link.='?'.implode("&",$cgivars);
            }
            echo button_link($link,lang('activate_query_permanently'),'toggle-on');
            $perm_queries=query__get_permanent($experiment_id);
            if (count($perm_queries)>0) {
                echo '<BR>'.lang('found_active_permanent_query');
            }
        }

        echo '</div></div>';
        $query_array=query__get_query_array($posted_query['query']);

        $active_clause=array('query'=>participant_status__get_pquery_snippet("eligible_for_experiments"),'pars'=>array());
        $exptype_clause=array('query'=>"subscriptions LIKE (:experiment_ext_type)",'pars'=>array(':experiment_ext_type'=>"%|".$experiment['experiment_ext_type']."|%"));
        $notyetassigned_clause=array('query'=>"participant_id NOT IN (SELECT participant_id FROM ".table('participate_at')." WHERE experiment_id= :experiment_id)",'pars'=>array(':experiment_id'=>$experiment_id));
        $additional_clauses=array($active_clause,$exptype_clause,$notyetassigned_clause);

        $query=query__get_query($query_array,$query_id,$additional_clauses,$sort);

        //query__debug_sql_panel($query['query'],$query['pars'],'Query');

        echo  '<FORM name="part_list" method="POST" action="'.thisdoc().'">
                <INPUT type=hidden name=experiment_id value="'.$experiment_id.'">
                '.csrf__field().'
                ';

        // show list of results
        $assign_ids=query_show_query_result($query,"assign");
        $_SESSION['assign_ids_'.$experiment_id]=$assign_ids;

        echo '</FORM>';
    } else {
        if (!isset($_SESSION['lastquery_assign_'.$experiment_id])) {
            $_SESSION['lastquery_assign_'.$experiment_id]='';
        }
        $load_query=$_SESSION['lastquery_assign_'.$experiment_id];
        if (!$load_query) {
            $load_query=query__load_default_query('assign',$experiment_id);
        }
        $hide_modules=array('statusids','subscriptions');
        $status_query=participant_status__get_pquery_snippet("eligible_for_experiments");
        $saved_queries=query__load_saved_queries('assign',$settings['queryform_experimentassign_savedqueries_numberofentries'],$experiment_id);

        $exptypes=load_external_experiment_types();
        $active_clause=participant_status__get_pquery_snippet("eligible_for_experiments");
        $exptype_clause="subscriptions LIKE '%|".$experiment['experiment_ext_type']."|%'";
        echo participants__count_participants($active_clause.' AND '.$exptype_clause);
        echo ' '.lang('xxx_part_in_db_for_xxx_exp').' ';
        if (!isset($exptypes[$experiment['experiment_ext_type']]['exptype_name'])) {
            $exptypes[$experiment['experiment_ext_type']]['exptype_name']='type undefined';
        }
        echo $exptypes[$experiment['experiment_ext_type']]['exptype_name'];
        echo '<BR><BR>';
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
