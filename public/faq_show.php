<?php
// part of orsee. see orsee.org
ob_start();

$title="faq";

include ("nonoutputheader.php");
html__header();
include ("../style/".$settings['style']."/help_html_header.php");

	if (!isset($_REQUEST['id'])) $_REQUEST['id']="";
	
	if (!isset($_SESSION['vote'])) $_SESSION['vote']=array();
	if (!isset($_SESSION['vote'][$_REQUEST['id']])) $_SESSION['vote'][$_REQUEST['id']]="";

	if ($_SESSION['vote'][$_REQUEST['id']]) $v_already=true; else $v_already=false;

	if (isset($_REQUEST['eval']) && !($v_already)) {

		$query="UPDATE ".table('faqs')." SET evaluation=evaluation+1 WHERE faq_id='".mysqli_real_escape_string($GLOBALS['mysqli'],$_REQUEST['id'])."'";
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		$_SESSION['vote'][$_REQUEST['id']]=true;
	
		echo '
		<SCRIPT LANGUAGE="JavaScript">
		<!--

		window.close()

  		//-->
		</SCRIPT>
		';
	
		}
	
	if (!$_REQUEST['id']) echo "No ID!<BR>";


	if (isset($_REQUEST['eval']) && $v_already) {
			message($lang['you_have_already_voted_faq']);
			//	unset($_REQUEST['id']);
		}


	show_message();

	if ($_REQUEST['id']) {

		$query="SELECT * FROM ".table('lang')." WHERE content_type='faq_question' AND content_name='".mysqli_real_escape_string($GLOBALS['mysqli'],$_REQUEST['id'])."' LIMIT 1";
		$result=orsee_query($query);
		$question=stripslashes($result[$lang['lang']]);

        	$query="SELECT * FROM ".table('lang')." WHERE content_type='faq_answer' AND content_name='".mysqli_real_escape_string($GLOBALS['mysqli'],$_REQUEST['id'])."' LIMIT 1";
        	$result=orsee_query($query);
        	$answer=stripslashes($result[$lang['lang']]);

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
