<?php
// part of orsee. see orsee.org
ob_start();

$jquery=array();
$title="options";
$menu__area="options";
include ("header.php");
if ($proceed) {
    $allow=check_allow('pform_saved_queries_view','options_main.php');
}
if ($proceed) {
    $query_types=array('participants_search_active','participants_search_all');
    if (isset($_REQUEST['type']) && $_REQUEST['type'] && in_array($_REQUEST['type'],$query_types)) $type=$_REQUEST['type'];
    else redirect('admin/options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel'] && check_allow('pform_saved_queries_delete')) {
        $dids=array();
        if (isset($_REQUEST['del']) && is_array($_REQUEST['del'])) {
            foreach($_REQUEST['del'] as $k=>$v) {
                if ($v=='y') $dids[]=$k;
            }
        }
        if (count($dids)>0) {
            $i=0; $parnames=array();
            foreach($dids as $id) {
                $i++;
                $tparname=':query_id'.$i;
                $parnames[]=$tparname;
                $pars[$tparname]=$id;
            }
            $pars[':query_type']=$type;
            $query="DELETE FROM ".table('queries')."
                    WHERE query_type=:query_type
                    AND query_id IN (".implode(",",$parnames).") ";
            $done=or_query($query,$pars);
            $number=pdo_num_rows($done);
            message ($number.' '.lang('xxx_queries_deleted'));
            if ($number>0) log__admin("query_delete","Type: ".$type.", Count: ".$number);
            redirect ("admin/options_saved_queries.php?type=".$type);
        } else {
            message(lang('error__query_delete_no_queries_selected'));
            redirect ("admin/options_saved_queries.php?type=".$type);
        }
    }
}

if ($proceed) {

    $pars=array();
    $pars[':query_type']=$type;
    $query="SELECT * FROM ".table('queries')."
        WHERE query_type = :query_type
        ORDER BY query_time DESC";
    $result=or_query($query,$pars);
    $num_rows=pdo_num_rows($result);

    $titles=array('participants_search_active'=>'saved_queries_for_active_participants',
                'participants_search_all'=>'saved_queries_for_all_participants');

    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.lang($titles[$type]).'
            </TD>';
    echo '</TR></TABLE><br>';

    if (check_allow('pform_saved_queries_delete')) {
        echo '<FORM action="'.thisdoc().'" method="POST">
                <INPUT type="hidden" name="type" value="'.$type.'">';
        echo '<TABLE width=90% border=0>
            <TD align=right>
            <input class="button" type=submit name="deletesel" value="'.lang('delete_selected').'">
            </TD></TR></TABLE>';
    }
    echo '<TABLE class="or_listtable" style="width: 90%;">';
    // header
    echo '
        <thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
        <TD>'.lang('date_and_time').'</TD>
        <TD>'.lang('query').'</TD>';
    if (check_allow('pform_saved_queries_delete')) {
        echo '<TD>
            '.lang('select_all').'
            <INPUT id="selall" type="checkbox" name="selall" value="y">
            <script language="JavaScript">
                $("#selall").change(function() {
                    if (this.checked) {
                        $("input[name*=\'del[\']").each(function() {
                            this.checked = true;
                        });
                    } else {
                        $("input[name*=\'del[\']").each(function() {
                            this.checked = false;
                        });
                    }
                });
            </script>
        </TD>';
    }
    echo '
          </TR>
          </thead>
          <tbody>
        ';

    $shade=false; $ids=array();
    if ($type=='participants_search_active') $active=true; else $active=false;
    while ($line=pdo_fetch_assoc($result)) {
        $posted_query=json_decode($line['json_query'],true);
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,$active);

        echo '<TR';
        if ($shade) $shade=false; else $shade=true;
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';

        echo '>
            <TD>'.ortime__format($line['query_time'],'hide_second:false',lang('lang')).'</TD>
            <TD>'.$pseudo_query_display.'</TD>';
        $reference=array();
        if (check_allow('pform_saved_queries_delete')) {
            echo '<TD><INPUT type="checkbox" name="del['.$line['query_id'].']" value="y"></TD';
        }
        echo '</TR>';
    }
    echo '</tbody></TABLE>';
    if (check_allow('pform_saved_queries_delete')) {
        echo '</FORM>';
    }
    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>