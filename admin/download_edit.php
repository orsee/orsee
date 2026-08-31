<?php
// part of orsee. see orsee.org
ob_start();
$title="edit_file_properties";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['file']) && $_REQUEST['file']) {
        $upload_id=$_REQUEST['file'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    $upload=orsee_db_load_array("uploads",$upload_id,"upload_id");
    if (!isset($upload['upload_id'])) {
        redirect('admin/download_main.php');
    }
}

if ($proceed) {
    if ($upload['experiment_id']>0) {
        $experiment_id=$upload['experiment_id'];
        if (!check_allow('experiment_restriction_override')) {
            check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
        }
    } else {
        $experiment_id=0;
    }
}

if ($proceed) {
    if ($experiment_id>0) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!isset($experiment['experiment_id'])) {
            $experiment_id=0;
        }
    }
}

if ($proceed) {
    if ($experiment_id>0) {
        $experimenters=db_string_to_id_array($experiment['experimenter']);
        if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_edit_experiment_my'))
                || check_allow('file_edit_experiment_all'))) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
    } else {
        $allow=check_allow('file_edit_general','download_main.php');
    }
}


if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $continue=true;
        if (!$_REQUEST['upload_name']) {
            $continue=false;
            message(lang('error_no_upload_file_name'),'error');
        }

        if ($continue) {
            $upload['session_id']=$_REQUEST['session_id'];
            $upload['upload_type']=$_REQUEST['upload_type'];
            $upload['upload_name']=$_REQUEST['upload_name'];

            $done=orsee_db_save_array($upload,"uploads",$upload['upload_id'],"upload_id");

            if ($done) {
                message(lang('changes_saved'));
                $target="file: ".$upload_id;
                $target.= ($experiment_id) ? ", experiment:".$experiment['experiment_name'] : ", general";
                log__admin("file_upload",$target);
                if ($experiment_id) {
                    redirect('admin/download_main.php?experiment_id='.urlencode($experiment_id));
                } else {
                    redirect('admin/download_main.php');
                }
                $proceed=false;
            }
        }
    }
}

if ($proceed) {
    //form for editing file


    show_message();

    echo '  <form method="post" action="download_edit.php">
                <input type="hidden" name="file" value="'.$upload_id.'">
                '.csrf__field().'
                <div class="orsee-panel">
                    <div class="orsee-form-shell">';
    if ($experiment_id) {
        $sessions=sessions__get_sessions($experiment_id);
        echo '          <div class="field">
                            <label class="label">'.lang('session').':</label>
                            <div class="control">
                                <div class="select is-primary">'.select__sessions($upload['session_id'],'session_id',$sessions).'</div>
                            </div>
                        </div>';
    }
    echo '          <div class="field">
                        <label class="label">'.lang('upload_category').':</label>
                        <div class="control">
                            <div class="select is-primary">'.language__selectfield_item('file_upload_category','','upload_type',$upload['upload_type'],false,'fixed_order').'</div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="upload_name">'.lang('upload_name').':</label>
                        <div class="control">
                            <input id="upload_name" class="input is-primary orsee-input orsee-input-text" type="text" name="upload_name" maxlength="40" value="'.htmlspecialchars($upload['upload_name'],ENT_QUOTES,'UTF-8').'">
                        </div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">';
    if ($experiment_id) {
        echo button_back('download_main.php?experiment_id='.urlencode($experiment_id),lang('back'));
    } else {
        echo button_back('download_main.php');
    }
    echo '              </div>
                        <div class="orsee-form-row-col has-text-right">
                            <input class="button orsee-btn" type="submit" name="edit" value="'.lang('save').'">
                        </div>
                    </div>
                </div>
            </div>
        </form>';
}
include("footer.php");

?>
