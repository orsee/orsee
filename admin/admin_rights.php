<?php
ob_start();

$menu__area="options";
$title="my rights";
include ("header.php");

	echo '<center>
		<BR><BR>
			<h4>'.$lang['my_rights'].'</h4>
		';

	$rights=$expadmindata['rights'];

	echo '<TABLE border=0>
		<TR>
			<TD>'.$lang['authorization'].'</TD>
			<TD>'.$lang['description'].'</TD>
		</TR>';

	$shade=true; $lastclass="";
	foreach ($system__admin_rights as $right) {
		$line=explode(":",$right);
		if ($rights[$line[0]]) {
			$tclass=str_replace(strstr($line[0],"_"),"",$line[0]);
			if ($tclass!=$lastclass) {
				echo '<TR><TD colspan=4>&nbsp;</TD></TR>';
				$lastclass=$tclass; //$shade=true;
				}
			echo '	<TR bgcolor="';
				if ($shade) echo $color['list_shade1']; else echo $color['list_shade2'];
				echo '">
					<TD class="small" align=left>
                                        	'.$line[0].'
                                	</TD>
					<TD class="small">
						'.$line[1].'
					</TD>
			  </TR>';
			if ($shade) $shade=false; else $shade=true;
			}
		}
	echo '	</TABLE>
		</center>
		<BR><BR>';


include ("footer.php");

?>
