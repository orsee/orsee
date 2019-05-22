truncate table pesa2019.or_faqs;
insert into pesa2019.or_faqs(faq_id, evaluation) 
select faq_id, evaluation from pesa.or_faqs;

update pesa.or_lang set lang_id = lang_id + 220004 where content_type = "faq_question";
insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de) 
select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang where content_type = "faq_question";

