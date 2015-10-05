<?php
// part of orsee. see orsee.org

function payments__paytype_selectfield($formfieldvarname,$selected,$exclude=array(),$only=array()) {
    $paytypes=payments__load_paytypes();
    $first=true; if (count($only)>0) $restrict=true; else $restrict=false;
    $out='';
    $out.='<SELECT name="'.$formfieldvarname.'">';
    foreach ($paytypes as $k=>$v) {
        if (($restrict && in_array($k,$only)) || ((!$restrict) && (!in_array($k,$exclude)))) {
            $out.='<OPTION value="'.$k.'"';
            if (!$selected && $first) {
                $out.=' SELECTED';
                $first=false;
            } elseif ($selected==$k) {
                $out.=' SELECTED';
            }
            $out.='>'.$v.'</OPTION>';
        }
    }
    $out.='</SELECT>';
    return $out;
}

function payments__budget_selectfield($formfieldvarname,$selected,$exclude=array(),$only=array()) {
    $budgets=payments__load_budgets();
    $first=true; if (count($only)>0) $restrict=true; else $restrict=false;
    $out='';
    $out.='<SELECT name="'.$formfieldvarname.'">';
    foreach ($budgets as $k=>$v) {
        if (($restrict && in_array($k,$only)) || ((!$restrict) && (!in_array($k,$exclude)))) {
            $out.='<OPTION value="'.$k.'"';
            if (!$selected && $first) {
                $out.=' SELECTED';
                $first=false;
            } elseif ($selected==$k) {
                $out.=' SELECTED';
            }
            $out.='>'.$v['budget_name'].'</OPTION>';
        }
    }
    $out.='</SELECT>';
    return $out;
}

function payments__budget_multiselectfield($postvarname,$selected,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected class ids
    $out="";
    if (!is_array($mpoptions)) $mpoptions=array();
    $default_options=array('tag_color'=>'#FF6600','picker_icon'=>'credit-card',
                    'picker_color'=>'#FF6600','picker_maxnumcols'=>1);
    foreach ($default_options as $k=>$v) if (!isset($mpoptions[$k])) $mpoptions[$k]=$v;
    $budgets=payments__load_budgets(true);
    $mylist=array();
    foreach ($budgets as $k=>$v) {
        if ($v['enabled'] || in_array($k,$selected)) $mylist[$k]=$v['budget_name'];
    }
    $out.= get_multi_picker($postvarname,$mylist,$selected,$mpoptions);
    return $out;
}

function payments__paytype_multiselectfield($postvarname,$selected,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected class ids
    if (!is_array($mpoptions)) $mpoptions=array();
    $default_options=array('tag_color'=>'#33CC33','picker_icon'=>'money',
                    'picker_color'=>'#33CC33','picker_maxnumcols'=>1);
    foreach ($default_options as $k=>$v) if (!isset($mpoptions[$k])) $mpoptions[$k]=$v;
    $mylist=payments__load_paytypes();
    $out="";
    $out.= get_multi_picker($postvarname,$mylist,$selected,$mpoptions);
    return $out;
}

function payments__load_paytypes() {
    global $preloaded_payment_types;
    if (isset($preloaded_payment_types) && is_array($preloaded_payment_types)) {
        return $preloaded_payment_types;
    } else {
        $paytypes=array();
        $query="SELECT * FROM ".table('lang')."
                WHERE content_type='payments_type'
                ORDER BY order_number";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $paytypes[$line['content_name']]=$line[lang('lang')];
        }
        $preloaded_payment_types=$paytypes;
        return $paytypes;
    }
}

function payments__load_budgets($include_notenabled=false) {
    global $preloaded_payment_budgets;
    if (isset($preloaded_payment_budgets) && is_array($preloaded_payment_budgets) && !$include_notenabled) {
        return $preloaded_payment_budgets;
    } else {
        $budgets=array();
        $query="SELECT * FROM ".table('budgets');
        if (!$include_notenabled) $query.=" WHERE enabled = 1 ";
        $query.=" ORDER BY budget_name";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $budgets[$line['budget_id']]=$line;
        }
        $preloaded_payment_budgets=$budgets;
        return $budgets;
    }
}

function payments__get_default_paytype($experiment=array(),$session=array()) {
    $continue=true;
    if ($continue) {
        if (is_array($session) && isset($session['payment_types'])) {
            $paytypes=db_string_to_id_array($session['payment_types']);
            if (count($paytypes)>0) {
                $continue=false;
                return $paytypes[0];
            }
        }
    }
    if ($continue) {
        if (is_array($experiment) && isset($experiment['payment_types'])) {
            $paytypes=db_string_to_id_array($experiment['payment_types']);
            if (count($paytypes)>0) {
                $continue=false;
                return $paytypes[0];
            }
        }
    }
    if ($continue) {
        $paytypes=payments__load_paytypes();
        ksort($paytypes); $first=true;
        foreach ($paytypes as $k=>$paytype) {
            if ($first) {
                return $k;
                $first=false;
            }
        }
    }
}

function payments__get_default_budget($experiment=array(),$session=array()) {
    $continue=true;
    if ($continue) {
        if (is_array($session) && isset($session['payment_budgets'])) {
            $budgets=db_string_to_id_array($session['payment_budgets']);
            if (count($budgets)>0) {
                $continue=false;
                return $budgets[0];
            }
        }
    }
    if ($continue) {
        if (is_array($experiment) && isset($experiment['payment_budgets'])) {
            $budgets=db_string_to_id_array($experiment['payment_budgets']);
            if (count($budgets)>0) {
                $continue=false;
                return $budgets[0];
            }
        }
    }
    if ($continue) {
        $budgets=payments__load_budgets(true);
        ksort($budgets); $first=true;
        foreach ($budgets as $k=>$budget) {
            if ($first) {
                return $k;
                $first=false;
            }
        }
    }
    if ($continue) {
        $query="SELECT * FROM ".table('budgets')."
                ORDER BY budget_id
                LIMIT 1";
        $result=or_query($query);
        $line = pdo_fetch_assoc($result);
        return $line['budget_id'];
    }
}

?>
