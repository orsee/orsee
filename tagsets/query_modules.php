<?php

// participant query modules. part of orsee. see orsee.org.

function query__form_module($module="",$experiment_id="") {
	global $lang, $settings;
switch ($module) {

	case "all":
		break;

	case "experiment_classes":
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
		$bz=$_REQUEST['field_bezug'];
		echo '	<TABLE width=90%>
				<TR>
					<TD> 
						'.$lang['where'].'
	                         		<INPUT type=text size=10 maxlength=30 name=query_field
                                 			value="'.$_REQUEST['query_field'].'">
						'.$lang['in'].'
        	                		<SELECT name="field_bezug">
                	        	<OPTION value="all"'; if ($bz=="all" || $_REQUEST['new']) echo ' SELECTED'; echo '>'.
						$lang['anyone'].'</OPTION>
                        		<OPTION value="fname"'; if ($bz=="fname") echo ' SELECTED'; echo '>'.
						$lang['firstname'].'</OPTION>
	                        	<OPTION value="lname"'; if ($bz=="lname") echo ' SELECTED'; echo '>'
						.$lang['lastname'].'</OPTION>
        	                	<OPTION value="email"'; if ($bz=="email") echo ' SELECTED'; echo '>'.
						$lang['e-mail-address'].'</OPTION>
                	        		</SELECT>
					</TD>
				</TR>
			</TABLE>';
		break;

	case "field_of_studies":
		echo '	<TABLE width=90%>
				<TR>
					<TD>
				 		'.$lang['where_field_of_studies_is'].'
				 		<select name="field_of_studies_not">
				 			<OPTION value=""';
                                        			if (!$_REQUEST['field_of_studies_not']) 
									echo ' SELECTED';
                                        			echo '></OPTION>
				 			<OPTION value="!"';
								if ($_REQUEST['field_of_studies_not']) 
									echo ' SELECTED';
								echo '>'.$lang['not'].'</OPTION>
				 		</select> ';

				 		select__field_of_studies($_REQUEST['field_of_studies'],
										"field_of_studies");
	        echo '			</TD>
				</TR>
	                </TABLE>';
		break;

        case "profession":
                echo '  <TABLE width=90%>
                                <TR>
                                        <TD>
                                                '.$lang['where_profession_is'].'
                                                <select name="profession_not">
                                                        <OPTION value=""';
                                                                if (!$_REQUEST['profession_not']) echo ' SELECTED';
                                                                echo '></OPTION>
                                                        <OPTION value="!"';
                                                                if ($_REQUEST['profession_not']) echo ' SELECTED';
                                                                echo '>'.$lang['not'].'</OPTION>
                                                </select> ';

                                                select__profession($_REQUEST['profession'],"profession");
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	case "gender":
                echo '	<TABLE width=90%>
				<TR>
					<TD>
				 		'.$lang['where_gender_is'].'
        	                		<SELECT name="query_gender">
						<OPTION value="m"'; if ($_REQUEST['query_gender']!="f") echo ' SELECTED'; 
							echo '>'.$lang['gender_m'].'</OPTION>
	                        		<OPTION value="f"'; if ($_REQUEST['query_gender']=="f") echo ' SELECTED';
                                       			echo '>'.$lang['gender_f'].'</OPTION>
                	        		</SELECT>
	                		</TD>
				</TR>
	                </TABLE>';
		break;


	case "noshowups":
		$query="SELECT max(number_noshowup) as maxnoshow FROM ".table('participants')."
			WHERE deleted='n' ORDER BY number_noshowup DESC LIMIT 1";
		$line=orsee_query($query);
               	echo '<TABLE width=90%>
				<TR>
					<TD>
						'.$lang['where_nr_noshowups_is'].'
				 		<select name="query_noshowups_sign">
                                 			<OPTION value="<="'; 
						if ($_REQUEST['query_noshowups_sign']!=">") echo ' SELECTED';
                                                        echo '><=</OPTION>
                                 			<OPTION value=">""'; 
						if ($_REQUEST['query_noshowups_sign']==">") echo ' SELECTED';
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
                echo '<TABLE width=90%>
                                <TR>
                                        <TD>
                                                '.$lang['where_nr_participations_is'].'
                                                <select name="query_nr_participations_sign">
                                                        <OPTION value="<="';
                                           if ($_REQUEST['query_nr_participations_sign']!=">") echo ' SELECTED';
                                                        echo '><=</OPTION>
                                                        <OPTION value=">""';
                                           if ($_REQUEST['query_nr_participations_sign']==">") echo ' SELECTED';
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
		$query_limit = (!$_REQUEST['query_limit']) ? $settings['query_random_subset_default_size'] : $_REQUEST['query_limit'];
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

	case "study_start":

              	echo '	<TABLE width=90%>
				<TR>
					<TD>
				 		'.$lang['where_study_start_in_year'].'
                                                <select name="query_study_start_sign">
                                                        <OPTION value="<="'; if ($_REQUEST['query_study_start_sign']=="<=") echo ' SELECTED';
                                                        		echo '><=</OPTION>
							<OPTION value="="'; 
							if ($_REQUEST['query_study_start_sign']=="=" || $_REQUEST['new']) echo ' SELECTED';
                                                                        echo '>=</OPTION>
                                                        <OPTION value=">"'; if ($_REQUEST['query_study_start_sign']==">") echo ' SELECTED';
                                                                        echo '>></OPTION>
                                                </select> 

        	                		<SELECT name=query_study_start>';
                		$query="SELECT DISTINCTROW begin_of_studies
                        		FROM ".table('participants')."
                        		ORDER BY begin_of_studies";
				$result=mysql_query($query) or die("Database error: " . mysql_error());

				while ($line = mysql_fetch_assoc($result)) {
	                        	echo '<OPTION value="'.$line['begin_of_studies'].'"';
						if ($_REQUEST['query_study_start']==$line['begin_of_studies']) echo ' SELECTED';
						echo '>'.$line['begin_of_studies'].'</OPTION>';
					}
                	        echo '		</SELECT>
	                		</TD>
				</TR>
	                </TABLE>';
		break;

        case "subjectpool":
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
						subpools__select_field('query_subjectpool','subpool_id',
								'subpool_name',$_REQUEST['query_subjectpool']);
                echo '                  </TD>
                                </TR>
                        </TABLE>';
                break;

	}

}


function query__where_clause_module($module="") {
	$where_clause="";

switch ($module) {

        case "all":
		$where_clause=" AND ".table('participants').".participant_id IS NOT NULL";
		break;

	case "experiment_classes":
                $class_posted=array(); $class_part=array(); $participants=array();
                $class_posted= ($_REQUEST['expclass']) ? $_REQUEST['expclass'] : array();
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
                                WHERE participated='y' ".$wclause." ORDER BY participant_id";
                        $result=mysql_query($query) or die("Database error: " . mysql_error());

                        while ($line = mysql_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['expclass_not'].
                                " IN ('".$in_phrase."') ";
                break;

	case "experiment_assigned_or":
		$exp_posted=array(); $exp_part=array(); $participants=array();
		$exp_posted= ($_REQUEST['exp_ass']) ? $_REQUEST['exp_ass'] : array();
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
                        $result=mysql_query($query) or die("Database error: " . mysql_error());

                        while ($line = mysql_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['inex_ass'].
                                " IN ('".$in_phrase."') ";
                break;

	case "experiment_participated_and":
                $exp_posted=array(); $exp_part=array(); $participants=array();
                $exp_posted=$_REQUEST['exp_and'];
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
                	$result=mysql_query($query) or die("Database error: " . mysql_error());
                	while ($line = mysql_fetch_assoc($result)) $part_exp[$line['experiment_id']][]=$line['participant_id'];
		
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
                $exp_posted=$_REQUEST['exp'];
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
                	$result=mysql_query($query) or die("Database error: " . mysql_error());

                	while ($line = mysql_fetch_assoc($result)) $participants[]=$line['participant_id'];
                 } else {
                        $participants=array();
                        }
                $in_phrase=implode("','",$participants);

                $where_clause=" ".table('participants').".participant_id ".$_REQUEST['inex'].
                                " IN ('".$in_phrase."') ";
                break; 


	case "field":
		if ($_REQUEST['query_field']) {
			$qval=$_REQUEST['query_field'];
			switch ($_REQUEST['field_bezug']) {
        			case "email":
				case "fname":
				case "lname":
                   			$where_clause=table('participants').".".$_REQUEST['field_bezug']." like '%".$qval."%'";
					break;
        			case "all":
		   			$where_clause="(".table('participants').".fname like '%".$qval."%' OR 
							".table('participants').".lname like '%".$qval."%' OR
							".table('participants').".email like '%".$qval."%')"; 
					break;
				}
			}
		break;


	case "field_of_studies":
		$where_clause=table('participants').".field_of_studies ".$_REQUEST['field_of_studies_not']."= '".
					$_REQUEST['field_of_studies']."'";
		break;

        case "profession":
                $where_clause=table('participants').".profession ".$_REQUEST['profession_not']."= '".
                                        $_REQUEST['profession']."'";
                break;

	case "gender":
		$where_clause=table('participants').".gender = '".$_REQUEST['query_gender']."'"; 
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
		/* do we do this somewhere else?
		$limit = int ($_REQUEST['query_limit']);
		if ($limit && $limit > 0) {
                        $query="UPDATE ".table('participants')." SET rand_string=concat(rand())";
			$done=mysql_query($query) or die("Database error: " . mysql_error());
                        $query="SELECT rand_string FROM ".table('participants')."
                                ORDER BY rand_string LIMIT ".$limit.",1";
			$line=orsee_query($query);
		    	$where_clause=table('participants').".rand_string < '".$line['rand_string']."'";
			}
		*/
		break;	


	case "study_start":
		$where_clause=table('participants').".begin_of_studies ".$_REQUEST['query_study_start_sign'].
					" '".$_REQUEST['query_study_start']."'";
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

	echo $module;

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
?>
