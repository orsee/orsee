<?php

// form helper functions. part of orsee. see orsee.org.


// select field for numbers from begin to end by steps
function helpers__select_numbers($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$none=false) {

	$i=$begin;
	echo '<select name="'.$name.'">';
	if ($none) '<option value=""></option>';
	while ($i<=$end) {
		echo '<option value="'.$i.'"';
		if ($i == (int) $prevalue) echo ' SELECTED';
                echo '>';
		echo helpers__pad_number($i,$fillzeros);
		echo '</option>
			';
		$i=$i+$steps;
		}
	echo '</select>';
}


// select field for text array
function helpers__select_text($tarray,$name,$prevalue,$none=false) {
	global $lang;

        echo '<select name="'.$name.'">';
        if ($none) '<option value=""></option>';
        foreach ($tarray as $text) {
                echo '<option value="'.$text.'"';
                if ($text == $prevalue) echo ' SELECTED';
                echo '>';
                if (isset($lang[$text])) echo $lang[$text];
			else echo $text;
                echo '</option>
                        ';
                }
        echo '</select>';
}


// select field for values for reminder time and registration end
function helpers__select_numbers_relative($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$current_time=0) {
	global $authdata;
	$i=$begin;
	echo '<select name="'.$name.'">';
	while ($i <= $end) {
		echo '<option value="'.$i.'"';
		if ($i== (int) $prevalue) echo ' SELECTED';
                echo '>';
		echo helpers__pad_number($i,$fillzeros); 
		if ($current_time > 0) {
			$utime=$current_time - ($i * 60 * 60); 
			echo ' ('.time__format($authdata['language'],"",false,false,true,true,$utime).')';
			}
		echo '</option>
			';
		$i=$i+$steps;
		}
	echo '</select>';
}


// select field for field of studies
function select__field_of_studies($preval,$varname) {
	global $lang;
	if (!$preval) $preval=0;
	if (!$varname) $varname="field_of_studies";
        echo '<SELECT name="'.$varname.'">';
        $query="SELECT *, ".$lang['lang']." AS stud
                FROM ".table('lang')."
		WHERE content_type='field_of_studies' 
                ORDER BY ".$lang['lang'];
        $result=mysql_query($query);
        while ($line = mysql_fetch_assoc($result)) {
                        echo '<OPTION value="'.$line['content_name'].'"';
                        if ($preval==$line['content_name']) echo ' SELECTED';
                        echo '>'.stripslashes($line['stud']).'</OPTION>
                             ';
                        }
        echo '</SELECT>';
} 

function select__begin_of_studies($preval,$varname) {
	global $settings;
        if (!$preval) $preval=0;
        if (!$varname) $varname="begin_of_studies";

        $now = (int) date("Y");
        $begin_of_studies_start=$now-$settings['begin_of_studies_years_backward'];
        $begin_of_studies_stop=$now+$settings['begin_of_studies_years_forward'];

        echo '<SELECT name="'.$varname.'">';
        $i=$begin_of_studies_start;

        echo '<option value=""></option>
                ';
        while ($i-1 < $begin_of_studies_stop) {
                echo '<option value="'.$i.'"';
                if ($i==$preval) echo " SELECTED";
                echo '>'.$i.'</option>
                        ';
                $i++;
                }
        echo '</SELECT>';
}

// select field for professions
function select__profession($preval,$varname) {
	global $lang;
	if (!$preval) $preval=0;
	if (!$varname) $varname="profession";
        echo '<SELECT name="'.$varname.'">';
        $query="SELECT *, ".table('lang').".".$lang['lang']." AS prof 
                FROM ".table('lang')."
		WHERE content_type='profession' 
                ORDER BY ".$lang['lang'];
	$result=mysql_query($query);
	while ($line = mysql_fetch_assoc($result)) {
                        echo '<OPTION value="'.$line['content_name'].'"';
                        if ($preval==$line['content_name']) echo ' SELECTED';
                        echo '>'.stripslashes($line['prof']).'</OPTION>
                             '; 
			}
        echo '</SELECT>';
}


function experiment_ext_types__checkboxes($postvarname,$showvar,$checked="",$var="") {
	if (!$checked) $checked=array();
	if (!$var) $var="exptype_name";

        $query="SELECT *
                FROM ".table('experiment_types')." as texpt, ".table('lang')." as tlang
                WHERE texpt.exptype_id=tlang.content_name
                AND tlang.content_type='experiment_type'
		AND texpt.enabled='y' 
                ORDER BY exptype_id";

        $result=mysql_query($query);
        while ($line = mysql_fetch_assoc($result)) {
                echo '<INPUT type="checkbox" name="'.$postvarname.'['.$line[$var].']" 
					value="'.$line[$var].'"';
                if ($checked[$line[$var]]) echo " CHECKED";
                echo '>'.$line[$showvar];
                echo '<BR>
                                ';
                }

}

function participant__subscription_checkboxes($postvarname,$subpool_id=-1,$checked="") {
	global $settings, $lang;

        if (!$checked) $checked=array();
	if ($subpool_id==-1) $subpool_id=$settings['subpool_default_registration_id'];

        $query="SELECT *
                FROM ".table('experiment_types')." as texpt, ".table('lang')." as tlang, ".table('subpools')." as tsub
                WHERE texpt.exptype_id=tlang.content_name
                AND tlang.content_type='experiment_type'
                AND texpt.enabled='y'
		AND tsub.experiment_types LIKE concat('%',texpt.exptype_name ,'%') 
		AND tsub.subpool_id='".$subpool_id."' 
                ORDER BY exptype_id";
        $result=mysql_query($query);
        while ($line = mysql_fetch_assoc($result)) {
                echo '<INPUT type="checkbox" name="'.$postvarname.'['.$line['exptype_name'].']"
                                        value="'.$line['exptype_name'].'"';
                if ($checked[$line['exptype_name']]) echo " CHECKED";
                echo '>'.$line[$lang['lang']];
                echo '<BR>
                                ';
                }

}

// select field for question types
function select__question_type($preval,$varname) {
        global $settings;
        if (!$preval) $preval=0;
        if (!$varname) $varname="question_type";
        echo '<SELECT name="'.$varname.'">';
	$question_types=explode(",",$settings['os_question_types']);
        foreach ($question_types as $type) {
                echo '<OPTION value="'.$type.'"';
                      if ($preval==$type) echo " SELECTED";
                echo '>'.$type.'</OPTION>
                      ';
        }
        echo '</SELECT>';
}


// select field for sessions
function select__sessions($preval,$varname,$exp_id,$hide_nosession) {
	global $lang, $expadmindata;

	if (!$preval) $preval=0; 
	if (!$varname) $varname="session";
	if ($exp_id) {  
			$query="SELECT *
                                FROM ".table('sessions')."
				WHERE experiment_id='".$exp_id."'
				OR session_id=0
                                ORDER BY session_start_year, session_start_month, session_start_day, 
                                 session_start_hour, session_start_minute";
			$with_exp=false;
			}
		else {
			$query="SELECT *
                                FROM ".table('sessions').", ".table('experiments')."
                                WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id
				AND ".table('sessions').".session_finished='n' 
                                ORDER BY session_start_year, session_start_month, session_start_day, 
                                 session_start_hour, session_start_minute";
			$with_exp=true;
			}


        echo '<SELECT name="'.$varname.'">';
	if ($with_exp && !$hide_nosession) {
		echo '<OPTION value="0"';
			if ($preval==0) echo " SELECTED";
		echo '>'.$lang['no_session'].'</OPTION>';
		}

	$result=mysql_query($query);

	while ($line = mysql_fetch_assoc($result)) {
        	echo '<OPTION value="'.$line['session_id'].'"';
                	if ($preval==$line['session_id']) echo " SELECTED";
		echo '>';
		if ($line['session_id']==0) {
				echo $lang['no_session'];
		 	 } else {
				if ($with_exp) echo $line['experiment_name'].' - ';
				$tarr=array('day'=>$line['session_start_day'],
						'month'=>$line['session_start_month'],
						'year'=>$line['session_start_year'],
						'hour'=>$line['session_start_hour'],
						'minute'=>$line['session_start_minute']);
				echo time__format($expadmindata['language'],$tarr,false,false,true,false);
				}
               	echo '</OPTION>';
		}
        echo '</SELECT>';
}

function headcell($value,$sort="",$focus="") {
	echo '
        <TD class=small';
	if ($_REQUEST['focus']==$focus && $focus) echo ' bgcolor="'.$color['list_highlighted_table_head_background'].'"';
	echo '>';
        if ($sort) {
		echo '<A HREF="'.thisdoc().'?sort='.urlencode($sort).'&show=true';
		if ($_REQUEST['experiment_id']) echo '&experiment_id='.$_REQUEST['experiment_id'];
		if ($_REQUEST['session_id']) echo '&session_id='.$_REQUEST['session_id'];
		if ($_REQUEST['focus']) echo '&focus='.$_REQUEST['focus'];
		echo '">';
		}
	echo '<FONT class="small"';
	if ($_REQUEST['focus']==$focus && $focus) echo ' color="'.$color['list_highlighted_table_head_text'].'"';
	echo '>';
        echo $value;
	echo '</FONT>';
        if ($sort) echo '</A>';
        echo '</TD>';
}

?>
