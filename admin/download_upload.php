<?php
ob_start();

$title="upload";
include ("header.php");


if ($_REQUEST['experiment_id']) {
		$experiment_id=$_REQUEST['experiment_id'];
		if (!check_allow('experiment_restriction_override'))
			check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
		}
		else $experiment_id=0;

	if ($experiment_id>0) {
		$allow=check_allow('download_experiment_upload','experiment_show.php?experiment_id='.$experiment_id);
		$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
		}
	   else {
		$allow=check_allow('download_general_upload','download_main.php');
		}

	if ($_REQUEST['upload']) {

		$file=$_FILES['contents'];
		if ($file['size']>$settings['upload_max_size'] || $file['error']>0) {
			message ($lang['error_not_uploaded']);
			redirect ("admin/download_upload.php");
			}
		   else {
			$upload=array();

			$upload['upload_id']=time();
			$upload['experiment_id']=$experiment_id;	
			$upload['upload_type']=$_REQUEST['upload_category'];
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
				message ($lang['file_uploaded']);

				$target= ($experiment_id) ? "experiment:".$experiment['experiment_name'] : "general";
				log__admin("file_upload",$target);

				if ($experiment_id>0) 
					redirect ('admin/experiment_show.php?experiment_id='.$experiment_id);
				   else redirect ('admin/download_main.php');
				}
			}
		}

	//form for uploading file

	echo '<center>
		<BR><bR><BR>
		<h4>';
	if ($experiment_id>0) {
		echo $lang['upload_file_for_experiment'];
		echo ' "'.$experiment['experiment_name'].'"';
		}
	   else {
		echo $lang['upload_general_file'];
		}
	echo '</h4>';

	show_message();


	echo '	<form method=post enctype="multipart/form-data" action="download_upload.php">
                <input type=hidden name=experiment_id value="'.$experiment_id.'">

		<table width=80% border=0>
                        <TR>
                                <TD>
                                        '.$lang['upload_category'].':
                                </TD>
                                <TD>
                                        <SELECT name="upload_category">
					';
					$categories=explode(",",$settings['upload_categories']);
					foreach ($categories as $cat) 
						echo  '<OPTION value="'.$cat.'">'.$lang[$cat].'</OPTION>';
					echo '</SELECT>
                                </TD>
                        </TR>
			<TR>
				<TD>
					'.$lang['upload_name'].':
				</TD>
				<TD>
					<INPUT type=text name=upload_name size=30 maxlength=40>
				</TD>
			</TR>
			<TR>
				<TD>
					'.$lang['file'].':
				</TD>
				<TD>
					<input name="contents" type=file size=30  accept="*/*">
					<BR>
				</TD>
			</TR>
			<TR>
				<TD></TD>
				<TD>
					<input type=submit name=upload value="'.$lang['upload'].'">
					<BR><BR>
				</TD>
			</TR>
		</TABLE>
		</form>

		</center>';

include ("footer.php");

?>
