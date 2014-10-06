<?php
/*
This file defines the entries for the participant form.

To create a new form field, do the following three steps:

1. Add a new entry below. An entry starts with "$participantform[]=array(" and ends with ");" and has a number of options in between separated by commas, with each option in the format 'optionname'=>'optionvalue'. PHP will throw an error if there is a syntax error in this definition format. Order of entries does not matter for the participant form, but matters for the order of fields in the search form and in search results tables.

2. Add your field as a column of the same name in the or_participants table and the or_participanrts_temp table of your mysql database. (Note: If you already have that field in your database and you are now upgrading, you can skip this step.) No matter whether the field contains numbers or text, I recommend to use a varchar column of sufficient size but no more than 250 characters (except when you need to create a column for a textarea field which is bigger). I.e.

mysql> ALTER TABLE or_participants ADD COLUMN mysql_column_name varchar(200) collate utf8_unicode_ci default NULL;

or, if a textarea,

mysql> ALTER TABLE or_participants ADD COLUMN mysql_column_name mediumtext collate utf8_unicode_ci;

Note that these examples assume a unicode database. If you kept the old latin1 database, then replace utf8_unicode_ci with latin1_general_ci.

I also recommend to add an index for your new column, this will reduce query time significantly:

mysql> ALTER TABLE or_participants ADD INDEX mysql_column_name_index (mysql_column_name);

3. Edit the participant form template in ftpl/participant_form.tpl. This template is very flexible and allows you do design your particiant form as you wish. (If you added a participant form field that is only available to experimenters/admins (option 'admin_only'=>'y'), then you will need to edit ftpl/participant_form_admin_addons.tpl instead.) ftpl/template_instructions.txt contains a short documentation of the template format.

Note that you will have to apply exactly the same changes to the table or_participants_temp, both should look identical.

--
ORSEE allows for a range of form fields types which are described below. There are a number of options which are shared among the types, and some that are specific. If an option is not stated the field definition, then the default is assumed. Only the options 'mysql_column_name','name_lang',' and type' are compulsory and no default exists for them.

Whenever an option requires a language item (that is, an entry in the or_lang language table), this can be created in Options/Languages/Add symbol (your admin type will need the right to create a language symbol). You will need to use the same shortcut for the symbol as provided here. 

One special feature is that the system also allows to assign php code to an option. If the option value is preceded by "func:" then what follows is interpreted as a php expression and is evaluated before the value is assigned in runtime. Examples are provided below for ype "select_numbers" where the current year is used to determine the start year of a list, as in 'value_begin'=>'func:(int) date("Y")-10'.


OPTIONS SHARED ACROSS TYPES:
'mysql_column_name': name of the mysql column in which data for this field is stored

'name_lang': shortcut of language item expressing the name of this field

'type': either select_lang, select_numbers, select_list, radioline, textline, or textarea. See more details below.

'subpools': sub-subjectpools to which this form field appies. Can be either 'all' or a comma-separated list of subject pool ids. Ids can be seen in Options/Sub-subjectpools. Default is 'all'.

'compulsory': Either 'y' or 'n'. Whether this form field is compulsory (miust be non-empty) or not. If set to 'y', then a check will be performed whenever the participant form is submitted. Default is 'n'.

'error_message_if_empty_lang': shortcut of language item containing the error message to be displayed when compulsory='y' but the submitted form field is empty.


'perl_regexp': Either empty '' or a perl regular expression as defined here: http://php.net/manual/en/pcre.pattern.php. When not empty, then ORSEE will perfrom a pattern match on the submitted value using the provided pattern, and will only accept the entry if it matches the pattern. Most usefully this is applied to textline items (e.g. email address, univeristy ID, etc.). Default is ''.

'error_message_if_no_regexp_match_lang': shortcut of language item containing the error message to be displayed when perl_regexp is not empty and the pattern match fails.

'admin_only': Either 'y' or 'n'. Whether this form field is only displayed to administrators when editing the profile ('y'), or is also present to subjects ('n'). Default is 'n'.

'require_unique_on_create_page': Either 'y' or 'n'. Whether, when a subject registers with the database, the entry in this field have to be unique across the whole participant subjectpool or not. When a value is submitted that already exists, then the registration will not be accepted. Default 'n'.

'unique_on_create_page_error_message_if_exists_lang': shortcut of language item containing the error message to be displayed when require_unique_on_create_page='y' but the submitted form field already exists in the database. Default ''.

'unique_on_create_page_tell_if_deleted': Either 'y' or 'n'. Whether, when the subject registers with the database and submits a value that already exists for a field required to be unqiue, the system should check whether this corresponds to a subject that was deleted or excluded from the system, and if so it should isse an error message in this regard. Default 'n'.

'unique_on_create_page_email_regmail_confmail_again': Either 'y' or 'n'. Whether, when the subject registers with the database, submits a value that already exists for a field required to be unqiue, and the corresponding profile is neither deleted or excluded, the system should resend the confirmation email (when subject had not confirmed yet) or a profile edit email (when the subject had confirmed) to the regsitered email address. Default 'n'.

'check_unique_on_edit_page': Either 'y' or 'n'. Whether, when a subject changes her profile data, a check on uniqueness is executed or not. When a value is submitted that already exists in another prfile, then the change will not be accepted. Default 'n'.

'unique_on_edit_page_error_message_if_exists_lang:  shortcut of language item containing the error message to be displayed when check_unique_on_edit_page='y' but the submitted form field already exists in the database. Default ''.

'default_value': The default value of this form field (i.e. the pre-filled value on the participant profile registration/creation form).

'search_include_in_participant_query': Either 'y' or 'n'. Whether this participant field should be included as a search field in the participant query (on Participants/Edit participants). Default 'n'.

'search_include_in_experiment_assign_query': Either 'y' or 'n'. Whether this participant field should be included as a search field in the query for participants when assigning them to an experiment (on Experiment/Assign participants). Default 'n'.

'searchresult_list_in_participant_results': Either 'y' or 'n'. Whether this participant field should be included as a column in the results table after a search for participants. Default 'n'.

'searchresult_list_in_experiment_assign_results': Either 'y' or 'n'. Whether this participant field should be included as a column in the results table after searching for participants to be assigned to an experiment. Default 'n'.

'search_result_allow_sort': Either 'y' or 'n'. Whether the system shoudl allow to sort the search results list by this column. If yes, then the column header will be a link, and a click on the link sorts the results. Default is 'n'.

'search_result_sort_order': Either empty '' or a comma-separated list of mysql columns representing the sort order when a table is sorted by this field. Default is empty ''. To illustrate: in the default ORSEE configuration, we wanted to allow a sort by lastname and then firstname. So for field fname, search_result_sort_order='n', while for field lname, search_result_sort_order='y' and search_result_sort_order='lname, fname'.

'list_in_session_participants_list': Either 'y' or 'n'. Whether the field should be listed as a column in the list of session participants. Default is 'n'.

'allow_sort_in_session_participants_list': Either empty '' or a comma-separated list of mysql columns representing the sort order when a session participant list is sorted by this field. Default is empty ''.

'list_in_session_pdf_list': Either 'y' or 'n'. Whether the field should be listed as a column in the PDF list of session participants. Default is 'n'.

'link_as_email_in_lists': Either 'y' or 'n'. If 'y' then this field will be presented as an email address with an underlying emailto: link. So only set to 'y' for fields that contain an email address. Default is 'n'.

'link_as_email_in_lists': Can be 'n', 'pie' or 'bars'. If set to 'pie' or 'bars', then the field is automatically included in the participant statistics, as a pie or bar chart, respectively.  Default is 'n'.

TYPE-SPECIFIC OPTIONS

TYPE: select_lang
A select list with a number of options. Options can be freely configured in Options/itemname where the new field is automatically included (similar to field of studies and professions in older ORSEE versions). For examples, see the ORSEE-original definitions of "field_of_studies" and "profession" below.
'include_none_option': Whether or not to include a "none" option (represented by value 0 and '-' in the select list) in addition to the options defined in Options/itemname. 

TYPE: select_numbers
A select list with numbers.
'value_begin': start number, default '0'
'value_end': last number, default '1'
'value_step: step size, default '1'
'values_reverse': whether to display from largest number to smallest (value 'y') or smallest to largest (value 'n'). Default is 'n'.
In this type, the possibility of using code within the options is particularly useful. For an example, see the ORSEE-original definition of "begin of studies" below. As another example, for a year of birth that allows all years from current-17 to current-100 we could use 'value_begin'=>'func:(int) date("Y")-100','value_end'=>'func:(int) date("Y")-17','value_step'=>'1','values_reverse'=>'y'.
 
TYPE: select_list
If there are only a few values, it might be more efficient to use this type instead of select_numbers. It allows to define the options right away here in the field definition.
'option_values': a comma-separated list of the values for each option
'option_values_lang': a comma-separated list of the language-table items that should be used for the dsiplay of these options. If the respective language items do not exist, ORSEE will simply display these item names.

TYPE: radioline
Same idea and the same options as for type select_list, only that the options are preented as a row of radio buttons (rather than a select field). For an example, see the ORSEE-original definition of "gender" below.

TYPE: textline
Asks for a line of text. For an example, see the ORSEE-original fields 'lname', 'fname', 'email' below.
'size': The size of text field in chars, as given to the INPUT form tag. Default 40.
'maxlength': The maximal lenght of the text in the INPUT field, in chars. Default 100.

TYPE: textarea
Allows to enter text into a larger text area (using the html tag TEXTAREA). For an example, see the ORSEE-original admin-only field 'remarks' defined below.
'cols': The number of chars per row in the textarea field, as passed through to the html tag. Default 40.
'rows'=>'3', The number of rows in the textarea field, as passed through to the html tag. Default 3.
'wrap': Can be 'virtual', 'physical', or 'off', and is passed through to the textarea html tag. Default is 'virtual'.

TYPE: language
Creates a select field with the installed languages (set as available to public) as options. You can use this type in custom fields if needed, but do *not* *delete* the default langauge field named 'language': This could break the system.

TYPE: invitations
Creates a checkbox list of the configured "external experiment types" (see Options/Experiment types). You can use this type in custom fields if needed, but do *not* *delete* the default invitations field named 'subscriptions': This could break the system.


*/

// DEFINITION OF PARTIPANT FORM FIELDS

function participantform__define() {
$participantform=array();

$participantform[]=array(
'mysql_column_name'=>'lname',
'name_lang'=>'lastname',
'type'=>'textline',
'subpools'=>'all',
'compulsory'=>'y',
'error_message_if_empty_lang'=>'you_have_to_lname',
'default_value'=>'',
'size'=>'40',
'maxlength'=>'100',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'search_result_sort_order'=>'lname,fname',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y'
);


$participantform[]=array(
'mysql_column_name'=>'fname',
'name_lang'=>'firstname',
'type'=>'textline',
'subpools'=>'all',
'compulsory'=>'y',
'error_message_if_empty_lang'=>'you_have_to_fname',
'default_value'=>'',
'size'=>'40',
'maxlength'=>'100',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'n',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y'
);

$participantform[]=array(
'mysql_column_name'=>'email',
'name_lang'=>'email',
'type'=>'textline',
'subpools'=>'all',
'compulsory'=>'y',
'error_message_if_empty_lang'=>'you_have_to_email_address',
'require_unique_on_create_page'=>'y',
'unique_on_create_page_error_message_if_exists_lang'=>'your_email_address_exists',
'unique_on_create_page_tell_if_deleted'=>'y',
'unique_on_create_page_email_regmail_confmail_again'=>'y',
'check_unique_on_edit_page'=>'n',
'unique_on_edit_page_error_message_if_exists_lang'=>'your_email_address_exists',
'default_value'=>'',
'size'=>'40',
'maxlength'=>'100',
'perl_regexp'=>'/^[^@ \t\r\n]+@[-_0-9a-zA-Z]+\.[^@ \t\r\n]+$/i',
'error_message_if_no_regexp_match_lang'=>'email_address_not_ok',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'link_as_email_in_lists'=>'y',
'list_in_session_pdf_list'=>'y'
);

$participantform[]=array(
'mysql_column_name'=>'language',
'name_lang'=>'language',
'type'=>'language'
);

$participantform[]=array(
'mysql_column_name'=>'subscriptions',
'name_lang'=>'invitations',
'type'=>'invitations',
'compulsory'=>'y',
'error_message_if_empty_lang'=>'at_least_one_exptype_has_to_be_selected',
'searchresult_list_in_participant_results'=>'y',
'search_result_allow_sort'=>'y'
);

$participantform[]=array(
'mysql_column_name'=>'phone_number',
'name_lang'=>'phone_number',
'type'=>'textline',
'subpools'=>'all',
'default_value'=>'',
'size'=>'20',
'maxlength'=>'30',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y'
);

$participantform[]=array(
'mysql_column_name'=>'gender',
'name_lang'=>'gender',
'type'=>'radioline',
'subpools'=>'all',
'option_values'=>'m,f',
'option_values_lang'=>'gender_m,gender_f',
'default_value'=>'',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y',
'include_in_statistics'=>'pie'
);

$participantform[]=array(
'mysql_column_name'=>'profession',
'name_lang'=>'profession',
'type'=>'select_lang',
'subpools'=>'all',
'default_value'=>'',
'include_none_option'=>'n',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y',
'include_in_statistics'=>'pie'
); 

$participantform[]=array(
'mysql_column_name'=>'field_of_studies',
'name_lang'=>'studies',
'type'=>'select_lang',
'subpools'=>'all',
'default_value'=>'',
'include_none_option'=>'n',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y',
'include_in_statistics'=>'pie'
);

$participantform[]=array(
'mysql_column_name'=>'begin_of_studies',
'name_lang'=>'begin_of_studies',
'type'=>'select_numbers',
'subpools'=>'all',
'default_value'=>'',
'value_begin'=>'func:(int) date("Y")-$settings["begin_of_studies_years_backward"]',
'value_end'=>'func:(int) date("Y")+$settings["begin_of_studies_years_forward"]',
'value_step'=>'1',
'values_reverse'=>'n',
'include_none_option'=>'y',
'search_include_in_participant_query'=>'y',
'search_include_in_experiment_assign_query'=>'y',
'searchresult_list_in_participant_results'=>'y',
'searchresult_list_in_experiment_assign_results'=>'y',
'search_result_allow_sort'=>'y',
'list_in_session_participants_list'=>'y',
'allow_sort_in_session_participants_list'=>'y',
'list_in_session_pdf_list'=>'y',
'include_in_statistics'=>'bars'
);

$participantform[]=array(
'mysql_column_name'=>'remarks',
'name_lang'=>'remarks',
'admin_only'=>'y',
'type'=>'textarea',
'rows'=>'3',
'cols'=>'70',
'wrap'=>'virtual',
'subpools'=>'all',
'default_value'=>''
);

return $participantform;
}
?>
