<?php
// language functions. part of orsee. see orsee.org

function load_language($language) {
	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." WHERE content_type='lang'";
	$result=mysql_query($query);
	while ($line = mysql_fetch_assoc($result)) {
        	$lang[$line['content_name']]=htmlentities(stripslashes($line['content_value']));
            	}
	mysql_free_result($result);
	return $lang;
}


function load_language_symbol($symbol,$language) {

	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." 
		WHERE content_type='lang' and content_name='".$symbol."'";
	$line=orsee_query($query);
	return stripslashes($line['content_value']);
}


function get_languages() {
	global $site__database_database;
        $languages=array();
        $fields = mysql_list_fields($site__database_database,table('lang'));
        $column_count = mysql_num_fields($fields);
        for ($i = 0; $i < $column_count; $i++) {
                $columnname=mysql_field_name($fields, $i);
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


function lang__select_lang($varname,$selected,$type="all") {
	switch ($type) {
		case "public": $sel_langs=lang__get_public_langs(); break;
		case "part": $sel_langs=lang__get_part_langs(); break;
		default: $sel_langs=get_languages();
		}
	$lang_names=lang__get_language_names();

	echo '<SELECT name="'.$varname.'">';
	foreach ($sel_langs as $olang) {
		echo '<OPTION value="'.$olang.'"';
		if ($olang==$selected) echo ' SELECTED';
		echo '>'.$lang_names[$olang].'</OPTION>';
		}

	echo '</SELECT>';

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
        $done=mysql_query($query);


	// insert new ordered stuff
	$i=1; $content_type="";

	// first all not-lang stuff
	$query="SELECT lang_id, content_type FROM ".table('lang')." ORDER BY content_type, content_name";
	$result=mysql_query($query);
	$ids=array();
	while ($line = mysql_fetch_assoc($result)) {
		$ids[]=$line;
		}

	foreach ($ids as $item) {
		if ($content_type!=$item['content_type']) {
			$content_type=$item['content_type'];
			$current_step= (int) floor($i/$steps);
			$i=($current_step + 1) * $steps;
			}
		$query="UPDATE ".table('lang')." SET lang_id='".$i."' WHERE lang_id='".$item['lang_id']."'";
		$result=mysql_query($query);
		$i++;	
                }
	mysql_free_result($result);

	return true;
}


function lang__load_studies($language="",$enabled=false) {
	global $lang;

	if (!$language) $language=$lang['lang'];

	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." WHERE content_type='field_of_studies'";

	if ($enabled) $query.=" AND enabled='y'";

	$result=mysql_query($query);

	while ($line = mysql_fetch_assoc($result)) {
            	$studies[$line['content_name']]=stripslashes($line['content_value']);
            	}
	mysql_free_result($result);

	return $studies;
}

function lang__load_professions($language="",$enabled=false) {
        global $lang;

        if (!$language) $language=$lang['lang'];

	$query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." WHERE content_type='profession'";

	if ($enabled) $query.=" AND enabled='y'";

	$result=mysql_query($query);

	while ($line = mysql_fetch_assoc($result)) {
            	$professions[$line['content_name']]=stripslashes($line['content_value']);
            	}
	mysql_free_result($result);

	return $professions;
}

function lang__load_genders($language="") {
        global $lang;

        if (!$language) $language=$lang['lang'];

        $query="SELECT content_name, ".$language." as content_value FROM ".table('lang')." 
		WHERE content_type='lang'
		AND (content_name='gender_f' OR content_name='gender_m' OR content_name='gender_?')";

        $result=mysql_query($query);

        while ($line = mysql_fetch_assoc($result)) {
                $genders[substr($line['content_name'],7)]=stripslashes($line['content_value']);
                }
        mysql_free_result($result);

        return $genders;
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
