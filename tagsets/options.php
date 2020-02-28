<?php
// part of orsee. see orsee.org

function load_settings() {
    global $system__options_general, $system__options_defaults;

    $query="SELECT * FROM ".table('options')."
    WHERE option_type='general' OR option_type='default'";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $settings[$line['option_name']]=stripslashes($line['option_value']);
    }

    foreach ($system__options_general as $option) {
        if (isset($option['type']) && ($option['type']=='line' || $option['type']=='comment')) {
        } else {
            if (!isset($settings[$option['option_name']])) {
                $settings[$option['option_name']]=$option['default_value'];
            }
        }
    }
    foreach ($system__options_defaults as $option) {
        if (isset($option['type']) && ($option['type']=='line' || $option['type']=='comment')) {
        } else {
            if (!isset($settings[$option['option_name']])) {
                $settings[$option['option_name']]=$option['default_value'];
            }
        }
    }
    return $settings;
}

function load_colors() {
    global $settings, $system__colors;
    $color=array();

    $pars=array(':style'=>$settings['style']);
    $query="select * from ".table('options')."
            where option_type='color'
            and option_style= :style
            order by option_name";
    $result=or_query($query,$pars);
    while ($line=pdo_fetch_assoc($result)) $color[$line['option_name']]=$line['option_value'];

    // what if this does not exist? Then load default orsee colors
    if (count($color)==0) {
        $query="select * from ".table('options')."
                where option_type='color'
                and option_style= 'orsee'
                order by option_name";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) $color[$line['option_name']]=$line['option_value'];
    }

    foreach ($system__colors as $c) {
        if (isset($c['type']) && ($c['type']=='line' || $c['type']=='comment')) {
        } else {
            if (!isset($color[$c['color_name']])) {
                $color[$c['color_name']]=$c['default_value'];
            }
        }
    }

    return $color;
}



function or_setting($o,$v="") {
    global $settings;
    $ret=false;
    if ($v && $settings[$o]==$v) $ret=true;
    elseif ($settings[$o]=='y') $ret=true;
    return $ret;
}

function options__show_option($o) {
    global $options;
    if($o['type']=='plain') {
        $o=options__replace_funcs_in_field($o);
        $done=option__display_option($o['option_text'],$o['field']);
    } elseif(isset($o['type']) && $o['type']=='line') {
        options__line();
    } elseif(isset($o['type']) && $o['type']=='comment') {
        $done=option__display_option('<B>'.$o['text'].'</B>','',true);
    } else {
        $o=options__replace_funcs_in_field($o);
        if (isset($options[$o['option_name']]) && ($options[$o['option_name']] || $options[$o['option_name']]=='0')) $o['value']=$options[$o['option_name']];
            else $o['value']=$o['default_value'];
        $o['submitvarname']='options['.$o['option_name'].']';
        $field=survey__render_field($o);
        $done=option__display_option($o['option_text'],$field);
    }
}

function options__get_styles() {
    $styles=get_style_array();
    return implode(",",$styles);
}
function options__replace_funcs_in_field($f) {
    global $lang, $settings, $options;
    foreach ($f as $o=>$v) {
        if (substr($f[$o],0,5)=='func:') eval('$f[$o]='.substr($f[$o],5).';');
    }
    return $f;
}


function options__show_color_option($o) {
    global $mycolors;
    if(isset($o['type']) && $o['type']=='line') {
        options__line();
    } elseif(isset($o['type']) && $o['type']=='comment') {
        $done=option__display_option('<B>'.$o['text'].'</B>','',true);
    } elseif (isset($o['color_name']) && isset($o['default_value'])) {
        if (isset($mycolors[$o['color_name']]) && ($mycolors[$o['color_name']] || $mycolors[$o['color_name']]=='0')) $o['value']=$mycolors[$o['color_name']];
            else $o['value']=$o['default_value'];
        $o['submitvarname']='mycolors['.$o['color_name'].']';
        if (isset($o['options']['size'])) $size=$o['options']['size'];
        else $size=10;
        if (isset($o['options']['maxlength'])) $maxlength=$o['options']['maxlength'];
        else $maxlength=10;
        if (isset($o['options']['nopicker']) && $o['options']['nopicker']) $picker="";
        else $picker=' class="colorpickerinput" style="border-color: '.$o['value'].';" ';
        $field='<INPUT type="text" '.$picker.' name="'.$o['submitvarname'].'" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$o['value'].'">';

        $done=option__display_option($o['color_name'],$field);
    }
}


function option__display_option($text,$field,$colspan=false) {
    if ($colspan) {
    echo '<TR>
              <TD colspan="2">'.$text.'</TD>
           </TR>';
    } else {
    echo '<TR>
              <TD>'.$text.'</TD>
              <TD>'.$field.'</TD>
           </TR>';
    }
}

function options__line() {
    echo '  <TR><TD colspan=2><hr></TD></TR>';
}

function options__get_color_styles() {
    global $preloaded_color_styles;
    if (isset($preloaded_color_styles) && is_array($preloaded_color_styles)
        && count($preloaded_color_styles)>0)
            return $preloaded_color_styles;
    else {
        $color_styles=array();
        $query="select option_style from ".table('options')."
                where option_type='color'
                group by option_style
                order by option_style";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) $color_styles[]=$line['option_style'];
        $preloaded_color_styles=$color_styles;
        return $color_styles;
    }
}

function options__save_item_order($item_type,$order_array,$details=array()) {
    $pars=array(':item_type'=>$item_type);
    $query="DELETE FROM ".table('objects')."
            WHERE item_type= :item_type";
    $done=or_query($query,$pars);

    $pars=array();
    foreach ($order_array as $k=>$v) {
        if (isset($details[$v]) && is_array($details[$v])) $detstr=property_array_to_db_string($details[$v]);
        else $detstr='';
        $pars[]=array(':item_type'=>$item_type,
                    ':item_name'=>$v,
                    ':order_number'=>$k,
                    ':item_details'=>$detstr);
    }
    $query="INSERT INTO ".table('objects')."
            SET order_number = :order_number,
            item_type = :item_type,
            item_name = :item_name,
            item_details = :item_details";
    $done=or_query($query,$pars);
    return $done;
}

function options__ordered_lists_get_current($poss_cols,$saved_cols,$extra_fields=array()) {
    // filter out non-draggable at begin and end
    $first_draggable=false;
    $first=array(); $num_first=0; $last=array(); $num_last=0;
    $draggable=array();
    foreach ($poss_cols as $k=>$arr) {
        if (isset($arr['allow_drag']) && $arr['allow_drag']==false) {
            if ($first_draggable) {
                $num_last--;
                $arr['fixed_position']=$num_last;
                if (isset($arr['allow_remove']) && $arr['allow_remove']==false) $arr['on_list']=true;
                else $arr['on_list']=false;
                $last[$k]=$arr;
                unset($poss_cols[$k]);
            } else {
                $num_first++;
                $arr['fixed_position']=$num_first;
                if (isset($arr['allow_remove']) && $arr['allow_remove']==false) $arr['on_list']=true;
                else $arr['on_list']=false;
                $first[$k]=$arr;
                unset($poss_cols[$k]);
            }
        } else {
            $draggable[$k]=$arr;
            $first_draggable=true;
        }
    }
    // get the saved columns and put them on list
    $draggable_num=0;   $onlist_draggable=array();
    foreach($saved_cols as $k=>$line) {
        if(isset($first[$k])) $first[$k]['on_list']=true;
        elseif(isset($last[$k])) $last[$k]['on_list']=true;
        elseif(isset($draggable[$k])) {
            $draggable_num++;
            $onlist_draggable[$k]=$draggable[$k];
            $onlist_draggable[$k]['fixed_position']=$num_first+$draggable_num;
            $onlist_draggable[$k]['on_list']=true;
            $onlist_draggable[$k]['item_details']=db_string_to_property_array($line['item_details']);
            unset($draggable[$k]);
        }
    }
    foreach ($draggable as $k=>$arr) {
        if (isset($arr['allow_remove']) && $arr['allow_remove']==false) {
            $draggable_num++;
            $onlist_draggable[$k]=$draggable[$k];
            $onlist_draggable[$k]['fixed_position']=$num_first+$draggable_num;
            $onlist_draggable[$k]['on_list']=true;
            unset($draggable[$k]);
        }
    }
    // now put eveyrhting together
    $listrows=array();
    foreach ($first as $k=>$arr) $listrows[$k]=$arr;
    foreach ($onlist_draggable as $k=>$arr) $listrows[$k]=$arr;
    foreach ($draggable as $k=>$arr) {
        $arr['fixed_position']=0;
        $arr['on_list']=false;
        $listrows[$k]=$arr;
    }
    foreach ($last as $k=>$arr) $listrows[$k]=$arr;
    // and now just make sure all fields exist
    foreach ($listrows as $k=>$arr) {
        if (!isset($arr['display_text'])) $arr['display_text']=$k;
        if (!isset($arr['on_list'])) $arr['on_list']=false;
        if (!isset($arr['allow_remove'])) $arr['allow_remove']=true;
        if (!isset($arr['allow_drag'])) $arr['allow_drag']=true;
        if (!isset($arr['fixed_position'])) $arr['fixed_position']=0;
        if (!isset($arr['sortable'])) $arr['sortable']=true;
        if (!isset($arr['cols'])) $arr['cols']='<TD>'.$arr['display_text'].'</TD>';
        foreach ($extra_fields as $extra_field=>$display_name) {
            if ($extra_field=='sortby_radio') {
                if ($arr['sortable']) {
                    $arr['cols'].='<TD align="center"><INPUT type="radio" name="sortby" value="'.$k.'"';
                    if (isset($arr['item_details']['default_sortby']) && $arr['item_details']['default_sortby']) {
                        $arr['cols'].=' CHECKED';
                    }
                    $arr['cols'].='></TD>';
                } else {
                    $arr['cols'].='<TD></TD>';
                }
            } elseif ($extra_field=='field_value') {
                if (!isset($arr['item_details'])) {
                    $arr['item_details']=array();
                }
                if (!isset($arr['item_details']['field_value'])) {
                    $arr['item_details']['field_value']='';
                }
                $arr['cols'].='<TD><INPUT type="text" size="30" maxlength="255" name="field_values['.$k.']" value="'.$arr['item_details']['field_value'].'"></TD>';
            } elseif ($extra_field=='hide_for_admin_types') {
                if (!(isset($arr['disallow_hide']) && $arr['disallow_hide'])) {
                    if (!isset($arr['item_details'])) {
                        $arr['item_details']=array();
                    }
                    if (!isset($arr['item_details']['hide_admin_types'])) {
                        $arr['item_details']['hide_admin_types']='';
                    }
                    $arr['cols'].='<TD><INPUT type="text" size="30" maxlength="255" name="hide_admin_types['.$k.']" value="'.$arr['item_details']['hide_admin_types'].'"></TD>';
                } else {
                    $arr['cols'].='<TD></TD>';
                }
            }
        }
        $listrows[$k]=$arr;
    }
    return $listrows;
}


function pform_options_yesnoradio($varname,$field) {
    global $restrict_to;
    $out='';
    if (in_array($varname,$restrict_to)) {
        $out.=lang('y').'<INPUT TYPE="radio" NAME="'.$varname.'" VALUE="y"';
        if ($field[$varname]=='y') $out.=' CHECKED';
        $out.='>&nbsp;'.lang('n').'<INPUT TYPE="radio" NAME="'.$varname.'" VALUE="n"';
        if ($field[$varname]!='y') $out.=' CHECKED';
        $out.='>';
    } else {
        $out.=($field[$varname]=='y')?lang('y'):lang('n');
    }
    return $out;
}

function pform_options_inputtext($varname,$field,$size=25) {
    global $restrict_to;
    $out='';
    if (in_array($varname,$restrict_to)) {
        $out='<INPUT type="text" name="'.$varname.'" size="'.$size.'" maxlength="200" value="'.htmlentities($field[$varname], ENT_QUOTES).'">';
     } else {
        $out=htmlentities($field[$varname], ENT_QUOTES);
     }
    return $out;
}

function pform_options_vallanglist($varname_val,$varname_lang,$field) {
    global $restrict_to;
    $i=0;
    $out='<TABLE><TR><TD>Option values</TD><TD>Language symbols</TD></TR>';

    if ($field[$varname_val]) {
        $vals=explode(",",$field[$varname_val]);
        $langs=explode(",",$field[$varname_lang]);
        foreach ($vals as $k=>$v) {
            $out.='<TR><TD>';
            if (in_array($varname_val,$restrict_to)) {
                $out.='<INPUT type="text" name="'.$varname_val.'['.$i.']" size="10" maxlength="200" value="'.trim($v).'">';
            } else {
                $out.=trim($v);
            }
            $out.='</TD><TD>';
            if (in_array($varname_lang,$restrict_to)) {
                $out.='<INPUT type="text" name="'.$varname_lang.'['.$i.']" size="25" maxlength="200" value="'.trim($langs[$k]).'">';
            } else {
                $out.=trim($langs[$k]);
            }
            $out.='</TD></TR>';
            $i++;
        }
    }
    if (in_array($varname_val,$restrict_to) && in_array($varname_lang,$restrict_to)) {
        for ($j=1; $j<=3; $j++) {
            $out.='<TR><TD><INPUT type="text" name="'.$varname_val.'['.$i.']" size="10" maxlength="200" value=""></TD>
                    <TD><INPUT type="text" name="'.$varname_lang.'['.$i.']" size="25" maxlength="200" value=""></TD></TR>';
            $i++;
        }
    }
    $out.='</TABLE>';
    return $out;
}

function options__load_object($item_type,$item_name) {
    $pars=array(':item_type'=>$item_type,
                ':item_name'=>$item_name);
    $query="select * from ".table('objects')."
            where item_type= :item_type
            and item_name= :item_name";
    $object=orsee_query($query,$pars);
    $object['item_details']=db_string_to_property_array($object['item_details']);
    return $object;
}

function options__show_main_section($sname,$optionlist) {
    global $color;
    if (is_array($optionlist) && count($optionlist)>0) {
        echo '<TR><TD><TABLE class="or_optionssection">';
        echo '<TR class="section_title"><TD>'.$sname.'</TD></TR>';
        foreach ($optionlist as $oitem) {
            echo '<TR><TD>'.$oitem.'</TD></TR>';
        }
        echo '</TABLE></TD></TR>';
        echo '<TR><TD>&nbsp;</TD></TR>';
    }
}

function pform_options_selectfield($name,$array,$field,$id="") {
    global $restrict_to;
    $out='';
    if (in_array($name,$restrict_to)) {
        $out='<SELECT name="'.$name.'"';
        if ($id) $out.=' id="'.$id.'"';
        $out.='>';
        foreach ($array as $v) {
            $out.='<OPTION value="'.$v.'"';
            if ($field[$name]==$v) $out.=' SELECTED';
            $out.='>'.$v.'</OPTION>';
        }
        $out.='</SELECT>';
    } else {
        $out=$field[$name];
    }
    return $out;
}



?>
