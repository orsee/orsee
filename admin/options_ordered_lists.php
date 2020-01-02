<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="options";
$jquery=array('dropit','listtool');
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['list'])) $list=$_REQUEST['list']; else $list="";
}

if ($proceed) {
    $result_lists=array('result_table_search_active','result_table_search_all',
                'result_table_assign','result_table_search_duplicates',
                'experiment_assigned_list','session_participants_list','session_participants_list_pdf',
                'email_participant_guesses_list','result_table_search_unconfirmed','anonymize_profile_list');
    if (!in_array($list,$result_lists)) redirect ('admin/options_main.php');
}

if ($proceed) {
    if ($list=='result_table_search_active') {
        $header=lang('columns_in_search_results_table_for_active_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='result_table_search_all') {
        $header=lang('columns_in_search_results_table_for_all_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='result_table_assign') {
        $header=lang('columns_in_results_table_for_assign_query');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='result_table_search_duplicates') {
        $header=lang('columns_in_search_results_table_for_profile_duplicates');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='result_table_search_unconfirmed') {
        $header=lang('columns_in_search_results_table_for_unconfirmed_profiles');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='experiment_assigned_list') {
        $header=lang('columns_in_list_of_assigned_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='session_participants_list') {
        $header=lang('columns_in_session_participants_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='session_participants_list_pdf') {
        $header=lang('columns_in_pdf_session_participants_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='email_participant_guesses_list') {
        $header=lang('email_module_participant_guesses_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_option='sortby_radio';
        $list_add_option_name=lang('sort_table_by');
    } elseif ($list=='anonymize_profile_list') {
        $header=lang('fields_to_anonymize_in_anonymization_bulk_action');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_anonymization_fields_edit';
        $list_add_option='field_value';
        $list_add_option_name=lang('anonymized_dummy_value');
        $button_text=lang('save');
    }
    if (!isset($cols)) redirect ('admin/options_main.php');
}

if ($proceed && $allow_check) {
    $allow=check_allow($allow_check,'options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if(isset($_REQUEST['item_order']) && is_array($_REQUEST['item_order']) && count($_REQUEST['item_order'])>0) {
            $details=array();
            if (isset($_REQUEST['sortby']) && $_REQUEST['sortby']) {
                $details=array(trim($_REQUEST['sortby'])=>array('default_sortby'=>1));
            }
            if (isset($_REQUEST['field_values']) && $_REQUEST['field_values'] && is_array($_REQUEST['field_values'])) {
                foreach ($_REQUEST['field_values'] as $field=>$field_value) {
                    if (isset($details[$field])) {
                        $details[$field]['field_value']=$field_value;
                    } else {
                        $details[$field]=array('field_value'=>$field_value);
                    }
                }
            }
            $done=options__save_item_order($list,$_REQUEST['item_order'],$details);
            message(lang('changes_saved'));
            redirect('admin/options_ordered_lists.php?list='.urlencode($list));
        }
    }
}

if ($proceed) {
    $pars=array(':item_type'=>$list);
    $query="SELECT *
            FROM ".table('objects')."
            WHERE item_type= :item_type
            ORDER BY order_number";
    $result=or_query($query,$pars);

    $rows=array();
    while ($line=pdo_fetch_assoc($result)) {
        $rows[$line['item_name']]=$line;
    }

    if ($list_add_option)  {
        $listrows=options__ordered_lists_get_current($cols,$rows,$list_add_option);
        $headers='<TD></TD><TD align="center">'.str_replace(" ","<BR>",lang('sort_table_by')).'</TD>';
        $headers='<TD></TD><TD align="center">'.$list_add_option_name.'</TD>';
    } else {
        $listrows=options__ordered_lists_get_current($cols,$rows);
        $headers='';
    }

    echo '<center>';
    echo '<form action="" method="POST">';
    echo '<TABLE class="or_formtable">
            <TR><TD>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.$header.'
                        </TD>
                </TR></TABLE>
            </TD></TR>';
    echo '<TR><TD align="center">';
    echo formhelpers__orderlist("ordered_list", "item_order", $listrows, false, lang('add'),$headers);
    echo '<input class="button" style="display: block;" name="save_order" type="submit" value="';
    if (isset($button_text) && $button_text) {
        echo $button_text;
    } else {
        echo lang('save_order');
    }
    echo '">';
    echo '</TD></TR></TABLE>';

    echo '</form>';

    echo '<BR><BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</CENTER>';

}
include ("footer.php");
?>