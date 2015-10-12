<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_participant_profile_form_template";
$jquery=array();
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['item_name'])) $item_name=$_REQUEST['item_name']; else redirect ("admin/options_profile_template.php");
}
if ($proceed) {
    if (!in_array($item_name,array('profile_form_public','profile_form_admin_part'))) redirect ("admin/options_profile_template.php");
}
if ($proceed) {
    $allow=check_allow('pform_templates_edit','options_main.php');
}

if ($proceed) {
    $t=options__load_object('profile_form_template',$item_name);
}

if ($proceed) {
    if (!isset($_REQUEST['subpool_id'])) $subpool_id=1; else $subpool_id=$_REQUEST['subpool_id'];
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
}

if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $t['item_details']['current_draft']=$_REQUEST['current_draft'];
        $t['item_details']=property_array_to_db_string($t['item_details']);
        $done=orsee_db_save_array($t,"objects",$t['item_id'],"item_id");
        log__admin("pform_templates_edit","item_name:".$t['item_name']);
        message (lang('changes_saved'));
        redirect ('admin/options_profile_template_edit.php?item_name='.$item_name.'&subpool_id='.$subpool_id);
    } elseif (isset($_REQUEST['activate']) && $_REQUEST['activate']) {
        $t['item_details']['current_template']=$t['item_details']['current_draft'];
        $t['item_details']=property_array_to_db_string($t['item_details']);
        $done=orsee_db_save_array($t,"objects",$t['item_id'],"item_id");
        log__admin("pform_templates_activate","item_name:".$t['item_name']);
        message (lang('template_draft_activated'));
        redirect ('admin/options_profile_template_edit.php?item_name='.$item_name.'&subpool_id='.$subpool_id);
    }
}

if ($proceed) {
    if (!isset($t['item_details']['current_template'])) $t['item_details']['current_template']='';
    if (!isset($t['item_details']['current_draft'])) $t['item_details']['current_draft']=$t['item_details']['current_template'];

    echo '<center>
            <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 98%">
                <TR><TD align="center">'.lang('edit_participant_profile_form_template').' '.$t['item_name'].'</TD></TR></TABLE>
        <BR>';


    echo '
        <FORM action="options_profile_template_edit.php" METHOD=POST>
        <INPUT type=hidden name="item_name" value="'.$item_name.'">
        <TABLE width="95%" border=0 cellspacing="0">
        <TR><TD>
            '.lang('display_preview_for_subjectpool').subpools__select_field('subpool_id',$subpool_id).
            '<INPUT class="button" id="change_subpool" name="change_subpool" type="submit" value="'.lang('apply').'">
        </TD></TR></TABLE></FORM>
    ';

    $edit=array();
    if (isset($subpool_id)) $edit['subpool_id']=$subpool_id;

    // form
    echo '  <FORM action="options_profile_template_edit.php" METHOD=POST>
        <INPUT type=hidden name="item_name" value="'.$item_name.'">
        <INPUT type=hidden name="subpool_id" value="'.$subpool_id.'">

        <TABLE class="or_formtable" style="max-width: 98%; width: 98%; padding: 0px;">
            <TR>
                <TD></TD>
                <TD></TD>
                <TD>
                    <INPUT class="button" id="activate_button" name="activate" type="submit" value="'.lang('activate_template_draft').'">
                </TD>
            </TR>
            <TR>
                <TD>
                    <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
                    <TR><TD align="center">'.lang('currently_active_form_template').'</TD></TR></TABLE>
                </TD>
                <TD></TD>
                <TD>
                    <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
                    <TR><TD align="center">'.lang('current_template_draft').'</TD></TR></TABLE>
                </TD>
            </TR>
            <TR>
                <TD valign="top" bgcolor="'.$color['list_shade1'].'">';
    if ($item_name=='profile_form_public') participant__show_inner_form($edit,array(),false,'current_template');
    elseif ($item_name=='profile_form_admin_part') echo participant__get_inner_admin_form($edit,array(),'current_template');
    echo '  </TD>
            <TD valign="top">';
    echo '<textarea id="current_draft" name="current_draft" cols=50 rows=40 wrap=virtual>'.
                    $t['item_details']['current_draft'].'</textarea>';
    echo '  </TD><TD valign="top" bgcolor="'.$color['list_shade2'].'">';
    if ($item_name=='profile_form_public') participant__show_inner_form($edit,array(),false,'current_draft');
    elseif ($item_name=='profile_form_admin_part') echo participant__get_inner_admin_form($edit,array(),'current_draft');
    echo '  </TD>
            </TR>
            <TR><TD></TD>
                <TD align="center">
                    <INPUT class="button" name="edit" type="submit" value="'.lang('save').'">
                </TD>
                <TD></TD>
            </TR>
        </table>
        </FORM>
        <BR>';

    // hide active button when textarea is changed
    echo '<script type="text/javascript">
            $("#current_draft").bind("input propertychange", function() {
                  $("#activate_button").attr("disabled","disabled").attr("value","'.lang('activate_template_draft').' ('.lang('first_save').')");
                  $("#change_subpool").attr("disabled","disabled").attr("value","'.lang('apply').' ('.lang('first_save').')");
            });
            </script>';

    echo '<BR><BR>
        <A href="options_profile_template.php"><i class="fa fa-level-up fa-lg" style="padding-right: 3px;"></i>'.lang('back').'</A><BR><BR>
        </center>';

}

/*
The participant profile form template contains HTML code that define the layout of the participant profile form, and placeholders to be filled by ORSEE in runtime.

It is recommended that the template defines a table. Otherwise, your are free in the layout you choose.

There are four types of placeholders:

FORM FIELDS
#field_name#: If there is a participant form field defined in config/participant_form.php that has "field_name" as its 'mysql_column_name', then this placeholder will be replaced with the HTML form item of that field.

LANGUAGE SYMBOLS
lang[lang_symbol_name]: If a language symbol is defined in the or_lang table (Options/Languages) under the name "lang_symbol_name", then this placeholder will be replaced by its value in the current language.

ERROR HIGHLIGHTING
#error_field_name#: If a 'compulsory' or 'perl_regexp' condition is not met for a participant form field named field_name after submitting, then #error_field_name# is replaced by a ' bgcolor="orange" ' (or whatever color is defined in the config) in the next form shown. That is, using this as in '<tr #error_email#>' will highlight that table row in orange if the conditions for the email field were not met.

CONDITIONAL STATEMENTS
If a construct of the form "{ #some_condition#   some content }" is found, then first #some_condition# will be evaluated, and if this evaluation yields a non-empty non-false value, then "some content" is displayed (which is not displayed otherwise). The following conditions can be used as #some_condition#:

#multiple_participant_languages_exist# evaluates to true if there are more than one ORSEE system languages available to participants.

#is_admin# evaluates to true if the form is shown in the admin section of ORSEE.

#is_not_admin# evaluates to true if the form is shown in the public section of ORSEE.

#is_subjectpool_X# evaluates to true if the  (self-selected) sub-subjectpool of the subject had the id number X. So, for example, #is_subjectpool_1# evaluates to true if this subject's subject pool is the "unspecified" default subject pool in ORSEE. The subject pool id numbers can be seen in Options/Sub-subjectpools.

Note that ORSEE assumes consistency here. That is, if a form_field is only defined for certain subpools in Options/Participant profile fields, then it should also be only shown for those subpools in this participant form template using the #is_subjectpool_X# conditional statement above. If that construction does not match, then there might be some errors in evaluating the form. As a simple example, if Options/Participant profile fields defines a compulsory form field for a subpool but that form field is not included in the form for this subpool, it will be evaluated as empty and result in an error message when submitting the participant form.

#is_part_create_form# Only available in the admin section, this evaluates to true when the form is displayed on a participant profile creation page (as opposed to a participant profile edit page), i.e. when you crate a new participant.
*/

include ("footer.php");
?>