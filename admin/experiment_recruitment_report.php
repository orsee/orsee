<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments";
$title="experiment_recruitment_report";
$lang_icons_prepare=true;
include("header.php");
if ($proceed) {
    if (!$_REQUEST['experiment_id']) redirect ("admin/");
    else $experiment_id=$_REQUEST['experiment_id'];
}

if ($proceed) {
    $allow=check_allow('experiment_recruitment_report_show','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    $alllangs=get_languages();
    if (isset($_REQUEST['replang']) && in_array($_REQUEST['replang'],$alllangs) && $_REQUEST['replang']!=lang('lang')) {
        $replang=$_REQUEST['replang'];
    } else $replang=lang('lang');

    $lang_names=lang__get_language_names();
    $switchlang_text='';
    foreach ($alllangs as $thislang) {
        if ($thislang != $replang) {
            $switchlang_text.='<A HREF="'.thisdoc().'?experiment_id='.$experiment_id.'&replang='.$thislang.'"><span class="languageicon langicon-'.$thislang.'">';
            if (isset($lang_names[$thislang]) && $lang_names[$thislang]) $switchlang_text.=$lang_names[$thislang]; else $switchlang_text.=$thislang;
            $switchlang_text.='</span></A>&nbsp;&nbsp;&nbsp;';
        }
    }
    if ($switchlang_text) $switchlang_text='<P align="right">'.lang('this_report_in_language').' '.$switchlang_text.'</P>';
    if ($replang!=lang('lang')) {
        $switched_lang=true;
        $mylang=$lang;
        $lang=load_language($_REQUEST['replang']);
    }
}

if ($proceed) {
    // load sessions if lab experiment
    $sessions=array();
    if ($experiment['experiment_type']=="laboratory") {
        $pars=array(':experiment_id'=>$experiment['experiment_id']);
        $query="SELECT *
                FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id
                ORDER BY session_start";
        $result=or_query($query,$pars); $min=0; $max=0; $sids=array();
        while ($s=pdo_fetch_assoc($result)) {
            $sessions[$s['session_id']]=$s;
            $sesstime=$s['session_start'];
            if ($min==0) {
                $min=$sesstime; $max=$sesstime;
            } else {
                if ($sesstime < $min) $min=$sesstime;
                if ($sesstime > $max) $max=$sesstime;
            }
            $sids[]=$s['session_id'];
        }
        // get pstatus counts
        if (count($sids)>0) {
            $pars=array(':experiment_id'=>$experiment['experiment_id']);
            $query="SELECT session_id, pstatus_id,
                    COUNT(*) as num
                    FROM ".table('participate_at')."
                    WHERE experiment_id= :experiment_id
                    AND session_id>0
                    GROUP BY session_id, pstatus_id";
            $result=or_query($query,$pars);
            while ($s=pdo_fetch_assoc($result)) {
                $sessions[$s['session_id']]['num_status'.$s['pstatus_id']]=$s['num'];
            }
        }
    }
}

if ($proceed) {
    // load all types we need to know
    $exptypes=load_external_experiment_types();
    $preloaded_laboratories=laboratories__get_laboratories();
    $pstatuses=expregister__get_participation_statuses();

}

if ($proceed) {
    if ($switchlang_text) echo '<BR>'.$switchlang_text.'<BR>';
    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.$experiment['experiment_name'].'
            </TD></TR></TABLE><BR>';

    echo '<TABLE width="90%">';


    /////////////////////////////
    /// EXPERIMENT
    /////////////////////////////
    echo '<TR><TD>';
    echo '<TABLE class="or_orr_section_head"><TR><TD align="center" valign="middle">
            '.lang('experiment').'
            </TD></TR></TABLE>';
    echo '</TD></TR>
            <TR><TD>';

    echo '<TABLE class="or_orr_section_content">
                    <TR>
                        <TD>'.lang('id').':</TD><TD>'.$experiment['experiment_id'].'</TD>
                        <TD>'.lang('type').':</TD>';
    if (!isset($exptypes[$experiment['experiment_ext_type']]['exptype_name']))
            $exptypes[$experiment['experiment_ext_type']]['exptype_name']='type undefined';
    echo '<TD>'.$lang[$experiment['experiment_type']].' ('.$exptypes[$experiment['experiment_ext_type']]['exptype_name'].')</TD>
                    </TR>
                    <TR>
                        <TD>'.lang('name').':</TD><TD>'.$experiment['experiment_name'].'</TD>
                        <TD>'.lang('public_name').':</TD><TD>'.$experiment['experiment_public_name'].'</TD>
                    </TR>';
    echo '          <TR>
                        <TD>'.lang('class').':</TD>
                        <TD>'.experiment__experiment_class_field_to_list($experiment['experiment_class']).'</TD>
                        <TD>'.lang('experimenter').':</TD><TD>'.experiment__list_experimenters($experiment['experimenter'],true,true).'</TD>
                    </TR>';

    // CONDITIONAL EXPERIMENT FIELDS
    $conditional_fields=array();

    if ($experiment['experiment_description'])
        $conditional_fields[]='<TD>'.lang('internal_description').':</TD><TD>'.$experiment['experiment_description'].'</TD>';
    if ($experiment['public_experiment_note'])
        $conditional_fields[]='<TD>'.lang('public_experiment_note').':</TD><TD>'.$experiment['public_experiment_note'].'</TD>';
    if ($settings['enable_editing_of_experiment_sender_email']=='y')
        $conditional_fields[]='<TD>'.lang('email_sender_address').':</TD><TD>'.$experiment['sender_mail'].'</TD>';



    $i=0;
    foreach ($conditional_fields as $condfield) {
        if ($i/2 == round($i/2)) {
            echo '<TR>';
            echo $condfield;
            if (isset($conditional_fields[$i+1])) echo $conditional_fields[$i+1];
            else echo '<TD></TD>';
            echo '</TR>';
        }
        $i++;
    }

    // ETHICS APPROVAL - IF ENABLED
    if ($settings['enable_ethics_approval_module']=='y') {
        $ethics=experiment__get_ethics_approval_desc($experiment);
        echo '<TR><TD colspan="4">'.$ethics['text'].'</TD></TR>';
    }
    echo '</TABLE>';
    echo '</TD></TR>';

    if ($experiment['experiment_type']=="laboratory") {
    /////////////////////////////
    /// SESSIONS
    /////////////////////////////
    echo '<TR><TD>';
    echo '<TABLE class="or_orr_section_head"><TR><TD align="center" valign="middle">
            '.lang('sessions');
    if ($min>0) echo ' '.lang('from').' '.ortime__format(ortime__sesstime_to_unixtime($min),'hide_time').'
                    '.lang('to').' '.ortime__format(ortime__sesstime_to_unixtime($max),'hide_time');
    echo '  </TD></TR></TABLE>';
    echo '</TD></TR><TR><TD>';

    echo '<table class="or_orr_section_content">';
    $shade=false;
    $num_cols=count($pstatuses);
    foreach ($sessions as $s) {
        if ($shade) { $rowspec=' class="or_orr_list_shade_even"'; $shade=false; }
        else { $rowspec=' class="or_orr_list_shade_odd"'; $shade=true; }
        $session_time=session__build_name($s);
        $ssicons=array("planned"=>"wrench","live"=>"spinner fa-spin fa-fw","completed"=>"thumbs-o-up","balanced"=>"money");
        echo '<tr'.$rowspec.'><td colspan="'.$num_cols.'"><B>'.$session_time;
        echo ', '.$preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        echo '</B></td>
                <td colspan=3 align="right">'.lang('session_status').': <B><span class="session_status_'.$s['session_status'].'">'.
            '<i class="fa fa-'.$ssicons[$s['session_status']].'"></i>&nbsp;'.$lang['session_status_'.$s['session_status']].'</span></B>
            </td>
            </tr>';
        echo '  <TR'.$rowspec.'>
                <TD>'.lang('subjects').'</TD>
                <TD>'.lang('needed_participants_abbr').': '.$s['part_needed'].'</TD>
                <TD>'.lang('reserve_participants_abbr').': '.$s['part_reserve'].'</TD>';
                foreach ($pstatuses as $pstatus_id=>$pstatus) {
                    echo '<TD>';
                    if ($pstatus['participated']) echo '<B>';
                    echo $pstatus['internal_name'].': ';
                    if (isset($s['num_status'.$pstatus_id])) echo $s['num_status'.$pstatus_id];
                    else echo '0';
                    if ($pstatus['participated']) echo '</B>';
                    echo '</TD>';
                }
        echo '</TR>';
        if ($s['public_session_note']) {
            echo '  <TR'.$rowspec.'>
                    <TD colspan="'.($num_cols+3).'">'.lang('public_session_note').': '.
                        $s['public_session_note'].'</TD></TR>';
        }
        if ($s['session_remarks']) {
            echo '  <TR'.$rowspec.'>
                    <TD colspan="'.($num_cols+3).'"><B>'.lang('remarks').': '.
                        $s['session_remarks'].'</B></TD></TR>';
        }
        echo '<TR'.$rowspec.'><TD colspan="'.($num_cols+3).'" class=small>&nbsp;</TD></TR>';
    }
    echo '</TABLE>';

    echo '</TD></TR>';
    }


    /////////////////////////////
    /// ASSIGNMENTS
    /////////////////////////////
    echo '<TR><TD>';
    echo '<TABLE class="or_orr_section_head"><TR><TD align="center" valign="middle">
            '.lang('recruitment_history').'
            </TD></TR></TABLE>';
    echo '</TD></TR>
            <TR><TD>';

    echo '<table class="or_orr_section_content">';

    $queries=query__load_saved_queries('assign,deassign',-1,$experiment_id,true,"query_time ASC");

    $shade=false;
    foreach ($queries as $q) {
        if ($shade) { $rowspec=' class="or_orr_list_shade_even""'; $shade=false; }
        else { $rowspec=' class="or_orr_list_shade_odd"'; $shade=true; }
        echo '<TR'.$rowspec.'>';
        echo '<TD valign="top"><B>'.ortime__format($q['query_time']).'</B><BR>';
        if ($q['permanent'] || (isset($q['properties']['is_permanent']) && $q['properties']['is_permanent'])) {
            echo '<B>';
            echo lang('report_queries__permanent_query').'</B><BR>';
            echo lang('from').' '.ortime__format($q['properties']['permanent_start_time']).' ';
            if (isset($q['properties']['permanent_start_time']) && !$q['permanent']) echo lang('to').' '.ortime__format($q['query_time']);
            else echo lang('until_now');
            echo '<BR>'.lang('report_queries__number_of_subjects_added').': <B>';
            if (isset($q['properties']['assigned_count']))  echo $q['properties']['assigned_count'];
            else echo 0;
            echo '</B>';
        } else {
            echo '<B>';
            if ($q['query_type']=='assign') echo lang('report_queries__potential_participants_added');
            else echo lang('report_queries__potential_participants_removed');
            echo '</B><BR>';
            if ($q['admin_id']) echo $q['admin_id'].'<BR>';
            echo lang('report_queries__subjects_in_result_set').': ';
            if ($q['properties']['selected']=='n') echo '<B>';
            echo $q['properties']['totalcount'];
            if ($q['properties']['selected']=='n') echo '</B>';
            echo '<BR>';
            if ($q['query_type']=='assign') {
                if ($q['properties']['selected']=='y') echo lang('report_queries__assigned_selected_subset_of_size').': <B>'.$q['properties']['assigned_count'].'</B>';
                else echo lang('report_queries__assigned_all');
            } else {
                if ($q['properties']['selected']=='y') echo lang('report_queries__deassigned_selected_subset_of_size').': <B>'.$q['properties']['assigned_count'].'</B>';
                else echo lang('report_queries__deassigned_all');
            }
        }
        echo '</TD>';
        echo '<TD>';
        $posted_query=json_decode($q['json_query'],true);
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,true);
        echo $pseudo_query_display;
        //echo $q['json_query'];
        echo '</TD>';
        echo '</TR>';
    }
    echo '</table>';
    echo '</TD></TR>';



    /////////////////////////////
    /// SUBJECT POOL STATISTICS
    /////////////////////////////
    echo '<TR><TD>';
    echo '<TABLE class="or_orr_section_head"><TR><TD align="center" valign="middle">
            '.lang('subject_pool_statistics').'
            </TD></TR></TABLE>';
    echo '</TD></TR>
            <TR><TD>';

    $pars=array(':experiment_id'=>$experiment['experiment_id']);
    $query="SELECT max(session_start) as max_time, min(session_start) as min_time
            FROM ".table('sessions')."
            WHERE experiment_id = :experiment_id";
    $line=orsee_query($query,$pars);
    $min_session_time=ortime__sesstime_to_unixtime($line['min_time']);
    $max_session_time=ortime__sesstime_to_unixtime($line['max_time']);

    $total_data=array();
    // pool
    $options=array('upper_experience_limit'=>$min_session_time);
    $condition=array('clause'=>" status_id > 0 AND creation_time < '".$max_session_time."' AND
                  (deletion_time=0 OR deletion_time > '".$min_session_time."') ",
                    'pars'=>array()
                    );
    $total_data['pool']=stats__get_data($condition,'report',array(),$options);
    // experiment eligible
    $options=array('upper_experience_limit'=>$min_session_time,'condition_only_on_pid'=>true);
    $condition=array('clause'=>"participant_id IN (SELECT participant_id FROM ".table('participate_at')."
                                WHERE experiment_id= :experiment_id )",
                    'pars'=>array(':experiment_id'=>$experiment_id)
                    );
    $total_data['exp']=stats__get_data($condition,'report',array(),$options);

    // participated
    $options=array('upper_experience_limit'=>$min_session_time,'condition_only_on_pid'=>true);
    $participated_clause=expregister__get_pstatus_query_snippet("participated");
    $condition=array('clause'=>"participant_id IN (SELECT participant_id FROM ".table('participate_at')."
                                WHERE experiment_id= :experiment_id AND session_id > 0 AND ".$participated_clause.")",
                    'pars'=>array(':experiment_id'=>$experiment_id)
                    );
    $total_data['part']=stats__get_data($condition,'report',array(),$options);

    echo '<TABLE class="or_orr_section_content">';
    $i=0; $cols=2;  $out=array();
    foreach ($total_data['pool'] as $k=>$table1) {
        if (isset($table1['data']) && is_array($table1['data']) && count($table1['data'])>0) $show=true;
        else $show=false;
        if ($show) {
            $out[]=stats__report_display_table($table1,lang('stats_report__pool'),$total_data['exp'][$k],lang('stats_report__assigned'),$total_data['part'][$k],lang('stats_report__participated'));
            if (count($out)==$cols) {
                echo '<TR><TD valign="top" align="center">'.implode('</TD><TD valign="top" align="center">',$out).'</TD></TR>';
                echo '<TR><TD colspan="'.$cols.'">&nbsp;</TD></TR>';
                $out=array();
            }
        }
    }
    if (count($out)>0) {
        echo '<TR><TD valign="top" align="center">'.implode('</TD><TD valign="top" align="center">',$out).'</TD>';
        for($i=count($out);$i<$cols;$i++) echo '<TD></TD>';
        echo '</TR>';
    }
    echo '</TABLE>';

    echo '</TD></TR>';



    echo '</TABLE>';

//  echo '<pre>';
//  var_dump($total_data);
//  echo '</pre>';

    if (isset($switched_lang) && $switched_lang && isset($mylang)) $lang=$mylang;

    echo '<BR><BR><A href="experiment_show.php?experiment_id='.$experiment_id.'">'.icon('back').' '.lang('back').'</A><BR><BR>';
    echo '</center>';

}
include("footer.php");
?>