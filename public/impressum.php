<?php
ob_start();
$menu__area="impressum";
include ("header.php");

echo '<BR><BR>
	<center>
		<BR>
		<TABLE width=80%><TR><TD>';
	echo content__get_content("impressum");
	echo '
		</TD></TR></TABLE>

		</center>';

include ("footer.php");

?>
