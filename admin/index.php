<?php
ob_start();

$menu__area=mainpage;
$title="Welcome";

include("header.php");

	echo '<BR><BR><center>
		';

	show_message();

	echo content__get_content("admin_mainpage");

	echo '</center><BR><BR>';

include("footer.php");

?>
