<?php
ob_start();
$menu__area="rules";
include ("header.php");

echo '<BR><BR>
	<center>
	<h4>'; echo $lang['rules'];
	echo '</h4>
		<BR>
		<TABLE width=70%><TR><TD>';
	echo content__get_content("rules");
	echo '
		</TD></TR></TABLE>

		</center>';

include ("footer.php");

?>
