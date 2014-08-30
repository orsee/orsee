<?php
// part of orsee. see orsee.org
ob_start();
$title="bulk mail participants";

include ("header.php");

	$allow=check_allow('participants_bulk_mail','participants_main.php');

	if (isset($_REQUEST['send']) && $_REQUEST['send']) $send=true; else $send=false;

	// load invitation languages
	$inv_langs=lang__get_part_langs();


	$plist_ids=$_SESSION['plist_ids'];
	$number=count($plist_ids);

	if ($send) {

		if ((!is_array($plist_ids)) || count($plist_ids)<1) redirect ("admin/");

		// checks
		$bulk=$_REQUEST;

		$continue=true;

		foreach ($inv_langs as $inv_lang) {
                        if (!$bulk[$inv_lang.'_subject']) {
				message ($lang['subject'].': '.$lang['missing_language'].": ".$inv_lang);
				$continue=false;
				}
			if (!$bulk[$inv_lang.'_body']) {
                                message ($lang['body_of_message'].': '.$lang['missing_language'].": ".$inv_lang);
                                $continue=false;
                                }
                        }

		if ($continue) {

			$bulk_id=time();
			foreach ($inv_langs as $inv_lang) {
				$query="INSERT INTO ".table('bulk_mail_texts')." 
					SET bulk_id='".$bulk_id."',
					lang='".$inv_lang."',
					bulk_subject='".mysqli_real_escape_string($GLOBALS['mysqli'],$bulk[$inv_lang.'_subject'])."',
					bulk_text='".mysqli_real_escape_string($GLOBALS['mysqli'],$bulk[$inv_lang.'_body'])."'";
                		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
				}

			$done=experimentmail__send_bulk_mail_to_queue($bulk_id,$plist_ids);

                	message ($number.' '.$lang['xxx_bulk_mails_sent_to_mail_queue']);
			log__admin("bulk_mail","recipients:".$number);
			redirect ('admin/');
			}
		}


        echo '<BR><BR>
                <center>
                        <h4>'.$lang['send_bulk_mail'].'</h4>
			<h4>'.$number.' '.$lang['recipients'].'</h4>
                ';
        show_message();

	// form

        echo '<FORM action="'.thisdoc().'" method="post">

        	<TABLE border=0 width=90%>';

	foreach ($inv_langs as $inv_lang) {

		if (count($inv_langs) > 1) {
			echo '<TR><TD colspan=2 bgcolor="'.$color['list_shade1'].'">'.$inv_lang.':</TD></TR>';
			}
		if (!isset($_REQUEST[$inv_lang.'_subject'])) $_REQUEST[$inv_lang.'_subject']="";
		if (!isset($_REQUEST[$inv_lang.'_body'])) $_REQUEST[$inv_lang.'_body']="";
		echo '
			<TR>
				<TD>
					'.$lang['subject'].':
				</TD>
				<TD>
					<INPUT type=text name="'.$inv_lang.'_subject" size=30 maxlength=80 value="'.
						$_REQUEST[$inv_lang.'_subject'].'">
				</TD>
			</TR>
                	<TR>
				<TD valign=top>
					'.$lang['body_of_message'].':
				</TD>
				<TD>
					<textarea name="'.$inv_lang.'_body" wrap=virtual rows=20 cols=50>'.
						$_REQUEST[$inv_lang.'_body'].'</textarea>
				</TD>
			</TR>';

		echo ' <TR><TD colspan=2>&nbsp;</TD></TR>';

		}

	echo '
                	<TR>
				<TD colspan=2 align=center>
                			<INPUT type=submit name="send" value="'.$lang['send'].'">
                		</TD>
			</TR>';

	echo '
        	</TABLE>
        	</FORM>';

	echo '<BR><BR>

		</CENTER>';

include ("footer.php");
?>
