<?php

// html output functions. part of orsee. see orsee.org


function navigation($orientation="vertical",$icons=true) {
	global $expadmindata, $lang, $navigation_disabled;

   if (!(isset($navigation_disabled) && $navigation_disabled)) {
	if (isset($expadmindata['adminname'])) {
		$now=time();
		$current_user_data_box=$lang['admin_area'].'<BR>'.
                	$lang['user'].': <FONT color="'.$color['menu_item'].'">'.
                	$expadmindata['adminname'].'</FONT><BR>'.
                	$lang['date'].': <FONT color="'.$color['menu_item'].'">'.
                	time__format($expadmindata['language'],"",false,true,true,true,$now).'</FONT><BR>'.
                	$lang['time'].': <FONT color="'.$color['menu_item'].'">'.
                	time__format($expadmindata['language'],"",true,false,true,true,$now).'</FONT>';
		$navfile=file ("../admin/navigation.php");
		}
	   else {
		$current_user_data_box="";
		$navfile=file ("../public/navigation.php");
		}

	foreach ($navfile as $entry) if (trim($entry) && substr(trim($entry),0,2)!="//") $menuitems[]=trim($entry);

	echo tab_menu($menuitems,$orientation,$current_user_data_box,$icons);
	}
}


function html__header($print=false) {
	global $pagetitle,$settings, $color;

	if ($print) $stylesheet="stylesheet_print.css"; else $stylesheet="stylesheet.css";

echo '<HTML>
<HEAD>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<meta http-equiv="expires" content="0">
<TITLE>'.$pagetitle.'</TITLE>
<link rel="stylesheet" type="text/css" href="../style/'.$settings['style'].'/'.$stylesheet.'">

';

script__open_help();
if (thisdoc()=="admin_login.php") script__login_page();
if (thisdoc()=="faq.php") script__open_faq();

echo '
</HEAD>
<body text="'.$color['body_text'].'" link="'.$color['body_link'].'" vlink="'.$color['body_vlink'].
	'" alink="'.$color['body_alink'].'" bgcolor="'.$color['body_bgcolor'].
	'" TOPMARGIN=0 LEFTMARGIN=0 MARGINWIDTH=0 MARGINHEIGHT=0';
if (thisdoc()=="admin_login.php") echo ' onload="gotoUsername();"';
echo '>
';

}


function html__footer() {

echo '
</BODY>
</HTML>';

}


function tab_menu($menu_items,$orientation="vertical",$current_user_data_box="",$showicons=true) {
	// menu entry format:
	// info[0]       1          2      3   4     5     6        7          8
	// entrytype|menu__area|lang_item|url|icon|target|addp?|showonlyifp?|hideifp?

	global $settings__root_url, $color, $lang, $menu__area;


	if (isset($_REQUEST['p']) && !(thisdoc()=="participant_create.php")) {
                $addp="?p=".urlencode($_REQUEST['p']);
        	} 
	   else {
                $addp="";
        	}

	$target="_top";

	$list=""; $list1=""; $list2="";

	$list.='<TABLE border=0>';
	$hlist[1].='<TR>'; $hlist[2].='<TR>';

	foreach ($menu_items as $item) {

		$info=array(); $icon=""; $target="";
		$info=explode("|",$item);
        	if (substr($info[3],0,1)=="/")
                	$info[3] = $settings__root_url.$info[3];

		if (isset($info[6]) && $info[6]) $info[3].=$addp;

		if (isset($info[7]) && $info[7] && !$addp) continue;
		if ($info[8] && $addp) continue;

        	if (isset($info[4]) && $info[4]) $icon=$info[4];
        	if (isset($info[5]) && $info[5]) $target=$info[5];
        	if (isset($color['menu_item_highlighted_background']) && eregi("^".$info[1].".*",$menu__area) &&
                                eregi("link",$info[0]))
                		$bgcolor=' BGCOLOR="'.$color['menu_item_highlighted_background'].'"';
           				else $bgcolor='';

		$list.='<TR>';

        	if (eregi("head",$info[0])) {

                	$list.='<td colspan=3>&nbsp;</td></tr><TR>
                        	<td'; if ($icon && $showicons) $list.=$bgcolor; $list.='>';
                        	if ($icon && $showicons) $list.= icon($icon,$info[3]);
                	$list.= '</td>
                        	<td colspan=2 valign=middle'.$bgcolor.'>';

			$hlist_cl=1; $hlist_ncl=2;

         		} else {
                	$list.= '<td>&nbsp;</td>
                        	<td'; if ($icon) $list.= $bgcolor; $list.= '>';
                        	if ($icon && $showicons) $list.= icon($icon,$info[3]);
                	$list.= '</td>
                        	<td valign=middle'.$bgcolor.'>';

			$hlist_cl=2; $hlist_ncl=1;

        		}

			$hlist[$hlist_cl].='<TD ';
				if ($info[2]=='current_user_data_box') $hlist[$hlist_cl].=' rowspan=2'; 
				if ($icon && $showicons) $hlist[$hlist_cl].=$bgcolor;
				if ($hlist_cl==1) $$hlist[$hlist_cl].=' valign=bottom'; else $hlist[$hlist_cl].=' valign=top';
				$hlist[$hlist_cl].='>';
                                if ($icon && $showicons) $hlist[$hlist_cl].= icon($icon,$info[3]);
                                $hlist[$hlist_cl].='</TD><TD '.$bgcolor;
				if ($info[2]=='current_user_data_box') $hlist[$hlist_cl].=' rowspan=2';
				if ($hlist_cl==1) $$hlist[$hlist_cl].=' valign=bottom'; else $hlist[$hlist_cl].=' valign=top';
				$hlist[$hlist_cl].='>';

        	if (eregi("link",$info[0])) {
    			$list.= '<A HREF="'.$info[3].'" target="'.$target.'" class="menu_item"><FONT color="'.$color['menu_item'].'">';
			$hlist[$hlist_cl].='<A HREF="'.$info[3].'" target="'.$target.'" class="menu_item"><FONT color="'.
					$color['menu_item'].'">';
			}

        	if (eregi("head",$info[0])) {
                	$list.= '<FONT class="menu_title"><FONT color="'.$color['menu_title'].'">';
			$hlist[$hlist_cl].='<FONT class="menu_title"><FONT color="'.$color['menu_title'].'">';
			}

        	if ($info[2]=='current_user_data_box') {
				$list.= $current_user_data_box;
				$hlist[$hlist_cl].= $current_user_data_box;
				}
			else {
				$list.= $lang[$info[2]];
				$hlist[$hlist_cl].= $lang[$info[2]];
				}

        	if (eregi("head",$info[0])) {
                	$list.= '</FONT></FONT>';
			$hlist[$hlist_cl].= '</FONT></FONT>';
			}

        	if (eregi("link",$info[0])) {
                	$list.= '</FONT></A>';
			$hlist[$hlist_cl].= '</FONT></A>';
			}

        	$list.= '</TD>
                	</TR>';

		$hlist[$hlist_cl].='</TD>
				';

		if (($hlist_cl==$last_hcl || !$info[2]) && $info[2]!='current_user_data_box')
			$hlist[$hlist_ncl].='<TD></TD><TD></TD>
				';

		$last_hcl=$hlist_cl;
		if ($info[2]=='current_user_data_box') $last_hcl=0;
		}

	$list.= '</TABLE>';

	$hlist[$hlist_cl].='</TR>';
        $hlist[$hlist_ncl].='</TR>';

	$hor_list='<TABLE border=0>'.$hlist[1].$hlist[2].'</TABLE>';

	if ($orientation=="horizontal") return $hor_list;
		else return $list;

}

function get_style_array() {
	global $settings__root_directory, $settings__root_to_server;

	$path=$settings__root_to_server.$settings__root_directory."/style";

   	$dir_arr = array () ;
   	$handle=opendir($path);
   	while ($file = readdir($handle)) {            
         	if ($file != "." && $file != ".." && is_dir($path."/".$file)) {                    
           		$dir_arr[] = $file ;        
       			}
   		}
   	return $dir_arr ;
}

?>
