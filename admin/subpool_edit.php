<?php
// part of orsee. see orsee.org
ob_start();

if (isset($_REQUEST['subpool_id'])) $subpool_id=$_REQUEST['subpool_id']; else $subpool_id="";

$menu__area="options";
$title="data_for_subpool";
include ("header.php");
if ($proceed) {
    if ($subpool_id) $allow=check_allow('subjectpool_edit','subpool_main.php');
    else $allow=check_allow('subjectpool_add','subpool_main.php');
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    $exptypes=load_external_experiment_types();

    if ($subpool_id) {
        $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
        if (!isset($subpool['subpool_id'])) redirect ("admin/subpool_main.php");
        else {
            $exptype_ids=db_string_to_id_array($subpool['experiment_types']);
            $subpool['exptypes']=array();
            foreach ($exptype_ids as $exptype_id) {
                $subpool['exptypes'][$exptype_id]=$exptype_id;
            }
            $pars=array(':subpool_id'=>$subpool_id);
            $query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name= :subpool_id";
            $selfdesc=orsee_query($query,$pars);
        }
    } else {
        $subpool=array('subpool_name'=>'','subpool_description'=>'','subpool_type'=>'','exptypes'=>array(),'show_at_registration_page'=>'');
        $selfdesc=array();
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        if (!$_REQUEST['subpool_name']) {
            message (lang('name_for_subpool_required'));
            $continue=false;
        }

        $exptype_ids=array();
        foreach ($exptypes as $exptype_id=>$exptype) {
            if (isset($_REQUEST['exptypes'][$exptype_id]) && $_REQUEST['exptypes'][$exptype_id]) $exptype_ids[]=$exptype_id;
        }
        if (count($exptype_ids)==0) {
            message(lang('at_minimum_one_exptype_mapping_required'));
            $continue=false;
        }

        $selfdesc=$_REQUEST['selfdesc'];
        if (!$subpool_id || $subpool_id > 1) {
            foreach ($languages as $language) {
                if (!(isset($selfdesc[$language]) && $selfdesc[$language])) {
                    message (lang('missing_language').': '.$language);
                    $continue=false;
                }
            }
        }

        if ($subpool_id==1) {
            $_REQUEST['show_at_registration_page']='n';
            foreach ($languages as $language) $selfdesc[$language]='';
        }

        if ($continue) {
            if (!$subpool_id) {
                $new=true;
                $query="SELECT subpool_id+1 as new_sub FROM ".table('subpools')."
                        ORDER BY subpool_id DESC LIMIT 1";
                $line=orsee_query($query);
                $subpool_id=$line['new_sub'];
                $lsub['content_type']="subjectpool";
                $lsub['content_name']=$subpool_id;
            } else {
                $new=false;
                $pars=array(':subpool_id'=>$subpool_id);
                $query="SELECT * from ".table('lang')."
                        WHERE content_type='subjectpool'
                        AND content_name= :subpool_id";
                $lsub=orsee_query($query,$pars);
            }

            $subpool=$_REQUEST;
            $subpool['experiment_types']=id_array_to_db_string($exptype_ids);
            foreach ($languages as $language) $lsub[$language]=$selfdesc[$language];
            $done=orsee_db_save_array($subpool,"subpools",$subpool_id,"subpool_id");
            if ($new) $lsub['lang_id']=lang__insert_to_lang($lsub);
            else $done=orsee_db_save_array($lsub,"lang",$lsub['lang_id'],"lang_id");

            message (lang('changes_saved'));
            log__admin("subjectpool_edit","subjectpool:".$subpool['subpool_name']."\nsubpool_id:".$subpool['subpool_id']);
            redirect ("admin/subpool_edit.php?subpool_id=".$subpool_id);
        } else {
            $subpool=$_REQUEST;
            $subpool['exptypes']=array();
            foreach ($exptype_ids as $exptype_id) {
                $subpool['exptypes'][$exptype_id]=$exptype_id;
            }
        }
    }
}

if ($proceed) {
    // form
    echo '<CENTER>';
    show_message();
    echo '
        <FORM action="subpool_edit.php">
        <INPUT type=hidden name="subpool_id" value="'.$subpool_id.'">
        <TABLE class="or_panel">
            <TR><TD>'.lang('id').':</TD><TD>'.$subpool_id.'</TD></TR>
            <TR><TD>'.lang('name').':</TD>
                <TD><INPUT name="subpool_name" type=text size=40 maxlength=100
                        value="'.$subpool['subpool_name'].'"></TD>
            </TR><TR><TD>'.lang('description').':</TD>
                <TD><textarea name="subpool_description" rows=5 cols=30 wrap=virtual>'.
                        $subpool['subpool_description'].'</textarea></TD></TR>
            <TR><TD valign=top>'.lang('can_request_invitations_for').'</TD>
                <TD>';
    experiment_ext_types__checkboxes('exptypes',lang('lang'),$subpool['exptypes']);
    echo '  </TD></TR>';

    if (!$subpool_id || $subpool_id>1) {
        echo '<TR>
                <TD>'.lang('show_at_registration_page?').'</TD>
                <TD><INPUT type=radio name="show_at_registration_page" value="y"';
        if ($subpool['show_at_registration_page']=="y") echo ' CHECKED';
        echo '>'.lang('yes').'&nbsp;&nbsp;
                        <INPUT type=radio name="show_at_registration_page" value="n"';
        if ($subpool['show_at_registration_page']!="y") echo ' CHECKED';
        echo '>'.lang('no').'</TD></TR>';
        echo '<TR><TD colspan=2>'.lang('registration_page_options').'</TD></TR>';
        foreach ($languages as $language) {
            if (!isset($selfdesc[$language])) $selfdesc[$language]='';
            echo '  <TR><TD>'.$language.':</TD>
                        <TD><INPUT name="selfdesc['.$language.']" type=text size=40 maxlength=200 value="'.
                        $selfdesc[$language].'"></TD></TR>';
        }
    }
    echo '<TR><TD COLSPAN=2 align=center>
                <INPUT class="button" name="edit" type=submit value="';
    if (!$subpool_id) echo lang('add'); else echo lang('change');
    echo '"></TD></TR>
        </table></FORM><BR>';

    if ($subpool_id && $subpool_id>1 && check_allow('subjectpool_delete')) {
        echo '<table>
                <TR>
                    <TD>'.button_link('subpool_delete.php?subpool_id='.urlencode($subpool_id),
                            lang('delete'),'trash-o').'
                    <TD>
                </TR>
            </table>';
    }

    echo '<BR><BR>
          <A href="subpool_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
          </center>';

}
include ("footer.php");
?>
