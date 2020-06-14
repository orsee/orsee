<?php
// part of orsee. see orsee.org

function lang($symbol) {
    global $lang;
    if (isset($lang[$symbol])) return $lang[$symbol];
    else return $symbol;
}

function load_language($language) {
    global $settings;
    $languages=get_languages();
    if (in_array($language,$languages)) $this_lang=$language;
    else $this_lang=$settings['public_standard_language'];
    $query="SELECT content_name, ".$this_lang." as content_value FROM ".table('lang')." WHERE content_type='lang' OR content_type='datetime_format'";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $lang[$line['content_name']]=stripslashes($line['content_value']);
    }
    return $lang;
}


function load_language_symbol($symbol,$language) {
    global $settings;
    if ($language==lang('lang')) return lang($symbol);
    else {
        $languages=get_languages();
        if (in_array($language,$languages)) $this_lang=$language;
        else $this_lang=$settings['public_standard_language'];
        $pars=array(':symbol'=>$symbol);
        $query="SELECT content_name, ".$this_lang." as content_value FROM ".table('lang')."
                WHERE (content_type='lang' OR content_type='datetime_format') and content_name= :symbol";
        $line=orsee_query($query,$pars);
        if (isset($line['content_value'])) return stripslashes($line['content_value']);
        else return $symbol;
    }
}

function language__get_item($content_type,$content_name,$language="") {
    if (!$language){
        global $lang;
        $this_lang=lang('lang');
    } elseif ($language==lang('lang')) {
        $this_lang=lang('lang');
    } else {
        $languages=get_languages();
        if (in_array($language,$languages)) $this_lang=$language;
        else $this_lang=$settings['public_standard_language'];
    }
    $pars=array(':content_type'=>$content_type,':content_name'=>$content_name);
    $query="SELECT * from ".table('lang')." WHERE content_type= :content_type AND content_name= :content_name";
    $line=orsee_query($query,$pars);
    if (isset($line[$language])) return stripslashes($line[$language]);
    else return false;
}

function language__save_item_order($content_type,$order_array) {
    $pars=array(':content_type'=>$content_type);
    $query="UPDATE ".table('lang')."
            SET order_number = -1
            WHERE content_type= :content_type";
    $done=or_query($query,$pars);

    $pars=array();
    foreach ($order_array as $k=>$v) {
        $pars[]=array(':content_type'=>$content_type,
                    ':content_name'=>$v,
                    ':order_number'=>$k);
    }
    $query="UPDATE ".table('lang')."
            SET order_number = :order_number
            WHERE content_type = :content_type
            AND content_name = :content_name";
    $done=or_query($query,$pars);
    return $done;
}

function language__selectfield_item($content_type,$ptablevarname,$formfieldvarname,$selected,$incnone=false,$order='alphabetically') {
    $language=lang('lang');
    if (!$selected) $preval=0;
    $items=array();
    if ($order=='fixed_order') $order_by='order_number'; else $order_by=$language;
    $pars=array(':content_type'=>$content_type);
    $query="SELECT *, ".$language." AS item
            FROM ".table('lang')."
            WHERE content_type= :content_type
            ORDER BY ".$order_by;
    $result=or_query($query,$pars);
    while ($line = pdo_fetch_assoc($result)) {
        $items[$line['content_name']]=$line['item'];
    }

    $out='';
    $out.='<SELECT name="'.$formfieldvarname.'">';
    if ($incnone) { $out.='<OPTION value="0"'; if ($selected==0) $out.=' SELECTED'; $out.='>-</OPTION>'; }
    foreach ($items as $k=>$v) {
        $out.='<OPTION value="'.$k.'"';
        if ($selected==$k) $out.=' SELECTED';
        $out.='>'.$v.'</OPTION>';
    }
    $out.='</SELECT>';
    return $out;
}

function language__radioline_item($content_type,$ptablevarname,$formfieldvarname,$selected,$incnone=false,$order='alphabetically') {
    $language=lang('lang');
    if (!$selected) $preval=0;
    $items=array();
    if ($order=='fixed_order') $order_by='order_number'; else $order_by=$language;
    $pars=array(':content_type'=>$content_type);
    $query="SELECT *, ".$language." AS item
            FROM ".table('lang')."
            WHERE content_type= :content_type
            ORDER BY ".$order_by;
    $result=or_query($query,$pars);
    while ($line = pdo_fetch_assoc($result)) {
        $items[$line['content_name']]=$line['item'];
    }

    $out='';
    foreach ($items as $k=>$v) {
        $out.='<INPUT type="radio" name="'.$formfieldvarname.'" value="'.$k.'"';
        if ($selected==$k) $out.=' CHECKED';
        $out.='>'.$v.'&nbsp;&nbsp;&nbsp;';
    }
    return $out;
}


function language__multiselectfield_item($content_type,$ptablevarname,$formfieldvarname,$selected,$language="",$existing=false,$where='',$show_count=false,$multi=true,$mpoptions=array()) {
    if (!$language){
        global $lang;
        $language=lang('lang');
    }

    $items=array();
    $pars=array(':content_type'=>$content_type);
    $query="SELECT *, ".lang('lang')." AS item
            FROM ".table('lang')."
            WHERE content_type= :content_type
            ORDER BY ".pdo_escape_string($language);
    $result=or_query($query,$pars);
    while ($line = pdo_fetch_assoc($result)) {
        $items[$line['content_name']]=stripslashes($line['item']);
    }

    $mylist=array();
    if (!$existing) {
        $mylist=$items;
    } else {
        $query="SELECT count(*) as tf_count, ".pdo_escape_string($ptablevarname)." as tf_value
                FROM ".table('participants')."
                WHERE ".table('participants').".participant_id IS NOT NULL ";
        if($where) $query.=" AND ".$where." ";
        $query.=" GROUP BY ".pdo_escape_string($ptablevarname)."
                ORDER BY ".pdo_escape_string($ptablevarname);
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $thisname="";
            if (isset($items[$line['tf_value']])) $thisname.=$items[$line['tf_value']];
            elseif ($line['tf_value']==0)  $thisname.='-';
            else $thisname.=$line['tf_value'];
            if ($show_count) $thisname.=' ('.$line['tf_count'].')';
            $mylist[$line['tf_value']]=$thisname;
        }
    }

    $out="";
    if (!is_array($mpoptions)) $mpoptions=array();
    if (!isset($mpoptions['picker_icon'])) $mpoptions['picker_icon']='bars';

    if ($multi) {
        $out.= get_multi_picker($formfieldvarname,$mylist,$selected,$mpoptions);
    } else {
        $out.= '<SELECT name="'.$formfieldvarname.'">
                <OPTION value=""'; if (!is_array($selected) || count($selected)==0) $out.= ' SELECTED'; $out.= '>-</OPTION>
                ';
        foreach ($mylist as $k=>$v) {
            $out.= '<OPTION value="'.$k.'"';
                if ((is_array($selected) && $selected[0]==$out) || $selected==$k) $out.= ' SELECTED'; $out.= '>'.$v.'</OPTION>
                ';
        }
        $out.= '</SELECT>
        ';
    }
    return $out;
}

function get_languages() {
    global $preloaded_languages_list;
    if (isset($preloaded_languages_list) && is_array($preloaded_languages_list) && count($preloaded_languages_list)>0) {
        return $preloaded_languages_list;
    } else {
        $languages=array();
        $query="SELECT * FROM ".table('lang')." LIMIT 1";
        $line=orsee_query($query);
        foreach($line as $columnname=>$v) {
            if (!preg_match("(lang_id|content_name|content_type|enabled|order_number)",$columnname))
                $languages[]=$columnname;
        }
        asort($languages);
        $preloaded_languages_list=$languages;
        return $languages;
    }
}

function lang__get_part_langs() {
    global $settings;
    $part_langs=explode(",",$settings['language_enabled_participants']);
    return $part_langs;
}

function lang__get_public_langs() {
        global $settings;
        $public_langs=explode(",",$settings['language_enabled_public']);
        return $public_langs;
}

function lang__get_language_names() {
        $names=orsee_db_load_array("lang","lang_name","content_name");
        return $names;
}


function lang__select_lang($varname,$selected="",$type="all") {
    global $lang;
    switch ($type) {
        case "public": $sel_langs=lang__get_public_langs(); break;
        case "part": $sel_langs=lang__get_part_langs(); break;
        default: $sel_langs=get_languages();
        }
    if(!$selected) $selected=lang('lang');
    $lang_names=lang__get_language_names();
    $out='';
    $out.='<SELECT name="'.$varname.'">';
    foreach ($sel_langs as $olang) {
        $out.='<OPTION value="'.$olang.'"';
        if ($olang==$selected) $out.=' SELECTED';
        $out.='>'.$lang_names[$olang].'</OPTION>';
        }
    $out.='</SELECT>';
    return $out;
}



function lang__insert_to_lang($item) {
    $pars=array(':content_type'=>$item['content_type']);
    $query="SELECT max(lang_id) as lcount
            FROM ".table('lang')."
            WHERE content_type= :content_type";
    $line=orsee_query($query,$pars);
    $maxid=$line['lcount'];

    $reorganize=false; $newmax=false; $newmin=false;

    // if there is no item under this content_type
    if ($maxid==NULL) {
        $newmax=true; $reorganize=true; $newmin=false;
    } else {
        $newid=$maxid+1;
        $pars=array(':newid'=>$newid);
        $query="SELECT * FROM ".table('lang')." WHERE lang_id= :newid";
        $line=orsee_query($query,$pars);

        if (isset($line['lang_id'])) {
            $reorganize=true; $newmax=true; $newmin=true;
        }
    }

    if ($newmax) {
        $query="SELECT max(lang_id) as maxid, min(lang_id) as minid FROM ".table('lang');
        $line=orsee_query($query);
        $newid=$line['maxid']+1;
        $steps=$line['minid'];
        if ($newmin) $steps=$steps*10;
    }

    $done1=orsee_db_save_array($item,"lang",$newid,"lang_id");
    if ($reorganize) $done2=lang__reorganize_lang_table($steps);
    return $newid;
}

function lang__check_symbol_exists($symbol) {
    global $lang;
    if (isset($lang[$symbol])) {
        return true;
    } else {
        $pars=array(':symbol'=>$symbol);
        $query="SELECT count(*) as nb_symbols FROM ".table('lang')."
                WHERE (content_type='lang' OR content_type='datetime_format') and content_name= :symbol";
        $line=orsee_query($query,$pars);
        if ($line['nb_symbols']>0) {
            return true;
        } else {
            return false;
        }
    }
}

function lang__add_new_symbol($specs) {
    $languages=get_languages();
    $item=array('content_type'=>'lang','content_name'=>$specs['content_name']);
    if (isset($specs['content_type'])) {
        $item['content_type']=$specs['content_type'];
    }
    
    foreach ($languages as $thislang) {
        if(isset($specs['content'][$thislang])) {
            $item[$thislang]=$specs['content'][$thislang];
        } elseif(isset($specs['content']['en'])) {
            $item[$thislang]=$specs['content']['en'];
        } else {
            $item[$thislang]=reset($specs['content']);
        }
    }
    $done=lang__insert_to_lang($item);
}

function lang__upgrade_symbol_if_not_exists($specs) {
    $symbol_exists=lang__check_symbol_exists($specs['content_name']);
    if(!$symbol_exists) {
        lang__add_new_symbol($specs);
        log__admin("Automatic database upgrade: added language symbol '".$specs['content_name']."'.");
        return true;
    } else {
        log__admin("Automatic database upgrade: symbol '".$specs['content_name']."' not added because it alread exists.");
        return false;
    }
}

function lang__reorganize_lang_table($steps=10000) {

    if ($steps < 1000) $steps=10000;
    $move=$steps*100;
    // copy stuff
    $query="UPDATE ".table('lang')." SET lang_id=lang_id+".$move;
    $done=or_query($query);

    // insert new ordered stuff
    $i=1; $content_type="";

    // first all not-lang stuff
    $query="SELECT lang_id, content_type FROM ".table('lang')." ORDER BY content_type, content_name";
    $result=or_query($query);
    $ids=array();
    while ($line = pdo_fetch_assoc($result)) {
        $ids[]=$line;
    }

    $pars=array();
    foreach ($ids as $item) {
        if ($content_type!=$item['content_type']) {
            $content_type=$item['content_type'];
            $current_step= (int) floor($i/$steps);
            $i=($current_step + 1) * $steps;
        }
        $pars[]=array(':new_lang_id'=>$i,':old_lang_id'=>$item['lang_id']);
        $i++;
    }
    $query="UPDATE ".table('lang')." SET lang_id= :new_lang_id WHERE lang_id= :old_lang_id";
    $done=or_query($query,$pars);
    return $done;
}


function lang__load_lang_cat($content_type,$language="") {
    global $lang, $preloaded_lang_cats;
    if (!$language) $language=lang('lang');

    if (isset($preloaded_lang_cats[$content_type][$language]) &&
        is_array($preloaded_lang_cats[$content_type][$language]) &&
        count($preloaded_lang_cats[$content_type][$language])>0)
            return $preloaded_lang_cats[$content_type][$language];
    else {
        $cat=array();
        $pars=array(':content_type'=>$content_type);
        $query="SELECT content_name, ".$language." as content_value
                FROM ".table('lang')." WHERE content_type= :content_type";
        $result=or_query($query,$pars);
        while ($line = pdo_fetch_assoc($result)) {
            $cat[$line['content_name']]=stripslashes($line['content_value']);
        }
        $preloaded_lang_cats[$content_type][$language]=$cat;
        return $cat;
    }
}


/* this is a list of used, but not explicit programmed language items
    just to not forget and delete them

lang('calendar')
lang('cron_job_time_every_12_hours')
lang('cron_job_time_every_15_minutes')
lang('cron_job_time_every_2_hours')
lang('cron_job_time_every_30_minutes')
lang('cron_job_time_every_5_minutes')
lang('cron_job_time_every_6_hours')
lang('cron_job_time_every_day_at_15')
lang('cron_job_time_every_day_at_22')
lang('cron_job_time_every_day_at_3')
lang('cron_job_time_every_day_at_8')
lang('cron_job_time_every_hour')
lang('cron_job_time_every_monday_at_8')
lang('cron_job_time_every_month_at_15th_at_8')
lang('cron_job_time_every_month_at_1st_at_8')
lang('cron_job_time_every_thursday_at_8')
lang('enough_participants_needed')
lang('enough_participants_needed_plus_reserve')
lang('gender_?')
lang('impressum')
lang('internet')
lang('in_any_case_dont_ask')
lang('lang_name')
lang('my_data')
lang('my_registrations')
lang('not_enough_participants')
lang('not_enough_reserve')
lang('online-survey')
lang('overview')

*/

?>
