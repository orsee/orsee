<?php
// some database functions for orsee. part of orsee. see orsee.org


function table($table) {
	global $site__database_table_prefix;
	$tablename=$site__database_table_prefix.$table;
	return $tablename;
}

// very convenient functions translated from metahtml

function orsee_query($query,$funcname="") {

	$result=mysql_query($query)
        	or die("Database error: " . mysql_error());
	if ($funcname) {
		$back=array();
        	while ($line = mysql_fetch_assoc($result)) {
                	$back[]=$funcname($line);
                	}
        	mysql_free_result($result);
		return $back;

         } else {
        	$line=mysql_fetch_assoc($result);
        	mysql_free_result($result);
        	return $line;
        	}
}

function orsee_db_load_array($table,$key,$keyname) {
        $query="SELECT * FROM ".table($table)." where ".$keyname."='".$key."'";
        $line=orsee_query($query);
        return $line;
}


function orsee_db_save_array($array,$table,$key,$keyname) {
	global $site__database_database;

	// find out which fields i can save
	$fields = mysql_list_fields($site__database_database,table($table));
	$column_count = mysql_num_fields($fields);
	for ($i = 0; $i < $column_count; $i++) {
    		$columns[]=mysql_field_name($fields, $i);
	}

	// delete key
	if (isset($array[$keyname])) unset($array[$keyname]);
	$arraykeys=array_keys($array);
	$fields_to_save=array_intersect($arraykeys,$columns);
	// build set phrase
	$first=true; $set_phrase="";
	foreach ($fields_to_save as $field) {
		if ($first) $first=false; else $set_phrase=$set_phrase.", ";
		$set_phrase=$set_phrase.$field."='".mysql_escape_string($array[$field])."'";
		}

	// check if already saved
	$query="SELECT ".$keyname." FROM ".table($table)." WHERE ".$keyname."='".$key."'";
	$result=mysql_query($query)
        	or die("Database error: " . mysql_error());
	$num_rows = mysql_num_rows($result);

	if ($num_rows>0) {
		// update query
        	$query="UPDATE ".table($table)." SET ".$set_phrase." WHERE ".$keyname."='".$key."'";
         } else {
		// insert query
        	$query="INSERT INTO ".table($table)." SET ".$keyname."='".$key."', ".$set_phrase;
        }
	$result=mysql_query($query)
		or die("Database error: " . mysql_error());
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
