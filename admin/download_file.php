<?php
ob_start();

include ("nonoutputheader.php");

	$allow=check_allow('download_download','download_main.php');

if (!$_REQUEST['i']) redirect ("admin/");

	// load file specification
	$file=orsee_db_load_array("uploads",$_REQUEST['i'],"upload_id");
	$filedata=orsee_db_load_array("uploads_data",$_REQUEST['i'],"upload_id");
	// mime type
	$mime_type=$file['upload_mimetype'];
	if (!$mime_type) $mime_type=downloads__mime_type($file['upload_suffix']);
	if (!$mime_type) $mime_type="text/*";

	$filename=str_replace(" ","_",$file['upload_name']).
                        ".".
                	$file['upload_suffix'];

	ob_end_clean();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 

	header("Content-Type: ".$mime_type);
	//header( "Content-Disposition: attachment; filename=\"$filename\"");

	header( "Content-Description: File Transfer");

	$data=base64_decode($filedata['upload_data']);

	echo $data;
?>
