<?php
// part of orsee. see orsee.org

function subpools__select_field($postvarname,$var,$showvar,$selected,$hidden='') {

	$out='<SELECT name="'.$postvarname.'">';
    $query="SELECT *
    		FROM ".table('subpools')." as tsub, ".table('lang')." as tlang
			WHERE tsub.subpool_id=tlang.content_name 
			AND tlang.content_type='subjectpool' 
      		ORDER BY subpool_id";

	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	while ($line = mysqli_fetch_assoc($result)) {
		if ($line[$var] != $hidden) {
			$out.='<OPTION value="'.$line[$var].'"';
			if ($line[$var]==$selected) $out.=" SELECTED";
			$out.='>'.$line[$showvar];
			$out.='</OPTION>
				';
			}
		}
	$out.='</SELECT>';
	return $out;
}

function subpools__get_subpools() {

	$sarr=array();
    $query="SELECT *
    		FROM ".table('subpools')."  
      		ORDER BY subpool_id";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	while ($line = mysqli_fetch_assoc($result)) {
		$sarr[]=$line['subpool_id'];
	}
	return $sarr;
}


function subpools__get_subpool_name($subpool_id) {
     $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
     return $subpool['subpool_name'];
}


?>
