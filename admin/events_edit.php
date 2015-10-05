<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiment_calendar";
$title="create_event";
$jquery=array('datepicker','clockpicker','arraypicker','textext');
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['event_id']) && $_REQUEST['event_id']) $event_id=$_REQUEST['event_id']; else $event_id="";
    $allow=check_allow('events_edit','calendar_main.php');
}


if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));

        $_REQUEST['event_start']=ortime__array_to_sesstime($_REQUEST,'event_start_');
        $_REQUEST['event_stop']=ortime__array_to_sesstime($_REQUEST,'event_stop_');

        $edit=$_REQUEST;
        $continue=true;

        if ($edit['event_start']>=$edit['event_stop']) {
            message(lang('start_time_must_be_earlier_than_stop_time'));
            $continue=false;
        }


        if ($continue) {
            $done=orsee_db_save_array($edit,"events",$edit['event_id'],"event_id");
            if ($done) {
                log__admin("events_edit","event_id:".$event_id);
                message (lang('changes_saved'));
                redirect ('admin/events_edit.php?event_id='.$edit['event_id']);
            } else {
                lang('database_error');
                redirect ('admin/events_edit.php?event_id='.$edit['event_id']);
            }
        }
    }
}

if ($proceed) {
    if ($event_id) {
        $edit=orsee_db_load_array("events",$event_id,"event_id");
        if (!isset($edit['event_id'])) redirect('admin/calendar_main.php');
    }
}


if ($proceed) {
// form

    if (isset($_REQUEST['copy']) && $_REQUEST['copy']) $event_id="";

    if (!$event_id) {
        $addit=true;
        $button_name=lang('add');

        if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
            $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));
            $_REQUEST['event_start']=ortime__array_to_sesstime($_REQUEST,'event_start_');
            $_REQUEST['event_stop']=ortime__array_to_sesstime($_REQUEST,'event_stop_');
            $edit=$_REQUEST;
            $edit['event_id']=time();
        } else {
            $edit['event_id']=time();

            $edit['event_start']=ortime__unixtime_to_sesstime();
            $edit['event_stop']=ortime__unixtime_to_sesstime(time()+60*60);

            $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
            $edit['laboratory_id']="";
            $edit['event_category']="";
            $edit['reason']="";
            $edit['reason_public']="";
            $edit['number_of_participants']="";
        }
    } else {
        session__check_lab_time_clash($edit);
        $button_name=lang('change');
    }

    echo '<center>';

    show_message();

    echo '<p width="70%">'.lang('for_session_time_reservation_please_use_experiments').'<BR>'.
            lang('this_reservation_type_is_for_maintenence_purposes').'<BR><BR></p>';


    echo '<FORM action="events_edit.php" method="POST">
        <INPUT type=hidden name=event_id value="'.$edit['event_id'].'">';
    if (isset($addit) && $addit) echo '<INPUT type=hidden name="addit" value="true">';
    echo '
        <TABLE class="or_formtable">
        <TR>
            <TD>'.lang('id').':</TD>
            <TD>'.$edit['event_id'].'</TD>
        </TR>';
    echo '  <TR>
            <TD>'.lang('laboratory').':</TD>
            <TD>';
    laboratories__select_field("laboratory_id",$edit['laboratory_id']);
    echo '  </TD>
            </TR>';

    echo '  <TR>
                <TD>
                        '.lang('event_category').':
                </TD>
                <TD>'.language__selectfield_item('events_category','','event_category',$edit['event_category'],false,'fixed_order').'
                </TD>
            </TR>';

    echo '  <TR>
            <TD>
                '.lang('start_date_and_time').':
            </TD>
            <TD>';


    echo formhelpers__pick_date('event_start',$edit['event_start'],$settings['session_start_years_backward'],$settings['session_start_years_forward']);
    echo '&nbsp;&nbsp;';
    echo formhelpers__pick_time('event_start', $edit['event_start']);

    echo'</TD>
        </TR>';
    echo '  <TR><TD>'.lang('stop_date_and_time').':</TD>
            <TD>';
    echo formhelpers__pick_date('event_stop',$edit['event_stop'],$settings['session_start_years_backward'],$settings['session_start_years_forward']);
    echo '&nbsp;&nbsp;';
    echo formhelpers__pick_time('event_stop', $edit['event_stop']);

    echo'   </TD>
        </TR>';
    echo '  <TR>
             <TD>'.lang('experimenter').':</TD>
             <TD>';
    if (!isset($_REQUEST['event_id']) || !$_REQUEST['event_id']) $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
    echo experiment__experimenters_select_field("experimenter",db_string_to_id_array($edit['experimenter']));
    echo '  </TD>
        </TR>';
    echo '  <TR>
            <TD>'.lang('description').':</TD>
            <TD><INPUT type="text" name="reason" size=40 maxlength=200 value="'.$edit['reason'].'"></TD>
        </TR>';
    echo '  <TR>
            <TD>'.lang('labspace_public_description').':<BR>
                <FONT class="small">'.lang('labspace_public_description_note').'</FONT></TD>
            <TD><INPUT type="text" name="reason_public" size=40 maxlength=200 value="'.$edit['reason_public'].'"></TD>
            </TR>';
    if ($settings['enable_event_participant_numbers']=='y') {
        echo '  <TR>
                <TD>'.lang('number_of_participants').':</TD>
                <TD><INPUT type="text" name="number_of_participants" size=5 maxlength=5 value="'.$edit['number_of_participants'].'"></TD>
                </TR>';
    }
    echo '<TR>
                <TD COLSPAN="2" align="center"><INPUT class="button" name="edit" type="submit" value="'.$button_name.'"></TD>
        </TR>';

    if ($event_id) {

        echo '
            <TR>
                <TD COLSPAN=2 align="right">
                    <INPUT class="button" name="copy" type="submit" value="'.lang('copy_as_new_event').'">
                </TD>
            </TR>';
    }

    echo '  </table>
    </FORM>
    <BR>';

    if ($event_id && check_allow('events_delete')) {
        echo '
            <table>
                <TR>
                    <TD>
                        '.button_link('events_delete.php?event_id='.$edit['event_id'],lang('delete'),'trash-o').'
                    </TD>
                </TR>
            </table>
            ';
    }

    echo '<BR><BR><A href="calendar_main.php">'.icon('back').' '.lang('back').'</A>';


}
include ("footer.php");
?>