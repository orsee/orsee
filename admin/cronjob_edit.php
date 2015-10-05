<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_cronjob";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['job_name'])) $job_name=$_REQUEST['job_name']; else $job_name="";
    if ($job_name) $allow=check_allow('regular_tasks_edit','cronjob_main.php');
    else $allow=check_allow('regular_tasks_add','cronjob_main.php');
}

if ($proceed) {

    // load languages
    $languages=get_languages();

    if ($job_name) {
        $job=orsee_db_load_array("cron_jobs",$job_name,"job_name");
    } else {
        $job=array('job_name'=>'','enabled'=>'n','job_last_exec'=>0,'job_time'=>'');
    }

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!$_REQUEST['job_name']) {
            message (lang('name_for_cronjob_required'));
            $continue=false;
        }
        if ($continue) {
            $done=orsee_db_save_array($_REQUEST,"cron_jobs",$job_name,"job_name");
            log__admin("cronjob_edit",$_REQUEST['job_name']);
            message (lang('changes_saved'));
            redirect ("admin/cronjob_edit.php?job_name=".$job_name);
            $proceed=false;
        } else {
            $job=$_REQUEST;
        }
    }

}


if ($proceed) {

    // form
    echo '<CENTER>';

    show_message();

    echo '
            <FORM action="cronjob_edit.php">

        <TABLE class="or_formtable">
            <TR>
                <TD>
                    '.lang('name').':
                </TD>
                <TD>';
    if ($job_name) {
        echo '<INPUT type="hidden" name="job_name" value="'.$job['job_name'].'">';
        if (isset($lang['cron_job_'.$job['job_name']])) echo $lang['cron_job_'.$job['job_name']];
        else echo $job['job_name'];
    } else echo '<INPUT style="max-width: 50%" type=text name="job_name" size=30 maxlength=200 value="">';
    echo '
                </TD>
            </TR>
            <TR>
                                <TD>
                                        '.lang('enabled?').'
                                </TD>
                                <TD>
                                        <INPUT type=radio name="enabled" value="y"';
    if ($job['enabled']!="n") echo ' CHECKED';
    echo '>'.lang('yes').'
                                        &nbsp;&nbsp;
                                        <INPUT type=radio name="enabled" value="n"';
    if ($job['enabled']=="n") echo ' CHECKED';
    echo '>'.lang('no').'
                                </TD>
                        </TR>
            <TR>
                <TD>
                    '.lang('last_execution').':
                </TD>
                <TD>';
    if ($job['job_last_exec']==0) echo lang('never');
    else ortime__format($job['job_last_exec'],'hide_second:false',lang('lang'));
    echo '  </TD>
            </TR>
            <TR>
                <TD>
                    '.lang('when_executed?').':
                </TD>
                <TD>
                    ';
    cron__job_time_select_field($job['job_time']);
    echo '  </TD>
            </TR>

            <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT class="button" name="edit" type=submit value="';
    if (!$job_name) echo lang('add'); else echo lang('change');
    echo '">
                </TD>
            </TR>


        </table>
        </FORM>
        <BR>';

        echo '<BR><BR>
                <A href="cronjob_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';
}
include ("footer.php");
?>