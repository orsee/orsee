<?php
// part of orsee. see orsee.org


function table($table) {
	global $site__database_table_prefix;
	$tablename=$site__database_table_prefix.$table;
	return $tablename;
}

// very convenient functions translated from metahtml

function orsee_query($query,$funcname="") {

	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']).", Query=".$query);
	if ($funcname) {
		$back=array();
        	while ($line = mysqli_fetch_assoc($result)) {
                	$back[]=$funcname($line);
                	}
        	mysqli_free_result($result);
		return $back;

         } else {
        	$line=mysqli_fetch_assoc($result);
        	mysqli_free_result($result);
        	return $line;
        	}
}

function orsee_db_load_array($table,$key,$keyname) {
        $query="SELECT * FROM ".table($table)." where ".$keyname."='".mysqli_real_escape_string($GLOBALS['mysqli'],$key)."'";
        $line=orsee_query($query);
        return $line;
}


function orsee_db_save_array($array,$table,$key,$keyname) {
	global $site__database_database;

	// find out which fields i can save
	$query="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE table_name='".table($table)."' 
			AND table_schema = '".$site__database_database."'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']).", Query=".$query);
    while ($line = mysqli_fetch_assoc($result)) {
    	$columns[]=$line['COLUMN_NAME'];
    }
    /*
	$fields = mysql_list_fields($site__database_database,table($table));
	$column_count = mysql_num_fields($fields);
	for ($i = 0; $i < $column_count; $i++) {
    		$columns[]=mysql_field_name($fields, $i);
	}
	*/

	// delete key
	if (isset($array[$keyname])) unset($array[$keyname]);
	$arraykeys=array_keys($array);
	$fields_to_save=array_intersect($arraykeys,$columns);
	// build set phrase
	$first=true; $set_phrase="";
	foreach ($fields_to_save as $field) {
		if ($first) $first=false; else $set_phrase=$set_phrase.", ";
		$set_phrase=$set_phrase.$field."='".mysqli_real_escape_string($GLOBALS['mysqli'],$array[$field])."'";
		}

	// check if already saved
	$query="SELECT ".$keyname." FROM ".table($table)." WHERE ".$keyname."='".mysqli_real_escape_string($GLOBALS['mysqli'],$key)."'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	$num_rows = mysqli_num_rows($result);

	if ($num_rows>0) {
		// update query
        	$query="UPDATE ".table($table)." SET ".$set_phrase." WHERE ".$keyname."='".mysqli_real_escape_string($GLOBALS['mysqli'],$key)."'";
         } else {
		// insert query
        	$query="INSERT INTO ".table($table)." SET ".$keyname."='".mysqli_real_escape_string($GLOBALS['mysqli'],$key)."', ".$set_phrase;
        }
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	return $result;
}

function dump_array($array,$lb="<BR>") {
	global $lang;

	echo '<TABLE border=0>';
	foreach ($array as $key => $value) {
		echo '<TR><TD align=right>';
		if (isset($lang[$key])) echo $lang[$key]; else echo stripslashes($key);
		echo ':</TD><TD>&nbsp;</TD><TD align=left>';
		if (isset($lang[$value])) echo $lang[$value]; else echo stripslashes($value);
		echo "</TD></TR>\n";
		}
	echo '</TABLE>';
}

?>
