<?php
// part of orsee. see orsee.org

function query__show_form($hide_modules,$experiment=array(),$load_query="",$button_title='Search and show',$saved_queries=array(),$status_query="",$formextra="") {
    global $lang, $color;

    if (is_array($experiment) && isset($experiment['experiment_id']) && $experiment['experiment_id']) $experiment_id=$experiment['experiment_id']; else $experiment_id="";

    $prototypes = query__get_query_form_prototypes($hide_modules,$experiment_id,$status_query);
    //echo '<pre>'; var_dump($prototypes); echo '</pre>';
    $done=query__echo_form_javascript($prototypes,$load_query);
    $pastitems = "";
    $pastitemsdata = "";
    if (is_array($saved_queries) && count($saved_queries)>0) {
        foreach($saved_queries as $id => $query){
            $decoded = json_decode($query,true);
            $pastitems = $pastitems . '<a style="text-align: left;" onclick="javascript:loadFromObj(pastQueries[' . $id . ']); return false;">' . query__display_pseudo_query(query__get_pseudo_query_array($decoded['query'])) . '</a>';
            $pastitemsdata = $pastitemsdata . $query;
            if((count($saved_queries)-1) != $id){
                $pastitems = $pastitems . '<hr>';
                $pastitemsdata = $pastitemsdata . ',';
            }
        }
    }
    echo '<script>var pastQueries = [' . $pastitemsdata . '];</script>';

    // display form table
    echo '  <form id="queryForm" action="'.thisdoc().'" method="POST">';
    if ($formextra) echo $formextra;
    if ($experiment_id) echo '<INPUT type="hidden" name="experiment_id" value="'.$experiment_id.'">';
    echo '  <TABLE border=0 width=100%>
            <TR>
                <TD align=left>
                    <TABLE width=100% border=0>
                        <TR>
                            <TD width="80" align=left style="font-size: 12pt; font-weight: bold;">
                                '.str_replace(" ","&nbsp;",trim(lang('query_select_all'))).'
                            </TD>
                            <TD>&nbsp;&nbsp;</TD>
                            <TD >
                                <ul id="addDropdown" class="query_add">
                                    <li><A HREF="#" class="button fa-plus-circle">'.lang('add_condition').'</A>
                                        <ul id="protoDropdown">
                                        </ul>
                                    </li>
                                </ul>
                            </TD>
                            <TD align=right>
                                <button class="button fa-eraser" style="font-size: 8pt;" onclick="javascript:clearQuery(); return false;">'.lang('reset_query_form').'</button>
                            </TD>';
    if ($pastitemsdata) {
        echo '              <TD align=right width="205">
                                <ul id="savedDropdown" class="past_queries">
                                    <li>
                                        <A HREF="#" class="button fa-file-text">'.lang('load_saved_query').'</A>
                                        <ul id="saveDropdown">
                                        ' . $pastitems . '
                                        </ul>
                                    </li>
                                </ul>
                            </TD>';
    }
    echo '                  </TR>
                    </TABLE>
                </TD>
            </TR>';

    echo '<TR><TD>
            <table id="queryTable" width="100%">';

    echo '
                <tbody>

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=4>
                        </td>
                    </tr>
                </tfoot>
            </table>
            </TD></TR>';
    echo '      <TR>
                    <TD>
                    <TABLE border=0 width=100%><TR>';

    echo '
                        <TD align=right>
                            <input type="hidden" name="search_submit" value="true">
                            <button name="search_submit" class="fa-search button" type="submit">'.$button_title.'</button>
                        </TD>
                    </TR></TABLE>
                    </TD>
            </TR>
        </TABLE>
        </form>';
}


function query__extract_string(&$string, $start, $end) {
    $string = " ".$string;
    $ini = strpos($string, $start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    $tmp = substr($string, $ini, $len);
    $string = substr_replace($string, '', $ini, $len);
    return $tmp;
}


function query__echo_form_javascript($prototypes,$load_query="") {

            function pos_prototype($icon='bars') {
                $dragbuttonstyle=
                $out='';
                $out.='\'<td class="queryControl"><button class="fa-'.$icon.' dragHandle" style="';
                $out.='font-family: FontAwesome; height: 2em; width: 2em; font-size: 1em';
                $out.='" onclick="return false;"></button></td>\';';
                return $out;
            }

            echo '<script>
                    var position_index=0;
                    var logop_index=1;
                    var field_index=2;
                    var deletion_index=3;
            ';
            if ($load_query) echo 'var jsonData = '.$load_query.';
            ';
            echo '  var deletionPrototype = \'<td class="queryControl"><i class="fa fa-times-circle fa-lg" style="color: red;" onclick="javascript:removeFromQuery($(this).parent().parent());"></i></td>\';
                    var positionPrototype = '.pos_prototype('bars').'
                    var positionPrototypeOpenBracket = '.pos_prototype('arrows').'
                    var positionPrototypeCloseBracket = '.pos_prototype('arrows-v').'
                </script>';


        $tmp = array();
        foreach($prototypes as $proto){
            $htmlJs = query__extract_string($proto['content'], '<script type="text/javascript">', '</script>');
            $tmp[$proto['type']] = array(
                'type' => $proto['type'],
                'displayName' => $proto['displayname'],
                'html' => $proto['content'],
                'jsEval' => $htmlJs,
                'placeholder' => $proto['field_name_placeholder']
            );
        }
        echo "<script type='text/javascript'>var Ptypes = ";
        echo json_encode($tmp);
        echo ";
            buildDropdown();
            ";
        echo "</script>";

}

function query__strip_ws_subqueries_recursively($subqueries) {
    foreach($subqueries as $k=>$subquery) {
        $subqueries[$k]['clause']['query'] = trim(preg_replace('/\s+/', ' ', $subqueries[$k]['clause']['query']));
        if (isset($subqueries[$k]['subqueries'])) $subqueries[$k]['subqueries']=query__strip_ws_subqueries_recursively($subqueries[$k]['subqueries']);
    }
    return $subqueries;
}

function query__make_like_list($list,$columnname) {
    $list_array=explode(",",$list);
    $like_array=array(); $pars=array();
    $i=0;
    foreach ($list_array as $key=>$value) {
        $like_array[]=$columnname." LIKE :".$columnname.$i." ";
        $pars[':'.$columnname.$i]='%|'.$value.'|%';
        $i++;
    }
    return array('par_names'=>implode(" OR ",$like_array),'pars'=>$pars);
}

function query__make_enquoted_list($list,$parname="id") {
    $list_array=explode(",",$list);
    $par_names=array(); $pars=array(); $i=0;
    foreach ($list_array as $key=>$value) {
        $par_names[]=':'.$parname.$i;
        $pars[':'.$parname.$i]=trim($value);
        $i++;
    }
    return array('par_names'=>implode(",",$par_names),'pars'=>$pars);
}

function query__get_subqueries($clause,$subqueries,$resolve_subqueries=false) {
    foreach($subqueries as $k=>$subquery) {
        if(isset($subquery['subqueries'])) $subquery['clause']=query__get_subqueries($subquery['clause'],$subquery['subqueries'],$resolve_subqueries);
        if ($resolve_subqueries) {
            // execute subquery
            $ids=array();
            $result=or_query($subquery['clause']['query'],$subquery['clause']['pars']);
            while ($line=pdo_fetch_assoc($result)) $ids[]=$line['id'];
            $clause['query']=str_replace("#subquery".$k."#","'".implode("', '",$ids)."'",$clause['query']);
        } else {
            $clause['query']=str_replace("#subquery".$k."#",$subquery['clause']['query'],$clause['query']);
            foreach ($subquery['clause']['pars'] as $p=>$v) $clause['pars'][$p]=$v;
        }
    }
    return $clause;
}

function query__get_query($query_array,$query_id,$additional_clauses,$sort,$resolve_subqueries=false) {
    $i=0; $pars=array();
    $query="SELECT * from ".table('participants')." ";
    if (count($query_array['clauses'])>0 || count($additional_clauses)>0) $query.="WHERE ";
    if (count($additional_clauses)>0) {
        $add_queries=array();
        foreach ($additional_clauses as $add_clause) {
            foreach ($add_clause['pars'] as $p=>$v) {
                $add_clause['query']=preg_replace('/'.$p.'([^0-9])/',$p.'_'.$i.'\\1',$add_clause['query'].' ');
                $pars[$p.'_'.$i]=$v;
            }
            $i++;
            $add_queries[]=$add_clause['query'];
        }
        $query.=implode(' AND ',$add_queries);
    }
    if (count($additional_clauses)>0 && count($query_array['clauses'])>0) $query.=' AND (';
    foreach ($query_array['clauses'] as $k=>$q) {
        $query.="\n";
        if ($q['ctype']=='bracket_open') {
            if ($q['op']) $query.=' '.$q['op'];
            $query.=' '.$q['clause']['query'].' ';
        } elseif ($q['ctype']=='bracket_close') {
            $query.=' '.$q['clause']['query'].' ';
        } else {
            if (isset($q['subqueries'])) $q['clause']=query__get_subqueries($q['clause'],$q['subqueries'],$resolve_subqueries);
            foreach ($q['clause']['pars'] as $p=>$v) {
                $q['clause']['query']=preg_replace('/'.$p.'([^0-9])/',$p.'_'.$i.'\\1',$q['clause']['query'].' ');
                $pars[$p.'_'.$i]=$v;
            }
            $i++;
            $query.=' '.$q['op'].' ('.$q['clause']['query'].') ';
        }
    }
    if (count($additional_clauses)>0 && count($query_array['clauses'])>0) $query.=' ) ';
    if (isset($query_array['limit'])) {
        $query.="\n ORDER BY rand(";
        if ($query_id) {
            $query.=':query_id';
            $pars[':query_id']= (int) $query_id;
        }
        $query.=") LIMIT :limit "; // parametrizing limit only works well with PDO::ATTR_EMULATE_PREPARES = FALSE
        $pars[':limit']= $query_array['limit'];
    }
    if ($sort) { // sort is filtered through whitelisting
        if (isset($query_array['limit'])) {
            $query="SELECT * FROM (".$query.") as participants ORDER BY ".$sort;
        } else {
            $query=$query." ORDER BY ".$sort;
        }
    }
    // strip whitespace
    $query=trim(preg_replace('/\s+/', ' ', $query));
    return array('query'=>$query,'pars'=>$pars);
}



function query__pseudo_query_not_without($params) {
    if ($params['not']) $text=lang('without');
    else $text=lang('only');
    return $text;
}

function query__pseudo_query_not_not($params) {
    if ($params['not']) $text=lang('not').' ';
    else $text='';
    return $text;
}


function query__display_pseudo_query($pseudo_query_array,$active=false) {
    global $color;
    // get max level
    $maxlevel=0;
    foreach ($pseudo_query_array as $key=>$entry) {
        if ($entry['level']>$maxlevel) $maxlevel=$entry['level'];
        $next_level=''; $previous_level='';
        if (isset($pseudo_query_array[$key+1])) {
            if ($entry['level']<$pseudo_query_array[$key+1]['level']) $next_level='higher';
            elseif ($entry['level']>$pseudo_query_array[$key+1]['level']) $next_level='lower';
        }
        if (isset($pseudo_query_array[$key-1])) {
            if ($entry['level']<$pseudo_query_array[$key-1]['level']) $previous_level='higher';
            elseif ($entry['level']>$pseudo_query_array[$key-1]['level']) $previous_level='lower';
        }
        $pseudo_query_array[$key]['next_level']=$next_level;
        $pseudo_query_array[$key]['previous_level']=$previous_level;
    }
    $out='';
    $out.='<TABLE border=0>';
    $numcol=$maxlevel+2+$maxlevel-1;
    if ($active) $select_phrase=lang('query_select_all_active'); else $select_phrase=lang('query_select_all');
    $out.='<TR><TD colspan="'.($numcol).'"><B>'.$select_phrase.'</B></TD></TR>';
    $thiscol=0;
    foreach ($pseudo_query_array as $entry) {
        //$entry['text']=str_replace(" ","&nbsp;",$entry['text']);
        if ($entry['previous_level']=='') {
            $out.= '<TR>';
            for ($i=1; $i<=$entry['level']; $i++) {
                $out.= '<TD></TD>'; $thiscol++;
            }
            $out.= '<TD>'.$entry['op_text'].'</TD>'; $thiscol++;
        }
        if ($entry['next_level']=='') {
            $span=$numcol-$thiscol;
            $out.= '<TD colspan="'.$span.'">'.$entry['text'].'</TD>';
            $out.= '</TR>';
            $thiscol=0;
        } else {
            $out.= '<TD>'.$entry['text'].'</TD>'; $thiscol++;
        }
    }
    $out.='</TABLE>';
    return $out;
}

function query_show_query_result($query_arr,$type="participants_search_active",$allow_sort=true) {
    global $lang, $color, $settings;
    $allow_edit=check_allow('participants_edit');

    // prepare edit popup
    if (($type=='participants_search_active' || $type=='participants_search_all' || $type=='participants_unconfirmed') && $allow_edit) {
        echo javascript__edit_popup();
    }

    $result=or_query($query_arr['query'],$query_arr['pars']);
    $count_results=pdo_num_rows($result);

    echo '<B>'.$count_results.' '.lang('xxx_participants_in_result_set').'</B>';
    if ($type=='assign') echo '<BR>'.lang('only_ny_assigned_part_showed');
    elseif($type=='deassign') echo '<BR>'.lang('only_assigned_part_ny_reg_shownup_part_showed');
    echo '<BR><BR>';
    if ($type=='participants_search_active' || $type=='participants_search_all') query__resulthead_participantsearch();
    elseif ($type=='assign' || $type=='deassign') query__resulthead_assign($type);

    if ($type=='participants_search_active') $cols=participant__get_result_table_columns('result_table_search_active');
    elseif ($type=='participants_search_all') $cols=participant__get_result_table_columns('result_table_search_all');
    elseif ($type=='participants_unconfirmed') $cols=participant__get_result_table_columns('result_table_search_unconfirmed');
    else $cols=participant__get_result_table_columns('result_table_assign');



    echo '<table class="or_listtable" style="width: 95%;">';
    echo '<thead>';
    echo '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
    echo participant__get_result_table_headcells($cols,$allow_sort);
    echo '</TR>';
    echo '</thead><tbody>';

    $shade=false; $assign_ids=array();
    while ($p=pdo_fetch_assoc($result)) {
        if ($type=='participants_unconfirmed') $assign_ids[]=$p['email'];
        else $assign_ids[]=$p['participant_id'];
        echo '<tr class="small"';
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
            else echo 'bgcolor="'.$color['list_shade2'].'"';
        echo '>';
        echo participant__get_result_table_row($cols,$p);
        echo '</tr>';
        if ($shade) $shade=false; else $shade=true;
    }
    echo '</tbody></table>';

    return $assign_ids;
}

function query__headcell($name,$sort="",$allow_sort=true) {
    global $color;
    $celltag='td';

    $out='';
    if (!isset($_REQUEST['focus'])) $_REQUEST['focus']="";
    if (!isset($_REQUEST['experiment_id'])) $_REQUEST['experiment_id']="";
    if (!isset($_REQUEST['session_id'])) $_REQUEST['session_id']="";
    if (!isset($_REQUEST['active'])) $_REQUEST['active']="";
    $out.= '
        <'.$celltag.' class="small"';
    if ($allow_sort && isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']==$sort && $sort) $out.= ' style="background: '.$color['list_header_highlighted_background'].'"';
    $out.= '>';
    if ($allow_sort && $sort) {
        $out.= '<A HREF="'.thisdoc().'?search_sort='.urlencode($sort);
        if ($_REQUEST['experiment_id']) $out.= '&experiment_id='.$_REQUEST['experiment_id'];
        if ($_REQUEST['session_id']) $out.= '&session_id='.$_REQUEST['session_id'];
        if ($_REQUEST['focus']) $out.= '&focus='.$_REQUEST['focus'];
        if ($_REQUEST['active']) $out.= '&active='.$_REQUEST['active'];
        $out.= '">';
    }
    $out.= '<FONT class="small"';
    if ($allow_sort && isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']==$sort && $sort) $out.= ' style="color: '.$color['list_header_highlighted_textcolor'].';"';
    else $out.= ' style="color: '.$color['list_header_textcolor'].';"';
    $out.= '>';
    $out.= $name;
    $out.= '</FONT>';
    if ($allow_sort && $sort) $out.= '</A>';
    $out.= '</'.$celltag.'>';
    return $out;
}

function query__headcell_new($name,$sort="",$allow_sort=true) {
    global $color;
    $celltag='td';

    $out='';
    if (!isset($_REQUEST['focus'])) $_REQUEST['focus']="";
    if (!isset($_REQUEST['experiment_id'])) $_REQUEST['experiment_id']="";
    if (!isset($_REQUEST['session_id'])) $_REQUEST['session_id']="";
    if (!isset($_REQUEST['active'])) $_REQUEST['active']="";
    if ($allow_sort && $sort) {
        $out.= '<FORM action="'.thisdoc().'?search_sort='.urlencode($sort);
        if ($_REQUEST['experiment_id']) $out.= '&experiment_id='.$_REQUEST['experiment_id'];
        if ($_REQUEST['session_id']) $out.= '&session_id='.$_REQUEST['session_id'];
        if ($_REQUEST['focus']) $out.= '&focus='.$_REQUEST['focus'];
        if ($_REQUEST['active']) $out.= '&active='.$_REQUEST['active'];
        $out.= '" method=POST>';
    }
    $out.= '
        <'.$celltag.' class=small';
    if ($allow_sort && isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']==$sort && $sort) $out.= ' style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';"';
    $out.= '>';
    if ($allow_sort && $sort) {
        $out.= '<A HREF="'.thisdoc().'?search_sort='.urlencode($sort);
        if ($_REQUEST['experiment_id']) $out.= '&experiment_id='.$_REQUEST['experiment_id'];
        if ($_REQUEST['session_id']) $out.= '&session_id='.$_REQUEST['session_id'];
        if ($_REQUEST['focus']) $out.= '&focus='.$_REQUEST['focus'];
        if ($_REQUEST['active']) $out.= '&active='.$_REQUEST['active'];
        $out.= '">';
    }
    $out.= '<FONT class="small"';
    if ($allow_sort && isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']==$sort && $sort) $out.= ' color="'.$color['list_header_highlighted_textcolor'].'"';
    $out.= '>';
    $out.= $name;
    $out.= '</FONT>';
    if ($allow_sort && $sort) $out.= '</A>';
    $out.= '</'.$celltag.'>';
    return $out;
}
function query__load_default_sort($type,$experiment_id=0) {
    //type can be: participants_search_active, participants_search_all, assign, deassign, session_list

    if ($type=='participants_search_active') $cols=participant__get_result_table_columns('result_table_search_active');
    elseif ($type=='participants_search_all') $cols=participant__get_result_table_columns('result_table_search_all');
    else $cols=participant__get_result_table_columns('result_table_assign');

    $pform_columns=participant__load_all_pform_fields();

    $default_sort='participant_id';

    foreach ($cols as $k=>$arr) {
        if (isset($arr['item_details']['default_sortby']) && $arr['item_details']['default_sortby']) {
            $default_sort=$k;
            if (isset($pform_columns[$k]['sort_order']) && $pform_columns[$k]['sort_order']) $default_sort=$pform_columns[$k]['sort_order'];
        }
    }
    return $default_sort;
}

function query__get_sort($type,$search_sort,$experiment_id=0) {
    //sanitizes sort string
    //type can be: participants_search_active, participants_search_all, assign, deassign, session_list

    if ($type=='participants_search_active') $cols=participant__get_result_table_columns('result_table_search_active');
    elseif ($type=='participants_search_all') $cols=participant__get_result_table_columns('result_table_search_all');
    elseif ($type=='session_participants_list') $cols=participant__get_result_table_columns('session_participants_list');
    elseif ($type=='session_participants_list_pdf') $cols=participant__get_result_table_columns('session_participants_list_pdf');
    else $cols=participant__get_result_table_columns('result_table_assign');

    $pform_columns=participant__load_all_pform_fields();

    $search_ok=false;
    foreach ($cols as $k=>$arr) {
        if (isset($pform_columns[$k]['sort_order']) && $pform_columns[$k]['sort_order']) {
            if ($search_sort==$pform_columns[$k]['sort_order']) $search_ok=true;
        } elseif (isset($cols[$k]['sort_order']) && $search_sort==$cols[$k]['sort_order']) {
            $search_ok=true;
        } elseif ($search_sort==$k) {
            $search_ok=true;
        }
    }

    if (!$search_ok) $search_sort=query__load_default_sort($type,$experiment_id);
    return $search_sort;
}

function query__get_bulkactions() {
    global $color;
    $bulkactions=array();
    // don't use ' in text!

    if (check_allow('participants_bulk_mail')) {
        // BULK EMAIL
        $display_text=lang('send_bulk_mail');
        $inv_langs=lang__get_part_langs();
        $html=' <center>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%;">
                    <TR><TD align="center">
                        '.lang('send_bulk_mail_to').' #xyz_participants#
                </TD></TR></TABLE>
                <input class="bforminput" type="hidden" name="action" value="bulkmail">
                <TABLE class="or_formtable" style="width: 90%;">';
        foreach ($inv_langs as $inv_lang) {
            if (count($inv_langs) > 1) {
                $html.= '<TR><TD colspan=2>
                                                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$inv_lang.':
                        </TD>
                        </TR></TABLE>
                        </TD></TR>';
            }
            if (isset($_REQUEST['message_subject_'.$inv_lang])) {
                $tsubject=$_REQUEST['message_subject_'.$inv_lang];
            } else $tsubject='';
            if (isset($_REQUEST['message_text_'.$inv_lang])) {
                $ttext=$_REQUEST['message_text_'.$inv_lang];
            } else $ttext='';
            $html.= '   <TR>
                    <TD>'.lang('subject').':</TD>
                    <TD><input class="bforminput" type="text" name="message_subject_'.$inv_lang.'" size="50" max-length="200" value="'.$tsubject.'"></TD>
                </TR><TR>
                    <TD valign="top">'.lang('message_text').':</TD>
                    <TD><textarea class="bforminput" name="message_text_'.$inv_lang.'" rows="20" cols="50" wrap="virtual">'.$ttext.'</textarea></TD>
                </TR>';
        }
        $html.= '<TR><TD colspan="2" align="center"><INPUT id="popupsubmit" class="button" type="submit" name="popupsubmit" value="'.lang('send').'"></TD></TR>
                </TABLE></center>';
        $bulkactions['bulkmail']=array('display_text'=>$display_text,'html'=>$html);
    }

    if (check_allow('participants_bulk_participant_status')) {
        // PARTICIPANT STATUS
        $display_text=lang('set_participant_status');
        if (isset($_REQUEST['new_status'])) {
            $new_status=$_REQUEST['new_status'];
        } else $new_status='';
        if (isset($_REQUEST['remark'])) {
            $remark=$_REQUEST['remark'];
        } else $remark='';
        $status_select=participant_status__select_field('new_status',$new_status,array(),'bforminput');
        $html=' <center>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%;">
                    <TR><TD align="center">
                        '.lang('set_participant_status_for').' #xyz_participants#
                </TD></TR></TABLE>
                <input class="bforminput" type="hidden" name="action" value="status">
                <TABLE class="or_formtable" style="width: 90%;">
                <TR>
                    <TD>'.lang('new_status').':</TD>
                    <TD>'.$status_select.'</TD>
                </TR>
                <TR>
                    <TD valign="top">'.lang('add_remark_to_profile').':</TD>
                    <TD><textarea class="bforminput" name="remark" rows="5" cols="30" wrap="virtual">'.$remark.'</textarea></TD>
                </TR>


                <TR><TD colspan="2" align="center"><INPUT id="popupsubmit" class="button" type="submit" name="popupsubmit" value="'.lang('set_status').'"></TD></TR>
                </TABLE></center>
                ';
        $bulkactions['status']=array('display_text'=>$display_text,'html'=>$html);
    }

    if (check_allow('participants_bulk_profile_update')) {
        // PROFILE UPDATE REQUEST
        $display_text=lang('set_profile_update_request');
        if (isset($_REQUEST['new_pool'])) {
            $new_pool=$_REQUEST['new_pool'];
        } else $new_pool='';
        if (isset($_REQUEST['new_profile_update_status'])) {
            $new_profile_update_status=$_REQUEST['new_profile_update_status'];
        } else $new_profile_update_status='';
        if (isset($_REQUEST['do_pool_transfer'])) {
            $do_pool_transfer=$_REQUEST['do_pool_transfer'];
        } else $do_pool_transfer='';
        $pool_select=subpools__select_field('new_pool',$new_pool,array(),'bforminput');
        $html=' <center>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%;">
                    <TR><TD align="center">
                        '.lang('set_profile_update_request_for').' #xyz_participants#
                </TD></TR></TABLE>
                <input class="bforminput" type="hidden" name="action" value="profile_update">
                <TABLE class="or_formtable" style="width: 90%;">
                <TR>
                    <TD>'.lang('set_profile_update_request_status_equal_to').
                        ' <SELECT name="new_profile_update_status" class="bforminput">
                            <OPTION value="y"';
        if ($new_profile_update_status=='y') $html.=' SELECTED';
        $html.='>'.lang('active').'</OPTION>
                            <OPTION value="n"';
        if ($new_profile_update_status!='y') $html.=' SELECTED';
        $html.='>'.lang('inactive').'</OPTION>
                            </SELECT>
                    </TD>
                </TR>
                <TR>
                    <TD><INPUT class="bforminput" type="checkbox" name="do_pool_transfer" value="y"';
        if ($do_pool_transfer=='y') $html.=' CHECKED';
        $html.='>'.lang('upon_profile_update_transfer_to_subject_pool').' '.$pool_select.'</TD>
                </TR>
                <TR><TD colspan="2" align="center"><INPUT id="popupsubmit" class="button" type="submit" name="popupsubmit" value="'.lang('set_status').'"></TD></TR>
                </TABLE></center>
                ';
        $bulkactions['profile_update']=array('display_text'=>$display_text,'html'=>$html);
    }
    
    if (check_allow('participants_bulk_anonymization')) {
        // BULK ANONYMIZATION
        $display_text=lang('anonymize_profiles');
        if (isset($_REQUEST['new_status'])) {
            $new_status=$_REQUEST['new_status'];
        } else $new_status='';
        if (isset($_REQUEST['do_status_change'])) {
            $do_status_change=$_REQUEST['do_status_change'];
        } else $do_status_change='';
        $anon_fields=participant__get_result_table_columns('anonymize_profile_list');
        $status_select=participant_status__select_field('new_status',$new_status,array(),'bforminput');
        $html=' <center>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%;">
                    <TR><TD align="center">
                        '.lang('anonymize_profiles_for').' #xyz_participants#
                </TD></TR></TABLE>
                <input class="bforminput" type="hidden" name="action" value="bulk_anonymization">
                <TABLE class="or_formtable" style="width: 90%;">
                <TR>
                    <TD>'.lang('fields_will_be_anonymized_as_follows').':<br>';
        foreach ($anon_fields as $field_name=>$anon_field) {
            $html.=$anon_field['display_text'].'=&gt;'.$anon_field['item_details']['field_value'].'<br>';
        } 
        $html.='<br>'.lang('disclaimer_anonymize_profiles').'</TD>
                </TR>
                <TR>
                    <TD><INPUT class="bforminput" type="checkbox" name="do_status_change" value="y"';
        if ($do_status_change=='y') $html.=' CHECKED';
        $html.='>'.lang('upon_anonymization_change_status_to').' '.$status_select.'</TD>
                </TR>
                <TR><TD align="center"><INPUT id="popupsubmit" class="button" type="submit" name="popupsubmit" value="'.lang('profile_anonymize').'"></TD></TR>
                </TABLE></center>
                ';
        $bulkactions['bulk_anonymization']=array('display_text'=>$display_text,'html'=>$html);
    }
    return $bulkactions;
}


function query__resulthead_participantsearch() {
    global $color;

    echo '<TABLE border="0" width="95%"><TR>';

    $bulkactions=query__get_bulkactions();
    if (count($bulkactions)>0) {
        echo '<div id="bulkPopupDiv" class="bulkpopupDiv" style=" background: '.$color['popup_bgcolor'].'; color: '.$color['popup_text'].';">
                <div align="right"><button class="b-close button fa-backward popupBack">'.lang('back_to_results').'</button></div>
                <div id="bulkPopupContent" style="margin-left: 20px; margin-top: 0px;"></div>
            </div>
            <script type="text/javascript">
                var bulkactions = ';
        echo json_encode($bulkactions);
        echo ';
                $(document).ready(function(){
                    $.each(bulkactions, function(actionName, action){
                        var item = $.parseHTML("<li><a>" + action.display_text + "</a></li>");
                        $(item).on("click", function(){
                            bulkaction(actionName);
                        });
                        $("#bulkDropdown").append(item);
                    });
                });
                var bulkBpopup;
                function bulkaction(act){
                    var participant_count = $("input[name*=\'sel[\']:checked").length;
                    var parstr = participant_count + " '.lang('selected_participants').'";
                    var str = bulkactions[act].html;
                    str = str.replace("#xyz_participants#", parstr);
                    $("#bulkPopupContent").html("");
                    $("#bulkPopupContent").append($.parseHTML(str));
                    bulkBpopup = $("#bulkPopupDiv").bPopup({
                        contentContainer: "#bulkPopupContent",
                        amsl: 50,
                        positionStyle: "fixed",
                        modalColor: "'.$color['popup_modal_color'].'",
                        opacity: 0.8
                        });
                    $("#popupsubmit").click(function(event){
                        event.preventDefault();
                        $(".bforminput").each(function(){
                            var $input = $( this );
                            var tval = "";
                            if ($input.is(":checkbox")) {
                                if ($input.prop("checked")) tval="y";
                                else tval="n";
                            } else {
                                tval=$input.val();
                            }
                            var tstr = \'<input type="hidden" name="\'+$input.prop("name")+\'" value="\'+tval+\'" />\';
                            $("#bulkactionform").append($.parseHTML(tstr));
                        });
                        bulkBpopup.close();
                        $("#bulkactionform").submit();
                    });
                }
            </script>';
        echo '<TD>';
        echo '<TABLE border="0" class="or_panel" style="width: auto;">';
        echo '<TR><TD>'.lang('for_all_selected_participants').'</TD>
                <TD><ul id="bulkactionDropdown" class="bulkaction">
                        <li><A HREF="#" class="button fa-group">'.lang('do___').'</A>
                            <ul id="bulkDropdown">

                            </ul>
                        </li>
                    </ul>
                </TD>';
        echo '</TR>';
        echo '</TABLE>';
        echo '
        <script type="text/javascript">
        $(document).ready(function(){
            $("#bulkactionDropdown").dropit(
                {
                    action: "mouseenter",
                    beforeShow: function(){
                        $("#bulkactionDropdown .dropit-submenu").css("width", "250px");
                    }
                }
            )
        });
        </script>';
        echo '</TD>';
    }

    echo '<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</TD>';

    // back to query form button
    $cgivars=array();
    if (isset($_REQUEST['active']) && $_REQUEST['active']) $cgivars[]='active=true';
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $cgivars[]='experiment_id='.$_REQUEST['experiment_id'];
    echo '<TD><A HREF="'.thisdoc();
    if (count($cgivars)>0) echo '?'.implode("&",$cgivars);
    echo '" class="button fa-backward" style="font-size: 8pt">'.lang('back_to_query_form').'</A>';
    echo '</TD>';

    // save query button
    $cgivars=array();
    $cgivars[]="save_query=true";
    if(isset($_REQUEST['search_sort'])) $cgivars[]='search_sort='.urlencode($_REQUEST['search_sort']);
    if (isset($_REQUEST['active']) && $_REQUEST['active']) $cgivars[]='active=true';
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $cgivars[]='experiment_id='.$_REQUEST['experiment_id'];
    echo '<TD><A HREF="'.thisdoc();
    if (count($cgivars)>0) echo '?'.implode("&",$cgivars);
    echo '" class="button fa-floppy-o">'.lang('save_query').'</A>';
    echo '</TD>';

    echo '</TR></TABLE>';

}

function query__resulthead_assign($type='assign') {

    echo '<TABLE border="0" width="95%">';

    echo '<TR><TD>';
    // assign/deassign
    echo '<TABLE border="0" style="outline: 1px solid black;"><TR>';
    if ($type=='assign') {
        echo '<TD><INPUT class="button" type=submit name="addselected" value="'.lang('assign_only_marked_participants').'"></TD>';
        echo '<TD><INPUT class="button" type=submit name="addall" value="'.lang('assign_all_participants_in_list').'"></TD>';
    } else {
        echo '<TD><INPUT class="button" type=submit name="dropselected" value="'.lang('remove_only_marked_participants').'"></TD>';
        echo '<TD><INPUT class="button" type=submit name="dropall" value="'.lang('remove_all_participants_in_list').'"></TD>';
    }
    echo '</TR>';
    echo '</TABLE>';
    echo '</TD>';

    echo '<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</TD>';

    // back to query form button
    $cgivars=array();
    if (isset($_REQUEST['active']) && $_REQUEST['active']) $cgivars[]='active=true';
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $cgivars[]='experiment_id='.$_REQUEST['experiment_id'];
    echo '<TD><A HREF="'.thisdoc();
    if (count($cgivars)>0) echo '?'.implode("&",$cgivars);
    echo '" class="button fa-backward" style="font-size: 8pt">Back to query form</A>';
    echo '</TD>';

    echo '</TR></TABLE>';

}

function query__get_permanent($experiment_id=0) {
    $perm_queries=array();
    if ($experiment_id) {
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT * FROM ".table('queries')."
                WHERE experiment_id= :experiment_id
                AND permanent = 1
                ORDER BY query_time";
    } else {
        $pars=array();
        $query="SELECT * FROM ".table('queries')."
                WHERE permanent = 1
                AND experiment_id IN (
                    SELECT experiment_id FROM ".table('experiments')."
                    WHERE experiment_finished='n')
                ORDER BY query_time";
    }
    $result=or_query($query,$pars);
    while ($q=pdo_fetch_assoc($result)) {
            $q['properties']=db_string_to_property_array($q['properties']);
            $perm_queries[]=$q;
    }
    return $perm_queries;
}

function participant__get_permanent_query_participants() {
    $active_clause=participant_status__get_pquery_snippet("eligible_for_experiments");
    $participants=array();
    $query="SELECT * FROM ".table('participants')."
            WHERE apply_permanent_queries = 1
            AND ".$active_clause."
            ORDER BY creation_time";
    $result=or_query($query);
    while ($p=pdo_fetch_assoc($result)) $participants[]=$p;
    return $participants;
}

function query__apply_permanent_queries() {
    global $settings;
    $continue=true; $target='';
    $num_queries=0; $num_p=0; $num_assigned=0;

    if ($continue) {
        if ($settings['allow_permanent_queries']!='y') $continue=false;
    }

    if ($continue) {
        $ppart=array();
        $ppart=participant__get_permanent_query_participants();
        if (count($ppart)==0) $continue=false;
    }

    if ($continue) {
        $pqu=array();
        $pqu=query__get_permanent();
        if (count($pqu)==0) $continue=false;
        else $num_queries=count($pqu);
    }

    if ($continue) {
        $query_assigned=array();
        foreach ($ppart as $p) {
            $num_p++;
            foreach ($pqu as $q) {
                $experiment=orsee_db_load_array("experiments",$q['experiment_id'],"experiment_id");
                if (!isset($experiment['experiment_id'])) $continue=false;
                if ($continue) {
                    $posted_query=json_decode($q['json_query'],true);
                    $query_array=query__get_query_array($posted_query['query']);
                    $active_clause=array('query'=>participant_status__get_pquery_snippet("eligible_for_experiments"),'pars'=>array());
                    $exptype_clause=array('query'=>"subscriptions LIKE (:experiment_ext_type)",
                                        'pars'=>array(':experiment_ext_type'=>"%|".$experiment['experiment_ext_type']."|%"));
                    $notyetassigned_clause=array('query'=>"participant_id NOT IN (SELECT participant_id FROM ".table('participate_at').
                                                " WHERE experiment_id= :experiment_id)",
                                                'pars'=>array(':experiment_id'=>$experiment['experiment_id']));
                    $additional_clauses=array($active_clause,$exptype_clause,$notyetassigned_clause);
                    $query=query__get_query($query_array,time(),$additional_clauses,'');
                    $result=or_query($query['query'],$query['pars']);
                    $p_is_eligibe=false;
                    while ($pc=pdo_fetch_assoc($result)) if ($pc['participant_id']=$p['participant_id']) $p_is_eligibe=true;
                    if (!$p_is_eligibe) $continue=false;
                }
                if ($continue) {
                    // assign participant
                    $pars=array(':participant_id'=>$p['participant_id'],':experiment_id'=>$experiment['experiment_id']);
                    $query="INSERT INTO ".table('participate_at')." (participant_id,experiment_id)
                            VALUES (:participant_id , :experiment_id)";
                    $done=or_query($query,$pars);
                    $num_assigned++;
                    if ($settings['permanent_queries_invite']=='y') {
                        // send invitation into mail queue
                        $pars=array(':experiment_id'=>$experiment['experiment_id'],
                                    ':now'=>time(),
                                    ':recipient'=>$p['participant_id']);
                        $query="INSERT INTO ".table('mail_queue')."
                                SET timestamp = :now,
                                mail_type = 'invitation',
                                mail_recipient = :recipient,
                                experiment_id = :experiment_id ";
                        $done=or_query($query,$pars);
                    }
                    if (!isset($query_assigned[$q['query_id']])) $query_assigned[$q['query_id']]=0;
                    $query_assigned[$q['query_id']]++;
                }
            }
            // done with participant, reset permanent status
            $pars=array(':participant_id'=>$p['participant_id']);
            $query="UPDATE ".table('participants')."
                    SET apply_permanent_queries = 0
                    WHERE participant_id = :participant_id";
            $done=or_query($query,$pars);
        }

        // and now update permanent queries with assignment numbers
        foreach ($pqu as $q) {
            if (!isset($query_assigned[$q['query_id']])) $query_assigned[$q['query_id']]=0;
            $done=query__update_permanent_query($q['query_id'],$query_assigned[$q['query_id']]);
        }


    }
    $target='Participants checked: '.$num_p;
    if ($num_p>0) $target.=', PermQueries found: '.$num_queries;
    if ($num_queries>0) $target.=', Assignments made: '.$num_assigned;
    return $target;
}

function query__update_permanent_query($query_id,$assigned) {
    $pars=array(':query_id'=>$query_id);
    $query="SELECT * FROM ".table('queries')."
            WHERE query_id= :query_id";
    $result=or_query($query,$pars);
    while ($q=pdo_fetch_assoc($result)) {
        $properties=db_string_to_property_array($q['properties']);
        if (!isset($properties['assigned_count'])) $properties['assigned_count']=0;
        $properties['assigned_count']=$properties['assigned_count']+$assigned;
        $properties_string=property_array_to_db_string($properties);
        $newpars=array(':properties'=>$properties_string,
                        ':query_id'=>$q['query_id']);
        $newquery="UPDATE ".table('queries')."
                    SET properties=:properties
                    WHERE query_id= :query_id";
        $done=or_query($newquery,$newpars);
    }
}


function query__reset_permanent($experiment_id) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * FROM ".table('queries')."
            WHERE experiment_id= :experiment_id
            AND permanent = 1";
    $result=or_query($query,$pars);
    while ($q=pdo_fetch_assoc($result)) {
        $properties=db_string_to_property_array($q['properties']);
        $properties['permanent_end_time']=time();
        $properties_string=property_array_to_db_string($properties);
        $newpars=array(':properties'=>$properties_string,
                        ':query_id'=>$q['query_id']);
        $newquery="UPDATE ".table('queries')."
                    SET properties=:properties,
                        permanent = 0
                    WHERE query_id= :query_id";
        $done=or_query($newquery,$newpars);
        $addmessage=lang('current_permanent_query_deactivated');
    }
    if (isset($addmessage)) message($addmessage);
}

function query__load_default_query($type,$experiment_id=0) {
    //type can be: participants_search_active, participants_search_all, assign, deassign
    $pars=array(':query_type'=>'default_'.$type);
    $query="SELECT * FROM ".table('queries')."
            WHERE query_type=:query_type
            LIMIT 1";
    $query_line=orsee_query($query,$pars);
    if (isset($query_line['json_query'])) return $query_line['json_query'];
    else return '';
}

function query__save_default_query($json_query,$type) {
// type can be participants_search_active, participants_search_all, assign, deassign
    global $expadmin;
    if (isset($expadmindata['admin_id'])) $admin_id=$expadmindata['admin_id'];
    else $admin_id='';

    $pars=array(':query_type'=>$type);
    $query="SELECT * FROM ".table('queries')."
            WHERE query_type=:query_type
            LIMIT 1";
    $query_line=orsee_query($query,$pars);

    $pars=array(':query_time'=>time(),
                ':json_query'=>$json_query,
                ':query_type'=>$type,
                ':admin_id'=>$admin_id
                );

    if (isset($query_line['query_id'])) {
        $query="UPDATE ".table('queries')."
                SET query_time=:query_time,
                json_query=:json_query,
                admin_id=:admin_id
                WHERE query_type=:query_type";
    } else {
        $query="INSERT INTO ".table('queries')."
                SET query_time=:query_time,
                json_query=:json_query,
                admin_id=:admin_id,
                query_type=:query_type";
    }
    $done=or_query($query,$pars);
    message(lang('query_saved'));
    return $done;
}

function query__save_query($json_query,$type,$experiment_id=0,$properties=array(),$permanent=false) {
// type can be participants_search_active, participants_search_all, assign, deassign
    global $expadmin;
    $now=time();

    if ($experiment_id && $permanent) {
        // if this query is supposed to be permanent, then reset current permanent query if any
        $done=query__reset_permanent($experiment_id);
        // for new query
        $properties['is_permanent']=1;
        $properties['permanent_start_time']=time();
        $properties['assigned_count']=0;
        $addquery=", permanent=1";
        $addmessage=lang('activated_as_permanent_query');
    } else $addquery=", permanent=0";

    $properties_string=property_array_to_db_string($properties);

    $continue=true;
    if ($experiment_id==0) {
        // check if we already know this query, and if so, just update the record
        $pars=array(':json_query'=>$json_query);
        $query="SELECT * FROM ".table('queries')."
                WHERE json_query= :json_query LIMIT 1";
        $line=orsee_query($query,$pars);
        if (isset($line['query_id'])) {
            $pars=array(':query_time'=>$now,
                        ':query_id'=>$line['query_id']);
            $query="UPDATE ".table('queries')."
                    SET query_time= :query_time
                    WHERE query_id= :query_id";
            $done=or_query($query,$pars);
            message(lang('query_existed_now_updated'));
            $continue=false;
        }
    }

    // otherwise, save the query
    if ($continue) {
        if (isset($expadmindata['admin_id'])) $admin_id=$expadmindata['admin_id'];
        else $admin_id='';
        $pars=array(':query_time'=>$now,
                    ':json_query'=>$json_query,
                    ':query_type'=>$type,
                    ':experiment_id'=>$experiment_id,
                    ':properties'=>$properties_string,
                    ':admin_id'=>$admin_id
                    );
        $query="INSERT INTO ".table('queries')."
                SET query_time=:query_time,
                json_query=:json_query,
                query_type=:query_type,
                experiment_id=:experiment_id,
                admin_id=:admin_id,
                properties=:properties ".$addquery;
        $done=or_query($query,$pars);
        message(lang('query_saved'));
        if (isset($addmessage)) message($addmessage);
    }
    return $done;
}

function query__load_saved_queries($type,$limit=-1,$experiment_id=0,$details=false,$order="query_time DESC") {
// type can be participants_search_active, participants_search_all, assign, deassign

    $conditions=array();
    if ($type) {
        $types=explode(",",$type); $tqueries=array();
        foreach ($types as $t) {
            $tqueries[]="query_type='".trim($t)."'";
        }
        $conditions[]="( ".implode(' OR ',$tqueries)." )";
    }

    if ($experiment_id) $conditions[]="( experiment_id='".$experiment_id."' )";

    $query="SELECT * FROM ".table('queries');
    if (count($conditions)>0) $query.=" WHERE ".implode(" AND ", $conditions);
    $query.=" ORDER BY ".$order;
    if ($limit>0) $query.=" LIMIT ".$limit;
    $result=or_query($query);
    $queries=array();
    while ($q=pdo_fetch_assoc($result)) {
        if ($details) {
            $q['properties']=db_string_to_property_array($q['properties']);
            $queries[]=$q;
        } else {
            $queries[]=$q['json_query'];
        }
    }
    return $queries;
}

?>
