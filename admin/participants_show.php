<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="participants";
$title="edit_participants";
$jquery=array('arraypicker','textext','dropit','queryform','datepicker','popup');
include ("header.php");
if ($proceed) {
    $allow=check_allow('participants_show','participants_main.php');
}
if ($proceed) {
    $only_eligible= (isset($_REQUEST['only_eligible']) && $_REQUEST['only_eligible']) ? $_REQUEST['only_eligible'] : false;

    if (isset($_REQUEST['active']) && $_REQUEST['active']) $active=true;
    else $active=false;

    // to encode: json_encode($_REQUEST['form']).'<BR>';
    // do decode: json_decode($_SESSION['lastquery'],true);

    if (isset($_REQUEST['save_query'])) {
        // get old query
        if ($active) {
            $posted_query_json=$_SESSION['lastquery_participants_search_active'];
            $done=query__save_query($posted_query_json,'participants_search_active');
        } else {
            $posted_query_json=$_SESSION['lastquery_participants_search_all'];
            $done=query__save_query($posted_query_json,'participants_search_all');
        }
        $cgivars=array();
        if(isset($_REQUEST['search_sort'])) $cgivars[]='search_sort='.urlencode($_REQUEST['search_sort']);
        if ($active) $cgivars[]='active=true';
        if (count($cgivars)>0) $cgivarst='?'.implode("&",$cgivars); else $cgivarst='';
        redirect('admin/'.thisdoc().$cgivarst);

    }
}

if ($proceed) {
    if (isset($_REQUEST['action']) && $_REQUEST['action']) {
        if (isset($_REQUEST['search_sort'])) $search_sort=$_REQUEST['search_sort'];
        else $search_sort='';

        $plist_ids=array();
        if (isset($_REQUEST['sel'])) {
            foreach($_REQUEST['sel'] as $k=>$v) if($v) $plist_ids[]=$k;
        }
        $num_participants=count($plist_ids);

        if ($num_participants>0) {
            if ($_REQUEST['action']=='bulkmail') {
                // message_subject  message_text
                $inv_langs=lang__get_part_langs();
                $continue=true;
                foreach ($inv_langs as $inv_lang) {
                    if (!isset($_REQUEST['message_subject_'.$inv_lang]) || !$_REQUEST['message_subject_'.$inv_lang]) {
                        message (lang('subject').': '.lang('missing_language').": ".$inv_lang);
                        $continue=false;
                    }
                    if (!isset($_REQUEST['message_text_'.$inv_lang]) || !$_REQUEST['message_text_'.$inv_lang]) {
                        message (lang('message_text').': '.lang('missing_language').": ".$inv_lang);
                        $continue=false;
                    }
                }
                if ($continue) {
                    $bulk_id=time();
                    $pars=array();
                    foreach ($inv_langs as $inv_lang) {
                        $pars[]=array(':bulk_id'=>$bulk_id,
                                    ':inv_lang'=>$inv_lang,
                                    ':subject'=>$_REQUEST['message_subject_'.$inv_lang],
                                    ':body'=>$_REQUEST['message_text_'.$inv_lang]);
                    }
                    $query="INSERT INTO ".table('bulk_mail_texts')."
                            SET bulk_id= :bulk_id,
                            lang= :inv_lang,
                            bulk_subject= :subject,
                            bulk_text= :body";
                    $done=or_query($query,$pars);
                    $done=experimentmail__send_bulk_mail_to_queue($bulk_id,$plist_ids);
                    message($num_participants.' '.lang('xxx_bulk_mails_sent_to_mail_queue'));
                    log__admin("bulk_mail","recipients: ".$num_participants);
                    redirect('admin/'.thisdoc().'?active='.$active.'&search_sort='.$search_sort);
                } else {
                    $_REQUEST['search_sort']=$search_sort;
                }
            } elseif ($_REQUEST['action']=='status') {
                if (isset($_REQUEST['new_status']) && $_REQUEST['new_status']!='') {
                    if (!isset($_REQUEST['remark'])) $remark='';
                    else $remark=', '.$_REQUEST['remark'];
                    $pars=array();
                    foreach ($plist_ids as $pid) {
                        $pars[]=array(':participant_id'=>$pid,
                                    ':status_id'=>$_REQUEST['new_status'],
                                    ':remark'=>$remark);
                        log__admin("bulk_change_status","participant_id: ".$pid);
                    }
                    $query="UPDATE ".table('participants')."
                            SET status_id= :status_id,
                            remarks= CONCAT(remarks, :remark)
                            WHERE participant_id = :participant_id";
                    $done=or_query($query,$pars);
                    message($num_participants.' '.lang('xxx_participants_moved_to_new_status'));
                    redirect('admin/'.thisdoc().'?active='.$active.'&search_sort='.$search_sort);
                }
            } elseif ($_REQUEST['action']=='profile_update') {
                // new_profile_update_status do_pool_transfer new_pool
                $continue=true;
                if (!isset($_REQUEST['new_profile_update_status']) || !in_array($_REQUEST['new_profile_update_status'],array('y','n'))) {
                    $continue=false;
                }
                if ($continue) {
                    if ($_REQUEST['new_profile_update_status']=='y' && isset($_REQUEST['do_pool_transfer']) && $_REQUEST['do_pool_transfer']=='y') {
                        if (!isset($_REQUEST['new_pool']) || !($_REQUEST['new_pool']>0)) {
                            $continue=false;
                        } else {
                            $new_pool=$_REQUEST['new_pool'];
                        }
                    } else {
                        $new_pool=NULL;
                    }
                }
                if ($continue) {
                    $pars=array();
                    foreach ($plist_ids as $pid) {
                        $thispar=array(':participant_id'=>$pid,
                                    ':pending_profile_update_request'=>$_REQUEST['new_profile_update_status'],
                                    ':profile_update_request_new_pool'=>$new_pool);
                        $pars[]=$thispar;
                        $target="participant_id: ".$pid.", update_request: ".$_REQUEST['new_profile_update_status'];
                        if ($new_pool) $target.=", new_pool: ".$new_pool;
                        log__admin("bulk_set_profile_update_request",$target);
                    }
                    $query="UPDATE ".table('participants')."
                            SET pending_profile_update_request= :pending_profile_update_request,
                                profile_update_request_new_pool = :profile_update_request_new_pool
                                WHERE participant_id = :participant_id";
                    $done=or_query($query,$pars);
                    message($num_participants.' '.lang('xxx_participants_were_assigned_a_new_profile_update_status'));
                    redirect('admin/'.thisdoc().'?active='.$active.'&search_sort='.$search_sort);
                }
            } elseif ($_REQUEST['action']=='bulk_anonymization') {
                // do_status_change new_status
                $continue=true;
                $anon_fields=participant__get_result_table_columns('anonymize_profile_list');
                if (!is_array($anon_fields) || !(count($anon_fields)>0)) {
                    $continue=false;
                    $message(lang('error_no_fields_to_anonymize_defined'));
                }
                if ($continue) {
                    if (isset($_REQUEST['do_status_change']) && $_REQUEST['do_status_change']=='y') {
                        if (!isset($_REQUEST['new_status'])) {
                            $continue=false;
                        } else {
                            $new_status=$_REQUEST['new_status'];
                        }
                    } else {
                        $new_status=-1;
                    }
                }
                if ($continue) {
                    $pars=array();
                    foreach ($plist_ids as $pid) {
                        $thispar=array(':participant_id'=>$pid);
                        foreach ($anon_fields as $field_name=>$anon_field) {
                            $thispar[$field_name]=$anon_field['item_details']['field_value'];
                        }
                        if ($new_status>-1) $thispar['status_id']=$new_status;
                        
                        $pars[]=$thispar;
                        $target="participant_id: ".$pid;
                        if ($new_status>-1) $target.=", new_status: ".$new_status;
                        log__admin("bulk_profile_anonymization",$target);
                    }
                    $anon_field_list=array();
                    foreach ($anon_fields as $field_name=>$anon_field) {
                        $anon_field_list[]=$field_name." = :".$field_name;
                    }
                    $query="UPDATE ".table('participants')."
                            SET ".implode(" , ",$anon_field_list);
                    if ($new_status>-1) $query.=", status_id = :status_id ";
                    $query.=" WHERE participant_id = :participant_id ";
                    $done=or_query($query,$pars);
                    message($num_participants.' '.lang('xxx_participant_profiles_were_anonymized'));
                    redirect('admin/'.thisdoc().'?active='.$active.'&search_sort='.$search_sort);
                }
            } else {
                // redirect to same page
                redirect('admin/'.thisdoc().'?active='.$active.'&search_sort='.$search_sort);
            }
        } else {
            message(lang('no_participants_selected'));
            $_REQUEST['search_sort']=$search_sort;
        }
    }
}

if ($proceed) {


    echo '<center>';

    show_message();
}

if ($proceed) {
    if(isset($_REQUEST['search_submit']) || isset($_REQUEST['search_sort'])) {
        if(isset($_REQUEST['search_sort'])){
            // use old query
            if ($active) {
                $posted_query_json=$_SESSION['lastquery_participants_search_active'];
                $query_id=$_SESSION['lastqueryid_participants_search_active'];
                $sort=query__get_sort('participants_search_active',$_REQUEST['search_sort']); // sanitize sort
            } else {
                $posted_query_json=$_SESSION['lastquery_participants_search_all'];
                $query_id=$_SESSION['lastqueryid_participants_search_all'];
                $sort=query__get_sort('participants_search_all',$_REQUEST['search_sort']); // sanitize sort
            }
            $posted_query=json_decode($posted_query_json,true);

        } else {
            // store new query in session
            $query_id=time();
            if(isset($_REQUEST['form'])) $posted_query=$_REQUEST['form']; else $posted_query=array('query'=>array());
            $posted_query_json=json_encode($posted_query);
            if ($active) {
                $_SESSION['lastquery_participants_search_active'] =  $posted_query_json;
                $_SESSION['lastqueryid_participants_search_active'] =  $query_id;
                $sort=query__load_default_sort('participants_search_active');
            } else {
                $_SESSION['lastquery_participants_search_all'] =  $posted_query_json;
                $_SESSION['lastqueryid_participants_search_all'] =  $query_id;
                $sort=query__load_default_sort('participants_search_all');
            }
        }

        // show query in human-readable form
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,$active);

        echo '<TABLE border=0>';
        echo '<TR><TD style="outline: 1px solid black; background: '.$color['search__pseudo_query_background'].'">';
        echo $pseudo_query_display;
        echo '</TD></TR></TABLE>';
        echo '<BR><BR>';
        $query_array=query__get_query_array($posted_query['query']);
        //dump_array($query_array);

        if ($active) {
            $active_clause=array('query'=>participant_status__get_pquery_snippet("eligible_for_experiments"),'pars'=>array());
            $additional_clauses=array($active_clause);
        } else $additional_clauses=array();

        $query=query__get_query($query_array,$query_id,$additional_clauses,$sort);

        //echo '<TABLE width="70%" border=0><TR><TD><B>Query:</B></TD></TR><TR><TD>';
        //echo $query['query'];
        //echo '</TD></TR></TABLE>';
        //dump_array($query['pars'],"Parameters");
        //dump_array($_REQUEST);
        // show list of results

        echo '<form id="bulkactionform" action="participants_show.php" method="POST">';
        if (isset($_REQUEST['search_sort'])) echo '<input type="hidden" name="search_sort" value="'.$_REQUEST['search_sort'].'">';
        if (isset($_REQUEST['active'])) echo '<input type="hidden" name="active" value="'.$_REQUEST['active'].'">';

        if ($active) $plist_ids=query_show_query_result($query,"participants_search_active");
        else $plist_ids=query_show_query_result($query,"participants_search_all");

        echo '</form>';

    } else {

        //if (!isset($_SESSION['lastquery_'.$experiment_id])) $_SESSION['lastquery_'.$experiment_id]='';
        //$load_query=$_SESSION['lastquery_'.$experiment_id];

        if ($active) {
            if (!isset($_SESSION['lastquery_participants_search_active'])) $_SESSION['lastquery_participants_search_active']='';
            $load_query=$_SESSION['lastquery_participants_search_active'];
            if (!$load_query) $load_query=query__load_default_query('participants_search_active');
            $hide_modules=array('statusids');
            $status_query=participant_status__get_pquery_snippet("eligible_for_experiments");
            $formextra='<INPUT type="hidden" name="active" value="true">';
            $saved_queries=query__load_saved_queries('participants_search_active',$settings['queryform_partsearchactive_savedqueries_numberofentries']);
        } else {
            if (!isset($_SESSION['lastquery_participants_search_all'])) $_SESSION['lastquery_participants_search_all']='';
            $load_query=$_SESSION['lastquery_participants_search_all'];
            if (!$load_query) $load_query=query__load_default_query('participants_search_all');
            $hide_modules=array();
            $status_query="";
            $formextra='';
            $saved_queries=query__load_saved_queries('participants_search_all',$settings['queryform_partsearchall_savedqueries_numberofentries']);
        }
        if ($active) {
            $active_clause=participant_status__get_pquery_snippet("eligible_for_experiments");
            $count=participants__count_participants($active_clause);
            echo $count.' '.lang('active_participant_profiles_in_database').'<BR>';
        } else {
            $count=participants__count_participants();
            echo $count.' '.lang('participant_profiles_in_database').'<BR>';
        }

        echo '<TABLE width="80%"><TR><TD>';
        query__show_form($hide_modules,false,$load_query,lang('search_and_show'),$saved_queries,$status_query,$formextra);
        echo '</TD></TR></TABLE>';

    }
}

if ($proceed) {
    echo '</center>';

}
include ("footer.php");
?>