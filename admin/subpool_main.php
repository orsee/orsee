<?php
ob_start();

$menu__area="options";
$title="options subjectpools";
include ("header.php");

	$allow=check_allow('subjectpool_edit','options_main.php');

        echo '<BR><BR><BR>
                <center><h4>'.$lang['sub_subjectpools'].'</h4>
		';
	if (check_allow('subjectpool_add')) echo '
                <BR>
                <form action="subpool_edit.php">
                <INPUT type=submit name="addit" value="'.$lang['create_new'].'">
                </FORM>';


        echo '<BR>
                <table border=0>
                        <TR>
                                <TD></TD>';
        echo '                  <TD>
				</TD>
                        </TR>';

        $query="SELECT *
                FROM ".table('subpools')." 
                ORDER BY subpool_id";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

        while ($line=mysql_fetch_assoc($result)) {

                echo '  <tr class="small"';
			if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
                                <td valign=top>
					<A HREF="subpool_edit.php?subpool_id='.$line['subpool_id'].'">
                                        '.$line['subpool_name'].'</A>
                                </td>
                		<TD>
					'.$line['subpool_description'].' 
				</TD>
				<TD>
                                        '.$lang['subjects'].': '.
					participants__count_participants("subpool_id='".$line['subpool_id']."'").'
                                </TD>
                        </tr>';
                if ($shade) $shade=false; else $shade=true;
                }


        echo '</table>

                </CENTER>';

include ("footer.php");

?>
