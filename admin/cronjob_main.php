<?php
ob_start();

$menu__area="options";
$title="options cronjobs";
include ("header.php");

	$allow=check_allow('regular_tasks_show','options_main.php');

	if (isset($_REQUEST['exec']) && $_REQUEST['exec'] && isset($_REQUEST['job_name']) && $_REQUEST['job_name']) {
		$allow=check_allow('regular_tasks_run','cronjob_main.php');

		$cronjob=$_REQUEST['job_name'];
		$now=time();
		$function_name='cron__'.$cronjob;
                $done=$function_name();
                // save and log job
                $ready=cron__save_and_log_job($cronjob,$now,$done);
		log__admin("cronjob_run",$cronjob);
		message($lang['ran_cronjob_xxx'].' '.$cronjob);
		redirect('admin/'.thisdoc());
		}


        echo '<BR><BR><BR>
                <center><h4>'.$lang['regular_tasks'].'</h4>
		';

	if (check_allow('regular_tasks_add')) echo '
                <BR>
                <form action="cronjob_edit.php">
                <INPUT type=submit name="addit" value="'.$lang['create_new'].'">
                </FORM>';


        echo '<BR>
                <table border=1>
                        <TR>
                                <TD></TD>
        			<TD>'.$lang['enabled?'].'</TD>
				<TD>'.$lang['when_executed?'].'</TD>
				<TD>'.$lang['last_execution'].'</TD>
				<TD></TD>
				<TD></TD>
                        </TR>';

        $query="SELECT *
                FROM ".table('cron_jobs')." 
                ORDER BY job_name";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

	$allow_run=check_allow('regular_tasks_run');
	$allow_edit=check_allow('regular_tasks_edit');

	$shade=true;
	
        while ($line=mysql_fetch_assoc($result)) {

                echo '  <tr class="small"';
			if ($line['enabled']=='n') echo ' bgcolor="'.$color['list_item_emphasize_background'].'"'; 
					elseif ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
					else echo ' bgcolor="'.$color['list_shade2'].'"';
			if ($shade) $shade=false; else $shade=true;

                        echo '>
                                <td valign=top>';
                                        if (isset($lang['cron_job_'.$line['job_name']])) echo $lang['cron_job_'.$line['job_name']];
						else echo $line['job_name'];
			echo '
                                </td>
				<TD>';
					if ($line['enabled']=='y') echo $lang['yes']; else echo $lang['no'];
			echo '	</TD>
                		<TD>';
					if (isset($lang['cron_job_time_'.$line['job_time']])) 
						echo $lang['cron_job_time_'.$line['job_time']];
                        		   else echo $line['job_time'];
			echo '	</TD>
				<TD>';
                                        if ($line['job_last_exec']==0) echo $lang['never'];
					   else echo time__format($lang['lang'],'',false,false,false,false,$line['job_last_exec']);
                        echo '  </TD>
				<TD>';
					if ($allow_edit) 
						echo '<A HREF="cronjob_edit.php?job_name='.$line['job_name'].'">'
                                        		.$lang['edit'].'</A>';
			echo '	</TD>';
			if ($allow_run) 
				echo '<FORM action="'.thisdoc().'"><INPUT type=hidden name="job_name" value="'.$line['job_name'].'">';
			echo '<TD>';
			if ($allow_run) 
				echo '<INPUT class="small" type=submit name="exec" value="'.$lang['run_now'].'">';
			echo '</TD>';
			if ($allow_run) 
				echo '</FORM>';
                echo '  </tr>';
                }


        echo '</table>

                </CENTER>';

include ("footer.php");

?>
