<?php
// part of orsee. see orsee.org
include ("nonoutputheader.php");
$pagetitle.=$lang['help'].": ".preg_replace("/_/"," ",$_REQUEST['topic']);
html__header();

include ("../style/".$settings['style']."/help_html_header.php");

	if (!$_REQUEST['topic']) {
		echo '<h4>'.$lang['no_topic_choosed'].'</h4>';
		}
	   elseif ($_REQUEST['topic']=="login_names") {
		echo '<TABLE>
			<TR bgcolor="'.$color['list_title_background'].'">
				<TD>'.$lang['name'].'</TD>
				<TD>'.$lang['login_name'].'</TD>
			</TR>';

     		$query="SELECT *
      			FROM ".table('admin')." 
      			ORDER BY lname, fname, adminname";
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

		while ($line=mysqli_fetch_assoc($result)) {
        		echo '<TR><TD>';
			echo $line['fname'];
			echo ' ';
			echo $line['lname'];
			echo '</TD>
                		<TD align=right>';
			echo $line['adminname'];
			echo '</TD>
        			</TR>';
			}
		echo '</TABLE>';

		}
	   else {
		$query="SELECT * FROM ".table('lang')." WHERE content_type='help'
			AND content_name='".$_REQUEST['topic']."'";

		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));


		if (mysqli_num_rows($result)==0) {
			echo '<h4>'.$lang['no_help_available_for_topic'].'</h4>';
			}
		   else {
			$line = mysqli_fetch_assoc($result);
			if ($line[$expadmindata['language']]) $thislang=$expadmindata['language'];
				elseif ($line[$settings['admin_standard_language']]) $thislang=$settings['admin_standard_language']; 
				else $thislang="en";

            		echo str_replace("\n","<BR>",stripslashes($line[$thislang]));
			}

		mysqli_free_result($result);
		}

include ("../style/".$settings['style']."/help_html_footer.php");
html__footer();

?>
