<?php 

echo '
<center>
<BR><BR><BR>
<P class="small" align=center>';
echo $lang['for_questions_contact_xxx']; 
echo ' <A class="small" HREF="mailto:';
echo $settings['support_mail'].'">'.icon('email').' '.$settings['support_mail'];
echo '</A>.<BR>
	<BR><BR>

	</CENTER>';

include ("../style/".$settings['style']."/html_footer.php");
html__footer();

?>
