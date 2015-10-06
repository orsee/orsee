<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_participant_status";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['status_id'])) $status_id=$_REQUEST['status_id'];

    if (isset($status_id)) $allow=check_allow('participantstatus_edit','participant_status_main.php');
    else $allow=check_allow('participantstatus_add','participant_status_main.php');
}

if ($proceed) {
    if (isset($status_id) && $status_id==0) $not_unconfirmed=false; else $not_unconfirmed=true;

    // load languages
    $languages=get_languages();

    if (isset($status_id)) {
        $status=orsee_db_load_array("participant_statuses",$status_id,"status_id");
        if (!isset($status['status_id'])) redirect ('admin/participant_status_main.php');
        if ($proceed) {
            $pars=array(':status_id'=>$status_id);
            $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
            $status_name=orsee_query($query,$pars);
            $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
            $status_error=orsee_query($query,$pars);
        }
    } else {
        $status=array('is_default_active'=>'n','is_default_inactive'=>'n','access_to_profile'=>'n','eligible_for_experiments'=>'n');
        $status_name=array();
        $status_error=array();
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        if ($not_unconfirmed && $_REQUEST['is_default_active']=="y" && $_REQUEST['is_default_inactive']=="y") {
            message(lang('error_participant_status_cannot_be_default_for_both_active_and_inactive'));
            $_REQUEST['is_default_active']="n"; $_REQUEST['is_default_inactive']="n";
            $continue=false;
        }

        $status_name=$_REQUEST['status_name'];
        foreach ($languages as $language) {
            if (!$status_name[$language]) {
                    message (lang('missing_language').': "'.lang('name').'" - '.$language);
                    $continue=false;
            }
        }
        if ($not_unconfirmed) {
            $status_error=$_REQUEST['status_error'];
            foreach ($languages as $language) {
                if ($_REQUEST['access_to_profile']!='y' && !$status_error[$language]) {
                    message (lang('missing_language').': "'.lang('error_message_to_participant_when_access_is_denied').'" - '.$language);
                    $continue=false;
                }
            }
        }

        if ($continue) {
            $status_name_lang=array(); $status_error_lang=array();
            if (!isset($status_id)) {
                $new=true;
                $query="SELECT status_id+1 as new_status_id FROM ".table('participant_statuses')."
                        ORDER BY status_id DESC LIMIT 1";
                $line=orsee_query($query);
                if (isset($line['new_status_id'])) $status_id=$line['new_status_id'];
                else $status_id=1;
                $status_name_lang['content_type']="participant_status_name";
                $status_name_lang['content_name']=$status_id;
                $status_error_lang['content_type']="participant_status_error";
                $status_error_lang['content_name']=$status_id;
            } else {
                $new=false;
                $pars=array(':status_id'=>$status_id);
                $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
                $status_name_lang=orsee_query($query,$pars);
                if ($not_unconfirmed) {
                    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
                    $status_error_lang=orsee_query($query,$pars);
                }
            }

            foreach ($languages as $language) {
                $status_name_lang[$language]=$status_name[$language];
                if ($not_unconfirmed) $status_error_lang[$language]=$status_error[$language];
            }

            if ($new) {
                $status_name['lang_id']=lang__insert_to_lang($status_name_lang);
                $status_error['lang_id']=lang__insert_to_lang($status_error_lang);
            } else {
                $done=orsee_db_save_array($status_name_lang,"lang",$status_name_lang['lang_id'],"lang_id");
                if ($not_unconfirmed) $done=orsee_db_save_array($status_error_lang,"lang",$status_error_lang['lang_id'],"lang_id");
            }

            if ($not_unconfirmed) {
                $status=$_REQUEST;
                $status['status_id']=$status_id;
                $pars=array(':status_id'=>$status_id);
                if ($status['is_default_active']=="y") {
                    $query="UPDATE ".table('participant_statuses')."
                            SET is_default_active='n'
                            WHERE status_id!= :status_id";
                    $done=or_query($query,$pars);
                }
                if ($status['is_default_inactive']=="y") {
                    $query="UPDATE ".table('participant_statuses')."
                            SET is_default_inactive='n'
                            WHERE status_id!= :status_id";
                    $done=or_query($query,$pars);
                }
                $done=orsee_db_save_array($status,"participant_statuses",$status_id,"status_id");
            }
            message (lang('changes_saved'));
            log__admin("participant_status_edit","status_id:".$status['status_id']);
            redirect ("admin/participant_status_edit.php?status_id=".$status_id);
        } else {
                $status=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form

    echo '  <CENTER>';
    show_message();
    echo '
            <FORM action="participant_status_edit.php">';
    if (isset($status_id)) echo '<INPUT type=hidden name="status_id" value="'.$status_id.'">';

    echo '
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('edit_participant_status');
    if (isset($status_id)) echo ' '.$status_name[lang('lang')];
    echo '
                        </TD>
                </TR></TABLE>
            </TD></TR>';

        if (isset($status_id)) {
            echo '
                <TR>
                    <TD>
                        '.lang('id').':
                    </TD>
                    <TD>
                        '.$status_id.'
                    </TD>
                </TR>';
        }

        echo '
            <TR>
                <TD valign="top">
                    '.lang('name').':
                </TD>
                <TD>';

                    echo '<TABLE border=0>';
                    foreach ($languages as $language) {
                        if (!isset($status_name[$language])) $status_name[$language]='';
                        echo '  <TR><TD>'.$language.':</TD>
                                <TD><INPUT name="status_name['.$language.']" type=text size=40 maxlength=200 value="'.
                                stripslashes($status_name[$language]).'">
                                </TD>
                            </TR>';
                    }
                    echo '</TABLE>
                </TD>
            </TR>';

    if ($not_unconfirmed) {
        echo '
                <TR>
                <TD valign=top>
                    '.lang('is_default_for_participants_becoming_active').'
                </TD>
                    <TD>';
                    if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
                        if ($status['is_default_active']=="y") echo lang('yes');
                        else echo lang('no');
                    } else {
                        echo '
                            <INPUT type=radio name="is_default_active" value="y"';
                            if ($status['is_default_active']=="y") echo ' CHECKED';
                            echo '>'.lang('yes').'
                            &nbsp;&nbsp;
                            <INPUT type=radio name="is_default_active" value="n"';
                            if ($status['is_default_active']!="y") echo ' CHECKED';
                            echo '>'.lang('no');
                    }
        echo '      </TD>
                </TR>';

        echo '
                <TR>
                <TD valign=top>
                    '.lang('is_default_for_participants_becoming_inactive').'
                </TD>
                    <TD>';
                    if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
                        if ($status['is_default_inactive']=="y") echo lang('yes');
                        else echo lang('no');
                    } else {
                        echo '
                            <INPUT type=radio name="is_default_inactive" value="y"';
                            if ($status['is_default_inactive']=="y") echo ' CHECKED';
                            echo '>'.lang('yes').'
                            &nbsp;&nbsp;
                            <INPUT type=radio name="is_default_inactive" value="n"';
                            if ($status['is_default_inactive']!="y") echo ' CHECKED';
                            echo '>'.lang('no');
                    }
        echo '      </TD>
                </TR>';

        echo '
            <TR>
                <TD>
                    '.lang('access_to_profile').'
                </TD>
                <TD>
                    <INPUT type=radio name="access_to_profile" value="y"';
                        if ($status['access_to_profile']=="y") echo ' CHECKED';
                        echo '>'.lang('yes').'
                    &nbsp;&nbsp;
                    <INPUT type=radio name="access_to_profile" value="n"';
                         if ($status['access_to_profile']!="y") echo ' CHECKED';
                        echo '>'.lang('no').'
                </TD>
            </TR>
            <TR>
                <TD valign="top">
                    '.lang('error_message_to_participant_when_access_is_denied').'
                </TD>
                <TD>';
                    echo '<TABLE border=0>';
                    foreach ($languages as $language) {
                        if (!isset($status_error[$language])) $status_error[$language]='';
                        echo '  <TR><TD>'.$language.':</TD>
                                <TD><INPUT name="status_error['.$language.']" type=text size=40 maxlength=200 value="'.
                                stripslashes($status_error[$language]).'">
                                </TD>
                            </TR>';
                    }
                    echo '</TABLE>
                </TD></TR>';

            echo '
                <TR>
                <TD valign=top>
                    '.lang('eligible_for_experiments').'
                </TD>
                    <TD>
                    <INPUT type=radio name="eligible_for_experiments" value="y"';
                        if ($status['eligible_for_experiments']=="y") echo ' CHECKED';
                        echo '>'.lang('yes').'
                    &nbsp;&nbsp;
                    <INPUT type=radio name="eligible_for_experiments" value="n"';
                         if ($status['eligible_for_experiments']!="y") echo ' CHECKED';
                        echo '>'.lang('no').'   </TD>
                </TR>';
    }
        echo '
            <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT class="button" name="edit" type="submit" value="';
                    if (!isset($status_id)) echo lang('add'); else echo lang('change');
                    echo '">
                </TD>
            </TR>


        </table>
        </FORM>
        <BR>';

    if (isset($status_id) && check_allow('participantstatus_delete') && $not_unconfirmed) {

            echo '<table>
                <TR>
                    <TD>
                        '.button_link('participant_status_delete.php?status_id='.urlencode($status_id),
                            lang('delete'),'trash-o').'
                    <TD>
                </TR>
                </table>';

    }

        echo '<BR><BR>
                <A href="participant_status_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>