<?php
// part of orsee. see orsee.org

function load_language($language) {
	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." WHERE content_type='lang'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	while ($line = mysqli_fetch_assoc($result)) {
        	$lang[$line['content_name']]=stripslashes($line['content_value']);
            	}
	mysqli_free_result($result);
	return $lang;
}


function load_language_symbol($symbol,$language) {

	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." 
		WHERE content_type='lang' and content_name='".$symbol."'";
	$line=orsee_query($query);
	return stripslashes($line['content_value']);
}

function language__get_item($content_type,$content_name,$language="") {
    if (!$language){
		global $lang;
		$language=$lang['lang'];
	}
	$query="SELECT * from ".table('lang')." WHERE content_type='".$content_type."' AND content_name='".$content_name."'";
	$line=orsee_query($query);
	if (isset($line[$language])) return stripslashes($line[$language]);
	else return false;
}

function language__selectfield_item($content_type,$varname,$selected,$incnone=false,$language="",$existing=false,$where='',$show_count=false) {
	if (!$language){
		global $lang;
		$language=$lang['lang'];
	}
	if (!$selected) $preval=0;
	$items=array();
    $query="SELECT *, ".$lang['lang']." AS item
            FROM ".table('lang')."
			WHERE content_type='".$content_type."'  
            ORDER BY ".$language;
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
    while ($line = mysqli_fetch_assoc($result)) {
		$items[$line['content_name']]=stripslashes($line['item']);
	}

	$out='';
    $out.='<SELECT name="'.$varname.'">';
    if (!$existing) {
	    if ($incnone) { $out.='<OPTION value="0"'; if ($selected==0) $out.=' SELECTED';	$out.='>-</OPTION>'; }
	    foreach ($items as $k=>$v) {
        	$out.='<OPTION value="'.$k.'"';
        	if ($selected==$k) $out.=' SELECTED';
    		$out.='>'.$v.'</OPTION>';
		}
	} else {
		$query="SELECT count(*) as tf_count, ".$varname." as tf_value 
				FROM ".table('participants')."  
				WHERE ".table('participants').".participant_id IS NOT NULL ";
		if($where) $query.=" AND ".$where." ";
		$query.=" GROUP BY ".$varname." 
			  	ORDER BY ".$varname;
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		while ($line = mysqli_fetch_assoc($result)) {
			$out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == $selected) $out.=' SELECTED'; $out.='>';
			if (isset($items[$line['tf_value']])) $out.=$items[$line['tf_value']];
			elseif ($line['tf_value']==0)  $out.='-';
			else $out.=$line['tf_value'];
			if ($show_count) $out.=' ('.$line['tf_count'].')';
			$out.='</option>';
		}
	}
    $out.='</SELECT>';
    return $out;
} 


function get_languages() {
	$languages=array();
	$query="SELECT * FROM ".table('lang')." LIMIT 1";
	$line=orsee_query($query);
	foreach($line as $columnname=>$v) {
		if (!preg_match("(lang_id|content_name|content_type|enabled)",$columnname))
        	$languages[]=$columnname;
	}
	asort($languages);
	return $languages;
}

function lang__get_part_langs() {
	global $settings;
	$part_langs=explode(",",$settings['language_enabled_participants']);
	return $part_langs;
}

function lang__get_public_langs() {
        global $settings;
        $public_langs=explode(",",$settings['language_enabled_public']);
        return $public_langs;
}

function lang__get_language_names() {
        $names=orsee_db_load_array("lang","lang_name","content_name");
        return $names;
}


function lang__select_lang($varname,$selected="",$type="all") {
	global $lang;
	switch ($type) {
		case "public": $sel_langs=lang__get_public_langs(); break;
		case "part": $sel_langs=lang__get_part_langs(); break;
		default: $sel_langs=get_languages();
		}
	if(!$selected) $selected=$lang['lang'];
	$lang_names=lang__get_language_names();
	$out='';
	$out.='<SELECT name="'.$varname.'">';
	foreach ($sel_langs as $olang) {
		$out.='<OPTION value="'.$olang.'"';
		if ($olang==$selected) $out.=' SELECTED';
		$out.='>'.$lang_names[$olang].'</OPTION>';
		}
	$out.='</SELECT>';
    return $out;
}



function lang__insert_to_lang($item) {

	$query="SELECT max(lang_id) as lcount
		FROM ".table('lang')." 
		WHERE content_type='".$item['content_type']."'";
	$line=orsee_query($query);
	$maxid=$line['lcount'];

	$reorganize=false; $newmax=false; $newmin=false;
	// if there is no item under this content_type
	if ($maxid==NULL) {
		$newmax=true;
		$reorganize=true;
		$newmin=false;
		}
	else	{
		$newid=$maxid+1;
		$query="SELECT * FROM ".table('lang')." WHERE lang_id=".$newid;
		$line=orsee_query($query);

		if (isset($line['lang_id'])) {
			$reorganize=true;
			$newmax=true;
			$newmin=true;
			}
		}


	if ($newmax) {
		$query="SELECT max(lang_id) as maxid, min(lang_id) as minid FROM ".table('lang');
                $line=orsee_query($query);
                $newid=$line['maxid']+1;
                $steps=$line['minid'];
		if ($newmin) $steps=$steps*10;
		}

	$done1=orsee_db_save_array($item,"lang",$newid,"lang_id");
        if ($reorganize) $done2=lang__reorganize_lang_table($steps);
			

	return $newid;
}

function lang__reorganize_lang_table($steps=10000) {

	if ($steps < 1000) $steps=10000;

	$move=$steps*100;

	// copy stuff
        $query="UPDATE ".table('lang')." SET lang_id=lang_id+".$move;
        $done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));


	// insert new ordered stuff
	$i=1; $content_type="";

	// first all not-lang stuff
	$query="SELECT lang_id, content_type FROM ".table('lang')." ORDER BY content_type, content_name";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	$ids=array();
	while ($line = mysqli_fetch_assoc($result)) {
		$ids[]=$line;
		}

	foreach ($ids as $item) {
		if ($content_type!=$item['content_type']) {
			$content_type=$item['content_type'];
			$current_step= (int) floor($i/$steps);
			$i=($current_step + 1) * $steps;
			}
		$query="UPDATE ".table('lang')." SET lang_id='".$i."' WHERE lang_id='".$item['lang_id']."'";
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		$i++;	
                }
	mysqli_free_result($result);

	return true;
}


function lang__load_lang_cat($content_type,$language="") {
	global $lang; $cat=array();

	if (!$language) $language=$lang['lang'];
	$query="SELECT content_name, ".$language." as content_value 
	FROM ".table('lang')." WHERE content_type='".$content_type."'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

	while ($line = mysqli_fetch_assoc($result)) {
        $cat[$line['content_name']]=stripslashes($line['content_value']);
    }
	return $cat;
}


/* this is a list of used, but not explicit programmed language items 
	just to not forget and delete them

$lang['calendar']
$lang['cron_job_time_every_12_hours']
$lang['cron_job_time_every_15_minutes']
$lang['cron_job_time_every_2_hours']
$lang['cron_job_time_every_30_minutes']
$lang['cron_job_time_every_5_minutes']
$lang['cron_job_time_every_6_hours']
$lang['cron_job_time_every_day_at_15']
$lang['cron_job_time_every_day_at_22']
$lang['cron_job_time_every_day_at_3']
$lang['cron_job_time_every_day_at_8']
$lang['cron_job_time_every_hour']
$lang['cron_job_time_every_monday_at_8']
$lang['cron_job_time_every_month_at_15th_at_8']
$lang['cron_job_time_every_month_at_1st_at_8']
$lang['cron_job_time_every_thursday_at_8']
$lang['data_files']
$lang['enough_participants_needed']
$lang['enough_participants_needed_plus_reserve']
$lang['gender_?']
$lang['impressum']
$lang['instructions']
$lang['internet']
$lang['in_any_case_dont_ask']
$lang['lang_name']
$lang['my_data']
$lang['my_registrations']
$lang['not_enough_participants']
$lang['not_enough_reserve']
$lang['online-survey']
$lang['overview']
$lang['other']
$lang['paper']
$lang['presentations']
$lang['programs']

*/

?>
