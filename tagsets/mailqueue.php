<?php
// part of orsee. see orsee.org

function mailqueue__show_mailqueue($experiment_id="",$limit=-1) {
    global $lang, $color, $options,$proceed;

if ($proceed) {
    $pars=array();

    if ($limit==-1 && $experiment_id && isset($options['mailqueue_experiment_number_of_entries_per_page']) && $options['mailqueue_experiment_number_of_entries_per_page']) {
        $limit=$options['mailqueue_experiment_number_of_entries_per_page'];
    } elseif ($limit==-1 && isset($options['mailqueue_number_of_entries_per_page']) && $options['mailqueue_number_of_entries_per_page']) {
        $limit=$options['mailqueue_number_of_entries_per_page'];
    } else {
        $limit=100;
    }

    if (isset($_REQUEST['os']) && $_REQUEST['os']>0) $offset=$_REQUEST['os']; else $offset=0;

    if ($experiment_id) {
        $equery=" AND experiment_id=:experiment_id ";
        $pars[':experiment_id']=$experiment_id;
    } else $equery="";

    if (isset($_REQUEST['deleteall']) && $_REQUEST['deleteall']) $dall=true; else $dall=false;
    if (isset($_REQUEST['deleteallonpage']) && $_REQUEST['deleteallonpage']) $dallpage=true; else $dallpage=false;
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel']) $dsel=true; else $dsel=false;
}

if ($proceed) {
    if ( $dall || $dallpage || $dsel ) {

        if ($experiment_id) $allow=check_allow('mailqueue_edit_experiment','experiment_mailqueue_show?experiment_id='.$experiment_id);
        else $allow=check_allow('mailqueue_edit_all','mailqueue_show.php');

        $where_clause=" WHERE mail_id IS NOT NULL ".$equery;

        $ok=false;
        if ($dall) $ok=true;


        if ($dallpage) {
            $tallids=array();
            if (isset($_REQUEST['allids']) && trim($_REQUEST['allids'])) $tallids=explode(",",trim($_REQUEST['allids']));
            if (count($tallids)>0) {
                $i=0; $parnames=array();
                foreach($tallids as $id) {
                    $i++;
                    $tparname=':mailid'.$i;
                    $parnames[]=$tparname;
                    $pars[$tparname]=$id;
                }
                $where_clause.=" AND mail_id IN (".implode(",",$parnames).") ";
                $ok=true;
            } else {
                message(lang('error__mailqueue_delete_no_emails_selected'));
                $ok=false;
            }
        }

        if ($dsel) {
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
                    $tparname=':mailid'.$i;
                    $parnames[]=$tparname;
                    $pars[$tparname]=$id;
                }
                $where_clause.=" AND mail_id IN (".implode(",",$parnames).") ";
                $ok=true;
            } else {
                message(lang('error__mailqueue_delete_no_emails_selected'));
                $ok=false;
            }
        }

        if ($ok) {
            $query="DELETE FROM ".table('mail_queue').$where_clause;
            //echo $query;

            $done=or_query($query,$pars);
            $number=pdo_num_rows($done);
            message ($number.' '.lang('xxx_emails_deleted_from_queue'));

            if ($experiment_id) {
                if ($number>0) log__admin("mailqueue_delete_entries","Experiment: ".$experiment_id.", Count: ".$number);
            } else {
                if ($number>0) log__admin("mailqueue_delete_entries","Count: ".$number);
            }
        }
        if ($experiment_id) {
            redirect ("admin/experiment_mailqueue_show.php?experiment_id=".$experiment_id);
        } else {
            redirect ("admin/mailqueue_show.php");
        }
    }
}

if ($proceed) {

    $pars=array();
    if ($experiment_id) {
        $equery=" AND experiment_id=:experiment_id ";
        $pars[':experiment_id']=$experiment_id;
    } else $equery="";
    $pars[':offset']=$offset;
    $pars[':limit']=$limit;
    $query="SELECT * FROM ".table('mail_queue')."
        WHERE mail_id IS NOT NULL ".
        $equery.
        " ORDER BY timestamp DESC
        LIMIT :offset , :limit";
    $result=or_query($query,$pars);
    $num_rows=pdo_num_rows($result);

    if ($experiment_id && check_allow('mailqueue_edit_experiment')) {
        echo '<FORM action="experiment_mailqueue_show.php" method="POST">
            <INPUT type="hidden" name="experiment_id" value="'.$experiment_id.'">';
    } elseif (check_allow('mailqueue_edit_all')) {
        echo '<FORM action="mailqueue_show.php" method="POST">';
    }

    echo '<TABLE width=90% border=0>
        <TR><TD width=50%>';
    //echo '<FONT class="small">'.lang('query').': '.$query.'</FONT><BR><BR>';
    echo '&nbsp;</TD>
        <TD align=right width=50%>';

    if (check_allow('mailqueue_edit_all')) {
        echo '
            <TABLE width="100%" border="0">
            <TR><TD width="33%" align="right">
            <input class="button" type=submit name="deleteall" value="'.lang('delete_all').'">
            </TD><TD width="33%" align="right">
            <input class="button" type=submit name="deleteallonpage" value="'.lang('delete_all_on_page').'">
            </TD><TD width="33%" align="right">
            <input class="button" type=submit name="deletesel" value="'.lang('delete_selected').'">
            </TD></TR>
            </TABLE>
            ';
        }
    echo '</TD></TR></TABLE>';

    if ($offset > 0) echo '['.log__link('os='.($offset-$limit)).lang('previous').'</A>]';
    else echo '['.lang('previous').']';
    echo '&nbsp;&nbsp;';
    if ($num_rows >= $limit) echo '['.log__link('os='.($offset+$limit)).lang('next').'</A>]';
    else echo '['.lang('next').']';


    if (check_allow('participants_edit')) {
        echo javascript__edit_popup();
    }

    echo '<TABLE class="or_listtable" style="width: 90%;"><thead>';
    // header
    echo '
        <thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
        <TD>'.lang('id').'</TD>
        <TD>'.lang('date_and_time').'</TD>
        <TD>'.lang('email_type').'</TD>
        <TD>'.lang('email_recipient').'</TD>
        <TD>'.lang('reference').'</TD>
        <TD>'.lang('error').'</TD>';
    if (check_allow('mailqueue_edit_all')) {
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

    $shade=false; $ids=array(); $experiment_ids=array(); $entries=array();
    while ($line=pdo_fetch_assoc($result)) {
        $ids[]=$line['mail_id'];
        if ($line['experiment_id']) $experiment_ids[]=$line['experiment_id'];
        $entries[]=$line;
    }
    $experiments=experiment__load_experiments_for_ids($experiment_ids);
    foreach ($entries as $line) {
        echo '<TR';
        if ($shade) $shade=false; else $shade=true;
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';

        echo '>
            <TD>'.$line['mail_id'].'</TD>
            <TD>'.ortime__format($line['timestamp'],'hide_second:false',lang('lang')).'</TD>
            <TD>'.$line['mail_type'].'</TD>
            <TD>'.$line['mail_recipient'];
        if (check_allow('participants_edit')) echo ' <FONT class="small">'.javascript__edit_popup_link($line['mail_recipient']).'</FONT>';
        echo '</TD>
            <TD>';
        $reference=array();
        if ($line['experiment_id']) $reference[]='Experiment: <A HREF="experiment_show.php?experiment_id='.$line['experiment_id'].'">'.$experiments[$line['experiment_id']]['experiment_name'].'</A>';
        if ($line['session_id']) $reference[]='Session: <A HREF="session_edit.php?session_id='.$line['session_id'].'">'.$line['session_id'].'</A>';
        if ($line['bulk_id']) $reference[]='Bulk email: '.$line['bulk_id'];
        echo implode('<BR>',$reference);
        echo '</TD>
            <TD>'.$line['error'].'</TD>';
        if (check_allow('mailqueue_edit_all')) {
            echo '<TD><INPUT type="checkbox" name="del['.$line['mail_id'].']" value="y"></TD';
        }
        echo '</TR>';
    }
    echo '</tbody></TABLE>';
    if (check_allow('mailqueue_edit_all')) {
        echo '<INPUT type="hidden" name="allids" value="'.implode(",",$ids).'">';
        echo '</FORM>';
    }
    return $num_rows;
}
}


?>
