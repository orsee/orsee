<?php 

	echo '
		<br><BR><BR>
		<center>';

	if (!(preg_match("(admin_login|admin_logout|index.php)",thisdoc())))
		echo '
			'.icon('home','index.php').'<A href="index.php">'.$lang['mainpage'].'</a>
			<BR><BR>
			';
	if (!(preg_match("(admin_login|admin_logout)",thisdoc())))
		echo '<A href="admin_logout.php">'.icon('logout').'<FONT COLOR=RED>'.$lang['logout'].'</FONT></A>';

	echo '</center>';

include ("../style/".$settings['style']."/html_footer.php");
html__footer();

?>
