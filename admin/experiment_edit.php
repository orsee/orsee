<?php
ob_start();

$menu__area="experiments_new";
$title="edit experiment";
include ("header.php");
              
echo '<center><h4>'.$lang['edit_experiment'].'</h4></center>';


	if ($_REQUEST['experiment_id']) {
		$allow=check_allow('experiment_edit','experiment_show.php?experiment_id='.$_REQUEST['experiment_id']);
      		$edit=orsee_db_load_array("experiments",$_REQUEST['experiment_id'],"experiment_id");
		$edit['experiment_show_type']=$edit['experiment_type'].','.$edit['experiment_ext_type'];
		if (!check_allow('experiment_restriction_override')) 
			check_experiment_allowed($edit,"admin/experiment_show.php?experiment_id=".$edit['experiment_id']);
		}
	   else {
		$allow=check_allow('experiment_edit','experiment_main.php');
		}

	$continue=true;

	if ($_REQUEST['edit']) {
 
		if (isset($_REQUEST['experimenter_list'])) {
			$texperimenter=array();
			foreach ($_REQUEST['experimenter_list'] as $key=>$value) {
				if ($value) $texperimenter[]=$value;
				}
			$_REQUEST['experimenter']=implode(",",$texperimenter);
			}
		else $_REQUEST['experimenter']="";

		if (isset($_REQUEST['experimenter_mail_list'])) {
                        $texperimenter_mail=array();
                        foreach ($_REQUEST['experimenter_mail_list'] as $key=>$value) {
                                if ($key) $texperimenter_mail[]=$value;
                                }
                        $_REQUEST['experimenter_mail']=implode(",",$texperimenter_mail);
                        }
                else $_REQUEST['experimenter_mail']="";


  		if (!$_REQUEST['experiment_public_name']) {
  			message($lang['error_you_have_to_give_public_name']);
  			$continue=false;
			}

                if (!$_REQUEST['experiment_name']) {
                        message($lang['error_you_have_to_give_internal_name']);
                        $continue=false;
                        }

  		if (!eregi("^[^@ \t\r\n]+@[-_0-9a-zA-Z]+\\.[^@ \t\r\n]+$",$_REQUEST['sender_mail'])) {
			message($lang['error_no_valid_sender_mail']);
        		$continue=false;
			}

		if (!$_REQUEST['experimenter']) {
			message($lang['error_at_least_one_experimenter_required']);
			$continue=false;
			}

		if (!$_REQUEST['experimenter_mail']) {
                        message($lang['error_at_least_one_experimenter_mail_required']);
                        $continue=false;
                        }


  		if ($continue) {

        		if (!$_REQUEST['experiment_finished']) $_REQUEST['experiment_finished']="n";

			if (!$_REQUEST['hide_in_stats']) $_REQUEST['hide_in_stats']="n";

			if (!$_REQUEST['hide_in_cal']) $_REQUEST['hide_in_cal']="n";

			if (!$_REQUEST['access_restricted']) $_REQUEST['access_restricted']="n";

			$exptypes=explode(",",$_REQUEST['experiment_show_type']);
			$_REQUEST['experiment_type']=$exptypes[0];
			$_REQUEST['experiment_ext_type']=$exptypes[1];

   			$edit=$_REQUEST; 

   			$done=orsee_db_save_array($edit,"experiments",$edit['experiment_id'],"experiment_id");
   	
			if ($done) {
       				message ($lang['changes_saved']);
				redirect ("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
				}
			   else {
   				message ($lang['database_error']);
				redirect ("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
   				}		

			} 

		$edit=$_REQUEST;

		}


	// form

	echo '<CENTER>';

	show_message();


	if (!$edit['experiment_id']) {
           $gibtsschon=true;
           srand ((double)microtime()*1000000);
           while ($gibtsschon) {
                $crypt_id = "/";
                while (eregi("(/|\\.)",$crypt_id)) { //<or <match <get-var crypt_id> "/"> <match <get-var crypt_id> "\\.">>>
                        $exp_id = rand();
                        $crypt_id=unix_crypt($exp_id);
                        }

                $query="SELECT experiment_id FROM ".table('experiments')."
                        WHERE experiment_id=".$exp_id;
                $line=orsee_query($query);
                if (isset($line['experiment_id'])) $gibtsschon=true; else $gibtsschon=false;
                }
	$edit['experiment_id']=$exp_id;
        }

	echo '<FORM action="experiment_edit.php">
		<INPUT type=hidden name=experiment_id value="'.$edit['experiment_id'].'">
		<TABLE>';

	echo '		<TR>
				<TD>
					'.$lang['id'].'
				</TD>
				<TD>
					'.$edit['experiment_id'].'
				</TD>
			</TR>';

	echo '		<TR>
				<TD>
					'.$lang['internal_name'].':
				</TD>
				<TD>
					<INPUT name=experiment_name type=text size=40 maxlength=100 value="'.stripslashes($edit['experiment_name']).'"> 
					'.help("experiment_name").'
				</TD>
			</TR>';


	echo '		<TR>
				<TD>
					'.$lang['public_name'].':
				</TD>
				<TD>
					<INPUT name=experiment_public_name type=text size=40 maxlength=100 
					value="'.stripslashes($edit['experiment_public_name']).'">
					'.help("experiment_public_name").'
				</TD>
			</TR>';


	echo '		<TR>
				<TD>
					'.$lang['description'].':
				</TD>
				<TD>
					<textarea name=experiment_description rows=5 cols=30 
					wrap=virtual>'.stripslashes($edit['experiment_description']).'</textarea> 
					'.help("experiment_description").'
				</TD>
			</TR>';


	echo '		<TR>
				<TD>
					'.$lang['type'].':
				</TD>
				<TD>
					<SELECT name=experiment_show_type>';

				$experiment_types=array();
				$experiment_internal_types=$system__experiment_types;
				foreach ($experiment_internal_types as $inttype) {
					$expexttypes=load_external_experiment_types($inttype);
					foreach ($expexttypes as $exttype) {
						$value=$inttype.','.$exttype;
						echo '<OPTION value="'.$value.'"';
                                        	   if ($value==$edit['experiment_show_type']) echo ' SELECTED';
                                        		echo '>'.$lang[$inttype].' ('.$exttype.')</OPTION>
                                                ';
						}
					}

			echo '		</SELECT> 
					'.help("experiment_type").'
				</TD>
			</TR>';

	 echo '          <TR>
                                <TD>
                                        '.$lang['class'].':
                                </TD>
                                <TD>
					'; experiment__experiment_class_select_field('experiment_class',
									$edit['experiment_class']);
					echo '
                                </TD>
                        </TR>';

	echo '		<TR>
				<TD valign="top">
					'.$lang['experimenter'].':<BR>'.help("experimenter").'
				</TD>
				<TD>';
					if (!$_REQUEST['experiment_id']) $edit['experimenter']=$expadmindata['adminname'];
					experiment__experimenters_checkbox_list("experimenter_list",$edit['experimenter']);
	echo '			</TD>
			</TR>';

	if ($settings['allow_experiment_restriction']=='y') {
	echo '          <TR>
                                <TD valign="top">
                                        '.$lang['experiment_access_restricted'].':
                                </TD>
                                <TD>
					<INPUT name="access_restricted" type=checkbox value="y"';
                                        if ($edit['access_restricted']=="y") echo " CHECKED";
                                        echo '>
                                        '.help("experiment_access_restricted").'
        			</TD>
                        </TR>';
		}

	echo '		<TR>
				<TD>
					'.$lang['get_emails'].':<BR>'.help("experimenters_email").'
				</TD>
				<TD>';
					if (!$_REQUEST['experiment_id']) $edit['experimenter_mail']=$expadmindata['adminname'];
					experiment__experimenters_checkbox_list("experimenter_mail_list",$edit['experimenter_mail']);
	echo '			</TD>
			</TR>';

	echo '		<TR>
				<TD>
					'.$lang['email_sender_address'].':
				</TD>
				<TD>
					<INPUT name=sender_mail type=text size=40 maxlength=60
					value="';
					if ($edit['sender_mail']) echo stripslashes($edit['sender_mail']);
						else echo $settings['support_mail'];
					echo '"> 
					'.help("email_sender_address").'
				</TD>
			</TR>';


	echo '		<TR>
				<TD>
					'.$lang['experiment_finished?'].'
				</TD>
				<TD>
					<INPUT name=experiment_finished type=checkbox value="y"';
					if ($edit['experiment_finished']=="y") echo " CHECKED";
					echo '> 
					'.help("experiment_finished").'
				</TD>
			</TR>';

	echo '		<TR>
				<TD>
					'.$lang['hide_in_stats?'].'
				</TD>
				<TD>
					<INPUT name=hide_in_stats type=checkbox value="y"'; 
					if ($edit['hide_in_stats']=="y") echo " CHECKED";
					echo '> 
					'.help("experiment_hide_in_stats").'
				</TD>
			</TR>';

	echo '		<TR>
				<TD>
					'.$lang['hide_in_cal?'].'
				</TD>
				<TD>
					<INPUT name=hide_in_cal type=checkbox value="y"';
					if ($edit['hide_in_cal']=="y") echo " CHECKED";
					echo '> 
					'.help("experiment_hide_in_cal").'
				</TD>
			</TR>';

	echo '		<TR>
				<TD>
					'.$lang['experiment_link_to_paper'].':
				</TD>
				<TD>
					<INPUT name=experiment_link_to_paper type=text size=40 maxlength=200
					value="'.stripslashes($edit['experiment_link_to_paper']).'">
					'.help("link_to_paper").'
				</TD>
			</TR>';


	echo '		<TR>
				<TD COLSPAN=2 align=center>
					<INPUT name=edit type=submit 
					value="';
					if (!$_REQUEST['experiment_id']) echo $lang['add'];
						else echo $lang['change'];
					echo '">
				</TD>
			</TR>';


	echo '	</TABLE>
		</FORM>
		<BR>';

	if ($_REQUEST['experiment_id'] && check_allow('experiment_delete')) {
		echo '	<FORM action="experiment_delete.php">
			<INPUT type=hidden name="experiment_id" value="'.$edit['experiment_id'].'">
			<table>
				<TR>
					<TD>
						<INPUT type=submit name=submit value="'.$lang['delete'].'">
					<TD>
				</TR>
			</table>
			</FORM>';
		}

	if ($_REQUEST['experiment_id']) 
		echo '	<BR><BR>
			<A HREF="experiment_show.php?experiment_id='.$_REQUEST['experiment_id'].'">'
			.$lang['mainpage_of_this_experiment'].'</A>';
	
	echo '</center>';


include ("footer.php");
?>
