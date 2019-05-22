insert into pesa2019.or_participants(participant_id, participant_id_crypt, subpool_id, email, phone_number, lname, fname, begin_of_studies, subscriptions, field_of_studies, profession, gender, number_reg, number_noshowup) 
select participant_id, participant_id_crypt, subpool_id, email, phone_number, lname, fname, begin_of_studies, subscriptions, field_of_studies, profession, gender, number_reg, number_noshowup
from pesa.or_participants where deleted = "n" and excluded = "n";

/* set status_id to 1 because we have only not deleted and not excluded ones */
update pesa2019.or_participants set status_id=1;

/* subsciptions in or_participants uses |<exptype_id1>|, |<exptype_id2>| and not exptype_name */
/*drop table numbers;*/
create temporary table pesa2019.numbers (N int1);
insert into pesa2019.numbers values (1),(2),(3),(4),(5),(6);
/*select * from numbers;*/

update pesa2019.or_participants as a join
(select participant_id, group_concat(concat('|',exptype_id,'|') separator ',') as new_subs
from ((select participant_id, substring_index(substring_index(subscriptions, ',', N), ',', -1) as subs
			from pesa.or_participants
            join pesa2019.numbers on char_length(subscriptions) - char_length(replace(subscriptions, ',', '') >= N-1)
            group by participant_id, subs) as a
	 join pesa2019.or_experiment_types as b on a.subs = b.exptype_name)
group by participant_id) as b on a.participant_id = b.participant_id
set a.subscriptions = b.new_subs
where a.participant_id = b.participant_id;