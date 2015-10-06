<?php
// part of orsee. see orsee.org
ob_start();
$temp__nosession=true;
$menu__area="public_register";
$title="confirm_registration";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['c'])) $c=$_REQUEST['c']; else $c='';
    if (!$c) {
        message(lang('confirmation_error'));
        redirect("public/");
    }
}
if ($proceed) {
    $participant_id=participant__participant_get_if_not_confirmed($c);
    if (!$participant_id) {
        message(lang('already_confirmed_error'));
        redirect("public/");
    } else {
        // change status to active
        $default_active_status=participant_status__get("is_default_active");
        $pars=array(':participant_id'=>$participant_id,':default_active_status'=>$default_active_status);
        if ($settings['allow_permanent_queries']=='y') {
            $qadd=', apply_permanent_queries = 1 ';
        } else $qadd='';
        $query="UPDATE ".table('participants')."
                SET status_id= :default_active_status,
                 confirmation_token = ''
                ".$qadd."
                WHERE participant_id= :participant_id ";
        $done=or_query($query,$pars);

        echo '<center>';
        if (!$done) {
            message(lang('database_error'));
            redirect("public/");
        } else {
            log__participant("confirm",$participant_id);
            // load participant package
            $mess=lang('registration_confirmed').'<BR><BR>';
            $mess.=lang('thanks_for_registration');
            message($mess);
            show_message();
        }
        echo '</center>';
    }
}
include("footer.php");
?>