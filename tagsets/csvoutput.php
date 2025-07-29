<?php
// part of orsee. see orsee.org

function csvoutput__make_part_list($experiment_id,$session_id="",$pstatus="",$focus="",$sort="",$file=false,$tlang="") {
    global $settings;

    if ($tlang=="") {
        global $lang;
    } else {
        $lang=load_language($tlang);
    }

    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

    $pstatuses=expregister__get_participation_statuses();

    if ($session_id) {
        $clause="session_id = '".$session_id."'";
        $title=lang('registered_subjects');
    } elseif (isset($pstatuses[$pstatus])) {
        $clause="pstatus_id = '".$pstatus."'";
        if ($pstatus==0) $clause.=" AND session_id != 0";
        $title=lang('subjects_in_participation_status').' "'.$pstatuses[$pstatus]['internal_name'].'"';
    } elseif ($focus=='enroled') {
        $clause="session_id != 0";
        $title=lang('registered_subjects');
    }

    $cols=participant__get_result_table_columns('session_participants_list_pdf');
    if ($session_id) unset($cols['session_id']);
    // load sessions of this experiment
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :experiment_id
            ORDER BY session_start";
    $result=or_query($query,$pars); global $thislist_sessions; $thislist_sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $thislist_sessions[$line['session_id']]=$line;
    }

    // load participant data for this session/experiment
    $pars=array(':experiment_id'=>$experiment_id);
    $select_query="SELECT * FROM ".table('participate_at').", ".table('participants')."
                    WHERE ".table('participate_at').".experiment_id= :experiment_id
                    AND ".table('participate_at').".participant_id=".table('participants').".participant_id
                    AND (".$clause.")";

    $order=query__get_sort('session_participants_list_pdf',$sort);
    if(!$order) $order=table('participants').".participant_id";
    $select_query.=" ORDER BY ".$order;

    // get result
    $result=or_query($select_query,$pars);

    $participants=array();
    while ($line=pdo_fetch_assoc($result)) {
        $participants[]=$line;
    }
    $result_count=count($participants);

    // load sessions of this experiment
    $pars=array(':texperiment_id'=>$experiment_id);
    $squery="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :texperiment_id
            ORDER BY session_start";
    $result=or_query($squery,$pars); $thislist_sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $thislist_sessions[$line['session_id']]=$line;
    }

    // reorder by session date if ordered by session id
    if ($sort=="session_id") {
        $temp_participants=$participants; $participants=array();
        foreach ($thislist_sessions as $sid=>$s) {
            foreach ($temp_participants as $p) if ($p['session_id']==$sid) $participants[]=$p;
        }
    }
    unset($temp_participants);

    // determine table title
    $table_title=$experiment['experiment_public_name'];
    if ($session_id) $table_title.=', '.lang('session').' '.str_replace("&nbsp;"," ",session__build_name($thislist_sessions[$session_id]));
    $table_title.=' - '.$title;

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$table_title.'.csv"');

    $fp = fopen('php://output', 'w');
    $headers_csv = participant__get_result_table_headcells_pdf($cols);
    fputcsv($fp, $headers_csv);
    $pnr=0;
    foreach ($participants as $p) {
        $pnr++;
        $p['order_number']=$pnr;
        $part_csv = participant__get_result_table_row_pdf($cols,$p);
        fputcsv($fp, $part_csv);
    }
    fclose($fp);

}

?>
