update pesa.or_lang set lang_id = lang_id + 220004 where content_name = "mainpage_welcome";
delete from pesa2019.or_lang where content_name = "impressum";
insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de)
select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang where content_name = "impressum";
