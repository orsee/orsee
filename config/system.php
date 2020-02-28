<?php
// part of orsee. see orsee.org
// THIS FILE WILL CHANGE FROM VERSION TO VERSION. BETTER NOT EDIT.
$system__version="3.1.0";
$system__database_version=2020022800;

// implemented experiment types
$system__experiment_types=array('laboratory','online-survey','internet');

// from 3.0 on we will always use UTF-8
$settings__charset="UTF-8";

// build experiment system root url
$settings__root_url=$settings__server_protocol.$settings__server_url.$settings__root_directory;

// define administration rights
$system__admin_rights=array(
"admin_delete:delete an administrator account:admin_edit",
"admin_edit:create/edit administrator accounts",
"admin_type_delete:delete admin user types:admin_type_edit",
"admin_type_edit:add/edit admin user types",
"calendar_export_all:export full calendar in ics format",
"calendar_export_my:export own experiment calendar in ics format",
"calendar_view_my:view own experiment calendar",
"calendar_view_all:view full experiment calendar",
"datetime_format_add:add new datetime format string - dev!:datetime_format_edit",
"datetime_format_delete:delete datetime format string - dev!:datetime_format_edit",
"datetime_format_edit:edit datetime format string",
"default_text_add:add new default text item - dev!:default_text_edit",
"default_text_delete:delete default text item - dev!:default_text_edit",
"default_text_edit:edit system's default texts",
"emails_change_all:change properties of all emails in system  - if email module enabled:emails_read_all",
"emails_change_assigned:change properties of emails assigned to me  - if email module enabled:emails_read_assigned",
"emails_change_experiments:change properties of emails assigned to my experiments  - if email module enabled:emails_read_experiments",
"emails_delete_all:move to trash all emails in system - if email module enabled:emails_read_all",
"emails_delete_assigned:move to trash emails assigned to me  - if email module enabled:emails_read_assigned",
"emails_delete_experiments:move to trash emails assigned to my experiments  - if email module enabled:emails_read_experiments",
"emails_mailbox_add:add new special mailbox  - if email module enabled:emails_mailbox_edit",
"emails_mailbox_delete:delete special mailbox  - if email module enabled:emails_mailbox_edit",
"emails_mailbox_edit:edit special mailboxes - if email module enabled",
"emails_note_all:add note to all emails in system  - if email module enabled:emails_read_all",
"emails_note_assigned:add note to emails assigned to me  - if email module enabled:emails_read_assigned",
"emails_note_experiments:add note to emails assigned to my experiments  - if email module enabled:emails_read_experiments",
"emails_read_all:read all emails in system  - if email module enabled",
"emails_read_assigned:read emails assigned to me  - if email module enabled",
"emails_read_experiments:read emails assigned to my experiments  - if email module enabled",
"emails_reply_all:reply to all emails in system  - if email module enabled:emails_read_all",
"emails_reply_assigned:reply to emails assigned to me  - if email module enabled:emails_read_assigned",
"emails_reply_experiments:reply to emails assigned to my experiments  - if email module enabled:emails_read_experiments",
"emails_trash_view:view emails in trash - if email module enabled",
"emails_trash_empty:permanently delete all emails in trash - if email module enabled",
"events_category_add:add category for events",
"events_category_delete:delete category for events",
"events_category_edit:edit list of categories for events",
"events_delete:delete lab reservation:events_edit",
"events_edit:create/change lab reservation",
"experiment_assign_participants:assign subjects to experiment/delete assignments:experiment_show",
"experiment_assign_query_permanent_activate:make assignment query permanently applying to newly registering particiapnts:experiment_assign_participants",
"experiment_assign_query_permanent_deactivate:deactivate permanent query:experiment_assign_participants",
"experiment_change_sender_address:change the email address from which invitations are sent - if enabled:experiment_show,experiment_edit",
"experiment_customize_session_reminder:customize session reminder email of a particular experiment:experiment_show",
"experiment_customize_enrolment_confirmation:customize enrolment confirmation email of a particular experiment:experiment_show",
"experiment_delete:delete experiment (with sessions, assignments, etc.):experiment_show,experiment_edit",
"experiment_edit:create experiment/edit experiment's basic settings:experiment_show",
"experiment_edit_add_public_experiment_note:add a note that will be displayed next to experiment details on enrolment page:experiment_show,experiment_edit",
"experiment_edit_ethics_approval_details:change the details for ethics approval of an experiment - if module enabled:experiment_show,experiment_edit",
"experiment_edit_participants:edit session's participants list (show-ups etc.):experiment_show,experiment_show_participants",
"experiment_invitation_edit:edit invitation texts:experiment_show",
"experiment_invite_participants:send out invitations for an experiment:experiment_show,experiment_invitation_edit",
"experiment_restriction_override:override experiment access restriction",
"experiment_recruitment_report_show:view recruitment report for experiment:experiment_show",
"experiment_show:see an experiment's main page",
"experiment_show_participants:see participant list for an experiment:experiment_show",
"experimentclass_add:add class of experiments:experimentclass_edit",
"experimentclass_delete:delete experiment class:experimentclass_edit",
"experimentclass_edit:edit experiment class",
"experimenttype_add:add external experiment type:experimenttype_edit",
"experimenttype_delete:delete external experiment type:experimenttype_edit",
"experimenttype_edit:edit external experiment types",
"faq_add:add FAQ:faq_edit",
"faq_delete:delete FAQ:faq_edit",
"faq_edit:edit FAQ",
"file_delete_experiment_all:delete files uploaded for any experiment:file_view_experiment_all",
"file_delete_experiment_my:delete files uploaded for own experiments:file_view_experiment_my",
"file_delete_general:delete files uploaded in general section:file_view_general",
"file_download_experiment_all:download experiment-related files of all experiments:file_view_experiment_all",
"file_download_experiment_my:download experiment-related files of own experiments:file_view_experiment_my",
"file_download_general:download files in the general section",
"file_edit_experiment_all:delete files uploaded for any experiment:file_view_experiment_all",
"file_edit_experiment_my:delete files uploaded for own experiments:file_view_experiment_my",
"file_edit_general:delete files uploaded in general section:file_view_general",
"file_upload_category_add:add category for file uploads",
"file_upload_category_delete:delete category for file uploads",
"file_upload_category_edit:edit list of categories for file uploads",
"file_upload_experiment_all:upload files for all experiments:experiment_show",
"file_upload_experiment_my:upload files for own experiments:experiment_show",
"file_upload_general:upload files in general section:file_download_general",
"file_view_experiment_all:view experiment-related files of all experiments",
"file_view_experiment_my:view experiment-related files of own experiments",
"file_view_general:view files in the general section",
"import_data:import experiment/participant data from old ORSEE version",
"laboratory_add:add new laboratory:laboratory_edit",
"laboratory_delete:delete laboratory:laboratory_edit",
"laboratory_edit:edit laboratory settings",
"lang_avail_edit:edit language's public avability settings",
"lang_lang_add:install language:lang_lang_edit",
"lang_lang_delete:uninstall language:lang_lang_edit",
"lang_lang_edit:edit specific language settings",
"lang_lang_export:export language to orsee language file:lang_lang_edit",
"lang_lang_import: import language from orsee langauge file:lang_lang_edit",
"lang_symbol_add:add new language symbol- dev!:lang_symbol_edit",
"lang_symbol_delete:delete language symbol - dev!:lang_symbol_edit",
"lang_symbol_edit:edit language symbols (words, expressions, etc.)",
"log_file_experimenter_actions_delete:delete log file of experimenter actions:log_file_experimenter_actions_show",
"log_file_experimenter_actions_show:see log file of experimenter actions",
"log_file_participant_actions_delete:delete log file of participant actions:log_file_participant_actions_show",
"log_file_participant_actions_show:view log file for participant actions",
"log_file_regular_tasks_delete:delete log file of regular system tasks:log_file_regular_tasks_show",
"log_file_regular_tasks_show:view log file of regular tasks",
"login:login into administration area",
"mail_add:add new default email text - dev!:mail_edit",
"mail_delete:delete default email text - dev!:mail_edit",
"mail_edit:edit default email texts",
"mailqueue_edit_all:delete emails from overall mail queue:mailqueue_show_all",
"mailqueue_edit_experiment:delete emails from an experiment's mail queue:mailqueue_show_experiment",
"mailqueue_show_all:view overall mail queue",
"mailqueue_show_experiment:view a particular experiment's mail queue",
"participantstatus_add:add new participant status:participantstatus_edit",
"participantstatus_delete:delete a participant status:participantstatus_edit",
"participantstatus_edit:edit participant status settings",
"participants_bulk_mail:send bulk email to participants:participants_show",
"participants_bulk_participant_status:set the participant status of a set of participants:participants_show",
"participants_bulk_profile_update:request profile update from a set of participants:participants_show",
"participants_duplicates:search for duplicates and specify primary accounts",
"participants_edit:create participant/change participant profile:participants_show",
"participants_change_status:change the status of a participant from active to deleted/excluded:participants_edit",
"participants_bulk_anonymization:anonymize participant profiles:participants_edit",
"participants_show:search participants, view search results",
"participants_unconfirmed_edit:view and delete unconfirmed participant entries",
"participationstatus_add:add new experiment/session participation status:participationstatus_edit",
"participationstatus_delete:delete a experiment/session participation status:participationstatus_edit",
"participationstatus_edit:edit experiment/session participation status settings",
"payments_budget_add:add a budget for participant payments - if module enabled:payments_budget_edit",
"payments_budget_delete:delete a budget for participant payments  - if module enabled:payments_budget_edit",
"payments_budget_edit:edit a budget for participant payments - if module enabled",
"payments_budget_view_my:view own budgets and payment reports- if module enabled",
"payments_budget_view_all:view all budgets and payment reports- if module enabled",
"payments_edit:edit participant payments for a session - if module enabled:payments_view",
"payments_type_add:add new paymentcurrency - if module enabled:payments_type_edit",
"payments_type_delete:delete a payment currency - if module enabled:payments_type_edit",
"payments_type_edit:edit a payment currency - if module enabled",
"payments_view:view payments to participants - if module enabled",
"pform_config_field_add:add a participant mysql column:pform_config_field_configure",
"pform_config_field_configure:configure participant profile form fields",
"pform_config_field_delete:delete a participant profile form fields and mysql column:pform_config_field_configure",
"pform_default_query_edit:set and change default queries for various query types",
"pform_lang_field_add:add item for a select_lang/radioline_lang field in participant profile:pform_lang_field_edit",
"pform_lang_field_delete:delete item of a select_lang/radioline_lang field in participant profile:pform_lang_field_edit",
"pform_lang_field_edit:edit item of a select_lang/radioline_lang field in participant profile",
"pform_results_lists_edit: edit the columns that appear in results tables after queries",
"pform_anonymization_fields_edit: edit the fields to anonymize upon participant profile bulk anonymization",
"pform_saved_queries_delete:delete saved queries for searches into all or active participants:pform_saved_queries_view",
"pform_saved_queries_view:view saved queries for searches into all or active participants",
"pform_templates_edit: edit the templates for the participant profile form",
"public_content_add:add new public content item - dev!:public_content_edit",
"public_content_delete:delete public content item - dev!:public_content_edit",
"public_content_edit:edit public content pages",
"regular_tasks_add:add new regular task - dev!:regular_tasks_show,regular_tasks_edit",
"regular_tasks_edit:edit regular tasks:regular_tasks_show",
"regular_tasks_run:run regular task manually:regular_tasks_show",
"regular_tasks_show:show list of system's regular tasks",
"session_edit:create session/change session settings:experiment_show",
"session_edit_add_public_session_note:add a note that will be displayed next to session details on enrolment page:experiment_show,session_edit",
"session_empty_delete:delete a session that hasn't any signups:experiment_show,session_edit",
"session_nonempty_delete:delete a session independent of participant signups:experiment_show,session_edit",
"session_send_reminder:send session reminder manually:experiment_show,experiment_show_participants",
"settings_edit:edit general settings and defaults",
"settings_edit_colors:edit color values for ORSEE styles",
"settings_view:view general settings and defaults",
"settings_view_colors:view color values for ORSEE styles",
"settings_option_add:add general option item - dev!",
"statistics_participants_show:see participant statistics",
"statistics_server_usage_show:see web server statistics",
"statistics_system_show:see system statistics",
"subjectpool_add:add new sub-subject pool:subjectpool_edit",
"subjectpool_delete:delete sub-subject pool:subjectpool_edit",
"subjectpool_edit:edit sub-subjectpool settings"
        );


// define options

/////////////////////////////////
///      GENERAL OPTIONS      ///
/////////////////////////////////
$system__options_general=array();

$system__options_general[]=array('type'=>'comment',
            'text'=>'ORSEE version: '.$system__version);

$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Languages');

$system__options_general[]=array(
'option_name'=>'admin_standard_language',
'option_text'=>'Administrator Standard Language',
'type'=>'plain',
'field'=>'func:lang__select_lang("options[admin_standard_language]",
                    $options["admin_standard_language"],"all")'
);

$system__options_general[]=array(
'option_name'=>'public_standard_language',
'option_text'=>'Public Standard Language',
'type'=>'plain',
'field'=>'func:lang__select_lang("options[public_standard_language]",
                    $options["public_standard_language"],"part")'
);


$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Email configuration');

$system__options_general[]=array(
'option_name'=>'support_mail',
'option_text'=>'System support email address (used as sender for most emails)?',
'type'=>'textline',
'default_value'=>'change_this_address@orsee.org',
'size'=>'30',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'enable_editing_of_experiment_sender_email',
'option_text'=>'Allow experimenters to change the sender email address for their experiments?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'email_sendmail_type',
'option_text'=>'Type of sending emails (see Manual,<BR>
                try "direct", and if it doesn\'t work, try "indirect")?',
'type'=>'select_list',
'option_values'=>'direct,indirect',
'option_values_lang'=>'direct,indirect',
'default_value'=>'direct',
'include_none_option'=>'n'
);

$system__options_general[]=array(
'option_name'=>'email_sendmail_path',
'option_text'=>'If indirect: path to sendmail program/wrapper?',
'type'=>'textline',
'default_value'=>'/usr/sbin/sendmail',
'size'=>'30',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'mail_queue_number_send_per_time',
'option_text'=>'Number of mails send from mail queue on each processing:',
'type'=>'textline',
'default_value'=>'50',
'size'=>'3',
'maxlength'=>'6',
);


$system__options_general[]=array(
'option_name'=>'bcc_all_outgoing_emails',
'option_text'=>'Bcc all outgoing emails to an email address?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'bcc_all_outgoing_emails__address',
'option_text'=>'Address which to bcc',
'type'=>'textline',
'default_value'=>'',
'size'=>'30',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'enable_email_module',
'option_text'=>'Enable module to receive emails within ORSEE?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'email_module_delete_emails_from_server',
'option_text'=>'Email module: Delete emails from server after retrieval (strong recommendaion: yes)?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'email_module_allow_assign_emails',
'option_text'=>'Email module: Allow to assign emails to experimenters?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Display settings');

$system__options_general[]=array(
'option_name'=>'stop_public_site',
'option_text'=>'Stop public site (might be useful if installing/upgrading)?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'orsee_public_style',
'option_text'=>'Style for Public Area',
'type'=>'select_list',
'option_values'=>'func:options__get_styles()',
'option_values_lang'=>'func:options__get_styles()',
'default_value'=>'orsee',
'include_none_option'=>'n'
);


$system__options_general[]=array(
'option_name'=>'orsee_admin_style',
'option_text'=>'Style for Administration Area',
'type'=>'select_list',
'option_values'=>'func:options__get_styles()',
'option_values_lang'=>'func:options__get_styles()',
'default_value'=>'orsee',
'include_none_option'=>'n'
);

$system__options_general[]=array(
'option_name'=>'default_area',
'option_text'=>'First part of each page\'s title tag (and footer title for mobile pages)?',
'type'=>'textline',
'default_value'=>'ORSEE',
'size'=>'20',
'maxlength'=>'30',
);


$system__options_general[]=array(
'option_name'=>'public_calendar_hide_exp_name',
'option_text'=>'Participant calendar: Hide public experiment name?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'hide_planned_sessions_in_public_calendar',
'option_text'=>'Participant calendar: Hide "planned" sessions?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'show_public_rules_page',
'option_text'=>'Should ORSEE show the rules page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'show_public_privacy_policy',
'option_text'=>'Should ORSEE show the privacy policy page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'show_public_calendar',
'option_text'=>'Should ORSEE show the calendar page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'show_public_faqs',
'option_text'=>'Should ORSEE show the FAQ page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'show_public_legal_notice',
'option_text'=>'Should ORSEE show the legal notice/impressum page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'show_public_contact',
'option_text'=>'Should ORSEE show the contact page in the public section?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);


$system__options_general[]=array(
'option_name'=>'include_sign_up_until_in_invitation',
'option_text'=>'Include "sign up until ..." in session list in invitation email?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'include_sign_up_until_on_enrolment_page',
'option_text'=>'Include "sign up until ..." in session list on enrolment webpage?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'include_weekday_in_session_name',
'option_text'=>'Include weekday in session date whereever displayed?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);


$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Extra features');

$system__options_general[]=array(
'option_name'=>'enable_ethics_approval_module',
'option_text'=>'Enable ethics approval module?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'enable_payment_module',
'option_text'=>'Enable payment tracking module?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);



$system__options_general[]=array(
'option_name'=>'enable_rules_signed_tracking',
'option_text'=>'Enable the "rules" checkbox in participant profiles and session participant lists?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'enable_event_participant_numbers',
'option_text'=>'Allow to add participant numbers to non-ORSEE-experimental laboratory bookings?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array('type'=>'line');


$system__options_general[]=array('type'=>'comment',
            'text'=>'Participant system registration settings');

$system__options_general[]=array(
'option_name'=>'subpool_default_registration_id',
'option_text'=>'Default registration subject pool?',
'type'=>'plain',
'field'=>'func:subpools__select_field("options[subpool_default_registration_id]",$options["subpool_default_registration_id"])'
);

$system__options_general[]=array(
'option_name'=>'registration__require_rules_acceptance',
'option_text'=>'Do subjects have to accept the lab rules when enroling?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'registration__require_privacy_policy_acceptance',
'option_text'=>'Do subjects have to accept the privacy policy when enroling?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Experiment enrolment settings');

$system__options_general[]=array(
'option_name'=>'enable_enrolment_only_on_invite',
'option_text'=>'Eligible assigned subjects can only enrol after having received an invitation email?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);


$system__options_general[]=array(
'option_name'=>'enable_mobile_pages',
'option_text'=>'Enable special enrolment page for mobile devices?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);


$system__options_general[]=array(
'option_name'=>'allow_public_experiment_note',
'option_text'=>'Allow experimenters to add public note to experiments (displayed on participant enrolment page)?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'allow_public_session_note',
'option_text'=>'Allow experimenters to add public note to sessions (displayed on participant enrolment page)?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'enable_session_reminder_customization',
'option_text'=>'Allow experimenters to customize session reminder emails for their experiments?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'enable_enrolment_confirmation_customization',
'option_text'=>'Allow experimenters to customize enrolment confirmation emails for their experiments?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'allow_permanent_queries',
'option_text'=>'Allow experimenters to apply "permanent queries" to new subject pool members?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'permanent_queries_invite',
'option_text'=>'When new subjects are assigned through a permanent query, also send an invitation email?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'allow_subject_cancellation',
'option_text'=>'Allow subjects to cancel their session enrolment?',
'type'=>'select_yesno_switchy',
'default_value'=>'n',
'include_none_option'=>'y'
);

$system__options_general[]=array(
'option_name'=>'subject_cancellation_hours_before_start',
'option_text'=>'If yes: Allow cancellation until how many hours before session start?',
'type'=>'textline',
'default_value'=>'6',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_general[]=array(
'option_name'=>'subject_cancellation_participation_status',
'option_text'=>'If yes: Participation status to be assigned to subjects who canceled their session enrollment',
'type'=>'plain',
'field'=>'func:expregister__participation_status_select_field("options[subject_cancellation_participation_status]",
            $options["subject_cancellation_participation_status"],array(),false)',
'default_value'=>'0'
);


$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Automated noshow warnings and exclusions');

$system__options_general[]=array(
'option_name'=>'send_noshow_warnings',
'option_text'=>'Send warning email on no-show?',
'type'=>'select_yesno_switchy',
'default_value'=>'y'
);

$system__options_general[]=array(
'option_name'=>'automatic_exclusion_noshows',
'option_text'=>'Exclusion Policy: Max. Number of No-Shows',
'type'=>'textline',
'default_value'=>'2',
'size'=>'2',
'maxlength'=>'3',
);

$system__options_general[]=array(
'option_name'=>'automatic_exclusion',
'option_text'=>'Automatically exclude participants after max no-shows?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);


$system__options_general[]=array(
'option_name'=>'automatic_exclusion_to_participant_status',
'option_text'=>'Participant status to be assigned to excluded subjects',
'type'=>'plain',
'field'=>'func:participant_status__select_field("options[automatic_exclusion_to_participant_status]",
            $options["automatic_exclusion_to_participant_status"],array("0"))'
);



$system__options_general[]=array(
'option_name'=>'automatic_exclusion_inform',
'option_text'=>'Inform excluded participants about automatic exclusion?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'restrict_noshow_warnings_to_date',
'option_text'=>'Restrict calculation of no-shows to sessions after a certain date?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'restrict_noshow_warnings_date',
'option_text'=>'If yes, use this date:',
'type'=>'date',
'default_value'=>'0'
);

$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Admin authentication related settings');

$system__options_general[]=array(
'option_name'=>'allow_experiment_restriction',
'option_text'=>'Allow restriction of experiment page access to experimenters?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'default_experiment_restriction',
'option_text'=>'If restriction enabled: Should a new experiment be restricted by default?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'admin_password_regexp',
'option_text'=>'Regular expression for admin passwords (default: PW between 8 and 20 characters long,
                 at least one lower-case letter (a-z), one upper-case (A-Z), one digit (0-9)).
                 For description of rule to users, please edit language item "admin_password_strength_requirements".',
'type'=>'textline',
'default_value'=>'^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,20}$',
'size'=>'50',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'admin_password_change_require_different',
'option_text'=>'Require new password to be different from old password?',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array(
'option_name'=>'max_number_of_failed_logins_before_lockout',
'option_text'=>'Maximal number of failed admin logins before admin account is locked?',
'type'=>'textline',
'default_value'=>'3',
'size'=>'5',
'maxlength'=>'5',
);

$system__options_general[]=array(
'option_name'=>'lockout_period_minutes_after_failed_logins',
'option_text'=>'Number of minutes to lock account until another login attempt is allowed?',
'type'=>'textline',
'default_value'=>'30',
'size'=>'5',
'maxlength'=>'5',
);

$system__options_general[]=array(
'option_name'=>'disable_admin_login_js',
'option_text'=>'Disable auto-submit on admin login form? (May interfere with some browser autofill functions.)',
'type'=>'select_yesno_switchy',
'default_value'=>'n'
);

$system__options_general[]=array('type'=>'line');

$system__options_general[]=array('type'=>'comment',
            'text'=>'Subject security and privacy related settings');


$system__options_general[]=array(
'option_name'=>'subject_authentication',
'option_text'=>'How should subjects authenticate with the system?',
'type'=>'select_list',
'option_values'=>'token,migration,username_password',
'option_values_lang'=>'per_token,migration_token_to_username_password,username_password',
'default_value'=>'token',
'include_none_option'=>'n'
);

$system__options_general[]=array(
'option_name'=>'participant_password_regexp',
'option_text'=>'Regular expression for participant passwords (default: PW between 8 and 20 characters long,
                 at least one lower-case letter (a-z), one upper-case (A-Z), one digit (0-9)).
                 For description fo rule to users, please edit language item "participant_password_note".',
'type'=>'textline',
'default_value'=>'^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,20}$',
'size'=>'50',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'participant_failed_logins_before_lockout',
'option_text'=>'Maximal number of failed participant logins before profile is locked?',
'type'=>'textline',
'default_value'=>'3',
'size'=>'5',
'maxlength'=>'5',
);

$system__options_general[]=array(
'option_name'=>'participant_lockout_minutes',
'option_text'=>'Number of minutes to lock participant account until another login attempt is allowed?',
'type'=>'textline',
'default_value'=>'30',
'size'=>'5',
'maxlength'=>'5',
);

$system__options_general[]=array('type'=>'line');


$system__options_general[]=array('type'=>'comment',
            'text'=>'Diverse settings');

$system__options_general[]=array(
'option_name'=>'http_log_file_location',
'option_text'=>'Path to server log file (access.log)?',
'type'=>'textline',
'default_value'=>'/var/log/apache2/access_log',
'size'=>'30',
'maxlength'=>'200',
);

$system__options_general[]=array(
'option_name'=>'upload_max_size',
'option_text'=>'Upload max size in bytes?',
'type'=>'textline',
'default_value'=>'500000',
'size'=>'10',
'maxlength'=>'20',
);

$system__options_general[]=array(
'option_name'=>'emailed_calendar_included_months',
'option_text'=>'Emailed PDF Calendar: Number of months included?',
'type'=>'select_numbers',
'default_value'=>'2',
'value_begin'=>'1',
'value_end'=>'12',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_general[]=array(
'option_name'=>'calendar_export_months_back',
'option_text'=>'When exporting ORSEE calendar: How many months should we go back?',
'type'=>'select_numbers',
'default_value'=>'2',
'value_begin'=>'1',
'value_end'=>'24',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_general[]=array(
'option_name'=>'calendar_export_months_ahead',
'option_text'=>'When exporting ORSEE calendar: How many months should we go into the future?',
'type'=>'select_numbers',
'default_value'=>'6',
'value_begin'=>'1',
'value_end'=>'36',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

/////////////////////////////////
///      DEFAULT VALUES       ///
/////////////////////////////////
$system__options_defaults=array();

$system__options_defaults[]=array(
'option_name'=>'default_admin_type',
'option_text'=>'Default administrator type?',
'type'=>'plain',
'field'=>'func:admin__select_admin_type("options[default_admin_type]",$options["default_admin_type"])'
);


$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'stats_logs_results_per_page',
'option_text'=>'Log files: entries per page?',
'type'=>'select_numbers',
'default_value'=>'100',
'value_begin'=>'50',
'value_end'=>'500',
'value_step'=>'50',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'stats_months_backward',
'option_text'=>'Participant statistics: How many months should we go back when tracking over time?',
'type'=>'select_numbers',
'default_value'=>'18',
'value_begin'=>'6',
'value_end'=>'60',
'value_step'=>'6',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'stats_report_rows_limit',
'option_text'=>'Recruitment report: Maximum number of rows per report table',
'type'=>'textline',
'default_value'=>'10',
'size'=>'5',
'maxlength'=>'5'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'calendar_pdf_title_fontsize',
'option_text'=>'PDF Calendar: title font size?',
'type'=>'select_numbers',
'default_value'=>'12',
'value_begin'=>'6',
'value_end'=>'25',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'calendar_pdf_table_fontsize',
'option_text'=>'PDF Calendar: table entry font size?',
'type'=>'select_numbers',
'default_value'=>'8',
'value_begin'=>'6',
'value_end'=>'25',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'participant_list_pdf_title_fontsize',
'option_text'=>'PDF Participant list: title font size?',
'type'=>'select_numbers',
'default_value'=>'12',
'value_begin'=>'6',
'value_end'=>'25',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'participant_list_pdf_table_fontsize',
'option_text'=>'PDF Participant list: table entry font size?',
'type'=>'select_numbers',
'default_value'=>'10',
'value_begin'=>'6',
'value_end'=>'25',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'multipicker_left_or_right',
'option_text'=>'Multipickers on left or ritgh side of multi-select fields?',
'type'=>'select_list',
'option_values'=>'left,right',
'option_values_lang'=>'left,right',
'default_value'=>'left',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'experimenter_list_nr_columns',
'option_text'=>'Multipicker list of experimenters: max number of columns?',
'type'=>'select_numbers',
'default_value'=>'3',
'value_begin'=>'1',
'value_end'=>'6',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'query_experiment_classes_list_nr_columns',
'option_text'=>'Participant query: Multipicker list of experiment classes: number of columns?',
'type'=>'select_numbers',
'default_value'=>'3',
'value_begin'=>'1',
'value_end'=>'6',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);


$system__options_defaults[]=array(
'option_name'=>'query_experiment_list_nr_columns',
'option_text'=>'Participant query: Multipicker list of other experiments: number of columns?',
'type'=>'select_numbers',
'default_value'=>'3',
'value_begin'=>'1',
'value_end'=>'6',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array('type'=>'line');
$system__options_defaults[]=array(
'option_name'=>'query_random_subset_default_size',
'option_text'=>'Participant query: default size of random subset?',
'type'=>'select_numbers',
'default_value'=>'200',
'value_begin'=>'50',
'value_end'=>'2000',
'value_step'=>'50',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'queryform_partsearchactive_savedqueries_numberofentries',
'option_text'=>'Participant query: Number of saved queries to show in query form when searching for active participants',
'type'=>'textline',
'default_value'=>'5',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'queryform_partsearchall_savedqueries_numberofentries',
'option_text'=>'Participant query: Number of saved queries to show in query form when searching for all participants',
'type'=>'textline',
'default_value'=>'5',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'queryform_experimentassign_savedqueries_numberofentries',
'option_text'=>'Participant query: Number of previous queries to show when assigning new participants to an experiment',
'type'=>'textline',
'default_value'=>'5',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'queryform_experimentdeassign_savedqueries_numberofentries',
'option_text'=>'Participant query: Number of previous queries to show when de-assigning participants from an experiment',
'type'=>'textline',
'default_value'=>'5',
'size'=>'3',
'maxlength'=>'3',
);


$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'laboratory_opening_time_hour',
'option_text'=>'Laboratory: opening time?',
'type'=>'plain',
'field'=>'func:helpers__select_number("options[laboratory_opening_time_hour]",$options["laboratory_opening_time_hour"],0,23,2,1).
        ":".helpers__select_number("options[laboratory_opening_time_minute]",$options["laboratory_opening_time_minute"],0,59,2,15)'
);


$system__options_defaults[]=array(
'option_name'=>'laboratory_closing_time_hour',
'option_text'=>'Laboratory: closing time?',
'type'=>'plain',
'field'=>'func:helpers__select_number("options[laboratory_closing_time_hour]",$options["laboratory_closing_time_hour"],0,23,2,1).
        ":".helpers__select_number("options[laboratory_closing_time_minute]",$options["laboratory_closing_time_minute"],0,59,2,15)'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'lab_participants_max',
'option_text'=>'Laboratory: max number of participants?',
'type'=>'textline',
'default_value'=>'15',
'size'=>'3',
'maxlength'=>'4',
);

$system__options_defaults[]=array(
'option_name'=>'lab_participants_default',
'option_text'=>'Laboratory/Experiment Session: default number of participants?',
'type'=>'select_numbers',
'default_value'=>'3',
'value_begin'=>'1',
'value_end'=>'func:$options["lab_participants_max"]',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'reserve_participants_max',
'option_text'=>'Experiment session: max number of reserve participants?',
'type'=>'textline',
'default_value'=>'15',
'size'=>'3',
'maxlength'=>'4',
);

$system__options_defaults[]=array(
'option_name'=>'reserve_participants_default',
'option_text'=>'Experiment Session: default number of reserve participants?',
'type'=>'select_numbers',
'default_value'=>'3',
'value_begin'=>'1',
'value_end'=>'func:$options["reserve_participants_max"]',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'session_duration_hour_max',
'option_text'=>'Experiment Session: max duration in hours?',
'type'=>'select_numbers',
'default_value'=>'12',
'value_begin'=>'1',
'value_end'=>'24',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);


$system__options_defaults[]=array(
'option_name'=>'session_duration_minute_steps',
'option_text'=>'Experiment Session: duration minute steps?',
'type'=>'select_list',
'option_values'=>'5,10,15,20,30',
'option_values_lang'=>'5,10,15,20,30',
'default_value'=>'15',
'include_none_option'=>'n'
);


$system__options_defaults[]=array(
'option_name'=>'session_duration_hour_default',
'option_text'=>'Experiment session: default duration?',
'type'=>'plain',
'field'=>'func:helpers__select_number("options[session_duration_hour_default]",$options["session_duration_hour_default"],0,$options["session_duration_hour_max"],0,1).
    ":".helpers__select_number("options[session_duration_minute_default]",$options["session_duration_minute_default"],0,59,2,$options["session_duration_minute_steps"])'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'session_registration_end_hours_max',
'option_text'=>'Experiment Session: registration end: max hours before session?',
'type'=>'textline',
'default_value'=>'240',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'session_registration_end_hours_steps',
'option_text'=>'Experiment Session: registration end: steps for hours before session?',
'type'=>'textline',
'default_value'=>'12',
'size'=>'3',
'maxlength'=>'3',
);


$system__options_defaults[]=array(
'option_name'=>'session_registration_end_hours_default',
'option_text'=>'Experiment Session: registration end: default hours before session?',
'type'=>'select_numbers',
'default_value'=>'48',
'value_begin'=>'0',
'value_end'=>'func:$options["session_registration_end_hours_max"]',
'value_step'=>'func:$options["session_registration_end_hours_steps"]',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'session_reminder_send_on_default',
'option_text'=>'Experiment Session: default for "send session reminder on"-condition?',
'type'=>'select_list',
'option_values'=>'enough_participants_needed_plus_reserve,enough_participants_needed,in_any_case_dont_ask',
'option_values_lang'=>'enough_participants_needed_plus_reserve,enough_participants_needed,in_any_case_dont_ask',
'default_value'=>'enough_participants_needed',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'session_reminder_hours_max',
'option_text'=>'Experiment Session: send session reminder email: max hours before session?',
'type'=>'textline',
'default_value'=>'120',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'session_reminder_hours_steps',
'option_text'=>'Experiment Session: send session reminder email: steps for hours before session?',
'type'=>'textline',
'default_value'=>'12',
'size'=>'3',
'maxlength'=>'3',
);


$system__options_defaults[]=array(
'option_name'=>'session_reminder_hours_default',
'option_text'=>'Experiment Session: send session reminder email: default hours before session?',
'type'=>'select_numbers',
'default_value'=>'24',
'value_begin'=>'0',
'value_end'=>'func:$options["session_reminder_hours_max"]',
'value_step'=>'func:$options["session_reminder_hours_steps"]',
'values_reverse'=>'n',
'include_none_option'=>'n'
);


$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'session_start_years_backward',
'option_text'=>'Experiment Session: "session start"-field: years backward?',
'type'=>'select_numbers',
'default_value'=>'10',
'value_begin'=>'0',
'value_end'=>'40',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array(
'option_name'=>'session_start_years_forward',
'option_text'=>'Experiment Session: "session start"-field: years forward?',
'type'=>'select_numbers',
'default_value'=>'10',
'value_begin'=>'0',
'value_end'=>'20',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'n'
);

$system__options_defaults[]=array('type'=>'line');

$system__options_defaults[]=array(
'option_name'=>'mailqueue_number_of_entries_per_page',
'option_text'=>'General mail queue display: number of entries per page',
'type'=>'textline',
'default_value'=>'100',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array(
'option_name'=>'mailqueue_experiment_number_of_entries_per_page',
'option_text'=>'Experiment mail queue display: number of entries per page',
'type'=>'textline',
'default_value'=>'100',
'size'=>'3',
'maxlength'=>'3',
);

$system__options_defaults[]=array('type'=>'line');



//////////////////////////////////
// COLORS
//////////////////////////////////
$system__colors=array();


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Colors for header');

$system__colors[]=array(
'color_name'=>'html_header_top_bar_background',
'default_value'=>'#6b7da5'
);

$system__colors[]=array(
'color_name'=>'html_header_logo_bar_background',
'default_value'=>'#566383'
);

$system__colors[]=array(
'color_name'=>'html_header_menu_background',
'default_value'=>'#566383'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Colors for content');

$system__colors[]=array(
'color_name'=>'body_text',
'default_value'=>'#000000'
);

$system__colors[]=array(
'color_name'=>'body_link',
'default_value'=>'#006dc7'
);

$system__colors[]=array(
'color_name'=>'body_alink',
'default_value'=>'#006dc7'
);

$system__colors[]=array(
'color_name'=>'body_vlink',
'default_value'=>'#006dc7'
);

$system__colors[]=array(
'color_name'=>'shade_around_content',
'default_value'=>'#dadada'
);

$system__colors[]=array(
'color_name'=>'content_background_color',
'default_value'=>'#fefefe'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Colors for lists, panels, etc.');

$system__colors[]=array(
'color_name'=>'page_subtitle_background',
'default_value'=>'#EEEEEE'
);

$system__colors[]=array(
'color_name'=>'page_subtitle_textcolor',
'default_value'=>'#222222'
);

$system__colors[]=array(
'color_name'=>'panel_title_background',
'default_value'=>'#6b7da5'
);

$system__colors[]=array(
'color_name'=>'panel_title_textcolor',
'default_value'=>'#ffffff'
);

$system__colors[]=array(
'color_name'=>'options_box_background',
'default_value'=>'#d3d3d3'
);

$system__colors[]=array(
'color_name'=>'list_header_background',
'default_value'=>'#6b7da5'
);

$system__colors[]=array(
'color_name'=>'list_header_textcolor',
'default_value'=>'#FFFFFF'
);

$system__colors[]=array(
'color_name'=>'list_header_highlighted_background',
'default_value'=>'#93712c'
);

$system__colors[]=array(
'color_name'=>'list_header_highlighted_textcolor',
'default_value'=>'#ffffff'
);

$system__colors[]=array(
'color_name'=>'list_shade1',
'default_value'=>'#fefefe'
);

$system__colors[]=array(
'color_name'=>'list_shade2',
'default_value'=>'#e0e0e0'
);

$system__colors[]=array(
'color_name'=>'list_shade_subtitle',
'default_value'=>'#d3d3d3'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'ORSEE menu colors');

$system__colors[]=array(
'color_name'=>'menu_title',
'default_value'=>'#d7d7d7'
);


$system__colors[]=array(
'color_name'=>'menu_item',
'default_value'=>'#e2e2e2'
);


$system__colors[]=array(
'color_name'=>'menu_item_highlighted_background',
'default_value'=>'#93712c'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Notification message colors');

$system__colors[]=array(
'color_name'=>'message_background',
'default_value'=>'#ff7f50'
);

$system__colors[]=array(
'color_name'=>'message_border',
'default_value'=>'#566383'
);

$system__colors[]=array(
'color_name'=>'message_text',
'default_value'=>'#000000'
);

$system__colors[]=array(
'color_name'=>'important_note_textcolor',
'default_value'=>'#FF0000'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Colors to be used for participant profile / email popup');

$system__colors[]=array(
'color_name'=>'popup_bgcolor',
'default_value'=>'#e8e8e8'
);

$system__colors[]=array(
'color_name'=>'popup_text',
'default_value'=>'#021d59'
);

$system__colors[]=array(
'color_name'=>'popup_modal_color',
'default_value'=>'#a3a3a3'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Experiment calendar colors');

$system__colors[]=array(
'color_name'=>'calendar_month_font',
'default_value'=>'#ffffff'
);

$system__colors[]=array(
'color_name'=>'calendar_month_background',
'default_value'=>'#6b7da5'
);

$system__colors[]=array(
'color_name'=>'calendar_day_background',
'default_value'=>'#e6e6e6'
);

$system__colors[]=array('type'=>'comment',
        'text'=>'The following three are color <B>lists</B>, seperated by ",".<BR>
                The calendar will rotate through these colors to depict different experiments.<BR>
                If you want the same color for all experiments (e.g. in the public calendar,
                just choose only one color.');

$system__colors[]=array(
'color_name'=>'calendar_admin_experiment_sessions',
'default_value'=>'burlywood,cadetblue,darkkhaki,darkturquoise,gold,lightcyan,lightsteelblue,mediumspringgreen,springgreen',
'options'=>array('nopicker'=>true,'size'=>40,'maxlength'=>200)
);

$system__colors[]=array(
'color_name'=>'calendar_public_experiment_sessions',
'default_value'=>'burlywood,cadetblue,darkkhaki,darkturquoise,gold,lightcyan,lightsteelblue,mediumspringgreen,springgreen',
'options'=>array('nopicker'=>true,'size'=>40,'maxlength'=>200)
);

$system__colors[]=array(
'color_name'=>'calendar_event_reservation',
'default_value'=>'darksalmon,lightcoral,hotpink,lightpink,pink',
'options'=>array('nopicker'=>true,'size'=>40,'maxlength'=>200)
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Session state text colors for public and admin pages');

$system__colors[]=array(
'color_name'=>'session_complete',
'default_value'=>'#008000'
);

$system__colors[]=array(
'color_name'=>'session_not_enough_participants',
'default_value'=>'#ff0000'
);

$system__colors[]=array(
'color_name'=>'session_not_enough_reserve',
'default_value'=>'#ffa500'
);

$system__colors[]=array(
'color_name'=>'session_public_complete',
'default_value'=>'#ff0000'
);

$system__colors[]=array(
'color_name'=>'session_public_expired',
'default_value'=>'#0000ff'
);

$system__colors[]=array(
'color_name'=>'session_public_free_places',
'default_value'=>'#008000'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Participation status colors');

$system__colors[]=array('type'=>'comment',
            'text'=>'Public area');

$system__colors[]=array(
'color_name'=>'shownup_no',
'default_value'=>'#ff0000'
);

$system__colors[]=array(
'color_name'=>'shownup_yes',
'default_value'=>'#008000'
);

$system__colors[]=array('type'=>'comment',
            'text'=>'Admin pages');

$system__colors[]=array(
'color_name'=>'pstatus_noshow',
'default_value'=>'#ff9999'
);

$system__colors[]=array(
'color_name'=>'pstatus_participated',
'default_value'=>'#99ff99'
);

$system__colors[]=array(
'color_name'=>'pstatus_other',
'default_value'=>'#e1e1e6'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Participant status colors');

$system__colors[]=array(
'color_name'=>'participant_status_eligible_for_experiments',
'default_value'=>'#b9e6a8'
);

$system__colors[]=array(
'color_name'=>'participant_status_noneligible_for_experiments',
'default_value'=>'#c2bcac'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Text color for status of session reminder');

$system__colors[]=array(
'color_name'=>'session_reminder_state_sent_text',
'default_value'=>'#008000'
);


$system__colors[]=array(
'color_name'=>'session_reminder_state_checked_text',
'default_value'=>'#ff0000'
);

$system__colors[]=array(
'color_name'=>'session_reminder_state_waiting_text',
'default_value'=>'#0000ff'
);


$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Ethics approval background colors');

$system__colors[]=array(
'color_name'=>'ethics_approval_not_entered',
'default_value'=>'#e0e0eb'
);


$system__colors[]=array(
'color_name'=>'ethics_approval_valid',
'default_value'=>'#c2f0c2'
);


$system__colors[]=array(
'color_name'=>'ethics_approval_expired',
'default_value'=>'#ffd985'
);



$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Admin type list: error backgrounds');

$system__colors[]=array(
'color_name'=>'admin_type_error_missing_required',
'default_value'=>'#f08080'
);

$system__colors[]=array(
'color_name'=>'admin_type_required_by_error',
'default_value'=>'#90ee90'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Background color for fields not supplied in a form');

$system__colors[]=array(
'color_name'=>'missing_field',
'default_value'=>'#ffa500'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Public area, experiment registration');

$system__colors[]=array(
'color_name'=>'just_registered_session_background',
'default_value'=>'#f4a460'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Background color for graphs in statistics section');

$system__colors[]=array(
'color_name'=>'stats_graph_background',
'default_value'=>'#fffafa'
);



$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Some colors in admin area');

$system__colors[]=array(
'color_name'=>'search__pseudo_query_background',
'default_value'=>'#d8e1eb'
);

$system__colors[]=array(
'color_name'=>'email__new_emails_textcolor',
'default_value'=>'#0000FF'
);

$system__colors[]=array('type'=>'line');

$system__colors[]=array('type'=>'comment',
            'text'=>'Tooltips');

$system__colors[]=array(
'color_name'=>'tool_tip_background_color',
'default_value'=>'#566383'
);

$system__colors[]=array(
'color_name'=>'tool_tip_text_color',
'default_value'=>'#ffffff'
);



/*
$system__colors[]=array(
'color_name'=>'',
'default_value'=>''
);

*/


// DATABASE UPGRADE DEFINITIONS //
$system__database_upgrades=array();
include ("../config/dbupdates.php");

?>