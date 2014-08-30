<?php
// part of orsee. see orsee.org

function query__form_module($module="",$experiment_id="",$deleted='n') {
	global $lang, $settings;
	if(!isset($_REQUEST['new'])) $_REQUEST['new']="";

	if(substr($module,0,6)=='pform:') {
		$fieldname=substr($module,6);
		$module='pform';
	}

switch ($module) {

	case "all":
		break;

	case "experiment_classes":
		if (!isset($_REQUEST['expclass_not'])) $_REQUEST['expclass_not']="";
		echo '<TABLE width=90%>
                                <TR>
                                        <TD>
                                        <SELECT name="expclass_not">
                                        <OPTION value="NOT"';
                                        if ($_REQUEST['expclass_not']=="NOT" || $_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['without'].'</OPTION>
                                        <OPTION value=""';
                                        if ($_REQUEST['expclass_not']!="NOT" && !$_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['only'].'</OPTION>
                                        </SELECT>
                                        '.$lang['participants_participated_expclass'].'
                                        <BR>';
                query__experiment_classes_checkbox_list($experiment_id);
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	case "experiment_assigned_or":
		if (!isset($_REQUEST['inex_ass'])) $_REQUEST['inex_ass']="";
               	echo '<TABLE width=90%>
				<TR>
					<TD>
					<SELECT name="inex_ass">
					<OPTION value="NOT"';
					if ($_REQUEST['inex_ass']=="NOT" || $_REQUEST['new']) echo ' SELECTED';
					echo '>'.$lang['without'].'</OPTION>
					<OPTION value=""';
					if ($_REQUEST['inex_ass']!="NOT" && !$_REQUEST['new']) echo ' SELECTED';
					echo '>'.$lang['only'].'</OPTION>
					</SELECT>
					'.$lang['participants_were_assigned_to'].'
					<BR>';
		query__current_other_experiments_checkbox_list($experiment_id,"exp_ass");
	        echo '			</TD>
				</TR>
	                </TABLE>';
		break;


	case "experiment_participated_and":
				if (!isset($_REQUEST['inex2'])) $_REQUEST['inex2']="";
                echo '<TABLE width=90%>
                                <TR>
                                        <TD>
                                        <SELECT name="inex2">
                                        <OPTION value="NOT"';
                                        if ($_REQUEST['inex2']=="NOT" || $_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['without'].'</OPTION>
                                        <OPTION value=""';
                                        if ($_REQUEST['inex2']!="NOT" && !$_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['only'].'</OPTION>
                                        </SELECT>
                                        '.$lang['participants_participated_all'].'
                                        <BR>';
		query__current_other_experiments_checkbox_list($experiment_id,$formname="exp_and");
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	case "experiment_participated_or":
				if (!isset($_REQUEST['inex'])) $_REQUEST['inex']="";
                echo '<TABLE width=90%>
                                <TR>
                                        <TD>
                                        <SELECT name="inex">
                                        <OPTION value="NOT"';
                                        if ($_REQUEST['inex']=="NOT" || $_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['without'].'</OPTION>
                                        <OPTION value=""';
                                        if ($_REQUEST['inex']!="NOT" && !$_REQUEST['new']) echo ' SELECTED';
                                        echo '>'.$lang['only'].'</OPTION>
                                        </SELECT>
                                        '.$lang['participants_have_participated_on'].'
                                        <BR>';
                query__current_other_experiments_checkbox_list($experiment_id,$formname="exp");
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	case "field":
		if (isset($_REQUEST['field_bezug'])) $bz=$_REQUEST['field_bezug']; else $bz="";
		if (!isset($_REQUEST['query_field'])) $_REQUEST['query_field']="";
		$formfields=participantform__load();
		$form_query_fields=array();
		foreach ($formfields as $f) {
			if( preg_match("/(textline|textarea)/i",$f['type']) &&
				((!$experiment_id && $f['search_include_in_participant_query']=='y')	||
				($experiment_id && 	$f['search_include_in_experiment_assign_query']=='y'))) {
					$tfield=array();
					$tfield['value']=$f['mysql_column_name'];
					if (isset($lang[$f['name_lang']])) $tfield['name']=$lang[$f['name_lang']];
					else $tfield['name']=$f['name_lang']; 
					$form_query_fields[]=$tfield;
				}
		} 
		echo '	<TABLE width=90%>
				<TR>
					<TD> 
						'.$lang['where'].'
	                         		<INPUT type=text size=10 maxlength=30 name=query_field
                                 			value="'.$_REQUEST['query_field'].'">
						'.$lang['in'].'
        	                		<SELECT name="field_bezug">
                	        	<OPTION value="all"'; if ($bz=="all" || $_REQUEST['new']) echo ' SELECTED'; echo '>'.
						$lang['anyone'].'</OPTION>';
						foreach($form_query_fields as $tf) {
                        	echo '<OPTION value="'.$tf['value'].'"'; 
                        	if ($bz==$tf['value']) echo ' SELECTED'; 
                        	echo '>'.$tf['name'].'</OPTION>';
						}
					echo '
                	        		</SELECT>
					</TD>
				</TR>
			</TABLE>';
		break;

	case "pform":
		if (!isset($_REQUEST[$fieldname.'_not'])) $_REQUEST[$fieldname.'_not']="";
		if (!isset($_REQUEST[$fieldname.'_sign'])) $_REQUEST[$fieldname.'_sign']="";
		if (!isset($_REQUEST[$fieldname])) $_REQUEST[$fieldname]="";
		
		// $existing=true;
		//if ($experiment_id) $show_count=false; else $show_count=true;
		// needs to much time for queries. So  better:
		$existing=false; $show_count=false;
		
		$formfields=participantform__load(); $f=array();
		foreach ($formfields as $p) { if($p['mysql_column_name']==$fieldname) $f=$p; }
		$f=form__replace_funcs_in_field($f);
		if (isset($f['mysql_column_name'])) {
			echo '<TABLE width=90%>
					<TR>
					<TD>'.$lang['where'].' ';
			if(isset($lang[$f['name_lang']])) echo  $lang[$f['name_lang']]; else echo $f['name_lang'];
			echo ' ';
			if ($f['type']=='select_numbers') {
				echo '<select name="'.$fieldname.'_sign">
                      <OPTION value="<="'; if ($_REQUEST[$fieldname.'_sign']=="<=") echo ' SELECTED'; echo '><=</OPTION>
					  <OPTION value="="'; if ($_REQUEST[$fieldname.'_sign']=="=" || $_REQUEST[$fieldname.'_sign']=="" || $_REQUEST['new']) echo ' SELECTED'; echo '>=</OPTION>
                      <OPTION value=">"'; if ($_REQUEST[$fieldname.'_sign']==">") echo ' SELECTED'; echo '>></OPTION>
					  </select>';							
			} else {
				echo '<select name="'.$fieldname.'_not">
				 	<OPTION value=""'; if (!$_REQUEST[$fieldname.'_not']) echo ' SELECTED'; echo '>=</OPTION>
				 	<OPTION value="!"'; if ($_REQUEST[$fieldname.'_not']) echo ' SELECTED';	echo '>'.$lang['not'].' =</OPTION>
				 	</select> ';
			}

			if ($f['type']=='select_lang') {
				echo language__selectfield_item($fieldname,$fieldname,$_REQUEST[$fieldname],false,"",$existing,"deleted='".$deleted."'",$show_count);
			} elseif ($f['type']=='select_numbers') {
				if ($f['values_reverse']=='y') $reverse=true; else $reverse=false;
        		echo participant__select_numbers($fieldname,$_REQUEST[$fieldname],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,false,$existing,"deleted='".$deleted."'",$show_count);
			} elseif (preg_match("/(select_list|radioline)/i",$f['type']) && !$existing) {
				$f['value']=$_REQUEST[$fieldname];
				echo  form__render_select_list($f);
			} else {
				echo participant__select_existing($fieldname,$_REQUEST[$fieldname],"deleted='".$deleted."'",$show_count);
			}

	    	echo '			</TD>
					</TR>
	            	    </TABLE>';
	    }
		break;

	case "noshowups":
		$query="SELECT max(number_noshowup) as maxnoshow FROM ".table('participants')."
			WHERE deleted='n' ORDER BY number_noshowup DESC LIMIT 1";
		$line=orsee_query($query);
		if (!isset($_REQUEST['query_noshowups'])) $_REQUEST['query_noshowups']=0;
               	echo '<TABLE width=90%>
				<TR>
					<TD>
						'.$lang['where_nr_noshowups_is'].'
				 		<select name="query_noshowups_sign">
                                 			<OPTION value="<="'; 
						if (!isset($_REQUEST['query_noshowups_sign']) || $_REQUEST['query_noshowups_sign']!=">") echo ' SELECTED';
                                                        echo '><=</OPTION>
                                 			<OPTION value=">""'; 
						if (isset($_REQUEST['query_noshowups_sign']) && $_REQUEST['query_noshowups_sign']==">") echo ' SELECTED';
                                                        echo '>></OPTION>
                                 		</select> ';
				 		helpers__select_numbers("query_noshowups",
							$_REQUEST['query_noshowups'],
							0,$line['maxnoshow'],0);
	        echo '			</TD>
				</TR>
	                </TABLE>';
		break;

        case "nr_participations":
                $query="SELECT max(number_reg) as maxnumreg FROM ".table('participants')."
                        WHERE deleted='n' ORDER BY number_reg DESC LIMIT 1";
                $line=orsee_query($query);
                if (!isset($_REQUEST['query_nr_participations'])) $_REQUEST['query_nr_participations']=0;
                echo '<TABLE width=90%>
                                <TR>
                                        <TD>
                                                '.$lang['where_nr_participations_is'].'
                                                <select name="query_nr_participations_sign">
                                                        <OPTION value="<="';
                                           if (!isset($_REQUEST['query_nr_participations_sign']) || $_REQUEST['query_nr_participations_sign']!=">") echo ' SELECTED';
                                                        echo '><=</OPTION>
                                                        <OPTION value=">""';
                                           if (isset($_REQUEST['query_nr_participations_sign']) && $_REQUEST['query_nr_participations_sign']==">") echo ' SELECTED';
                                                        echo '>></OPTION>
                                                </select> ';
                                                helpers__select_numbers("query_nr_participations",
                                                        $_REQUEST['query_nr_participations'],
                                                        0,$line['maxnumreg'],0);
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	case "rand_subset":
		$query_limit = (!isset($_REQUEST['query_limit']) ||!$_REQUEST['query_limit']) ? $settings['query_random_subset_default_size'] : $_REQUEST['query_limit'];
                echo '	<TABLE width=90%>
				<TR>
					<TD>
						'.$lang['limit_to_randomly_drawn'].'<BR>
						<INPUT type=text name="query_limit" value="'.
							$query_limit.'" size=5 maxlength=6>
	                		</TD>
				</TR>
	                </TABLE>';
		break;

        case "subjectpool":
        		if (!isset($_REQUEST['subjectpool_not'])) $_REQUEST['subjectpool_not']="";
        		if (!isset($_REQUEST['query_subjectpool'])) $_REQUEST['query_subjectpool']="";
                echo '  <TABLE width=90%>
                                <TR>
                                        <TD>
                                                '.$lang['who_are_in_subjectpool'].'
                                                <select name="subjectpool_not">
                                                        <OPTION value=""';
                                                                if (!$_REQUEST['subjectpool_not']) 
                                                                        echo ' SELECTED';
                                                                echo '></OPTION>
                                                        <OPTION value="!"';
                                                                if ($_REQUEST['subjectpool_not']) 
                                                                        echo ' SELECTED';
                                                                echo '>'.$lang['not'].'</OPTION>
                                                </select> ';
						echo subpools__select_field('query_subjectpool','subpool_id',
								'subpool_name',$_REQUEST['query_subjectpool']);
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	}

}


function query__where_clause_module($module="",$experiment_id="") {
	$where_clause="";
	if(substr($module,0,6)=='pform:') {
		$fieldname=substr($module,6);
		$module='pform';
	}

switch ($module) {

        case "all":
		$where_clause=" AND ".table('participants').".participant_id IS NOT NULL";
		break;

	case "experiment_classes":
                $class_posted=array(); $class_part=array(); $participants=array();
                $class_posted= (isset($_REQUEST['expclass']) && $_REQUEST['expclass']) ? $_REQUEST['expclass'] : array();
                if (count($class_posted) > 0) {
                                foreach ($class_posted as $class) if ($class) $class_part[]=$class;
                        } else {
                                $class_part=array();
                                }
                if (count($class_part) > 0) {
                        $wclause="";
                        $first=true;
                        foreach ($class_part as $class) {
                                if ($first==true) { $wclause.=" AND ( "; $first=false; } else $wclause.=" OR ";
                                $wclause.="experiment_class = '".$class."'";
                                $part_class[$class]=array();
                                }
                        if ($wclause) $wclause.=" )";

                        $query="SELECT DISTINCT participant_id 
								FROM ".table('participate_at').", ".table('experiments')."
								WHERE participated='y' 
								AND ".table('participate_at').".experiment_id=".table('experiments').".experiment_id 
								".$wclause." ORDER BY participant_id";
                        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

                        while ($line = mysqli_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['expclass_not'].
                                " IN ('".$in_phrase."') ";
                break;

	case "experiment_assigned_or":
		$exp_posted=array(); $exp_part=array(); $participants=array();
		if(isset($_REQUEST['exp_ass'])) $exp_posted= ($_REQUEST['exp_ass']);
                if (count($exp_posted) > 0) {
                                foreach ($exp_posted as $exp) if ($exp) $exp_part[]=$exp;
                        } else {
                                $exp_part=array();
                                }
                if (count($exp_part) > 0) {
                        $wclause="";
                        $first=true;
                        foreach ($exp_part as $exp) {
                                if ($first==true) { $wclause.=" AND ( "; $first=false; } else $wclause.=" OR ";
                                $wclause.="experiment_id = '".$exp."'";
                                $part_exp[$exp]=array();
                                }
                        if ($wclause) $wclause.=" )";

                        $query="SELECT DISTINCT participant_id FROM ".table('participate_at')."
                                WHERE experiment_id IS NOT NULL ".$wclause." ORDER BY participant_id";
                        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

                        while ($line = mysqli_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['inex_ass'].
                                " IN ('".$in_phrase."') ";
                break;

	case "experiment_participated_and":
                $exp_posted=array(); $exp_part=array(); $participants=array();
                if(isset($_REQUEST['exp_and'])) $exp_posted=$_REQUEST['exp_and'];
		if (count($exp_posted) > 0) {
				foreach ($exp_posted as $exp) if ($exp) $exp_part[]=$exp;
			} else {
				$exp_part=array();
				}
		if (count($exp_part) > 0) {
                	$wclause="";
			$first=true;
                	foreach ($exp_part as $exp) {
				if ($first==true) { $wclause.=" AND ( "; $first=false; } else $wclause.=" OR ";
				$wclause.="experiment_id = '".$exp."'";
				$part_exp[$exp]=array();
				}
			if ($wclause) $wclause.=" )";

                	$query="SELECT DISTINCT participant_id, experiment_id FROM ".table('participate_at')."
                        	WHERE participated='y' ".$wclause." ORDER BY participant_id";
                	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                	while ($line = mysqli_fetch_assoc($result)) $part_exp[$line['experiment_id']][]=$line['participant_id'];
		
			if (count($exp_part)<2) {
				$participants=$part_exp[$exp_part[0]];
			 } else {
				$intersect_function='$participants=array_intersect(';
				$first=true;
				foreach ($exp_part as $exp) {
					if ($first) $first=false; else $intersect_function.=',';
					$intersect_function.='$part_exp['.$exp.']';
					}
				$intersect_function.=');';
				eval($intersect_function);
				}
		 } else {
			$participants=array();
			}

                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['inex2'].
                                " IN ('".$in_phrase."') ";
                break;

	case "experiment_participated_or":
		$exp_posted=array(); $exp_part=array(); $participants=array();
                if(isset($_REQUEST['exp'])) $exp_posted=$_REQUEST['exp'];
                if (count($exp_posted) > 0) {
                                foreach ($exp_posted as $exp) if ($exp) $exp_part[]=$exp;
                        } else {
                                $exp_part=array();
                                }
		if (count($exp_part) > 0) {
                	$wclause="";
                        $first=true;
                        foreach ($exp_part as $exp) {
                                if ($first==true) { $wclause.=" AND ( "; $first=false; } else $wclause.=" OR ";
                                $wclause.="experiment_id = '".$exp."'";
                                $part_exp[$exp]=array();
                                }
                        if ($wclause) $wclause.=" )";

                	$query="SELECT DISTINCT participant_id FROM ".table('participate_at')."
                        	WHERE participated='y' ".$wclause." ORDER BY participant_id";
                	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

                	while ($line = mysqli_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['inex'].
                                " IN ('".$in_phrase."') ";
                break; 


	case "field":
		$qval=trim($_REQUEST['query_field']);
		
		if ($_REQUEST['field_bezug']=='all') {
			$formfields=participantform__load();
			$form_query_fields=array();
			foreach ($formfields as $f) {
				if( preg_match("/(textline|textarea)/i",$f['type']) &&
					((!$experiment_id && $f['search_include_in_participant_query']=='y')	||
					($experiment_id && 	$f['search_include_in_experiment_assign_query']=='y'))) {
					$form_query_fields[]=" ".table('participants').".".$f['mysql_column_name']." like '%".$qval."%' ";
				}
			}
			$where_clause="(".implode(" OR ",$form_query_fields).")"; 
		} else {
				if (!$qval) $where_clause="(".table('participants').".".$_REQUEST['field_bezug']."='' OR ".table('participants').".".$_REQUEST['field_bezug']." IS NULL)";
                else $where_clause=table('participants').".".$_REQUEST['field_bezug']." like '%".$qval."%'";
		};
		break; 
		

	case "pform":
		if (!isset($_REQUEST[$fieldname.'_not'])) $_REQUEST[$fieldname.'_not']="";
		if (!isset($_REQUEST[$fieldname.'_sign'])) $_REQUEST[$fieldname.'_sign']="=";
		if (!isset($_REQUEST[$fieldname])) $_REQUEST[$fieldname]="";
		$formfields=participantform__load(); $f=array();
		foreach ($formfields as $p) { if($p['mysql_column_name']==$fieldname) $f=$p; }
		if (isset($f['mysql_column_name'])) {
			if ($f['type']=='select_numbers') {
				$where_clause=table('participants').".".$fieldname." ".$_REQUEST[$fieldname.'_sign'].
					" '".mysqli_real_escape_string($GLOBALS['mysqli'],$_REQUEST[$fieldname])."'";
			} else {
				$where_clause=table('participants').".".$fieldname." ".$_REQUEST[$fieldname.'_not']."= '".
					mysqli_real_escape_string($GLOBALS['mysqli'],$_REQUEST[$fieldname])."'";
			}
	    } else $where_clause="";
		break;
		
	case "noshowups":
		$where_clause=table('participants').".number_noshowup ".$_REQUEST['query_noshowups_sign'].
					" '".$_REQUEST['query_noshowups']."'";
		break;

        case "nr_participations":
                $where_clause=table('participants').".number_reg ".$_REQUEST['query_nr_participations_sign'].
                                        " '".$_REQUEST['query_nr_participations']."'";
                break;

	case "rand_subset":
		// done in query__orderlimit_module
		break;	

        case "subjectpool":
                $where_clause=table('participants').".subpool_id ".$_REQUEST['subjectpool_not']."= '".
                                        $_REQUEST['query_subjectpool']."'";
                break;
	}

return $where_clause;

}

function query__orderlimit_module($module) {
	$orderlimit="";

	switch ($module) {

	case "rand_subset":
        	$limit = (int) $_REQUEST['query_limit'];
                if ($limit && $limit > 0) {
			mt_srand((double)microtime()*1000000);
			$now=mt_rand();
        		$orderlimit="ORDER BY rand(".$now.") LIMIT ".$limit." ";
			}
		break;
	}
	return $orderlimit;
}

function query__join_assign_module($module) {
	$join_clause="";

	return $join_clause;
}

function query__get_participant_form_modules($query_modules,$experiment_id="") {
	global $lang;
	$return_array=array();
	foreach($query_modules as $module) {
		if ($module!='participant_form_fields') $return_array[]=$module;
		else {
			$formfields=participantform__load();
			foreach ($formfields as $f) {
				if( (!preg_match("/(textline|textarea)/i",$f['type'])) &&
					( ((!$experiment_id)	&& $f['search_include_in_participant_query']=='y') ||
					  ($experiment_id && $f['search_include_in_experiment_assign_query']=='y')
					)  ) $return_array[]="pform:".$f['mysql_column_name'];
			}
		} 
	}
	return $return_array;
}

?>
