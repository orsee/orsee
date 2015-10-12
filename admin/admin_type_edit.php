<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_admin_type";
include ("header.php");
if ($proceed) {

    $allow=check_allow('admin_type_edit','admin_type_show.php');

}

if ($proceed) {
    if (isset($_REQUEST['type_id']) && $_REQUEST['type_id']) $type_id=$_REQUEST['type_id']; else $type_id="";

    $rights=array();
    if ($type_id) $type=orsee_db_load_array("admin_types",$type_id,"type_id");
    else    $type=array();


    if (isset($_REQUEST['save']) && $_REQUEST['save']) {

        $continue=true;

        $type=$_REQUEST;

        if (!$type_id && !$type['type_name']) {
            message (lang('error_admintype_name_required'));
            $continue=false;
        }

        if (isset($type['right_list'])) {
            $trights=array();
            foreach ($type['right_list'] as $key=>$value) {
                if ($value) $trights[]=$key;
            }
            $type['rights']=implode(",",$trights);
        } else $type['rights']="";


        if ($continue) {
            if (!$type_id) {
                $pars=array(':type_name'=>$type['type_name'],
                            ':rights'=>$type['rights']);
                $query="INSERT INTO ".table('admin_types')."
                    SET type_name= :type_name,
                    rights= :rights";
                $done=or_query($query,$pars);
                $type_id=pdo_insert_id();
            } else {
                $done=orsee_db_save_array($type,"admin_types",$type_id,"type_id");
            }

            if ($done) {
                message (lang('changes_saved'));
                redirect ("admin/admin_type_edit.php?type_id=".$type_id);
                $proceed=false;
            } else {
                message(lang('database_error'));
            }
        }
    }
}


if ($proceed) {

    $rights=array();
    if (isset($type['rights']) && $type['rights']) {
        $trights=explode(",",$type['rights']);
        foreach ($trights as $right) $rights[$right]=true;
    }

    $errors=array(); $required=array();
    // perform precondition checks
    foreach ($system__admin_rights as $systemright) {
        $line=explode(":",$systemright);
        // if selected and preconditions exist ...
        if (isset($line[2]) && $line[2] && isset($rights[$line[0]]) && $rights[$line[0]]) {
            $preconds=explode(",",$line[2]);
            // check if preconditions are met!
            foreach ($preconds as $cond) {
                if (!isset($rights[$cond]) || !$rights[$cond]) {
                    message(lang('warning').' "'.
                        $line[0].'" '.lang('xxx_right_requires_right_xxx').' "'.$cond.'"!');
                    $errors[]=$line[0];
                    $required[]=$cond;
                }
            }
        }
    }

    echo '<center>';


    show_message();
    if (!isset($type['type_name'])) $type['type_name']="";
    // form
    echo '<FORM action="admin_type_edit.php" method="post">
        <INPUT type=hidden name="type_id" value="'.$type_id.'">
        ';

    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%;">
            <TR><TD align="center">
            '.lang('name').':';
    if ($type_id) echo $type['type_name'];
    else echo '<INPUT type=text name="type_name" size=20 maxlength=20 value="'.$type['type_name'].'">';
    echo '          </TD>';
    echo '</TR></TABLE>';

    echo '
        <TABLE class="or_listtable" style="width: 90%;"><thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
            <TD></TD>
            <TD>'.lang('authorization').'</TD>
            <TD>'.lang('description').'</TD>
            <TD>'.lang('precondition_rights').'</TD>
        </TR></thead><tbody>';

    $shade=true; $lastclass="";

    foreach ($system__admin_rights as $right) {
        $line=explode(":",$right);
        $tclass=str_replace(strstr($line[0],"_"),"",$line[0]);
        if (!isset($line[1])) $line[1]="";
        if (!isset($line[2])) $line[2]="";
        if ($tclass!=$lastclass) {
            echo '<TR><TD colspan=4>&nbsp;<BR>&nbsp;</TD></TR>';
            $lastclass=$tclass; $shade=true;
            }
        echo '  <TR';
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                else echo ' bgcolor="'.$color['list_shade2'].'"';
            echo '>
                <TD class="small" align=right>
                    <INPUT type="checkbox" name="right_list['.$line[0].']" value="'.$line[0].'"';
                    if (isset($rights[$line[0]]) && $rights[$line[0]]) echo ' CHECKED';
                    echo '>
                </TD>
                <TD class="small" align=left';
                $bgcolor="";
                if (in_array($line[0],$required))
                    $bgcolor=' bgcolor="'.$color['admin_type_required_by_error'].'"';
                if (in_array($line[0],$errors))
                    $bgcolor=' bgcolor="'.$color['admin_type_error_missing_required'].'"';
                echo $bgcolor;
                echo '>
                                        '.$line[0].'
                                </TD>
                <TD class="small">
                    '.$line[1].'
                </TD>
                <TD class="small"';
                                $bgcolor="";
                                if (in_array($line[0],$required))
                    $bgcolor=' bgcolor="'.$color['admin_type_error_missing_required'].'"';
                                if (in_array($line[0],$errors))
                    $bgcolor=' bgcolor="'.$color['admin_type_required_by_error'].'"';
                echo $bgcolor;
                                echo '>
                    '.$line[2].'
                </TD>
              </TR>';
        if ($shade) $shade=false; else $shade=true;
        }
    echo '  <TR>
            <TD colspan=4 align=center>
                <INPUT class="button" type=submit name="save" value="';
                if ($type_id) echo lang('change'); else echo lang('add');
                echo '">
            </TD>
        </TR>';

    echo '  </tbody></TABLE>
        </FORM><BR><BR>';

    if ($type_id) {
            if (check_allow('admin_type_delete')) {
                echo '  <table border=0 width=80%>
                        <tr>
                                <td align=right>
                                        '.button_link('admin_type_delete.php?type_id='.urlencode($type_id),
                                        lang('delete'),'trash-o').'
                                <td>
                        </tr>
                        </table>';
                }
            }

    echo ' <A href="admin_type_show.php">'.icon('back').' '.lang('back').'</A><BR><BR>
        </center><BR><BR>';

}
include ("footer.php");

?>
