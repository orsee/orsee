<?php
// part of orsee. see orsee.org

function stats__get_data($condition=array(),$type='stats',$restrict=array(),$options=array()) {
    global $settings;
    $conditions=array(); $pars=array();
    if (is_array($condition) && count($condition)>0) {
        $conditions[]=$condition['clause'];
        if (isset($condition['pars']) && is_array($condition['pars'])) {
            foreach ($condition['pars'] as $k=>$v) $pars[$k]=$v;
        }
    }

    $formfields=participantform__load();
    $pform_fields=array(); $pform_types=array();
    foreach ($formfields as $f) {
        if( (!preg_match("/(textline|textarea)/i",$f['type'])) &&
            isset($f['include_in_statistics']) &&
            ($f['include_in_statistics']=='pie' || $f['include_in_statistics']=='bars')) {
                $pform_fields[]=$f['mysql_column_name'];
                $pform_types[$f['mysql_column_name']]=$f;
        }
    }

    $statfields=array('subpool_id',
                    'subscriptions',
                    'status_id');
    foreach ($pform_fields as $field) if (!in_array($field,$statfields)) $statfields[]=$field;
    if ($type=='stats' && $settings['enable_rules_signed_tracking']=='y') {
        if (!in_array('rules_signed',$statfields)) $statfields[]='rules_signed';
    } else {
        $exists=array_search('rules_signed',$statfields);
        if ($exists) unset($statfields[$exists]);
    }

    if ($type=='stats') foreach (array('number_reg','number_noshowup','last_enrolment',
                        'last_profile_update','last_activity') as $field)
                                if (!in_array($field,$statfields)) $statfields[]=$field;
    $query="SELECT * from ".table('participants');
    $query_conditions="";
    if (count($conditions)>0) {
        $query_conditions.=" WHERE";
        $cond_strings=array();
        foreach ($conditions as $cond) {
            $cond_strings[]="(".$cond.")";
        }
        $query_conditions.=" ".implode(" AND ",$cond_strings);
    }
    $query.=$query_conditions;
    $result=or_query($query,$pars);
    $counts=array(); $pids=array();
    while ($line=pdo_fetch_assoc($result)) {
        // check whether we should count this participant and (for monthly stats) use this value
        $p_restrict=false;
        foreach ($statfields as $c) {
            $value=$line[$c];
            if (in_array($c,array('last_enrolment','last_profile_update','last_activity'))) {
                if (!$value) $value=0;
                if ($value==0) $value='-';
                else $value=date('Ym',$value);
            }
            $value=db_string_to_id_array($value);
            if (count($value)>0) {
                $this_restrict=true;
                foreach ($value as $v) {
                    if (!isset($restrict[$c][$v])) $this_restrict=false;
                }
                if ($this_restrict)  $p_restrict=true;
            } else {
                $value='-';
                if (isset($restrict[$c][$value])) $p_restrict=true;
            }
        }
        if (!$p_restrict) {
            $pids[]=$line['participant_id'];
            foreach ($statfields as $c) {
                $value=$line[$c];
                $continue=true;
                if (in_array($c,array('last_enrolment','last_profile_update','last_activity'))) {
                    if (!$value) $value=0;
                    if (date('Ym',$value)<date('Ym',strtotime("-".$settings['stats_months_backward']." month",time()))) $continue=false;
                    if ($value==0) $value='-';
                    else $value=date('Ym',$value);
                }
                if ($continue) {
                    $value=db_string_to_id_array($value);
                    if (count($value)>0) {
                        foreach ($value as $v) {
                            if (!isset($counts[$c][$v])) $counts[$c][$v]=0;
                            $counts[$c][$v]++;
                        }
                    } else {
                        $value='-';
                        if (!isset($counts[$c][$value])) $counts[$c][$value]=0;
                        $counts[$c][$value]++;
                    }
                }
            }
        }
    }

    $count_pids=count($pids);
    $pid_condition="";
    if (count($restrict)>0) {
        $pid_condition=" AND participant_id IN (".implode(", ",$pids).") ";
    } elseif (count($conditions)==1 && isset($options['condition_only_on_pid']) && $options['condition_only_on_pid']) {
        $pid_condition=" AND ".$condition['clause']." ";
    } elseif($query_conditions) {
        $pid_condition=" AND participant_id IN (SELECT participant_id FROM ".table('participants')." ".$query_conditions.") ";
    }
    // avg. experience (participation in experiments of class x, y, z  at time of experiment)
    $statfields[]='experience_avg_experimentclass';
    if ($count_pids>0) {
        $participated_clause=expregister__get_pstatus_query_snippet("participated");
        $query="SELECT count(*) as num, experiment_class
                FROM ".table('participate_at')." as p, ".table('experiments')." as e
                WHERE p.session_id>0 ".$pid_condition."
                AND e.experiment_id=p.experiment_id
                AND e.experiment_class !=''
                AND ".$participated_clause." ";
        if (is_array($options) && isset($options['upper_experience_limit'])) {
            $query.=" AND session_id IN (SELECT session_id FROM ".table('sessions')."
                        WHERE session_start < ".ortime__unixtime_to_sesstime($options['upper_experience_limit'])."
                        AND session_status IN ('completed','balanced') ) ";
        }
        $query.="GROUP BY experiment_class";
        $result=or_query($query,$pars);
        while ($line=pdo_fetch_assoc($result)) {
            $line['experiment_class']=db_string_to_id_array($line['experiment_class']);
            foreach ($line['experiment_class'] as $v) {
                if ($v>0) {
                    if (!isset($counts['experience_avg_experimentclass'][$v])) $counts['experience_avg_experimentclass'][$v]=0;
                    $counts['experience_avg_experimentclass'][$v]+=round($line['num']/$count_pids,2);
                }
            }
        }
    }
    if ($type=='report') {
            // avg. count pstatus at time of experiment)
            $statfields[]='experience_avg_pstatus';
    }
    if ($count_pids>0 && $type=='report') {
        $query="SELECT count(*) as num, pstatus_id
                FROM ".table('participate_at')."
                WHERE session_id>0 ".$pid_condition;
        if (is_array($options) && isset($options['upper_experience_limit'])) {
            $query.=" AND session_id IN (SELECT session_id FROM ".table('sessions')."
                    WHERE session_start < ".ortime__unixtime_to_sesstime($options['upper_experience_limit'])."
                    AND session_status IN ('completed','balanced') ) ";
        }
        $query.="GROUP BY pstatus_id";
        $result=or_query($query,$pars);
        while ($line=pdo_fetch_assoc($result)) {
            $counts['experience_avg_pstatus'][$line['pstatus_id']]=round($line['num']/$count_pids,2);
        }
    }
    if ($type=='stats') {
        // by pstatus: pstatus count // really needed? we have no_noshows, num_reg ...
        // by month: pstatus
        $statfields[]='bymonth_pstatus';
        $statfields[]='bymonth_noshowrate';
    }
    if ($count_pids>0 && $type=='stats') {
        $first_month=date('Ym000000',strtotime("-".$settings['stats_months_backward']." month",time()));
        $noshow_statuses=expregister__get_specific_pstatuses("noshow");
        $query="SELECT date_format(s.session_start*100,'%Y%m') as sessionmonth,
                pstatus_id, count(p.participate_id) as num
                FROM ".table('participate_at')." as p, ".table('sessions')." as s
                WHERE p.session_id>0
                AND p.session_id=s.session_id
                AND s.session_status IN ('completed','balanced')
                AND s.session_start>".$first_month." ".$pid_condition."
                GROUP BY sessionmonth, pstatus_id
                ORDER BY sessionmonth, pstatus_id ";
        $result=or_query($query,$pars); $noshowperc_data=array();
        while ($line=pdo_fetch_assoc($result)) {
            $counts['bymonth_pstatus_'.$line['pstatus_id']][$line['sessionmonth']]=$line['num'];
            if (!isset($noshowperc_data[$line['sessionmonth']]['n'])) $noshowperc_data[$line['sessionmonth']]['n']=0;
            if (!isset($noshowperc_data[$line['sessionmonth']]['y'])) $noshowperc_data[$line['sessionmonth']]['y']=0;
            if (in_array($line['pstatus_id'],$noshow_statuses)) {
                $noshowperc_data[$line['sessionmonth']]['n']+=$line['num'];
            } else {
                $noshowperc_data[$line['sessionmonth']]['y']+=$line['num'];
            }
        }
        // get the noshow percentages as well
        foreach ($noshowperc_data as $month=>$shownup) {
            $counts['bymonth_noshowrate'][$month]=round(($shownup['n']/($shownup['n']+$shownup['y']))*100,1);
        }
    }


    $xnames=array(
            'number_reg'=>lang('experience'),
            'number_noshowup'=>lang('noshowup'),
            'pstatus'=>lang('month'),
            'experience_avg_experimentclass'=>lang('experiment_classes'),
            'experience_avg_pstatus'=>lang('participation_status')
            );
    $titles=array(
            'number_reg'=>lang('experience'),
            'number_noshowup'=>lang('noshows_by_count'),
            'last_enrolment'=>lang('last_enrolment'),
            'last_profile_update'=>lang('last_profile_update'),
            'last_activity'=>lang('last_activity'),
            'bymonth_noshowrate'=>lang('noshows_by_month'),
            'experience_avg_experimentclass'=>lang('experience_in_experiment_classes'),
            'experience_avg_pstatus'=>lang('average_participation_experience')
            );


    // prepare all-containing arrray to return
    $data_temparray=array();
    foreach ($counts as $c=>$nums) {
        $d=array();
        $d['N']=$count_pids;
        if(isset($pform_types[$c])) {
            $d['browsable']=true;
            $d['charttype']=$pform_types[$c]['include_in_statistics'];
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=lang($pform_types[$c]['name_lang']);
            $d['title']=lang($pform_types[$c]['name_lang']);
            if (preg_match("/(select_lang|radioline_lang)/",$pform_types[$c]['type'])) $d['value_names']=lang__load_lang_cat($c,lang('lang'));
            elseif(preg_match("/(radioline|select_list)/",$pform_types[$c]['type'])) {
                $optionvalues=explode(",",$pform_types[$c]['option_values']);
                $optionnames=explode(",",$pform_types[$c]['option_values_lang']);
                $d['value_names']=array();
                foreach($optionvalues as $k=>$v) {
                    if (isset($optionnames[$k])) {
                        $d['value_names'][$v]=lang($optionnames[$k]);
                    }
                }
            } else $d['value_names']=array(); // select numbers?
            if ($pform_types[$c]['type']=='select_numbers') krsort($nums);
            else arsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif (in_array($c,array('number_reg','number_noshowup'))) {
            $d['browsable']=true;
            $d['charttype']='bars';
            if ($c=='number_reg') $d['wide']=true;
            $d['limit_not_apply']=true;
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=$xnames[$c];
            $d['title']=$titles[$c];
            $d['value_names']=array();
            krsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif (in_array($c,array('experience_avg_experimentclass','experience_avg_pstatus'))) {
            $d['charttype']='pie';
            $d['type_of_data']='calc';
            $d['yname']=lang('average_count');
            $d['xname']=$xnames[$c];
            $d['title']=$titles[$c];
            $d['value_names']=array();
            if ($c=='experience_avg_experimentclass') {
                $d['value_names']=experiment__load_experimentclassnames();
            } else {
                $pstatuses=expregister__get_participation_statuses();
                foreach ($pstatuses as $k=>$s) {
                    $d['value_names'][$k]=$s['internal_name'];
                }
            }
            arsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif ($c=='rules_signed') {
            $d['browsable']=true;
            $d['charttype']='pie';
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=lang('rules_signed');
            $d['title']=lang('rules_signed');
            $d['value_names']=array('n'=>lang('n'),'y'=>lang('y'));
            arsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif ($c=='subpool_id') {
            $d['browsable']=true;
            $d['charttype']='pie';
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=lang('subpool');
            $d['title']=lang('subpool');
            $subpools=subpools__get_subpools();
            $d['value_names']=array();
            foreach ($subpools as $k=>$p) $d['value_names'][$k]=$p['subpool_name'];
            arsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif ($c=='status_id') {
            $d['browsable']=true;
            $d['charttype']='pie';
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=lang('participant_status');
            $d['title']=lang('participant_statuses');
            $statuses=participant_status__get_statuses();
            $d['value_names']=array();
            foreach ($statuses as $k=>$p) $d['value_names'][$k]=$p['name'];
            krsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif ($c=='subscriptions') {
            $d['browsable']=true;
            $d['charttype']='none';
            $d['type_of_data']='count';
            $d['yname']=lang('count');
            $d['xname']=lang('subscriptions');
            $d['title']=lang('subscriptions');
            $exptypes=load_external_experiment_types("",false);
            $d['value_names']=array();
            foreach ($exptypes as $k=>$p) $d['value_names'][$k]=$p[lang('lang')];
            krsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif (in_array($c,array('last_enrolment','last_profile_update','last_activity',
                                'bymonth_noshowrate'))) {
            $d['charttype']='bars';
            $d['wide']=true;
            if ($c=='bymonth_noshowrate') {
                $d['yname']=lang('share_in_percent');
                $d['type_of_data']='calc';
            } else {
                $d['yname']=lang('count');
                $d['type_of_data']='count';
            }
            $d['xname']=lang('month');
            $d['title']=$titles[$c];
            foreach ($nums as $k=>$number) {
                if ($k=='-') $d['value_names'][$k]=$k;
                else $d['value_names'][$k]=substr($k,4,2).'/'.substr($k,2,2);
            }
            krsort($nums);
            $d['data']=$nums;
            $data_temparray[$c]=$d;
        } elseif (preg_match('/^bymonth_pstatus/',$c)) {
            $tarr=explode("_",$c);
            $status=$tarr[2];
            if (!isset($data_temparray['bymonth_pstatus'])) {
                $pstatuses=expregister__get_participation_statuses();
                $d['charttype']='multibars';
                $d['wide']=true;
                $d['type_of_data']='count';
                $d['yname']=lang('count');
                $d['xname']=lang('month');
                $d['title']=lang('participation_status_count_by_month');
                $d['value_names']=array();
                $d['column_names']=array();
                foreach ($pstatuses as $k=>$s) {
                    $d['column_names'][$k]=$s['internal_name'];
                }
                $data_temparray['bymonth_pstatus']=$d;
            }
            krsort($nums);
            $data_temparray['bymonth_pstatus']['data'][$status]=$nums;
            $d['value_names']=array();
            foreach ($data_temparray['bymonth_pstatus']['data'] as $status=>$months) {
                foreach ($months as $month=>$count) {
                    $d['value_names'][$month]=$month;
                }
            }
            krsort($d['value_names']);
            foreach ($d['value_names'] as $k=>$v) $d['value_names'][$k]=substr($v,4,2).'/'.substr($v,2,2);
            $data_temparray['bymonth_pstatus']['value_names']=$d['value_names'];
        }
    }

    // prepare all-containing array to return
    $data_array=array();

    foreach($statfields as $c) {
        if (isset($data_temparray[$c]['data'])) {
            $data_temparray[$c]['name']=$c;
            $data_array[$c]=$data_temparray[$c];
        } else {
            $data_array[$c]=array('name'=>$c,'N'=>0,'data'=>array());
        }
    }
    return $data_array;
}


function stats__report_display_table($table1,$table1_name,$table2=array(),$table2_name="",$table3=array(),$table3_name="") {
    global $settings, $color;

    $num_tables=1;
    $has_table2=false; $has_table3=false;
    if(is_array($table2) && count($table2)>0) { $has_table2=true; $num_tables++; }
    if(is_array($table3) && count($table3)>0) { $has_table3=true; $num_tables++; }

    $has_data1=true; $has_data2=true; $has_data3=true;
    if(!$table1['data']) { $has_data1=false; }
    if(!$table2['data']) { $has_data2=false; }
    if(!$table3['data']) { $has_data3=false; }

    // order keys
    $allvals=array();
    foreach ($table1['data'] as $k=>$v) $allvals[$k]=$v;
    if ($has_data2) foreach ($table2['data'] as $k=>$v)
        if (!isset($allvals[$k]) || $v>$allvals[$k]) $allvals[$k]=$v;
    if ($has_data3) foreach ($table3['data'] as $k=>$v)
        if (!isset($allvals[$k]) || $v>$allvals[$k]) $allvals[$k]=$v;
    arsort($allvals);
    $usevals=array(); $i=0;
    foreach ($allvals as $k=>$v) {
        if($i<$settings['stats_report_rows_limit']) $usevals[]=$k;
        $i++;
    }

    $out="";
    $out.= '<TABLE class="or_orr_spstatstable"><TR>';
    $out.= '<TD colspan="'.($num_tables+1).'" class="orr_header_title">'.$table1['title'].'</TD></TR>';
    $out.= '<TR><TD class="orr_header_poolnames"></TD>
            <TD class="orr_header_poolnames">'.$table1_name.'</TD>';
    if ($has_table2) $out.= '<TD class="orr_header_poolnames">'.$table2_name.'</TD>';
    if ($has_table3) $out.= '<TD class="orr_header_poolnames">'.$table3_name.'</TD>';
    $out.= '</TR>';
    $out.= '<TR><TD class="orr_header_n"></TD>
            <TD class="orr_header_n">N='.$table1['N'].'</TD>';
    if ($has_table2) $out.= '<TD class="orr_header_n">N='.$table2['N'].'</TD>';
    if ($has_table3) $out.= '<TD class="orr_header_n">N='.$table3['N'].'</TD>';
    $out.= '</TR>';
    $out.= '<TR><TD class="orr_header_varnames">'.$table1['xname'].'</TD>';
    if ($table1['type_of_data']=='count') {
        $out.='<TD class="orr_header_varnames"></TD><TD class="orr_header_varnames"></TD><TD class="orr_header_varnames"></TD>';
    } else {
        $out.='<TD class="orr_header_varnames">'.$table1['yname'].'</TD>';
        if ($has_table2) $out.= '<TD class="orr_header_varnames">'.$table1['yname'].'</TD>';
        if ($has_table3) $out.= '<TD class="orr_header_varnames">'.$table1['yname'].'</TD>';
    }
    $out.= '</TR>';
    $shade=false; $other=array('t1'=>0,'t2'=>0,'t3'=>0);
    foreach ($table1['data'] as $k=>$value) {
        if (in_array($k,$usevals)) {
            $out.= '<TR';
            if ($shade) { $out.= ' class="or_orr_list_shade_even"'; $shade=false; }
            else { $out.= ' class="or_orr_list_shade_odd"'; $shade=true; }
            $out.='>';
            $out.= '<TD>';
            if (isset($table1['value_names'][$k])) $out.= $table1['value_names'][$k];
            else $out.=$k;
            $out.= '</TD>';
            $out.= '<TD>';
                if ($table1['type_of_data']=='count') $out.= round(($value/$table1['N'])*100,1).'%';
                else $out.= $value;
            $out.='</TD>';
            if ($has_table2) {
                $out.= '<TD>';
                if ($has_data2 && $table2['N']>0) {
                    if (isset($table2['data'][$k])) {
                        if ($table1['type_of_data']=='count') $out.= round(($table2['data'][$k]/$table2['N'])*100,1).'%';
                        else $out.= $table2['data'][$k];
                    } else $out.= 0;
                } else $out.= '-';
                $out.= '</TD>';
            }
            if ($has_table3) {
                $out.= '<TD>';
                if ($has_data3 && $table3['N']>0) {
                    if (isset($table3['data'][$k])) {
                        if ($table1['type_of_data']=='count') $out.= round(($table3['data'][$k]/$table3['N'])*100,1).'%';
                        else $out.= $table3['data'][$k];
                    } else $out.= 0;
                } else $out.= '-';
                $out.= '</TD>';
            }
            $out.= '</TR>';
        } else {
            $other['t1']+=$value;
            if (isset($table2['data'][$k])) $other['t2']+=$table2['data'][$k];
            if (isset($table3['data'][$k])) $other['t3']+=$table3['data'][$k];
        }
    }
    if ($other['t1']>0 || $other['t2']>0 || $other['t3']>0) {
        $out.= '<TR';
        if ($shade) { $out.= ' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
        else { $out.= ' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
        $out.='>';
        $out.= '<TD>'.lang('other_categories').'</TD>';
        $out.= '<TD>';
        if ($table1['type_of_data']=='count') $out.= round(($other['t1']/$table1['N'])*100,1).'%';
        else $out.= $other['t1'];
        $out.='</TD>';
        if ($has_table2) {
            $out.= '<TD>';
            if ($has_data2) {
                if ($table1['type_of_data']=='count') $out.= round(($other['t2']/$table2['N'])*100,1).'%';
                else $out.= $other['t2'];
            } else $out.= '-';
            $out.= '</TD>';
        }
        if ($has_table3) {
            $out.= '<TD>';
            if ($has_data3) {
                if ($table1['type_of_data']=='count') $out.= round(($other['t3']/$table3['N'])*100,1).'%';
                else $out.= $other['t3'];
            } else $out.= '-';
            $out.= '</TD>';
        }
        $out.= '</TR>';
    }
    $out.= '</TABLE>';
    return $out;
}


function stats__stats_get_table_array($table) {
    $ta=array();
    $allvals=array();
    if ($table['charttype']=='multibars') {
        $num_cols=count($table['column_names'])+1;
        $multi=true;
        foreach ($table['data'] as $k=>$arr) foreach($arr as $k=>$value) $allvals[]=$k;
        $allvals=array_unique($allvals);
        rsort($allvals);
    } else {
        $multi=false;
        foreach($table['data'] as $k=>$value) $allvals[]=$k;
    }
    if ($multi) {
        $ta['multi_header']=array();
        $ta['multi_header'][]='';
        foreach ($table['column_names'] as $k=>$col) $ta['multi_header'][]=$col;
    }
    $ta['header']=array();
    $ta['header'][]=$table['xname'];
    if ($multi) foreach ($table['column_names'] as $k=>$col) $ta['header'][]=$table['yname'];
    else $ta['header'][]=$table['yname'];
    $ta['data']=array();
    foreach ($allvals as $k) {
        $row=array();
        if (isset($table['value_names'][$k])) $row[]= $table['value_names'][$k];
        else $row[]=$k;
        if ($multi) {
            foreach ($table['column_names'] as $c=>$col) {
                if(isset($table['data'][$c][$k])) $row[]= $table['data'][$c][$k];
                else $row[]= '0';
            }
        } else $row[]= $table['data'][$k];
        $ta['data']['v'.$k]=$row;
    }
    return $ta;
}

function stats__stats_display_table($table,$browsable=false,$restrict=array()) {
    global $color;
    if ($browsable && isset($table['browsable']) && $table['browsable']) $browsable=true;
    else $browsable=false;
    $ta=stats__stats_get_table_array($table);
    $out="";
    $out.= '<TABLE class="or_listtable" style="width: 100%;"><thead>';
    if (isset($ta['multi_header'])) {
        $out.= '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
        if ($browsable) $out.='<TD></TD>';
        foreach ($ta['multi_header'] as $c) $out.='<TD>'.$c.'</TD>';
        $out.= '</TR>';
    }
    if (isset($ta['header'])) {
        $out.= '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
        if ($browsable) $out.='<TD>'.lang('filter_out').'</TD>';
        foreach ($ta['header'] as $c) $out.='<TD>'.$c.'</TD>';
        $out.= '</TR>';
    }
    $out.='</thead><tbody>';
    $shade=false;
    foreach ($ta['data'] as $vk=>$r) {
        $k=substr($vk,1);
        $out.= '<TR';
        if ($shade) { $out.= ' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
        else { $out.= ' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
        if ($browsable && isset($restrict[$table['name']][$k])) {
            $tr=true;
            $style_sn=' style="background: #8B8B8B; font-style: italic;"';
        } else {
             $tr=false;
             $style_sn='';
        }
        $out.='>';
        if ($browsable) {
            $out.='<TD '.$style_sn.'>'.lang('n').
                    '<INPUT type="radio" name="restrict['.$table['name'].']['.$k.']" value="n"';
            if (!$tr) $out.=' CHECKED';
            $out.='><INPUT type="radio" name="restrict['.$table['name'].']['.$k.']" value="y"';
            if ($tr) $out.=' CHECKED';
            $out.='>'.lang('y').'</TD>';
        }
        foreach ($r as $c) $out.= '<TD '.$style_sn.'>'.$c.'</TD>';
        $out.= '</TR>';
    }
    if (isset($restrict[$table['name']]) && $browsable) {
        foreach ($restrict[$table['name']] as $k=>$v) {
            $out.= '<TR style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].'; font-style: italic;">';
            $out.='<TD '.$style_sn.'>'.lang('n').
                    '<INPUT type="radio" name="restrict['.$table['name'].']['.$k.']" value="n">'.
                    '<INPUT type="radio" name="restrict['.$table['name'].']['.$k.']" value="y" CHECKED>'.lang('y').'</TD>';
            if (isset($table['value_names'][$k])) $out.='<TD>'.$table['value_names'][$k].'</TD>';
            else $out.='<TD>'.$k.'</TD>';
            if ($table['charttype']=='multibars') foreach ($table['column_names'] as $c=>$col) $out.='<TD>-</TD>';
            else $out.='<TD>-</TD>';
            $out.= '</TR>';
        }
    }
    $out.= '</tbody></TABLE>';
    return $out;
}


function stats__stats_display_textstats($table) {
    global $settings;
    $ta=stats__stats_get_table_array($table);

    // calculate column lenghts
    $len=array();
    if (isset($ta['multiheader'])) $len=stats__textstats_max_col_width($ta['multiheader'],$len);
    if (isset($ta['header'])) $len=stats__textstats_max_col_width($ta['header'],$len);
    foreach ($ta['data'] as $row) $len=stats__textstats_max_col_width($row,$len);

    $out="";
    $out.="**".$table['title']."\n";
    if (isset($ta['multiheader'])) $out.=stats__textstats_display_row($ta['multiheader'],$len);
    if (isset($ta['header'])) $out.=stats__textstats_display_row($ta['header'],$len);
    foreach ($ta['data'] as $row) $out.=stats__textstats_display_row($row,$len);
    return $out;
}

function stats__textstats_display_row($row,$len) {
    $i=0; $out='';
    $out.='| ';
    foreach ($row as $c) {
        $out.=str_pad($c,$len[$i]," ",STR_PAD_RIGHT).' | ';
        $i++;
    }
    return $out."\n";
}


function stats__textstats_max_col_width($cols,$len) {
    $i=0;
    foreach ($cols as $c) {
        if (!isset($len[$i])) $len[$i]=0;
        $len[$i]=max(strlen($c),$len[$i]);
        $i++;
    }
    return $len;
}

function stats__generate_graph_data_multibars($d) {
    global $lang, $settings;
    $stat=array();

    // titles ect ...
    $stat['xtitle']=$d['xname'];
    $stat['ytitle']=$d['yname'];
    $stat['title']=''; //$d['title'];
    $stat['graphtype']='bars';
    $stat['xsize']=600;
    $stat['legend']=array();
    foreach($d['column_names'] as $k=>$col) $stat['legend'][]=$col;
    //$stat['legend_y']='';

    $data=array();
    $allvals=array();
    foreach ($d['data'] as $k=>$arr) {
        foreach($arr as $k=>$value) $allvals[]=$k;
    }
    $allvals=array_unique($allvals);
    rsort($allvals);
    $i=0;
    //foreach ($allvals as $val) {
    foreach($d['value_names'] as $val=>$dname) {
        if ($i<$settings['stats_stats_rows_limit']) {
            $data[$i][]=$dname;
            foreach($d['column_names'] as $k=>$col) {
                if (isset($d['data'][$k][$val])) $data[$i][]=$d['data'][$k][$val];
                else $data[$i][]=0;
            }
            $i++;
        }
    }
    $stat['data']=$data;
    return $stat;
}

function stats__generate_graph_data($d) {
        global $lang, $settings;
        $stat=array();

        // titles ect ...
        if ($d['charttype']=='pie') {
            $stat['xtitle']='';
            $stat['ytitle']='';
        } else {
            $stat['xtitle']=$d['xname'];
            $stat['ytitle']=$d['yname'];
        }
        $stat['title']=''; //$d['title'];
        $stat['graphtype']=$d['charttype'];
        $stat['legend']=array();
        $stat['legend_y']='';
        if (isset($d['wide']) && $d['wide']) $stat['xsize']=600;

        $data=array();
        if($d['charttype']=='pie') $data[0][]='mmm';
        $names=$d['value_names'];
        $values=$d['data']; $i=0;
        foreach ($values as $k=>$v) {
            $i++;
            //if ($d['charttype']!='bars' || //$i<=$settings['stats_stats_rows_limit'] ||
            //  (isset($d['limit_not_apply']) && $d['limit_not_apply'])) {
                $tname=$k;
                $tname=(isset($names[$k]))?$names[$k]:$k;
                if($d['charttype']=='bars') {
                    $data[]=array($tname,$v);
                } else {
                    $stat['legend'][]=$tname;
                    $data[0][]=$v;
                }
            //}
        }
        $stat['data']=$data;
        return $stat;
}


function stats__get_participant_action_data($months_backward=12) {
    global $lang, $settings;
    $actions=array('subscribe','confirm','edit','delete');
    if (isset($settings['stats_months_backward']) && $settings['stats_months_backward']>0) $months_backward=$settings['stats_months_backward'];

    // titles ect ...
    $d['xname']=lang('month');
    $d['yname']=lang('count');
    $d['title']=lang('participant_actions');
    $d['charttype']='multibars';
    $d['type_of_data']='count';
    $d['column_names']=array();
    foreach ($actions as $action) $d['column_names'][$action]=lang($action);

    // the data
    //first get the stuff from the database
    $nums=array();
    $first_date_unixtime=strtotime("-".$months_backward." month",time());
    $query="SELECT action, date_format(FROM_UNIXTIME(timestamp),'%Y%m') as yearmonth,
            count(log_id) as nractions
            FROM ".table('participants_log')."
            WHERE date_format(FROM_UNIXTIME(timestamp),'%Y%m')>=date_format(FROM_UNIXTIME(".$first_date_unixtime."),'%Y%m')
            AND action IN ('".implode("','",$actions)."')
            GROUP BY action, yearmonth
            ORDER BY timestamp DESC";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        $nums[$line['action']][$line['yearmonth']]=$line['nractions'];
    }
    $d['value_names']=array();
    foreach ($nums as $action=>$months) {
        foreach ($months as $month=>$count) {
            $d['value_names'][$month]=$month;
        }
    }
    krsort($d['value_names']);
    foreach ($d['value_names'] as $k=>$v) $d['value_names'][$k]=substr($v,4,2).'/'.substr($v,2,2);
    $d['data']=$nums;
    return $d;
}


function stats__get_textstats_for_email() {
    $condition=array('clause'=>participant_status__get_pquery_snippet('eligible_for_experiments'),'pars'=>array());
    $stats_data=stats__get_data($condition,'stats',array());
    $tout='';
    foreach ($stats_data as $k=>$table) $tout.=stats__stats_display_textstats($table)."\n\n";
    return $tout;
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

?>