<?php
ob_start();

if (isset($_REQUEST['subpool_id'])) $subpool_id=$_REQUEST['subpool_id']; else $subpool_id="";

$menu__area="options";
$title="edit subject pool";
include ("header.php");

	if ($subpool_id) $allow=check_allow('subjectpool_edit','subpool_main.php');
		else $allow=check_allow('subjectpool_add','subpool_main.php');

	// load languages
        $languages=get_languages();


        if ($subpool_id) {
                $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
                $etypes=explode(",",$subpool['experiment_types']);
                foreach ($etypes as $etype) {
                        $subpool['exptypes'][$etype]=$etype;
                }

                $query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name='".$subpool_id."'";
                $selfdesc=orsee_query($query);
                }

	$continue=true;

	if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

		if (!$_REQUEST['subpool_name']) {
		  	message ($lang['name_for_subpool_required']);
		  	$continue=false;
			}

                $etypes=array();

                $types=load_external_experiment_types();
                foreach ($types as $etype) {
                        if ($_REQUEST['exptypes'][$etype]) $etypes[]=$_REQUEST['exptypes'][$etype];
                        }
                if (count($etypes)==0) {
                                message($lang['at_minimum_one_exptype_mapping_required']);
                                $continue=false;
                                }

		$selfdesc=$_REQUEST['selfdesc'];
		if (!$subpool_id || $subpool_id > 1) {
        		foreach ($languages as $language) {
                		if (!$selfdesc[$language]) {
                        		message ($lang['missing_language'].': '.$language);
                        		$continue=false;
               				}
				}
			}

	  	if ($continue) {

			if (!$subpool_id) {
				$new=true;
                		$query="SELECT subpool_id+1 as new_sub FROM ".table('subpools')."
                 			ORDER BY subpool_id DESC LIMIT 1";
				$line=orsee_query($query);
				$subpool_id=$line['new_sub'];
				$lsub['content_type']="subjectpool";
				$lsub['content_name']=$subpool_id;
				}
			   else {
				$new=false;
				$query="SELECT * from ".table('lang')." 
					WHERE content_type='subjectpool' 
					AND content_name='".$subpool_id."'";
				$lsub=orsee_query($query);
				}

			$subpool=$_REQUEST;
			$subpool['experiment_types']=implode(",",$etypes);
			foreach ($languages as $language) $lsub[$language]=$selfdesc[$language];

			$done=orsee_db_save_array($subpool,"subpools",$subpool_id,"subpool_id");

			if ($new) $lsub['lang_id']=lang__insert_to_lang($lsub);
			   else $done=orsee_db_save_array($lsub,"lang",$lsub['lang_id'],"lang_id");

       			message ($lang['changes_saved']);
			log__admin("subjectpool_delete","subjectpool:".$subpool['subpool_name']."\nsubpool_id:".$subpool['subpool_id']);
                        redirect ("admin/subpool_edit.php?subpool_id=".$subpool_id);
			} else {
			$subpool=$_REQUEST;
			}
		}


	// form

	echo '	<CENTER>
			<h4>'.$lang['data_for_subpool'].'</h4>';

	show_message();

	echo '
			<FORM action="subpool_edit.php">
				<INPUT type=hidden name="subpool_id" value="'.$subpool['subpool_id'].'">

		<TABLE>
			<TR>
				<TD>
					'.$lang['id'].':
				</TD>
				<TD>
					'.$subpool['subpool_id'].'
				</TD>
			</TR>
			<TR>
				<TD>
					'.$lang['name'].':
				</TD>
				<TD>
					<INPUT name="subpool_name" type=text size=40 maxlength=100 
						value="'.$subpool['subpool_name'].'">
				</TD>
			</TR>
			<TR>
				<TD>
					'.$lang['description'].':
				</TD>
				<TD>
					<textarea name="subpool_description" rows=5 cols=30 wrap=virtual>'.
						stripslashes($subpool['subpool_description']).'</textarea>
				</TD>
			</TR>

			<TR>
				<TD>
					'.$lang['registration_page_type'].'
				</TD>
				<TD>
					<INPUT type=radio name="subpool_type" value="s"';
						if ($subpool['subpool_type']=="s") echo ' CHECKED';
						echo '>'.$lang['student'].'
					&nbsp;&nbsp;
					<INPUT type=radio name="subpool_type" value="w"';
						if ($subpool['subpool_type']=="w") echo ' CHECKED';
						echo '>'.$lang['working'].'
					&nbsp;&nbsp;
					<INPUT type=radio name="subpool_type" value="b"';
						if (!$subpool['subpool_type'] || $subpool['subpool_type']=="b") echo ' CHECKED';
						echo '>'.$lang['both'].'

				</TD>
			</TR>

        		<TR>
				<TD valign=top>
					'.$lang['can_request_invitations_for'].'
				</TD>
        			<TD>
        				';
					experiment_ext_types__checkboxes('exptypes',$lang['lang'],
								$subpool['exptypes'],'exptype_name');
        		echo '	</TD>
			</TR>

			<TR>
				<TD>
					'.$lang['show_at_registration_page?'].'
				</TD>
				<TD>
					<INPUT type=radio name="show_at_registration_page" value="y"';
						if ($subpool['show_at_registration_page']=="y") echo ' CHECKED';
						echo '>'.$lang['yes'].'
					&nbsp;&nbsp;
					<INPUT type=radio name="show_at_registration_page" value="n"';
						 if ($subpool['show_at_registration_page']!="y") echo ' CHECKED';
						echo '>'.$lang['no'].'
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					'.$lang['registration_page_options'].'
				</TD>
			</TR>';

			foreach ($languages as $language) {
				echo '	<TR>
						<TD>
							'.$language.':
						</TD>
						<TD>
							<INPUT name="selfdesc['.$language.']" type=text size=40 maxlength=200 value="'.
								stripslashes($selfdesc[$language]).'">
						</TD>
					</TR>';
				}
	echo '
			<TR>
				<TD COLSPAN=2 align=center>
					<INPUT name="edit" type=submit value="';
					if (!$subpool_id) echo $lang['add']; else echo $lang['change'];
					echo '">
				</TD>
			</TR>


		</table>
		</FORM>
		<BR>';

	if ($subpool_id && check_allow('subjectpool_delete')) {
		echo '	<FORM action="subpool_delete.php">
				<INPUT type=hidden name=subpool_id value="'.$subpool_id.'">
			<table>
				<TR>
					<TD>
						<INPUT type=submit name=submit value="'.$lang['delete'].'">
					<TD>
				</TR>
			</table>
			</form>';
		}

        echo '<BR><BR>
                <A href="subpool_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';

include ("footer.php");

?>
