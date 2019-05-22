ALTER TABLE pesa.or_participate_at ADD invited_tiny TINYINT(1);

ALTER TABLE pesa2019.or_participate_at DROP INDEX uindex2,
ADD UNIQUE INDEX uindex2 (participant_id ASC, experiment_id ASC, session_id ASC);

update pesa.or_participate_at set invited_tiny = IF(invited='y',1,0);

alter table pesa.or_participate_at add pstatus_id int(20);
update pesa.or_participate_at set pstatus_id = 3 where shownup='n' and participated='n';
update pesa.or_participate_at set pstatus_id = 3 where shownup='n' and participated='y';
update pesa.or_participate_at set pstatus_id = 2 where shownup='y' and participated='n';
update pesa.or_participate_at set pstatus_id = 1 where shownup='y' and participated='y';

insert into pesa2019.or_participate_at(participate_id, participant_id, experiment_id, invited, 
session_id, pstatus_id) 
select participate_id, participant_id, experiment_id, invited_tiny, session_id, pstatus_id from pesa.or_participate_at
	where experiment_id in (select experiment_id from pesa2019.or_experiments);


