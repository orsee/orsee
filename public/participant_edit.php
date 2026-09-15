<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="my_data";
$title="edit_participant_data";
$js_modules=array('flatpickr','switchy','intltelinput');
include("header.php");

$active_tab='profile';
if (isset($_REQUEST['mode']) && in_array($_REQUEST['mode'],array('profile','change_pw','unsubscribe'))) {
    $active_tab=$_REQUEST['mode'];
}
if ($settings['subject_authentication']=='token' && $active_tab=='change_pw') {
    $active_tab='profile';
}
$allow_change_pw=($settings['subject_authentication']!='token');
$edit_url='participant_edit.php'.$token_string;

if ($proceed) {
    $form=true;
    $errors__dataform=array();
    if (isset($_REQUEST['add']) && $_REQUEST['add']) {
        $active_tab='profile';
        if (!csrf__validate_request_message()) {
            redirect("public/".$edit_url.'#profile');
        }
        $continue=true;
        $_REQUEST['participant_id']=$participant['participant_id'];
        $_REQUEST['subpool_id']=$participant['subpool_id'];
        if (isset($participant['pending_profile_update_request']) && $participant['pending_profile_update_request']=='y' &&
            isset($participant['profile_update_request_new_pool']) && $participant['profile_update_request_new_pool']) {
            $_REQUEST['subpool_id']=$participant['profile_update_request_new_pool'];
        }

        // checks and errors
        foreach ($_REQUEST as $k=>$v) {
            if (!is_array($v)) {
                $_REQUEST[$k]=trim($v);
            }
        }
        $check_result=participantform__check_fields($_REQUEST,'profile_form_public_edit');
        $errors__dataform=$check_result['errors'];
        $form_input=$check_result['sanitized'];
        $allowed_fields=$check_result['allowed_fields'];
        $error_count=count($errors__dataform);
        if ($error_count>0) {
            $continue=false;
        }

        $response=participantform__check_unique($form_input,"edit",$form_input['participant_id']);
        if ($response['problem']) {
            $continue=false;
        }

        if ($continue) {
            if (isset($participant['pending_profile_update_request']) && $participant['pending_profile_update_request']=='y') {
                $form_input['pending_profile_update_request']='n';
                $form_input['profile_update_request_new_pool']=null;
                message(lang('profile_confirmed').'<BR>');
            }
            $participant=$form_input;

            $participant['last_profile_update']=time();
            $save_allowed_fields=array_values(array_unique(array_merge(
                $allowed_fields,
                array('participant_id','subpool_id','language','pending_profile_update_request','profile_update_request_new_pool','last_profile_update')
            )));
            $participant=array_filter_allowed($participant,$save_allowed_fields);

            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");

            if ($done) {
                message(lang('changes_saved'),'note',null,'toast');
                log__participant("edit",$participant['participant_id']);
                redirect("public/".$edit_url.'#profile');
            } else {
                message(lang('database_error'),'error');
                redirect("public/".$edit_url.'#profile');
            }
        }
    }
    if ((!isset($_REQUEST['add']) || !$_REQUEST['add']) &&
        (!isset($_REQUEST['submit']) || !$_REQUEST['submit']) &&
        !(isset($_REQUEST['doit']) && $_REQUEST['doit'])) {
        $form_input=$participant;
    }
}

if ($proceed) {
    if (isset($participant['pending_profile_update_request']) && $participant['pending_profile_update_request']=='y') {
        message(lang('profile_update_request_message').'<BR>','warning');
        if (isset($participant['profile_update_request_new_pool']) && $participant['profile_update_request_new_pool']) {
            $form_input['subpool_id']=$participant['profile_update_request_new_pool'];
        }
    }
}

if ($proceed && $allow_change_pw) {
    if (isset($_REQUEST['submit']) && $_REQUEST['submit']) {
        $active_tab='change_pw';
        if (!csrf__validate_request_message()) {
            redirect("public/".$edit_url.'#change_pw');
        }

        if (isset($_REQUEST['passold'])) {
            $passold=$_REQUEST['passold'];
        } else {
            $passold="";
        }
        if (isset($_REQUEST['password'])) {
            $password=$_REQUEST['password'];
        } else {
            $password="";
        }
        if (isset($_REQUEST['password2'])) {
            $password2=$_REQUEST['password2'];
        } else {
            $password2="";
        }

        $continue=true;
        if ($continue) {
            if (!$passold) {
                message(lang('error_please_fill_in_all_fields'),'error');
                $continue=false;
            }
        }
        if ($continue) {
            if (!crypt_verify($passold,$participant['password_crypted'])) {
                message(lang('error_old_password_wrong'),'error');
                message(lang('for_security_reasons_we_logged_you_out'),'warning');
                $continue=false;
                participant__logout();
                redirect("public/participant_login.php");
            }
        }
        if ($continue) {
            $continue=participant__check_password($password,$password2);
        }

        if ($continue==false) {
            message(lang('error_password_not_changed'),'error');
            redirect("public/".$edit_url.'#change_pw');
        } else {
            participant__set_password($password,$participant['participant_id']);
            message(lang('password_changed_log_in_again'));
            log__participant("participant_password_change",$participant['participant_id']);
            log__participant("logout",$participant['participant_id']);
            participant__logout();
            redirect("public/participant_login.php?pw=true");
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['doit']) && $_REQUEST['doit']) {
        $active_tab='unsubscribe';
        if (!csrf__validate_request_message()) {
            redirect("public/".$edit_url.'#unsubscribe');
        }
        $default_inactive_status=participant_status__get("is_default_inactive");
        $pars=array(':participant_id'=>$participant_id,':default_inactive_status'=>$default_inactive_status);
        $query="UPDATE ".table('participants')."
                SET status_id= :default_inactive_status,
                deletion_time='".time()."'
                WHERE participant_id= :participant_id";
        $done=or_query($query,$pars);
        log__participant("delete",$participant_id);
        log__participant("logout",$participant_id);
        participant__logout();
        message(lang('removed_from_invitation_list'));
        redirect("public/");
    }
}

if ($proceed) {
    // form
    if ($form) {
        if (isset($_REQUEST['add']) && $_REQUEST['add'] && isset($_SESSION['message_queue']) && is_array($_SESSION['message_queue'])) {
            foreach ($_SESSION['message_queue'] as $idx=>$msg) {
                $style=(isset($msg['style']) ? (string)$msg['style'] : 'note');
                if ($style==='note' || $style==='') {
                    $_SESSION['message_queue'][$idx]['style']='error';
                }
            }
        }

        $tabs=array('profile','unsubscribe');
        if ($allow_change_pw) {
            array_splice($tabs,1,0,'change_pw');
        }
        $participant_password_dir=($settings['force_ltr_participant_login_password']==='y' ? ' dir="ltr"' : '');
        echo '<div id="orsee-public-mobile-screen" class="orsee-public-screen has-rail">';
        echo '<div class="orsee-public-inline-message-buffer">';
        show_message();
        echo '</div>';
        echo '  <div class="orsee-public-tabpages">
                    <div id="orsee-public-edit-tabtrack" class="orsee-public-tabtrack">
                        <div id="orsee-public-edit-profile" class="orsee-public-tabpage">
                            <div class="orsee-public-profile-formwrap">
                                <form id="orsee-public-unsubscribe-form" action="'.$edit_url.'" method="POST" class="orsee-public-profile-form">
                                    '.csrf__field().'
                                    <div class="orsee-form-shell orsee-public-profile-shell">';
        participant__show_inner_form($form_input,$errors__dataform,'profile_form_public_edit');
        echo '                      <div class="orsee-public-profile-actions">
                                        <button class="button orsee-public-btn" name="add" type="submit" value="true">'.lang('save').'</button>
                                    </div>
                                  </div>
                                </form>
                            </div>
                        </div>';

        if ($allow_change_pw) {
            echo '      <div id="orsee-public-edit-change_pw" class="orsee-public-tabpage">
                            <div class="orsee-public-profile-formwrap">
                                <form id="orsee-public-change-pw-form" action="'.$edit_url.'" method="POST" class="orsee-public-profile-form">
                                    '.csrf__field().'
                                    <div class="orsee-form-shell orsee-public-profile-shell">
                                        <div class="field">
                                            <label class="label">'.lang('old_password').'</label>
                                            <div class="control">
                                                <input type="password" class="input is-primary orsee-input" name="passold"'.$participant_password_dir.' maxlength="30">
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="label">'.lang('new_password').'</label>
                                            <div class="control">
                                                <input type="password" class="input is-primary orsee-input" name="password"'.$participant_password_dir.' maxlength="40">
                                            </div>
                                            <p class="help">'.lang('participant_password_note').'</p>
                                        </div>
                                        <div class="field">
                                            <label class="label">'.lang('repeat_new_password').'</label>
                                            <div class="control">
                                                <input type="password" class="input is-primary orsee-input" name="password2"'.$participant_password_dir.' maxlength="40">
                                            </div>
                                        </div>
                                        <div class="orsee-public-profile-actions">
                                        <button class="button orsee-public-btn" name="submit" type="submit" value="true">'.lang('change').'</button>
                                    </div>
                                    </div>
                                </form>
                            </div>
                        </div>';
        }

        echo '          <div id="orsee-public-edit-unsubscribe" class="orsee-public-tabpage">
                            <div class="orsee-public-profile-formwrap">
                                <form id="orsee-public-unsubscribe-form" action="'.$edit_url.'" method="POST" class="orsee-public-profile-form">                                    '.csrf__field().'
                                    <div class="orsee-form-shell orsee-public-profile-shell">
                                        <div class="field">
                                            <div class="control">'.lang('do_you_really_want_to_unsubscribe').'</div>
                                        </div>
                                        <div class="orsee-public-profile-actions">
                                        <button class="button orsee-public-btn orsee-public-btn--delete orsee-btn--delete" name="doit" type="submit" value="true" data-orsee-confirm-submit="1" data-orsee-confirm-form="orsee-public-unsubscribe-form" data-confirm="'.lang('do_you_really_want_to_unsubscribe').'">'.lang('yes_i_want').'</button>
                                        <button class="button orsee-public-btn" type="button" data-orsee-edit-tab="profile">'.lang('no_sorry').'</button>
                                    </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <nav id="orsee-public-mobile-tabbar">
                    <div class="orsee-public-tabbar-inner">';
        echo '          <a href="#" id="orsee-public-edit-tabbtn-profile" class="orsee-public-tabitem">
                            <span class="orsee-public-tab-icon-wrap"><i class="fa fa-user" aria-hidden="true"></i></span>
                            <span class="orsee-public-tab-label">'.lang('my_data').'</span>
                        </a>';
        if ($allow_change_pw) {
            echo '      <a href="#" id="orsee-public-edit-tabbtn-change_pw" class="orsee-public-tabitem">
                            <span class="orsee-public-tab-icon-wrap"><i class="fa fa-key" aria-hidden="true"></i></span>
                            <span class="orsee-public-tab-label">'.lang('change_my_password').'</span>
                        </a>';
        }
        echo '          <a href="#" id="orsee-public-edit-tabbtn-unsubscribe" class="orsee-public-tabitem">
                            <span class="orsee-public-tab-icon-wrap"><i class="fa fa-minus-circle" aria-hidden="true"></i></span>
                            <span class="orsee-public-tab-label">'.lang('unsubscribe').'</span>
                        </a>
                        <a href="participant_show.php'.$token_string.'" class="orsee-public-tabitem orsee-public-tabitem--profile">
                            <span class="orsee-public-tab-icon-wrap"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                            <span class="orsee-public-tab-label">'.lang('mobile_enrolments').'</span>
                        </a>
                    </div>
                </nav>
                '.participant__public_confirm_modal().'
            </div>';

        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var tabs = '.json_encode($tabs).';
                var track = document.getElementById("orsee-public-edit-tabtrack");
                var activeTab = "'.$active_tab.'";
                if (tabs.indexOf(activeTab) < 0) activeTab = "profile";
                var currentIndex = 0;
                var slideSign = (document.documentElement && document.documentElement.dir === "rtl") ? 1 : -1;

                function syncTrackHeight() {
                    if (!track) return;
                    var pages = track.querySelectorAll(".orsee-public-tabpage");
                    if (!pages[currentIndex]) return;
                    track.style.height = pages[currentIndex].offsetHeight + "px";
                }

                function scrollToTop() {
                    window.scrollTo(0, 0);
                    if (document.documentElement) document.documentElement.scrollTop = 0;
                    if (document.body) document.body.scrollTop = 0;
                }

                function setActiveButton(name) {
                    tabs.forEach(function (t) {
                        var btn = document.getElementById("orsee-public-edit-tabbtn-" + t);
                        if (!btn) return;
                        if (t === name) btn.classList.add("is-active");
                        else btn.classList.remove("is-active");
                    });
                }

                function openTab(name, pushHistory) {
                    var idx = tabs.indexOf(name);
                    if (idx < 0 || !track) return;
                    currentIndex = idx;
                    track.style.transform = "translateX(" + (slideSign * idx * 100) + "%)";
                    syncTrackHeight();
                    window.setTimeout(syncTrackHeight, 60);
                    scrollToTop();
                    activeTab = name;
                    setActiveButton(name);
                    if (window.history && window.history.pushState && pushHistory) {
                        var urlNoHash = window.location.href.split("#")[0];
                        window.history.pushState(null, "", urlNoHash + "#" + name);
                    } else if (window.history && window.history.replaceState) {
                        var urlNoHash = window.location.href.split("#")[0];
                        window.history.replaceState(null, "", urlNoHash + "#" + name);
                    } else {
                        window.location.hash = name;
                    }
                }

                tabs.forEach(function (t) {
                    var btn = document.getElementById("orsee-public-edit-tabbtn-" + t);
                    if (!btn) return;
                    btn.addEventListener("click", function (ev) {
                        ev.preventDefault();
                        if (t === activeTab) return;
                        openTab(t, true);
                    });
                });

                document.querySelectorAll("[data-orsee-edit-tab]").forEach(function (btn) {
                    btn.addEventListener("click", function (ev) {
                        ev.preventDefault();
                        var target = this.getAttribute("data-orsee-edit-tab");
                        if (!target || target === activeTab) return;
                        openTab(target, true);
                    });
                });

                if (window.ResizeObserver && track) {
                    var ro = new ResizeObserver(function () {
                        syncTrackHeight();
                    });
                    track.querySelectorAll(".orsee-public-tabpage").forEach(function (p) { ro.observe(p); });
                }
                window.addEventListener("resize", syncTrackHeight);
                window.addEventListener("load", syncTrackHeight);

                var startTab = activeTab;
                if (window.location.hash) {
                    var hashTab = window.location.hash.replace(/^#/, "");
                    if (tabs.indexOf(hashTab) >= 0) startTab = hashTab;
                }
                openTab(startTab, false);

                window.addEventListener("popstate", function () {
                    var hashTab = window.location.hash ? window.location.hash.replace(/^#/, "") : "profile";
                    if (tabs.indexOf(hashTab) < 0) hashTab = "profile";
                    openTab(hashTab, false);
                });
                window.addEventListener("hashchange", function () {
                    var hashTab = window.location.hash ? window.location.hash.replace(/^#/, "") : "profile";
                    if (tabs.indexOf(hashTab) < 0) hashTab = "profile";
                    openTab(hashTab, false);
                });
            });
        </script>';
        echo javascript__confirm_modal_script();
    }
}
include("footer.php");

?>
