<?php
ob_start();
$menu__area="privacy";
include ("header.php");

echo '<BR><BR>
	<center>
	<h4>'; echo $lang['privacy_policy'];
	echo '</h4>
		<BR>
		<TABLE width=70%><TR><TD>';
	echo content__get_content("privacy_policy");
	echo '
		</TD></TR></TABLE>

		</center>';

include ("footer.php");

?>
