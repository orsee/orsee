<?php
ob_start();

if (isset($_REQUEST['item'])) $item=$_REQUEST['item']; else $item="";

$menu__area="options";
$title="options ".$item;
include ("header.php");

	$allow=check_allow($item.'_edit','options_main.php');

                switch($item) {
                        case 'field_of_studies':
						$header=$lang['studies'];
						$where=" AND content_name > 0 ";
						$order=$lang['lang'];
						break;
                        case 'profession':
						$header=$lang['professions']; 
						$where=" AND content_name > 0 ";
                                                $order=$lang['lang'];
						break;
                        case 'public_content':
						$header=$lang['public_content'];
						$where="";
                                                $order=" content_name ";
						break;
                        case 'help':
                                                $header=$lang['help'];
                                                $where="";
                                                $order=" content_name ";
						$chnl2br=true;
                                                break;
                        case 'mail':
                                                $header=$lang['default_mails'];
                                                $where="";
                                                $order=" content_name ";
						$chnl2br=true;
                                                break;
                        case 'default_text':
                                                $header=$lang['default_texts'];
                                                $where="";
                                                $order=" content_name ";
                                                break;
                        case 'laboratory':
                                                $header=$lang['laboratories'];
                                                $where="";
                                                $order=" content_name ";
						$chnl2br=true;
                                                break;
			case 'experimentclass':
                                                $header=$lang['experiment_classes'];
                                                $where=" AND content_name > 0 ";
                                                $order=$lang['lang'];
                                                break;
                        }

	echo '<BR><BR><BR>
		<center><h4>'.$header.'</h4>';

	if (check_allow($item.'_add')) {
		echo '	<BR>
			<form action="lang_item_edit.php">
			<INPUT type=hidden name="item" value="'.$item.'">
			<INPUT type=submit name="addit" value="'.$lang['create_new'].'">
			</FORM>';
		}


	// load languages
	$languages=get_languages();


	echo '<BR>
		<table border=0>
			<TR>
				<TD class="small">
					'.$lang['id'].'
				</TD>';
        		foreach ($languages as $language) {
                			echo '<td class="small">
							'.$language.'
						</td>';
				}
	echo '			<TD></TD>
			</TR>';


	$query="SELECT *
      		FROM ".table('lang')."
		WHERE content_type='".$item."' 
		 ".$where."
      		ORDER BY ".$order;
	$result=mysql_query($query) or die("Database error: " . mysql_error());

	if (mysql_num_rows($result)>0) {

	while ($line=mysql_fetch_assoc($result)) {

   		echo '	<tr class="small"'; 
   			echo '>
				<td class="small" valign=top>
					'.$line['content_name'].'
				</td>';
		foreach ($languages as $language) {
                	echo '	<td class="small" valign=top';
                                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                                echo '>';
				if (isset($chnl2br) && $chnl2br) echo nl2br(stripslashes($line[$language]));
                        		else echo stripslashes($line[$language]);
                        echo '</td>';
                        }
   		echo '		<TD';
				if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
				echo '>
					<A HREF="lang_item_edit.php?item='.$item.'&id='.$line['lang_id'].'">'.
						$lang['edit'].'</A>
				</TD>
   			</tr>';
   		if ($shade) $shade=false; else $shade=true;
		}
		
		}
	   else {
		echo '	<tr>
				<td>
					'.$lang['no_items_found'].'
				</td>
			</tr>';
		}

	echo '</table>

		</CENTER>';

include ("footer.php");

?>
