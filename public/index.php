<?php
ob_start();

$menu__area="mainpage";

include "header.php";

echo "<center>";

show_message();

echo content__get_content("mainpage_welcome");

if ($addp) $sign="&"; else $sign="?";

$langarray=lang__get_public_langs();

$lang_names=lang__get_language_names();

if  (count($langarray) > 1) {
echo '<BR><BR>
	switch to ';

	foreach ($langarray as $thislang) {
		if ($thislang != $authdata['language']) {
		echo '<A HREF="index.php'.$addp.$sign.'language='.$thislang.'">';
		$iconpath='../style/'.$settings['style'].'/icons/lang_'.$thislang.'.png';
		if (file_exists($iconpath)) echo icon('lang_'.$thislang);
		if ($lang_names[$thislang]) echo $lang_names[$thislang]; else echo $thislang;
		echo '</A>&nbsp;&nbsp;&nbsp;';
		}
	}
}

echo "</center>";

include "footer.php";


?>
