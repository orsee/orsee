<?php
// part of orsee. see orsee.org

function subpools__select_field($postvarname,$selected,$hidden=array(),$class='') {

    $subpools=subpools__get_subpools();
    $out='<SELECT name="'.$postvarname.'"';
    if ($class) $out.=' class="'.$class.'"';
    $out.='>';
    foreach ($subpools as $pool) {
        if (!in_array($pool['subpool_id'],$hidden)) {
            $out.='<OPTION value="'.$pool['subpool_id'].'"';
            if ($pool['subpool_id']==$selected) $out.=" SELECTED";
            $out.='>'.$pool['subpool_name'];
            $out.='</OPTION>';
            }
        }
    $out.='</SELECT>';
    return $out;
}

function subpools__multi_select_field($postvarname,$selected,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected experimenter usernames
    global $lang, $settings;

    $out="";
    $subpools=subpools__get_subpools();

    $mylist=array();
    foreach($subpools as $pool) {
        $mylist[$pool['subpool_id']]=$pool['subpool_name'];
    }

    if (!is_array($mpoptions)) $mpoptions=array();
    if (!isset($mpoptions['picker_icon'])) $mpoptions['picker_icon']='globe';
    $out.= get_multi_picker($postvarname,$mylist,$selected,$mpoptions);
    return $out;
}


function subpools__get_subpools() {
    global $preloaded_subpools;
    if (is_array($preloaded_subpools) && count($preloaded_subpools)>0) {
        return $preloaded_subpools;
    } else {
        $subpools=array();
        $query="SELECT *
                FROM ".table('subpools')."
                ORDER BY subpool_id";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $subpools[$line['subpool_id']]=$line;
        }
        $preloaded_subpools = $subpools;
        return $subpools;
    }
}

function subpools__idlist_to_namelist($idlist) {
    $names=subpools__get_subpools();
    $ids=explode(",",$idlist);
    $namearr=array();
    foreach ($ids as $id) {
        if (isset($names[$id])) $namearr[]=$names[$id]['subpool_name'];
        else $namearr[]=$id;
    }
    return implode(", ",$namearr);
}

function subpools__get_subpool($subpool_id) {
    $subpools=subpools__get_subpools();
    return $subpools[$subpool_id];
}


function subpools__get_subpool_name($subpool_id) {
    $subpools=subpools__get_subpools();
    return $subpools['subpool_id']['subpool_name'];
}


?>
