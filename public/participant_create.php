<?php
ob_start();
$menu__area="public_register";
include ("header.php");

if (isset($_REQUEST['no'])) redirect("public/");
echo '<BR><BR>';

if (!(isset($_REQUEST['s'])) && !(isset($_REQUEST['r']))) {
	echo '<BR><BR>
		<center>';
	echo $lang['please_choose_subgroup'];
	echo '<BR><BR>
		';

function subpool__save_all_pool_ids($alist) {
global $all_pool_ids;
$all_pool_ids[]=$alist['subpool_id'];
}

$all_pool_ids=NULL;
$query="SELECT * FROM ".table('subpools')." WHERE subpool_id > 1 AND show_at_registration_page='y'
 	ORDER BY subpool_id";
orsee_query($query,"subpool__save_all_pool_ids");

if (count($all_pool_ids)==1) redirect ("public/".thisdoc()."?s=".$all_pool_ids[0]);

if (count($all_pool_ids)==0) redirect ("public/".thisdoc()."?s=1");

////////////////////////////////////////
// show subpools
function subpool__show_subpool_list($alist) {
	global $lang;

        echo '<A HREF="'.thisdoc().'?s='.$alist['subpool_id'].'">';
        echo stripslashes($alist['description']);
        echo '</A>
              <BR><BR>';
}

$query="SELECT *, ".table('lang').".".$lang['lang']." AS description
	FROM ".table('subpools').", ".table('lang')." 
	WHERE subpool_id > 0
	AND ".table('subpools').".subpool_id=".table('lang').".content_name 
	AND ".table('lang').".content_type='subjectpool' 
	AND show_at_registration_page='y' ORDER BY subpool_id";

orsee_query($query,"subpool__show_subpool_list");

echo '</center>';
}

if (isset($_REQUEST['s']) && !(isset($_REQUEST['dr']))) {
	echo '<center>
	      <FORM action='.thisdoc().'>
	      <INPUT type=hidden name=s value="'.$_REQUEST['s'].'">
	      	<TABLE width=70%>
		<TR><TD bgcolor="'.$color['list_title_background'].'">'.$lang['rules'].'</TD></TR>
		<TR><TD>';
	echo content__get_content("rules");
	echo '</TD></TR>
		<TR><TD bgcolor="'.$color['list_title_background'].'">'.$lang['privacy_policy'].'</TD></TR>
		<TR><TD>';
	echo content__get_content("privacy_policy");
	echo '</TD></TR>
		<TR><TD bgcolor="'.$color['list_title_background'].'">'.$lang['do_you_agree_privacy'].'</TD></TR>
		<TR><TD align=center>
		<INPUT type=submit name=dr value="'.$lang['yes'].'">&nbsp;&nbsp;&nbsp;
		<INPUT type=submit name=no value="'.$lang['no'].'">
		</TD></TR>
		</TABLE>
		</FORM>
		</center>';
}



echo '<center>';

$form=true;

	if (isset($_REQUEST['add'])) {
 
		$continue=true;
		$errors__dataform=array();

		participant__check_email(true);
		participant__check_fname(true);
		participant__check_lname(true);
		participant__check_invitations(true);

        	$error_count=count($errors__dataform);

        	if ($error_count>0) {
			$continue=false;
 
	  		if (in_array("email",$errors__dataform)) 
					message($lang['you_have_to_email_address']);
          		if (in_array("fname",$errors__dataform)) 
					message($lang['you_have_to_fname']);
          		if (in_array("lname",$errors__dataform)) 
					message($lang['you_have_to_lname']);
			if (in_array("subscriptions",$errors__dataform))
                                message($lang['at_least_one_exptype_has_to_be_selected']);
          		}


	if ($_REQUEST['email'] && $continue) {

                $query="SELECT participant_id FROM ".table('participants')." 
                 	WHERE email='".$_REQUEST['email']."'";
		$result=orsee_query($query);
		$gibtsschon_part=$result['participant_id'];

                if (!$gibtsschon_part) {
                	$query="SELECT participant_id FROM ".table('participants_temp')." 
                        WHERE email='".$_REQUEST['email']."'";
                	$result=orsee_query($query);
                	$gibtsschon_temp=$result['participant_id'];
                }

		if ($gibtsschon_part) {
			$deleted=participant__deleted($gibtsschon_part);
			$excluded=participant__excluded($gibtsschon_part);
			
			if ($excluded) {
                		message($lang['error_sorry_you_are_excluded']);
				show_message();
                        	echo $lang['if_you_have_questions_write_to'].' ';
                        	echo support_mail_link();
				$continue=false;
                		$form=false;
                	}

                        if ($deleted && $continue) {
                        	message($lang['error_sorry_you_are_deleted']);
				show_message();
                        	echo $lang['if_you_have_questions_write_to'].' ';
                        	echo support_mail_link();
				$continue=false;
                		$form=false;
                        }

			if ($continue) {
				experimentmail__mail_edit_link($gibtsschon_part);
			message($lang['your_email_address_exists'].' '.$lang['message_with_edit_link_mailed']);
				show_message();
				$continue=false;
				$form=false;
				}
		}

		if ($gibtsschon_temp && $continue) {
			experimentmail__confirmation_mail($gibtsschon_temp);
			message($lang['already_registered_but_not_confirmed'].'
				'.$lang['confirmation_message_mailed_again']);
			show_message();
                        $continue=false;
                        $form=false;
		}

	}	


	if ($continue) {


                $participant=$_REQUEST;


		$participant['participant_id']=participant__create_participant_id();
		$participant['participant_id_crypt']=unix_crypt($participant['participant_id']);
		$participant['creation_time']=time();
		if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id']) 
				$participant['subpool_id']=$_REQUEST['subpool_id'];
			else  	$participant['subpool_id']=$settings['subpool_default_registration_id'];
		if (!$participant['language']) $participant['language']=$settings['public_standard_language'];


		$geschafft=orsee_db_save_array($participant,"participants_temp",$participant['participant_id'],"participant_id");

		$p=$participant['participant_id_crypt'];

	   if ($geschafft) {
		log__participant("subscribe",$participant['lname'].', '.$participant['fname']);
		$form=false;
		experimentmail__confirmation_mail($participant['participant_id']);
		redirect ("public/".thisdoc()."?r=t&p=".urlencode($p));
		}
	 	else {	
	    	echo $lang['database_error'].'<BR>';
	  	} 

	}
}


if ($_REQUEST['r']=="t") {
	$form=false;
	show_message($lang['successfully_registered']);
	}


if ($_REQUEST['s'] && $_REQUEST['dr']) {
	$_REQUEST['subpool_id']=$_REQUEST['s'];  

	if ($form) participant__form($lang['registration_form'],$lang['submit']);

	}

echo '</center>';

include("footer.php");

?>
