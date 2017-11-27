<?php
// part of orsee. see orsee.org


//returns an array of every day of the month. first dimension
//represents the week, second dimension represents the weekday
function days_in_month($month, $year){
    global $lang;
    $dates = array();
    $time = mktime(0,0,0,$month,1,$year);
    if (!isset($lang['format_datetime_firstdayofweek_0:Su_1:Mo']) || (!$lang['format_datetime_firstdayofweek_0:Su_1:Mo'])) {
        $firstdayofweek = 7;
    } else {
        $firstdayofweek = 1;
    }
    $firstdays = 1;
    for($i = 1; $i <= date("t", $time); $i++){
        $time = mktime(0,0,0,$month,$i,$year);
        //subtract 'weeks up to this month' from 'weeks up to
        //previous month' to get the number of weeks in this month
        //$weekNum = date("W", $time) - date("W",  strtotime(date("Y-m-01", $time))) + 1;
        //$weekNum = date("W", $time) - date("W", strtotime(date("Y-m-01", $time))) + 1;

        //count the number of firstdays( Weeks )
        if(date('N', $time) == $firstdayofweek && $i == 1){
            $firstdays = 0;
        }
        if(date('N', $time) == $firstdayofweek){
            $firstdays++;
        }
        $day = date('d', $time);
        $dayOfWeek = date('N', $time);
        if ($firstdayofweek==7) $dayOfWeek++;
        if ($dayOfWeek>7) $dayOfWeek=$dayOfWeek-7;
        $dates[$firstdays][$dayOfWeek] = $day;
    }
    return $dates;
}

function date__skip_months($count,$time=0) {
    if ($time==0) $time=time();
    $td=getdate($time);
    $tmonth=$td['mon']+$count;
    $newtimestamp=mktime(0,0,1,$tmonth,1,$td['year']);
    return $newtimestamp;
}

function date__skip_years($count,$time=0) {
    if ($time==0) $time=time();
    $td=getdate($time);
    $newyear=$td['year']+$count;
    $newtimestamp=mktime(0,0,1,1,1,$newyear);
    return $newtimestamp;
}

function date__year_start_time($time=0) {
    if ($time==0) $time=time();
    $td=getdate($time);
    $newtimestamp=mktime(0,0,1,1,1,$td['year']);
    return $newtimestamp;
}

function calendar__days_in_month($month, $year){
    // calculate number of days in a month
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}


function calendar__get_events($admin = false, $start_time = 0, $end_time = 0, $admin_id = false, $split_events=false, $laboratory_id=false){
    $events = array();
    global $lang, $settings, $settings__root_url, $color;

    $labs=laboratories__get_laboratories();
    $sessions=array(); $signed_up=array(); $lines=array();

    //build query to get all sessions
    $query="SELECT * FROM ".table('sessions').", ".table('experiments').
            " WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id";
    //don't include hidden if not admin
    if (!$admin) {
        $query.=" AND ".table('experiments').".hide_in_cal='n' ";
    }
    // don't include planned sessions if not admin and setting is disabled
    if (!$admin & $settings['hide_planned_sessions_in_public_calendar']=='y') {
        $query.=" AND ".table('sessions').".session_status!='planned' ";
    }
    //only events between start and end time parameters
    $pars=array(':end_time'=>date("Ym320000", $end_time), // lowerr than "32nd day" of end time month
                ':start_time'=>date("Ym000000", $start_time)); // larger than "0st day" of start time month
    $query .= " AND session_start <= :end_time ";
    $query .= " AND session_start >= :start_time ";
    if ($admin_id) {
        $query.=" AND ".table('experiments').".experimenter LIKE :admin_id ";
        $pars[':admin_id']='%|'.$admin_id.'|%';
    }
    if ($laboratory_id) {
        $query.=" AND ".table('sessions').".laboratory_id=:laboratory_id ";
        $pars[':laboratory_id']=$laboratory_id;
    }

    $result=or_query($query,$pars);
    $exp_colors = array();
    $exp_colors_used = 0;
    $exp_colors_defined_list=explode(",",$color['calendar_public_experiment_sessions']);
    while ($line=pdo_fetch_assoc($result)) {
        $lines[]=$line;
        $sessions[]=$line['session_id'];
    }

    if(count($sessions)>0) {
        $query="SELECT session_id, COUNT(*) as regcount FROM ".table('participate_at')."
                WHERE session_id IN (".implode(",",$sessions).")
                GROUP BY session_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $signed_up[$line['session_id']]=$line['regcount'];
        }
    }

    foreach ($lines as $line) {
        $tmp_new_event = array();
        //get colour
        if(!isset($exp_colors[$line['experiment_id']])){
            $exp_colors[$line['experiment_id']] = $exp_colors_defined_list[$exp_colors_used];
            $exp_colors_used +=1;
            if($exp_colors_used > count($exp_colors_defined_list)-1 ){
                $exp_colors_used = 0;
            }
        }
        $tmp_new_event['color'] = $exp_colors[$line['experiment_id']];
        //convert to unix time
        $unix_time = ortime__sesstime_to_unixtime($line['session_start']);
        $tmp_new_event['start_time'] = $unix_time;
        //add duration to start time to create end time (in seconds)
        $duration = (($line['session_duration_hour'] * 60) + $line['session_duration_minute']) * 60;
        $tmp_new_event['end_time'] = $unix_time + $duration;
        //formatted time with language features
        $tmp_new_event['display_time'] = ortime__format($unix_time,'hide_date:true,hide_second:true',$lang['lang']).'-'.
                        ortime__format($unix_time + $duration,'hide_date:true,hide_second:true',$lang['lang']);
        if ($admin) {
            $tmp_new_event['title'] = $line['experiment_name'];
        } else {
            $tmp_new_event['title'] = $line['experiment_public_name'];
        }
        if(check_allow('experiment_show')){
            $tmp_new_event['title_link'] = $settings__root_url.'/admin/experiment_show.php?experiment_id='.$line['experiment_id'];
        }
        $tmp_new_event['participants_link'] = $settings__root_url.'/admin/experiment_participants_show.php?experiment_id=' . $line['experiment_id'] . '&session_id=' . $line['session_id'];
        if (isset($labs[$line['laboratory_id']]['lab_name'])) {
            $tmp_new_event['location'] = $labs[$line['laboratory_id']]['lab_name'];
        } else {
            $tmp_new_event['location'] = lang('unknown_laboratory');
        }
        if (isset($signed_up[$line['session_id']])) $participating = $signed_up[$line['session_id']];
        else $participating=0;
        $tmp_new_event['participants_needed'] = $line['part_needed'];
        $tmp_new_event['participants_reserve'] = $line['part_reserve'];
        $tmp_new_event['participants_registered'] = $participating;
        //uid (unique identifier) for use by ICS
        $tmp_new_event['uid'] = "session_" . $line['session_id'] . "@" .  $settings__root_url;
        $tmp_new_event['type'] = "experiment_session";
        if($participating < $line['part_needed']){
            $tmp_new_event['status'] = "not_enough_participants";
        }elseif($participating < ($line['part_needed'] + $line['part_reserve'])){
            $tmp_new_event['status'] = "not_enough_reserve";
        }else{
            $tmp_new_event['status'] = "complete";
        }
        $tmp_new_event['experimenters'] = $line['experimenter'];

        $tmp_new_event['id'] = $line['session_id'];

        $events[date("Y",$tmp_new_event['start_time'])*10000+date("n",$tmp_new_event['start_time'])*100+date("j",$tmp_new_event['start_time'])][] = $tmp_new_event;
    }

    //non-experimental laboratory booking events
    $event_categories=lang__load_lang_cat('events_category');
    $pars=array(':end_time'=>date("Ym320000", $end_time), // lowerr than "32nd day" of end time month
                ':start_time'=>date("Ym000000", $start_time)); // larger than "0st day" of start time month
    $query = "SELECT *  FROM ".table('events').
            " WHERE event_start <= :end_time
              AND event_stop >= :start_time";
    if ($admin_id) {
        $query.=" AND ".table('events').".experimenter LIKE :admin_id ";
        $pars[':admin_id']='%|'.$admin_id.'|%';
    }
    if ($laboratory_id) {
        $query.=" AND ".table('events').".laboratory_id=:laboratory_id ";
        $pars[':laboratory_id']=$laboratory_id;
    }
    $result=or_query($query,$pars);
    $exp_colors = array();
    $exp_colors_used = 0;
    $exp_colors_defined_list=explode(",",$color['calendar_event_reservation']);
    while ($line=pdo_fetch_assoc($result)){
        if($admin || trim($line['reason_public'])) {
            //get color
            if(!isset($exp_colors[$line['laboratory_id']])){
                $exp_colors[$line['laboratory_id']] = $exp_colors_defined_list[$exp_colors_used];
                $exp_colors_used +=1;
                if($exp_colors_used > count($exp_colors_defined_list)-1 ){
                    $exp_colors_used = 0;
                }
            }
            $tmp_new_event = array();
            $tmp_new_event['color'] = $exp_colors[$line['laboratory_id']];
            $unix_start_time = ortime__sesstime_to_unixtime($line['event_start']);
            $unix_stop_time = ortime__sesstime_to_unixtime($line['event_stop']);
            $tmp_new_event['start_time'] = $unix_start_time;
            $tmp_new_event['end_time'] = $unix_stop_time;
            $tmp_new_event['display_time'] = ortime__format($unix_start_time,'hide_second:true',$lang['lang']).'-'.
                        ortime__format($unix_stop_time,'hide_second:true',$lang['lang']);
            if (isset($labs[$line['laboratory_id']]['lab_name'])) {
                $tmp_new_event['location'] = $labs[$line['laboratory_id']]['lab_name'];
            } else {
                $tmp_new_event['location'] = $lang['unknown_laboratory'];
            }
            $tmp_new_event['type'] = "location_reserved";
            if ($admin) {
                $tmp_new_event['title'] = $line['reason'];
                if (trim($line['reason_public'])) $tmp_new_event['title'] .= ' ('.$line['reason_public'].')';
                if ($line['event_category'] && isset($event_categories[$line['event_category']])) {
                    $tmp=$event_categories[$line['event_category']];
                    if ($tmp_new_event['title']) $tmp.=", ".$tmp_new_event['title'];
                    $tmp_new_event['title']=$tmp;
                }
                if(!$tmp_new_event['title']){
                    $tmp_new_event['title'] = lang('laboratory_booked');
                }
            } else {
                $tmp_new_event['title'] = $line['reason_public'];
                if(!$tmp_new_event['title']){
                    $tmp_new_event['title'] = lang('laboratory_booked');
                }
            }
            $tmp_new_event['edit_link'] = $settings__root_url."/admin/events_edit.php?event_id=" . $line['event_id'];
            $tmp_new_event['experimenters'] = $line['experimenter'];
            $tmp_new_event['id'] = $line['event_id'];
            $tmp_new_event['uid'] = "booking_" . $line['event_id'] . "@" .  $settings__root_url;
            if ($split_events) {
                $continue=true; $today=$unix_start_time;
                while($continue) {
                    if (date("Ymd",$today)==date("Ymd",$unix_start_time)) $tmp_new_event['start_time']=$unix_start_time;
                    else $tmp_new_event['start_time']=mktime($settings['laboratory_opening_time_hour'],$settings['laboratory_opening_time_minute'],0,date("n",$today),date("j",$today),date("Y",$today));
                    if (date("Ymd",$today)>=date("Ymd",$unix_stop_time)) {
                        $tmp_new_event['end_time']=$unix_stop_time;
                        $continue=false;
                    } else $tmp_new_event['end_time']=mktime($settings['laboratory_closing_time_hour'],$settings['laboratory_closing_time_minute'],0,date("n",$today),date("j",$today),date("Y",$today));
                    $tmp_new_event['display_time'] = ortime__format($tmp_new_event['start_time'],'hide_date:true,hide_second:true',lang('lang')).'-'.
                                            ortime__format($tmp_new_event['end_time'],'hide_date:true,hide_second:true',lang('lang'));
                    $events[date("Y",$today)*10000+date("n",$today)*100+date("j",$today)][] = $tmp_new_event;
                    $today=strtotime("+1 day", $today);
                }
            } else {
                $events[date("Y",$tmp_new_event['start_time'])*10000+date("n",$tmp_new_event['start_time'])*100+date("j",$tmp_new_event['start_time'])][] = $tmp_new_event;
            }
        }
    }

    return $events;
}

function calendar__display_calendar($admin = false){
    global $lang, $color, $settings;
    $displayfrom = time();
    if(isset($_REQUEST['displayfrom'])){
        $displayfrom = $_REQUEST['displayfrom'];
    }
    $wholeyear = false;
    if(isset($_REQUEST['wholeyear']) && $admin){
        $wholeyear = true;
    }
    $labid_urlstring = ''; $laboratory_id=false;
    $labs=laboratories__get_laboratories();
    if(isset($_REQUEST['laboratory_id']) && $_REQUEST['laboratory_id'] && $admin) {
        if(isset($labs[$_REQUEST['laboratory_id']])) {
            $laboratory_id=$_REQUEST['laboratory_id'];
            $labid_urlstring = "laboratory_id=".urlencode($laboratory_id);
        }
    }
    //$monthsum format: years x 12 + months
    $calendar_month_font = "white";
    if(isset($color['calendar_month_font'])) $calendar_month_font = $color['calendar_month_font'];
    $calendar_day_background =  "white";
    if(isset($color['calendar_day_background'])) $calendar_day_background = $color['calendar_day_background'];
    $calendar_month_background =  "black";
    if(isset($color['calendar_month_background'])) $calendar_month_background = $color['calendar_month_background'];
    echo '
    <style>
        #calendarContainer {
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        .calendarTable {
            border: 0px;
            border-collapse: separate;
        }

        /* head of calendar */
        .calendarTable thead  {
            background: '.$calendar_month_background.';
            color: '.$calendar_month_font.';
        }
        .calendarTable>thead>tr>th {
            border: 0;
            border-bottom: 3px solid #E2E2E2;
            height: 20px;
            text-align: right;
            font-weight: 600;
            padding: 0px 10px 0px 0px;
        }
        .calendarTable>thead>tr>th.monthTag{
            font-size: 13pt;
            height: 30px;
        }
        /* round corners */
        .calendarTable>thead>tr:first-child>th:only-child {
            -moz-border-radius: 10px 10px 0px 0px;
            -webkit-border-radius: 10px 10px 0px 0px;
            border-radius: 10px 10px 0px 0px;
        }

        /* calendar rows and cells*/
        .calendarTable>tbody>tr {
        }
        .calendarTable>tbody>tr>td {
            border: 1px solid #C5C5C5;
            padding: 0;
            margin: 0;
            height: 100px;
            min-width: 3%;
            width: 3%;
            max-width: 30%;
            text-align: left;
            vertical-align: top;
        }
        .calendarTable>tbody>tr .calendarCellRealDate{
            border: 2px solid #C5C5C5;
        }
        .calendarTable>tbody>tr>td .calendarCellHead {
            padding: 0;
            padding-left: 3px;
            padding-right: 10px;
            margin: 0;
            background: '.$calendar_day_background.';
            text-align: right;
            height: 17px;
            font-weight: bold;
        }
        .calendarTable>tbody>tr>td .calendarCellContent {
            padding: 0;
            padding-top: 3px;
            padding-bottom: 3px;
            padding-left: 6px;
            padding-right: 15px;
            position: relative;
            margin-left: 5px;
            margin-right: 5px;
            margin-top: 3px;
            margin-bottom: 3px;
            -moz-border-radius: 5px 20px 5px 5px;
            -webkit-border-radius: 5px 20px 5px 5px;
            border-radius: 5px 20px 5px 5px;
        }
        .calendarTable>tbody>tr>td .calendarCellContent .calendarCellContentTitle {
            display: block;
        }
        .calendarTable>tbody>tr>td .calendarCellContent span {
            display: block;
        }

        /* round corners */
        .calendarTable>tbody>tr:last-child>td:first-child {
            -moz-border-radius: 0px 0px 0px 10px;
            -webkit-border-radius: 0px 0px 0px 10px;
            border-radius: 0px 0px 0px 10px;
        }

        .calendarTable>tbody>tr:last-child>td:last-child {
             -moz-border-radius: 0px 0px 10px 0px;
             -webkit-border-radius: 0px 0px 10px 0px;
             border-radius: 0px 0px 10px 0px;
        }

        /* highlight today cell */
        .calendarTable>tbody>tr>td.today {
            border: 2px solid #F00;
        }

    </style>
    ';

    $statusdata = array("not_enough_participants" => array(
            "color" => ($admin) ? $color['session_not_enough_participants'] : $color['session_public_free_places'],
            "message" => ($admin) ? $lang["not_enough_participants"] : lang('free_places')
        ),
        "not_enough_reserve" => array(
            "color" => ($admin) ? $color['session_not_enough_reserve'] : $color['session_public_free_places'],
            "message" => ($admin) ? $lang["not_enough_reserve"] : lang('free_places')
        ),
        "complete" => array(
            "color" => ($admin) ? $color['session_complete'] : $color['session_public_complete'],
            "message" => $lang["complete"]
        )
    );

    echo '<div id="calendarContainer">';

    //start building calendar
    $displayfrom_lower = $displayfrom;
    $displayfrom_upper = date__skip_months(1, $displayfrom_lower);
    if($wholeyear && $admin){
        $displayfrom_upper = mktime(0, 0, 0, 1, 1, date('Y', $displayfrom)+1);
    }
    $results = calendar__get_events($admin, $displayfrom_lower, $displayfrom_upper, false, true, $laboratory_id);
    $buttons1="";
    $buttons2="";

    if ($admin) {
        $buttons1.="<TABLE border=0 width=100%>";
        $buttons1.='<TR>';
        if ($wholeyear) {
            $buttons1.='<TD align="left">'.button_link("?".$labid_urlstring,lang('current_month'),'','font-size: 8pt;').'</TD>';
        } else {
            $buttons1.='<TD align="left">'.button_link("?wholeyear=true&displayfrom=" . mktime(0, 0, 0, 1, 1, date('Y', $displayfrom))."&".$labid_urlstring,lang('whole_year'),'','font-size: 8pt;').'</TD>';
        }
        $buttons1.='<TD align="center">'.button_link('events_edit.php',lang('create_event'),'plus-circle').'<BR>
                <FONT class="small">'.lang('for_session_time_reservation_please_use_experiments').'</FONT></TD>';
        $buttons1.='<TD align="right">'.button_link('calendar_main_print_pdf.php?displayfrom='.$displayfrom.'&wholeyear='.$wholeyear."&".$labid_urlstring,
                    lang('print_version'),'print','font-size: 8pt;','target="_blank"').'</TD>';
        $buttons1.='</TR></TABLE>';
    }
    $buttons2 .= '<TABLE width="100%"><TR><TD colspan=2 align="left">';
    $buttons2 .= button_link("?displayfrom=".date__skip_months(-1, $displayfrom)."&".$labid_urlstring, strtoupper(lang('previous')),'caret-square-o-up','font-size: 8pt;');
    $buttons2 .= '</TD>';
    $buttons2 .= '<TD colspan=3 align="center">';
    if (count($labs)>1 && $admin) {
        $lab_links=array();
        $lab_links[]='<A HREF="calendar_main.php?displayfrom='.$displayfrom.'&wholeyear='.$wholeyear.'&laboratory_id=">'.lang('select_all').'</A>';
        foreach ($labs as $lab_id=>$lab) {
            $lab_links[]='<A HREF="calendar_main.php?displayfrom='.$displayfrom.'&wholeyear='.$wholeyear.'&laboratory_id='.urlencode($lab_id).'">'.$lab['lab_name'].'</A>';
        }
        $buttons2 .= lang('laboratories').': '.implode("&nbsp;|&nbsp;",$lab_links);
    }
    $buttons2 .= '</TD>';
    $buttons2 .= '<TD colspan=2 align="right">';
    $buttons2 .= button_link("?displayfrom=".date__skip_months(1, $displayfrom)."&".$labid_urlstring,strtoupper(lang('next')),'caret-square-o-down','font-size: 8pt;');
    $buttons2 .= '</TD></TR></TABLE><BR>';

    echo $buttons1;
    echo $buttons2;
    $month_names=explode(",",$lang['month_names']);
    //loop through each month
    for($itime = $displayfrom_lower; $itime <= $displayfrom_upper; $itime = date__skip_months(1, $itime)){
        $year = date("Y", $itime); $month = date("m", $itime);
        $weeks = days_in_month($month, $year);
        echo '<TABLE class="calendarTable" WIDTH="100%">';
        echo '<thead><tr>';
        echo '<th colspan="7" class="monthTag"><center>' . $month_names[($month-1)] . ' ' . $year . '</center></td></tr><tr>';

            $calendar__weekdays=explode(",",$lang['format_datetime_weekday_abbr']);
            for ($i3 = 1; $i3 <= 7; ++$i3) {
                if (!isset($lang['format_datetime_firstdayofweek_0:Su_1:Mo']) || (!$lang['format_datetime_firstdayofweek_0:Su_1:Mo'])) {
                    $wdindex = $i3-1;
                } else {
                    $wdindex = $i3;
                    if ($wdindex==7) $wdindex=0;
                }
                echo '<th>' . $calendar__weekdays[$wdindex] . '</th>';
            }
        echo '</tr></thead>';
        echo '<tbody>';
        for($i2 = 1; $i2 <= count($weeks); ++$i2){
            echo '<tr>';
            for ($i3 = 1; $i3 <= 7; ++$i3){
                if(isset($weeks[$i2][$i3])){
                    //the date is the key of the $results array for easy searching
                    $today = $year*10000+$month*100+$weeks[$i2][$i3];
                    $realtoday = date("Y")*10000+date("m")*100+date("d");
                    echo '<td class="calendarCellRealDate';
                    if ($today==$realtoday) echo ' today';
                    echo '">';
                    echo '<div class="calendarCellHead">';
                    echo $weeks[$i2][$i3];
                    echo '</div>';
                    if(isset($results[$today])){
                        foreach($results[$today] as $item){
                            $title = $item['title'];
                            if(isset($item['title_link'])){
                                $title = '<a href="' . $item['title_link'] . '">' . $title . '</a>';
                            }
                            echo '<div style="background: ' . $item['color'] . ';" class="calendarCellContent">';
                            echo '<span style="font-weight: bold;">';
                            echo $item['display_time'];
                            echo '</span>';
                            echo '<span style="font-size: 11;">';
                            echo $item['location'];
                            echo '</span>';
                            if($admin || $settings['public_calendar_hide_exp_name']!='y'){
                                echo '<div class="calendarCellContentTitle">' . $title . '</div>';
                            } else {
                                echo '<div class="calendarCellContentTitle">'.lang('calendar_experiment_session').'</div>';
                            }

                            if($admin){
                                echo '<span style="font-size: 11;">';
                                echo experiment__list_experimenters($item['experimenters'],true,true);
                                echo '</span>';
                            }
                            if($item['type'] == "location_reserved"){
                                echo '<span>';
                                if(check_allow('events_edit')){
                                    echo '<a style="font-size: 11;" href="' . $item['edit_link'] . '">[' . lang('edit') . ']</a>';
                                }
                                echo '</span>';

                            }elseif($item['type'] == "experiment_session"){

                                echo '<span style="color: ' . $statusdata[$item['status']]['color'] . ';">';

                                    if($admin){
                                        echo " " . $item['participants_registered'] . " (" . $item['participants_needed']. "," . $item['participants_reserve'] . ")";
                                    } else {
                                        echo $statusdata[$item['status']]['message'];
                                    }
                                echo '</span>';
                                if($admin && check_allow('experiment_show_participants')){
                                    echo '<span>';
                                    echo '<a style="font-size: 11;" href="' . $item['participants_link'] . '">[' . lang('participants') . ']</a>';
                                    echo '</span>';
                                }
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<td>&nbsp;';
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></TABLE><br /><br /><br />';
    }
    echo $buttons2;
    //echo $buttons1;
    echo '</div>';
}


/// ics export functions

function calendar__gen_ics_token($admin_id,$password){
    return md5($admin_id."|-|".$password);
}

function calendar__get_user_for_ics_token($icstoken){
    $pars=array(':icstoken'=>$icstoken);
    $query="SELECT * FROM ".table('admin').
           " WHERE MD5(concat(admin_id,'|-|',password_crypt))=:icstoken";
    $result=or_query($query,$pars);
    $admin=false;
    while ($line = pdo_fetch_assoc($result)) {
        $admin = $line;
    }
    return $admin;
}

function calendar__unixtime_to_ical_date($timestamp) {
    $current_time_zone=date_default_timezone_get();
    $done=date_default_timezone_set('UTC');
    $thisdate=date('Ymd\THis\Z', $timestamp);
    $done=date_default_timezone_set($current_time_zone);
    return $thisdate;
}

function calendar__escapestring($string) {
  return preg_replace('/([\,;])/','\\\$1', $string);
}


?>
