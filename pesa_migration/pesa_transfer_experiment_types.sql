truncate table pesa2019.or_experiment_types;
insert into pesa2019.or_experiment_types(exptype_id, exptype_name, exptype_description, exptype_mapping, enabled) 
select exptype_id, exptype_name, exptype_description, exptype_mapping, enabled from pesa.or_experiment_types;

delete from pesa2019.or_lang where content_type='experiment_type';
update pesa.or_lang set lang_id = lang_id + 220004 where content_type = "experiment_type";

insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de) 
select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang where content_type = "experiment_type";

/* add new types which combine payment, feedback and VP-hours */
/*insert into pesa2019.or_experiment_types 
values (7, 'LaboratoryP,VP', 'normal laboratory experiments with payment and VP-hours', 'laboratory', 'y'),
       (8, 'LaboratoryFB,VP', 'normal laboratory experiments with feedback and VP-hours', 'laboratory', 'y'),
       (9, 'LaboratoryP,FB', 'normal laboratory experiments with payment and feedback', 'laboratory', 'y'),
       (10, 'LaboratoryP,FB,VP', 'normal laboratory experiments with payment, feedback and VP-hours', 'laboratory', 'y'),
       (11, 'Online-SurveyP,VP', 'online survey experiments with payment and VP-hours', 'online-survey', 'y'),
       (12, 'Online-SurveyFB,VP', 'online survey experiments with feedback and VP-hours', 'online-survey', 'y'),
       (13, 'Online-SurveyP,FB', 'online survey experiments with payment and feedback', 'online-survey', 'y'),
       (14, 'Online-SurveyP,FB,VP', 'online survey experiments with payment, feedback and VP-hours', 'online-survey', 'y');*/
       
/* add missing or_lang entries for experiment types */
/*insert into pesa2019.or_lang
values (300010, 'y', -1, 'experiment_type', 7, 'laboratory experiments with payment and VP-hours (students of psychology)', 'Labor-Experimente mit Bezahlung und VP-Stunden (für Psychologiestudenten)'),
       (300011, 'y', -1, 'experiment_type', 8, 'laboratory experiments with feedback and VP-hours (students of psychology)', 'Labor-Experimente mit Feedback und VP-Stunden (für Psychologiestudenten)'),
       (300012, 'y', -1, 'experiment_type', 9, 'laboratory experiments with payment and feedback', 'Labor-Experimente mit Bezahlung und Feedback'),
       (300013, 'y', -1, 'experiment_type', 10, 'laboratory experiments with payment, feedback and VP-hours (students of psychology)', 'Labor-Experimente mit Bezahlung, Feedback und VP-Stunden (für Psychologiestudenten)'),
       (300014, 'y', -1, 'experiment_type', 11, 'online survey with payment and VP-hours (students of psychology)', 'Online-Umfrage mit Bezahlung und VP-Stunden (für Psychologiestudenten)'),
       (300015, 'y', -1, 'experiment_type', 12, 'online survey with feedback and VP-hours (students of psychology)', 'Online-Umfrage mit Feedback und VP-Stunden (für Psychologiestudenten)'),
       (300016, 'y', -1, 'experiment_type', 13, 'online survey with payment and feedback', 'Online-Umfrage mit Bezahlung und Feedback'),
       (300017, 'y', -1, 'experiment_type', 14, 'online survey with payment, feedback and VP-hours (students of psychology)', 'Online-Umfrage mit Bezahlung, Feedback und VP-Stunden (für Psychologiestudenten)');*/
