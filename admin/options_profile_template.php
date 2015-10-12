<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="participant_profile_form_template";
include ("header.php");
if ($proceed) {
    $allow=check_allow('pform_templates_edit','options_main.php');
}

if ($proceed) {
    if (!isset($_REQUEST['subpool_id'])) $subpool_id=1; else $subpool_id=$_REQUEST['subpool_id'];
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
}

if ($proceed) {

    echo '<center>';

    echo '
        <FORM action="options_profile_template.php" METHOD="GET">
        <TABLE width="90%" border=0 cellspacing="0">
        <TR><TD>
            '.lang('display_preview_for_subjectpool').subpools__select_field('subpool_id',$subpool_id).
            '<INPUT class="button" id="change_subpool" name="change_subpool" type="submit" value="'.lang('apply').'">
        </TD></TR></TABLE></FORM>
    ';

    echo '<TABLE class="or_formtable">';
    echo '<TR>
            <TD></TD>
            <TD>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
                <TR><TD align="center">'.lang('currently_active_form_template').'</TD></TR></TABLE>
            </TD>
            <TD>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
                <TR><TD align="center">'.lang('current_template_draft').'</TD></TR></TABLE>
            </TD>
            <TD></TD>
        </TR>';

    $edit=array();
    if (isset($subpool_id)) $edit['subpool_id']=$subpool_id;

    $query="SELECT *
            FROM ".table('objects')."
            WHERE item_type='profile_form_template'
            ORDER BY item_id ";
    $result=or_query($query);
    while ($t=pdo_fetch_assoc($result)) {
        $details=db_string_to_property_array($t['item_details']);
        if ($t['item_name']=='profile_form_public') {
            echo '<TR><TD valign="top">'.lang('profile_form_public').'</TD>
                    <TD valign="top" bgcolor="'.$color['list_shade1'].'">';
            participant__show_inner_form($edit,array(),false,'current_template');
            echo '</TD>
                    <TD valign="top" bgcolor="'.$color['list_shade2'].'">';
            if ($details['current_template']!=$details['current_draft'])
                participant__show_inner_form($edit,array(),false,'current_draft');
            echo '</TD><TD valign="top">'.button_link('options_profile_template_edit.php?item_name='.
                            $t['item_name'],lang('edit'),'pencil-square-o').'
                    </TD></TR>';
        } elseif ($t['item_name']=='profile_form_admin_part') {
            echo '<TR><TD valign="top">'.lang('profile_form_admin_part').'</TD>
                    <TD valign="top" bgcolor="'.$color['list_shade1'].'">';
            echo participant__get_inner_admin_form($edit,array(),'current_template');
            echo '</TD>
                    <TD valign="top" bgcolor="'.$color['list_shade2'].'">';
            if ($details['current_template']!=$details['current_draft'])
                echo participant__get_inner_admin_form($edit,array(),'current_draft');
            echo '</TD><TD valign="top">'.button_link('options_profile_template_edit.php?item_name='.
                            $t['item_name'],lang('edit'),'pencil-square-o').'
                    </TD></TR>';
        }
    }

    echo '</TABLE>';

    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</CENTER>';

}
include ("footer.php");
?>