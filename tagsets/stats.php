<?php
// part of orsee. see orsee.org

function str_max_len($string1,$max) {
	if (strlen($string1) > $max) return strlen($string1);
		else return $max;
	}

function stats__rearrange_pie_array($stat) {
	$data=$stat['data'];
	$i=0; $new_data=array();
	foreach ($stat['legend'] as $row) {
		$new_data[]=array($row,$data[0][$i+1]);
		$i++;
		}
	$stat['data']=$new_data;
	$stat['xtitle']=$stat['title'];
	$stat['legend']=array();
	return $stat;
}

function stats__textstats_format_table($stat,$lines=true) {
	global $lang;

	if (!(is_array($stat['legend']) && count($stat['legend'])>0)) {
                        if ($stat['ytitle']) $stat['legend']=array($stat['ytitle']);
                                else $stat['legend']=array($lang['count']);
                        }

	// calculate column lenghts
	$len=array();
	foreach ($stat['data'] as $row) {
		$i=0;
		$num_cols=count($row);
		foreach ($row as $column) {
			if (isset($len[$i])) $len[$i]=str_max_len($column,$len[$i]);
				else $len[$i]=strlen($column);
			$i++;
			}
		}

	if (is_array($stat['legend']) && count($stat['legend'])>0) {
		if (isset($stat['xtitle'])) $len[0]=str_max_len($stat['xtitle'],$len[0]);
			else $stat['xtitle']='';
		$i=1;
		foreach ($stat['legend'] as $legval) {
			if (!isset($len[$i])) $len[$i]=0;
            $len[$i]=str_max_len($legval,$len[$i]);
			$i++;
            }
		}

	// increment by 1
	$col_len=array();
	foreach ($len as $key=>$length) $col_len[$key]=$len[$key]+1;

	// now put out
	$output='';
	$output="**".$stat['title']."\n";
        if (is_array($stat['legend']) && count($stat['legend'])>0) {
			$i=0;
			if ($lines) $output.="| ";
			$output.=str_pad($stat['xtitle'],$col_len[$i]);
			$i=1;
                        foreach ($stat['legend'] as $legval) {
				if ($lines) $output.="| ";
                                $output.=str_pad($legval,$col_len[$i]);
                                $i++;
                                }
			if ($lines) $output.="|";
			$output.="\n";
			if ($lines) {
				$i=0;
				$output.="+-";
				$output.=str_pad("",$col_len[$i],"-");
				$i=1;
				foreach ($stat['legend'] as $legval) {
                                	$output.="+-";
                                	$output.=str_pad("",$col_len[$i],"-");
                                	$i++;
                                	}
				$output.="+\n";
				}
			}

	foreach ($stat['data'] as $row) {
		$i=0;
		foreach ($row as $column) {
			if ($lines) $output.="| ";
			$output.=str_pad($column,$col_len[$i]);
			$i++;
			}
		if ($lines) $output.="|";
		$output.="\n";
		}

	if ($lines && is_array($stat['legend']) && count($stat['legend'])>0) {
                $i=0;
                $output.="+-";
                $output.=str_pad("",$col_len[$i],"-");
                $i=1;
                foreach ($stat['legend'] as $legval) {
                        $output.="+-";
                        $output.=str_pad("",$col_len[$i],"-");
                        $i++;
                        }
                $output.="+\n";
                }
	return $output;
}

function stats__get_textstats($stype) {
    $stat=array(); $output="";
    if(substr($stype,0,6)=="pform:") {
        $stat=stats__array_pform_field(substr($stype,6));
        if ($stat['graphtype']=='pie') $stat=stats__rearrange_pie_array($stat);
    } elseif (function_exists('stats__array_'.$stype)) {
        $fname='stats__array_'.$stype;
        $stat=$fname();
		if ($stat['graphtype']=='pie') $stat=stats__rearrange_pie_array($stat);
    } else {
    	$stat['data']=array(array('no data',0));
    }
    $output.=stats__textstats_format_table($stat,true);
	$output.="\n";
	return $output;
}

function stats__all($type='text') { // text, html, graph, htmlgraph
	$statfields=array();
	if ($type=='text') $statfields[]='participant_actions|text';
	if ($type=='htmlgraph') {
		$statfields[]='subpool|graph';
		$statfields[]='subpool|html';
	} else {
		$statfields[]='subpool|'.$type;
	}
	if ($type=='graph') $statfields[]='subpool|html';

	$formfields=participantform__load();
	foreach($formfields as $f) {
		if ($f['include_in_statistics']=='pie' || $f['include_in_statistics']=='bars') {
			if ($type=='htmlgraph') {
				$statfields[]='pform:'.$f['mysql_column_name'].'|graph';
				$statfields[]='pform:'.$f['mysql_column_name'].'|html';
			} else {
				$statfields[]='pform:'.$f['mysql_column_name'].'|'.$type;
			}
		}
    }

	$others=array('experiment_participations','nr_participations','nr_noshows','noshows_by_month');
	foreach($others as $v) {
		if ($type=='htmlgraph') {
			$statfields[]=$v.'|graph';
			$statfields[]=$v.'|html';
		} else {
			$statfields[]=$v.'|'.$type;
		}
	}

	$output="";
	foreach ($statfields as $v) {
		$sfield=explode("|",$v);
		if ($sfield[1]=='text') $output.=stats__get_textstats($sfield[0]);
		elseif ($sfield[1]=='html') $output.=stats__get_htmlstats($sfield[0]);
		elseif ($sfield[1]=='graph') $output.=stats__get_graphstats($sfield[0]);
	}

	return $output;      
}

function stats__textstats_all() {
	$output='';
	$output.=stats__all('text');
	return $output;
}

function stats__htmlstats_format_table($stat,$lines=true,$stype='') {
        global $lang, $subpool_id, $color;

        if (!(is_array($stat['legend']) && count($stat['legend'])>0)) {
                        if ($stat['ytitle']) $stat['legend']=array($stat['ytitle']);
				else $stat['legend']=array($lang['count']);
			}

        $output='';
        $output.='<TR><TD bgcolor="'.$color['list_header_background'].'">'.$stat['title'].'</TD></TR>';
	$output.='<TR><TD bgcolor="'.$color['list_list_background'].'">';
	$output.='<TABLE width=100% border=';
		if ($lines) $output.='1'; else $output.='0';
	$output.='>';
        if (is_array($stat['legend']) && count($stat['legend'])>0) {
                        $output.='<TR>
                        		<TD>'.$stat['xtitle'].'</TD>';
                        foreach ($stat['legend'] as $legval) {
                                $output.='<TD>'.$legval.'</TD>';
                                }
                        $output.='</TR>';
                        }
	$i=0;
        foreach ($stat['data'] as $row) {
		$output.='<TR>';
                foreach ($row as $column) {
                        $output.='<TD>'.$column.'</TD>';
                        }
		if ($stype=='subpool') {
			$output.='<TD>';
			if ($stat['subpool_ids'][$i] != $subpool_id) 
				$output.='<A HREF="'.thisdoc().'?subpool_id='.$stat['subpool_ids'][$i].'">'.
					$lang['restrict_stats_to_this_pool'].'</A>';
			   else $output.='<A HREF="'.thisdoc().'">'.
                                        $lang['withdraw_restriction'].'</A>';
			$output.='</TD>';
			}
                $output.="</TR>";
		$i++;
                }
	$output.='</TABLE></TD></TR>';
	if ($stype=='subpool' && $subpool_id)
		$output.='<TR><TD bgcolor="orange" align=center>'.$lang['statistics_below_restricted_to_subpool'].' "'.
			  subpools__get_subpool_name($subpool_id).'"</TD></TR>';
        return $output;
}

function stats__get_htmlstats($stype) {
        $stat=array(); $output="";
        if(substr($stype,0,6)=="pform:") {
        	$stat=stats__array_pform_field(substr($stype,6));
        	if ($stat['graphtype']=='pie') $stat=stats__rearrange_pie_array($stat);
        } elseif (function_exists('stats__array_'.$stype)) {
            $fname='stats__array_'.$stype;
            $stat=$fname();
			if ($stat['graphtype']=='pie') $stat=stats__rearrange_pie_array($stat);
        } else {
            $stat['data']=array(array('no data',0));
        }
        $output.=stats__htmlstats_format_table($stat,true,$stype);
        return $output;
}

function stats__participant_htmlstats_all() {
    $output='<TABLE width=90% border=1>';
	$output.=stats__all('html');
	$output.='</TABLE>';
    return $output;
}


function stats__system_htmlstats_all() {
        $output='<TABLE width=90% border=1>';

        $output.=stats__get_htmlstats('participant_actions');
        $output.='</TABLE>';

        return $output;
}

function stats__get_graphstats($stype) {
	global $subpool_id;
	$output='<TR>
        		<TD align=center>
				<IMG border=0 src="statistics_plot.php?stype='.urlencode($stype).'&subpool_id='.urlencode($subpool_id).'">
			</TD>
                </TR>';
        return $output;
}

function stats__participant_graphstats_all() {
    $output='<TABLE width=90% border=1>';
	$output.=stats__all('graph');
    $output.='</TABLE>';
    return $output;
}

function stats__system_graphstats_all() {
        $output='<TABLE width=90% border=1>';
        $output.=stats__get_graphstats('participant_actions');
        $output.='</TABLE>';

        return $output;
}

function stats__participant_htmlgraphstats_all() {
    $output='<TABLE width=90% border=1>';
	$output.=stats__all('htmlgraph');
    $output.='</TABLE>';
    return $output;
}

function stats__system_htmlgraphstats_all() {
        $output='<TABLE width=90% border=1>';

        $output.=stats__get_graphstats('participant_actions');
        $output.=stats__get_htmlstats('participant_actions');
        $output.='</TABLE>';

        return $output;
}

function stats__get_y_increment($data) {
	$biggest=1;

	if (is_array($data)) foreach ($data as $row) {
		$c=0;
		if (is_array($row)) foreach ($row as $column) {
			if ($c>0 && $column > $biggest) $biggest=$column;
			$c++;
			}
		}
	$dec_places=floor(log10($biggest));
	$big_scaled=$biggest/pow(10,$dec_places);
	if ($big_scaled<=5) $inc=0.5 * pow(10,$dec_places);
		else $inc=1 * pow(10,$dec_places);
	return $inc;
}


function stats__array_participant_actions($months_backward=12) {
	global $lang;
        $actions=array('subscribe','confirm','edit','delete');

	$years=array(); $months=array();
        $i=0;
        $query="SELECT DISTINCT year, month
                FROM ".table('participants_log')."
                ORDER BY timestamp DESC
                LIMIT ".$months_backward;
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $years[$i]=$line['year'];
                $months[$i]=$line['month'];
                $i++;
                }

	// titles ect ...
        $stat['xtitle']=$lang['month'];
	$stat['ytitle']=$lang['count'];
	$stat['title']=$lang['participant_actions'];
	$stat['graphtype']='bars';
	$stat['xsize']=600;

	$stat['legend']=array();
        foreach($actions as $action) $stat['legend'][]=$action;

//	$last_month_id= count($months)-1;
//	$last_month=$years[$last_month_id]*100+$months[$last_month_id];


	// the data
	//first get the stuff from the database
	$pre_data=array();
	$limit=count($months) * (count($actions)+1);
	$query="SELECT year, month, action, count(log_id) as nractions
                FROM ".table('participants_log')."
                GROUP by year, month, action 
		ORDER BY year DESC, month DESC, action 
		LIMIT ".$limit;
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result))
        	$pre_data[$line['year'].'_'.$line['month'].'_'.$line['action']]=$line['nractions'];

	// fine: now write into array
	$data=array();
	$i=0;
	foreach ($months as $month) {
		$data[$i][]=str_pad($months[$i],2,"0",STR_PAD_LEFT).'/'.$years[$i];
		foreach ($actions as $action) {
             		if (isset($pre_data[$years[$i].'_'.$months[$i].'_'.$action])) 
				$data[$i][]=$pre_data[$years[$i].'_'.$months[$i].'_'.$action];
			   else $data[$i][]=0;
			}
		$i++;
		}
	$stat['data']=$data;
	return $stat;
}


function stats__array_begin_of_studies() {
        global $lang, $subpool_id;
	$stat=array();

        // titles ect ...
        $stat['xtitle']=$lang['begin_of_studies'];
        $stat['ytitle']=$lang['count'];
        $stat['title']=$lang['begin_of_studies'];
        $stat['graphtype']='bars';

        $stat['legend']=array();

	$data=array();
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        $query="SELECT begin_of_studies, count(participant_id) as nrpart
                FROM ".table('participants')."
                WHERE deleted='n' ".
		$qsubpool."  
      		GROUP BY begin_of_studies
      		ORDER BY begin_of_studies DESC";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        $bgs=array();
        while ($line=mysqli_fetch_assoc($result)) {
                if (!$line['begin_of_studies']) $line['begin_of_studies']="?";
                if(isset($bgs[$line['begin_of_studies']]))
                        $bgs[$line['begin_of_studies']]=$bgs[$line['begin_of_studies']]+$line['nrpart'];
                else $bgs[$line['begin_of_studies']]=$line['nrpart']; 
        }               
        foreach ($bgs as $k=>$v) {
                $data[]=array($k,$v);
        }
        $stat['data']=$data;
        return $stat;
}


        

function stats__array_gender() {
        global $lang, $subpool_id;
        $stat=array();

        // titles ect ...
        $stat['xtitle']='';
        $stat['ytitle']='';
        $stat['title']=$lang['gender'];
        $stat['graphtype']='pie';

        $stat['legend']=array();
	$stat['legend_y']='';
	$stat['legend_y']='';

        $data=array();
	$data[0][]='mmm';
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        $query="SELECT gender, count(participant_id) as nrpart
                FROM ".table('participants')."
                WHERE deleted='n' ".
                $qsubpool."
                GROUP BY gender
                ORDER BY gender";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
		$lang_string='gender_'.$line['gender'];
		$stat['legend'][]=$lang[$lang_string];
                $data[0][]=$line['nrpart'];
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_pform_field($field) {
        global $lang, $subpool_id;
        $stat=array();
        
        // load pform field
        $formfields=participantform__load(); $f=array();
		foreach($formfields as $tf) if ($tf['mysql_column_name']==$field && 
			($tf['include_in_statistics']=='pie' || $tf['include_in_statistics']=='bars')) $f=$tf;
			
     
        // titles ect ...
        $stat['xtitle']='';
        $stat['ytitle']='';
        $stat['title']=isset($lang[$f['name_lang']])?$lang[$f['name_lang']]:$f['name_lang'];;
        $stat['graphtype']=$f['include_in_statistics'];
        $stat['legend']=array();
        $stat['legend_y']='';
        $stat['legend_y']='';
        $data=array();
        if($f['include_in_statistics']=='pie') $data[0][]='mmm';
        
        if ($f['type']=='select_lang') $names=lang__load_lang_cat($f['mysql_column_name'],$lang['lang']);
        elseif(preg_match("/(radioline|select_list)/",$f['type'])) {
				$optionvalues=explode(",",$f['option_values']);
				$optionnames=explode(",",$f['option_values_lang']);
				$names=array();
				foreach($optionvalues as $k=>$v) {
					if (isset($optionnames[$k])) {
						if (isset($lang[$optionnames[$k]])) $names[$v]=$lang[$optionnames[$k]];
						else $names[$v]=$optionnames[$k];
					}
				}
		} else $names=array();
        
        $order=($f['include_in_statistics']=='pie')?"nrpart DESC, ".$f['mysql_column_name']:$f['mysql_column_name'];
        
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";
        $query="SELECT ".$f['mysql_column_name']." as myfield, count(participant_id) as nrpart 
		        FROM ".table('participants')."
                WHERE deleted='n' ".
				$qsubpool."
                GROUP BY ".$f['mysql_column_name']."
                ORDER BY ".$order;
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        $values=array();
        while ($line=mysqli_fetch_assoc($result)) {
        	if (!$line['myfield']) $line['myfield']="?";
            if(isset($values[$line['myfield']]))
                     $values[$line['myfield']]=$values[$line['myfield']]+$line['nrpart'];
            else $values[$line['myfield']]=$line['nrpart'];
        }
        foreach ($values as $k=>$v) {
        	$tname=$k;
        	$tname=(isset($names[$k]))?$names[$k]:$k;
        	if($f['include_in_statistics']=='bars') {
                $data[]=array($tname,$v);
            } else {
                $stat['legend'][]=$tname;
                $data[0][]=$v;
			}
		}
        $stat['data']=$data;
        return $stat;
}

function stats__array_field_of_studies() {
        global $lang, $subpool_id;
        $stat=array();
        // titles ect ...
        $stat['xtitle']='';
        $stat['ytitle']='';
        $stat['title']=$lang['studies'];
        $stat['graphtype']='pie';
        $stat['legend']=array();
        $stat['legend_y']='';
        $stat['legend_y']='';
        $data=array();
        $data[0][]='mmm';
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";
        $query="SELECT field_of_studies, count(participant_id) as nrpart, 
		".$lang['lang']." as study
                FROM ".table('participants').", ".table('lang')."
                WHERE deleted='n'
		AND field_of_studies!='0'   
		AND field_of_studies=content_name 
		AND content_type='field_of_studies' ".
                $qsubpool."
                GROUP BY field_of_studies
                ORDER BY nrpart DESC, study";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $stat['legend'][]=stripslashes($line['study']);
                $data[0][]=$line['nrpart'];
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_profession() {
        global $lang, $subpool_id;
        $stat=array();
        // titles ect ...
        $stat['xtitle']='';
        $stat['ytitle']='';
        $stat['title']=$lang['profession'];
        $stat['graphtype']='pie';
        $stat['legend']=array();
        $stat['legend_y']='';
        $stat['legend_y']='';
        $data=array();
        $data[0][]='mmm';
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";
        $query="SELECT profession, count(participant_id) as nrpart,
                ".$lang['lang']." as prof
                FROM ".table('participants').", ".table('lang')."
                WHERE deleted='n'
		AND profession!='0' 
                AND profession=content_name
                AND content_type='profession' ".
                $qsubpool."
                GROUP BY profession
                ORDER BY nrpart DESC, prof";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $stat['legend'][]=stripslashes($line['prof']);
                $data[0][]=$line['nrpart'];
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_subpool() {
        global $lang;
        $stat=array();
        // titles ect ...
        $stat['xtitle']='';
        $stat['ytitle']='';
        $stat['title']=$lang['subpool'];
        $stat['graphtype']='pie';
        $stat['legend']=array();
        $stat['legend_y']='';
        $stat['legend_x']='';
	$stat['subpool_ids']=array();

        $data=array();
        $data[0][]='mmm';
        $query="SELECT subpool_name, ".table('participants').".subpool_id, count(participant_id) as nrpart
                FROM ".table('participants').", ".table('subpools')."
                WHERE deleted='n'
                AND ".table('participants').".subpool_id=".table('subpools').".subpool_id
                GROUP BY ".table('participants').".subpool_id
                ORDER BY ".table('participants').".subpool_id";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $stat['legend'][]=stripslashes($line['subpool_name']);
		$stat['subpool_ids'][]=$line['subpool_id'];
                $data[0][]=$line['nrpart'];
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_nr_participations_old() {
        global $lang, $subpool_id;
        $stat=array();

        // titles ect ...
        $stat['xtitle']=$lang['experience'];
        $stat['ytitle']=$lang['count'];
        $stat['title']=$lang['experience'];
        $stat['graphtype']='bars';

        $stat['legend']=array();

        $data=array();
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        $query="SELECT number_reg-number_noshowup as num_part, count(participant_id) as nrpart
                FROM ".table('participants')."
                WHERE deleted='n' ".
                $qsubpool."
                GROUP BY num_part
                ORDER BY num_part DESC";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $data[]=array($line['num_part'],$line['nrpart']);
                }
        $stat['data']=$data;
        return $stat;
}


function stats__array_nr_noshows() {
        global $lang, $subpool_id;
        $stat=array();

        // titles ect ...
        $stat['xtitle']=$lang['noshowup'];
        $stat['ytitle']=$lang['count'];
        $stat['title']=$lang['noshows_by_count'];
        $stat['graphtype']='bars';

        $stat['legend']=array();

        $data=array();
        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        $query="SELECT number_noshowup, count(participant_id) as nrpart
                FROM ".table('participants')."
                WHERE deleted='n' ".
                $qsubpool."
                GROUP BY number_noshowup
                ORDER BY number_noshowup DESC";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $data[]=array($line['number_noshowup'],$line['nrpart']);
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_noshows_by_month($months_backward=18) {
        global $lang, $subpool_id;

	if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        $years=array(); $months=array();
        $i=0;
        $query="SELECT DISTINCT session_start_month, session_start_year
      		FROM ".table('sessions')."
      		WHERE session_finished='y' 
      		ORDER by session_start_year DESC, session_start_month DESC
		LIMIT ".$months_backward;
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                $years[$i]=$line['session_start_year'];
                $months[$i]=$line['session_start_month'];
                $i++;
                }

        // titles ect ...
        $stat['xtitle']=$lang['month'];
        $stat['ytitle']=$lang['share_in_percent'];
        $stat['title']=$lang['noshows_by_month'];
        $stat['graphtype']='bars';
	$stat['xsize']=600;

        $stat['legend']=array();

        // the data
        //first get the stuff from the database
        $pre_data=array();
        $limit=$months_backward * 2;
        $query="SELECT session_start_year, session_start_month, shownup,
		count(".table('participate_at').".participate_id) as number
      		FROM ".table('participants').", ".table('participate_at').", ".table('sessions')."
      		WHERE registered='y'
        	AND ".table('participate_at').".session_id=".table('sessions').".session_id
        	AND session_finished='y'  
        	AND ".table('participate_at').".participant_id=".
				table('participants').".participant_id ".
		$qsubpool."
        	GROUP BY session_start_year, session_start_month, shownup 
        	ORDER BY session_start_year DESC, session_start_month DESC, shownup 
		LIMIT ".$limit;
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result))
                $pre_data[$line['session_start_year'].'_'.
			  $line['session_start_month'].'_'.
			  $line['shownup']]=$line['number'];

        // fine: now write into array
        $data=array();
        $i=0;
        foreach ($months as $month) {
                $data[$i][]=str_pad($months[$i],2,"0",STR_PAD_LEFT).'/'.$years[$i];
                if (isset($pre_data[$years[$i].'_'.$months[$i].'_n']))
			$nr_noshows=$pre_data[$years[$i].'_'.$months[$i].'_n'];
		   else $nr_noshows=0;
		if (isset($pre_data[$years[$i].'_'.$months[$i].'_y']))
                        $nr_reg=$pre_data[$years[$i].'_'.$months[$i].'_y']+$nr_noshows;
                   else $nr_reg=$nr_noshows;
		if ($nr_reg>0) $data[$i][]=number_format(($nr_noshows/$nr_reg)*100,2);
			else $data[$i][]=0;
                $i++;
                }
        $stat['data']=$data;
        return $stat;
}

function stats__array_experiment_participations($months_backward=12) {
        global $lang, $subpool_id;

        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        // titles ect ...
        $stat['xtitle']=$lang['month'];
        $stat['ytitle']=$lang['count'];
        $stat['title']=$lang['experiment_participations'];
        $stat['graphtype']='bars';
	$stat['xsize']=600;

        $stat['legend']=array();

        // the data
        //first get the stuff from the database
        $data=array();
        $query="SELECT session_start_year, session_start_month,
                count(".table('participate_at').".participate_id) as number
                FROM ".table('participants').", ".table('participate_at').", ".table('sessions')."
                WHERE participated='y'
                AND ".table('participate_at').".session_id=".table('sessions').".session_id
                AND session_finished='y'  
                AND ".table('participate_at').".participant_id=".
                                table('participants').".participant_id ".
                $qsubpool."
                GROUP BY session_start_year, session_start_month
                ORDER BY session_start_year DESC, session_start_month DESC
                LIMIT ".$months_backward;
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	$i=0;
        while ($line=mysqli_fetch_assoc($result)) {
                $data[$i][]=str_pad($line['session_start_month'],2,"0",STR_PAD_LEFT).'/'.$line['session_start_year'];
                $data[$i][]=$line['number'];
		$i++;
		}
        $stat['data']=$data;
        return $stat;
}

function stats__array_nr_participations() {
        global $lang, $subpool_id;

        if ($subpool_id) $qsubpool=" AND subpool_id='".$subpool_id."'";
                else $qsubpool="";

        // titles ect ...
        $stat['xtitle']=$lang['experience'];
        $stat['ytitle']=$lang['count'];
        $stat['title']=$lang['experience'];
        $stat['graphtype']='bars';
	$stat['xsize']=600;

        $stat['legend']=array();

        // the data
        //first get the stuff from the database
        $pre_data=array();
        $query="SELECT ".table('participate_at').".participant_id, 
                count(".table('participate_at').".participate_id) as number
                FROM ".table('participants').", ".table('participate_at').", ".table('sessions')."
                WHERE participated='y'
                AND ".table('participate_at').".session_id=".table('sessions').".session_id
                AND session_finished='y' 
                AND ".table('participate_at').".participant_id=".
                                table('participants').".participant_id ".
                $qsubpool."
                GROUP BY ".table('participate_at').".participant_id
		ORDER BY number DESC";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        while ($line=mysqli_fetch_assoc($result)) {
                if (isset($pre_data[$line['number']])) $pre_data[$line['number']]++;
			else $pre_data[$line['number']]=1;
                }
	//krsort($pre_data);

	$data=array();
	$i=0;
	foreach ($pre_data as $key=>$value) {
		$data[$i][]=$key;
		$data[$i][]=$value;
		$i++;
		}

	
        $stat['data']=$data;
        return $stat;
}

?>
