
ALTER TABLE pesa.or_sessions
ADD session_start_month_text VARCHAR(2),
ADD session_start_day_text VARCHAR(2),
ADD session_start_hour_text VARCHAR(2),
ADD session_start_minute_text VARCHAR(2),
ADD session_start VARCHAR(12),
ADD session_status VARCHAR(200);

update pesa.or_sessions set session_start_month_text = cast(session_start_month as char(2));
update pesa.or_sessions set session_start_month_text = concat("0", cast(session_start_month as char(1))) WHERE session_start_month < 10;

update pesa.or_sessions set session_start_day_text = cast(session_start_day as char(2));
update pesa.or_sessions set session_start_day_text = concat("0", cast(session_start_day as char(1))) WHERE session_start_day < 10;

update pesa.or_sessions set session_start_hour_text = cast(session_start_hour as char(2));
update pesa.or_sessions set session_start_hour_text = concat("0", cast(session_start_hour as char(1))) WHERE session_start_hour < 10;

update pesa.or_sessions set session_start_minute_text = cast(session_start_minute as char(2));
update pesa.or_sessions set session_start_minute_text = concat("0", cast(session_start_minute as char(1))) WHERE session_start_minute < 10;

update pesa.or_sessions set session_start = concat(session_start_year, session_start_month_text, session_start_day_text,
session_start_hour_text, session_start_minute_text);

update pesa.or_sessions set session_status = "completed" where experiment_id in (select experiment_id from pesa.or_experiments where experiment_finished = "y");

insert into pesa2019.or_sessions(session_id, experiment_id, session_start, session_duration_hour, session_duration_minute, session_reminder_hours, send_reminder_on, reminder_checked, reminder_sent, noshow_warning_sent, registration_end_hours, part_needed, part_reserve, session_remarks, laboratory_id, session_status)
select session_id, experiment_id, session_start, session_duration_hour, session_duration_minute, session_reminder_hours, send_reminder_on, reminder_checked, reminder_sent, noshow_warning_sent, registration_end_hours, part_needed, part_reserve, session_remarks, laboratory_id, session_status from pesa.or_sessions
	where session_start_year > 2016 and experiment_id in (select experiment_id from pesa2019.or_experiments);