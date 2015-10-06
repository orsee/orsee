<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="statistics";
$title="subject_pool_statistics";
include("header.php");
if ($proceed) {
    $allow=check_allow('statistics_participants_show','statistics_main.php');
}

if ($proceed) {

    if (isset($_REQUEST['all']) && $_REQUEST['all']) {
        $all=true;
        $title_add=lang('for_all_profiles_in_database');
    } else {
        $all=false;
        $title_add=lang('for_active_subjects_in_pool');
    }

    $browsable=true;

    $restrict=array();
    if (isset($_REQUEST['restrict']) && array($_REQUEST['restrict'])) {
        $posted=$_REQUEST['restrict'];
        foreach ($posted as $s=>$valarr) {
            if (is_array($valarr)) {
                foreach ($valarr as $k=>$v) {
                    if ($v=='y') $restrict[$s][$k]=true;
                }
            }
        }
    }

    if ($all) $condition=array();
    else $condition=array('clause'=>participant_status__get_pquery_snippet('eligible_for_experiments'),
                    'pars'=>array()
                    );
    $stats_data=stats__get_data($condition,'stats',$restrict);
    $_SESSION['stats_data']=$stats_data;

    echo '<center>';

    if ($browsable) {
        echo '<FORM action="'.thisdoc().'" METHOD="POST">';
        echo '<INPUT type="hidden" name="all" value="'.urlencode($all).'">';
    }
    echo '<TABLE border=0 cellspacing="0" width="100%">';
    echo '<TR><TD align="center">';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'"><TR><TD>
            '.lang('subject_pool_statistics').' '.$title_add.'
            </TD>';
    echo '<TD>';
    if ($all) {
        echo '<P align="right">'.button_link(thisdoc(),lang('stats_show_for_active'),'dot-circle-o').'</p>';
    } else {
        echo '<P align="right">'.button_link(thisdoc().'?all=true',lang('stats_show_for_all'),'circle-o').'</p>';
    }
    echo '</TD>';
    echo '</TR></TABLE>';
    echo '  </TD></TR>';

    if ($browsable) {
        echo '<TR><TD align="center"><BR><INPUT class="button" type="submit" name="filter" value="'.lang('apply_filter').'"><BR></TD></TR>';
    }

    foreach ($stats_data as $k=>$table) {
        if (isset($table['data']) && is_array($table['data']) && count($table['data'])>0) $show=true;
        else $show=false;
        if ($show) {
            $out=stats__stats_display_table($table,$browsable,$restrict);
            echo '<TR><TD align="center">';
            echo '<TABLE class="or_formtable" style="width: 90%">
                    <TR><TD colspan="2">
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.$table['title'].'
                            </TD>
                        </TR></TABLE>
                    </TD></TR>';
            if ($table['charttype']=='none') {
                echo '<TR>';
                echo '<TD colspan="2" valign="top" align="center">
                            <TABLE width="50%"><R><TD>'.$out.'</TD></TR></TABLE></TD>';
                echo '</TR>';
            } else {
                echo '<TR>';
                echo '<TD valign="top" align="left" style="width: 50%">'.$out.'</TD>';
                echo '<TD valign="top" align="center" style="width: 50%">';
                echo '<img border="0" src="statistics_graph.php?stype='.$k.'">';
                echo '</td>';
                echo '</TR>';
            }
            echo '</TABLE>';
            echo '<BR><BR>';
            echo '</TD><TR>';
        }
    }
    echo '</TABLE>';
    if ($browsable) {
        echo '<BR><BR><INPUT class="button" type="submit" name="filter" value="'.lang('apply_filter').'"><BR><BR>';
        echo '</FORM>';
    }

    echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';
    echo '</center>';

}
include("footer.php");
?>