<?php
// part of orsee. see orsee.org
ob_start();

$title="participant_profile_fields";
$menu__area="options_main";
include ("header.php");

if ($proceed) {
    $allow=check_allow('pform_config_field_configure','options_main.php');
}

if ($proceed) {

    $user_columns=participant__userdefined_columns();
    foreach ($user_columns as $k=>$arr) {
        $user_columns[$k]['has_index']=0;
        $user_columns[$k]['is_configured']=0;
    }
    $query="SHOW INDEX FROM ".table('participants');
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        if (isset($user_columns[$line['Column_name']])) {
            $user_columns[$line['Column_name']]['has_index']=1;
        }
    }
    $query="SELECT * FROM ".table('profile_fields');
    $result=or_query($query); $redundant=array();
    while ($line=pdo_fetch_assoc($result)) {
        if (isset($user_columns[$line['mysql_column_name']])) {
            $user_columns[$line['mysql_column_name']]['is_configured']=1;
            $user_columns[$line['mysql_column_name']]['enabled']=$line['enabled'];
            $user_columns[$line['mysql_column_name']]['type']=$line['type'];
            $user_columns[$line['mysql_column_name']]['properties']=db_string_to_property_array($line['properties']);
        } else {
            $redundant[]=$line['mysql_column_name'];
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['delete_redundant']) && $_REQUEST['delete_redundant']) {
        $pars=array(); foreach ($redundant as $r) $pars[]=array(':mysql_column_name'=>$r);
        $query="DELETE FROM ".table('profile_fields')." WHERE mysql_column_name = :mysql_column_name";
        $done=or_query($query,$pars);
        message(lang('redundant_configurations_deleted'));
        redirect('admin/'.thisdoc());
    }
}


if ($proceed) {
    echo '<center>';

    if (count($redundant)>0) {
        $m=lang('pfields_redundant_configurations_message').'<BR><B>'.implode(", ",$redundant).'</B>';
        $m.='<BR><FORM action="'.thisdoc().'" method="POST">
                <INPUT class="button" type="submit" name="delete_redundant" value="'.lang('yes').'">
                </FORM>
            </p>';
        show_message($m); echo '<BR><BR>';
    }

    echo '<TABLE class="or_listtable" style="width: 80%;"><thead>';
    echo '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
        echo '<TD>'.lang('mysql_column_name').'</TD>';
        echo '<TD>'.lang('mysql_column_type').'</TD>';
        echo '<TD>'.lang('mysql_column_is_indexed').'</TD>';
        echo '<TD>'.lang('orsee_is_configured').'</TD>';
        echo '<TD>'.lang('enabled?').'</TD>';
        echo '<TD>'.lang('profile_field_type').'</TD>';
        echo '<TD></TD>';
        echo '</TR></thead>
            <tbody>';

    $shade=false;
    foreach ($user_columns as $k=>$uc) {
        echo '<tr';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>';
        echo '<TD>'.$k.'</TD>';
        echo '<TD>'.$uc['Type'].'</TD>';
        echo '<TD>'.($uc['has_index']?lang('yes'):lang('no')).'</TD>';
        if ($uc['is_configured']) {
            echo '<TD>'.lang('yes').'</TD>';
            echo '<TD>'.($uc['enabled']?lang('yes'):lang('no')).'</TD>';
            echo '<TD>'.$uc['type'].'</TD>';
            echo '<TD><A HREF="options_participant_profile_edit.php?mysql_column_name='.$k.'">'.lang('edit').'</A></TD>';
        } else {
            echo '<TD>'.lang('no').'</TD>';
            echo '<TD>-</TD>';
            echo '<TD>-</TD>';
            echo '<TD><A HREF="options_participant_profile_edit.php?mysql_column_name='.$k.'">'.lang('configure').'</A></TD>';
        }
        echo '</TR>';
    }
    echo '</tbody></TABLE>';

    echo '<TABLE width="80%"><TR><TD>'.
            button_link('options_participant_profile_add.php',
                    lang('create_new_mysql_table_column'),'plus-circle').'
        </TD></TR></TABLE>';

    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>