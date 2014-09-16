<?php
// part of orsee. see orsee.org

function load_settings() {
$query="SELECT * FROM ".table('options')." 
	WHERE option_type='general' OR option_type='default'";

$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

while ($line = mysqli_fetch_assoc($result)) {
            $settings[$line['option_name']]=stripslashes($line['option_value']);
            }
mysqli_free_result($result);
return $settings;
}


function check_options_exist() {
	global $system__options;
	$existing_options=array();
	$now=time();
	$i=0;

	$query="SELECT * FROM ".table('options')."
        	WHERE option_type='general' OR option_type='default'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

	while ($line = mysqli_fetch_assoc($result)) {
            	$existing_options[$line['option_name']]=stripslashes($line['option_value']);
            	}
	mysqli_free_result($result);

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


function load_colors() {
	global $settings;

	$colorfile=array(); $color=array();
	// load color file
	$colorfile=file("../style/".$settings['style']."/colors.php");

	// parse file
	foreach ($colorfile as $line) {
		if (!($line && substr($line,0,1)!="#")) continue; 
		$citems=explode(":",$line);
		if (!(isset($citems[0]) && isset($citems[1]))) continue;
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
global $site__database_port;

	if (isset($site__database_port) && $site__database_port) {
		$GLOBALS['mysqli'] = mysqli_connect($site__database_host,$site__database_admin_username,$site__database_admin_password,$site__database_database,$site__database_port) 
			or die("Database connection failed.");
    } elseif (preg_match("/^([^:]+):([0-9]+)$/",trim($site__database_host),$matches)) {
    	$host=$matches[1]; $port=$matches[2];
    	$GLOBALS['mysqli'] = mysqli_connect($host,$site__database_admin_username,$site__database_admin_password,$site__database_database,$port) 
			or die("Database connection failed.");
    } else {
    	$GLOBALS['mysqli'] = mysqli_connect($site__database_host,$site__database_admin_username,$site__database_admin_password,$site__database_database) 
			or die("Database connection failed.");
    }
}

function clearpixel() {
global $settings__disable_orsee_tracking;
if(!(isset($settings__disable_orsee_tracking) && $settings__disable_orsee_tracking=='y')) {
	if(isset($_SERVER['SERVER_NAME'])) $host=$_SERVER['SERVER_NAME']; else $host='';
	if(isset($_SERVER['PHP_SELF'])) $uri=$_SERVER['PHP_SELF']; else $uri='';
	$url=$host.$uri;
	echo '<IMG height=1 width=1 border=0 src="http://www.orsee.org/clearpixel.php?u='.urlencode($url).'">';
}

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

	if (isset($_SESSION['message_text'])) $message_text=$_SESSION['message_text'];
	else $message_text="";

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


function redirect($url) {
global $settings__root_url;

if (preg_match("/http:\/\//i",substr($url,0,7))) {
	header("Location: ".trim($url));
	} else {
	$newurl=trim($settings__root_url."/".$url);
	header("Location: ".$newurl);
	}
if (ob_get_level() != 0) {
	ob_end_flush();
}
session_write_close();

}

function thisdoc() {
if (isset($_SERVER['SCRIPT_NAME'])) return basename($_SERVER['SCRIPT_NAME']); else return '';
}

function icon($icon,$link="") {
        global $settings;
        if (preg_match("/\.(gif|jpg|png)$/i",$icon)) $ending=""; else $ending=".png";
        $out='';
        if ($link) $out.='<A HREF="'.$link.'">';
        $out.='<IMG src="../style/'.$settings['style'].'/icons/'.$icon.$ending.'" border=0>';
        if ($link) $out.='</A>';
        return $out;
}

?>
