<?php
// part of orsee. see orsee.org

function survey__render_field($field) {
    $out='';
    switch($field['type']) {
        case 'textline': $out=survey__render_textline($field); break;
        case 'textarea': $out=survey__render_textarea($field); break;
        case 'radioline': $out=survey__render_radioline($field); break;
        case 'select_list': $out=survey__render_select_list($field); break;
        case 'select_numbers': $out=survey__render_select_numbers($field); break;
        case 'select_yesno': $out=survey__render_select_yesno($field); break;
        case 'select_yesno_switchy': $out=survey__render_select_yesno_switchy($field); break;
        case 'date': $out=survey__render_date($field); break;
    }
    return $out;
}

function survey__render_textline($f) {
    $out='<INPUT type="text" name="'.$f['submitvarname'].'" value="'.$f['value'].'" size="'.
        $f['size'].'" maxlength="'.$f['maxlength'].'">';
    return $out;
}

function survey__render_textarea($f) {
        $out='<textarea name="'.$f['submitvarname'].'" cols="'.$f['cols'].'" rows="'.
                $f['rows'].'" wrap="'.$f['wrap'].'">'.$f['value'].'</textarea>';
        return $out;
}

function survey__render_radioline($f) {
    global $lang;
    $optionvalues=explode(",",$f['option_values']);
    $optionnames=explode(",",$f['option_values_lang']);
    $items=array();
    foreach($optionvalues as $k=>$v) {
        if (isset($optionnames[$k])) $items[$v]=$optionnames[$k];
    }
    $out='';
    foreach ($items as $val=>$text) {
        $out.='<INPUT name="'.$f['submitvarname'].'" type="radio" value="'.$val.'"';
        if ($f['value']==$val) $out.=" CHECKED";
        $out.='>';
        if (isset($lang[$text])) $out.=$lang[$text]; else $out.=$text;
        $out.='&nbsp;&nbsp;&nbsp;';
    }
    return $out;
}

function survey__render_select_list($f,$formfieldvarname='') {
    global $lang;
    if (!$formfieldvarname) $formfieldvarname=$f['submitvarname'];
    $optionvalues=explode(",",$f['option_values']);
    $optionnames=explode(",",$f['option_values_lang']);
    if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
    $items=array();
    foreach($optionvalues as $k=>$v) {
        if (isset($optionnames[$k])) $items[$v]=$optionnames[$k];
    }
    $out='';
    $out=helpers__select_text($items,$formfieldvarname,$f['value'],$incnone);
    return $out;
}

function survey__render_select_numbers($f) {
        if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
        if ($f['values_reverse']=='y') $reverse=true; else $reverse=false;
        $out=participant__select_numbers($f['submitvarname'],$f['submitvarname'],$f['value'],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,$incnone);
        return $out;
}

function survey__render_select_yesno($f) {
    global $lang;
    $items=array('y'=>'y','n'=>'n');
    if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
    $out='';
    $out=helpers__select_text($items,$f['submitvarname'],$f['value'],$incnone);
    return $out;
}

function survey__render_select_yesno_switchy($f) {
    global $lang, $ynswitch_jscode;
    if (!isset($ynswitch_jscode) || !$ynswitch_jscode) $ynswitch_jscode=false;

    $items=array('n'=>'n','y'=>'y');

    $id=uniqid();
    $out='';
    $out='<select data-elem-name="yesnoswitch" id="id'.$id.'" name="'.$f['submitvarname'].'">';
    foreach ($items as $k=>$text) {
        $out.='<option value="'.$k.'"';
        if ($k == $f['value']) $out.=' SELECTED';
        $out.='>';
        $out.=lang($text);
        $out.='</option>
        ';
    }
    $out.='</select>';

    if (!$ynswitch_jscode) {
        $out.="<script type=\"text/javascript\">
        $(function() {
            var ynswitches = $('html').find(\"[data-elem-name='yesnoswitch']\");
            ynswitches.switchy();
            ynswitches.on('change', function(){
                var firstOption = $(this).children('option').first().val();
                var lastOption = $(this).children('option').last().val();
                var bgColor = '#bababa';
                if ($(this).val() == firstOption){
                    bgColor = '#DC143C';
                } else if ($(this).val() == lastOption){
                    bgColor = '#008000';
                }
                $(this).next().next().children().first().css(\"background-color\", bgColor);
            });
            ynswitches.trigger('change');
        });
        </script>";
        $ynswitch_jscode=true;
     }
    return $out;
}

function survey__render_date($f,$formfieldvarname='') {
    global $lang;
    if (!$formfieldvarname) $formfieldvarname=$f['submitvarname'];
    if (preg_match('/([^\[]+)\[([^\[\]]+)\]/', $formfieldvarname, $matches)) {
        $formfieldvarname=$matches[1]."__".$matches[2];
    }
    $out='';
    $out=formhelpers__pick_date($formfieldvarname,$f['value']);
    return $out;
}

?>
