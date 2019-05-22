/* delete defaul mail texts which are also in old pesa */
delete from pesa2019.or_lang where (content_type = "mail" and (
content_name = 'admin_mail_footer' 
or content_name = 'admin_registration_notice' 
or content_name = 'admin_session_reminder_notice' 
or content_name = 'default_invitation_internet' 
or content_name = 'default_invitation_laboratory' 
or content_name = 'default_invitation_online-survey' 
or content_name = 'public_experiment_registration' 
or content_name = 'public_mail_footer' 
or content_name = 'public_session_reminder' 
or content_name = 'public_system_registration' 
or content_name = 'admin_calendar_mailtext' 
or content_name = 'admin_participant_statistics_mailtext' 
or content_name = 'public_participant_exclusion' 
or content_name = 'public_noshow_warning'));

/* increase index to prevent possible */
update pesa.or_lang set lang_id = lang_id + 220004 where content_type = "mail";

/* insert old mail texts */
insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de) 
( select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang
where content_type = "mail" and (
content_name = 'admin_mail_footer' 
or content_name = 'admin_registration_notice' 
or content_name = 'admin_session_reminder_notice' 
or content_name = 'default_invitation_internet' 
or content_name = 'default_invitation_laboratory' 
or content_name = 'default_invitation_online-survey' 
or content_name = 'public_experiment_registration' 
or content_name = 'public_mail_footer' 
or content_name = 'public_session_reminder' 
or content_name = 'public_system_registration' 
or content_name = 'admin_calendar_mailtext' 
or content_name = 'admin_participant_statistics_mailtext' 
or content_name = 'public_participant_exclusion' 
or content_name = 'public_noshow_warning'));