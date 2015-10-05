<?php
// part of orsee. see orsee.org
ob_start();

$title="files";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
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
        if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_upload_experiment_my'))
                || check_allow('file_upload_experiment_all')) ) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
    } else {
        $allow=check_allow('file_upload_general','download_main.php');
    }
}

if ($proceed) {
    if (!isset($_REQUEST['upload_name'])) $_REQUEST['upload_name']='';
    if (!isset($_REQUEST['upload_type'])) $_REQUEST['upload_type']='';
    if (!isset($_REQUEST['session_id'])) $_REQUEST['session_id']=0;

    if (isset($_REQUEST['upload']) && $_REQUEST['upload']) {
        $redirect_target="admin/download_upload.php?experiment_id=".urlencode($experiment_id)
                .'&upload_name='.urlencode($_REQUEST['upload_name'])
                .'&upload_category='.urlencode($_REQUEST['upload_category']);

        $file=$_FILES['contents'];
        if ($file['size']>$settings['upload_max_size'] || $file['error']>0) {
            message (lang('error_not_uploaded'));
            redirect ($redirect_target);
        } else {
            $continue=true;
            if (!$_REQUEST['upload_name']) {
                $continue=false;
                message (lang('error_no_upload_file_name'));
                redirect ($redirect_target);
            }

            if ($continue) {
                $upload=array();

                $upload['upload_id']=time();
                $upload['experiment_id']=$experiment_id;
                $upload['session_id']=$_REQUEST['session_id'];
                $upload['upload_type']=$_REQUEST['upload_type'];
                $upload['upload_name']=$_REQUEST['upload_name'];
                $upload['upload_filesize']=$file['size'];

                $done=preg_match("/.*\.([^\.]*)$/",$file['name'],$matches);
                $upload['upload_suffix']=$matches[1];

                if ($file['type']) $upload['upload_mimetype']=$file['type'];
                else $upload['upload_mimetype']=downloads__mime_type($upload['upload_suffix']);

                $handle = fopen ($file['tmp_name'], "r");
                $upload_contents = fread ($handle, filesize ($file['tmp_name']));
                fclose ($handle);

                $upload['upload_data']=base64_encode($upload_contents);
                $done=orsee_db_save_array($upload,"uploads",$upload['upload_id'],"upload_id");
                $done2=orsee_db_save_array($upload,"uploads_data",$upload['upload_id'],"upload_id");

                if ($done && $done2) {
                    message (lang('file_uploaded'));
                    $target= ($experiment_id) ? "experiment:".$experiment['experiment_name'] : "general";
                    log__admin("file_upload",$target);
                    if ($experiment_id>0)
                        redirect ('admin/experiment_show.php?experiment_id='.$experiment_id);
                    else redirect ('admin/download_main.php');
                    $proceed=false;
                }
            }
        }
    }
}

if ($proceed) {

    //form for uploading file


    echo '<center>';

    show_message();

    echo '  <form method="post" enctype="multipart/form-data" action="download_upload.php">
                <input type="hidden" name="experiment_id" value="'.$experiment_id.'">
            <table class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">';
    if ($experiment_id>0) {
        echo lang('upload_file_for_experiment');
        echo ' "'.$experiment['experiment_name'].'"';
    } else {
        echo lang('upload_general_file');
    }
    echo '          </TD>
                </TR></TABLE>
            </TD></TR>';

    if ($experiment_id) {
        $sessions=sessions__get_sessions($experiment_id);
        echo '<TR>
                    <TD>
                            '.lang('session').':
                    </TD>
                    <TD>'.select__sessions($_REQUEST['session_id'],'session_id',$sessions).'
                    </TD>
            </TR>';
    }
    echo '  <TR>
                <TD>
                        '.lang('upload_category').':
                </TD>
                <TD>'.language__selectfield_item('file_upload_category','','upload_type',$_REQUEST['upload_type'],false,'fixed_order').'
                </TD>
            </TR>
            <TR>
                <TD>
                    '.lang('upload_name').':
                </TD>
                <TD>
                    <INPUT type="text" name="upload_name" size="30" maxlength="40" value="'.$_REQUEST['upload_name'].'">
                </TD>
            </TR>
            <TR>
                <TD>
                    '.lang('file').':
                </TD>
                <TD>
                    <input name="contents" type=file size=30  accept="*/*">
                    <BR>
                </TD>
            </TR>
            <TR>
                <TD></TD>
                <TD>
                    <input class="button" type=submit name=upload value="'.lang('upload').'">
                    <BR><BR>
                </TD>
            </TR>
        </TABLE>
        </form>

        </center>';

}
include ("footer.php");
?>