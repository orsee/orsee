<?php
ob_start();

$title="login";

include("header.php");


	echo '<center>
		<BR><BR>';

	if ($_REQUEST['logout']) message($lang['logout']);

	if ($_REQUEST['pw']) {
		message($lang['logout']);
		message ($lang['password_changed_log_in_again']);
		}

	show_message();

	echo '	<H4>'.$lang['admin_login_page'].'</H4>
		';

	if (isset($_REQUEST['adminname']) && isset($_REQUEST['password'])) {
		$password=unix_crypt($_REQUEST['password']);
		$logged_in=admin__check_login($_REQUEST['adminname'],$password);
		if ($logged_in) {
			$expadmindata['admin_id']=$_SESSION['expadmindata']['admin_id'];
			log__admin("login");
			if ($_REQUEST['redirect']) redirect($_REQUEST['redirect']);
				else redirect("admin/index.php");
			}
		   else {
			message($lang['error_password_or_username']);
			redirect("admin/admin_login.php");
			}
		}

	check_options_exist();

	admin__login_form();

	echo '</center>';

include("footer.php");

?>
