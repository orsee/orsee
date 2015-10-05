<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_participant_profile_field";
include ("header.php");
if ($proceed) {
    $user_columns=participant__userdefined_columns();
    if (!isset($_REQUEST['mysql_column_name']) || !isset($user_columns[$_REQUEST['mysql_column_name']]))
        redirect('admin/options_participant_profile.php');
    else $field_name=$_REQUEST['mysql_column_name'];
}

if ($proceed) {
    if ($field_name=='email') redirect('admin/options_participant_profile.php');
}

if ($proceed) {
    $allow=check_allow('pform_config_field_delete','options_participant_profile.php');
}

if ($proceed) {
    $field=orsee_db_load_array("profile_fields",$field_name,"mysql_column_name");
    $allvalues=participantform__allvalues();
    if (!isset($field['mysql_column_name'])) {
        $field['mysql_column_name']=$field_name;
    } else {
        $prop=db_string_to_property_array($field['properties']); unset($field['properties']);
        foreach ($prop as $k=>$v) $field[$k]=$v;
    }
    foreach ($allvalues as $k=>$v) if (!isset($field[$k])) $field[$k]=$v;
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
        else $reallydelete=false;
}

if ($proceed) {

    if ($reallydelete) {
        // transaction?
        $pars=array(':mysql_column_name'=>$field_name);
        $query="DELETE FROM ".table('profile_fields')."
                WHERE mysql_column_name= :mysql_column_name";
        $result=or_query($query,$pars);

        $query="ALTER TABLE ".table('participants')."
                DROP COLUMN ".$field_name.",
                DROP INDEX ".$field_name."_index";
        $result=or_query($query);

        log__admin("profile_form_field_delete","mysql_column_name:".$field_name);
        message (lang('profile_form_field_deleted'));
        redirect ("admin/options_participant_profile.php");
    }
}


if ($proceed) {
    // form

    echo '  <CENTER>
            <FORM action="options_participant_profile_delete.php">
            <INPUT type="hidden" name="mysql_column_name" value="'.$field_name.'">
            <TABLE class="or_formtable">
                <TR><TD colspan="2">
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                '.lang('delete_participant_profile_field').' "'.$field_name.'"
                            </TD>
                    </TR></TABLE>
                </TD></TR>
                <TR>
                    <TD colspan=2>'.lang('really_delete_profile_form_field?').'<BR><BR>
                                <B>'.lang('delete_profile_form_field_note').'</B><BR><BR>';
    dump_array($field);
    echo '          </TD>
                </TR>
                <TR>
                <TD align=center>
                '.button_link('options_participant_profile_delete.php?mysql_column_name='.urlencode($field_name).'&reallydelete=true',
                                        lang('yes_delete'),'check-square biconred').'
                </TD>
            </TR>
            <TR>
                <TD align="right" colspan=2><BR><BR>
                '.button_link('options_participant_profile_edit.php?mysql_column_name='.urlencode($field_name),
                                        lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
            </TABLE>

            </FORM>
            </center>';

}
include ("footer.php");
?>