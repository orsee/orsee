<?php

// laboratory functions. part of orsee. see orsee.org.

function laboratories__strip_lab_name($lab_text="") {
	$textarray=explode("\n",$lab_text);
	$textarray[0]=str_replace("\r","",$textarray[0]);
	return ($textarray[0]);
}

function laboratories__strip_lab_address($lab_text="") {
        $textarray=explode("\n",$lab_text);
        unset($textarray[0]);
	$address=implode("\n",$textarray);
	return $address;
}

function laboratories__select_field($postvarname,$selected) {
	global $lang;
	echo '<SELECT name="'.$postvarname.'">';
     	$query="SELECT *
      		FROM ".table('lang')."
		WHERE content_type='laboratory' 
		AND enabled='y' 
      		ORDER BY content_name";
	$result=mysql_query($query);
	while ($line = mysql_fetch_assoc($result)) {
		$labname=laboratories__strip_lab_name(stripslashes($line[$lang['lang']]));
		echo '<OPTION value="'.$line['content_name'].'"';
		if ($line['content_name']==$selected) echo " SELECTED";
		echo '>'.$labname.'</OPTION>';
		}
	echo '</SELECT>';
}

function laboratories__get_laboratory_name($laboratory_id) {
     global $lang;
     $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory' AND content_name='".$laboratory_id."'";
     $lab=orsee_query($query);
     return laboratories__strip_lab_name(stripslashes($lab[$lang['lang']]));
}

function laboratories__get_laboratory_address($laboratory_id) {
     global $lang;
     $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory' AND content_name='".$laboratory_id."'";
     $lab=orsee_query($query);
     return laboratories__strip_lab_address(stripslashes($lab[$lang['lang']]));
}

function laboratories__get_laboratory_text($laboratory_id,$tlang="") {
     if (!$tlang) {
     		global $lang;
		$tlang=$lang['lang'];
		}
     $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory' AND content_name='".$laboratory_id."'";
     $lab=orsee_query($query);
     return stripslashes($lab[$tlang]);
}

?>
