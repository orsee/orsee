/* delete defaul privacy and laboratory rules */
delete from pesa2019.or_lang where content_name = "rules" or content_name = "privacy_policy";

/* increase index to prevent possible */
update pesa.or_lang set lang_id = lang_id + 220004 where content_name = "rules" or content_name = "privacy_policy";

/* insert old privacy and laboratory rules */
insert into pesa2019.or_lang(lang_id, enabled, content_type, content_name, en, de) 
select lang_id, enabled, content_type, content_name, en, de from pesa.or_lang where content_name = "rules" or content_name = "privacy_policy";