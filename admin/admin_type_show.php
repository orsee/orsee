<?php
ob_start();

$title="show admin types";
$menu__area="options";
include ("header.php");

	$allow=check_allow('admin_type_edit','options_main.php');

	echo '<center><BR><br>
        	<h4>'.$lang['admin_types'].'</h4>

		<br>
		<form action="admin_type_edit.php">
		<input type=submit name="new" value="'.$lang['create_new'].'">
		</form>';

	echo '<br>

		<table border=0 width=80%>
			<tr>
				<td class="small">'.$lang['name'].'</td>
				<td class="small">'.$lang['rights'].'</td>
				<td></td>
			</tr>';

     	$query="SELECT * FROM ".table('admin_types')." ORDER BY type_name";
	$result=mysql_query($query) or die("Database error: " . mysql_error());

	while ($type=mysql_fetch_assoc($result)) {

                echo '<tr class="small"';
			if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                                else echo ' bgcolor="'.$color['list_shade2'].'"';
			echo '>

                        	<td>
                                	'.$type['type_name'].'
                        	</td>
                        	<td class="small">
                                	'.str_replace(",",", ",$type['rights']).'
                        	</td>
                        	<td>
                                	<a href="admin_type_edit.php?type_id='.$type['type_id'].'">'.$lang['edit'].'</a>
                        	</td>
                	</tr>';

                if ($shade) $shade=false; else $shade=true;
		}

	echo '</table>

                <br><br>
		</center>';


include ("footer.php");
?>
