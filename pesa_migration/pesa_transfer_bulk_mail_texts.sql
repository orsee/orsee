insert into pesa2019.or_bulk_mail_texts(bulktext_id, bulk_id, lang, bulk_subject, bulk_text) 
select bulktext_id, bulk_id, lang, bulk_subject, bulk_text
from pesa.or_bulk_mail_texts 