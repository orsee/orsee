<?php
// part of orsee. see orsee.org
ob_start();
include ("cronheader.php");

$continue=true; $all=false;

if(!isset($_REQUEST['cal'])) { 
    $continue=false; 
    $message="no token"; 
}

if ($continue) {
    $caltype=substr($_REQUEST['cal'],0,1);
    $token=substr($_REQUEST['cal'],1);
    if ($caltype=='a') {
        $all=true;
    } elseif ($caltype=='p') {
        $all=false;
    } else { 
        $continue=false; 
        $message="cal type not allowed"; 
    }
}

if ($continue) {
    $expadmindata=calendar__get_user_for_ics_token($token);
    if (is_array($expadmindata)) {
        $expadmindata['rights']=admin__load_admin_rights($expadmindata['admin_type']);
        if (check_allow('login') && $expadmindata['disabled']!='y'
            && (check_allow('calendar_export_my') || check_allow('calendar_export_all'))
        ) {
            if ($all==true && !check_allow('calendar_export_all')) {
                $all=false;
            }
        } else { 
            $continue=false; 
            $message="no rights to export"; 
        }
    } else { 
        $continue=false; 
        $message="invalid token"; 
    }
}


if ($continue) {
    $labs=laboratories__get_laboratories();
    $laboratory_id=false;
    if(isset($_REQUEST['lab_id']) && $_REQUEST['lab_id']) {
        if(isset($labs[$_REQUEST['lab_id']])) {
            $laboratory_id=$_REQUEST['lab_id'];
        }
    }

    $displayfrom_lower = time()-60*60*24*31*$settings['calendar_export_months_back'];
    $displayfrom_upper = time()+60*60*24*31*$settings['calendar_export_months_ahead'];

    if ($all) {
        $expadminid=false; 
    } else {
        $expadminid=$expadmindata['admin_id'];
    }
    $results = calendar__get_events(true, $displayfrom_lower, $displayfrom_upper,$expadminid, false, $laboratory_id);


    if (isset($_REQUEST['dispout'])) {
        header('Content-Type: text/plain');
    } else {
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . time() . "downlaod.ics");
    }


    ///Start echoing the file out
    echo 'BEGIN:VCALENDAR' . "\r\n";
    echo 'VERSION:2.0' . "\r\n";
    echo 'PRODID:-//hacksw/handcal//NONSGML v1.0//EN' . "\r\n";
    echo 'CALSCALE:GREGORIAN' . "\r\n";
    echo 'BEGIN:VTIMEZONE' . "\r\n";
    echo 'TZID:UTC' . "\r\n";
    echo 'BEGIN:STANDARD' . "\r\n";
    echo 'DTSTART:19700101T000000' . "\r\n";
    echo 'RDATE:19700101T000000' . "\r\n";
    echo 'TZOFFSETFROM:-0000' . "\r\n";
    echo 'TZOFFSETTO:-0000' . "\r\n";
    echo 'TZNAME:UTC' . "\r\n";
    echo 'END:STANDARD' . "\r\n";
    echo 'END:VTIMEZONE' . "\r\n";
    foreach($results as $day) {
        foreach($day as $item) {
            $description='';
            $description.=experiment__list_experimenters($item['experimenters'],false,true).'\n';
            if($item['type'] == "location_reserved") {
                if (check_allow('events_edit')) {
                    $item['title_link']=$item['edit_link'];
                }
            } elseif($item['type'] == "experiment_session") {
                $description.=$item['participants_registered'] . " (" . $item['participants_needed']. "," . $item['participants_reserve'] . ")".'\n';
            }
            $description=trim($description);
            echo 'BEGIN:VEVENT' . "\r\n";
            echo 'DTEND:' . calendar__unixtime_to_ical_date($item['end_time']) . "\r\n";
            echo 'UID:' . calendar__escapestring($item['uid']) . "\r\n";
            echo 'DTSTAMP:' . calendar__unixtime_to_ical_date(time()) . "\r\n";
            echo 'DTSTART:' . calendar__unixtime_to_ical_date($item['start_time']) . "\r\n";
            echo 'LOCATION:' . calendar__escapestring($item['location']) . "\r\n";
            echo 'SUMMARY:' . calendar__escapestring($item['title']) . "\r\n";
            if(isset($item['title_link'])){
                echo 'URL:' . $item['title_link'] . "\r\n";
            }
            echo 'DESCRIPTION:' . calendar__escapestring($description). "\r\n";;
            echo 'END:VEVENT' . "\r\n";
        }
    }
    echo 'END:VCALENDAR';
} else {
    header("HTTP/1.0 404 Not Found", true, 404);
    die();
}
?>