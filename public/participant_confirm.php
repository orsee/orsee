<?php
ob_start();
$temp__nosession=true;

include("header.php");

	echo '<BR><BR>
		<center><BR><BR><h4>'.$lang['confirm_registration'].'</h4>
		';

	$p=$_REQUEST['p'];
	$geschickt__p=$p;
	$p=url_cr_decode($p,true);
	$continue=true;

	if (!$p) {
		message($lang['confirmation_error']);
		redirect("public/");
		}

	$already_confirmed=participant__participant_id_exists($p);

        if ($already_confirmed) {
                message($lang['already_confirmed_error']);
                redirect("public/");
                }


	// copy from participants_temp to participants
	$query="INSERT INTO ".table('participants')." SELECT * FROM ".table('participants_temp')." WHERE participant_id='".$p."'";
	$done=mysql_query($query) or die("Database error: " . mysql_error());

	if (!$done) {
		message($lang['database_error']);
		redirect("public/");
		}

	// delete old entry

        $query="DELETE FROM ".table('participants_temp')." WHERE participant_id='".$p."'";
        $done=mysql_query($query) or die("Database error: " . mysql_error());


	log__participant("confirm",$p);


	// load participant package
	$participant=orsee_db_load_array("participants",$p,"participant_id");

	$mess=$lang['registration_confirmed'].'<BR><BR>
		'.$lang['you_will_be_invited_to'].':<BR><BR>
			<UL>';

		$exptypes=explode(",",$participant['subscriptions']);
		$typenames=load_external_experiment_type_names();

		foreach ($exptypes as $type) {
			$mess.='<LI>'.$typenames[$type].'</LI>';
			}
			$mess.='</UL>
				<BR>
				'.$lang['thanks_for_registration'];

	message($mess);
	show_message();

	echo '</center>';

include("footer.php");


?>
