<?php

function help($topic,$link="") {
	global $lang;
	$str='<A class="small" HREF="javascript:helppopup(\'topic='.urlencode($topic).'\')">[';
	if ($link) $str=$str.$link;
		else $str=$str.$lang['help'];
	$str=$str.']</A>';
	return $str;
}

?>
