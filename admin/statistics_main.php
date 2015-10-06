<?php
// part of orsee. see orsee.org
ob_start();

$title="statistics";
$menu__area="statistics";
include ("header.php");
if ($proceed) {
    echo '<center>

        <TABLE width=80% border=0>';

    function display_stat_line($s) {
        echo '<TR><TD><A HREF="'.$s[0].'">'.$s[1].'</A></TD></TR>';
    }

    $stats=array();
    if (check_allow('statistics_system_show')) $stats[]=array('statistics_system.php',lang('system_statistics'));
    if (check_allow('statistics_participants_show')) $stats[]=array('statistics_participants.php',lang('subject_pool_statistics'));
    if (check_allow('statistics_server_usage_show')) $stats[]=array('../usage/index.php',lang('server_usage_statistics'));

    if (count($stats)>0) {
        echo '<TR><TD><TABLE class="or_optionssection">';
        echo '<TR class="section_title"><TD>'.lang('statistics').'</TD></TR>';
        foreach ($stats as $s) display_stat_line($s);
        echo '</TABLE></TD></TR>';
    }

    echo '<TR><TD>&nbsp;&nbsp;</TD></TR>';

    $stats=array();
    if (check_allow('log_file_participant_actions_show')) $stats[]=array('statistics_show_log.php?log=participant_actions',lang('participant_actions'));
    if (check_allow('log_file_experimenter_actions_show')) $stats[]=array('statistics_show_log.php?log=experimenter_actions',lang('experimenter_actions'));
    if (check_allow('log_file_regular_tasks_show')) $stats[]=array('statistics_show_log.php?log=regular_tasks',lang('regular_tasks'));

    if (count($stats)>0) {
        echo '<TR><TD><TABLE class="or_optionssection">';
        echo '<TR class="section_title"><TD>'.lang('log_files').'</TD></TR>';
        foreach ($stats as $s) display_stat_line($s);
        echo '</TABLE></TD></TR>';
    }

    echo '<TR><TD>&nbsp;&nbsp;</TD></TR>';

    $stats=array();
    if (check_allow('mailqueue_show_all')) $stats[]=array('mailqueue_show.php',lang('monitor_mail_queue'));
    if ($settings['enable_payment_module']=='y' && (check_allow('payments_budget_view_my') || check_allow('payments_budget_view_all')))
        $stats[]=array('payments_budget_view.php',lang('budget_reports'));

    if (count($stats)>0) {
        echo '<TR><TD><TABLE class="or_optionssection">';
        echo '<TR class="section_title"><TD>'.lang('monitoring').'</TD></TR>';
        foreach ($stats as $s) display_stat_line($s);
        echo '</TABLE></TD></TR>';
    }

    echo '<TR><TD>&nbsp;&nbsp;</TD></TR>';
    echo '</TABLE>';
    echo '</center>';

}
include ("footer.php");
?>