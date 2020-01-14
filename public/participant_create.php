<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="public_register";
$title="registration_form";
include ("header.php");

if ($proceed) {
    // check for sub-subject pool
    if (!(isset($_SESSION['subpool_id']))) {
        // get available subpools
        $subpools=subpools__get_subpools();
        $all_pool_ids=array();
        foreach ($subpools as $pool) {
            if ($pool['subpool_id']>1 && $pool['show_at_registration_page']=='y') {
                $all_pool_ids[]=$pool['subpool_id'];
            }
        }

        if (isset($_REQUEST['s']) && $_REQUEST['s']) {
            if (in_array(trim($_REQUEST['s']),$all_pool_ids)) {
                // set subpool
                $_SESSION['subpool_id']=trim($_REQUEST['s']);
                redirect("public/".thisdoc());
            } else {
                redirect("public/".thisdoc());
            }
        } else {
            if (count($all_pool_ids)<=1 && $settings['subpool_default_registration_id']) {
                $_SESSION['subpool_id']=$settings['subpool_default_registration_id'];
                redirect("public/".thisdoc());
            } elseif (count($all_pool_ids)==1 && !$settings['subpool_default_registration_id']) {
                $_SESSION['subpool_id']=$all_pool_ids[0];
                redirect ("public/".thisdoc());
            } elseif (count($all_pool_ids)==0 && !$settings['subpool_default_registration_id']) {
                $_SESSION['subpool_id']=1;
                redirect ("public/".thisdoc());
            } else {
                $self_descriptions=array();
                $query="SELECT * from ".table('lang')."
                        WHERE content_type='subjectpool'";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) $self_descriptions[$line['content_name']]=$line[lang('lang')];

                echo '<BR><BR><center>
                        <TABLE class="or_formtable" style="width: 80%"><TR><TD align="center">
                            <B>'.lang('please_choose_subgroup').'</B><BR><BR>';
                foreach ($all_pool_ids as $subpool_id) {
                    echo '<A HREF="'.thisdoc().'?s='.$subpool_id.'">'.$self_descriptions[$subpool_id].'</A><BR><BR>';
                }
                echo '</TD></TR></TABLE><BR><BR><BR></center>';
            }
        }
        $proceed=false;
    }
}

if ($proceed) {
    // check for rules
    if (!isset($_SESSION['rules'])) {
        if ($settings['registration__require_rules_acceptance']=='y' ||
            $settings['registration__require_privacy_policy_acceptance']=='y') {
            if (isset($_REQUEST['accept_rules']) && $_REQUEST['accept_rules']) {
                $_SESSION['rules']=true;
                redirect ("public/".thisdoc());
            } elseif (isset($_REQUEST['notaccept_rules']) && $_REQUEST['notaccept_rules']) {
                unset ($_SESSION['subpool_id']);
                redirect ("public/");
            } else {
                echo '<center><BR><BR>
                      <FORM action='.thisdoc().'>
                      <TABLE class="or_panel" style="width: 80%">';
                if ($settings['registration__require_rules_acceptance']=='y') {
                    echo '<TR><TD>
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.lang('rules').'
                        </TD>
                    </TR></TABLE>
                    </TD></TR>
                        <TR><TD>'.content__get_content("rules").'</TD></TR>';
                }
                if ($settings['registration__require_privacy_policy_acceptance']=='y') {
                    echo '<TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.lang('privacy_policy').'
                        </TD>
                        </TR></TABLE>
                    </TD></TR>
                            <TR><TD>'.content__get_content("privacy_policy").'</TD></TR>';
                }
                echo '<TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.lang('do_you_agree_rules_privacy').'
                        </TD>
                        </TR></TABLE>
                    </TD></TR>
                        <TR><TD align=center>
                            <INPUT class="button" type="submit" name="accept_rules" value="'.lang('yes').'">&nbsp;&nbsp;&nbsp;
                            <INPUT class="button" type="submit" name="notaccept_rules" value="'.lang('no').'">
                        </TD></TR>
                    </TABLE>
                    </FORM>
                    </center>';
            }
        } else {
            $_SESSION['rules']=true;
            redirect ("public/".thisdoc());
        }
        $proceed=false;
    }
}

if ($proceed) {
    echo '<center>';
    $form=true; $errors__dataform=array();
    if (isset($_REQUEST['add'])) {
        $continue=true;

        if (!isset($_REQUEST['captcha']) || !isset($_SESSION['captcha_string']) || $_REQUEST['captcha']!=$_SESSION['captcha_string']) {
            if (!isset($_REQUEST['subscriptions']) || !is_array($_REQUEST['subscriptions'])) $_REQUEST['subscriptions']=array();
            $_REQUEST['subscriptions']=id_array_to_db_string($_REQUEST['subscriptions']);
            $continue=false;
            message(lang('error_wrong_captcha'));
        }

        if ($continue) {
            // checks and errors
            foreach ($_REQUEST as $k=>$v) {
                if(!is_array($v)) $_REQUEST[$k]=trim($v);
            }
            $_REQUEST['subpool_id']=$_SESSION['subpool_id'];
            $errors__dataform=participantform__check_fields($_REQUEST,false);
            $error_count=count($errors__dataform);
            if ($error_count>0) $continue=false;

            $response=participantform__check_unique($_REQUEST,"create");
            if (isset($response['disable_form']) && $response['disable_form']) {
                $continue=false;
                $proceed=false;
                unset ($_SESSION['subpool_id']);
                unset ($_SESSION['rules']);
                unset ($_SESSION['pauthdata']['pw_provided']);
                unset ($_SESSION['pauthdata']['submitted_checked_pw']);
                if($settings['subject_authentication']=='token') {
                    redirect ("public/");
                } else {
                    redirect ("public/participant_login.php");
                }
            } elseif($response['problem']) {
                $continue=false;
            }

            if ($settings['subject_authentication']!='token') {
                if (isset($_SESSION['pauthdata']['pw_provided']) && $_SESSION['pauthdata']['pw_provided'] &&
                    isset($_SESSION['pauthdata']['submitted_checked_pw']) && $_SESSION['pauthdata']['submitted_checked_pw']) {
                        $_REQUEST['password']=$_SESSION['pauthdata']['submitted_checked_pw'];
                } else {
                    $pw_ok=participant__check_password($_REQUEST['password'],$_REQUEST['password2']);
                    if ($pw_ok) {
                        $_SESSION['pauthdata']['pw_provided']=true;
                        $_SESSION['pauthdata']['submitted_checked_pw']=$_REQUEST['password'];
                    } else {
                        $continue=false;
                    }
                }
            }
        }

        if ($continue) {
            $participant=$_REQUEST;
            unset ($_SESSION['pauthdata']['pw_provided']);
            unset ($_SESSION['pauthdata']['submitted_checked_pw']);
            unset ($_SESSION['captcha_string']);
            $new_id=participant__create_participant_id($participant);
            $participant['participant_id']=$new_id['participant_id'];
            $participant['participant_id_crypt']=$new_id['participant_id_crypt'];
            if ($settings['subject_authentication']!='token') {
                $participant['password_crypted']=unix_crypt($participant['password']);
            }
            $participant['confirmation_token']=create_random_token(get_entropy($participant));
            $participant['creation_time']=time();
            $participant['last_profile_update']=$participant['creation_time'];
            $participant['status_id']=0;
            $participant['subpool_id']=$_SESSION['subpool_id'];
            if (!isset($participant['language']) || !$participant['language']) $participant['language']=$settings['public_standard_language'];
            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
            if ($done) {
                log__participant("subscribe",$participant['lname'].', '.$participant['fname']);
                $proceed=false;
                $done=experimentmail__confirmation_mail($participant);
                message(lang('successfully_registered'));
                redirect ("public/");
            } else {
                message(lang('database_error'));
            }
        }
    }
}

if ($proceed) {

    echo '<CENTER>
            <TABLE class="or_formtable" style="width: auto;"><TR><TD>';
    show_message();
    $_REQUEST['subpool_id']=$_SESSION['subpool_id'];

    $extra=''; $pwfields=''; $captcha='';
    if ($settings['subject_authentication']!='token') {
        if (isset($_SESSION['pauthdata']['pw_provided']) && $_SESSION['pauthdata']['pw_provided']) {
                $pwfields.=participant__password_form_fields(false,true);
        } else {
                $pwfields.=participant__password_form_fields(false,false);
        }
    }
    $captcha='<TR><TD>'.lang('captcha_text').'<br><IMG src="captcha.php"><BR>
            <INPUT type="text" name="captcha" size="8" maxlength="8" value="">
            </TD></TR>';
    if ($pwfields || $captcha) $extra='<TABLE width="400px"><TR><TD>&nbsp;</TD></TR>'.
        $pwfields.$captcha.'</TABLE>';
    else $extra='';
    participant__show_form($_REQUEST,lang('submit'),$errors__dataform,false,$extra);
    echo '</TD></TR></TABLE></center>';

}

include("footer.php");
?>
