<?php
ob_start();
$menu__area="contact";
include ("header.php");

echo '<BR><BR>
	<center>
	<h4>'; echo $lang['contact'];
	echo '</h4>
		<BR>
		<TABLE width=80%><TR><TD>';
	echo content__get_content("contact");
	echo '
		</TD></TR></TABLE>

		</center>';

include ("footer.php");

?>
