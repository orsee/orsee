<?php
ob_start();

$title="show admin";
$menu__area="options";
include ("header.php");

	$allow=check_allow('admin_edit','options_main.php');

	echo '<center><BR><br>
        	<h4>'.$lang['edit_administrators'].'</h4>

		<br>
		<form action="admin_edit.php">
		<input type=submit name="new" value="'.$lang['create_new'].'">
		</form>';

	echo '<br>

		<table border=0>
			<tr>
				<td>'.$lang['username'].'</td>
				<td>'.$lang['firstname'].'</td>
				<td>'.$lang['lastname'].'</td>
				<td>'.$lang['type'].'</td>
				<td>'.$lang['is_experimenter'].'</td>
				<td></td>
			</tr>';

     	$query="SELECT * FROM ".table('admin')." ORDER BY adminname";
	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$emails=array();
	while ($admin=mysql_fetch_assoc($result)) {

        	if ($admin['email']) $emails[]=$admin['email'];
                echo '<tr class="small"';
			if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                                else echo ' bgcolor="'.$color['list_shade2'].'"';
			echo '>

                        	<td>
                                	'.$admin['adminname'].'
                        	</td>
                        	<td>
                                	'.$admin['fname'].'
                        	</td>
                        	<td>
                                	'.$admin['lname'].'
                        	</td>
				<td>
					'.$admin['admin_type'].'
				</td>
				<td>
					'; if ($admin['is_experimenter']=='n') echo $lang['n']; else echo $lang['y']; echo '
                        	<td>
                                	<a href="admin_edit.php?admin_id='.$admin['admin_id'].'">'.$lang['edit'].'</a>
                        	</td>
                	</tr>';

                if ($shade) $shade=false; else $shade=true;
		}

	echo '</table>

                <br><br>';

	echo '<A HREF="mailto:'.$settings['support_mail'].'?bcc='.implode(",",$emails).'">'.$lang['write_message_to_all_listed'].'</A>';

	echo '<br><br>
		</center>';


include ("footer.php");
?>
