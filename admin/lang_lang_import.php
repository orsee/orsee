<?php
ob_start();

$title="upload";
include ("header.php");


	if ($_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id'];
		else redirect ("admin/lang_main.php");

	$allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);

	if ($_REQUEST['upload']) {

		switch ($_REQUEST['action']) {
			case 'upgrade': $do_upgrade=true; $do_update=false; break;
			case 'update': $do_upgrade=false; $do_update=true; break;
			case 'both':	$do_upgrade=true; $do_update=true; break;
			default:	$do_upgrade=false; $do_update=false;
			}

		$file=$_FILES['contents'];
		if ($file['size']>$settings['upload_max_size'] || $file['error']>0) {
			message ($lang['error_not_uploaded']);
			redirect ("admin/lang_lang_import.php?lang_id=".$lang_id);
			}
		   else {
			$upload=array();

			$handle = fopen ($file['tmp_name'], "r");
				$upload_contents = fread ($handle, filesize ($file['tmp_name']));
			fclose ($handle);

			$langtext=base64_decode($upload_contents);

        		$item_array=explode('--:orsee_line:--',$langtext);

			if (count($item_array)<1) {
				message($lang['error_uploaded_file_not_orsee_lang_file']);
				redirect ("admin/lang_lang_import.php?lang_id=".$lang_id);
				}
				
			// load old lang
                        $old_lang=array();
                        $query="SELECT content_type, content_name, ".$lang_id."
                                as content_value FROM ".table('lang');
                        $result=mysql_query($query) or die("Database error: " . mysql_error());
                        while ($line = mysql_fetch_assoc($result)) {
				if ($line['content_value']==NULL) $line['content_value']="";
                                $old_lang[$line['content_type']][$line['content_name']]=$line['content_value'];
                                }
                        mysql_free_result($result);

       			$update=array();
			$upgrade=array();

        		foreach ($item_array as $item) {
				if (!trim($item)) continue;
                		$tarr=explode('--:orsee_next:--',$item);
				if (count($tarr)!=3) {
                                	message($lang['error_uploaded_file_not_orsee_lang_file']);
                                	redirect ("admin/lang_lang_import.php?lang_id=".$lang_id);
                                	}
				if ($old_lang[$tarr[0]][$tarr[1]]) 
                			$update[$tarr[0]][$tarr[1]]=$tarr[2];
				   else $upgrade[$tarr[0]][$tarr[1]]=$tarr[2];
                		}

			$ignored=0; $errors=0;

			if ($do_update) {
				$imported=array();
				foreach ($update as $type=>$item) {
					$count=0;
					foreach ($item as $name=>$value) {
						if ($name=='lang' || $name=='lang_name') continue;
						$query="UPDATE ".table('lang')." 
							SET ".$lang_id."='".mysql_escape_string($value)."' 
							WHERE content_type='".mysql_escape_string($type)."' 
							AND content_name='".mysql_escape_string($name)."'";
						$done=mysql_query($query) or die("Database error: " . mysql_error());
						if (mysql_affected_rows() > 0) $count++;
					   	else $errors++;
						}
					$imported[]=$count.' '.$type;
					}

				$impstring=implode(", ",$imported);
				if ($impstring) message($impstring.' '.$lang['xxx_language_items_updated']);
				}
			   else {
				foreach ($update as $item) $ignored=$ignored+count($item);
				}
		

			// add new items	
			if ($do_upgrade) {
				$query="SELECT max(lang_id) as max_id FROM ".table('lang');
                                $line=orsee_query($query);
                                $new_id=$line['max_id'];

				$created=array();
                                foreach ($upgrade as $type=>$item) {
                                        $count=0;
                                        foreach ($item as $name=>$value) {
                                                if ($name=='lang' || $name=='lang_name') continue;

						if (isset($old_lang[$type][$name])) { 
                                                	$query="UPDATE ".table('lang')."
                                                        	SET ".$lang_id."='".mysql_escape_string($value)."'
                                                        	WHERE content_type='".mysql_escape_string($type)."'
                                                        	AND content_name='".mysql_escape_string($name)."'";
                                                	$done=mysql_query($query) 
								or die("Database error: " . mysql_error());
							}
						   else {
							$new_id++;
							$query="INSERT INTO ".table('lang')."
                                                                SET lang_id='".$new_id."', 
								".$lang_id."='".mysql_escape_string($value)."',
                                                                content_type='".mysql_escape_string($type)."',
                                                                content_name='".mysql_escape_string($name)."'";
                                                        $done=mysql_query($query)                                                                 						or die("Database error: " . mysql_error());
							}
                                                if (mysql_affected_rows() > 0) $count++;
                                                else $errors++;
                                                }
                                        $created[]=$count.' '.$type;
                                        }

                                $crstring=implode(", ",$created);
                                if ($crstring) message($crstring.' '.$lang['xxx_language_items_created']);
                                }
                           else {
                                foreach ($upgrade as $item) $ignored=$ignored+count($item);
                                }

			message($ignored.' '.$lang['xxx_language_items_in_file_ignored']);
			message($lang['please_check_language_symbols']);
			//redirect ("admin/lang_edit.php?el=".$lang_id);
			}
		}

	//form for uploading file

	echo '<center>
		<BR><bR><BR>
			<h4>'.$lang['import_language'].'</h4>

		';

	show_message();


	echo '	<form method=post enctype="multipart/form-data" action="lang_lang_import.php">
                <input type=hidden name="lang_id" value="'.$lang_id.'">

		<table width=80% border=0>
                        <TR>
                                <TD colspan=2>
					Language symbols, default email texts, default texts and help texts 
					will be imported.<BR><BR>
                                        Do you want to<BR><BR>
                                </TD>
                        </TR>
			<TR>
				<TD valign=top width=50%>
					<INPUT type=radio name="action" value="update" CHECKED>
						update this language
				</TD>
				<TD width=50%>
					This means that only language symbols already defined in the system
					will be imported. Existing terms will be overwritten. Use this to 
					install a new language on this system.
				</TD>
			</TR>
			<TR>
				<TD valign="top">
					<INPUT type=radio name="action" value="upgrade">
                                                upgrade this language
				</TD>
				<TD>
					Symbols not existing or empty on your system will be installed.
					Use this when you just have upgraded to a new version of ORSEE and
					want to install the new symbols needed by the new version.
				</TD>
			</TR>
			<TR>
                                <TD valign="top">
                                        <INPUT type=radio name="action" value="both">
                                                both at once
                                </TD>
                                <TD>
                                </TD>
                        </TR>
			<TR><TD colspan=2>&nbsp;</TD></TR>
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
		</form>';

	echo '<BR><BR>
                <A href="lang_lang_edit.php?elang='.$lang_id.'">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';

include ("footer.php");

?>
