<?php
// part of orsee. see orsee.org
ob_start();

include ("nonoutputheader.php");
$proceed=true;

if ($proceed) {
    if (isset($_REQUEST['i']) && $_REQUEST['i']) $upload_id=$_REQUEST['i'];
    else redirect ("admin/download_main");
}

if ($proceed) {
    $upload=orsee_db_load_array("uploads",$upload_id,"upload_id");
    if (!isset($upload['upload_id'])) redirect ('admin/download_main.php');
}

if ($proceed) {
    if ($upload['experiment_id']>0) {
        $experiment_id=$upload['experiment_id'];
        if (!check_allow('experiment_restriction_override'))
            check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
    } else $experiment_id=0;
}

if ($proceed) {
    if ($experiment_id>0) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!isset($experiment['experiment_id'])) $experiment_id=0;
    }
}

if ($proceed) {
    if ($experiment_id>0) {
        $experimenters=db_string_to_id_array($experiment['experimenter']);
        if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_download_experiment_my'))
                || check_allow('file_download_experiment_all')) ) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
    } else {
        $allow=check_allow('file_download_general','download_main.php');
    }
}

if ($proceed) {
    // load file specification
    $filedata=orsee_db_load_array("uploads_data",$upload_id,"upload_id");
    // mime type
    $mime_type=$upload['upload_mimetype'];
    if (!$mime_type) $mime_type=downloads__mime_type($upload['upload_suffix']);
    if (!$mime_type) $mime_type="text/*";
    $filename=str_replace(" ","_",$upload['upload_name']).".".$upload['upload_suffix'];

    ob_end_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    header("Content-Type: ".$mime_type."; name=\"$filename\"");
    header( "Content-Disposition: attachment; filename=\"$filename\"");

    header( "Content-Description: File Transfer");

    $data=base64_decode($filedata['upload_data']);

    echo $data;
}
?>