<?php
ob_start();

$title="options faqs";
include ("header.php");

	$allow=check_allow('faq_edit','options_main.php');

        echo '<BR><BR><BR>
                <center><h4>'.$lang['faq_long'].'</h4>
		';
	if (check_allow('faq_add'))
		echo '
                <BR>
                <form action="faq_edit.php">
                <INPUT type=submit name="addit" value="'.$lang['create_new'].'">
                </FORM>';


        // load languages
        $languages=get_languages();


        echo '<BR>
                <table border=0>
                        <TR>
                                <TD></TD>';
                        foreach ($languages as $language) {
                                        echo '<td class="small">
                                                        '.$language.'
                                                </td>';
                                }
        echo '                  <TD>
					'.$lang['this_faq_answered_questions_of_xxx'].'
				</TD>
				<TD></TD>
                        </TR>';

        $query="SELECT *
                FROM ".table('faqs').", ".table('lang')."
                WHERE content_type='faq_question'
		AND ".table('faqs').".faq_id=".table('lang').".content_name 
                ORDER BY ".$lang['lang'];
        $result=mysql_query($query) or die("Database error: " . mysql_error());

	$faqcount=0;
        while ($line=mysql_fetch_assoc($result)) {
		$faqcount++;

                echo '  <tr class="small"';
                        echo '>
                                <td valign=top>
                                        '.$faqcount.'
                                </td>';
                foreach ($languages as $language) {
                        echo '  <td class="small"';
                                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                                echo '>
                                        '.stripslashes($line[$language]).'
                                </td>';
                        }
                echo '          <TD';
                                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                                echo '>
					'.$line['evaluation'].' '.$lang['persons'].' 
				</TD>
				<TD';
                                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                                echo '>
                                        <A HREF="faq_edit.php?faq_id='.$line['faq_id'].'">'.
                                                $lang['edit'].'</A>
                                </TD>
                        </tr>';
                if ($shade) $shade=false; else $shade=true;
                }


        echo '</table>

                </CENTER>';

include ("footer.php");

?>
