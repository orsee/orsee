<?php
ob_start();
include ("nonoutputheader.php");

        if ($_REQUEST['time']) $caltime= (int) $_REQUEST['time']; else $caltime=time();
        if ($_REQUEST['year']) $calyear=true; else $calyear=false;

        pdfoutput__make_calendar($caltime,$calyear,true,0);

        //echo experimentmail__send_calendar();

?>
