<?php
// part of orsee. see orsee.org
ob_start();

$title="configure_participant_profile_field";
$menu__area="options_main";
$jquery=array();
include ("header.php");
if ($proceed) {
    $user_columns=participant__userdefined_columns();
    if (!isset($_REQUEST['mysql_column_name']) || !isset($user_columns[$_REQUEST['mysql_column_name']]))
        redirect('admin/options_participant_profile.php');
    else $field_name=$_REQUEST['mysql_column_name'];
}

if ($proceed) {
    $allow=check_allow('pform_config_field_configure','options_participant_profile.php');
}

if ($proceed) {
    $field=orsee_db_load_array("profile_fields",$field_name,"mysql_column_name");
    $allvalues=participantform__allvalues();
    if (!isset($field['mysql_column_name'])) {
        $new=true;
        $field=array('mysql_column_name'=>$field_name,
                    'enabled'=>'y',
                    'name_lang'=>$field_name,
                    'type'=>'select_lang');
    } else {
        $new=false;
        $prop=db_string_to_property_array($field['properties']); unset($field['properties']);
        foreach ($prop as $k=>$v) $field[$k]=$v;
    }
    foreach ($allvalues as $k=>$v) if (!isset($field[$k])) $field[$k]=$v;
}

if ($proceed) {
    if ($field_name=='email') {
        $restrict_to=array('size','maxlength','error_message_if_empty_lang',
                        'perl_regexp','error_message_if_no_regexp_match_lang','unique_on_create_page_error_message_if_exists_lang',
                            'unique_on_create_page_tell_if_deleted','unique_on_create_page_email_regmail_confmail_again',
                            'unique_on_edit_page_error_message_if_exists_lang','default_value','link_as_email_in_lists');
    } else {
        $restrict_to=array_keys($allvalues);
        $restrict_to[]='enabled';
        $restrict_to[]='type';
        $restrict_to[]='name_lang';
    }

}

if ($proceed) {
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $pform_field['mysql_column_name']=$field_name;
        if (in_array('enabled',$restrict_to)) $pform_field['enabled']=($_REQUEST['enabled']=='y')?1:0;
        if (in_array('type',$restrict_to)) $pform_field['type']=$_REQUEST['type'];
        $prop_array=array();
        if (in_array('name_lang',$restrict_to)) $prop_array['name_lang']=$_REQUEST['name_lang']; else $prop_array['name_lang']=$field['name_lang'];
        foreach($allvalues as $k=>$v) {
            if ((!in_array($k,array('mysql_column_name','type','enabled','name_lang')))) {
                if (in_array($k,$restrict_to) && isset($_REQUEST[$k])) {
                    if (is_array($_REQUEST[$k])) {
                        foreach ($_REQUEST[$k] as $tk=>$tv) {
                            if ($tv) $_REQUEST[$k][$tk]=trim($tv);
                            else unset($_REQUEST[$k][$tk]);
                        }
                        $prop_array[$k]=implode(',',$_REQUEST[$k]);
                    } else $prop_array[$k]=trim($_REQUEST[$k]);
                } else {
                    $prop_array[$k]=$field[$k];
                }
            }
        }
        $pform_field['properties']=property_array_to_db_string($prop_array);
        $done=orsee_db_save_array($pform_field,"profile_fields",$field_name,"mysql_column_name");
        message(lang('changes_saved'));
        redirect('admin/'.thisdoc().'?mysql_column_name='.$field_name);
    }
}


if ($proceed) {
    echo '<center>';

    javascript__tooltip_prepare();

    echo '<FORM action="'.thisdoc().'" method="POST">';
    echo '<INPUT type="hidden" name="mysql_column_name" value="'.$field_name.'">';
    echo '<TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                    <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('configure_participant_profile_field').' '.$field_name.'
                    </TD>
                </TR></TABLE>
            </TD></TR>';

    echo '<TR><TD valign="top" width="50%">';

    echo '<TABLE width="100%" border=0>';

    echo '<TR class="tooltip" title="Name of the mysql column in which data for this field is stored"><TD>MySQL column name:</TD><TD>'.$field_name.'</TD></TR>';

    $field['enabled']=($field['enabled'])?'y':'n';
    echo '<TR class="tooltip" title="Whether ORSEE should just use this field or ignore just the respective datbase column."><TD>'.lang('enabled?').'</TD>
            <TD>'.pform_options_yesnoradio('enabled',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Language symbol expressing the name of this field. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol."><TD>Name language symbol</TD><TD>'.pform_options_inputtext('name_lang',$field).'</TD></TR>';

    echo '<TR><TD colpsan="2"><BR><B>Form field settings</B></TD></TR>';

    echo '<TR class="tooltip" title="The type of the participant profile field. Explanation appears below when selected."><TD>Type</TD><TD>
            '.pform_options_selectfield('type',array('select_lang','radioline_lang','select_numbers','textline','textarea','select_list','radioline'),$field,'type_select').'
        </TD></TR>';

    if (in_array('type',$restrict_to)) {
        echo '<script type="text/javascript">
                $(document).ready(function () {
                    toggle_form_fields();
                    $("#type_select").change(function () {
                        toggle_form_fields();
                    });

                });
                function toggle_form_fields() {
                    $(".condfield").hide();
                    var typeval = $("#type_select").val();
                    $("."+typeval).show();
                }
            </script>';
    } else {
        echo '<script type="text/javascript">
                $(document).ready(function () {
                    toggle_form_fields();
                });
                function toggle_form_fields() {
                    $(".condfield").hide();
                    $(".'.$field['type'].'").show();
                }
            </script>';
    }

    echo '<TR class="condfield select_lang"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
            A select list with a number of options. Options can be freely configured in "Options/Items for profile fields of type select_lang/radioline_lang".
            </TD></TR>';

    echo '<TR class="condfield radioline_lang"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
            A list of radio buttons. Options can be freely configured in "Options/Items for profile fields of type select_lang/radioline_lang".
            </TD></TR>';

    echo '<TR class="condfield select_numbers"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
        A select list with numbers. You can use code within the options, e.g. for a year of birth that allows all years from current-17 to current-100 you could use as Start value <span style="background: white; white-space:nowrap;">func:(int) date("Y")-100</span> and as End value <span style="background: white; white-space:nowrap;">func:(int) date("Y")-17</span>.
            </TD></TR>';

    echo '<TR class="condfield textline"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
            Asks for a line of text.
            </TD></TR>';

    echo '<TR class="condfield textarea"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
            Allows to enter text into a larger text area.
            </TD></TR>';

    echo '<TR class="condfield select_list"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
    This type is only provided for backward compatibility and import (e.g. of the default gender field in ORSEE<=2.3). For new fields, please use &quot;select_lang&quot;.
            </TD></TR>';

    echo '<TR class="condfield radioline"><TD colspan="2" style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';">
    This type is only provided for backward compatibility and import (e.g. of the default gender field in ORSEE 2). For new fields, please use &quot;radioline_lang&quot;.
            </TD></TR>';


    echo '<TR class="condfield select_lang select_numbers tooltip" title="Whether or not to include a &quot;none&quot; option (represented by value 0 and &quot;-&quot; in the select list) in addition to the options defined in &quot;Options/Items for profile fields of type select_lang&quot;. Potentially useful in conjunction with &quot;compulsory=yes&quot; in order to not have a 0-value field pre-selected when one must be chosen."><TD>Include a &quot;none&quot; option</TD>
            <TD>'.pform_options_yesnoradio('include_none_option',$field).'</TD></TR>';

    echo '<TR class="condfield select_lang tooltip" title="Whether to sort values of this field by the order predetermined in &quot;Options/Items for profile fields of type select_lang&quot; or alphabetically in the respective language.">
            <TD>Order values</TD>
            <TD>'.pform_options_selectfield('order_select_lang_values',array('alphabetically','fixed_order'),$field).'</TD></TR>';


    echo '<TR class="condfield radioline_lang tooltip" title="Whether to sort values of this field by the order predetermined in &quot;Options/Items for profile fields of type select_lang/radioline_lang&quot; or alphabetically in the respective language.">
            <TD>Order values</TD>
            <TD>'.pform_options_selectfield('order_radio_lang_values',array('alphabetically','fixed_order'),$field).'</TD></TR>';


    echo '<TR class="condfield select_numbers tooltip" title="First number in list. Must be integer."><TD>Start number
            </TD><TD>'.pform_options_inputtext("value_begin",$field,10).'
            </TD></TR>';

    echo '<TR class="condfield select_numbers tooltip" title="Last number in list. Must be integer."><TD>End number
            </TD><TD>'.pform_options_inputtext("value_end",$field,10).'
            </TD></TR>';

    echo '<TR class="condfield select_numbers tooltip" title="Step size from one to the next number."><TD>Step size
            </TD><TD>'.pform_options_inputtext("value_step",$field,10).'
            </TD></TR>';

    echo '<TR class="condfield select_numbers tooltip" title="Whether to display from largest number to smallest (yes) or smallest to largest (no)."><TD>Reverse values</TD>
            <TD>'.pform_options_yesnoradio('values_reverse',$field).'</TD></TR>';

    echo '<TR class="condfield select_list radioline tooltip" title="Option values: List of the values to be put in database. Language symbols: list of the language-table symbols that should be used to display these values. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol. If the respective language symbol does not exist, ORSEE will simply display this symbol name."><TD colspan="2">
            '.pform_options_vallanglist('option_values','option_values_lang',$field).'</TD></TR>';

    echo '<TR class="condfield textline tooltip" title="The display width of the text field (in characters)."><TD>Size
            </TD><TD>'.pform_options_inputtext("size",$field,20).'
            </TD></TR>';

    echo '<TR class="condfield textline tooltip" title="The maximal length of text (in characters) a user can add into this text field."><TD>Maximal input length
            </TD><TD>'.pform_options_inputtext("maxlength",$field,20).'
            </TD></TR>';

    echo '<TR class="condfield textarea tooltip" title="The number of chars per row in the textarea field."><TD>Columns
            </TD><TD>'.pform_options_inputtext("cols",$field,20).'
            </TD></TR>';

    echo '<TR class="condfield textarea tooltip" title="The number of rows in the textarea field."><TD>Rows
            </TD><TD>'.pform_options_inputtext("rows",$field,20).'
            </TD></TR>';

    echo '<TR class="condfield textarea tooltip" title="How the browser wraps in the text: Physical - by adding line breaks. Virtual - line-breaking just for display, not added to text. Off - no line-breaking at all."><TD>wrap:</TD><TD>'.pform_options_selectfield('wrap',array('virtual','physical','off'),$field).'</TD></TR>';

    echo '</TABLE></TD>
        <TD valign="top"><TABLE width="100%" border=0>';

    // settings

    echo '<TR><TD colpsan="2"><B>Field properties</B></TD></TR>';


    echo '<TR class="tooltip" title="Whether this form field is only displayed to administrators/researchers when editing the profile (yes), or is also presented to subjects (no)."><TD>Field is for admins only</TD>
            <TD>'.pform_options_yesnoradio('admin_only',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Sub-subjectpools to which this form field applies. Can be either the keyword &quot;all&quot; or a comma-separated list of subject pool ids (which can be seen in Options/Sub-subjectpools)."><TD>Sub-subjectpools
            </TD><TD>'.pform_options_inputtext("subpools",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="The default value of this form field (i.e. the pre-filled value on the participant profile creation form)."><TD>Default value
            </TD><TD>'.pform_options_inputtext("default_value",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="If set to pie or bars, then the field is automatically included in the participant statistics, as a pie or bar chart, respectively. This only makes sense for fields that can be aggregated (like fields of study etc.), but not for heterogenous fields like names (unless you want to see the distributions of first names in your subject pool).">
            <TD>Include in statistics</TD>
            <TD>'.pform_options_selectfield('include_in_statistics',array('n','pie','bars'),$field).'</TD></TR>';

    echo '<TR><TD colpsan="2"><B>Search properties</B></TD></TR>';

    echo '<TR class="tooltip" title="Whether this participant field should be included as a search field in the participant query (on Participants/Edit participants).">
            <TD>Include in participant search</TD>
            <TD>'.pform_options_yesnoradio('search_include_in_participant_query',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Whether this participant field should be included as a search field in the query for participants when assigning them to an experiment (on Experiment/Assign participants).">
            <TD>Include in experiment assignment search</TD>
            <TD>'.pform_options_yesnoradio('search_include_in_experiment_assign_query',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Either empty or a comma-separated list of mysql column names representing the SQL query sort order when a table is sorted by this field. If empty, ti will just take the mysql column (i.e. no tie-breaking). But if, for example, this is field &quot;lname&quot; and when we sort by last name we want first name to be the secondary sort criterium, then we would put &quot;lname,fname&quot; here.">
            <TD>Search result sort order
            </TD><TD>'.pform_options_inputtext("search_result_sort_order",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="Whether the content of this field should be interpreted as an email address such that it will be displayed with an email link when displayed in a results table."><TD>Link as email in result lists</TD>
            <TD>'.pform_options_yesnoradio('link_as_email_in_lists',$field).'</TD></TR>';

    echo '<TR><TD colpsan="2"><B>Checks</B></TD></TR>';

    echo '<TR class="tooltip" title="Whether this form field is compulsory (must be non-empty) or not."><TD>Compulsory?</TD>
            <TD>'.pform_options_yesnoradio('compulsory',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Language symbol for error message to be displayed when this field is compulsory but the field is empty when submitted. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol."><TD>Empty - Error language symbol
            </TD><TD>'.pform_options_inputtext("error_message_if_empty_lang",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="Either empty or a Perl regular expression as defined in PHP manual. When not empty, then ORSEE will perform a pattern match on the submitted value using the provided pattern, and will only accept the entry if it matches the pattern. Most usefully this is applied to textline items (e.g. email address, univeristy ID, etc.)."><TD>PERL Regular Expression
            </TD><TD>'.pform_options_inputtext("perl_regexp",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="Language symbol for error message to be displayed when the submitted entry does not match the provided pattern. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol."><TD>No pattern match - Error language symbol
            </TD><TD>'.pform_options_inputtext("error_message_if_no_regexp_match_lang",$field).'
            </TD></TR>';

    echo '<TR><TD colpsan="2"><B>Uniqueness</B></TD></TR>';

    echo '<TR class="tooltip" title="Whether, when a subject registers with the database, the entry in this field has to be unique across the whole participant subjectpool, or not. When unqiueness is required, and a value is submitted that already exists, then the registration will not be accepted.">
            <TD>Field must be unique on profile creation page</TD>
            <TD>'.pform_options_yesnoradio('require_unique_on_create_page',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Language symbol for error message on profile creation page to be displayed when the field has to be unique but the submitted entry already exists in the database. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol.">
            <TD>Not unique on profile creation - Error language symbol
            </TD><TD>'.pform_options_inputtext("unique_on_create_page_error_message_if_exists_lang",$field).'
            </TD></TR>';

    echo '<TR class="tooltip" title="Whether, when a subject creates a profile and submits a value that already exists for a field required to be unique, the system should check whether this corresponds to a subject that has a status that does not allow the subject to access a profile page, and if so it should issue an error message in this regard.">
            <TD>If not unique, tell if profile unsubscribed</TD>
            <TD>'.pform_options_yesnoradio('unique_on_create_page_tell_if_deleted',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Only applies if subject authentication method is TOKEN. Whether, when a subject creates a profile and submits a value that already exists for a field required to be unqiue, and the corresponding profile has a status that is allowed to access the subject profile, the system should resend the confirmation email (when subject had not confirmed yet) or an email with a link to the profile (when the subject had confirmed) to the registered email address.">
            <TD>If not unique, email profile access link</TD>
            <TD>'.pform_options_yesnoradio('unique_on_create_page_email_regmail_confmail_again',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Whether, when a subject changes her profile, the entry in this field has to be unique across the whole participant subjectpool, or not. When unqiueness is required, and a value is submitted that already exists, then the profile change will not be accepted.">
            <TD>Field must be unique on profile change</TD>
            <TD>'.pform_options_yesnoradio('check_unique_on_edit_page',$field).'</TD></TR>';

    echo '<TR class="tooltip" title="Language symbol for error message on profile change page to be displayed when the field has to be unique but the submitted entry already exists in the database. A language symbol (that is, an entry in the or_lang language table) can be created in Options/Languages/Add symbol.">
            <TD>Not unique on profile change- Error language symbol
            </TD><TD>'.pform_options_inputtext("unique_on_edit_page_error_message_if_exists_lang",$field).'
            </TD></TR>';





    echo '</TABLE></TD></TR>

        <TR><TD colspan="2" align="center">
            <INPUT class="button" type="submit" name="save" value="'.lang('save').'">
        </TD></TR>
        </TABLE>';

    echo '</FORM>';

    //if (!$new && check_allow('pform_config_field_delete')) {
        echo '<BR><BR>
            '.button_link('options_participant_profile_delete.php?mysql_column_name='.urlencode($field_name),
                            lang('delete'),'trash-o');
    //}


    echo '<BR><BR><A href="options_participant_profile.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>