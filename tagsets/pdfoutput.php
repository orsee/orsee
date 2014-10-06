<?php
// part of orsee. see orsee.org

function pdfoutput__make_part_list($experiment_id,$session_id,$focus,$sort="",$file=false,$tlang="") {
	global $settings;

	if ($tlang=="") {
		global $lang;
		}
	  else {
		$lang=load_language($tlang);
	}

	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

	if ($session_id) $session=orsee_db_load_array("sessions",$session_id,"session_id");
		else $session=array();

	$columns=participant__load_result_table_fields('sessionpdf');

	if ($session_id) $session=orsee_db_load_array("sessions",$session_id,"session_id");

	$csorts=array(); 
	foreach($columns as $c) if (count($csorts)<2 && $c['allow_sort']) $csorts[]=$c['sort_order'];
	$csorts_string=implode(",",$csorts);

	if ($sort) $order=$sort;
    	else { 
			$order="session_start_year, session_start_month, session_start_day,
                	session_start_hour, session_start_minute";
            if ($csorts_string) $order.=",".$csorts_string;
    }
  
		$focuses=array('assigned','invited','registered','shownup','participated');
		foreach($focuses as $f) { $$f=false; }
        if (!$focus) $focus="assigned";
        $$focus=true;

        switch ($focus) {
                case "assigned":        $where_clause="AND registered='n'";
                                        $title=$lang['assigned_subjects_not_yet_registered'];
                                        break;
                case "invited":         $where_clause="AND invited='y' AND registered='n'";
                                        $title=$lang['invited_subjects_not_yet_registered'];
                                        break;
                case "registered":      $where_clause="AND registered='y'";
                                        $title=$lang['registered_subjects'];
                                        break;
                case "shownup":         $where_clause="AND shownup='y'";
                                        $title=$lang['shownup_subjects'];
                                        break;
                case "participated":    $where_clause="AND participated='y'";
                                        $title=$lang['subjects_participated'];
                                        break;
                }


        $select_query=" SELECT * FROM ".table('participants').", ".table('participate_at').",
                                        ".table('sessions')."
                        WHERE ".table('participants').".participant_id=".
                                        table('participate_at').".participant_id
                        AND ".table('sessions').".session_id=".table('participate_at').".session_id
                        AND ".table('participate_at').".experiment_id='".$experiment_id."' ";
        if ($session_id) $select_query.=" AND ".table('participate_at').".session_id='".$session_id."' ";
        $select_query.=$where_clause." ORDER BY ".$order;


	// get result
        $result=mysqli_query($GLOBALS['mysqli'],$select_query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

        $participants=array();
        while ($line=mysqli_fetch_assoc($result)) {
                $participants[]=$line;
                }
        $result_count=count($participants);


	// determine table title
	$table_title=$experiment['experiment_public_name'];
	if ($session_id) $table_title.=', '.$lang['session'].' '.session__build_name($session);
	$table_title.=' - '.$title;
	
	// determine table headings
	$table_headings=array();
	$table_headings[]="";
	foreach($columns as $c) {
        $table_headings[]=$c['column_name'];
    }	
	$table_headings[]=$lang['noshowup'];
	if ($assigned || $invited) $table_headings[]=$lang['invited_abbr'];
	if ($registered || $shownup || $participated) {
		if (!$session_id) $table_headings[]=$lang['session'];
		$table_headings[]=$lang['shownup_abbr'];
		$table_headings[]=$lang['participated_abbr'];
		}
	$table_headings[]=$lang['rules_abbr'];


	$table_data=array();
	$session_names=array();

	$pnr=0;
        foreach ($participants as $p) {

		$row=array();
		$pnr++;
		$row[]=$pnr;
		foreach($columns as $c) {
          	if(preg_match("/(radioline|select_list|select_lang)/",$c['type']) && isset($c['lang'][$p[$c['mysql_column_name']]]))
               		$row[]=$c['lang'][$p[$c['mysql_column_name']]];
            else $row[]=$p[$c['mysql_column_name']];
        }
		$row[]=$p['number_noshowup'].'/'.$p['number_reg'];
		if ($assigned || $invited) $row[]=$p['invited'];
		if ($registered || $shownup || $participated) {
			if (!$session_id) {
				if (!isset($session_names[$p['session_id']])) 
					$session_names[$p['session_id']]=session__build_name($p);
				$row[]=$session_names[$p['session_id']];
				}
			$row[]=$lang[$p['shownup']];
                        $row[]=$lang[$p['participated']];
			}
		$row[]= ($p['rules_signed']!='y') ? "X" : '';
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
                        array(	'gridlines'=>31, 
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

	// debugging stuff
        if ($file) {
                $pdffilecode = $pdf->output();

                return $pdffilecode;
                } else {
                $pdf->ezStream(array('Content-Disposition'=>'participant_list.pdf',
                                'Accept-Ranges'=>0,
                                'compress'=>1));
                }

}


function pdfoutput__make_calendar($caltime=0,$calyear=false,$admin=false,$forward=0,$file=false) {
	global $settings;

	if(isset($_REQUEST['d'])) $debug=$_REQUEST['d']; else $debug=false;

	if ($caltime==0) $caltime=time();

	if ($calyear) {
		$caltime=date__year_start_time($caltime);
		$forward=11;
		}

	// prepare pdf
	include_once('../tagsets/class.ezpdf.php');

	$pdf = new Cezpdf('a4');

	$pdf->selectFont('../tagsets/fonts/Times-Roman.afm');

	$fontsize= ($settings['calendar_pdf_table_fontsize']) ? $settings['calendar_pdf_table_fontsize'] : 8;
        $titlefontsize= ($settings['calendar_pdf_title_fontsize']) ? $settings['calendar_pdf_title_fontsize'] : 12;

	$i=0;
	while ($i< $forward+1) {
		$monthdata=pdfoutput__calendar_get_month_table($caltime,$admin);

		$title=$monthdata['table_title'];
		$headings=$monthdata['table_headings'];
		$data=$monthdata['table_data'];

		$y=$pdf->ezTable($data,
              			$headings,
				$title,
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
		$caltime=date__skip_months(1,$caltime);
		$i++;
		}

	// debugging stuff
	if ($file) {
		$pdffilecode = $pdf->output();

		return $pdffilecode;	
  		/*
  		$fname ="/apache/orsee/admin/pdfdir/test.pdf";
  
  		$fp = fopen($fname,'w');
  			fwrite($fp,$pdffilecode);
  		fclose($fp);
		echo '<A HREF="pdfdir/test.pdf" target="_blank">pdf file</A><BR><BR>';
  		$pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
  		echo trim($pdfcode);
		*/
		} else {
  		$pdf->ezStream(array('Content-Disposition'=>'calendar.pdf',
				'Accept-Ranges'=>0,
				'compress'=>1));
		}
}

function pdfoutput__calendar_get_month_table($time=0,$admin=false) {
	if ($time==0) $time=time();

	global $lang,$settings;

  	$start_date=date__skip_months(0,$time);
  	$date=getdate($start_date);
  	$limit=calendar__days_in_month($date['mon'],$date['year']);
  	$first_day=$date['wday'];
  	if ($first_day==0) $first_day=7; $first_day=$first_day-1;
  	$day="01";
  	$month=helpers__pad_number($date['mon'],2);
  	$year=$date['year'];
	$month_names=explode(",",$lang['month_names']);

	// prepare day array
        $days=array();
        for ($i=1; $i<=$limit; $i++) {
                $days[$i]=array();
                }

        // get session times
        $query="SELECT * FROM ".table('sessions').", ".table('experiments').", ".table('lang')."
                WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id
                AND ".table('sessions').".laboratory_id=".table('lang').".content_name
                AND ".table('lang').".content_type='laboratory'";
        if (!$admin) $query.=" AND ".table('experiments').".hide_in_cal='n' ";
        $query.=" AND session_start_year='".$year."'
                 AND session_start_month='".$date['mon']."'
                 ORDER BY session_start_day, session_start_hour, session_start_minute";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) $days[$line['session_start_day']][]=$line;

        // get maintenance times
        $monthstring=$year.$month;

        $query="SELECT *, (space_start_year*100+space_start_month) as space_start,
                        (space_stop_year*100+space_stop_month) as space_stop
                FROM ".table('lab_space').", ".table('lang')."
                WHERE ".table('lab_space').".laboratory_id=".table('lang').".content_name
                AND ".table('lang').".content_type='laboratory'
                HAVING space_start<='".$monthstring."'
                        AND space_stop>='".$monthstring."'
                ORDER BY space_start_day, space_start_hour, space_start_minute";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $maintstart = ($line['space_start']==$monthstring) ? $line['space_start_day'] : 1;
                $maintstop = ($line['space_stop']==$monthstring) ? $line['space_stop_day'] : $limit;
                for ($i=$maintstart; $i<=$maintstop; $i++) $days[$i][]=$line;
                }



        // remember table title
        $table_title=$month_names[$date['mon']-1].' '.$year;

        // remember table heading
        $calendar__weekdays=explode(",",$lang['weekdays_abbr']);
	$wday=1;
        foreach ($calendar__weekdays as $weekday) {
                $table_headings[$wday]=$weekday;
		$wday++;
        }

	// write empty fields in days array
	$i=0;
 	$wday=1; $las1=array(); $las2=array();
  	while ($i<$first_day) {
    		$las1[$wday]=""; $las2[$wday]=""; 
    		$i++; $wday++;
  	}

	// write day numbers
  	$i=1; $pointer=0;
  	while ($i<=$limit) {
          	if ($wday==8) {
           		$table_data[]=$las1;
	   		$table_data[]=$las2;
	   		$las1=array(); $las2=array(); $wday=1;
           		}
        	$las1[$wday]=helpers__pad_number($i,2); $las2[$wday]="";
		$nonempty=false;

        	foreach ($days[$i] as $entry) {
		   $nonempty=true;
		   if (isset($entry['session_start_day'])) {
			$nonempty=true;

			$start_time=time__get_timepack_from_pack($entry,"session_start_");
                        $duration=time__get_timepack_from_pack($entry,"session_duration_");
                        $end_time=time__add_packages($start_time,$duration);

						if (!isset($las2[$wday])) $las2[$wday]="";
                        $las2[$wday].=time__format($lang['lang'],$start_time,true,false,true,true).'-'.
                                                time__format($lang['lang'],$end_time,true,false,true,true);

			$las2[$wday].="\n"."<i>".laboratories__strip_lab_name(stripslashes($entry[$lang['lang']]))."</i>\n";

        		if ($admin) 
				$las2[$wday].="<b>".$entry['experiment_name']."</b>";
                  		else $las2[$wday].="<b>".$entry['experiment_public_name']."</b>";
			$las2[$wday].="\n";

			if ($admin) $las2[$wday].=$entry['experimenter']."\n";

        		$cs__reg=experiment__count_participate_at($entry['experiment_id'],$entry['session_id']);

        		if ($cs__reg<$entry['part_needed']) $cs__status="not_enough_participants";
        		  elseif ($cs__reg<$entry['part_needed']+$entry['part_reserve']) $cs__status="not_enough_reserve";
        		  else $cs__status="complete";


        		if ($admin) $las2[$wday].=$cs__reg.' ('.$entry['part_needed'].','.$entry['part_reserve'].')';

        		$reg_end=sessions__get_registration_end($entry);

        		if (time()>$reg_end) $cs__status="complete";

        		if (!($admin)) {
        			switch ($cs__status) {
                			case "not_enough_participants":
                			case "not_enough_reserve":
                                        			        $text=$lang['free_places'];
                                                			break;
                			case "complete":
                                               				$text=$lang['complete'];
                                               				break;
        				}
        			$las2[$wday].=$text;
        			}
        		}
		    else {
			if ($admin) {
                                if ($entry['space_start_day']==$i) {
                                	$from['hour']=$entry['space_start_hour'];
                                        $from['minute']=$entry['space_start_minute'];
                                        }
                                   else {
                                        $from['hour']=$settings['laboratory_opening_time_hour'];
                                        $from['minute']=$settings['laboratory_opening_time_minute'];
                                        }
                                   if ($entry['space_stop_day']==$i) {
                                        $to['hour']=$entry['space_stop_hour'];
                                        $to['minute']=$entry['space_stop_minute'];
                                        }
                                   else {
                                        $to['hour']=$settings['laboratory_closing_time_hour'];
                                        $to['minute']=$settings['laboratory_closing_time_minute'];
                                        }
                                   $las2[$wday].=time__format($lang['lang'],$from,true,false,true,true).'-'.
                                                time__format($lang['lang'],$to,true,false,true,true);
                                   $las2[$wday].="\n"."<i>".laboratories__strip_lab_name(stripslashes($entry[$lang['lang']]))."</i>\n";

                                   $las2[$wday].="<b>".$entry['reason']."</b>\n".$entry['experimenter'];
                                        }
			}
		    $las2[$wday].="\n\n";
                    $pointer++;
		    }
		if (!$nonempty) $las2[$wday]="";
		$i++; $wday++;
  		}

  	while ($wday<8) {
    		$las1[$wday]=""; $las2[$wday]="";
    		$wday++;
  		}
  	$table_data[]=$las1;
  	$table_data[]=$las2;

	$alldata['table_title']=$table_title;
	$alldata['table_headings']=$table_headings;
	$alldata['table_data']=$table_data;

  	return $alldata;
}

?>
