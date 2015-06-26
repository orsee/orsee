<?php
// part of orsee. see orsee.org
ob_start();
include ("nonoutputheader.php");

        if (isset($_REQUEST['displayfrom']) && $_REQUEST['displayfrom']) $displayfrom= (int) $_REQUEST['displayfrom']; else $displayfrom=time();
        if (isset($_REQUEST['wholeyear']) && $_REQUEST['wholeyear']) $wholeyear=true; else $wholeyear=false;

        pdfoutput__make_pdf_calendar($displayfrom,$wholeyear,true,1);


?>
