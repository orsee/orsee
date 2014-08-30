<?php
// part of orsee. see orsee.org
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

if (count($all_pool_ids)<=1 && $settings['subpool_default_registration_id']) redirect("public/".thisdoc()."?s=".$settings['subpool_default_registration_id']);
elseif (count($all_pool_ids)==1 && !$settings['subpool_default_registration_id']) redirect("public/".thisdoc()."?s=".$all_pool_ids[0]);
elseif (count($all_pool_ids)==0 && !$settings['subpool_default_registration_id']) redirect ("public/".thisdoc()."?s=1");


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

$form=true; $errors__dataform=array();

	if (isset($_REQUEST['add'])) {

		if (!$_REQUEST['subpool_id']) $_REQUEST['subpool_id']=$settings['subpool_default_registration_id'];
        $subpool=orsee_db_load_array("subpools",$_REQUEST['subpool_id'],"subpool_id");
        if (!$subpool['subpool_id']) {
			$subpool=orsee_db_load_array("subpools",$settings['subpool_default_registration_id'],"subpool_id");
			$_REQUEST['subpool_id'] = $subpool['subpool_id'];
		}

		$continue=true;
		
		// checks and errors
		foreach ($_REQUEST as $k=>$v) {
			if(!is_array($v)) $_REQUEST[$k]=trim($v);
		}
		$errors__dataform=participantform__check_fields($_REQUEST,false);		
        $error_count=count($errors__dataform);
        if ($error_count>0) $continue=false;
       
		$response=participantform__check_unique($_REQUEST,"create");
		if ($response['disable_form']) { $continue=false; $form=false; show_message(); }
		elseif($response['problem']) { $continue=false; }
		
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


if (isset($_REQUEST['r']) && $_REQUEST['r']=="t") {
	$form=false;
	show_message($lang['successfully_registered']);
	}


if (isset($_REQUEST['s']) && $_REQUEST['s'] && isset($_REQUEST['dr']) && $_REQUEST['dr']) {
	$_REQUEST['subpool_id']=$_REQUEST['s'];  

	if ($form) participant__show_form($_REQUEST,$lang['submit'],$lang['registration_form'],$errors__dataform,false);

	}

echo '</center>';

include("footer.php");

?>
