<?php
ob_start();

$title="options import data";
include ("header.php");

	$allow=check_allow('import_data','options_main.php');

	if ($_REQUEST['step']) $step=$_REQUEST['step']; else $step=1;

        echo '<BR><BR><BR>
                <center><h4>Import data from old versions</h4>
		';


	// first step: from which system?
	if ($step==1 || !$step) {


        	echo '	<BR>
			<FORM action="import_data.php">
			<INPUT type=hidden name="step" value="2">
                	<table border=0 width=80%>
                        	<TR>
					<TD align=right>
						Import from version
					</TD>
					<TD align=left>
						<SELECT name="old_version">
							<OPTION value="1.0"';
								if ($_SESSION['import_data']['old_version']=='1.0')
									echo ' SELECTED';
								echo '>ORSEE 1.0.1 - MetaHTML</OPTION>
						</SELECT>
					</TD>
                        	</TR>
				<TR>
					<TD colspan=2 align=right>
						<INPUT type=submit name="submit" value="Continue">
					</TD>
				</TR>
			</table>
			</FORM>

			';
		}

	// second step: database stuff

	if ($step==2) {

		$_SESSION['import_data']['old_version']=$_REQUEST['old_version'];

		if ($_SESSION['import_data']['old_version']=='1.0') {


			echo '  <BR>
                        	<FORM action="import_data.php">
                        	<INPUT type=hidden name="step" value="3">
                        	<table border=0 width=80%>
					<TR>
						<TD colspan=2 align=center>
							Parameters for database containing old data
						</TD>
					</TR>
					<TR>
                                        	<TD align=right>
                                                	Database host:
                                        	</TD>
                                        	<TD align=left>
                                                	<INPUT type=text name="old_db_host" size=30 maxlength=100 value="';
							if ($_SESSION['import_data']['old_db_host']) 
								echo $_SESSION['import_data']['old_db_host'];
							   else echo 'localhost';
							echo '">
                                        	</TD>
                                	</TR
                                	<TR>
                                        	<TD align=right>
                                                	Database name:
                                        	</TD>
                                        	<TD align=left>
							<INPUT type=text name="old_db_name" size=30 maxlength=100 
								value="'.$_SESSION['import_data']['old_db_name'].'">
                                        	</TD>
                                	</TR>
					<TR>
                                        	<TD align=right>
                                                	Database user:
                                        	</TD>
                                        	<TD align=left>
                                                	<INPUT type=text name="old_db_user" size=30 maxlength=100 
								value="'.$_SESSION['import_data']['old_db_user'].'">
                                        	</TD>
                                	</TR>
					<TR>
                                        	<TD align=right>
                                                	Database password:
                                        	</TD>
                                        	<TD align=left>
                                                	<INPUT type=password name="old_db_password" size=30 maxlength=100 
								value="'.$_SESSION['import_data']['old_db_password'].'">
                                        	</TD>
                                	</TR>
                                	<TR>
                                        	<TD colspan=2 align=right>
                                                	<INPUT type=submit name="submit" value="Continue">
                                        	</TD>
                                	</TR>
                        	</table>
                        	</FORM>

                        	';
			}
		}


	// step 3: ask for some options

	if ($step==3) {

		if ($_SESSION['import_data']['old_version']=='1.0') {

			if ($_REQUEST['submit']) {
				$_SESSION['import_data']['old_db_host']=$_REQUEST['old_db_host'];
                		$_SESSION['import_data']['old_db_name']=$_REQUEST['old_db_name'];
				$_SESSION['import_data']['old_db_user']=$_REQUEST['old_db_user'];
				$_SESSION['import_data']['old_db_password']=$_REQUEST['old_db_password'];
				}

			function import_db_config() {
				$link2 = mysql_connect($_SESSION['import_data']['old_db_host'],$_SESSION['import_data']['old_db_user'],
							$_SESSION['import_data']['old_db_password'])
       						or die("Database connection failed: " . mysql_error());
				mysql_select_db($_SESSION['import_data']['old_db_name'], $link2) or die("Database selection failed.");
				}


			function select_field_new_subpools ($old_subpool_id,$selected) {
				echo '<SELECT name="new_subpool['.$old_subpool_id.']">';
				$query="SELECT * FROM ".table('subpools');
				$result=mysql_query($query) or die("Database error: " . mysql_error());
				while ($line=mysql_fetch_assoc($result)) {
					echo '<OPTION value="'.$line['subpool_id'].'"';
					if ($line['subpool_id']==$selected) echo ' SELECTED';
					echo '>'.$line['subpool_name'].'</OPTION>';
                                	}
				echo '<SELECT>';
				}

			function get_array_old_subpools() {
				$query="SELECT * FROM subpools";
				$result=mysql_query($query) or die("Database error: " . mysql_error());;
				$oldpools=array();
                                while ($line=mysql_fetch_assoc($result)) {
                                        $oldpools[$line['subpool_id']]=$line['subpool_name'];
                                        }
				return $oldpools;
				}

			function select_field_new_labs ($old_lab_id,$selected) {
                                echo '<SELECT name="new_laboratories['.$old_lab_id.']">';
                                $query="SELECT * FROM ".table('lang')." WHERE content_type='laboratory'";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                while ($line=mysql_fetch_assoc($result)) {
                                        echo '<OPTION value="'.$line['content_name'].'"';
                                        if ($line['content_name']==$selected) echo ' SELECTED';
                                        echo '>'.laboratories__strip_lab_name($line['subpool_name']).'</OPTION>';
                                        }
                                echo '<SELECT>';
                                }

			function get_array_old_laboratories() {
                                $query="SELECT * FROM laboratories";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $oldlabs=array();
                                while ($line=mysql_fetch_assoc($result)) $oldlabs[$line['laboratory_id']]=$line['laboratory_name'];
				return $oldlabs;
				}

			function old_exp_type_to_ext_type_select_field($oldexptype,$selected) {
				if ($oldexptype=='online_survey') $search='online-survey';
					else $search=$oldexptype;

        			$query="SELECT *
                			FROM ".table('experiment_types')." 
                			WHERE enabled='y' AND exptype_mapping LIKE '%".$search."%' 
                			ORDER BY exptype_id";
        			$result=mysql_query($query);
				echo '<SELECT name="new_ext_type_'.$oldexptype.'">';
        			while ($line = mysql_fetch_assoc($result)) {
                			echo '<OPTION value="'.$line['exptype_name'].'"';
                			if ($line['exptype_name']==$selected) echo " SELECTED";
                			echo '>'.$line['exptype_name'].'</OPTION>';
                			}
				echo '</SELECT>';

				}
			
			import_db_config();
			$old_subjectpools=get_array_old_subpools();
			$old_labs=get_array_old_laboratories();

			site__database_config();
			
                	echo '  <BR>
                        	<FORM action="import_data.php">
                        	<INPUT type=hidden name="step" value="4">
                        	<table border=0 width=80%>
                                	<TR>
                                        	<TD colspan=2 align=center>
                                                	Import options
                                        	</TD>
                                	</TR>
					<TR><TD colspan=2><hr></TD></TR>
                                	<TR>
                                        	<TD align=right>
                                                	Subscribe participants who voted for LABORATORY experiments to experiment type:
                                        	</TD>
                                        	<TD align=left>
                                                	';
							experiment_ext_types__checkboxes('new_exptypes_lab',$lang['lang'],
                                                                	$_SESSION['import_data']['new_exptypes_lab'],'exptype_name');
							echo '
                                        	</TD>
                                	</TR>
					<TR><TD colspan=2><hr></TD></TR>
                                	<TR>
                                        	<TD align=right>
                                                	Subscribe participants who voted for INTERNET experiments to experiment type:
                                        	</TD>
                                        	<TD align=left>
                                                	';
                                                        experiment_ext_types__checkboxes('new_exptypes_internet',$lang['lang'],
                                                                        $_SESSION['import_data']['new_exptypes_internet'],'exptype_name');
                                                        echo '
                                        	</TD>
                                	</TR>

					<TR><TD colspan=2><hr></TD></TR>

					';



					foreach ($old_subjectpools as $oldpool_id=>$oldpool_name) {
                                		echo '<TR>
                                        		<TD align=right>
                                                		Move participants from subjectpool "'.$oldpool_name.'" to:
                                        		</TD>
                                        		<TD align=left>
                                                		'; select_field_new_subpools ($oldpool_id,
								$_SESSION['import_data']['new_subpool'][$oldpool_id]); echo '
                                        		</TD>
                                			</TR>
							<TR><TD colspan=2><hr></TD></TR>
							';
						}

					echo '
					<TR>
                                                <TD align=right>
                                                        To laboratory experiments, assign external type:
                                                </TD>
                                                <TD align=left>
                                                        ';
                                                        old_exp_type_to_ext_type_select_field('laboratory',
                                                                        $_SESSION['import_data']['new_ext_type_laboratory']);
                                                        echo '
                                                </TD>
                                        </TR>

                                        <TR><TD colspan=2><hr></TD></TR>

                                        <TR>
                                                <TD align=right>
                                                        To online-surveys, assign external type:
                                                </TD>
                                                <TD align=left>
                                                        ';
                                                        old_exp_type_to_ext_type_select_field('online_survey',
                                                                        $_SESSION['import_data']['new_ext_type_online_survey']);
                                                        echo '
                                                </TD>
                                        </TR>

					<TR><TD colspan=2><hr></TD></TR>

                                        <TR>
                                                <TD align=right>
                                                        To internet experiments, assign external type:
                                                </TD>
                                                <TD align=left>
                                                        ';
                                                        old_exp_type_to_ext_type_select_field('internet',
                                                                        $_SESSION['import_data']['new_ext_type_internet']);
                                                        echo '
                                                </TD>
                                        </TR>

					<TR><TD colspan=2><hr></TD></TR>

                                        ';



                                        foreach ($old_labs as $oldlab_id=>$oldlab_name) {
                                                echo '<TR>
                                                        <TD align=right>
                                                                Old laboratory "'.$oldlab_name.'" is now:
                                                        </TD>
                                                        <TD align=left>
                                                                ';
								laboratories__select_field('new_laboratories['.$oldlab_id.']',
                                                                $_SESSION['import_data']['new_laboratories'][$oldlab_id]); echo '
                                                        </TD>
                                                        </TR>
                                                        <TR><TD colspan=2><hr></TD></TR>
                                                        ';
                                                }

					echo '

                                        <TR><TD colspan=2><hr></TD></TR>

                                        ';

					echo '
                                	<TR>
                                        	<TD colspan=2 align=right>
                                                	<INPUT type=submit name="submit" value="Continue">
                                        	</TD>
                                	</TR>
                        	</table>
                        	</FORM>

                        	';
			}

		}


	// step4: confirm:
        if ($step==4) {

		if ($_SESSION['import_data']['old_version']=='1.0') {

			if ($_REQUEST['submit']) {
			$_SESSION['import_data']['new_exptypes_lab']=$_REQUEST['new_exptypes_lab'];
                        $_SESSION['import_data']['new_exptypes_internet']=$_REQUEST['new_exptypes_internet'];
                        $_SESSION['import_data']['new_subpool']=$_REQUEST['new_subpool'];
			$_SESSION['import_data']['new_ext_type_laboratory']=$_REQUEST['new_ext_type_laboratory'];
			$_SESSION['import_data']['new_ext_type_online_survey']=$_REQUEST['new_ext_type_online_survey'];
			$_SESSION['import_data']['new_ext_type_internet']=$_REQUEST['new_ext_type_internet'];
			$_SESSION['import_data']['new_laboratories']=$_REQUEST['new_laboratories'];
			}

			$continue=true;

			if (count($_SESSION['import_data']['new_exptypes_lab'])<1) {
				message ("At least one new  experiment type should be given for LABORATORY experiments.");
				$continue=false;
				}

			if (count($_SESSION['import_data']['new_exptypes_internet'])<1) {
                                message ("At least one new experiment type should be given for INTERNET experiments.");
                                $continue=false;
                                }

			if (!$continue) redirect ("admin/import_data.php?step=3");

			$import_settings=$_SESSION['import_data'];

			function list_array($array) {
				echo '<UL>';
				while (list ($key, $value) = each ($array)) {
					if (is_array($value)) {
						echo "<LI><b>$key:</b>\n";
						list_array($value);
						echo "</li>\n";
						}
					else echo "<LI><b>$key:</b> $value</LI>\n";
                                        }
				echo '</UL>';
				}


			echo '  <BR>
                                <FORM action="import_data.php">
                                <INPUT type=hidden name="step" value="5">
                                <table border=0 width=80%>
                                        <TR>
                                                <TD colspan=2 align=center>
                                                        The following dump contains all import settings.<BR>
							Do you want to continue and start the data import?
                                                </TD>
                                        </TR>
					<TR>
						<TD colspan=2>
							<pre>';
							list_array($import_settings);
							echo '</pre>
						</TD>
					</TR>
					<TR>
                                                <TD colspan=2 align=right>
                                                        <INPUT type=submit name="submit" value="Continue">
                                                </TD>
                                        </TR>

				</table>
                                </FORM>

                                ';

			}
		}

	// step4: import it!
        if ($step==5) {

                if ($_SESSION['import_data']['old_version']=='1.0') {

			$imset=$_SESSION['import_data'];

			$continue=true;

                        $check_settings=array('old_version','old_db_host','old_db_name','old_db_user',
						'old_db_password','new_exptypes_lab','new_exptypes_internet',
						'new_subpool');

			foreach ($check_settings as $setting) {
				if (!isset($imset[$setting])) {
					echo 'Error: import setting "'.$setting.'" not found!<BR>';
					$continue=false;
					}
				}

			function import_db_config() {
				global $imset;
                                $link2 = mysql_connect($imset['old_db_host'],$imset['old_db_user'],
                                                        $imset['old_db_password'])
                                                or die("Database connection failed: " . mysql_error());
                                mysql_select_db($imset['old_db_name'], $link2) or die("Database selection failed.");
                                }


			if ($continue) {

				// import participants

				// load old participants
				import_db_config();
				$query="SELECT * FROM participants";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
				$old_participants=array();
                                while ($line=mysql_fetch_assoc($result)) $old_participants[]=$line;
				
				site__database_config();


				$query="DELETE FROM ".table('participants');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

				foreach ($old_participants as $op) {

					// translate subscriptions
					$subscriptions=array();
					if ($op['get_labor']=='y') 
						$subscriptions=array_merge($subscriptions,$imset['new_exptypes_lab']);
					if ($op['get_internet']=='y') 
						$subscriptions=array_merge($subscriptions,$imset['new_exptypes_internet']);
					$op['subscriptions']=implode(",",$subscriptions);


					// translate subjectpool
					$op['subpool_id']=$imset['new_subpool'][$op['subpool_id']];

					$done=orsee_db_save_array($op,"participants",$op['participant_id'],"participant_id");
					}
				unset($old_participants);


				// import participants_os

				// load old participants_os
                                import_db_config();
                                $query="SELECT * FROM participants_os";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_participants=array();
                                while ($line=mysql_fetch_assoc($result)) $old_participants[]=$line;

                                site__database_config();


                                $query="DELETE FROM ".table('participants_os');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

                                foreach ($old_participants as $op) {

                                        // translate subscriptions
                                        $subscriptions=array();
                                        if ($op['get_labor']=='y')
                                                $subscriptions=array_merge($subscriptions,$imset['new_exptypes_lab']);
                                        if ($op['get_internet']=='y')
                                                $subscriptions=array_merge($subscriptions,$imset['new_exptypes_internet']);
                                        $op['subscriptions']=implode(",",$subscriptions);


                                        // translate subjectpool
                                        $op['subpool_id']=$imset['new_subpool'][$op['subpool_id']];

                                        $done=orsee_db_save_array($op,"participants_os",$op['participant_id'],"participant_id");
                                        }
				unset($old_participants);


                                // import experiments

                                // load old experiments
                                import_db_config();
                                $query="SELECT * FROM experiments";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_experiments=array();
                                while ($line=mysql_fetch_assoc($result)) $old_experiments[]=$line;

                                site__database_config();


                                $query="DELETE FROM ".table('experiments');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

				// load experimenters and mails
				$admins=array(); $admin_mails=array();
				$query="SELECT * FROM ".table('admin');
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
				while ($line=mysql_fetch_assoc($result)) {
					$admins[$line['fname'].' '.$line['lname']]=$line['adminname'];
					$admins[$line['lname']]=$line['adminname'];
					$admins[$line['email']]=$line['adminname'];
					$admin_mails[$line['email']]=$line['adminname'];
					}


                                foreach ($old_experiments as $exp) {

                                        // translate internal to external experiment type
					if ($exp['experiment_type']=='laboratory')
						$exp['experiment_ext_type']=$imset['new_ext_type_laboratory'];
					   elseif ($exp['experiment_type']=='internet')
						$exp['experiment_ext_type']=$imset['new_ext_type_internet'];
					   elseif ($exp['experiment_type']=='online-survey')
                                                $exp['experiment_ext_type']=$imset['new_ext_type_online_survey'];

					// guess experimenters
					$tadmins=explode(",",$exp['experimenter']);
					foreach ($tadmins as $key=>$tadmin) {
						if (isset($admins[trim($tadmin)])) $tadmins[$key]=$admins[trim($tadmin)];
						}
					$exp['experimenter']=implode(",",$tadmins);

					// guess experimenter mails
					$tmails=explode(",",$exp['experimenter_mail']);
                                        foreach ($tmails as $key=>$tmail) {
                                                if (isset($admin_mails[trim($tmail)])) $tmails[$key]=$admin_mails[trim($tmail)];
                                                }
                                        $exp['experimenter_mail']=implode(",",$tmails);
					$exp['experiment_class']='0';

                                        $done=orsee_db_save_array($exp,"experiments",$exp['experiment_id'],"experiment_id");
                                        }
                                unset($old_experiments);


				// import os stuff
				
				// whcih tables?
				$os_tables=array('os_data_form','os_items_checkbox','os_items_radio','os_items_select_numbers',
						'os_items_select_text','os_items_textarea','os_items_textline','os_page_content',
						'os_playerdata','os_pre_answers','os_properties','os_questions','os_results');
				$os_ids=array('experiment_id','item_id','item_id','item_id',
						'item_id','item_id','item_id','page_id',
						'playerdata_id','answer_id','experiment_id','question_id','result_id');

				$i=0;
				foreach ($os_tables as $table) {
					$id=$os_ids[$i];
                                	// load old data
                                	import_db_config();
                                	$query="SELECT * FROM ".$table;
                                	$result=mysql_query($query) or die("Database error: " . mysql_error());
                                	$old_entries=array();
                                	while ($line=mysql_fetch_assoc($result)) $old_entries[]=$line;

                                	site__database_config();

                                	$query="DELETE FROM ".table($table);
                                	$done=mysql_query($query) or die("Database error: " . mysql_error());

                                	foreach ($old_entries as $entry) {
                                        	$done=orsee_db_save_array($entry,$table,$entry[$id],$id);
                                        	}
                                	unset($old_entries);
					$i++;
					}

				// import sessions

                                // load old sessions
                                import_db_config();
                                $query="SELECT * FROM sessions";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_sessions=array();
                                while ($line=mysql_fetch_assoc($result)) $old_sessions[]=$line;

                                site__database_config();

                                $query="DELETE FROM ".table('sessions');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

                                foreach ($old_sessions as $sess) {
					$sess['send_reminder_on']='enough_participants_needed';
					$sess['reminder_checked']=$sess['reminder_sent'];
					$sess['noshow_warning_sent']='y';

					// translate laboratories
					$sess['laboratory_id']=$imset['new_laboratories'][$sess['laboratory_id']];

                                        $done=orsee_db_save_array($sess,"sessions",$sess['session_id'],"session_id");
                                        }
                                unset($old_sessions);

				// import participate_at

				// this might need a lot of memory, so let's do it in tranches of 200
                                // load old participate_data
				$i=0; $count=1;

				$query="DELETE FROM ".table('participate_at');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

				while ($count > 0) {
                                	import_db_config();
                                	$query="SELECT * FROM participate_at ORDER by participate_id LIMIT $i,200";
                                	$result=mysql_query($query) or die("Database error: " . mysql_error());
					$count=mysql_num_rows($result);

                                	$old_part=array();
                                	while ($line=mysql_fetch_assoc($result)) $old_part[]=$line;

                                	site__database_config();

                                	foreach ($old_part as $part) {
						$query="INSERT INTO ".table('participate_at')." 
							SET participate_id='".$part['participate_id']."',
							participant_id='".$part['participant_id']."',
							experiment_id='".$part['experiment_id']."',
							invited='".$part['invited']."',
							registered='".$part['registered']."',
							shownup='".$part['shownup']."',
							participated='".$part['participated']."',
							session_id='".$part['session_id']."'";
                                        	$done=mysql_query($query); 
                                        	}
                                	unset($old_part);
					$i=$i+200;
					}

				$inst_langs=get_languages();

				// import experiment mails

				// load old mails
                                import_db_config();
                                $query="SELECT * FROM experimentmail";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_mails=array();
                                while ($line=mysql_fetch_assoc($result)) $old_mails[]=$line;

                                site__database_config();

                                $query="DELETE FROM ".table('lang')." WHERE content_type='experiment_invitation_mail'";
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

				$query="SELECT max(lang_id) as max_id FROM ".table('lang');
                                $line=orsee_query($query);
				$new_id=$line['max_id'];

                                foreach ($old_mails as $omail) {
					$new_id++;
					$newmail=array();
					$newmail['enabled']='y';
					$newmail['content_type']='experiment_invitation_mail';
					$newmail['content_name']=$omail['experiment_id'];
					$value=$omail['subject']."\n".$omail['mailtext'];
					foreach ($inst_langs as $tlang) $newmail[$tlang]=$value;
                                        $done=orsee_db_save_array($newmail,"lang",$new_id,"lang_id");
					unset($newmail);
                                        }
                                unset($old_mails);


				// import fields of studies

                                // load old studies
                                import_db_config();
                                $query="SELECT * FROM field_of_studies ORDER by studies_id";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_studies=array();
                                while ($line=mysql_fetch_assoc($result)) $old_studies[]=$line;

                                site__database_config();

                                $query="DELETE FROM ".table('lang')." WHERE content_type='field_of_studies'";
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

                                $query="SELECT max(lang_id) as max_id FROM ".table('lang');
                                $line=orsee_query($query);
                                $new_id=$line['max_id'];

                                foreach ($old_studies as $study) {
                                        $new_id++;
                                        $newmail=array();
                                        $newmail['enabled']='y';
                                        $newmail['content_type']='field_of_studies';
                                        $newmail['content_name']=$study['studies_id'];
                                        foreach ($inst_langs as $tlang) {
						if (isset($study[$tlang])) $newmail[$tlang]=$study[$tlang];
							else $newmail[$tlang]=$study['en'];
						}
                                        $done=orsee_db_save_array($newmail,"lang",$new_id,"lang_id");
                                        unset($newmail);
                                        }
                                unset($old_studies);


				// import professions

                                // load old professions
                                import_db_config();
                                $query="SELECT * FROM professions ORDER by profession_id";
                                $result=mysql_query($query) or die("Database error: " . mysql_error());
                                $old_profs=array();
                                while ($line=mysql_fetch_assoc($result)) $old_profs[]=$line;

                                site__database_config();

                                $query="DELETE FROM ".table('lang')." WHERE content_type='profession'";
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

                                $query="SELECT max(lang_id) as max_id FROM ".table('lang');
                                $line=orsee_query($query);
                                $new_id=$line['max_id'];

                                foreach ($old_profs as $prof) {
                                        $new_id++;
                                        $newmail=array();
                                        $newmail['enabled']='y';
                                        $newmail['content_type']='profession';
                                        $newmail['content_name']=$prof['profession_id'];
                                        foreach ($inst_langs as $tlang) {
                                                if (isset($prof[$tlang])) $newmail[$tlang]=$prof[$tlang];
                                                        else $newmail[$tlang]=$prof['en'];
						}
                                        $done=orsee_db_save_array($newmail,"lang",$new_id,"lang_id");
                                        unset($newmail);
                                        }
                                unset($old_profs);

				// clean up lang table
				$done=lang__reorganize_lang_table(1000);
		


				 // import participants log

                                // this might need a lot of memory, so let's do it in tranches of 200
                                // load old log data
                                $i=0; $count=1;

                                $query="DELETE FROM ".table('participants_log');
                                $done=mysql_query($query) or die("Database error: " . mysql_error());

                                while ($count > 0) {
                                        import_db_config();
                                        $query="SELECT * FROM participants_log ORDER by log_id LIMIT $i,200";
                                        $result=mysql_query($query) or die("Database error: " . mysql_error());
                                        $count=mysql_num_rows($result);

                                        $old_log=array();
                                        while ($line=mysql_fetch_assoc($result)) $old_log[]=$line;

                                        site__database_config();

                                        foreach ($old_log as $log) {
                                                $query="INSERT INTO ".table('participants_log')."
                                                        SET log_id='".$log['log_id']."',
                                                        id='".$log['participant_id']."',
                                                        action='".$log['action']."',
                                                        target='',
                                                        year='".$log['year']."',
                                                        month='".$log['month']."',
                                                        day='".$log['day']."',
                                                        timestamp='".$log['timestamp']."'";
                                                $done=mysql_query($query);
                                                }
                                        unset($old_log);
                                        $i=$i+200;
                                        }

				$query="UPDATE ".table('participants_log')." 
					SET action='subscribe' where action='register'";
                                $done=mysql_query($query) or die("Database error: " . mysql_error());
	
				// end continue
				}

			// end version 1.0
                        }

		// end step 5
                }

        echo '</CENTER>';

include ("footer.php");

?>
