<?php
// part of orsee. see orsee.org
ob_start();

if (isset($_REQUEST['exptype_id'])) $exptype_id=$_REQUEST['exptype_id']; else $exptype_id="";

$menu__area="options";
$title="data_for_exptype";
include ("header.php");
if ($proceed) {

    // load languages
        $languages=get_languages();

    if ($exptype_id)  $allow=check_allow('experimenttype_edit','experiment_type_main.php');
    else $allow=check_allow('experimenttype_add','experiment_type_main.php');
}

if ($proceed) {

        if ($exptype_id) {
            $exptype=orsee_db_load_array("experiment_types",$exptype_id,"exptype_id");
            $map=explode(",",$exptype['exptype_mapping']);
            foreach ($map as $etype) {
                $exptype['exptype_map'][$etype]=$etype;
            }
            $query="SELECT * from ".table('lang')." WHERE content_type='experiment_type' AND content_name='".$exptype_id."'";
            $selfdesc=orsee_query($query);
        } else {
            $exptype=array('exptype_name'=>'','exptype_description'=>'');
            $selfdesc=array();
        }

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        if (!$_REQUEST['exptype_name']) {
            message (lang('name_for_exptype_required'));
            $continue=false;
        }

        $map=array();

        $types=$system__experiment_types;
        foreach ($types as $etype) {
            if (isset($_REQUEST['exptype_map'][$etype]) && $_REQUEST['exptype_map'][$etype]) $map[]=$_REQUEST['exptype_map'][$etype];
        }
        if (count($map)==0) {
            message(lang('at_minimum_one_exptype_mapping_required'));
            $continue=false;
        }

        $selfdesc=$_REQUEST['selfdesc'];
        if (!$exptype_id || $exptype_id > 1) {
            foreach ($languages as $language) {
                if (!$selfdesc[$language]) {
                    message (lang('missing_language').': '.$language);
                    $continue=false;
                }
            }
        }

        if ($continue) {
            if (!$exptype_id) {
                $new_entry=true;
                $query="SELECT exptype_id+1 as new_sub FROM ".table('experiment_types')."
                        ORDER BY exptype_id DESC LIMIT 1";
                $line=orsee_query($query);
                $exptype_id=$line['new_sub'];
                $lsub['content_type']="experiment_type";
                $lsub['content_name']=$exptype_id;
            } else {
                $new_entry=false;
                $query="SELECT * from ".table('lang')."
                        WHERE content_type='experiment_type'
                        AND content_name='".$exptype_id."'";
                $lsub=orsee_query($query);
            }

            $exptype=$_REQUEST;
            $exptype['exptype_mapping']=implode(",",$map);

            foreach ($languages as $language) $lsub[$language]=$selfdesc[$language];

            $done=orsee_db_save_array($exptype,"experiment_types",$exptype_id,"exptype_id");

            if ($new_entry) $done=lang__insert_to_lang($lsub);
            else $done=orsee_db_save_array($lsub,"lang",$lsub['lang_id'],"lang_id");
            log__admin("experimenttype_edit",$exptype['exptype_name']);

            message (lang('changes_saved'));
            redirect ("admin/experiment_type_edit.php?exptype_id=".$exptype_id);
        } else {
            $exptype=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form

    echo '  <CENTER>';

    show_message();

    echo '
            <FORM action="experiment_type_edit.php">
                <INPUT type=hidden name="exptype_id" value="'.$exptype_id.'">

        <TABLE class="or_formtable">
            <TR>
                <TD>
                    '.lang('id').':
                </TD>
                <TD>
                    '.$exptype_id.'
                </TD>
            </TR>
            <TR>
                <TD>
                    '.lang('name').':
                </TD>
                <TD>
                    <INPUT name="exptype_name" type=text size=40 maxlength=100
                        value="'.$exptype['exptype_name'].'">
                </TD>
            </TR>
            <TR>
                <TD>
                    '.lang('description').':
                </TD>
                <TD>
                    <textarea name="exptype_description" rows=5 cols=30 wrap=virtual>'.
                        stripslashes($exptype['exptype_description']).'</textarea>
                </TD>
            </TR>

            <TR>
                <TD>
                    '.lang('assigned_internal_experiment_types').'
                </TD>
                <TD>';
                    $experiment_types=$system__experiment_types;
                    foreach ($experiment_types as $etype) {
                    echo '<INPUT type=checkbox name="exptype_map['.$etype.']" value="'.$etype.'"';
                        if (isset($exptype['exptype_map'][$etype]) && $exptype['exptype_map'][$etype]) echo ' CHECKED';
                        echo '>'.$lang[$etype].'
                    <BR>';
                    }

    echo '          </TD>
            </TR>

            <TR>
                <TD colspan=2>
                    '.lang('public_exptype_description').'
                </TD>
            </TR>';

            foreach ($languages as $language) {
                if (!isset($selfdesc[$language])) $selfdesc[$language]='';
                echo '  <TR>
                        <TD>
                            '.$language.':
                        </TD>
                        <TD>
                            <INPUT name="selfdesc['.$language.']" type=text size=40 maxlength=200 value="'.
                                stripslashes($selfdesc[$language]).'">
                        </TD>
                    </TR>';
                }
    echo '
            <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT class="button" name="edit" type=submit value="';
                    if (!$exptype_id) echo lang('add'); else echo lang('change');
                    echo '">
                </TD>
            </TR>


        </table>
        </FORM>
        <BR>';

    if ($exptype_id && check_allow('experimenttype_delete')) {
        echo '<table>
                <TR>
                    <TD>
                        '.button_link('experiment_type_delete.php?exptype_id='.urlencode($exptype_id),
                            lang('delete'),'trash-o').'
                    <TD>
                </TR>
            </table>';
        }

        echo '<BR><BR>
                <A href="experiment_type_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>