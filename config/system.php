<?php
// system version dependent settings. part of orsee. see orsee.org

$system__version="2.0.2";


// implemented experiment types

$system__experiment_types=array('laboratory','online-survey','internet');

$system__os_question_types=array('textline','textarea','select_text','select_numbers','radio','checkbox');



// include path for tagsets
ini_set("include_path",ini_get("include_path").":./tagsets:./../tagsets:./../../tagsets");

// build experiment system root url
$settings__root_url="http://".$settings__server_url.$settings__root_directory;

// define administration rights
$system__admin_rights=array(
"admin_delete:delete an administrator:admin_edit",
"admin_edit:create/edit administrators",
"admin_type_delete:delete admin user types:admin_type_edit",
"admin_type_edit:add/edit user types of admin area",
"default_text_add:add new default text item - dev!:default_text_edit",
"default_text_delete:delete default text item - dev!:default_text_edit",
"default_text_edit:edit system's default texts",
"download_download:download files in download section",
"download_experiment_delete:delete file uploaded for experiment:download_download",
"download_experiment_upload:upload files for experiments:download_download,experiment_show",
"download_general_delete:delete uploaded general file:download_download",
"download_general_upload:upload general file:download_download",
"experiment_assign_participants:assign subjects to experiment/delete assignments:experiment_show",
"experiment_delete:delete experiment (with sessions, assignments ...):experiment_show,experiment_edit",
"experiment_edit:create experiment/edit experiment's basic settings:experiment_show",
"experiment_edit_participants:edit session's participants list (show-ups etc.):experiment_show,experiment_show_participants",
"experiment_invitation_edit:edit invitation texts:experiment_show",
"experiment_invite_participants:send out invitations for an experiment:experiment_show,experiment_invitation_edit",
"experiment_restriction_override:override experiment access restriction",
"experiment_show:see an experiment's main page",
"experiment_show_participants:see participants list for an experiment:experiment_show",
"experimentclass_add:add class of experiments:experimentclass_edit",
"experimentclass_delete:delete experiment class:experimentclass_edit",
"experimentclass_edit:edit experiment class",
"experimenttype_add:add external experiment type:experimenttype_edit",
"experimenttype_delete:delete external experiment type:experimenttype_edit",
"experimenttype_edit:edit external experiment types",
"faq_add:add FAQ:faq_edit",
"faq_delete:delete FAQ:faq_edit",
"faq_edit:edit FAQ",
"field_of_studies_add:add field of studies:field_of_studies_edit",
"field_of_studies_delete:delete field of studies:field_of_studies_edit",
"field_of_studies_edit:edit fields of studies",
"help_add:add new help item - dev!:help_edit",
"help_delete:delete help item - dev!:help_edit",
"help_edit:edit help entries",
"import_data:import experiment/participant data from old ORSEE version",
"lab_space_delete:delete lab reservation:lab_space_edit",
"lab_space_edit:create/change lab reservation",
"laboratory_add:add new laboratory:laboratory_edit",
"laboratory_delete:delete laboratory:laboratory_edit",
"laboratory_edit:edit laboratory settings",
"lang_avail_edit:edit language public avability settings",
"lang_lang_add:install language:lang_lang_edit",
"lang_lang_delete:uninstall language:lang_lang_edit",
"lang_lang_edit:edit specific language settings",
"lang_lang_export:export language to orsee language file:lang_lang_edit",
"lang_lang_import: import language from orsee_langauge_file:lang_lang_edit",
"lang_symbol_add:add new language symbol- dev!:lang_symbol_edit",
"lang_symbol_delete:delete language symbol - dev!:lang_symbol_edit",
"lang_symbol_edit:edit language symbols (words,expressions)",
"log_file_experimenter_actions_delete:delete log file of experimenter actions:log_file_experimenter_actions_show",
"log_file_experimenter_actions_show:see log file of experimenter actions",
"log_file_participant_actions_delete:delete log file of participant actions:log_file_participant_actions_show",
"log_file_participant_actions_show:view log file for participant actions",
"log_file_regular_tasks_delete:delete log file of regular system tasks:log_file_regular_tasks_show",
"log_file_regular_tasks_show:view log file of regzular tasks",
"login:login into administration area",
"mail_add:add new default mail text - dev!:mail_edit",
"mail_delete:delete default mail text - dev!:mail_edit",
"mail_edit:edit default email texts",
"participants_bulk_mail:send bulk mail to participants:participants_show",
"participants_edit:create participant/change data for participant:participants_show",
"participants_resubscribe:resubscribe participant:participants_show,participants_edit",
"participants_show:search participants, view list",
"participants_unconfirmed_edit:view and delete unconfirmed participant entries",
"participants_unsubscribe:unsubscribe/exlude participant:participants_show,participants_edit",
"profession_add:add profession:profession_edit",
"profession_delete:delete profession:profession_edit",
"profession_edit:edit professions",
"public_content_add:add new public content item - dev!:public_content_edit",
"public_content_delete:delete public content item - dev!:public_content_edit",
"public_content_edit:edit system's public content",
"regular_tasks_add:add new regular task - dev!:regular_tasks_show,regular_tasks_edit",
"regular_tasks_edit:edit regular tasks:regular_tasks_show",
"regular_tasks_run:run regular task by hand:regular_tasks_show",
"regular_tasks_show:show list of system's regular tasks",
"session_edit:create session/change session settings:experiment_show",
"session_empty_delete:remove session without registrations:experiment_show,session_edit",
"session_nonempty_delete:delete sessions with and without registrations:experiment_show,session_edit",
"session_send_reminder:send session reminder by hand:experiment_show,experiment_show_participants",
"settings_edit:edit general settings and defaults",
"settings_option_add:add general option item - dev!",
"statistics_participants_show:see participant statistics",
"statistics_server_usage_show:see web server statistics",
"statistics_system_show:see system statistics",
"subjectpool_add:add new subject pool:subjectpool_edit",
"subjectpool_delete:delete subject pool:subjectpool_edit",
"subjectpool_edit:edit subjectpool settings"
		);	


// define options
$system__options=array(

// set at general options
"general:admin_standard_language:en",
"general:public_standard_language:en",

"general:automatic_exclusion_noshows:3",
"general:send_noshow_warnings:y",
"general:automatic_exclusion:y",
"general:automatic_exclusion_inform:y",

"general:allow_experiment_restriction:y",

"general:default_area:ORSEE",

"general:email_sendmail_type:direct",
"general:email_sendmail_path:/usr/sbin/sendmail",
"general:mail_queue_number_send_per_time:50",

"general:http_log_file_location:/var/log/httpd/access_log",

"general:orsee_public_style:orsee",
"general:orsee_admin_style:orsee",

"general:stats_plots_gd_version:1",

"general:stop_public_site:n",

"general:subpool_default_registration_id:1",

"general:support_mail:experiments@orsee.org",

"general:upload_categories:instructions,data_files,programs,paper,presentations,other",
"general:upload_max_size:500000",

"general:public_calendar_hide_exp_name:n",

"general:emailed_calendar_included_months:2",

// set at language options
"general:language_enabled_public:en",
"general:language_enabled_participants:en",


// set at default options
"default:default_admin_type:experimenter",

"default:stats_type:both",

"default:stats_logs_results_per_page:50",

"default:calendar_pdf_title_fontsize:12",
"default:calendar_pdf_table_fontsize:8",
"default:participant_list_pdf_title_fontsize:12",
"default:participant_list_pdf_table_fontsize:10",

"default:experimenter_list_nr_columns:3",

"default:query_experiment_classes_list_nr_columns:3",
"default:query_experiment_list_nr_columns:3",
"default:query_number_exp_limited_view:10",
"default:query_random_subset_default_size:100",

"default:laboratory_opening_time_hour:8",
"default:laboratory_opening_time_minute:0",
"default:laboratory_closing_time_hour:20",
"default:laboratory_closing_time_minute:0",

"default:lab_participants_max:30",
"default:lab_participants_default:24",
"default:reserve_participants_max:5",
"default:reserve_participants_default:3",

"default:session_duration_hour_max:4",
"default:session_duration_minute_steps:15",
"default:session_duration_hour_default:1",
"default:session_duration_minute_default:30",

"default:session_registration_end_hours_max:240",
"default:session_registration_end_hours_steps:12",
"default:session_registration_end_hours_default:72",

"default:session_reminder_send_on_default:enough_participants_needed",
"default:session_reminder_hours_max:120",
"default:session_reminder_hours_steps:12",
"default:session_reminder_hours_default:48",

"default:session_start_years_backward:2",
"default:session_start_years_forward:5",

"default:begin_of_studies_years_backward:10",
"default:begin_of_studies_years_forward:1",

		);

?>
