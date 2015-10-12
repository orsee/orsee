<?php
// part of orsee. see orsee.org

function pdfoutput__make_part_list($experiment_id,$session_id="",$pstatus="",$focus="",$sort="",$file=false,$tlang="") {
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

    // determine table headings

    $table_headings=participant__get_result_table_headcells_pdf($cols);
    $table_data=array();

    $pnr=0;
    foreach ($participants as $p) {
        $pnr++;
        $p['order_number']=$pnr;
        $row=participant__get_result_table_row_pdf($cols,$p);
        $table_data[]=$row;
    }

    // prepare pdf
    include_once('../tagsets/class.ezpdf.php');

    $pdf = new Cezpdf('a4','landscape');

    $pdf->selectFont('../tagsets/fonts/Times-Roman.afm');

    $fontsize= ($settings['participant_list_pdf_table_fontsize']) ? $settings['participant_list_pdf_table_fontsize'] : 10;
    $titlefontsize= ($settings['participant_list_pdf_title_fontsize']) ? $settings['participant_list_pdf_title_fontsize'] : 12;

    $y=$pdf->ezTable($table_data,
                    $table_headings,
                    $table_title,
                    array(  'gridlines'=>31,
                            'showHeadings'=>1,
                            'shaded'=>2,
                            'shadeCol'=>array(1,1,1),
                            'shadeCol2'=>array(0.9,0.9,0.9),
                            'fontSize'=>$fontsize,
                            'titleFontSize'=>$titlefontsize,
                            'rowGap'=>1,
                            'colGap'=>3,
                            'innerLineThickness'=>0.5,
                            'outerLineThickness'=>1,
                            'maxWidth'=>800,
                            'width'=>800,
                            'protectRows'=>2));


    if ($file) {
        $pdffilecode = $pdf->output();
        return $pdffilecode;
    } else {
        $pdf->ezStream(array('Content-Disposition'=>'participant_list.pdf',
                            'Accept-Ranges'=>0,
                            'compress'=>1));
    }

}


function pdfoutput__make_pdf_calendar($displayfrom=0,$wholeyear=false,$admin=false,$forward=0,$file=false){
    global $settings, $lang;

    if ($displayfrom==0) $displayfrom=time();

    // prepare pdf
    include_once('../tagsets/class.ezpdf.php');

    $pdf = new Cezpdf('a4');

    $pdf->selectFont('../tagsets/fonts/Times-Roman.afm');

    $fontsize= ($settings['calendar_pdf_table_fontsize']) ? $settings['calendar_pdf_table_fontsize'] : 8;
        $titlefontsize= ($settings['calendar_pdf_title_fontsize']) ? $settings['calendar_pdf_title_fontsize'] : 12;


    //start building calendar
    $displayfrom_lower = $displayfrom;
    if ($forward > 0) {
        $displayfrom_upper = date__skip_months($forward, $displayfrom_lower);
    } else {
        $displayfrom_upper=$displayfrom_lower;
    }
    if($wholeyear){
        $displayfrom_upper = mktime(0, 0, 0, 1, 1, date('Y', $displayfrom)+1);
    }
    $results = calendar__get_events($admin, $displayfrom_lower, $displayfrom_upper,false,true);
    $month_names=explode(",",$lang['month_names']);


    //loop through each month
    for($itime = $displayfrom_lower; $itime <= $displayfrom_upper; $itime = date__skip_months(1, $itime)){
        $year = date("Y", $itime); $month = date("m", $itime);
        $weeks = days_in_month($month, $year);
        $table_title=$month_names[($month-1)] . ' ' . $year;
        $table_headings=array();
        $calendar__weekdays=explode(",",$lang['format_datetime_weekday_abbr']);
        for ($i3 = 1; $i3 <= 7; ++$i3) {
            if (!isset($lang['format_datetime_firstdayofweek_0:Su_1:Mo']) || (!$lang['format_datetime_firstdayofweek_0:Su_1:Mo'])) {
                $wdindex = $i3-1;
            } else {
                $wdindex = $i3;
                if ($wdindex==7) {
                    $wdindex=0;
                }
            }
            $table_headings[$i3]=$calendar__weekdays[$wdindex];
        }

        $table_data=array();
        for($i2 = 1; $i2 <= count($weeks); ++$i2){
            $las1=array(); $las2=array();;
            for ($i3 = 1; $i3 <= 7; ++$i3){
                if(!isset($weeks[$i2][$i3])){
                    $las1[$i3]="";
                    $las2[$i3]="";
                } else {
                    $las1[$i3]=$weeks[$i2][$i3];
                    $las2[$i3]="";
                    //the date is the key of the $results array for easy searching
                    $today = $year*10000+$month*100+$weeks[$i2][$i3];
                    if(isset($results[$today])){
                        foreach($results[$today] as $item){
                            $las2[$i3].=$item['display_time'];
                            $las2[$i3].="\n"."<i>".$item['location']."</i>\n";
                            if($admin || $settings['public_calendar_hide_exp_name']!='y'){
                                $las2[$i3].='<b>'.$item['title'].'</b>';
                            } else {
                                $las2[$i3].='<b>'.$lang['calendar_experiment_session'].'</b>';
                            }
                            $las2[$i3].="\n";

                            if($admin){
                                $las2[$i3].=experiment__list_experimenters($item['experimenters'],false,true)."\n";
                            }
                            if($item['type'] == "experiment_session"){
                                if($admin){
                                    $las2[$i3].=$item['participants_registered']." (" . $item['participants_needed']. "," . $item['participants_reserve'] . ")";
                                } else {
                                    $las2[$i3].=$statusdata[$item['status']]['message'];
                                }
                            }
                            $las2[$i3].="\n\n";
                        }
                    }
                }
            }
            $table_data[]=$las1;
            $table_data[]=$las2;
        }

        $y=$pdf->ezTable($table_data,
                        $table_headings,
                        $table_title,
                    array( //'showLines'=>2,
                            'gridlines'=>31,
                            'showHeadings'=>1,
                            'shaded'=>2,
                            'shadeCol'=>array(1,1,1),
                'shadeCol2'=>array(0.9,0.9,1),
                            'fontSize'=>$fontsize,
                            'titleFontSize'=>$titlefontsize,
                            'rowGap'=>1,
                            'colGap'=>3,
                            'innerLineThickness'=>0.5,
                            'outerLineThickness'=>1,
                            'maxWidth'=>500,
                'width'=>500,
                'protectRows'=>2));
        $pdf->ezSetDy(-20);
    }


    if ($file) {
        $pdffilecode = $pdf->output();

        return $pdffilecode;

        //$fname ="/apache/orsee/admin/pdfdir/test.pdf";
        //$fp = fopen($fname,'w');
        //fwrite($fp,$pdffilecode);
        //fclose($fp);
        //echo '<A HREF="pdfdir/test.pdf" target="_blank">pdf file</A><BR><BR>';
        //$pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
        //echo trim($pdfcode);

        } else {
        $pdf->ezStream(array('Content-Disposition'=>'calendar.pdf',
                'Accept-Ranges'=>0,
                'compress'=>1));
        }

}



?>
