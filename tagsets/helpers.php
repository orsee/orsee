<?php

// helper functions for use by other functions. part of orsee. see orsee.org
// 

function getmicrotime()
{
   list($usec, $sec) = explode(" ",microtime());
   return ((float)$usec + (float)$sec);
}

function support_mail_link() {
	global $settings;
	$sml='<A HREF="mailto:'.$settings['support_mail'].'">'.$settings['support_mail'].'</A>';
	return $sml;
}

function multi_array_sort(&$data, $sortby) {
   	static $sort_funcs = array();

   	if (is_array($sortby)) {
       		$sortby = join(',',$sortby);
   		}

   	if (empty($sort_funcs[$sortby])) {
       		$code = "\$c=0;";
       		foreach (split(',', $sortby) as $key) {
           		$code .= "if ( (\$c = strcasecmp(\$a['$key'],\$b['$key'])) != 0 ) return \$c;\n";
       			}
       		$code .= 'return $c;';
       		$sort_func = $sort_funcs[$sortby] = create_function('$a, $b', $code);
   	 } else {
       		$sort_func = $sort_funcs[$sortby];
   		}
   	$sort_func = $sort_funcs[$sortby];
   	uasort($data, $sort_func);
}


function time__unixtime_to_time_package($unixtime=-1) {
	if ($unixtime<0) $unixtime=time();
	$tarr=getdate($unixtime);
        $ta['day']=$tarr['mday'];
        $ta['month']=$tarr['mon'];
        $ta['year']=$tarr['year'];
        $ta['minute']=$tarr['minutes'];
        $ta['hour']=$tarr['hours'];
        $ta['second']=$tarr['seconds'];
	return $ta;
}

// unused?
function time__build_session_timestring($unixtime="") {
	if (!$unixtime) $unixtime=time();
	$timestring=date("YmdHi",$unixtime);
	return $timestring;
}


function time__get_timepack_from_pack ($from_package,$prefix="session_start_") {
	$vars=array('day','month','year','minute','hour');
	foreach ($vars as $var) $to_package[$var]=$from_package[$prefix.$var];
        $to_package['second']='0';
	return $to_package;
}


function time__load_session_time($from_package) {
	return time__get_timepack_from_pack ($from_package,'session_start_');
}

function time__load_survey_start_time($from_package) {
	return time__get_timepack_from_pack ($from_package,'start_');
}

function time__load_survey_stop_time($from_package) {
	return time__get_timepack_from_pack ($from_package,'stop_');
}


function time__add_packages($orig,$plus,$return_unixtime=false) {
	$orig_pack=array('year'=>0,'month'=>0,'day'=>0,'hour'=>0,'minute'=>0,'second'=>0);
	$plus_pack=array('day'=>0,'hour'=>0,'minute'=>0,'second'=>0);
	foreach ($orig as $key=>$value) $orig_pack[$key]=$value;
	foreach ($plus as $key=>$value) $plus_pack[$key]=$value;

	$orig_time=time__time_package_to_unixtime($orig_pack);
	$plus_time=$plus_pack['day']*24*60*60+$plus_pack['hour']*60*60+$plus_pack['minute']*60+$plus_pack['second'];
	$new_time=$orig_time+$plus_time;
	if ($return_unixtime) return $new_time;
	else return time__unixtime_to_time_package($new_time);

}


function time__format_session_time($session_id,$language="",$package="") {
	global $authdata, $settings;
        if (!$language) {
                if (isset($authdata['language']) && $authdata['language']) $thislang=$authdata['language'];
                        else $thislang=$settings['public_standard_language'];
                }
                else {
                $thislang=$language;
                }
	if ($session_id)
        	$pack=orsee_db_load_array("sessions",$session_id,"session_id");
	   else $pack=$package;

        $session_time=time__load_session_time($pack);
	$timestring=time__format($thislang,$session_time,false,false,true,false);
        return $timestring;
}

function time__time_package_to_unixtime($tarray) {
extract($tarray);
$unixtime=mktime($hour,$minute,$second,$month,$day,$year);
return $unixtime;
}


function time__format($lang,$ttime,$hide_date=false,$hide_time=false,$hide_second=true,$hide_year=false,$unixtime="") { 

	if ($unixtime) $ta=time__unixtime_to_time_package($unixtime);
		else $ta=$ttime;

	$ta['day']=helpers__pad_number($ta['day'],2);
	$ta['month']=helpers__pad_number($ta['month'],2);
	$ta['year']=helpers__pad_number($ta['year'],4);
    if (isset($ta['minute']) && (string) $ta['minute']!="" && isset($ta['hour']) && (string) $ta['hour']!="") { 
					  $time_ex=true;
					  $ta['hour']=helpers__pad_number($ta['hour'],2);
					  $ta['minute']=helpers__pad_number($ta['minute'],2);
					  }

	if (isset($ta['second'])) $ta['second']=helpers__pad_number($ta['second'],2);

	if (!$lang) {
		global $expadmindata, $settings;
		if ($expadmindata['language']) $lang=$expadmindata['language'];
			else $lang=$settings['public_standard_language'];
		}

	$datestring="";

	// de - german
	if ($lang=="de") {
		$ready=true;
		if (!$hide_date) {
			$datestring.=$ta['day'].'.'.$ta['month'].'.';
			if (!$hide_year) $datestring.=$ta['year'];
			$datestring.=" ";
			}
		if ((!$hide_time) && $time_ex) {
			$datestring.=$ta['hour'].':'.$ta['minute'];
			if (!$hide_second && $ta['second']) $datestring.=':'.$ta['second'];
			}
		}

        // es - spanish
        if ($lang=="es") {
                $ready=true;
                if (!$hide_date) {
                        $datestring.=$ta['day'].'/'.$ta['month'];
                        if (!$hide_year) $datestring.='/'.$ta['year'];
			$datestring.=" ";
                        }
                if (!$hide_time && $time_ex) {
                        $datestring.=$ta['hour'].':'.$ta['minute'];
                        if (!$hide_second && $ta['second']) $datestring.=':'.$ta['second'];
                        }
                }

        // default en - english
        if (!$ready) {
                $ready=true;
                if (!$hide_date) {
                        $datestring.=$ta['month'].'/'.$ta['day'];
                        if (!$hide_year) $datestring.='/'.$ta['year'];
			$datestring.=" ";
                        }
                if (!$hide_time && $time_ex) {
                        $datestring.=$ta['hour'].':'.$ta['minute'];
                        if (!$hide_second && $ta['second']) $datestring.=':'.$ta['second'];
                        }
                }

	return $datestring;

}

 
function helpers__pad_number() {

if (func_num_args()>0) { $help=func_get_arg(0); $number="$help"; } else { $number="1"; }
if (func_num_args()>1) $fillzeros=func_get_arg(1); else $fillzeros=2;

$padnumber="";
$length=strlen($number);
while ($length<$fillzeros) {
	$padnumber=$padnumber."0";
	$length++;
	}
$padnumber=$padnumber.$number;
return $padnumber;
}


// Url-Coding-Functions

function get_unique_id($table,$idcol) {
           $exists=true;
           srand ((double)microtime()*1000000);
           while ($exists) {
                $crypt_id = "/";
                while (eregi("(/|\\.)",$crypt_id)) {
                        $id = rand();
                        $crypt_id=unix_crypt($id);
                        }

                $query="SELECT ".$idcol." FROM ".table($table)."
                        WHERE ".$idcol."='".$id."'";
                $line=orsee_query($query);
                if (isset($line[$idcol])) $exists=true; else $exists=false;
                }
        return $id;
}


// encryption
function unix_crypt($value) {
	$encrypted=crypt($value,"cd");
	return $encrypted;
}

// Url-Encode
function url_cr_encode($var) {
	$t=unix_crypt($var);
	$encoded=urlencode($t);
	return $encoded;
	}

// Url-Decode
function url_cr_decode($value,$temp=false) {
	$decoded=""; 
	if ($temp) {
		$query="SELECT participant_id FROM ".table('participants_temp')." 
                	WHERE participant_id_crypt='".$value."'";
		$decarray=orsee_query($query);
		$decoded=$decarray['participant_id'];
	}

	if (!$decoded) {
		$query="SELECT participant_id FROM ".table('participants')." 
                 	WHERE participant_id_crypt='".$value."'";
		$decarray=orsee_query($query);
		$decoded=$decarray['participant_id'];
		}
	return $decoded;
}

// Url-Decodec session id
function url_cr_decode_session($value) {
	$query="SELECT session_id FROM ".table('sessions')."
                WHERE session_id_crypt='".$value."'";
	$decarray=orsee_query($query);
        $decoded=$decarray['session_id'];
	return $decoded;
}


function helpers__update_encrypted($table) {

	$ids=array();

	switch ($table) {

		case "sessions": $idvar="session_id";
			 	break;
		case "participants": $idvar="participant_id";
				break;
		case "participants_temp": $idvar="participant_id";
				break;
		default: return 0;
	}

	$query="SELECT ".$idvar." FROM ".table($table);
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	while ($line=mysql_fetch_assoc($result)) {
		$ids[$line[$idvar]]=$line[$idvar];
		}

	foreach ($ids as $id) {
		$query="UPDATE ".table($table)." SET ".$idvar."_crypt='".unix_crypt($id)."'
			WHERE ".$idvar."='".$id."'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());
		}
}

?>
