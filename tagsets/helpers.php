<?php
// part of orsee. see orsee.org

function getmicrotime($time=-1)
{
   if ($time==-1) $time=microtime();
   list($usec, $sec) = explode(" ",$time);
   return ((float)$usec + (float)$sec);
}

function support_mail_link() {
    global $settings;
    $sml='<A HREF="mailto:'.$settings['support_mail'].'">'.$settings['support_mail'].'</A>';
    return $sml;
}

function multi_array_sort(&$data, $sortby) {
    $sorting_var1=""; $sorting_var2=""; $sorting_var3="";
    if (is_array($sortby)) {
        $sorting_var1 = $sortby[0];
        if (isset($sortby[1])) $sorting_var2 = $sortby[1];
        if (isset($sortby[2])) $sorting_var3 = $sortby[2];
    } else {
        $sorting_var1 = $sortby;
    } 
    uasort($data,function ($a,$b) use ($sorting_var1, $sorting_var2, $sorting_var3) {
        if ($a[$sorting_var1]!=$b[$sorting_var1] || (!$sorting_var2)) {
            return $a[$sorting_var1]>$b[$sorting_var1];
        } elseif ($a[$sorting_var2]!=$b[$sorting_var2] || (!$sorting_var3)) {
            return $a[$sorting_var2]>$b[$sorting_var2];
        } else {
            return $a[$sorting_var3]>$b[$sorting_var3];
        }
    });
}

// debug output
function debug_output() {
    global $settings__time_debugging_enabled, $settings__query_debugging_enabled, $debug__script_started;
    global $debug__query_array, $debug__query_time;
    if (isset($settings__time_debugging_enabled) && $settings__time_debugging_enabled=='y') {
        if (isset($debug__script_started)) {
            $debug__script_stopped=getmicrotime();
            $time_needed=round(($debug__script_stopped-getmicrotime($debug__script_started))*1000,3);
            echo 'Overall query time: '.round($debug__query_time*1000,3).'msec,<br>
                 Overall script time: '.$time_needed.'msec<BR><BR>';
        } else {
            echo 'No script start time found.<BR><BR>';
        }
    }
    if (isset($settings__query_debugging_enabled) && $settings__query_debugging_enabled=='y') {
        $i=0;
        if (isset($debug__query_array)) {
            echo 'Nb of queries: '.count($debug__query_array).'<BR>
                <table border=0>';
            foreach ($debug__query_array as $query) {
                $i++;
                echo '<tr><td valign="top"><B>'.$i.'.</b></td><td valign="top">'.
                    round($query['time']*1000,3).'msec </td><td>'.
                    str_replace(array("\n",","),array("<br />",", "),$query['query']).
                '</td></tr>';
            }
            echo '</table>';
        }
    }
}


/////////////////////////////
/// NEW TIME FUNCTIONS
function ortime__unixtime_to_sesstime($unixtime=-1) {
        if ($unixtime<0) $unixtime=time();
        return date("YmdHi",$unixtime);
}

function ortime__sesstime_to_unixtime($t) {
    $a=ortime__sesstime_to_array($t); extract($a);
    $unixtime=mktime($h,$i,$s,$m,$d,$y);
    return $unixtime;
}

function ortime__add_hourmin_to_sesstime($t,$h,$m=0) {
    $old_utime=ortime__sesstime_to_unixtime($t);
    $new_utime= $old_utime + ( (int) $h * 3600) + ( (int) $m * 60);
    return ortime__unixtime_to_sesstime($new_utime);
}



function ortime__sesstime_to_array($t) {
    $a=array();
        $a['y']=substr($t,0,4); $a['m']=substr($t,4,2); $a['d']=substr($t,6,2);
        $a['h']=(strlen($t)>8)?substr($t,8,2):12;
        $a['i']=(strlen($t)>10)?substr($t,10,2):0;
        $a['s']=(strlen($t)>12)?substr($t,12,2):0;
    foreach(array('h','i','s') as $v) $a[$v]=helpers__pad_number($a[$v],2);
        return $a;
}

function ortime__array_to_sesstime($a,$pre='') {
    $shortcuts=array('y','m','d','h','i');
    foreach ($shortcuts as $s) { if (!isset($a[$pre.$s])) $a[$pre.$s]=0; }
    return helpers__pad_number($a[$pre.'y'],4).
            helpers__pad_number($a[$pre.'m'],2).
            helpers__pad_number($a[$pre.'d'],2).
            helpers__pad_number($a[$pre.'h'],2).
            helpers__pad_number($a[$pre.'i'],2);
    //return $a[$pre.'y']*100000000+$a[$pre.'m']*1000000+$a[$pre.'d']*10000+$a[$pre.'h']*100+$a[$pre.'i'];
}

function is_mil_time ($time_format) {
    $ampme=strpos($time_format,"%a");
    if ("$ampme" == '0' || $ampme>0) return false;
    else return true;
}

function ortime__array_mil_time_to_array_ampm_time ($a) {
    $r=array();
    $h=($a['h']>12)?$a['h']-12:$a['h'];
    $r['h']=($h==0)?12:$h;
    $r['h']=helpers__pad_number($r['h'],2);
    $r['a']=($a['h']>=12)?"pm":"am";
    $r['i']=$a['i'];
    return $r;
}

function ortime__array_ampm_time_to_array_mil_time ($a) { // unused?
    $r=array();
    $p=strtolower($a['a']);
    $h=($a['h']==12)?0:$a['h'];
    $r['h']=($p=='pm')?$h+12:$h;
    $r['h']=helpers__pad_number($r['h'],2);
    $r['i']=$a['i'];
    return $r;
}

function ortime__get_weekday($unixtime,$language='') {
    global $lang, $expadmindata, $settings;
    if (!$language) {
        if (isset($lang['lang']) && $lang['lang']) {
            $language=$lang['lang'];
        } else {
            if (isset($expadmindata['language']) && $expadmindata['language']) {
                $language=$expadmindata['language'];
            } else {
                $language=$settings['public_standard_language'];
            }
        }
    }
    $w_index=date("w",$unixtime);
    if(isset($lang['lang']) && $language==$lang['lang']) {
        $wdays=$lang['format_datetime_weekday_abbr'];
    } else {
        $wdays=load_language_symbol('format_datetime_weekday_abbr',$language);
    }
    $wday_arr=explode(',',$wdays);
    $w=$wday_arr[$w_index];
    return $w;
}

function ortime__format($unixtime,$options='',$language='') {
    // possible options: hide_time hide_second hide_date hide_year
    global $lang;

    $op=array('hide_second'=>true);
    $opa=explode(",",$options);
    foreach ($opa as $o) { $to=explode(":",trim($o)); if (isset($to[1]) && trim($to[1])=="false") unset($op[$to[0]]); else $op[$to[0]]=true; }

    $arr=ortime__sesstime_to_array(ortime__unixtime_to_sesstime($unixtime));
    $p=ortime__array_mil_time_to_array_ampm_time($arr);
    $arr['h12']=$p['h'];
    $arr['a']=$p['a'];

    if (!$language) {
        if (isset($lang['lang']) && $lang['lang']) {
            $language=$lang['lang'];
        } else {
            global $expadmindata, $settings;
            if (isset($expadmindata['language']) && $expadmindata['language']) $language=$expadmindata['language'];
            else $language=$settings['public_standard_language'];
        }
    }

    if(isset($op['hide_year'])) { $fd='date_no_year';} else { $fd='date';}
    if(isset($op['hide_second'])) { $ft='time_no_sec';} else { $ft='time';}
    if(isset($lang['lang']) && $language==$lang['lang']) {
        $dformat=$lang['format_datetime_'.$fd];
        $tformat=$lang['format_datetime_'.$ft];
    } else {
        $dformat=load_language_symbol('format_datetime_'.$fd,$language);
        $tformat=load_language_symbol('format_datetime_'.$ft,$language);
    }

    $f="";
    if(!isset($op['hide_date'])) $f.=$dformat;
    if (!isset($op['hide_date']) && !isset($op['hide_time'])) $f.=" ";
    if (!isset($op['hide_time'])) $f.=$tformat;
    $arr['w']=ortime__get_weekday($unixtime,$language);
    
    $datestring=str_replace(array('%Y','%m','%d','%H','%h','%i','%s','%a','%w'),
            array($arr['y'],$arr['m'],$arr['d'],$arr['h'],
            $arr['h12'],$arr['i'],$arr['s'],$arr['a'],$arr['w']),
            $f);
    $datestring=str_replace(" ","&nbsp;",$datestring);
    return $datestring;
}

// functions for parsing dates according to format

function mult_strpos($haystack,$needle,$sort=true) {
        $result = array();
    if (is_array($needle)) {
        foreach($needle as $n) {
            $result=$result + mult_strpos($haystack,$n,false);
        }
    } else {
        $pos=0; $continue=true;
        $nlen=strlen($needle);
        while ($continue) {
            $pos=strpos(strtolower($haystack),strtolower($needle),$pos);
            if ("$pos"=='0' || $pos>0) {
                $result[$pos]=$needle;
                $pos=$pos+$nlen;
            } else { $continue=false; }
        }
    }
    if($sort) ksort($result);
        return $result;
}

function date__parse_string($string,$format) { // unused?
    // check for existence and position of %Y, %m, %d
    $vars=mult_strpos($format,array('%Y','%m','%d'));

    // check whether there are delimiters, and choose relaxed or strict format
    if (preg_match("/(".implode(").+(",$vars).")/i",$format)) {
        $ye='([0-9]{2,4})'; $me='([0-9]{1,2})'; $de='([0-9]{1,2})'; // relaxed format
    } else { $ye='([0-9]{4})'; $me='([0-9]{2})';  $de='([0-9]{2})'; } // strict format

    // build pattern
    $pattern="/".str_replace(array('%Y','%m','%d'),array($ye,$me,$de),
            preg_quote ($format,"/"))."/i";

    // extract numbers
    $vals=array();
    if (preg_match($pattern,$string,$matches)) {
        $i=1;
        foreach ($vars as $var) {
            if (isset($matches[$i])) $vals[strtolower(substr($var,1))]=$matches[$i];
            $i++;
        }
    }
    return($vals);
}


function or__format_number($number,$decimals=2) {
    return number_format($number,$decimals,
                lang('numberformat__decimal_point'),
                lang('numberformat__thousands_separator'));


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


function id_array_to_db_string($id_array) {
    $db_string="";
    if (is_array($id_array)) {
        foreach ($id_array as $k=>$v) $id_array[$k]='|'.trim($v).'|';
        $db_string=implode(",",$id_array);
    }
    return $db_string;
}

function db_string_to_id_array($db_string) {
    $in_array=explode(",",$db_string); $out_array=array();
    foreach ($in_array as $k=>$v) {
        $v=trim($v);
        if(substr($v,0,1)=='|') $v=substr($v,1);
        if(substr($v,strlen($v)-1,1)=='|') $v=substr($v,0,strlen($v)-1);
        if ($v) $out_array[$k]=$v;
    }
    return $out_array;
}

function property_array_to_db_string($property_array) {
    $db_string=""; $db_string_array=array();
    if (is_array($property_array)) {
        foreach ($property_array as $k=>$v) $db_string_array[]='|'.trim($k).'|===|'.trim($v).'|';
        $db_string=implode("+=+",$db_string_array);
    }
    return $db_string;
}

function db_string_to_property_array($db_string) {
    $property_array=array();
    if ($db_string) {
        $db_string_array=explode("+=+",$db_string);
        foreach ($db_string_array as $line) {
            $vals=explode("===",$line);
            $k=$vals[0]; $v=$vals[1];
            if(substr($v,0,1)=='|') $v=substr($v,1);
            if(substr($v,strlen($v)-1,1)=='|') $v=substr($v,0,strlen($v)-1);
            if(substr($k,0,1)=='|') $k=substr($k,1);
            if(substr($k,strlen($k)-1,1)=='|') $k=substr($k,0,strlen($k)-1);
            $property_array[$k]=$v;
        }
    }
    return $property_array;
}

function array_to_table($array) {
    echo '<TABLE>';
    foreach ($array as $row) {
        echo '<TR>';
        foreach ($row as $column) {
            echo '<TD>'.$column.'</TD>';
        }
        echo '</TR>';
    }
    echo '</TABLE>';
}

function or_array_delete_values($array,$values) {
    foreach ($values as $val) {
        if(($key = array_search($val, $array)) !== false) {
            unset($array[$key]);
        }
    }
    return $array;
}

?>
