<?php
// part of orsee. see orsee.org
ob_start();
$title="";
if (isset($_REQUEST['otype']) && $_REQUEST['otype']) {
    if ($_REQUEST['otype']=="general") $title='edit_general_settings';
    elseif ($_REQUEST['otype']=="default") $title='edit_default_values';
}

$jquery=array('switchy','datepicker');
$menu__area="options_main";
include ("header.php");
if ($proceed) {
    $allow=check_allow('settings_view','options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['otype']) && $_REQUEST['otype'] && in_array($_REQUEST['otype'],array('general','default'))) {
        $otype=$_REQUEST['otype'];
    } else {
        $otype="";
        redirect ("admin/options_main.php");
    }

    if ($otype=='general') $opts=$system__options_general;
    else $opts=$system__options_defaults;

    echo '<center>';

    $pars=array(':type'=>$otype);
    $query="select * from ".table('options')."
            where option_type= :type
            order by option_name";
    $result=or_query($query,$pars);
    $options=array();
    while ($line=pdo_fetch_assoc($result)) {
        $options[$line['option_name']]=$line['option_value'];
    }

    if (check_allow('settings_edit') && isset($_REQUEST['change']) && $_REQUEST['change']) {
        $newoptions=$_REQUEST['options']; $now=time();
        
        // add and process option values which may be differently submitted
        foreach ($opts as $o) {
            if($o['type']=='date') {
                $newoptions[$o['option_name']]=ortime__array_to_sesstime($_REQUEST,'options__'.$o['option_name'].'_');
            }
        }
        
        $pars_new=array(); $pars_update=array();
        foreach ($newoptions as $oname => $ovalue) {
            if (isset($options[$oname])) {
                $pars_update[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':type'=>$otype);
            } else {
                $pars_new[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':type'=>$otype,
                                    ':now'=>$now);
                $now++;
            }
        }
        if (count($pars_update)>0) {
            $query="UPDATE ".table('options')."
                    SET option_value= :value
                    WHERE option_name= :name
                    AND option_type= :type";
            $done=or_query($query,$pars_update);
        }
        if (count($pars_new)>0) {
            $query="INSERT INTO ".table('options')." SET
                option_id= :now,
                option_name= :name,
                option_value= :value,
                option_type= :type";
            $done=or_query($query,$pars_new);
        }
        message(lang('changes_saved'));
        log__admin("options_edit","type:".$otype);
        redirect ('admin/options_edit.php?otype='.$otype);
    }
}

if ($proceed) {
    if (check_allow('settings_edit')) echo '
        <FORM action="options_edit.php" method=post>
        <INPUT type=hidden name="otype" value="'.$otype.'">';

    echo '  <TABLE class="or_formtable" style="width: 80%;">';
    if (check_allow('settings_edit')) echo '
            <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="change" value="'.lang('change').'">
                </TD>
            </TR>
            <TR><TD colspan=2><hr></TD></TR>';

    foreach ($opts as $o) {
        $done=options__show_option($o);
    }



    if (check_allow('settings_edit')) echo '
            <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="change" value="'.lang('change').'">
                </TD>
            </TR>';
    echo '</TABLE>';
    if (check_allow('settings_edit')) echo '</FORM>';

    echo '</center>';

    if (!check_allow('settings_edit')) echo '
            <script type="text/javascript">
                $(":input").attr("disabled", true);
            </script>';

}
include ("footer.php");
?>