<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="options";
$jquery=array('arraypicker','textext','dropit','queryform','datepicker','popup');
include ("header.php");
if ($proceed) {
    $allow=check_allow('pform_default_query_edit','options_main.php');
}
if ($proceed) {
    $query_types=array('assign','deassign','participants_search_active','participants_search_all');
    if (isset($_REQUEST['type']) && $_REQUEST['type'] && in_array($_REQUEST['type'],$query_types)) $type=$_REQUEST['type'];
    else redirect('admin/options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['search_submit'])) {
        if(isset($_REQUEST['form'])) $posted_query=$_REQUEST['form']; else $posted_query=array('query'=>array());
        $posted_query_json=json_encode($posted_query);
        $done=query__save_default_query($posted_query_json,'default_'.$type);
        redirect('admin/'.thisdoc().'?type='.$type);
    }
}

if ($proceed) {

    $titles=array('assign'=>'default_search_for_assigning_participants_to_experiment',
                'deassign'=>'default_search_for_deassigning_participants_from_experiment',
                'participants_search_active'=>'default_search_for_active_participants',
                'participants_search_all'=>'default_search_for_all_participants');

    echo '<center>';
    show_message();
    $load_query=query__load_default_query($type);
    if ($type=='participants_search_active') $hide_modules=array('statusids');
    else $hide_modules=array();
    $formextra='<INPUT type="hidden" name="type" value="'.$type.'">';

    echo '<TABLE class="or_formtable" style="min-width: 80%">
            <TR><TD>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang($titles[$type]).'
                        </TD>
                </TR></TABLE>
            </TD></TR>';
    echo '<TR><TD>';
    query__show_form($hide_modules,false,$load_query,lang('save_query'),array(),"",$formextra);
    echo '</TD></TR></TABLE>';
    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';
    echo '</center>';

}
include ("footer.php");
?>