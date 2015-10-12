<?php
// part of orsee. see orsee.org

// messages
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
        echo '<BR><table class="or_message" style="border-color: '.$color['message_border'].'; background: '.$color['message_background'].'; color: '.$color['message_text'].'">
                            <tr valign=top>
                            <td align=right><b>';
        echo lang('message');
        echo ':
                            </b></td>
                            <td align=left>
                            ';
        echo $message_text;
        echo '
                            </td>
                            </tr>
                </tr>
                </table>';
    }
    $_SESSION['message_text']="";
}

// URL redirecting
function redirect($url) {
    global $settings__root_url, $proceed;
    $proceed=false;
    if (preg_match("/^(http:\/\/|https:\/\/)/i",$url)) {
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


// Icons
function lang_icons_prepare() {
    $langarray=lang__get_public_langs();
    foreach ($langarray as $tlang) {
        $tlang_icon=trim(load_language_symbol('lang_icon_base64',$tlang));
        if ($tlang_icon) {
            echo '.langicon-'.$tlang.':before {
                content:url(\''.$tlang_icon.'\');
                }
            ';
        }
    }
}

function oicon($icon) {
// displays icon on options page
    return '<i class="fa fa-'.$icon.' fa-fw optionsicon"></i>';
}

function micon($icon,$link="") {
    global $settings, $color;
    $out='';
    if ($link) $out.='<A HREF="'.$link.'">';
    $out.='<i class="fa fa-'.$icon.' fa-fw menuicon" style="color: '.$color['menu_title'].';"></i>';
    if ($link) $out.='</A>';
    return $out;
}

function icon($icon,$link="",$classes="",$style="",$title="") {
    global $settings;
    // for backward comp
    if ($icon=='back') $icon='level-up';
    $out='';
    if ($link) {
        $out.='<A HREF="'.$link.'"';
        if ($title) $out.=' title="'.$title.'"';
        $out.='>';
    }
    $out.='<i class="fa fa-'.$icon;
    if ($classes) $out.=' '.$classes;
    $out.='"';
    if ($style) $out.=' style="'.$style.'"';
    $out.='></i>';
    if ($link) $out.='</A>';
    return $out;
}

// security-related functions

// authenticate with token
function site__check_token() {
    $continue=true;
    // fix the uuencode malformed url issue
    /*
    if ((!isset($_REQUEST['p'])) || (!$_REQUEST['p'])) {
        foreach ($_REQUEST as $key=>$value) {
            if (substr($key,0,1)=='p') $_REQUEST['p']='cd'.substr($key,strlen($key)-11);
        }
    }*/
    if ((!isset($_REQUEST['p'])) || (!trim($_REQUEST['p']))) $continue=false;
    if ($continue) {
        $participant_id=url_cr_decode(trim($_REQUEST['p']));
        if (!$participant_id) $continue=false;
    }
    if ($continue) return $participant_id;
    else return false;
}

// decode participant token into participant id
function url_cr_decode($value) {
    $pars=array(':crypted_id'=>$value);
    $query="SELECT participant_id FROM ".table('participants')."
            WHERE participant_id_crypt= :crypted_id";
    $decarray=orsee_query($query,$pars);
    if (is_array($decarray) && isset($decarray['participant_id'])) {
        $decoded=$decarray['participant_id'];
        return $decoded;
    } else {
        return false;
    }
}

// password encryption
function unix_crypt($value) {
    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50500) {
        $hash=password_hash($value,PASSWORD_DEFAULT);
    } else {
        $hash=crypt($value);
    }
    return $hash;
}

// password verification
function crypt_verify($submitted,$hash) {
    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50500) {
        return password_verify($submitted,$hash);
    } else {
        if (crypt($submitted,$hash)==$hash) return true;
        else return false;
    }
}

// generate participant token
function make_p_token($entropy="") {
    global $settings;
    if (isset($settings['participant_token_length']) && round($settings['participant_token_length'])>=10)
        $token_length=round($settings['participant_token_length']);
    else $token_length=15;
    $t=or_get_token($token_length,$entropy);
    return $t;
}

// generate other token
function create_random_token($entropy="") {
    global $settings;
    $token_length=20;
    $t=or_get_token($token_length,$entropy);
    return $t;
}

function get_entropy($array) {
    $entropy='';
    if (is_array($array)) { foreach ($array as $v) { if (!is_array($v)) $entropy.=$v; } }
    else $entropy=$array;
    return $entropy;
}

function or_get_token($length,$entropy="") {
    $rnd=$entropy.mt_rand();
    if (function_exists ('openssl_random_pseudo_bytes') && strpos(php_uname('s'), 'Windows') === false) {
        $rnd .= hexdec(bin2hex(openssl_random_pseudo_bytes($length)));
    }
    if (function_exists ('hash')) $hash=hash('sha256',$rnd);
    else $hash=sha1($rnd);
    return substr(base64_encode($hash),0,$length);
}

// generate random number
function crypto_rand_secure($min, $max) {
    if (function_exists ('openssl_random_pseudo_bytes') && strpos(php_uname('s'), 'Windows') === false) {
        $range = $max - $min;
        if ($range <= 0) return $min; // if min<max return min
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    } else {
        return mt_rand($min, $max); // for older PHP versions, less secure
    }
}




function helpers__scramblemail($address) {
    $address = "<a class=\"small\" href=\"mailto:$address\">";
    $temp =  chunk_split($address,3,"##");
    $temp_array =  explode("##",$temp);
    $scrambled="";

    foreach($temp_array as $piece)
        { $scrambled.="+'$piece'"; }
    $scrambled =  substr($scrambled,1, strlen($scrambled));

    $result = "<script type='text/javascript'>";
    $result.="<!--\n";
    $result.= "document.write($scrambled);\n";
    $result.="-->";
    $result.="</SCRIPT>";
    echo $result;
}

// strip HTML tags from (posted vars) array
function strip_tags_array($var,$exempt=array()) {
    if (is_array($var)) {
        foreach($var as $k=>$v) {
            if (!in_array($k,$exempt)) $var[$k]=strip_tags_array($v);
        }
    } else {
        $var=strip_tags($var);
        $var=str_replace(array('&','<','>','"',"'",'/'),
                         array('&amp;','&lt;','&gt;','&quot;','&#x27;',' &#x2F;'),
                            $var);
    }
    return $var;
}


// orsee tracking
function clearpixel() {
    global $settings__disable_orsee_tracking, $settings__root_url,$system__version;
    if(!(isset($settings__disable_orsee_tracking) && $settings__disable_orsee_tracking=='y')) {
        if (check_clearpixel()) {
            $u=$settings__root_url.'|'.$system__version;
            or_load_url('www.orsee.org','/clearpixel3.php?u='.urlencode($u));
        }
    }
}

function check_clearpixel() {
    $return=false;
    $query="SELECT * from ".table('objects')."
            WHERE item_type='clearpixel' AND item_name='clearpixel'";
    $cp=orsee_query($query);
    if (!isset($cp['item_details'])) {
        $query="INSERT IGNORE INTO ".table('objects')."
                SET item_type='clearpixel', item_name='clearpixel', item_details='".time()."'";
        $done=or_query($query);
        $return=true;
    } else {
        if (time()-$cp['item_details']>24*60*60) {
            $query="UPDATE ".table('objects')."
                    SET item_details='".time()."'
                    WHERE item_type='clearpixel' AND item_name='clearpixel'";
            $done=or_query($query);
            $return=true;
        } else $return=false;
    }
    return $return;
}

function or_load_url($host,$file) {
    $fp = fsockopen( "$host", 80, $errno, $errdesc);
    $return=false;
    if (!$fp) {
        // no connection
    } else {
        $request = "GET $file HTTP/1.0\r\n";
        $request .= "Host: $host\r\n";
        $request .= "Referer: ORSEE\r\n";
        $request .= "User-Agent: ORSEE\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $page = array();
        fwrite($fp, $request);
        while (!feof($fp))  $page[]=fgets($fp);
        fclose($fp);
        $return=implode('',$page);
    }
    return $return;
}


?>