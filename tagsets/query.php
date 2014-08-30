<?php
// part of orsee. see orsee.org

function query__form($query_modules,$experiment=array()) {
	global $lang, $color;
	if (is_array($experiment) && isset($experiment['experiment_id']) && $experiment['experiment_id']) $experiment_id=$experiment['experiment_id']; else $experiment_id="";
	echo '	<TABLE border=0 width=90%>
			<TR>
				<TD colspan=3 align=left>
					<TABLE width=100% border=0>
					<TR><TD align=left>
					'.$lang['query_select_all'].'
					</TD><TD align=right>
					<A HREF="'.thisdoc().'?new=true';
					if ($experiment_id) echo '&experiment_id='.$experiment_id;
					echo '">'.$lang['reset_query_form'].'</A>
					</TD></TR>
					</TABLE>
				</TD>
			</TR>';
	$i=0;
	if (isset($_REQUEST['use'])) $lused=$_REQUEST['use']; else $lused=array();
	if (isset($_REQUEST['con'])) $lcons=$_REQUEST['con']; else $lcons=array();
	foreach ($query_modules as $module) {
		echo '	<TR bgcolor="'.$color['list_shade1'].'">
				<TD valign=middle align=center>
					'.($i+1).'. 
					<INPUT type=checkbox name="use['.$i.']" value=true';
					if (isset($lused[$i]) && $lused[$i]) echo ' CHECKED';
					echo '>
				</TD>
				<TD>';
		if ($i != 0 && $module != "rand_subset") { 
			echo '		<SELECT name="con['.$i.']">
					<OPTION value="AND"';
						if (isset($lcons[$i]) && $lcons[$i] != "OR") echo ' SELECTED'; echo '>'.$lang['and'].'</OPTION>
					<OPTION value="OR"';
                                                if (isset($lcons[$i]) && $lcons[$i] == "OR") echo ' SELECTED'; echo '>'.$lang['or'].'</OPTION>
					</SELECT>';
					}
		echo '		</TD>
				<TD>';
					query__form_module($module,$experiment_id);
		echo '		</TD>
			</TR>';
		$i=$i+1;
		}

	echo '		<TR>
				<TD colspan=3 align=right>
					<INPUT type=submit name="show" value="'.$lang['search_and_show'].'">
				</TD>
			</TR>
		</TABLE>';
}


function query__where_clause($query_modules,$use,$con,$experiment="") {
	$query__where_clause="";
	$first_where=true;
	$i=0;
	foreach ($query_modules as $module) {
		if (isset($use[$i]) && $use[$i]) {
			$current_where=query__where_clause_module($module,$experiment);
			if ($current_where) {
				$query__where_clause.=" ";
				if ($first_where) $query__where_clause.="AND ("; else $query__where_clause.=$con[$i];
				$query__where_clause.=" (".$current_where.") ";
				$first_where=false;
				}
			}
		$i=$i+1;
	}
	if($query__where_clause) $query__where_clause.=")";
	return $query__where_clause;
}

function query__join_assign($experiment,$query_modules,$use) {

	$query__join_phrase="  LEFT JOIN ".table('participate_at')." ON
                               ".table('participants').".participant_id = ".table('participate_at').".participant_id
                               AND (".table('participate_at').".experiment_id ='".$experiment['experiment_id']."' ";
	$i=0;
	foreach ($query_modules as $module) {
		if (isset($use[$i]) && $use[$i]) $query__join_phrase.=query__join_assign_module($module);
		$i=$i+1;
        	}
	$query__join_phrase.=	") WHERE ".table('participate_at').".participant_id IS NULL ";

	$query__join_phrase.=" AND ".table('participants').".subscriptions LIKE '%".
				$experiment['experiment_ext_type']."%'"; 
	$query__join_phrase.=" AND ".table('participants').".deleted='n'";
	return $query__join_phrase;
}

function query__orderlimit($query_modules,$use) {
        $query__orderlimit="ORDER BY lname, fname, participant_id";
		$i=0;
        foreach ($query_modules as $module) {
        	if (isset($use[$i]) && $use[$i]) {
        		$current_order=query__orderlimit_module($module);
              	if ($current_order) $query__orderlimit=$current_order;
           	}
		$i=$i+1;
        }
	return $query__orderlimit;
}


function query__current_other_experiments_checkbox_list($experiment_id="",$formname="exp_ass") {
		global $lang, $settings, $color;

		$sort_order="time,experiment_name";

		$experiments=array(); $exp_ids=array();

		if (preg_match("/^(exp|exp_and)$/",$formname)) $add_wc=" AND participated='y' ";
			else $add_wc="";

                $query="SELECT ".table('experiments').".*
                        FROM ".table('experiments').", ".table('participate_at')."
                        WHERE ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
                        AND ".table('experiments').".experiment_id!='".$experiment_id."'
			".$add_wc." 
                        GROUP BY experiment_id
                        ORDER BY experiment_id";
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		while ($line=mysqli_fetch_assoc($result)) {
			$experiments[$line['experiment_id']]=$line;
			$experiments[$line['experiment_id']]['time']="";
			$exp_ids[]=$line['experiment_id'];
			}
		$exp_ids_string=implode("','",$exp_ids);

		// get session times
		$query="SELECT *,
			min(session_start_year*100000000 +
                	session_start_month*1000000 +
                	session_start_day*10000 +
                	session_start_hour*100 +
                	session_start_minute) as time
                        FROM ".table('sessions')."
                        WHERE experiment_id IN ('".$exp_ids_string."')
                        AND session_id>0
                        GROUP BY experiment_id
                        ORDER BY experiment_id";
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                while ($line=mysqli_fetch_assoc($result)) {
			$experiments[$line['experiment_id']]['time']=1000000000000-$line['time'];
			$experiments[$line['experiment_id']]['timearray']=time__load_session_time($line);
			}

		multi_array_sort($experiments,$sort_order);
		if(isset($_REQUEST[$formname])) $posted=$_REQUEST[$formname]; else $posted="";
		$nr_experiments=count($experiments);

		if (isset($_REQUEST['restrict_'.$formname]) && $_REQUEST['restrict_'.$formname]) { $_REQUEST['extended_'.$formname]='';
							$_REQUEST['extend_'.$formname]='';
							$_REQUEST['restrict_'.$formname]='';
							$_SESSION['assign_request']['extended_'.$formname]='';
							$_SESSION['assign_request']['extend_'.$formname]='';
							$_SESSION['assign_request']['restrict_'.$formname]='';
							}
		if (isset($_REQUEST['extend_'.$formname]) && $_REQUEST['extend_'.$formname]) { $_REQUEST['extended_'.$formname]='true';
                                                        $_REQUEST['extend_'.$formname]='';
                                                        $_REQUEST['restrict_'.$formname]='';
                                                        $_SESSION['assign_request']['extended_'.$formname]='';
                                                        $_SESSION['assign_request']['extend_'.$formname]='';
                                                        $_SESSION['assign_request']['restrict_'.$formname]='';
                                                        }
		if (!isset($_REQUEST['extended_'.$formname]) || !$_REQUEST['extended_'.$formname]) {
			$i=0;
			foreach ($experiments as $key=>$value) {
				$i++;
				if ($i > $settings['query_number_exp_limited_view']) unset($experiments[$key]);
				}
			}

                $i=0;

		if(isset($_REQUEST[$formname])) $posted=$_REQUEST[$formname]; else $posted="";
		if(!isset($_REQUEST['extended_'.$formname])) $_REQUEST['extended_'.$formname]="";

		$cols=$settings['query_experiment_list_nr_columns'];
		$ccol=1;

		$shade=false;
		echo '<TABLE width=100% cellspacing=0 cellpadding=0><TR bgcolor="'.$color['list_shade1'].'">';
                foreach ($experiments as $exp) {
                        echo '<TD class="small">
							<INPUT class="small" type=checkbox name="'.$formname.'['.$i.']" value="'.$exp['experiment_id'].'"';
			if (isset($posted[$i]) && $posted[$i]) echo " CHECKED";
			echo '>'.$exp['experiment_name'].' (';
			if ($exp['experimenter']) echo $exp['experimenter'].',';
			if ($exp['time']) {
				echo time__format($lang['lang'],$exp['timearray'],false,true,true,false);
				}
			   else {
				echo '???';
				}
			echo ') 
                                </TD>';
			if ($ccol==$cols) { 
				$ccol=1; 
				echo '</TR><TR bgcolor="';
				if ($shade==true) echo $color['list_shade1']; else echo $color['list_shade2'];
				echo '">'; 
				if ($shade==true) $shade=false; else $shade=true;
				} else $ccol=$ccol+1; 
                        $i=$i+1;
                        }
		if ($ccol>1) {
			while ($ccol <= $cols) {
				echo '<TD></TD>';
				$ccol=$ccol+1;
				}
			echo '</TR><TR>';
			}
		echo '<TD colspan='.$cols.' class="small" align=right>
			<INPUT type=hidden name="extended_'.$formname.'" value="'.$_REQUEST['extended_'.$formname].'">';
		if ($nr_experiments > $settings['query_number_exp_limited_view']) {
			if ($_REQUEST['extended_'.$formname])
				echo '<INPUT class="small" type=submit name="restrict_'.$formname.'" value="restrict list">';
			else
				echo '<INPUT class="small" type=submit name="extend_'.$formname.'" value="extend list">';
			}
		echo '</TD></TR></TABLE>';
}

function query__experiment_classes_checkbox_list($experiment_id="") {
                global $lang, $settings, $color;

				$classes=array();
                $query="SELECT ".table('lang').".*
                        FROM ".table('experiments').", ".table('lang')."
                        WHERE ".table('experiments').".experiment_class=".table('lang').".content_name
                        AND ".table('lang').".content_type='experimentclass' 
                        AND ".table('lang').".content_name!='0' 
                        AND ".table('experiments').".experiment_id!='".$experiment_id."'
						GROUP BY content_name 
                        ORDER BY ".$lang['lang'];
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                while ($line=mysqli_fetch_assoc($result)) {
                        $classes[$line['content_name']]=$line;
                        }

                $nr_classes=count($classes);
                $i=0;

                $cols=$settings['query_experiment_classes_list_nr_columns'];
                $ccol=1;

		if (isset($_REQUEST['expclass'])) $posted=$_REQUEST['expclass']; else $posted="";

                $shade=false;
                echo '<TABLE width=100% cellspacing=0 cellpadding=0><TR bgcolor="'.$color['list_shade1'].'">';
                foreach ($classes as $class) {
                        echo '<TD class="small">
                                <INPUT class="small" type=checkbox name="expclass['.$i.']" value="'.
					$class['content_name'].'"';
                        if (isset($posted[$i]) && $posted[$i]) echo " CHECKED";
                        echo '>'.$class[$lang['lang']];
                        echo '
                                </TD>';
                        if ($ccol==$cols) {
                                $ccol=1;
                                echo '</TR><TR bgcolor="';
                                if ($shade==true) echo $color['list_shade1']; else echo $color['list_shade2'];
                                echo '">';
                                if ($shade==true) $shade=false; else $shade=true;
                                } else $ccol=$ccol+1;
                        $i=$i+1;
                        }
                if ($ccol>1) {
                        while ($ccol <= $cols) {
                                echo '<TD></TD>';
                                $ccol=$ccol+1;
                                }
                        echo '</TR><TR>';
                        }
                echo '<TD colspan='.$cols.' class="small" align=right>
                	</TD></TR></TABLE>';
}





function query_show_result($select_query,$sort="lname,fname",$type="edit") {
	global $lang, $color;

	$allow_edit=check_allow('participants_edit');

	$$type=true;
	$atypes=array('assign','drop','edit');
	foreach($atypes as $a) { if (!isset($$a)) $$a=false; }

	echo '  <P class="small">'.$lang['query'].': '.str_replace(",",", ",$select_query).'</P>';
	
	$result=mysqli_query($GLOBALS['mysqli'],$select_query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

        $shade=false; $participants=array();
        while ($line=mysqli_fetch_assoc($result)) {
                $participants[]=$line;
                }

        if ($sort) {
        	multi_array_sort($participants,$sort);
                }

	$count_results=count($participants);

	if ($assign || $drop) $columns=participant__load_result_table_fields($type='assign');
	else $columns=participant__load_result_table_fields($type='search');
	
    echo ' 
                        <A HREF="'.thisdoc();
			if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) 
				echo "?experiment_id=".$_REQUEST['experiment_id'];
			echo '">'.$lang['new_query'].'</A>
                <BR><BR>
			'.$count_results.' '.$lang['xxx_participants_in_result_set'].'
                <BR><BR>';

	if ($assign) echo $lang['only_ny_assigned_part_showed'].'<BR>';
	if ($drop) echo $lang['only_assigned_part_ny_reg_shownup_part_showed'].'<BR>';

        echo ' <table border=0>
                        <TR>';
			if ($assign || $drop) echo '<TD></TD>';
                        headcell($lang['id'],"participant_id");
                        foreach($columns as $c) {
                        	if($c['allow_sort']) headcell($c['column_name'],$c['sort_order']);
                        	else headcell($c['column_name']);
                        }
                        headcell($lang['noshowup'],"number_noshowup,number_reg");
                        headcell($lang['rules'],"rules_signed,lname,fname");
			if ($drop) headcell ($lang['invited'],"invited");
                        if ($edit) echo '<TD></TD>';
                        echo '</TR>';
	$i=0;
	$assign_ids=array();
        foreach ($participants as $p) {
		$assign_ids[]=$p['participant_id'];

		$i=$i+1;
        	echo '<tr class="small"';
                	if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                               else echo 'bgcolor="'.$color['list_shade2'].'"';
                echo '>';
		if ($assign || $drop) echo '<td><INPUT type=checkbox name="p'.$i.'" 
						value="'.$p['participant_id'].'"></td>';
                echo '	<td class="small">'.$p['participant_id'].'</TD>';
                foreach($columns as $c) {
                	echo '<td class="small">';
                	if($c['link_as_email_in_lists']=='y') echo '<A class="small" HREF="mailto:'.
                		$p[$c['mysql_column_name']].'">';
                	if(preg_match("/(radioline|select_list|select_lang)/",$c['type']) && isset($c['lang'][$p[$c['mysql_column_name']]]))
                		echo $c['lang'][$p[$c['mysql_column_name']]];
                	else echo $p[$c['mysql_column_name']];
                	if($c['link_as_email_in_lists']=='y') '</A>';
                	echo '</td>';
                }
                echo '
                        <td class="small">'.$p['number_noshowup'].
                                                '/'.$p['number_reg'].'</td>
                        <td class="small">'.$lang[$p['rules_signed']].'</td>';
		if ($drop) {
			echo '<TD class="small"';
			if ($p['invited']!='n') echo ' bgcolor="orange">'.$lang['yes'];
				else echo '>'.$lang['no'];
			echo '</TD>';
			}
                if ($edit) {
			echo '<TD class="small">';
			if ($allow_edit) echo '
                        	<A HREF="participants_edit.php?participant_id='
                                                 .$p['participant_id'].'">
                                      '.$lang['edit'].'</A>';
			echo 	'</TD>';
			}
		echo '
                     </tr>';
                if ($shade) $shade=false; else $shade=true;
                }

                echo '  </table>';
		return $assign_ids;
}


?>
