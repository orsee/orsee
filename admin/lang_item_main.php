<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
if (isset($_REQUEST['item'])) $item=$_REQUEST['item']; else $item='';
$title="options";
$jquery=array('dropit','listtool');
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['item'])) $sent_item=$_REQUEST['item']; else $sent_item="";

    $done=false;
    $formfields=participantform__load(); $allow_cat=$sent_item;
    foreach($formfields as $f) {
        if (preg_match("/(select_lang|radioline_lang)/",$f['type']) && $sent_item==$f['mysql_column_name']) {
            $done=true;
            $item=$sent_item;
            $header=isset($lang[$f['name_lang']])?$lang[$f['name_lang']]:$f['name_lang'];
            $where="";
            $order=" order_number ";
            $allow_order=true;
            $show_part_stats=true;
            $show_id=true;
            $allow_cat='pform_lang_field';
        }
    }

    $allow=check_allow($allow_cat.'_edit','options_main.php');
}

if ($proceed) {
    if (!$done) {
        switch($sent_item) {
            case 'public_content':
                $item=$sent_item;
                $header=lang('public_content');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                break;
            case 'datetime_format':
                $item=$sent_item;
                $header=lang('datetime_format');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                break;
//          case 'help':
//              $item=$sent_item;
//              $header=lang('help');
//              $where="";
//              $order=" content_name ";
//              $show_part_stats=false;
//              $show_id=true;
//              $chnl2br=true;
//              break;
            case 'mail':
                $item=$sent_item;
                $header=lang('default_mails');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                $chnl2br=true;
                break;
            case 'default_text':
                $item=$sent_item;
                $header=lang('default_texts');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                break;
            case 'laboratory':
                $item=$sent_item;
                $header=lang('laboratories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                $chnl2br=true;
                break;
            case 'experimentclass':
                $item=$sent_item;
                $header=lang('experiment_classes');
                $where="";
                $order=lang('lang');
                $allow_order=false;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'payments_type':
                $item=$sent_item;
                $header=lang('payment_types');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'file_upload_category':
                $item=$sent_item;
                $header=lang('upload_file_categories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'events_category':
                $item=$sent_item;
                $header=lang('event_categories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'emails_mailbox':
                $item=$sent_item;
                $header=lang('email_mailboxes');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            }
    }

    //var_dump($_REQUEST);
    if ($allow_order && isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if(isset($_REQUEST['langitem_order']) && is_array($_REQUEST['langitem_order']) && count($_REQUEST['langitem_order'])>0) {
            $done=language__save_item_order($item,$_REQUEST['langitem_order']);
            message(lang('new_order_saved'));
            redirect('admin/lang_item_main.php?item='.urlencode($item));
        }
    }
}

if ($proceed) {
    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.$header.'
            </TD>';
    echo '</TR></TABLE><br>';

    if (check_allow($allow_cat.'_add')) {
        echo '  <BR>
            '.button_link('lang_item_edit.php?item='.urlencode($item).'&addit=true',
                        lang('create_new'),'plus-circle').'<BR><BR>';
    }


    // load languages
    $languages=get_languages();

    // $item already sanitized above
    if ($show_part_stats) {
        $num_p=array();
        $query="SELECT ".$item." as type_p,
            count(*) as num_p
            FROM ".table('participants')."
            GROUP BY ".$item;
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $num_p[$line['type_p']]=$line['num_p'];
        }
    }


    $query="SELECT *
            FROM ".table('lang')."
            WHERE content_type='".$item."'
            ".$where."
            ORDER BY ".$order;
    $result=or_query($query);

    $rows=array();
    while ($line=pdo_fetch_assoc($result)) {
        $row='';
        if ($show_id) {
            $row.=' <td class="small" valign=top>
                        '.$line['content_name'].'
                    </td>';
        }
        foreach ($languages as $language) {
            $row.='<td>';
            if (isset($chnl2br) && $chnl2br) $row.= nl2br(stripslashes($line[$language]));
            else $row.=stripslashes($line[$language]);
            $row.='</td>';
        }
        if ($show_part_stats) {
            if (isset($num_p[$line['content_name']])) $np=$num_p[$line['content_name']]; else $np=0;
            $row.='<td class="small">'.$np.'</td>';
        }
        $row.='<TD valign="top">';
        //$row.='<A HREF="lang_item_edit.php?item='.$item.'&id='.$line['lang_id'].'">'.lang('edit').'</A>';
        $row.=button_link('lang_item_edit.php?item='.$item.'&id='.$line['lang_id'],lang('edit'),'pencil-square-o');
        $row.='</TD>';
        $rowelem=array('content_name'=>$line['content_name'],
                        'text'=>$row);
        $rows[]=$rowelem;
    }

    $table_head=''; $thc=0;
    if ($show_id) {
        $table_head.='<TD class="small">'.lang('id').'</TD>'; $thc++;
    }
    foreach ($languages as $language) {
        $table_head.='<td class="small">'.$language.'</td>'; $thc++;
    }
    if ($show_part_stats) {
        $table_head.='<td class="small">'.lang('participants').'</td>'; $thc++;
    }
    $table_head.='<TD></TD>'; $thc++;


    if (count($rows)==0) {
        echo '<table class="or_listtable" style="min-width: 30%; max-width: 90%;"><thead>';
        echo '<tr style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
        echo $table_head.'</tr></thead>
                <tbody>';
        echo '  <tr>
                <td colspan="'.$thc.'">
                    '.lang('no_items_found').'
                </td>
            </tr>';
        echo '</tbody></TABLE>';
    } elseif ($allow_order) {
        $listrows=array(); $i=0;
        foreach($rows as $k=>$row) {
            $i++;
            $listrows[$row['content_name']]=array(
                    'display_text' => $row['content_name'],
                    'on_list' => true,
                    'allow_remove' => false,
                    'allow_drag' => true,
                    'fixed_position' => $i,
                    'cols'=> $row['text']
                    );
        }
        echo '<form action="" method="POST">';
        echo formhelpers__orderlist("langitem_list", "langitem_order", $listrows, true, lang('add'), "");
        echo '<BR><input class="button" name="save_order" type="submit" value="'.lang('save_order').'"></form>';
    } else {
        echo '<table class="or_listtable" style="min-width: 30%; max-width: 90%;"><thead>';
        echo '<tr style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
        echo $table_head.'</tr></thead>
            <tbody>';
        $shade=false;
        foreach($rows as $k=>$row) {
            echo '<tr class="small"';
            if ($shade) { echo ' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
            else { echo ' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
            echo '>';
            echo $row['text'];
            echo '</TR>';
        }
        echo '</tbody></table>';
    }

    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</CENTER>';

}
include ("footer.php");
?>