<?php
ob_start();

$menu__area="calendar";
$title="calendar";
include ("header.php");

if ($_REQUEST['time']) $caltime=$_REQUEST['time']; else $caltime=time();
if ($_REQUEST['year']) $calyear=true; else $calyear=false;

	echo '<center>
		<BR><BR>
		<H4>'.$lang['experiment_calendar'].'</h4>

		<TABLE width=80% border=0>
			<TR>
			<TD align=left>
				<A class="small" HREF="calendar_main.php';
					if (!$calyear) echo '?time='.$caltime.'&year=true';
					echo '">';
				if ($calyear) echo $lang['current_month']; else echo $lang['whole_year'];
				echo '</A>
			</TD>
			<TD align=right>
				<A class="small" HREF="calendar_main_print_pdf.php?time='.$caltime.'&year='.$calyear.'"  
					target="_blank">'.$lang['print_version'].'</A>
			</TD>
			</TR>';
		if (check_allow('lab_space_edit')) echo '
			<TR><TD colspan=2 align=center>
				<A HREF="lab_space_edit.php">'.$lang['reserve_lab_space'].'</A><BR>
				<FONT class="small">'.$lang['for_session_time_reservation_please_use_experiments'].'</FONT>
			</TD></TR>';

	echo '	</TABLE>';


	if (!$calyear) {
        	$lastmonth=date__skip_months(-1,$caltime);
        	$nextmonth=date__skip_months(1,$caltime);

		echo '<BR><BR>
			<A HREF="calendar_main.php?time='.$lastmonth.'">'.$lang['SOONER'].'</A>
			<BR><BR>';

		calendar__month_table($caltime,1,true);

		echo '<BR><BR>
			<A HREF="calendar_main.php?time='.$nextmonth.'">'.$lang['LATER'].'</A>
			<BR><BR>';
		}
		else {
		$lastyear=date__skip_years(-1,$caltime);
		$nextyear=date__skip_years(1,$caltime);

		echo '
        		<BR><BR>
        		<A HREF="'.thisdoc().'?time='.$lastyear.'&year=true">'.$lang['SOONER'].'</A>
        		<BR><BR>';

		calendar__show_year($caltime,true);

		echo '
        		<BR><BR>
        		<A HREF="'.thisdoc().'?time='.$nextyear.'&year=true">'.$lang['LATER'].'</A>
        		<BR><BR>';
		}

	echo '</center>';

include ("footer.php");

?>
