<?php
ob_start();

$menu__area="options";
$title="options experiment types";
include ("header.php");

	$allow=check_allow('experimenttype_edit','options_main.php');

        echo '<BR><BR><BR>
                <center><h4>'.$lang['experiment_types'].'</h4>
		';
	if (check_allow('experimenttype_add')) {
                echo '<BR>
                <form action="experiment_type_edit.php">
                <INPUT type=submit name="addit" value="'.$lang['create_new'].'">
                </FORM>';
		}


        echo '<BR>
                <table border=0>
                        <TR>
                                <TD></TD>';
        echo '                  <TD>
				</TD>
                        </TR>';

        $query="SELECT *
                FROM ".table('experiment_types')." 
                ORDER BY exptype_id";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

        while ($line=mysql_fetch_assoc($result)) {

                echo '  <tr class="small"';
			if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
                                <td valign=top>
					<A HREF="experiment_type_edit.php?exptype_id='.$line['exptype_id'].'">
                                        '.$line['exptype_name'].'</A>
                                </td>
                		<TD>
					'.$line['exptype_description'].' 
				</TD>
				<TD>
					'.$line['exptype_mapping'].'
                                </TD>
                        </tr>';
                if ($shade) $shade=false; else $shade=true;
                }


        echo '</table>

                </CENTER>';

include ("footer.php");

?>
