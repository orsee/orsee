<?php
ob_start();
$menu__area="disabled";

$navigation_disabled=true;
include ("header.php");

echo '<BR><BR>
	<center>
		<BR>
		<TABLE width=80%><TR><TD>';
	echo content__get_content("error_temporary_disabled");
	echo '
		</TD></TR></TABLE>

		</center>';

include ("footer.php");

?>
