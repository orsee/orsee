<?php
// part of orsee. see orsee.org

function laboratories__strip_lab_name($lab_text="") {
    $textarray=explode("\n",$lab_text);
    $textarray[0]=str_replace("\r","",$textarray[0]);
    return ($textarray[0]);
}

function laboratories__strip_lab_address($lab_text="") {
        $textarray=explode("\n",$lab_text);
        unset($textarray[0]);
    $address=implode("\n",$textarray);
    return $address;
}

function laboratories__select_field($postvarname,$selected) {
    global $lang;
    echo '<SELECT name="'.$postvarname.'">';
     $query="SELECT *
            FROM ".table('lang')."
            WHERE content_type='laboratory'
            AND enabled='y'
            ORDER BY order_number, content_name";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $labname=laboratories__strip_lab_name(stripslashes($line[lang('lang')]));
        echo '<OPTION value="'.$line['content_name'].'"';
        if ($line['content_name']==$selected) echo " SELECTED";
        echo '>'.$labname.'</OPTION>';
        }
    echo '</SELECT>';
}

function laboratories__get_laboratory_name($laboratory_id) {
     global $lang;
     $pars=array(':laboratory_id'=>$laboratory_id);
     $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory' AND content_name=:laboratory_id";
     $lab=orsee_query($query,$pars);
     return laboratories__strip_lab_name(stripslashes($lab[lang('lang')]));
}


function laboratories__get_laboratory_text($laboratory_id,$tlang="") {
    if (!$tlang) {
        global $lang;
        $tlang=lang('lang');
    }
    $pars=array(':laboratory_id'=>$laboratory_id);
    $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory' AND content_name=:laboratory_id";
    $lab=orsee_query($query,$pars);
    return stripslashes($lab[$tlang]);
}

function laboratories__get_laboratories($tlang="") {
    if (!$tlang) {
        global $lang;
        $tlang=lang('lang');
    }
    $labs=array();
    $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory'
            ORDER BY order_number ";
    $result=or_query($query);
    while ($lab = pdo_fetch_assoc($result)) {
        $tlab=array();
        $tlab['lab_name']=laboratories__strip_lab_name(stripslashes($lab[lang('lang')]));
        $tlab['lab_address']=laboratories__strip_lab_address(stripslashes($lab[lang('lang')]));
        $labs[$lab['content_name']]=$tlab;
    }
    return $labs;
}


?>
