<?php
// part of orsee. see orsee.org
ob_start();

include ("nonoutputheader.php");
$proceed=true;

if ($proceed) {
    if (isset($_REQUEST['message_id']) && $_REQUEST['message_id']) $message_id=$_REQUEST['message_id']; else $message_id='';
    if (!$message_id) redirect('admin/emails_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['k'])) $k=$_REQUEST['k']; else redirect('admin/emails_main.php');
}

if ($proceed) {
    $email=orsee_db_load_array("emails",$message_id,"message_id");
    if (!isset($email['message_id'])) redirect('admin/emails_main.php');
}

if ($proceed) {
    if (!$email['has_attachments']) redirect('admin/emails_view.php?message_id='.urlencode($message_id));
}

if ($proceed) {
    $attachments=email__dbstring_to_attachment_array($email['attachment_data'],false);
    if (!isset($attachments[$k])) redirect('admin/emails_view.php?message_id='.urlencode($message_id));
}

if ($proceed) {
    // mime type
    $mime_type=$attachments[$k]['mimetype'];
    if (!$mime_type || $mime_type=='application/x-download') $mime_type=downloads__mime_type(pathinfo($attachments[$k]['filename'], PATHINFO_EXTENSION));
    if (!$mime_type) $mime_type="text/*";
    $filename=str_replace(" ","_",$attachments[$k]['filename']);

    ob_end_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    header("Content-Type: ".$mime_type."; name=\"$filename\"");
    header( "Content-Disposition: attachment; filename=\"$filename\"");

    header( "Content-Description: File Transfer");

    $data=$attachments[$k]['data'];

    echo $data;
}
?>