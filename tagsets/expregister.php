<?php
// part of orsee. see orsee.org

function expregister__get_invitations($participant_id) {
    global $settings;
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT *
            FROM ".table('participate_at').", ".table('experiments').", ".table('sessions')."
            WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
            AND ".table('participate_at').".participant_id = :participant_id
            AND ".table('sessions').".session_status = 'live'
            AND ".table('participate_at').".session_id=0
            AND ".table('participate_at').".pstatus_id=0 ";
    if ($settings['enable_enrolment_only_on_invite']=='y')
        $query.= " AND ".table('participate_at').".invited=1 ";
    $query.="AND ".table('experiments').".experiment_type='laboratory'
            ORDER BY ".table('experiments').".experiment_id, session_start";
    $result=or_query($query,$pars);
    $invited=array(); $last_exp_id=""; $inv_experiments=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $varray['registration_unixtime']=sessions__get_registration_end($varray);
        $varray['session_full']=sessions__session_full("",$varray);
        $now=time();
        if( $now < $varray['session_unixtime']) {
            if ($varray['experiment_id'] != $last_exp_id) {
                $last_exp_id=$varray['experiment_id'];
                $varray['new_experiment']=true;
            } else {
                $varray['new_experiment']=false;
            }
            $varray['session_name']=session__build_name($varray);
            $invited[]=$varray;
            $inv_experiments[$varray['experiment_id']]=true;
        }
    }
    $result=array('inv_experiments'=>$inv_experiments,'invited'=>$invited);
    return $result;
}


function expregister__list_invited_for($participant) {
    global $lang, $color, $preloaded_laboratories, $token_string;

    if (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0))
        $preloaded_laboratories=laboratories__get_laboratories();

    $invdata=expregister__get_invitations($participant['participant_id']);
    $invited=$invdata['invited'];
    $inv_experiments=$invdata['inv_experiments'];

    $now=time();

    echo '<TABLE width="100%" border="0" cellspacing="0">';
    $labs=array();
    foreach ($invited as $s) {
        if ($s['new_experiment']) {
            echo '<TR>
                    <TD colspan=3 bgcolor="'.$color['list_shade_subtitle'].'">
                        <B>'.$s['experiment_public_name'].'</B>';
            if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                    echo '<BR><i>'.lang('note').': '.trim($s['public_experiment_note']).'</i>';
            }
            echo '</TD></TR>';
        }
        echo '<TR><TD>&nbsp;&nbsp;&nbsp;</TD><TD bgcolor="'.$color['list_shade1'].'">';
        echo '<B>'.$s['session_name'].'</B>, ';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        else echo lang('unknown_laboratory');
        if (or_setting('include_sign_up_until_on_enrolment_page')) {
            echo ",  ".lang('registration_until')." ";
            echo ortime__format($s['registration_unixtime'],'',lang('lang'));
        }
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '<BR><i>'.lang('note').': '.trim($s['public_session_note']).'</i>';
        }
        echo '</TD>';
        if ((!$s['session_full']) && ($s['registration_unixtime'] >= $now)) {
            echo '<FORM action="participant_show.php">';
            if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
            echo '<INPUT type=hidden name="s" value="'.$s['session_id'].'">
                <TD bgcolor="'.$color['list_shade1'].'">
                <INPUT class="button small" style="font-size: 8pt;" type=submit name="register" value="'.lang('register').'">
                </TD>
                </FORM>';
        } elseif ($s['registration_unixtime'] < $now) {
            echo '<TD bgcolor="'.$color['list_shade1'].'">
                <span class="button disabled" style="font-size: 8pt; color: '.$color['session_public_expired'].';">'.lang('expired').'</span></TD>';
        } else {
                echo '<TD bgcolor="'.$color['list_shade1'].'">
                <span class="button disabled" style="font-size: 8pt; color: '.$color['session_public_complete'].';">'.lang('complete').'</span></TD>';
        }
        echo '
            </TR>
            <TR><TD colpan=3>&nbsp;</TD></TR>';

        $labs[$s['laboratory_id']]=$s['laboratory_id'];
    }
    if (count($invited)==0) echo '<TD><B>'.lang('no_current_invitations').'</B></TD>';
    echo '</TABLE>';
    return $labs;
}

function expregister__get_registrations($participant_id) {
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT * FROM ".table('experiments').",
        ".table('sessions').", ".table('participate_at')."
        WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
        AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
        AND ".table('participate_at').".participant_id = :participant_id
        AND ".table('sessions').".session_id = ".table('participate_at').".session_id
        AND ".table('sessions').".session_status = 'live'
        AND ".table('participate_at').".session_id!=0
        AND ".table('experiments').".experiment_type='laboratory'
        ORDER BY session_start";
    $result=or_query($query,$pars);
    $registered=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $now=time();
        if( $now < $varray['session_unixtime']) {
            $varray['session_name']=session__build_name($varray);
            $registered[]=$varray;
        }
    }
    return $registered;
}

function expregister__list_registered_for($participant,$reg_session_id="") {
    global $lang, $color, $preloaded_laboratories, $settings, $token_string;

    $registered=expregister__get_registrations($participant['participant_id']);

    if (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0))
        $preloaded_laboratories=laboratories__get_laboratories();

    echo '<TABLE width="100%" border=0 cellspacing="0">';

    $labs=array(); $shade=true;

    if (count($registered)>0) {
        echo '<TR bgcolor="'.$color['list_shade_subtitle'].'">
            <TD>'.lang('experiment').'</TD>
            <TD>'.lang('date_and_time').'</TD>
            <TD>'.lang('location').'</TD>';
        if (isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
            echo '<TD></TD>';
        }
        echo '</TR>';
    } else echo '<TD>'.lang('mobile_no_current_registrations').'</TD>';

    foreach ($registered as $s) {
        echo '<TR';
        if ($shade) $shade=false; else $shade=true;
        if ($s['session_id']==$reg_session_id) echo ' bgcolor="'.$color['just_registered_session_background'].'"';
        elseif ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '><TD>'.$s['experiment_public_name'];
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '<BR><i>'.lang('note').': '.trim($s['public_experiment_note']).'</i>';
        }
        echo '</TD>
             <TD>'.$s['session_name'];
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '<BR><i>'.lang('note').': '.trim($s['public_session_note']).'</i>';
        }
        echo '</TD>
             <TD>';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        else echo lang('unknown_laboratory');
        echo '</TD>';
        if (isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
            $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
            if ($s['cancellation_deadline']>time()) {
                echo '<FORM action="participant_show.php">
                <TD>';
                if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
                echo '<INPUT type=hidden name="s" value="'.$s['session_id'].'">
                <INPUT class="button small" style="font-size: 8pt;" type="submit" name="cancel" value="'.lang('cancel_enrolment').'">
                </td></FORM>';
            } else {
                echo '<TD></TD>';
            }
        }
        echo '</TR>';
        $labs[$s['laboratory_id']]=$s['laboratory_id'];
    }
    echo '</TABLE>';
    return $labs;
}

function expregister__get_history($participant_id) {
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT * FROM ".table('experiments').",
        ".table('sessions').", ".table('participate_at')."
        WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
        AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
        AND ".table('participate_at').".participant_id = :participant_id
        AND ".table('sessions').".session_id = ".table('participate_at').".session_id
        AND ".table('participate_at').".session_id!=0
        AND ".table('experiments').".experiment_type='laboratory'
        ORDER BY session_start DESC";
    $result=or_query($query,$pars);
    $history=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $now=time();
        if( $now >= $varray['session_unixtime']) {
            $varray['session_name']=session__build_name($varray);
            $history[]=$varray;
        }
    }
    return $history;
}

function expregister__list_history($participant) {
    global $lang, $color, $preloaded_laboratories;

    if (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0))
        $preloaded_laboratories=laboratories__get_laboratories();

    $history=expregister__get_history($participant['participant_id']);

    echo '<TABLE width=100% border=0 cellspacing="0">';

    if (count($history)>0) {
        echo '<TR bgcolor="'.$color['list_shade_subtitle'].'">
                <TD>'.lang('experiment').'</TD>
                <TD>'.lang('date_and_time').'</TD>
                <TD>'.lang('location').'</TD>
                <TD>'.lang('showup?').'</TD>
                </TR>';
    } else echo '<TD>'.lang('mobile_no_past_enrolments').'</TD>';

    $labs=array(); $shade=true;
    $pstatuses=expregister__get_participation_statuses();
    foreach ($history as $s) {
        echo '<TR';
        if ($shade) $shade=false; else $shade=true;
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '><TD>'.$s['experiment_public_name'].'</TD>
                <TD>'.$s['session_name'].'</TD>
                <TD>';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        else echo lang('unknown_laboratory');
        echo '</TD><TD>';
        if ($s['session_status']=="completed" || $s['session_status']=="balanced") {
            if ($pstatuses[$s['pstatus_id']]['noshow']) {
                $tcolor=$color['shownup_no'];
                //$ttext=lang('no');
            } else {
                $tcolor=$color['shownup_yes'];
                //$ttext=lang('yes');
            }
            $ttext=$pstatuses[$s['pstatus_id']]['display_name'];
            echo '<FONT color="'.$tcolor.'">'.$ttext.'</FONT>';
        } else echo lang('three_questionmarks');
        echo '</TD>';
        echo '</TR>';
        $labs[$s['laboratory_id']]=$s['laboratory_id'];
    }
    echo '</TABLE>';
    return $labs;
}

function expregister__get_participate_at($participant_id,$experiment_id) {
    $pars=array(':participant_id'=>$participant_id,':experiment_id'=>$experiment_id);
    $query="SELECT *
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND participant_id= :participant_id";
    $result=orsee_query($query,$pars);
    return $result;
}


function expregister__register($participant,$session) {
    $pars=array(':session_id'=>$session['session_id'],
                ':experiment_id'=>$session['experiment_id'],
                ':participant_id'=>$participant['participant_id']);
    $query="UPDATE ".table('participate_at')."
            SET session_id=:session_id,
            pstatus_id=0
            WHERE experiment_id=:experiment_id
            AND participant_id=:participant_id";
    $done=or_query($query,$pars);
    $done=experimentmail__experiment_registration_mail($participant,$session);
}

function expregister__cancel($participant,$session) {
    global $settings;
    $pstatuses=expregister__get_participation_statuses();
    if (!isset($settings['subject_cancellation_participation_status'])) $new_status=0;
    else $new_status=$settings['subject_cancellation_participation_status'];
    if (!isset($pstatuses[$new_status])) $new_status=0;
    if ($new_status==0) $session_id=0;
    else $session_id=$session['session_id'];
    $pars=array(':session_id'=>$session_id,
                ':pstatus_id'=>$new_status,
                ':experiment_id'=>$session['experiment_id'],
                ':participant_id'=>$participant['participant_id']);
    $query="UPDATE ".table('participate_at')."
            SET session_id=:session_id,
            pstatus_id=:pstatus_id
            WHERE experiment_id=:experiment_id
            AND participant_id=:participant_id";
    $done=or_query($query,$pars);
    $done=experimentmail__experiment_cancellation_mail($participant,$session);
}




function expregister__participation_status_select_field($postvarname,$selected,$hidden=array(),$show_color=true) {

    $statuses=expregister__get_participation_statuses();
    if ($show_color && isset($statuses[$selected])) {
        global $color;
        if ($statuses[$selected]['participated']) $scolor=$color['pstatus_participated'];
        elseif ($statuses[$selected]['noshow']) $scolor=$color['pstatus_noshow'];
        else $scolor=$color['pstatus_other'];
        $out='<SELECT name="'.$postvarname.'" style="background: '.$scolor.';">';
    } else $out='<SELECT name="'.$postvarname.'">';
    foreach ($statuses as $status) {
        if (!in_array($status['pstatus_id'],$hidden)) {
            $out.='<OPTION value="'.$status['pstatus_id'].'"';
            if ($status['pstatus_id']==$selected) $out.=" SELECTED";
            $out.='>'.$status['internal_name'];
            $out.='</OPTION>
                ';
        }
    }
    $out.='</SELECT>';
    return $out;
}

function expregister__get_pstatus_colors() {
    global $color;
    $statuses=expregister__get_participation_statuses();
    $scolors=array();
    foreach ($statuses as $k=>$status) {
        if ($status['participated']) $scolor=$color['pstatus_participated'];
        elseif ($status['noshow']) $scolor=$color['pstatus_noshow'];
        else $scolor=$color['pstatus_other'];
        $scolors[$k]=$scolor;
    }
    return $scolors;
}

function expregister__get_participation_statuses() {
    global $participation_statuses, $lang;
    if (!(is_array($participation_statuses) && count($participation_statuses)>0)) {
        $participation_statuses=array();
        $query="SELECT *
                FROM ".table('participation_statuses')."
                ORDER BY pstatus_id";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $participation_statuses[$line['pstatus_id']]=$line;
        }
        $query="SELECT *
                FROM ".table('lang')."
                WHERE content_type='participation_status_internal_name'
                OR content_type='participation_status_display_name'
                ORDER BY content_name";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            if ($line['content_type']=='participation_status_internal_name') $field='internal_name'; else $field='display_name';
            $participation_statuses[$line['content_name']][$field]=$line[lang('lang')];
        }
    }
    return $participation_statuses;
}

function expregister__get_specific_pstatuses($what="participated",$reverse=false) {
    // what can be participated, noshow, participateagain
    $pstatuses=expregister__get_participation_statuses();
    $psarr=array();
    foreach ($pstatuses as $psid=>$pstatus) {
        if ($pstatus[$what]) $psarr[]=$psid;
    }
    return $psarr;
}

function expregister__get_pstatus_query_snippet($what="participated",$reverse=false) {
    // what can be participated, noshow, participateagain
    $psarr=expregister__get_specific_pstatuses($what,$reverse);
    if (count($psarr)==1) return " pstatus_id='".$psarr[0]."' ";
    else {
        return " pstatus_id IN (".implode(", ",$psarr).") ";
        //$check_statuses_query=array();
        //foreach ($psarr as $cs) $check_statuses_query[]=" pstatus_id='".$cs."' ";
        //$snippet=" (".implode(" OR ",$check_statuses_query).") ";
        //return $snippet;
    }
}

?>
