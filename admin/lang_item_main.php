<?php
// part of orsee. see orsee.org
ob_start();

if (isset($_REQUEST['item'])) $item=$_REQUEST['item']; else $item="";

$menu__area="options";
$title="options ".$item;
include ("header.php");


	$done=false;
	$formfields=participantform__load(); $allow_cat=$item;
	foreach($formfields as $f) {
		if ($f['type']=='select_lang' && $item==$f['mysql_column_name']) {
			$done=true;
			$header=isset($lang[$f['name_lang']])?$lang[$f['name_lang']]:$f['name_lang'];
			$where="";
			$order=$lang['lang'];
			$show_part_stats=true;
			$allow_cat='pform_lang_field';
		}
    }
    
	$allow=check_allow($allow_cat.'_edit','options_main.php');

    
    if (!$done) {
                switch($item) {
                        case 'public_content':
							$header=$lang['public_content'];
							$where="";
                            $order=" content_name ";
                            $show_part_stats=false;
							break;
                        case 'help':
                            $header=$lang['help'];
                            $where="";
                            $order=" content_name ";
                            $show_part_stats=false;
							$chnl2br=true;
                            break;
                        case 'mail':
                            $header=$lang['default_mails'];
                            $where="";
                            $order=" content_name ";
                            $show_part_stats=false;
							$chnl2br=true;
                            break;
                        case 'default_text':
                            $header=$lang['default_texts'];
                            $where="";
                            $order=" content_name ";
                            $show_part_stats=false;
                            break;
                        case 'laboratory':
                            $header=$lang['laboratories'];
                            $where="";
                            $order=" content_name ";
                            $show_part_stats=false;
							$chnl2br=true;
                            break;
						case 'experimentclass':
                        	$header=$lang['experiment_classes'];
                            $where=" AND content_name > 0 ";
                            $order=$lang['lang'];
                            $show_part_stats=false;
                            break;
                        }
        }

	echo '<BR><BR><BR>
		<center><h4>'.$header.'</h4>';

	if (check_allow($allow_cat.'_add')) {
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
            	</TD>
 				';
        		foreach ($languages as $language) {
                	echo '<td class="small">
							'.$language.'
					</td>';
				}
	if ($show_part_stats) echo '<td class="small">'.$lang['participants'].'</td>';
	echo '			<TD></TD>
			</TR>';

	if ($show_part_stats) {
 		$num_p=array();
 		$query="SELECT ".$item." as type_p, 
 			count(*) as num_p 
 			FROM ".table('participants')." 
 			GROUP BY ".$item;
 		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysql_error($GLOBALS['mysqli']));
 		while ($line=mysqli_fetch_assoc($result)) {
 			$num_p[$line['type_p']]=$line['num_p'];
 		}
 	}

	$query="SELECT *
      		FROM ".table('lang')."
		WHERE content_type='".$item."' 
		 ".$where."
      		ORDER BY ".$order;
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

	$shade=false;
	if (mysqli_num_rows($result)>0) {

	while ($line=mysqli_fetch_assoc($result)) {

   		echo '	<tr class="small"'; 
   		if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
		else echo ' bgcolor="'.$color['list_shade2'].'"';
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
		if ($show_part_stats) {
			if (isset($num_p[$line['content_name']])) $np=$num_p[$line['content_name']]; else $np=0;
			echo '<td class="small">'.$np.'</td>';
		}
                       
   		echo '		<TD>
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
