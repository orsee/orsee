<?php

// experiment functions. part of orsee. see orsee.org.


// current experiments summary
function experiment__current_experiment_summary($experimenter="",$finished="n",$class="") {
	global $lang, $expadmindata, $color, $experimentclasses;

	$experimentclasses=experiment__get_experiment_class_names();

	if ($experimenter) $expq=" AND ".table('experiments').".experimenter LIKE '%".$expadmindata['adminname']."%' ";
		      else $expq="";

	if ($class) {
			if ($_REQUEST['class']) $tclass=$_REQUEST['class']; else $tclass="";
			if ($tclass) $classq= " AND ".table('experiments').".experiment_class = '".$tclass."' ";
				else $classq="";
			}

	$finq=" ".table('experiments').".experiment_finished='".$finished."' ";

	$aquery=$finq.$expq.$classq;

        if ($finished=="n") {
			$format_function="experiment__experiments_format_alist";
			$explang=$lang['xxx_current_experiments'];
			}
                   else {
			$format_function="experiment__old_experiments_format_alist";
			$explang=$lang['xxx_finished_experiments'];
			}

 	echo '<center>
		<BR>
		<table border=1 width="90%">
		<TR>
		<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h4>';
			if ($finished=="y") echo $lang['finished_experiments'];
			elseif ($experimenter) echo $lang['my_experiments'];
			else echo $lang['experiments'];
			echo '</h4></TD>
		</TR>
		<TR><TD';
		if (!$class) echo ' colspan=2';
		echo '>';
		echo experiment__count_experiments($aquery);
		echo ' '.$explang.'<BR></TD>';
		if ($class) {
			echo '<TD align=right><FORM action="'.thisdoc().'">'.
				$lang['restrict_list_to_experiments_of_class'].' ';
				experiment__experiment_class_select_field('class',
                                                                $tclass);
			echo '<INPUT class="small" type=submit name="show" value="'.$lang['show'].'"></FORM>';
			echo '</TD>';
			}
	
		echo '</TR>
			<TR><TD>&nbsp;&nbsp;&nbsp;</TD>
			<TD bgcolor="'.$color['list_list_background'].'">

			<TABLE border=0 width="100%">
			<TR bgcolor="'.$color['list_title_background'].'"><TD colspan=2>
				'.$lang['experiments_with_dedicated_sessions'].'
			</TD></TR>';

		$query="SELECT ".table('experiments').".*, 
      			min(session_start_year*100000000 +
      			session_start_month*1000000 +
      			session_start_day*10000 + 
      			session_start_hour*100 + 
      			session_start_minute) as time
      			FROM ".table('experiments').", ".table('sessions')."  
      			WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
      			AND ".table('experiments').".experiment_type='laboratory'
      			AND ".$aquery." 
      			GROUP BY experiment_id 
      			ORDER BY time DESC, experiment_id";

		$done=orsee_query($query,$format_function);

		echo '<TR><TD colspan=2>&nbsp;</TD></TR>
			<TR bgcolor="'.$color['list_title_background'].'"><TD colspan=2>
			'.$lang['experiments_without_dedicated_sessions'].'
			</TD></TR>';

     		$query="SELECT ".table('experiments').".*
      			FROM ".table('experiments')."
      			LEFT JOIN ".table('sessions')." 
      			ON ".table('experiments').".experiment_id=".table('sessions').".experiment_id
      			WHERE ".table('sessions').".experiment_id IS NULL
      			AND ".table('experiments').".experiment_type='laboratory'
      			AND ".$aquery." 
      			ORDER BY experiment_id";

		$done=orsee_query($query,$format_function);


		echo '<TR><TD colspan=2>&nbsp;</TD></TR>
			<TR bgcolor="'.$color['list_title_background'].'"><TD colspan=2>
			'.$lang['internet_experiments'].'
			</TD></TR>';

     		$query="SELECT *
      			FROM ".table('experiments')." 
      			WHERE experiment_type!='laboratory'
      			AND ".$aquery." 
      			ORDER BY experiment_id";

		$done=orsee_query($query,$format_function);


		echo '</table>

			</TD>
			</TR>
			<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
			'.$lang['options'].':<BR><BR></TD></TR>
			<TR><TD>';
			if (check_allow('experiment_edit')) echo '
				<A HREF="experiment_edit.php?addit=true">'.$lang['register_new_experiment'].'</A>
				<BR>';
		echo '	</TD>
			<TD>';
			if ($finished=="n") 
				echo '<A HREF="experiment_old.php">'.$lang['finished_experiments'].'</A>';
		   	else echo '<A HREF="experiment_main.php">'.$lang['current_experiments'].'</A>';
			

		echo '</TR>
			</TABLE>
			</center>
			<BR><BR>
			';
}

function experiment__experimenters_checkbox_list($fieldname,$list,$realnames=true) {
	global $settings;

	$selected=explode(",",$list);

	$admins=array();
	$query="SELECT * from ".table('admin')." where experimenter_list='y' order by adminname";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	while ($line=mysql_fetch_assoc($result)) {
		$admins[]=$line;
		}

        $cols=$settings['experimenter_list_nr_columns'];
        $ccol=1;

        echo '<TABLE width=100% border=1 cellspacing=0 cellpadding=0><TR>';
        foreach ($admins as $ad) {
        	echo '<TD class="small">
                      <INPUT class="small" type=checkbox name="'.$fieldname.'['.$ad['adminname'].']" value="'.$ad['adminname'].'"';
                if (in_array($ad['adminname'],$selected)) echo " CHECKED";
                echo '>';
		if ($realnames) echo $ad['fname'].' '.$ad['lname'];
			else echo $ad['adminname'];
                echo '</TD>';
                if ($ccol==$cols) { $ccol=1; echo '</TR><TR>'; } else $ccol=$ccol+1;
                $i=$i+1;
                }
         if ($ccol>1) {
                while ($ccol <= $cols) {
                	echo '<TD></TD>';
                        $ccol=$ccol+1;
                        }
          echo '</TR>';
                 }
          echo '</TABLE>';

}

function experiment__list_experimenters($namelist,$showlinks=true,$realnames=false) {
        global $settings;

        $selected=explode(",",$namelist);
	$list=array();

        $admins=array();
        $query="SELECT * from ".table('admin')." order by adminname";
        $result=mysql_query($query) or die("Database error: " . mysql_error());
        while ($line=mysql_fetch_assoc($result)) {
                $admins[$line['adminname']]=$line;
                }

	foreach ($selected as $admin) {
		$item='';
		if (isset($admins[$admin])) {
			if ($showlinks) $item.='<A  class="small" HREF="mailto:'.$admins[$admin]['email'].'">';
			if ($realnames) $item.=$admins[$admin]['fname'].' '.$admins[$admin]['lname'];
				else $item.=$admin;
			if ($showlinks) $item.='</A>';
			}
		   else {
			$item=$admin;
			}
		$list[]=$item;
		}
	$string=implode(", ",$list);
	return $string;
}


function check_experiment_allowed ($experiment_var,$redirect="admin/experiment_main.php") {
	if (!experiment__allowed($experiment_var)) {
		global $lang;
		message($lang['error_experiment_access_restricted']);
		redirect ($redirect);
		}
}


function experiment__allowed($experiment_var) {
	if (is_array($experiment_var)) $experiment=$experiment_var;
		else $experiment=orsee_db_load_array("experiments",$experiment_var,"experiment_id");

	$return=true;

	if ($experiment['access_restricted']=='y') {
		global $settings, $expadmindata;
		if ($settings['allow_experiment_restriction']=='y' && 
		    $expadmindata['rights']['experiment_override_restrictions']!='y') {
			$experimenters=explode(",",$experiment['experimenter']);
			if (!in_array($expadmindata['adminname'],$experimenters)) {
				$return=false;
				}
			}
		}
	return $return;
}

function experiment__check_required($varname) {
	global $error__error;
	$test=$_REQUEST[$varname];
	if ((!isset($_REQUEST[$varname])) || $test=="nix" || $test=="" || $test=" ") {
		$error__error=true;
		return true;
		}
	   else {
		return false;
		}
}


function experiment__experiments_format_alist($alist) {
	global $lang, $color, $experimentclasses;
	extract($alist);


        echo '<tr>
        	<td bgcolor="'.$color['list_item_emphasize_background'].'">';

  if (check_allow('experiment_show')) {
 	echo '
                <A HREF="experiment_show.php?experiment_id='.$experiment_id.'">
                '.$experiment_name.' ('.$experiment_public_name.')
                </A>';
        } else {
	echo $experiment_name.' ('.$experiment_public_name.')';
        }
	
        echo '</td>
        	<td>';

	if ($experiment_type=="laboratory") {
        	echo $lang['from'].' '.sessions__get_first_date($experiment_id).' ';
		echo $lang['to'].' '.sessions__get_last_date($experiment_id);
		}
	  elseif ($experiment_type=="online-survey") {
		echo $lang['from'].' '.survey__print_start_time($experiment_id).' ';
        	echo $lang['to'].' '.survey__print_stop_time($experiment_id);
		}

        echo '</td>
        	</TR>
        	<TR><TD class="small">'.$lang['type'].': '.$lang[$experiment_type].' ('.$experiment_ext_type.')</TD>
		<TD class="small">';
	if ($experiment_type=="laboratory") 
		echo $lang['sessions'].': '.experiment__count_sessions($experiment_id);

	echo '</TD></TR>
		<TR><TD class="small">
		'.$lang['class'].': '.$experimentclasses[$experiment_class].'
		</TD>
		<TD class="small"></TD>
		</TR>
		<TR><TD class="small">'.$lang['experimenter'].': '.experiment__list_experimenters($experimenter,true,true).'</TD>
		<TD class="small">'.$lang['get_emails'].': '.experiment__list_experimenters($experimenter_mail,true,true).'
			</TD></TR>';

	if ($experiment_type=="laboratory") {
		echo '<TR>
		<TD class="small">
		'.$lang['invited_subjects'].': 
		'.experiment__count_invited($experiment_id).'
		</TD>

	    	<TD class="small">
		'.$lang['registered_subjects'].': 
		'.experiment__count_registered($experiment_id).'
	    	</TD>
		</TR>
		<TR>
		<TD class="small">
		'.$lang['shownup_subjects'].': 
		'.experiment__count_shownup($experiment_id).'
		</TD>
	    	<TD class="small">
		'.$lang['subjects_participated'].': 
		'.experiment__count_participated($experiment_id).'
		</TD>
		</TR>';
		}

        if ($experiment_type=="online-survey") {
		echo '<TR>
                	<TD class="small">
			'.$lang['participants_from_subject_pool'].'
                	</TD>

                	<TD class="small">
			'.$lang['free_registration'].'
                	</TD>
        	</TR>
        	<TR>
                	<TD class="small">
                	'.$lang['finished'].':
			'.survey__count_finished($experiment_id,"n").'
                	</TD>
                	<TD class="small">
                	'.$lang['finished'].': 
			'.survey__count_finished($experiment_id,"y").'
                	</TD>
        	</TR>';
		}

	if ($experiment_link_to_paper) {
		echo '<TR>
			<TD colspan=2 class="small">
			<A HREF="'.$experiment_link_to_paper.'" target="_blank" class="small">
			['.$lang['download_paper'].']</A>
			</TD>
			</TR>';
		}	
        echo '<TR><TD colspan=2 class="small">&nbsp;</TD></TR>';
}
//-----------------------------------------------------------------------


// finished experiments - overview table
function experiment__old_experiments_format_alist($alist) {
	global $lang, $color, $experimentclasses;
	static $shade=true;
        extract($alist);

	

        echo '<tr>
        	<td bgcolor="';
		if ($shade) echo $color['list_shade1'];
		   else echo $color['list_shade2'];
		echo '"><font class="small">
			'.$experimenter.': ';
			if (check_allow('experiment_show')) echo '
                		<A HREF="experiment_show.php?experiment_id='.$experiment_id.'">
				<font class="small">
                		'.$experiment_name.' ('.$experiment_public_name.')
				</font>
                		</A>';
			else echo $experiment_name.' ('.$experiment_public_name.')';
	echo '	
        	<BR>
	
			'.$lang[$experiment_type].' ('.$experiment_ext_type.'), '.
				$experimentclasses[$experiment_class].', ';
 	if ($experiment_type=="laboratory") {
                echo $lang['from'].' '.sessions__get_first_date($experiment_id).' ';
                echo $lang['to'].' '.sessions__get_last_date($experiment_id).', ';
		echo experiment__count_sessions($experiment_id).' '.$lang['sessions'].', ';
		echo experiment__count_participated($experiment_id).' '.$lang['participants'];
                }
          elseif ($experiment_type=="online-survey") {
                echo $lang['from'].' '.survey__print_start_time($experiment_id).' ';
                echo $lang['to'].' '.survey__print_stop_time($experiment_id).', ';
		$part_count= (int) survey__count_finished($experiment_id,"n") + (int) survey__count_finished($experiment_id,"y");
		echo $part_count.' '.$lang['participants'];
                } 
 
        echo '</font></TD>
        	</TR>';
	if ($shade) $shade=false; else $shade=true;
}


// Experiment-Title
function experiment__get_title($experiment_id) {
     	$query="SELECT experiment_name
      		FROM ".table('experiments')."
      		WHERE experiment_id='".$experiment_id."'";
	$res=orsee_query($query);
	return $res['experiment_name'];
}


function experiment__exptype_select_field($postvarname,$var,$showvar,$selected,$hidden='') {

        echo '<SELECT name="'.$postvarname.'">';
        $query="SELECT *
                FROM ".table('experiment_types')." as ttype, ".table('lang')." as tlang
                WHERE ttype.exptype_id=tlang.content_name
                AND tlang.content_type='experiment_type'
                ORDER BY exptype_id";

        $result=mysql_query($query);
        while ($line = mysql_fetch_assoc($result)) {
                if ($line[$var] != $hidden) {
                        echo '<OPTION value="'.$line[$var].'"';
                        if ($line[$var]==$selected) echo " SELECTED";
                        echo '>'.$line[$showvar];
                        echo '</OPTION>
                                ';
                        }
                }
        echo '</SELECT>';

}


function experiment__experiment_class_select_field($postvarname,$selected,$hidden='') {
	global $lang;

        echo '<SELECT name="'.$postvarname.'">';
        $query="SELECT *
                FROM ".table('lang')." 
                WHERE content_type='experimentclass'
                ORDER BY ".$lang['lang'];

        $result=mysql_query($query);
        while ($line = mysql_fetch_assoc($result)) {
                if ($line['content_name'] != $hidden) {
                        echo '<OPTION value="'.$line['content_name'].'"';
                        if ($line['content_name']==$selected) echo " SELECTED";
                        echo '>'.$line[$lang['lang']];
                        echo '</OPTION>
                                ';
                        }
                }
        echo '</SELECT>';

}

function experiment__get_experiment_class_names($id='') {
        global $lang;

	if ($id) {
		$query="SELECT *
                        FROM ".table('lang')."
                        WHERE content_type='experimentclass'
			AND content_name='".$id."'";
                $line=orsee_query($query);
                return $line[$lang['lang']];

		}
	  else {
		$names=array();
        	$query="SELECT *
                	FROM ".table('lang')."
                	WHERE content_type='experimentclass'
                	ORDER BY ".$lang['lang'];
        	$result=mysql_query($query);
        	while ($line = mysql_fetch_assoc($result)) $names[$line['content_name']]=$line[$lang['lang']];
		return $names;
		}
}

function experiment__get_public_name($experiment_id) {
	$exp=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
       	return $exp['experiment_public_name'];
}


function experiment__count_experiments($constraint="") {
	if ($constraint) 
    		$whereclause="WHERE ".$constraint;
    	   else $whereclause="";

	$query="SELECT COUNT(experiment_id) as cnt
      		FROM ".table('experiments')." ".$whereclause;
	$line=orsee_query($query);
	return $line['cnt'];
}



function experiment__count_participate_at($experiment_id,$session_id="") {

$query="SELECT COUNT(participate_id) as regcount FROM ".table('participate_at')." WHERE ";
if ($session_id) $query=$query."session_id='".$session_id."'";
	else $query=$query."experiment_id='".$experiment_id."'";

$line=orsee_query($query);
return $line['regcount'];
}


function experiment__count_registered($experiment_id) {
     	$query="SELECT COUNT(registered) as cnt
      		FROM ".table('participate_at')." 
      		WHERE registered='y'
      		AND experiment_id='".$experiment_id."'";
        $res=orsee_query($query);
        return $res['cnt'];
}

function experiment__count_invited($experiment_id) {
     	$query="SELECT COUNT(invited) as cnt
      		FROM ".table('participate_at')."
      		WHERE invited='y'
      		AND experiment_id='".$experiment_id."'";
        $res=orsee_query($query);
        return $res['cnt'];
}

function experiment__count_shownup($experiment_id) {
     	$query="SELECT COUNT(shownup) as cnt
      		FROM ".table('participate_at')."
      		WHERE shownup='y'
      		AND experiment_id='".$experiment_id."'";
        $res=orsee_query($query);
        return $res['cnt'];
}

function experiment__count_participated($experiment_id) {
     	$query="SELECT COUNT(participated) as cnt
      		FROM ".table('participate_at')."
      		WHERE participated='y'
      		AND experiment_id='".$experiment_id."'";
        $res=orsee_query($query);
        return $res['cnt'];
}


function experiment__count_sessions($experiment_id) {
	$query="SELECT COUNT(session_id) as cnt
      		FROM ".table('sessions')."
      		WHERE experiment_id='".$experiment_id."'";
	$res=orsee_query($query);
       	return $res['cnt'];
}

function load_external_experiment_types($expinttype="",$enabled=true) {
	$exttypes=array();
        $query="SELECT * 
		FROM ".table('experiment_types')."
		WHERE exptype_id IS NOT NULL";
	if ($enabled) $query.=" AND enabled='y' ";
        if ($expinttype) $query.=" AND exptype_mapping LIKE '%".$expinttype."%' ";
        $result=mysql_query($query);
	while ($line=mysql_fetch_assoc($result)) {
		$exttypes[]=$line['exptype_name'];
		}
	return $exttypes;
}

function load_external_experiment_type_names($enabled=true) {
	global $lang;
	if ($enabled) $enstring=" AND texpt.enabled='y' "; else $enstring="";
        $exttypes=array();
	$query="SELECT *
                FROM ".table('experiment_types')." as texpt, ".table('lang')." as tlang
                WHERE texpt.exptype_id=tlang.content_name
                AND tlang.content_type='experiment_type'".
                $enstring."
                ORDER BY exptype_id";
        $result=mysql_query($query);
        while ($line=mysql_fetch_assoc($result)) {
                $exttypes[$line['exptype_name']]=$line[$lang['lang']];
                }
        return $exttypes;
}

?>
