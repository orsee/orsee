<?php

// important functions for whole orsee system. part of orsee. see orsee.org

function site__test() {
$test="Test succeeded!";
return $test;
}


function load_settings() {
$query="SELECT * FROM ".table('options')." 
	WHERE option_type='general' OR option_type='default'";

$result=mysql_query($query);

while ($line = mysql_fetch_assoc($result)) {
            $settings[$line['option_name']]=stripslashes($line['option_value']);
            }
mysql_free_result($result);
return $settings;
}


function check_options_exist() {
	global $system__options;
	$existing_options=array();
	$now=time();
	$i=0;

	$query="SELECT * FROM ".table('options')."
        	WHERE option_type='general' OR option_type='default'";
	$result=mysql_query($query);

	while ($line = mysql_fetch_assoc($result)) {
            	$existing_options[$line['option_name']]=stripslashes($line['option_value']);
            	}
	mysql_free_result($result);

	foreach ($system__options as $option) {
		$option_array=explode(":",$option);
		$option_array[3]=$now+$i;
		if (!isset($existing_options[$option_array[1]])) {
			$done=create_new_option($option_array);
			$i++;
			}
		}

}


function create_new_option($option_array) {
	$new_option=array();
	$new_option['option_type']=$option_array[0];
	$new_option['option_name']=$option_array[1];
	$new_option['option_value']=$option_array[2];
	$new_option['option_id']=$option_array[3];
	$done=orsee_db_save_array($new_option,"options",$new_option['option_id'],"option_id");
}


function load_colors_old() {
$query="SELECT * FROM ".table('options')." 
        WHERE option_type='color'";

$result=mysql_query($query);

while ($line = mysql_fetch_assoc($result)) {
            $colors[$line['option_name']]=stripslashes($line['option_value']);
            }
mysql_free_result($result);
return $colors;
}


function load_colors() {
	global $settings;

	$colorfile=array(); $color=array();
	// load color file
	$colorfile=file("../style/".$settings['style']."/colors.php");

	// parse file
	foreach ($colorfile as $line) {
		if (!($line && substr($line,0,1)!="#")) continue; 
		$citems=explode(":",$line);
		if (!(trim($citems[0]) && trim($citems[1]))) continue;
		$color[trim($citems[0])]=trim($citems[1]);
		}
	return $color;
}


function site__database_config() {
global $site__database_host;
global $site__database_admin_username;
global $site__database_admin_password;
global $site__database_database;
global $link;

$link = mysql_connect($site__database_host,$site__database_admin_username,$site__database_admin_password)
       or die("Database connection failed: " . mysql_error());
mysql_select_db($site__database_database) or die("Database selection failed.");
}

function make_date($zeit=0) {
if ($zeit==0) $zeit=time();
$timestring=date("d.m.Y H:i:s",$zeit);
return $timestring;
}

function make_date_mysql($zeit) {
$timestring=substr($zeit,6,2).".".substr($zeit,4,2).".".substr($zeit,0,4)." ".substr($zeit,8,2).":".substr($zeit,10,2).":".substr($zeit,12,2);
return $timestring;
}

function message($new_message,$icon="") {
        $message_text=$_SESSION['message_text'];

        if ($message_text) $seperator="<BR>"; else $seperator="";

	if ($icon) $new_message=icon($icon).' '.$new_message;

        $_SESSION['message_text']=$message_text.$seperator.$new_message;
        }

function show_message() {
	global $lang, $color;

	$numargs = func_num_args();
	if ($numargs>0) message(func_get_arg(0));

	$message_text=$_SESSION['message_text'];

        if ($message_text) {
			echo '<BR><table bgcolor="'.
				$color['message_border'].'" noshade width=400 CELLPADDING="2" CELLSPACING="0">
      				 <tr>
        				<td align=center>
          				<table bgcolor="'.$color['message_background'].'" 
                				border=0 width=100% CELLPADDING="4" CELLSPACING="0">
            					<tr valign=top>
            					<td align=right> <font color="'.$color['message_text'].'"><b>';
					echo $lang['message'];
					echo ':
                				</b></font> </td>
              					<td align=left>
                				<font color="'.$color['message_text'].'">';
					echo $message_text;
					echo '</font>
              					</td>
            					</tr>
          				</table>
        				</td>
      				</tr>
    				</table>';
			}
        $_SESSION['message_text']="";

        }

function copyright() {

echo '<FONT class="small">programming 2002 by ben greiner</FONT>';

}


function switch_to_lang($new_lang) {
global $lang;
include($settings__root_directory."/lang/".$new_lang."/words.lang");
}


function redirect($url) {
global $settings__root_url;

if (eregi("http://",substr($url,0,7))) {
	header("Location: ".$url);
	} else {
	$newurl=$settings__root_url."/".$url;
	header("Location: ".$newurl);
	}
ob_end_flush();
session_write_close();

}

function thisdoc() {
return basename($_SERVER['SCRIPT_NAME']);
}

function icon($icon,$link="") {
	global $settings;
	$out='';
	if ($link) $out.='<A HREF="'.$link.'">';
	$out.='<IMG src="../style/'.$settings['style'].'/icons/'.$icon.'.png" border=0>';
	if ($link) $out.='</A>';
	return $out;
}

?>
