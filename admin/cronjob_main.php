<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="regular_tasks";
include ("header.php");
if ($proceed) {
    $allow=check_allow('regular_tasks_show','options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['exec']) && $_REQUEST['exec'] && isset($_REQUEST['job_name']) && $_REQUEST['job_name']) {
        $allow=check_allow('regular_tasks_run','cronjob_main.php');
        if ($proceed) {
            $cronjob=$_REQUEST['job_name'];
            $now=time();
            $function_name='cron__'.$cronjob;
            $done=$function_name();
            // save and log job
            $ready=cron__save_and_log_job($cronjob,$now,$done);
            log__admin("cronjob_run",$cronjob);
            message(lang('ran_cronjob_xxx').' '.$cronjob);
            redirect('admin/'.thisdoc());
        }
    }
}

if ($proceed) {
        echo '<center><BR>';

    if (check_allow('regular_tasks_add'))
        echo button_link('cronjob_edit.php?addit=true',lang('create_new'),'plus-circle').'<BR>';


        echo '<BR>
                <table class="or_listtable"><thead>
                    <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                        <TD></TD>
                        <TD>'.lang('enabled?').'</TD>
                        <TD>'.lang('when_executed?').'</TD>
                        <TD>'.lang('last_execution').'</TD>
                        <TD></TD>
                        <TD></TD>
                    </TR>
                </thead>
                <tbody>';

        $query="SELECT *
                FROM ".table('cron_jobs')."
                ORDER BY job_name";
        $result=or_query($query);

    $allow_run=check_allow('regular_tasks_run');
    $allow_edit=check_allow('regular_tasks_edit');

    $shade=true;

    while ($line=pdo_fetch_assoc($result)) {

        echo '  <tr';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        if ($shade) $shade=false; else $shade=true;
        if ($line['enabled']=='n') echo ' style="color: #888"';
        echo '>
            <td valign=top>';
        if (isset($lang['cron_job_'.$line['job_name']])) echo $lang['cron_job_'.$line['job_name']];
        else echo $line['job_name'];
        echo '  </td>
                <TD>';
        if ($line['enabled']=='y') echo '<FONT color="green">'.lang('yes').'</FONT>';
        else echo lang('no');
        echo '  </TD>
                <TD>';
        if (isset($lang['cron_job_time_'.$line['job_time']])) echo $lang['cron_job_time_'.$line['job_time']];
        else echo $line['job_time'];
        echo '  </TD>
                <TD>';
        if ($line['job_last_exec']==0) echo lang('never');
        else echo ortime__format($line['job_last_exec'],'hide_second:false',lang('lang'));
        echo '  </TD>
                <TD>';
        if ($allow_edit)
        echo '<A HREF="cronjob_edit.php?job_name='.$line['job_name'].'">'.lang('edit').'</A>';
        echo '  </TD>';
        echo '<TD>';
        if ($allow_run) echo button_link('cronjob_main.php?job_name='.$line['job_name'].'&exec=true',
                    lang('run_now'),'play-circle','font-size: 8pt;');
        echo '</TD>';
        echo '</tr>';
    }


    echo '</tbody></table>
          </CENTER>';

}
include ("footer.php");
?>