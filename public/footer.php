<?php 
// part of orsee. see orsee.org

echo '
<center>
<BR><BR><BR>
<P class="small" align=center>';
echo $lang['for_questions_contact_xxx']; 
echo ' ';
if ($settings['support_mail']) {
 	helpers__scramblemail($settings['support_mail']);
 	echo $settings['support_mail'];
 	echo '</A>';
}
echo '.<BR><BR><BR>
	</CENTER>';

include ("../style/".$settings['style']."/html_footer.php");
html__footer();

?>
