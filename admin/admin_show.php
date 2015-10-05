<?php
// part of orsee. see orsee.org
ob_start();

$title="user_management";
$menu__area="options";
include ("header.php");
if ($proceed) {

    $allow=check_allow('admin_edit','options_main.php');
    if (check_allow('admin_edit')) {
        if (isset($_REQUEST['change']) && $_REQUEST['change']) {
            if (isset($_REQUEST['disabled']) && is_array($_REQUEST['disabled'])) {
                $pars=array();
                foreach ($_REQUEST['disabled'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET disabled= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
            if (isset($_REQUEST['experimenter_list']) && is_array($_REQUEST['experimenter_list'])) {
                $pars=array();
                foreach ($_REQUEST['experimenter_list'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET experimenter_list= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
            if (isset($_REQUEST['admin_type']) && is_array($_REQUEST['admin_type'])) {
                $pars=array();
                foreach ($_REQUEST['admin_type'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET admin_type= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
                log__admin("admin_show_edit");
                message(lang('changes_saved'));
                redirect("admin/admin_show.php");
                $proceed=false;
        }
    }
}

if ($proceed) {

    echo '<center><br>
        '.button_link('admin_edit.php?new=true',lang('create_new'),'plus-circle');

    echo '<br>

        <FORM action="'.thisdoc().'" method="POST">';


    echo '<table class="or_listtable"><thead>';

    if (check_allow('admin_edit')) {
        echo '<tr style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <td colspan="5"></td>
                <td><INPUT name="change" type="submit" class="button" value="'.lang('save_changes_in_list').'"></td>
                <td></td>
            </tr>';
    }

    echo '
            <tr style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <td>'.lang('firstname').'</td>
                <td>'.lang('lastname').'</td>
                <td>'.lang('username').'</td>
                <td>'.lang('type').'</td>
                <td>'.lang('is_experimenter').'</td>
                <td>'.lang('account').'</td>
                <td></td>
            </tr>
            </thead>
            <tbody>';

    $query="SELECT * FROM ".table('admin')."
            ORDER BY disabled, lname, fname";
    $result=or_query($query);

    $enabled_emails=array(); $emails=array(); $shade=false;
    while ($admin=pdo_fetch_assoc($result)) {

    if ($admin['email']) {
        $emails[]=$admin['email'];
        if ($admin['disabled']=='n') $enabled_emails[]=$admin['email'];
    }
    echo '<tr class="small"';
    if ($admin['disabled']=='y')  {
        echo ' bgcolor="#999999" style="color: #333333"';
    } else {
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
            else echo ' bgcolor="'.$color['list_shade2'].'"';
    }
        echo '>
                            <td>
                                    '.$admin['fname'].'
                            </td>
                            <td>
                                    '.$admin['lname'].'
                            </td>
                            <td>
                                    '.$admin['adminname'].'
                            </td>
                            <td>';
                                if (check_allow('admin_edit')) {
                                    echo admin__select_admin_type('admin_type['.$admin['admin_id'].']',$admin['admin_type']);
                                } else {
                                    echo $admin['admin_type'];
                                }
                    echo '  </td>
                            <td>
                            ';
                                    if (check_allow('admin_edit')) {
                                        echo '<input name="experimenter_list['.$admin['admin_id'].']" type=radio value="y"';
                                                if ($admin['experimenter_list']=='y') echo ' CHECKED';
                                        echo '>'.lang('yes').'&nbsp;&nbsp;
                                            <input name="experimenter_list['.$admin['admin_id'].']" type=radio value="n"';
                                                if ($admin['experimenter_list']!='y') echo ' CHECKED';
                                                echo '>'.lang('no');
                                    } else {
                                        if ($admin['experimenter_list']=='n') echo lang('n'); else echo lang('y');
                                    }
                            echo '</td>
                                <td>';
                                    if (check_allow('admin_edit')) {
                                        echo '<input name="disabled['.$admin['admin_id'].']" type=radio value="n"';
                                                if ($admin['disabled']!='y') echo ' CHECKED';
                                        echo '>'.lang('account_enabled').'&nbsp;&nbsp;
                                            <input name="disabled['.$admin['admin_id'].']" type=radio value="y"';
                                                if ($admin['disabled']=='y') echo ' CHECKED';
                                                echo '>'.lang('account_disabled');
                                    } else {
                                        if($admin['disabled']!='y') echo lang('account_enabled');
                                        else echo lang('account_disabled');
                                    }
                            echo '
                            </td>
                            <td>
                                    <a href="admin_edit.php?admin_id='.$admin['admin_id'].'">'.lang('edit').'</a>
                            </td>
                    </tr>';
                if ($shade) $shade=false; else $shade=true;
        }

    echo '</tbody>';
    if (check_allow('admin_edit')) {
        echo '<tfoot><tr>
                <td colspan=5></td>
                <td><INPUT name="change" type="submit" class="button" value="'.lang('save_changes_in_list').'"></td>
                <td></td>
            </tr></tfoot>';
    }

    echo '</table></FORM>

                <br><br>';

    echo '<A HREF="mailto:'.$settings['support_mail'].'?bcc='.implode(",",$enabled_emails).'">'.lang('write_message_to_all_enabled_admins').'</A>';
    echo '<BR><BR>';
    echo '<A HREF="mailto:'.$settings['support_mail'].'?bcc='.implode(",",$emails).'">'.lang('write_message_to_all_listed').'</A>';

    echo '<br><br>
        </center>';

}
include ("footer.php");
?>
