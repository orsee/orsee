<?php
ob_start();

$title="faq";

include ("nonoutputheader.php");
html__header();
include ("../style/".$settings['style']."/help_html_header.php");


	if ($vote[$_REQUEST['id']]) $v_already=true;


	if (isset($_REQUEST['eval']) && !($v_already)) {

		$query="UPDATE ".table('faqs')." SET evaluation=evaluation+1 WHERE faq_id=".$_REQUEST['id'];
		$done=mysql_query($query) or die("Database error: " . mysql_error());
		$vote[$_REQUEST['id']]=true;
		session_register(vote);

		echo '
		<SCRIPT LANGUAGE="JavaScript">
		<!--

		window.close()

  		//-->
		</SCRIPT>
		';

		}

	if (!isset($_REQUEST['id'])) echo "No ID!<BR>";


	if (isset($_REQUEST['eval']) && $v_already) {
			message($lang['you_have_already_voted_faq']);
			//	unset($_REQUEST['id']);
		}


	show_message();

	if (isset($_REQUEST['id'])) {

		$query="SELECT * FROM ".table('lang')." WHERE content_type='faq_question' AND content_name='".$_REQUEST['id']."' LIMIT 1";
		$result=orsee_query($query);
		$question=$result[$lang['lang']];

        	$query="SELECT * FROM ".table('lang')." WHERE content_type='faq_answer' AND content_name='".$_REQUEST['id']."' LIMIT 1";
        	$result=orsee_query($query);
        	$answer=$result[$lang['lang']];

		// FAQ print out
		echo '
  			<TABLE width=430>
  				<TR>
					<TD>
						'.$lang['question'].'
					</TD>
					<TD>
						'.$question.'
					</TD>
				</TR>
  				<TR>
					<TD valign="top">
						'.$lang['answer'].'
					</TD>
					<TD>
						'.$answer.'
					</TD>
				</TR>';

		if (!$v_already) {
  			echo '	<TR>
					<TD></TD>
        				<TD>
						<A HREF="'.thisdoc().'?eval=true&id='.$_REQUEST['id'].'">'.
						$lang['this_faq_answered_my_question'].'</A>
  					</TD>
				</TR>';
			}

		echo '  </TABLE>';

		}

include ("../style/".$settings['style']."/help_html_footer.php");
html__footer();
?>
