<?php

// subpool functions. part of orsee. see orsee.org.

function subpools__select_field($postvarname,$var,$showvar,$selected,$hidden='') {

	echo '<SELECT name="'.$postvarname.'">';
     	$query="SELECT *
      		FROM ".table('subpools')." as tsub, ".table('lang')." as tlang
		WHERE tsub.subpool_id=tlang.content_name 
		AND tlang.content_type='subjectpool' 
      		ORDER BY subpool_id";

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


function subpools__get_subpool_name($subpool_id) {
     $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
     return $subpool['subpool_name'];
}


?>
