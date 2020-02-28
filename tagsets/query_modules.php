<?php
// part of orsee. see orsee.org


$all_orsee_query_modules=array(
"statusids",
"pformtextfields",
"noshows",
"participations",
"activity",
"updaterequest",
"subsubjectpool",
"interfacelanguage",
"pformselects",
"experimentclasses",
"experimenters",
"experimentsparticipated",
"experimentsassigned",
"randsubset",
"brackets"
);


function query__get_query_form_prototypes($hide_modules=array(),$experiment_id="",$status_query="") {
    global $lang, $settings, $all_orsee_query_modules;
    $formfields=participantform__load();

    $orsee_query_modules=$all_orsee_query_modules;

    $protoypes=array();
    foreach ($orsee_query_modules as $module) { if (!in_array($module,$hide_modules)) { switch ($module) {

    case "brackets":
        $prototype=array('type'=>'brackets',
                        'displayname'=>lang('query_brackets'),
                        'field_name_placeholder'=>'#brackets#'
                        );
        $content="";
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "experimentclasses":
        $prototype=array('type'=>'experimentclasses_multiselect',
                        'displayname'=>lang('query_experiment_class'),
                        'field_name_placeholder'=>'#experiment_class#'
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('participants_participated_expclass').'<BR>';
        $content.=experiment__experiment_class_select_field('#experiment_class#_ms_classes',array(),true,array('cols'=>40,'picker_maxnumcols'=>3));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "experimenters":
        $prototype=array('type'=>'experimenters_multiselect',
                        'displayname'=>lang('query_experimenters'),
                        'field_name_placeholder'=>'#experimenters#'
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('participants_participated_experimenters').'<BR>';
        $content.=experiment__experimenters_select_field("#experimenters#_ms_experimenters",array(),true,array('cols'=>40,'tag_color'=>'#f1c06f','picker_color'=>'#c58720','picker_maxnumcols'=>3));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "experimentsassigned":
        $prototype=array('type'=>'experimentsassigned_multiselect',
                        'displayname'=>lang('query_experiments_assigned'),
                        'field_name_placeholder'=>'#experiments_assigned#'
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('participants_were_assigned_to').'<BR>';
        $content.=experiment__other_experiments_select_field("#experiments_assigned#_ms_experiments","assigned",$experiment_id,array(),true,array('cols'=>80,'tag_color'=>'#b3ffb3','picker_color'=>'#00a300','picker_maxnumcols'=>$settings['query_experiment_list_nr_columns']));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "experimentsparticipated":
        $prototype=array('type'=>'experimentsparticipated_multiselect',
                        'displayname'=>lang('query_experiments_participated'),
                        'field_name_placeholder'=>'#experiments_participated#'
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('participants_have_participated_on').'<BR>';
        $content.=experiment__other_experiments_select_field("#experiments_participated#_ms_experiments","participated",$experiment_id,array(),true,array('cols'=>80,'tag_color'=>'#a8a8ff','picker_color'=>'#0000ff','picker_maxnumcols'=>$settings['query_experiment_list_nr_columns']));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "statusids":
        $prototype=array('type'=>'statusids_multiselect',
                        'displayname'=>lang('query_participant_status'),
                        'field_name_placeholder'=>'#statusids#'
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('participants_of_status').' ';
        $content.=participant_status__multi_select_field("#statusids#_ms_status",array(),array('cols'=>80,'tag_color'=>'#a8a8ff','picker_color'=>'#0000ff','picker_maxnumcols'=>2));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    case "pformtextfields":
        $prototype=array('type'=>'pformtextfields_freetextsearch',
                        'displayname'=>lang('query_participant_form_textfields'),
                        'field_name_placeholder'=>'#participant_form_textfields#'
                        );
        $form_query_fields=array();
        foreach ($formfields as $f) {
            if( preg_match("/(textline|textarea)/i",$f['type']) &&
                ((!$experiment_id && $f['search_include_in_participant_query']=='y')    ||
                ($experiment_id &&  $f['search_include_in_experiment_assign_query']=='y'))) {
                    $tfield=array();
                    $tfield['value']=$f['mysql_column_name'];
                    $tfield['name']=lang($f['name_lang']);
                    $form_query_fields[]=$tfield;
                }
        }
        $int_fields=participant__get_internal_freetext_search_fields();
        foreach ($int_fields as $ifield) {
            $form_query_fields[]=$ifield;
        }
        $content="";
        $content.=lang('where');
        $content.=' <INPUT type="text" size="20" maxlength="100" name="search_string" value="">';
        $content.='<SELECT name="not">
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                        <OPTION value="" SELECTED></OPTION>
                    </SELECT> ';
        $content.=' '.lang('in').' ';
        $content.='<SELECT name="search_field">
                    <OPTION value="all" SELECTED>'.lang('any_field').'</OPTION>';
        foreach($form_query_fields as $tf) {
            $content.='<OPTION value="'.$tf['value'].'">'.$tf['name'].'</OPTION>';
        }
        $content.='</SELECT>';
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;



    case "pformselects":
        $pform_selects=array();
        foreach ($formfields as $f) {
            if( (!preg_match("/(textline|textarea)/i",$f['type'])) &&
                ( ((!$experiment_id)    && $f['search_include_in_participant_query']=='y') ||
                  ($experiment_id && $f['search_include_in_experiment_assign_query']=='y')
                )  ) $pform_selects[]=$f['mysql_column_name'];
        }

        // $existing=true;
        //if ($experiment_id) $show_count=false; else $show_count=true;
        // needs too much time for queries. So  better:
        $existing=false; $show_count=false;

        foreach ($pform_selects as $fieldname) {
            $f=array();
            foreach ($formfields as $p) { if($p['mysql_column_name']==$fieldname) $f=$p; }
            $f=form__replace_funcs_in_field($f);
            if (isset($f['mysql_column_name'])) {
                $fieldname_lang=lang($f['name_lang']);
                $fname_ph='#pform_select_'.$fieldname.'#';
                $prototype=array('type'=>'pform_select_'.$fieldname,
                        'displayname'=>lang('query_participant_form_selectfield').$fieldname_lang,
                        'field_name_placeholder'=>$fname_ph
                        );
                $content="";
                $content.=lang('where').' '.$fieldname_lang.' ';
                if ($f['type']=='select_numbers') {
                    $content.='<select name="sign">
                      <OPTION value="<="><=</OPTION>
                      <OPTION value="=" SELECTED>=</OPTION>
                      <OPTION value=">">></OPTION>
                      </select>';
                } else {
                    $content.='<select name="not">
                    <OPTION value="" SELECTED>=</OPTION>
                    <OPTION value="NOT">'.lang('not').' =</OPTION>
                    </select> ';
                }

                if (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
                    $content.=language__multiselectfield_item($fieldname,$fieldname,$fname_ph.'_ms_'.$fieldname,array(),"",$existing,$status_query,$show_count,true,array('cols'=>80,'tag_color'=>'#bbbbbb','picker_color'=>'#444444','picker_maxnumcols'=>3));
                    $prototype['type']='pform_multiselect_'.$fieldname;
                } elseif ($f['type']=='select_numbers') {
                    if ($f['values_reverse']=='y') $reverse=true; else $reverse=false;
                    $content.=participant__select_numbers($fieldname,'fieldvalue','',$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,false,$existing,$status_query,$show_count);
                    $prototype['type']='pform_numberselect_'.$fieldname;
                } elseif (preg_match("/(select_list|radioline)/i",$f['type']) && !$existing) {
                    $f['value']='';
                    $content.=form__render_select_list($f,'fieldvalue');
                    $prototype['type']='pform_simpleselect_'.$fieldname;
                } else {
                    $content.=participant__select_existing($fieldname,'fieldvalue','',$status_query,$show_count);
                    $prototype['type']='pform_simpleselect_'.$fieldname;
                }
                $prototype['content']=$content; $prototypes[]=$prototype;
            }
        }
        break;

    case "noshows":
        $prototype=array('type'=>'noshows_numbercompare',
                        'displayname'=>lang('query_noshows'),
                        'field_name_placeholder'=>'#noshows#'
                        );
        $query="SELECT max(number_noshowup) as maxnoshow FROM ".table('participants');
        if ($status_query) $query.=" WHERE ".$status_query;
        $line=orsee_query($query);
        $content="";
        $content.=lang('where_nr_noshowups_is').' ';
        $content.='<select name="sign">
                        <OPTION value="<=" SELECTED><=</OPTION>
                        <OPTION value=">">></OPTION>
                        </select> ';
        $content.=helpers__select_number("count",'0',0,$line['maxnoshow'],0);
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "participations":
        $prototype=array('type'=>'participations_numbercompare',
                        'displayname'=>lang('query_participations'),
                        'field_name_placeholder'=>'#participations#'
                        );
        $query="SELECT max(number_reg) as maxnumreg FROM ".table('participants');
        if ($status_query) $query.=" WHERE ".$status_query;
        $line=orsee_query($query);
        $content="";
        $content.=lang('where_nr_participations_is').' ';
        $content.='<select name="sign">
                        <OPTION value="<=" SELECTED><=</OPTION>
                        <OPTION value=">">></OPTION>
                        </select> ';
        $content.=helpers__select_number("count",'0',0,$line['maxnumreg'],0);
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    case "updaterequest":
        $prototype=array('type'=>'updaterequest_simpleselect',
                        'displayname'=>lang('query_profile_update_request'),
                        'field_name_placeholder'=>'#updaterequest#'
                        );
        $content="";
        $content.=lang('where_profile_update_request_is').' ';
        $content.='<select name="update_request_status">
                    <OPTION value="y">'.lang('active').'</OPTION>
                    <OPTION value="n">'.lang('inactive').'</OPTION>
                    </select> ';
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    case "interfacelanguage":
        $prototype=array('type'=>'interfacelanguage_simpleselect',
                        'displayname'=>lang('query_interface_language'),
                        'field_name_placeholder'=>'#interfacelanguage#'
                        );
        $content="";
        $content.=lang('where_interface_language_is');
        $content.=' <SELECT name="not">
                        <OPTION value="" SELECTED></OPTION>
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                    </SELECT> ';
        $content.=lang__select_lang('interface_language',$options['public_standard_language'],'public');
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;

    case "activity":
        $prototype=array('type'=>'activity_numbercompare',
                        'displayname'=>lang('query_activity'),
                        'field_name_placeholder'=>'#activity#'
                        );
        $content=lang('where');
        $content.='<SELECT name="activity_type">
                        <OPTION value="last_activity" SELECTED>'.lang('last_activity').'</OPTION>
                        <OPTION value="last_enrolment">'.lang('last_enrolment').'</OPTION>
                        <OPTION value="last_profile_update">'.lang('last_profile_update').'</OPTION>
                        <OPTION value="creation_time">'.lang('creation_time').'</OPTION>';
        //$content.='    <OPTION value="deletion_time">'.lang('deletion_time').'</OPTION>';
        $content.='</SELECT> ';
        $content.='<SELECT name="not">
                        <OPTION value="" SELECTED></OPTION>
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                    </SELECT> ';
        $content.=lang('before_date').' ';
        $content.=formhelpers__pick_date('#activity#_dt_activity');
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    case "randsubset":
        $prototype=array('type'=>'randsubset_limitnumber',
                        'displayname'=>lang('query_rand_subset'),
                        'field_name_placeholder'=>'#rand_subset#'
                        );
        $query_limit = (!isset($_REQUEST['query_limit']) ||!$_REQUEST['query_limit']) ? $settings['query_random_subset_default_size'] : $_REQUEST['query_limit'];
        $content="";
        $content.=lang('limit_to_randomly_drawn').' ';
        $content.='<INPUT type="text" data-elem-name="limit" value="'.$settings['query_random_subset_default_size'].'" size="5" maxlength="10">';
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    case "subsubjectpool":
        $prototype=array('type'=>'subsubjectpool_multiselect',
                        'displayname'=>lang('query_subsubjectpool'),
                        'field_name_placeholder'=>'#subsubjectpool#',
                        'defaults'=>array('#subsubjectpool#_not'=>'',
                                        '#subsubjectpool#_ms_subpool'=>''
                                        )
                        );
        $content="";
        $content.='<SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT> ';
        $content.=lang('who_are_in_subjectpool').' ';
        $content.=subpools__multi_select_field("#subsubjectpool#_ms_subpool",array(),array('cols'=>80,'tag_color'=>'#a8a8ff','picker_color'=>'#0000ff','picker_maxnumcols'=>1));
        $prototype['content']=$content; $prototypes[]=$prototype;
        break;
    }}}

    return $prototypes;
}



function query__get_query_array($posted_array,$experiment_id="") {
    global $lang;

    $formfields=participantform__load();
    $participated_clause=expregister__get_pstatus_query_snippet("participated");
    $allowed_signs=array('<=','=','>');

    $query_array=array();
    $query_array['clauses']=array();

    foreach ($posted_array as $num=>$entry) {
        $temp_keys=array_keys($entry);
        $module_string=$temp_keys[0];
        $module_string_array=explode("_",$module_string);
        $module=$module_string_array[0];
        $type=$module_string_array[1];
        if ($module=='pform') {
            unset($module_string_array[0]);
            unset($module_string_array[1]);
            if ($module_string_array[2]=='ms') unset($module_string_array[2]);
            $pform_formfield=implode("_",$module_string_array);
        } else $pform_formfield="";
        $params=$entry[$module_string];

        $op=''; $ctype=''; $pars=array(); $clause=''; $subqueries=array(); $add=true;

        if (isset($params['logical_op']) && $params['logical_op']) $op=strtoupper($params['logical_op']);


        switch ($module) {
            case "bracket":
                if ($type=='open') {
                    $ctype='bracket_open';
                    $clause='(';
                } else {
                    $ctype='bracket_close';
                    $clause=')';
                }
                break;
            case "experimentclasses":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (#subquery0#)
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=array();
                $likelist=query__make_like_list($params['ms_classes'],'experiment_class');
                $subqueries[0]['subqueries'][0]['clause']['query']="
                        SELECT experiment_id as id
                        FROM ".table('experiments')."
                        WHERE (".$likelist['par_names'].") ";
                $subqueries[0]['subqueries'][0]['clause']['pars']=$likelist['pars'];
                break;

            case "experimenters":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (#subquery0#)
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=array();
                $likelist=query__make_like_list($params['ms_experimenters'],'experimenter');
                $subqueries[0]['subqueries'][0]['clause']['query']="
                        SELECT experiment_id as id
                        FROM ".table('experiments')."
                        WHERE (".$likelist['par_names'].") ";
                $subqueries[0]['subqueries'][0]['clause']['pars']=$likelist['pars'];
                break;

            case "experimentsassigned":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $list=query__make_enquoted_list($params['ms_experiments'],'experiment_id');
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (".$list['par_names'].")";
                $subqueries[0]['clause']['pars']=$list['pars'];
                break;
            case "experimentsparticipated":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $list=query__make_enquoted_list($params['ms_experiments'],'experiment_id');
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (".$list['par_names'].")
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=$list['pars'];
                break;
            case "statusids":
                $ctype='part';
                $list=query__make_enquoted_list($params['ms_status'],'status_id');
                $clause='status_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.="IN (".$list['par_names'].")";
                $pars=$list['pars'];
                break;
            case "pformtextfields":
                $ctype='part';
                $clause="";
                if ($params['not']) $clause.='NOT ';
                $form_query_fields=array();
                foreach ($formfields as $f) { // whitelist by loop
                    if( preg_match("/(textline|textarea)/i",$f['type']) &&
                        ((!$experiment_id && $f['search_include_in_participant_query']=='y')    ||
                        ($experiment_id &&  $f['search_include_in_experiment_assign_query']=='y'))) {
                            if ($params['search_field']=='all') {
                                $form_query_fields[]=$f['mysql_column_name'];
                            } elseif ($params['search_field']==$f['mysql_column_name']) {
                                $form_query_fields[]=$f['mysql_column_name'];
                            }
                    }
                }
                $int_fields=participant__get_internal_freetext_search_fields();
                foreach ($int_fields as $ifield) {
                    if ($params['search_field']=='all') {
                        $form_query_fields[]=$ifield['value'];
                    } elseif ($params['search_field']==$ifield['value']) {
                        $form_query_fields[]=$ifield['value'];
                    }
                }
                $like_array=array();
                $pars=array(); $i=0;
                foreach ($form_query_fields as $field) {
                    $like_array[]=$field." LIKE :search_string".$i." ";
                    $pars[':search_string'.$i]='%'.$params['search_string'].'%';
                    $i++;
                }
                $clause.=' ('.implode(" OR ",$like_array).') ';
                break;
            case "pform":
                $ctype='part';
                $clause="";
                $f=array();
                foreach ($formfields as $p) { if($p['mysql_column_name']==$pform_formfield) $f=$p; }
                if (isset($f['mysql_column_name'])) {
                    $clause.=$f['mysql_column_name'].' ';
                    if ($type=='numberselect')  {
                        if (in_array($params['sign'],$allowed_signs)) $clause.=$params['sign'];
                        else $clause.=$allowed_signs[0];
                        $clause.=' :number';
                        $pars=array(':number'=>$params['fieldvalue']);
                    } elseif ($type=='simpleselect') {
                        if ($params['not']) $clause.="!= "; else $clause.="= ";
                        $clause.=" :fieldvalue";
                        $pars=array(':fieldvalue'=>trim($params['fieldvalue']));
                    } else {
                        if ($params['not']) $clause.="NOT ";
                        $list=query__make_enquoted_list($params['ms_'.$pform_formfield],'fieldvalue');
                        $clause.="IN (".$list['par_names'].")";
                        $pars=$list['pars'];
                    }
                } else $add=false;
                break;
            case "noshows":
                $ctype='part';
                if($params['count']==0) $params['count']=0;
                $clause='number_noshowup ';
                if (in_array($params['sign'],$allowed_signs)) $clause.=$params['sign'];
                else $clause.=$allowed_signs[0];
                $clause.=' :noshowcount';
                $pars=array(':noshowcount'=>$params['count']);
                break;
            case "participations":
                $ctype='part';
                if($params['count']==0) $params['count']=0;
                $clause='number_reg ';
                if (in_array($params['sign'],$allowed_signs)) $clause.=$params['sign'];
                else $clause.=$allowed_signs[0];
                $clause.=' :partcount';
                $pars=array(':partcount'=>$params['count']);
                break;
            case "updaterequest":
                $ctype='part';
                if($params['update_request_status']=='y') $params['update_request_status']='y'; else $params['update_request_status']='n';
                $clause='pending_profile_update_request = :pending_profile_update_request';
                $pars=array(':pending_profile_update_request'=>$params['update_request_status']);
                break;
            case "interfacelanguage":
                $ctype='part';
                $clause='language ';
                if ($params['not']) $clause.="!= "; else $clause.="= ";
                $clause.=' :interface_language';
                $pars=array(':interface_language'=>$params['interface_language']);
                break;
            case "randsubset":
                $add=false;
                if($params['limit']==0) $params['limit']=0;
                $query_array['limit']=$params['limit'];
                break;
            case "activity":
                $activities=array('last_activity','last_enrolment','last_profile_update',
                                'creation_time','deletion_time');
                if (in_array($params['activity_type'],$activities)) {
                    $ctype='part';
                    $clause="";
                    if ($params['not']) $clause.='NOT ';
                    $sesstime_act=ortime__array_to_sesstime($params,'dt_activity_');
                    $pars=array(':activity_time'=>ortime__sesstime_to_unixtime($sesstime_act));
                    $clause.=' ('.$params['activity_type'].' < :activity_time) ';
                } else {
                    $add=false;
                }
                break;
            case "subsubjectpool":
                $ctype='part';
                $list=query__make_enquoted_list($params['ms_subpool'],'subpool_id');
                $clause='subpool_id ';
                if ($params['not']) $clause.='NOT ';
                $clause.="IN (".$list['par_names'].")";
                $pars=$list['pars'];
                break;
            default:
                $add=false;
                break;
        }
        if ($add) $query_array['clauses'][]=array('op'=>$op, 'ctype'=>$ctype,'clause'=>array('query'=>$clause,'pars'=>$pars), 'subqueries'=>$subqueries);
    }

    // remove unnecessary whitespace from any queries
    foreach ($query_array['clauses'] as $k=>$q) {
        $query_array['clauses'][$k]['clause']['query'] = trim(preg_replace('/\s+/', ' ', $query_array['clauses'][$k]['clause']['query']));
        if (isset($query_array['clauses'][$k]['subqueries'])) $query_array['clauses'][$k]['subqueries']=query__strip_ws_subqueries_recursively($query_array['clauses'][$k]['subqueries']);
    }
    // unset empty brackets, recursively if needed
    $ok=false;
    while (!$ok) {
        $ok=true;
        foreach ($query_array['clauses'] as $k=>$q) {
            if ($ok && $q['ctype']=='bracket_close' && $query_array['clauses'][$k-1]['ctype']=='bracket_open') {
                unset($query_array['clauses'][$k]);
                unset($query_array['clauses'][$k-1]);
                $ok=false;
                if (isset($query_array['clauses'][$k-2]) &&
                    $query_array['clauses'][$k-2]['ctype']=='bracket_open' &&
                    isset($query_array['clauses'][$k+1]['op']) ) $query_array['clauses'][$k+1]['op']='';
            }
        }
        $new_clauses=array();
        foreach ($query_array['clauses'] as $k=>$q) $new_clauses[]=$q;
        $query_array['clauses']=$new_clauses;
    }

    return $query_array;
}



function query__get_pseudo_query_array($posted_array) {
    global $lang;

    $formfields=participantform__load();

    $pseudo_query_array=array();

    $clevel=1;
    foreach ($posted_array as $num=>$entry) {
        $temp_keys=array_keys($entry);
        $module_string=$temp_keys[0];
        $module_string_array=explode("_",$module_string);
        $module=$module_string_array[0];
        $type=$module_string_array[1];
        if ($module=='pform') {
            unset($module_string_array[0]);
            unset($module_string_array[1]);
            $pform_formfield=implode("_",$module_string_array);
        } else $pform_formfield="";
        $params=$entry[$module_string];

        $level=$clevel; $op_text=""; $text=''; $add=true;

        if (isset($params['logical_op']) && $params['logical_op']) $op_text=lang($params['logical_op']);

        switch ($module) {
            case "bracket":
                if ($type=='open') {
                    $level=$clevel; $clevel++;
                    $text='(';
                } else {
                    $clevel--; $level=$clevel;
                    $text=')';
                }
                break;
            case "experimentclasses":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('participants_participated_expclass');
                $text.=': '.experiment__experiment_class_field_to_list($params['ms_classes']);
                break;
            case "experimenters":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('participants_participated_experimenters');
                $text.=': '.experiment__list_experimenters($params['ms_experimenters'],false,true);
                break;
            case "experimentsassigned":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('participants_were_assigned_to');
                $text.=': '.experiment__exp_id_list_to_exp_names($params['ms_experiments']);
                break;
            case "experimentsparticipated":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('participants_have_participated_on');
                $text.=': '.experiment__exp_id_list_to_exp_names($params['ms_experiments']);
                break;
            case "statusids":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('participants_of_status');
                $text.=': '.participant__status_id_list_to_status_names($params['ms_status']);
                break;
            case "pformtextfields":
                $text=lang('where');
                $text.=' "'.$params['search_string'].'" ';
                $text.=query__pseudo_query_not_not($params);
                $text.=lang('in').' ';
                if ($params['search_field']=='all') $text.=lang('any_field');
                else $text.=$params['search_field'];
                break;
            case "pform":
                $f=array();
                foreach ($formfields as $p) { if($p['mysql_column_name']==$pform_formfield) $f=$p; }
                if (isset($f['mysql_column_name'])) {
                    $text=lang('where').' '.lang($f['name_lang']).' ';
                    if ($type=='numberselect')  $text.=$params['sign'].$params['fieldvalue'];
                    elseif ($type=='simpleselect') $text.=query__pseudo_query_not_not($params).'= "'.$params['fieldvalue'].'"';
                    else $text.=query__pseudo_query_not_not($params).lang('in').': '.participant__select_lang_idlist_to_names($f['mysql_column_name'],$params['ms_'.$pform_formfield]);

                } else $add=false;
                break;
            case "noshows":
                $text=lang('where_nr_noshowups_is').' ';
                $text.=$params['sign'].' '.$params['count'];
                break;
            case "participations":
                $text=lang('where_nr_participations_is').' ';
                $text.=$params['sign'].' '.$params['count'];
                break;
            case "updaterequest":
                $text=lang('where_profile_update_request_is').' ';
                if ($params['update_request_status']=='y') $text.=lang('active');
                else $text.=lang('inactive');
                break;
            case "interfacelanguage":
                $lnames=lang__get_language_names();
                $text=lang('where_interface_language_is').' ';
                $text.=query__pseudo_query_not_not($params).'= "'.$lnames[$params['interface_language']].'"';
                break;
            case "activity":
                $text=lang('where').' '.lang($params['activity_type']).' ';
                $text.=query__pseudo_query_not_not($params);
                $text.=lang('before_date').' ';
                $sesstime_act=ortime__array_to_sesstime($params,'dt_activity_');
                $text.=ortime__format(ortime__sesstime_to_unixtime($sesstime_act),'hide_time:true');
                break;
            case "randsubset":
                $text=lang('limit_to_randomly_drawn').' ';
                $text.=$params['limit'];
                break;
            case "subsubjectpool":
                $text=query__pseudo_query_not_without($params);
                $text.=' '.lang('who_are_in_subjectpool');
                $text.=': '.subpools__idlist_to_namelist($params['ms_subpool']);
                break;
        }
        if ($add) $pseudo_query_array[]=array('level'=>$level, 'op_text'=>$op_text, 'text'=>$text);
    }

    return $pseudo_query_array;
}


// some query helpers
function query__get_experimenter_or_clause($experimenter_array,$tablename='experiments',$columnname='experimenter') {
    if (is_array($experimenter_array) && count($experimenter_array)>0) {
        $clause_array=array(); $pars=array(); $i=0;
        foreach ($experimenter_array as $e) {
            $i++;
            $parname=':'.$columnname.$i;
            $pars[$parname]='%|'.$e.'|%';
            $clause_array[]="(".table($tablename).".".$columnname." LIKE ".$parname.")";
        }
        $exp_clause="( ".implode(" OR ",$clause_array)." )";
    } else {
        $exp_clause="";
        $pars=array();
    }
    return array('clause'=>$exp_clause,'pars'=>$pars);
}

function query__get_class_or_clause($class_array) {
    if (is_array($class_array) && count($class_array)>0) {
        $clause_array=array(); $pars=array(); $i=0;
        foreach ($class_array as $c) {
            $i++;
            $parname=':experiment_class'.$i;
            $pars[$parname]='%|'.$c.'|%';
            $clause_array[]="(".table('experiments').".experiment_class LIKE ".$parname.")";
        }
        $class_clause="( ".implode(" OR ",$clause_array)." )";
    } else {
        $class_clause="";
        $pars=array();
    }
    return array('clause'=>$class_clause,'pars'=>$pars);
}





?>