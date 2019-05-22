insert into pesa2019.or_experiments(experiment_id, experiment_name, experiment_public_name, experiment_type, experiment_ext_type, experiment_class, experiment_description, experimenter_mail, sender_mail, experiment_public, experimenter, access_restricted, experiment_finished, hide_in_stats, hide_in_cal, experiment_link_to_paper) 
select experiment_id, experiment_name, experiment_public_name, experiment_type, experiment_ext_type, experiment_class, experiment_description, experimenter_mail, sender_mail, experiment_public, experimenter, access_restricted, experiment_finished, hide_in_stats, hide_in_cal, experiment_link_to_paper
from pesa.or_experiments where experiment_id in (select experiment_id from pesa.or_sessions where session_start_year > 2016);

/* experiment_class in or_experiments uses |<content_name>| from or_lang
   content_name is already used in column experiment_class but the || are missing*/
update pesa2019.or_experiments set experiment_class=concat('|',experiment_class,'|');

/*----------------------------------------------------------------------------------
  experimenter in or_experiments uses |<admin_id1>|, |<admin_id2>| and not adminname
----------------------------------------------------------------------------------*/

/* drop table pesa2019.numbers; */
create temporary table pesa2019.numbers (N int1);
insert into pesa2019.numbers values (1),(2),(3),(4),(5);
/*select * from pesa2019.numbers;*/

/* create table containing a row for each experiment's experimenter... in fact it's describing the 1(experiment)-n(experimenter) relation */
/* drop table pesa2019.exps; */
create temporary table pesa2019.exps (experiment_id varchar(10), exp varchar(10), admin_id varchar(20));
insert into pesa2019.exps select experiment_id, exp, admin_id
	from (select experiment_id, substring_index(substring_index(experimenter, ',', N), ',', -1) as exp
			from pesa2019.or_experiments
            join pesa2019.numbers on char_length(experimenter) - char_length(replace(experimenter, ',', '') >= N-1)) as t
	join pesa2019.or_admin on t.exp = adminname
    group by experiment_id, admin_id, exp;
/* select * from pesa2019.exps; */

update pesa2019.exps set admin_id=concat('|', admin_id, '|');

/* "compress" the exps table to a table containing the experiment id and a list of experimenter in the new format */
/* drop table pesa2019.exp_id_to_admin_ids; */ 
create temporary table pesa2019.exp_id_to_admin_ids (experiment_id varchar(10), admin_ids varchar(100));
insert into pesa2019.exp_id_to_admin_ids select experiment_id, group_concat(admin_id separator ",") from pesa2019.exps group by experiment_id;
/* select * from pesa2019.exp_id_to_admin_ids; */

/* delete all experiments which are not contained in exp_id_to_admin_ids because the experiementer isn't contained in or_admins any more */
delete from pesa2019.or_experiments where experiment_id not in (select experiment_id from pesa2019.exp_id_to_admin_ids );

update pesa2019.or_experiments as a join pesa2019.exp_id_to_admin_ids as b on a.experiment_id = b.experiment_id 
	set a.experimenter = b.admin_ids
    where a.experiment_id = b.experiment_id;
    
/*---------------------------------------------------------
  transform experimenter_mail to new format: |<admin_id>|
---------------------------------------------------------*/

/* create table containing a row for each experiment's experimenter_mail... in fact it's describing the 1(experiment)-n(experimenter_mail) relation */
/* drop table pesa2019.exps; */
/* drop table pesa2019.exp_mail; */
create temporary table pesa2019.exp_mail (experiment_id varchar(10), exp varchar(10), admin_id varchar(20));
insert into pesa2019.exp_mail select experiment_id, exp, admin_id
	from (select experiment_id, substring_index(substring_index(experimenter_mail, ',', N), ',', -1) as exp
			from pesa2019.or_experiments
            join pesa2019.numbers on char_length(experimenter_mail) - char_length(replace(experimenter_mail, ',', '') >= N-1)) as t
	join pesa2019.or_admin on t.exp = adminname
    group by experiment_id, admin_id, exp;
 /* select * from pesa2019.exp_mail; */

update pesa2019.exp_mail set admin_id=concat('|', admin_id, '|');

/* "compress" the exp_mail table to a table containing the experiment id and a list of experimenter in the new format */
truncate table pesa2019.exp_id_to_admin_ids;
/* create temporary table pesa2019.exp_id_to_admin_ids (experiment_id varchar(10), admin_ids varchar(100)); */
insert into pesa2019.exp_id_to_admin_ids select experiment_id, group_concat(admin_id separator ",") from pesa2019.exp_mail group by experiment_id;
/* select * from pesa2019.exp_id_to_admin_ids; */

update pesa2019.or_experiments as a join pesa2019.exp_id_to_admin_ids as b on a.experiment_id = b.experiment_id 
	set a.experimenter_mail = b.admin_ids
    where a.experiment_id = b.experiment_id;
    
/* set experimenter_mail to the experimenter which are still there for all experiments which have no experimenter_mail entry anymore*/
update pesa2019.or_experiments 
	set experimenter_mail = experimenter
    where experiment_id not in (select experiment_id from pesa2019.exp_id_to_admin_ids);
    
/*---------------------------------------------------------
  update sender mail
---------------------------------------------------------*/
update pesa2019.or_experiments
	set sender_mail = (select email from pesa2019.or_admin where admin_id = replace(experimenter_mail, '|', ''))
    where sender_mail not in (select email from pesa2019.or_admin);

/* experiment_ext_type uses experiment_types.exptype_id and not exptype_name */
update pesa2019.or_experiments as a join pesa2019.or_experiment_types as b on a.experiment_ext_type = b.exptype_name
	set a.experiment_ext_type = concat('|', b.exptype_id, '|');

/* replace old "mixed"-experiment_ext_type with new types */
update pesa2019.or_experiments set experiment_ext_type='|3|,|1|' where experiment_ext_type='LaboratoryVP,LaboratoryP';
update pesa2019.or_experiments set experiment_ext_type='|2|,|3|' where experiment_ext_type='LaboratoryFB,LaboratoryVP';
update pesa2019.or_experiments set experiment_ext_type='|2|,|1|' where experiment_ext_type='LaboratoryFB,LaboratoryP';
update pesa2019.or_experiments set experiment_ext_type='|2|,|3|,|1|' where experiment_ext_type='LaboratoryFB,LaboratoryVP,LaboratoryP';
update pesa2019.or_experiments set experiment_ext_type='|6|,|4|' where experiment_ext_type='Online-SurveyVP,Online-SurveyP';
update pesa2019.or_experiments set experiment_ext_type='|6|,|5|' where experiment_ext_type='Online-SurveyVP,Online-SurveyFB';
update pesa2019.or_experiments set experiment_ext_type='|5|,|4|' where experiment_ext_type='Online-SurveyFB,Online-SurveyP';
update pesa2019.or_experiments set experiment_ext_type='|4|,|5|,|6|' where experiment_ext_type='Online-SurveyVP,Online-SurveyFB,Online-SurveyP';