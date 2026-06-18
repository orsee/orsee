<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="regular_tasks";
include("header.php");

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
            if ($cronjob=="retrieve_emails" && strpos((string)$done,'email_errors:')===0) {
                message($done,'error');
            } else {
                message(lang('ran_cronjob_xxx').' '.$cronjob);
            }
            redirect('admin/'.thisdoc());
        }
    }
}

if ($proceed) {
    show_message();

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('regular_tasks_add')) {
        echo button_link('cronjob_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('enabled?').'</div>';
    echo '<div class="orsee-table-cell">'.lang('when_executed?').'</div>';
    echo '<div class="orsee-table-cell">'.lang('last_execution').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '<div class="orsee-table-cell">'.lang('run_now').'</div>';
    echo '</div>';

    $query="SELECT *
                FROM ".table('cron_jobs')."
                ORDER BY job_name";
    $result=or_query($query);

    $allow_run=check_allow('regular_tasks_run');
    $allow_edit=check_allow('regular_tasks_edit');

    $shade=true;

    while ($line=pdo_fetch_assoc($result)) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        if ($line['enabled']=='n') {
            $row_class.=' orsee-table-row-disabled';
        }

        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('name')).'">';
        if (isset($lang['cron_job_'.$line['job_name']])) {
            echo $lang['cron_job_'.$line['job_name']];
        } else {
            echo $line['job_name'];
        }
        echo '</div>';

        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('enabled?')).'">';
        if ($line['enabled']=='y') {
            echo lang('yes');
        } else {
            echo lang('no');
        }
        echo '</div>';

        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('when_executed?')).'">';
        if (isset($lang['cron_job_time_'.$line['job_time']])) {
            echo $lang['cron_job_time_'.$line['job_time']];
        } else {
            echo $line['job_time'];
        }
        echo '</div>';

        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('last_execution')).'">';
        if ($line['job_last_exec']==0) {
            echo lang('never');
        } else {
            echo ortime__format($line['job_last_exec'],'hide_second:false',lang('lang'));
        }
        echo '</div>';

        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('action')).'">';
        if ($allow_edit) {
            echo button_link('cronjob_edit.php?job_name='.$line['job_name'],lang('edit'),'pencil-square-o');
        }
        echo '</div>';

        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('run_now')).'">';
        if ($allow_run) {
            echo button_link('cronjob_main.php?job_name='.$line['job_name'].'&exec=true',lang('run_now'),'play-circle');
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
