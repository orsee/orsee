update pesa.or_lang set lang_id = lang_id + 220004 where content_type="public_content" and content_name = "contact";
delete from pesa2019.or_lang where content_type="public_content" and content_name = "contact";
insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de)
select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang where content_type="public_content" and content_name = "contact";
