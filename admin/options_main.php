<?php
// part of orsee. see orsee.org
ob_start();

$title="options";
$menu__area="options_main";
include ("header.php");
if ($proceed) {

    echo '
    <center>
        <TABLE width="90%" border="0">';

    $optionlist=array();
    if (check_allow('settings_view')) {
        $optionlist[]='<A HREF="options_edit.php?otype=general" class="option">'.oicon('power-off').lang('general_settings').'</A>';
        $optionlist[]='<A HREF="options_edit.php?otype=default" class="option">'.oicon('star').lang('default_values').'</A>';
    }
    if (check_allow('settings_view_colors')) $optionlist[]='<A HREF="options_colors.php" class="option">'.oicon('paint-brush').lang('colors').'</A>';
    if (check_allow('regular_tasks_show')) $optionlist[]='<A HREF="cronjob_main.php" class="option">'.oicon('history').lang('regular_tasks').'</A>';
    if (check_allow('admin_type_edit')) $optionlist[]='<A HREF="admin_type_show.php" class="option">'.oicon('graduation-cap').lang('user_types_and_privileges').'</A>';
    if (check_allow('admin_edit')) $optionlist[]='<A HREF="admin_show.php" class="option">'.oicon('users').lang('user_management').'</A>';
    options__show_main_section(lang('system_setup'),$optionlist);

    $optionlist=array();
    $optionlist[]='<A HREF="admin_edit.php" class="option">'.oicon('user').lang('preferences').'</A>';
    $optionlist[]='<A HREF="admin_pw.php" class="option">'.oicon('key').lang('change_my_password').'</A>';
    $optionlist[]='<A HREF="admin_rights.php" class="option">'.oicon('shield').lang('show_my_rights').'</A>';
    options__show_main_section(lang('my_profile'),$optionlist);

    $optionlist=array();
    if (check_allow('laboratory_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=laboratory" class="option">'.oicon('map-marker').lang('laboratories').'</A>';
    if (check_allow('subjectpool_edit')) $optionlist[]='<A HREF="subpool_main.php" class="option">'.oicon('globe').lang('sub_subjectpools').'</A>';
    if (check_allow('experimenttype_edit')) $optionlist[]='<A HREF="experiment_type_main.php" class="option">'.oicon('newspaper-o').lang('experiment_types').'</A>';
    if (check_allow('experimentclass_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=experimentclass" class="option">'.oicon('tags').lang('experiment_classes').'</A>';
    if (check_allow('participantstatus_edit')) $optionlist[]='<A HREF="participant_status_main.php" class="option">'.oicon('star').lang('participant_statuses').'</A>';
    if (check_allow('participationstatus_edit')) $optionlist[]='<A HREF="participation_status_main.php" class="option">'.oicon('check-circle-o').lang('experiment_participation_statuses').'</A>';
    options__show_main_section(lang('lab_setup'),$optionlist);

    $optionlist=array();
    if (check_allow('events_category_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=events_category" class="option">'.oicon('calendar-o').lang('event_categories').'</A>';
    if (check_allow('file_upload_category_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=file_upload_category" class="option">'.oicon('upload').lang('upload_file_categories').'</A>';
    if ($settings['enable_payment_module']=='y' && check_allow('payments_budget_edit')) $optionlist[]='<A HREF="payments_budget_main.php" class="option">'.oicon('credit-card').lang('budgets').'</A>';
    if ($settings['enable_payment_module']=='y' && check_allow('payments_type_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=payments_type" class="option">'.oicon('money').lang('payment_types').'</A>';
    if ($settings['enable_email_module']=='y' && check_allow('emails_mailbox_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=emails_mailbox" class="option">'.oicon('envelope-square').lang('email_mailboxes').'</A>';
    options__show_main_section(lang('items'),$optionlist);

    $optionlist=array();
    if (check_allow('public_content_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=public_content" class="option">'.oicon('quote-left').lang('public_content').'</A>';
    if (check_allow('mail_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=mail" class="option">'.oicon('envelope').lang('default_mails').'</A>';
//  if (check_allow('default_text_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=default_text" class="option">'.lang('default_texts').'</A>';
    if (check_allow('faq_edit')) $optionlist[]='<A HREF="faq_main.php" class="option">'.oicon('question-circle').lang('faqs').'</A>';
    $optionlist[]='<A HREF="lang_main.php" class="option">'.oicon('language').lang('languages').'</A>';
    if (check_allow('datetime_format_edit')) $optionlist[]='<A HREF="lang_item_main.php?item=datetime_format" class="option">'.oicon('clock-o').lang('datetime_format').'</A>';
    options__show_main_section(lang('communication_and_site_content'),$optionlist);


    $optionlist=array();
    if (check_allow('pform_config_field_configure')) $optionlist[]='<A HREF="options_participant_profile.php" class="option">'.oicon('file-image-o').lang('participant_profile_fields').'</A>';
    if (check_allow('pform_templates_edit')) $optionlist[]='<A HREF="options_profile_template.php" class="option">'.oicon('newspaper-o').lang('participant_profile_form_template').'</A>';
    if (check_allow('pform_anonymization_fields_edit')) $optionlist[]='<A HREF="options_ordered_lists.php?list=anonymize_profile_list" class="option">'.oicon('bars').lang('fields_to_anonymize_in_anonymization_bulk_action').'</A>';

    options__show_main_section(lang('participant_profile'),$optionlist);


    if (check_allow('pform_lang_field_edit')) {
        $optionlist=array();
        $formfields=participantform__load();
        foreach($formfields as $f) {
            if (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
                $to='<A HREF="lang_item_main.php?item='.$f['mysql_column_name'].'" class="option">'.oicon('bars');
                if (isset($lang[$f['name_lang']])) $to.=$lang[$f['name_lang']];
                else $to.=$f['name_lang'];
                $to.='</A>';
                $optionlist[]=$to;
            }
        }
        options__show_main_section(lang('profile_field_language_items'),$optionlist);
    }

    $optionlist=array();
    if (check_allow('pform_default_query_edit')) {
        $optionlist[]='<A HREF="options_default_queries.php?type=participants_search_active" class="option">'.oicon('bars').lang('default_search_for_active_participants').'</A>';
        $optionlist[]='<A HREF="options_default_queries.php?type=participants_search_all" class="option">'.oicon('bars').lang('default_search_for_all_participants').'</A>';
        $optionlist[]='<A HREF="options_default_queries.php?type=assign" class="option">'.oicon('bars').lang('default_search_for_assigning_participants_to_experiment').'</A>';
        $optionlist[]='<A HREF="options_default_queries.php?type=deassign" class="option">'.oicon('bars').lang('default_search_for_deassigning_participants_from_experiment').'</A>';
    }
    if (check_allow('pform_saved_queries_view')) {
        $optionlist[]='<A HREF="options_saved_queries.php?type=participants_search_active" class="option">'.oicon('bars').lang('saved_queries_for_active_participants').'</A>';
        $optionlist[]='<A HREF="options_saved_queries.php?type=participants_search_all" class="option">'.oicon('bars').lang('saved_queries_for_all_participants').'</A>';
    }
    options__show_main_section(lang('default_and_saved_queries'),$optionlist);


    if (check_allow('pform_results_lists_edit')) {
        $optionlist=array();
        $optionlist[]='<A HREF="options_ordered_lists.php?list=result_table_search_active" class="option">'.oicon('bars').lang('columns_in_search_results_table_for_active_participants').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=result_table_search_all" class="option">'.oicon('bars').lang('columns_in_search_results_table_for_all_participants').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=result_table_assign" class="option">'.oicon('bars').lang('columns_in_results_table_for_assign_query').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=result_table_search_unconfirmed" class="option">'.oicon('bars').lang('columns_in_search_results_table_for_unconfirmed_profiles').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=result_table_search_duplicates" class="option">'.oicon('bars').lang('columns_in_search_results_table_for_profile_duplicates').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=experiment_assigned_list" class="option">'.oicon('bars').lang('columns_in_list_of_assigned_participants').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=session_participants_list" class="option">'.oicon('bars').lang('columns_in_session_participants_list').'</A>';
        $optionlist[]='<A HREF="options_ordered_lists.php?list=session_participants_list_pdf" class="option">'.oicon('bars').lang('columns_in_pdf_session_participants_list').'</A>';
        if ($settings['enable_email_module']=='y') $optionlist[]='<A HREF="options_ordered_lists.php?list=email_participant_guesses_list" class="option">'.oicon('bars').lang('email_module_participant_guesses_list').'</A>';
        options__show_main_section(lang('result_lists'),$optionlist);
    }

    $optionlist=array();
    if (check_allow('import_data')) $optionlist[]='<A HREF="import_prepare.php" class="option">'.lang('prepare_data_import').'</A>';
    options__show_main_section(lang('other'),$optionlist);

    echo '  </TABLE>';
    echo '</center>';
}
include ("footer.php");
?>