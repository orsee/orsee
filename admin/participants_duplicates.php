<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="participants";
$title="search_for_duplicates";
$jquery=array('popup');
include ("header.php");
if ($proceed) {
    $allow=check_allow('participants_duplicates','participants_main.php');
}
if ($proceed) {
    if (isset($_REQUEST['save_data'])) {


        redirect('admin/'.thisdoc());
    }
}

if ($proceed) {

    echo '<center>';

    show_message();
}

if ($proceed) {
    if(isset($_REQUEST['search'])) {

        $pform_fields=participantform__load();
        $fields=array();
        foreach ($pform_fields as $f) {
            $fields[]=$f['mysql_column_name'];
        }
        $field_names=array();
        foreach ($pform_fields as $f) {
            $field_names[$f['mysql_column_name']]=lang($f['name_lang']);
        }

        // sanitize search_for
        $columns=array();
        if (isset($_REQUEST['search_for']) && is_array($_REQUEST['search_for'])) {
            foreach ($_REQUEST['search_for'] as $k=>$v) if (in_array($k,$fields)) $columns[]=$k;
        }

        if (count($columns)==0) {
            message(lang('no_data_columns_selected'));
            redirect('admin/'.thisdoc());
        } else {
            $query="SELECT count(*) as num_matches, ".implode(', ',$columns)."
                    FROM ".table('participants')."
                    GROUP BY ".implode(', ',$columns)."
                    HAVING num_matches>1
                    ORDER BY num_matches DESC";
            $result=or_query($query); $dupvals=array();
            while ($line = pdo_fetch_assoc($result)) {
                $dupvals[]=$line;
            }
            if (check_allow('participants_edit')) {
                echo javascript__edit_popup();
            }
            $part_statuses=participant_status__get_statuses();
            $cols=participant__get_result_table_columns('result_table_search_duplicates');

            echo '<TABLE class="or_listtable"><thead>';
            echo '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
            echo '<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</TD>';
            echo participant__get_result_table_headcells($cols,false);
            echo '</TR></thead>
                    <tbody>';
            $num_cols=count($cols)+1;
            foreach ($dupvals as $dv) {
                $mvals=array(); $pars=array(); $qclause=array();
                foreach ($columns as $c) {
                    $mvals[]=$field_names[$c].': '.$dv[$c];
                    $pars[':'.$c]=$dv[$c];
                    $qclause[]=' '.$c.' = :'.$c.' ';
                }
                echo '<TR><TD colspan="'.$num_cols.'"><B>'.implode(", ",$mvals).'</B></TD></TR>';
                $query="SELECT * FROM ".table('participants')."
                        WHERE ".implode(" AND ",$qclause)."
                        ORDER BY creation_time";
                $result=or_query($query,$pars); $shade=false;
                while ($p = pdo_fetch_assoc($result)) {
                    echo '<tr class="small"';
                    if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                    else echo 'bgcolor="'.$color['list_shade2'].'"';
                    echo '>';
                    echo '<TD bgcolor="'.$color['content_background_color'].'"></TD>';
                    echo participant__get_result_table_row($cols,$p);
                    echo '</tr>';
                    if ($shade) $shade=false; else $shade=true;
                }
            }
            echo '</tbody></TABLE>';
        }
    } else {

        $pform_fields=participantform__load();
        $field_names=array();
        foreach ($pform_fields as $f) {
            $field_names[$f['mysql_column_name']]=lang($f['name_lang']);
        }

        echo '<FORM action="participants_duplicates.php" method="POST">';
        echo '<B></B>';

        echo '<TABLE class="or_formtable"><TR><TD>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('search_duplicates_on_the_following_combined_characteristics').'
                        </TD>
                </TR></TABLE>
                </TD></TR>
                <TR><TD>';
        $num_cols=4; $c=0;
        echo '<TABLE><TR>';
        foreach ($field_names as $m=>$n) {
            $c++;
            if ($c>$num_cols) {
                echo '</TR><TR>';
                $c=1;
            }
            echo '<TD><INPUT type="checkbox" name="search_for['.$m.']" value="y">'.$n.'</TD>';
        }
        if ($c<$num_cols) for($i=$c; $i<$num_cols; $i++) echo '<TD></TD>';
        echo '</TR><TR><TD align="center" colspan="'.$num_cols.'">
                <INPUT class="button" type="submit" name="search" value="'.lang('search').'">
                </TD></TR>';
        echo '</TABLE>';
        echo '</TD></TR></TABLE>';
        echo '</FORM>';
    }
}

if ($proceed) {
    echo '</center>';
}
include ("footer.php");
?>