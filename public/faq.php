<?php
ob_start();
$menu__area="faqs";

include("header.php");

	echo '
		<BR><BR>
		<center>
			<h4>'.$lang['faq_long'].'</h4>
		<BR>
		<TABLE width=70%>
			<TR>
				<TD>
				</TD>
				<TD width=20% class="small">
					'.$lang['this_faq_answered_questions_of_xxx'].'
				</TD>
			</TR>';

     	$query="SELECT * FROM ".table('faqs').", ".table('lang')."
	     	WHERE ".table('lang').".content_name=".table('faqs').".faq_id
	     	AND ".table('lang').".content_type='faq_question'
	     	ORDER BY ".table('faqs').".evaluation DESC, ".table('lang').".".$lang['lang'];
    	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$shade=false;
	while ($line=mysql_fetch_assoc($result)) {

		if ($shade) $shade=false; else $shade=true;
  		echo '<TR>
			<TD>
        			<A HREF="javascript:popupfaq(\'faq_show.php?id='.$line['faq_id'].'\')">';
  				if ($shade) echo '<FONT COLOR="#0000FF">'; else echo '<FONT color="#000000">';
  				echo stripslashes($line[$lang['lang']]);
  				echo '</FONT></A>
        		</TD>
			<TD>
  				'.$line['evaluation'].' '.$lang['persons'].'
			</TD>
		      </TR>';
		}
	echo '	</TABLE>
		</center>';

include ("footer.php");
?>
