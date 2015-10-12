<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="participants_main";
$title="participants";
include ("header.php");
if ($proceed) {

    // participants summary

    echo '  <center>
        <BR><BR>
        <table class="or_panel">
            <TR><TD colspan=2>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                        '.participants__count_participants().' '.lang('xxx_participants_registered').'.
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD>&nbsp;&nbsp;&nbsp;</TD>
                <TD><center>

                <TABLE border=1 style="border-collapse: collapse; border: 1px solid #AAA;">';

    $exptypes=load_external_experiment_types();
    $pstatuses=participant_status__get_statuses();

    $query="SELECT count(*) as num_part, subscriptions, status_id
            FROM ".table('participants')."
            GROUP BY subscriptions, status_id";
    $result=or_query($query);
    $part_nums=array();
    while ($line = pdo_fetch_assoc($result)) {
        $etemp=db_string_to_id_array($line['subscriptions']);
        foreach ($etemp as $et) {
            if (!isset($part_nums[$et][$line['status_id']])) $part_nums[$et][$line['status_id']]=0;
            $part_nums[$et][$line['status_id']]=$part_nums[$et][$line['status_id']]+$line['num_part'];
        }
    }


    echo '<TR><TD colspan=2></TD><TD colspan="'.count($pstatuses).'"><B>'.lang('participant_status').'</B></TD></TR>';
    echo '<TR><TD colspan=2></TD>';
    foreach ($pstatuses as $status_id=>$status) echo '<TD align="right">'.$status['name'].'&nbsp;&nbsp;&nbsp;&nbsp;</TD>';
    $first=true;
    foreach ($exptypes as $exptype_id=>$exptype) {
        echo '<TR>';
        if ($first) {
            echo '<TD rowspan="'.count($exptypes).'"><B>'.lang('registered_for_xxx_experiments_xxx').'</B></TD>';
            $first=false;
        }
        echo '<TD>'.$exptype[lang('lang')].'</TD>';
        foreach ($pstatuses as $status_id=>$status) {
            echo '<TD align="right">';
            if (isset($part_nums[$exptype_id][$status_id])) echo $part_nums[$exptype_id][$status_id];
            else echo '0';
            echo '&nbsp;&nbsp;&nbsp;&nbsp;</TD>';
        }
        echo '</TR>';
    }

    echo '</TABLE></center>
        <BR>

        <TABLE>';

    echo '
            <TR>
                <TD>';
                if (check_allow('participants_unconfirmed_edit')) echo '
                        <A HREF="participants_unconfirmed.php">'.
                            lang('registered_but_not_confirmed_xxx').':</A>';
                   else echo lang('registered_but_not_confirmed_xxx');
    echo '  </TD>
                    <TD>
                        '.participants__count_participants("status_id='0'").'
                    </TD>
                </TR>
                <TR>
                    <TD>
                        '.lang('from_this_older_than_4_weeks_xxx').':
                    </TD>
                    <TD>';
                        $now=time();
                        $before=$now-(60*60*24*7*4);
                        $tstring="status_id='0' AND creation_time < ".$before;
                    echo participants__count_participants($tstring).'
                    </TD>
                </TR>
        </TABLE>

        <BR>

        </TD>
        </TR>

        <TR>
            <TD colspan="2">
                <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                <TR>
        ';
        if (check_allow('participants_show')) echo '
                <TD>
                    '.button_link('participants_show.php?active=true',
                                        lang('edit_active_participants'),'list-alt').'
                </TD>
                <TD>
                    '.button_link('participants_show.php',
                    lang('edit_all_participants'),'search').'
                </TD>
            ';
        if (check_allow('participants_edit')) echo '
                <TD>
                    '.button_link('participants_edit.php',lang('add_participant'),'plus-circle').'
                </TD>
            ';
        if (check_allow('participants_duplicates')) echo '
                <TD>
                    '.button_link('participants_duplicates.php',lang('search_for_duplicates'),'magnet').'
                </TD>
            ';
        echo '
                </TR>
                </TABLE>
            </TD></TR>
            </TABLE>
        </center>';

}
include ("footer.php");
?>