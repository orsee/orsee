#!/bin/bash
array=(pesa_transfer_experiment_types.sql
pesa_transfer_experiments.sql
pesa_transfer_sessions.sql
pesa_transfer_bulk_mail_texts.sql
pesa_transfer_lang_labs.sql
pesa_transfer_faqs.sql
pesa_transfer_rules_and_privacy.sql
pesa_transfer_experiment_classes.sql
pesa_transfer_experiment_session_invitation_mail.sql
pesa_transfer_experiment_session_reminder_mail.sql
pesa_transfer_participants.sql
pesa_transfer_participate_at.sql
pesa_transfer_contact.sql
pesa_transfer_impressum.sql
pesa_transfer_subpools.sql
pesa_transfer_mail.sql
pesa_transfer_welcome.sql)

# transfering or_admin, this requires a .htaccess file
./pesa_transfer_admin.sh

for sql_file in ${!array[@]}
do
	#echo "$sql_file ${array[$sql_file]}"
	echo ${array[$sql_file]}
	mysql -uroot -p < ${array[$sql_file]}
done
