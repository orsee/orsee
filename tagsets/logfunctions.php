<?php
// part of orsee. see orsee.org


function log__participant($action,$participant_id,$target="") {
    $darr=getdate();
    $pars=array(':participant_id'=>$participant_id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('participants_log')."
            SET id= :participant_id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
}

function log__admin($action="unknown",$target="") {
    global $expadmindata;
    if (isset($expadmindata['admin_id'])) $admin_id= $expadmindata['admin_id'];
    else $admin_id='system';
    $darr=getdate();
    $pars=array(':admin_id'=>$admin_id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('admin_log')."
            SET id= :admin_id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
}

function log__cron_job($action="unknown",$target="",$now="",$id="") {
    if ($now=="") $now=time();
    $darr=getdate($now);

    $pars=array(':id'=>$id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('cron_log')."
            SET id= :id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
    return $done;
}


function log__link() {
    $post=$_REQUEST;
    unset($post['SID']); unset ($post['PHPSESSID']);
    $arg_list=func_get_args();
    foreach ($arg_list as $arg) {
        $var=explode("=",$arg);
        $post[$var[0]]=$var[1];
        }
    $link='<A HREF="'.thisdoc().'?';
    foreach ($post as $key=>$value) {
        $link.=$key.'='.urlencode($value).'&';
        }
    $link.='">';
    return $link;
}

function log__restrict_link($varname,$varvalue) {
    global $lang;
    $link=log__link($varname.'='.$varvalue,'os=0');
    $link.='<FONT class="small">['.lang('restrict').']</FONT></A>';
    return $link;
}

function log__show_log($log) {
    global $limit;

    if (!$limit) $limit=50;
    if (isset($_REQUEST['os']) && $_REQUEST['os']>0) $offset=$_REQUEST['os']; else $offset=0;

    global $lang, $color;

    $pars=array();

    if (isset($_REQUEST['action']) && $_REQUEST['action']) {
        $aquery=" AND action=:action ";
        $pars[':action']=$_REQUEST['action'];
    } else $aquery="";

    if (isset($_REQUEST['id']) && $_REQUEST['id']) {
        $idquery=" AND id=:id ";
        $pars[':id']=$_REQUEST['id'];
    } else $idquery="";

    if (isset($_REQUEST['target']) && $_REQUEST['target']) {
        $tquery=" AND target LIKE :target ";
        $pars[':target']='%'.$_REQUEST['target'].'%';
    } else $tquery="";

    $logtable=table('participants_log');
    switch ($log) {
        case "participant_actions":
            $logtable=table('participants_log');
            $secondtable=" LEFT JOIN ".table('participants')." ON id=participant_id ";
            break;
        case "experimenter_actions":
            $logtable=table('admin_log');
            $secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
            break;
        case "regular_tasks":
            $logtable=table('cron_log');
            $secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
            break;
    }

    if (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['days']) && $_REQUEST['days']) {

        $allow=check_allow('log_file_'.$log.'_delete','statistics_show_log.php?log='.$log);
        if (isset($_REQUEST['days']) && $_REQUEST['days']=="all") $where_clause="";
        else {
            $now=time();
            $dsec= (int) $_REQUEST['days']*24*60*60;
            $dtime=$now-$dsec;
            $where_clause=" WHERE timestamp < ".$dtime;
        }
        $query="DELETE FROM ".$logtable.$where_clause;
        $done=or_query($query);
        $number=pdo_num_rows($done);
        message ($number.' '.lang('xxx_log_entries_deleted'));
        if ($number>0) log__admin("log_delete_entries","log:".$log."\ndays:".$_REQUEST['days']);
        redirect ("admin/statistics_show_log.php?log=".$log);
    }


    $pars[':offset']=$offset;
    $pars[':limit']=$limit;
    $query="SELECT * FROM ".$logtable.$secondtable."
        WHERE id IS NOT NULL ".
        $aquery.$idquery.$tquery.
        " ORDER BY timestamp DESC, log_id DESC 
        LIMIT :offset , :limit ";
    $result=or_query($query,$pars);
    $num_rows=pdo_num_rows($result);

    echo '<TABLE width=80% border=0>
        <TR><TD width=50%>';
    //echo '<FONT class="small">'.lang('query').': '.$query.'</FONT><BR><BR>';
    echo '</TD>
        <TD align=right width=50%>';

    if (check_allow('log_file_'.$log.'_delete')) {
        echo '
            <FORM action="statistics_show_log.php">
            <INPUT type=hidden name="log" value="'.$log.'">
            '.lang('delete_log_entries_older_than').'
            <select name="days">
            <option value="all">'.lang('all_entries').'</option>';
        $ddays=array(1,7,30,90,180,360);
        if (isset($_REQUEST['days']) && $_REQUEST['days']) $selected=$_REQUEST['days']; else $selected=90;
        foreach ($ddays as $day) {
            echo '<option value="'.$day.'"';
            if ($day==$selected) echo ' SELECTED';
            echo '>'.$day.' ';
            if ($day==1) echo lang('day'); else echo lang('days');
            echo '</option>
                ';
        }
        echo '  </select><input type=submit name="delete" value="'.lang('delete').'">';
    }
    echo '</TD></TR></TABLE>';

    if ($offset > 0) echo '['.log__link('os='.($offset-$limit)).lang('previous').'</A>]';
    else echo '['.lang('previous').']';
    echo '&nbsp;&nbsp;';
    if ($num_rows >= $limit) echo '['.log__link('os='.($offset+$limit)).lang('next').'</A>]';
    else echo '['.lang('next').']';

    echo '<TABLE class="or_listtable" style="width: 90%;">';

    // header
    echo '<thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
        <TD>
            '.lang('date_and_time').'
        </TD>
        <TD>';
    if ($log=='participant_actions') {
        echo lang('lastname').', '.lang('firstname');
    } elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
        echo lang('experimenter');
    }
    if (isset($_REQUEST['id']) && $_REQUEST['id'])
        echo ' '.log__link('id=','os=0').'<FONT class="small">['.lang('unrestrict').']</FONT></A>';
    echo '  </TD><TD>'.lang('action');
    if (isset($_REQUEST['action']) && $_REQUEST['action'])
        echo ' '.log__link('action=','os=0').'<FONT class="small">['.lang('unrestrict').']</FONT></A>';
    echo '  </TD><TD>'.lang('target');
    if (isset($_REQUEST['target']) && $_REQUEST['target'])
        echo ' '.log__link('target=','os=0').'<FONT class="small">['.lang('unrestrict').']</FONT></A>';
    echo '  </TD></TR>
            </thead>
            <tbody>';

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '<tr class="small"';
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
            else echo 'bgcolor="'.$color['list_shade2'].'"';
        echo '>
            <TD>'.ortime__format($line['timestamp'],'hide_seconds:false',lang('lang')).'</TD>
            <TD>';
        if ($log=='participant_actions') {
            if ($line['participant_id']) {
                echo $line['lname'].', '.$line['fname'].' <A HREF="participants_edit.php?participant_id='.
                $line['participant_id'].'"><FONT class="small">['.lang('edit').']</FONT></A>';
            } else echo $line['id'];
        } elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
            echo $line['adminname'];
        }
        if (!isset($_REQUEST['id']) || $_REQUEST['id']!=$line['id']) echo ' '.log__restrict_link('id',$line['id']);
        echo '  </TD><TD>'.$line['action'];
        if (!isset($_REQUEST['action']) || $_REQUEST['action']!=$line['action'])
            echo ' '.log__restrict_link('action',$line['action']);
        echo '  </TD><TD>'.nl2br(stripslashes($line['target']));
        if (!isset($_REQUEST['target']) || $_REQUEST['target']!=$line['target'] && $log!='regular_tasks')
            echo ' '.log__restrict_link('target',$line['target']);
        echo '  </TD></TR>';
        if ($shade) $shade=false; else $shade=true;
    }
    echo '</tbody></TABLE>';
    return $num_rows;
}

?>
