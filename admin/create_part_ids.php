<?php
include ("nonoutputheader.php");
$num_ids=100;


$i=0;
$ids=array();
while ($i<$num_ids) {
	$new_id=participant__create_participant_id();
	if (!in_array($new_id,$ids)) {
		$ids[]=$new_id;
		$i++;
	}
}

foreach($ids as $id) {
	echo $id."\t".unix_crypt($id)."\n";
}

?>
