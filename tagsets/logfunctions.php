<?php
// log functions. part of orsee. see orsee.org


function log__participant($action,$participant_id,$target="") {
	$darr=getdate();	

	$query="INSERT INTO ".table('participants_log')." SET id='".$participant_id."',
		year='".$darr['year']."', 
		month='".$darr['mon']."', 
		day='".$darr['mday']."', 
		action='".$action."',
		target='".$target."',
		timestamp='".$darr[0]."'";
	$done=mysql_query($query);
}

function log__admin($action="unknown",$target="") {
	global $expadmindata;
        $darr=getdate();

        $query="INSERT INTO ".table('admin_log')." SET id='".$expadmindata['admin_id']."',
                year='".$darr['year']."',
                month='".$darr['mon']."',
                day='".$darr['mday']."',
                action='".$action."',
		target='".$target."',
                timestamp='".$darr[0]."'";
        $done=mysql_query($query);
}

function log__cron_job($action="unknown",$target="",$now="",$id="") {
	if ($now=="") $now=time();
        $darr=getdate($now);

        $query="INSERT INTO ".table('cron_log')." 
		SET id='".$id."',
                year='".$darr['year']."',
                month='".$darr['mon']."',
                day='".$darr['mday']."',
                action='".$action."',
                target='".mysql_escape_string($target)."',
                timestamp='".$now."'";
        $done=mysql_query($query);
	return $done;
}


function log__link() {
	$post=$_REQUEST;
	unset($post['SID']); unset ($post['PHPSESSID']);
	$arg_list=func_get_args();
	foreach ($arg_list as $arg) {
		$var=explode("=",$arg);
		$post[$var[0]]=$var[1];
		}
	$link='<A HREF="'.thisdoc().'?';
	foreach ($post as $key=>$value) {
		$link.=$key.'='.urlencode($value).'&';
		}
	$link.='">';
	return $link;
}

function log__restrict_link($varname,$varvalue) {
	global $lang;
	$link=log__link($varname.'='.$varvalue,'os=0');
	$link.='<FONT class="small">['.$lang['restrict'].']</FONT></A>';
	return $link;
}

function log__show_log($log) {
	global $limit;

	if (!$limit) $limit=50;
	if ($_REQUEST['os']>0) $offset=$_REQUEST['os']; else $offset=0;

	global $lang, $color;


	if ($_REQUEST['action']) $aquery=" AND action='".$_REQUEST['action']."' ";
			else $aquery="";

	if ($_REQUEST['id']) $idquery=" AND id='".$_REQUEST['id']."' ";
			else $idquery="";

	if ($_REQUEST['target']) $tquery=" AND target LIKE '%".$_REQUEST['target']."%' ";
                        else $tquery="";

	if (!$logtable) $logtable="participants_log";

	switch ($log) {
		case "participant_actions":
			$logtable=table('participants_log');
			$secondtable=" LEFT JOIN ".table('participants')." ON id=participant_id ";
			break;
		case "experimenter_actions":
			$logtable=table('admin_log');
			$secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
			break;
		case "regular_tasks":
			$logtable=table('cron_log');
			$secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
			break;
		}

	if ($_REQUEST['delete'] && $_REQUEST['days']) {

		$allow=check_allow('log_file_'.$log.'_delete','statistics_show_log.php?log='.$log);

		if ($_REQUEST['days']=="all") $where_clause="";
		   else {
                	$now=time();
                	$dsec= (int) $_REQUEST['days']*24*60*60;
                	$dtime=$now-$dsec;
			$where_clause=" WHERE timestamp < ".$dtime;
			}
                $query="DELETE FROM ".$logtable.$where_clause;
		$done=mysql_query($query);
		$number=mysql_affected_rows();
		message ($number.' '.$lang['xxx_log_entries_deleted']);
		if ($number>0) log__admin("log_delete_entries","log:".$log."\ndays:".$_REQUEST['days']);
		redirect ("admin/statistics_show_log.php?log=".$log);
                }



	$query="SELECT * FROM ".$logtable.$secondtable."
		WHERE id IS NOT NULL ".
		$aquery.$idquery.$tquery.
		" ORDER BY timestamp DESC 
		LIMIT ".$offset.",".$limit;

	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$num_rows=mysql_num_rows($result);

	echo '<TABLE width=80% border=0>
		<TR><TD width=50%>';
	echo '<FONT class="small">'.$lang['query'].': '.$query.'</FONT><BR><BR>';
	echo '</TD>
		<TD align=right width=50%>';
	
	if (check_allow('log_file_'.$log.'_delete')) {
		echo '
			<FORM action="statistics_show_log.php">
			<INPUT type=hidden name="log" value="'.$log.'">
			'.$lang['delete_log_entries_older_than'].'
			<select name="days">
			<option value="all">'.$lang['all_entries'].'</option>';
			$ddays=array(1,7,30,90,180,360);
			if ($_REQUEST['days']) $selected=$_REQUEST['days']; else $selected=90;
			foreach ($ddays as $day) {
				echo '<option value="'.$day.'"';
				if ($day==$selected) echo ' SELECTED';
				echo '>'.$day.' ';
				if ($day==1) echo $lang['day']; else echo $lang['days'];
				echo '</option>
					';
				}
			echo '	</select><input type=submit name="delete" value="'.$lang['delete'].'">';
		}
	echo '</TD></TR></TABLE>';

	if ($offset > 0) echo '['.log__link('os='.($offset-$limit)).$lang['previous'].'</A>]';
			else echo '['.$lang['previous'].']';
	echo '&nbsp;&nbsp;';
	if ($num_rows >= $limit) echo '['.log__link('os='.($offset+$limit)).$lang['next'].'</A>]';
			else echo '['.$lang['next'].']';

	echo '<TABLE width=90% border=1>';

	// header
	echo '<TR bgcolor="'.$color['list_header_background'].'">
		<TD>
			'.$lang['date_and_time'].'
		</TD>
		<TD>';
			if ($log=='participant_actions') {
				echo $lang['lastname'].', '.$lang['firstname'];
				}
			elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
				echo $lang['experimenter'];
				}
			if ($_REQUEST['id']) 
				echo ' '.log__link('id=','os=0').'<FONT class="small">['.$lang['unrestrict'].']</FONT></A>';
	echo '	</TD>
		<TD>
			'.$lang['action'];
			if ($_REQUEST['action'])
                                echo ' '.log__link('action=','os=0').'<FONT class="small">['.$lang['unrestrict'].']</FONT></A>';
	echo '	</TD>
		<TD>
			'.$lang['target'];
			if ($_REQUEST['target'])
                                echo ' '.log__link('target=','os=0').'<FONT class="small">['.$lang['unrestrict'].']</FONT></A>';
	echo '	</TD>
	      </TR>
		';

	while ($line=mysql_fetch_assoc($result)) {

		echo '<TR>
			<TD>
				'.time__format($lang['lang'],'',false,false,false,false,$line['timestamp']).'
			</TD>
			<TD>';
				if ($log=='participant_actions') {
				   if ($line['participant_id']) {
					echo $line['lname'].', '.$line['fname'].
					' <A HREF="participants_edit.php?participant_id='.
					$line['participant_id'].'"><FONT class="small">['.$lang['edit'].']</FONT></A>';
					}
				   else echo $line['id'];
				   }
				elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
					echo $line['adminname'];
					}
				if ($_REQUEST['id']!=$line['id']) echo ' '.log__restrict_link('id',$line['id']);
		echo '	</TD>
			<TD>
				'.$line['action'];
				if ($_REQUEST['action']!=$line['action']) 
					echo ' '.log__restrict_link('action',$line['action']);
		echo '	</TD>
			<TD>
                                '.nl2br(stripslashes($line['target']));
				if ($_REQUEST['target']!=$line['target'] && $log!='regular_tasks')
                                        echo ' '.log__restrict_link('target',$line['target']);
                echo '	</TD>
		      </TR>';
		}

	echo '</TABLE>';
	return $num_rows;
}

?>
