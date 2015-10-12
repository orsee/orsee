<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
if (isset($_REQUEST['item'])) $item=$_REQUEST['item']; else $item='';
$title="options";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['item'])) $item=$_REQUEST['item']; else redirect ("admin/");
}

if ($proceed) {
    if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";

    $done=false;
    $formfields=participantform__load(); $allow_cat=$item;
    foreach($formfields as $f) {
        if (preg_match("/(select_lang|radioline_lang)/",$f['type']) && $item==$f['mysql_column_name']) {
            $done=true;
            $header=isset($lang[$f['name_lang']])?$lang[$f['name_lang']]:$f['name_lang'];
            $new_id='time';
            $inputform='line';
            $check_allow_content_shortcut=false;
            $allow_cat='pform_lang_field';
        }
    }

    if (!$id) $allow=check_allow($allow_cat.'_edit','lang_item_main.php?item='.$item);
    else $allow=check_allow($allow_cat.'_edit','options_main.php');
}

if ($proceed) {
    if (!$done) {

        switch($item) {
            case 'experimentclass':
                if ($id) $header=lang('edit_experiment_class');
                else $header=lang('add_experiment_class');
                $new_id='time';
                $check_allow_content_shortcut=false;
                $inputform='line';
                break;
            case 'public_content':
                if ($id) $header=lang('edit_public_content'); else $header=lang('add_public_content');
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'datetime_format':
                if ($id) $header=lang('edit_datetime_format'); else $header=lang('add_datetime_format');
                $new_id='content_shortcut';
                $inputform='line';
                $check_allow_content_shortcut=true;
                break;
            case 'help':
                if ($id) $header=lang('edit_help'); else $header=lang('add_help');
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'mail':
                if ($id) $header=lang('edit_default_mail'); else $header=lang('add_default_mail');
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'default_text':
                if ($id) $header=lang('edit_default_text'); else $header=lang('add_default_text');
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'laboratory':
                if ($id) $header=lang('edit_laboratory'); else $header=lang('create_new_laboratory');
                $new_id='time';
                $inputform='area';
                $check_allow_content_shortcut=false;
                $extranote_content_shortcut=lang('lab_lists_are_ordered_by_this_name');
                $extranote_lang_field=lang('first_line_is_lab_name_rest_is_address');
                break;
            case 'payments_type':
                if ($id) $header=lang('edit_payment_type'); else $header=lang('add_payment_type');
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'file_upload_category':
                if ($id) $header=lang('edit_upload_category'); else $header=lang('add_upload_category');
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'events_category':
                if ($id) $header=lang('edit_event_category'); else $header=lang('add_event_category');
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'emails_mailbox':
                if ($id) $header=lang('edit_email_mailbox'); else $header=lang('add_email_mailbox');
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            }
        }

        if ($id) $button_title=lang('change'); else $button_title=lang('add');

    echo '<center>';

    // load languages
    $languages=get_languages();


    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        $continue=true;

        if ($new_id=='content_shortcut' && !$_REQUEST['content_shortcut']) {
            message(lang('you_have_to_give_content_name'));
            $continue=false;
        }

        foreach ($languages as $language) {
            if (trim($_REQUEST[$language])=="") {
                message (lang('missing_language').": ".$language);
                $continue=false;
            } else {
                $_REQUEST[$language]=trim($_REQUEST[$language]);
            }
        }

        if ($continue) {
            $sitem=$_REQUEST;
            $sitem['content_type']=$item;

            if (!$id) $new=true; else $new=false;

            if ($new && $new_id=="time") $sitem['content_name']=time();
            if ($new_id=="content_shortcut") $sitem['content_name']=trim($_REQUEST['content_shortcut']);

            if ($new) { $id=lang__insert_to_lang($sitem); $done=true; }
            else $done=orsee_db_save_array($sitem,"lang",$id,"lang_id");

            if (!$new && $new_id=="time") $sitem['content_name']=trim($_REQUEST['content_shortcut']);

            if ($done) {
                log__admin($item."_edit","lang_id:".$sitem['content_type'].','.$sitem['content_name']);
                message (lang('changes_saved'));
                if ($new) redirect ('admin/lang_item_main.php?&item='.$item);
                else redirect ('admin/lang_item_edit.php?id='.$id.'&item='.$item);
            } else {
                message (lang('database_error'));
                redirect ('admin/lang_item_edit.php?id='.$id.'&item='.$item);
            }
        } else {
            $titem=$_REQUEST;
            if ($new_id=="content_shortcut") $titem['content_name']=$_REQUEST['content_shortcut'];
        }
    }
}


if ($proceed) {
    if ($id) { $titem=orsee_db_load_array("lang",$id,"lang_id"); }
    else { $titem=array('content_name'=>''); }

    show_message();

    // form
    echo '  <FORM action="lang_item_edit.php" METHOD=POST>
        <INPUT type=hidden name="id" value="'.$id.'">
        <INPUT type=hidden name="item" value="'.$item.'">

        <TABLE class="or_formtable">
            <TR><TD colspan=2>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.$header.'
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD>';
    if ($new_id=='content_shortcut') {
        echo lang('content_name').':';

        if (!$check_allow_content_shortcut || check_allow($allow_cat.'_add')) {
            echo '<BR><FONT class="small">'.lang('symbol_name_comment').'</FONT>';
            if (isset($extranote_content_shortcut) && $extranote_content_shortcut)
                echo '<BR><FONT class="small">'.$extranote_content_shortcut.'</FONT>';
        }
    } else echo lang('id');
    echo '      </TD>
                <TD>';
    if ($new_id=='content_shortcut') {
        if (!$check_allow_content_shortcut || check_allow($allow_cat.'_add')) {
            echo '<INPUT type=text name="content_shortcut" size=30 maxlength=50 value="'.
                $titem['content_name'].'">';
        } else {
            echo $titem['content_name'].
            '<INPUT type=hidden name="content_shortcut" value="'.$titem['content_name'].'">';
        }
    } elseif ($id) {
                echo $titem['content_name'].
                    '<INPUT type=hidden name="content_shortcut" value="'.$titem['content_name'].'">';
    } else echo '???';
        echo '      </TD>
            </TR>';

    foreach ($languages as $language) {
        if (!isset($titem[$language])) $titem[$language]="";
        echo '  <TR>
                <TD valign="top">
                    '.$language.':';
                if (isset($extranote_lang_field) && $extranote_lang_field)
                                        echo '<BR><FONT class="small">'.$extranote_lang_field.'</FONT>';
                echo '
                </TD>
                <TD>';
        if ($inputform=='area') {
            echo '<textarea name="'.$language.'" cols=50 rows=20 wrap=virtual>'.
                    stripslashes($titem[$language]).'</textarea>';
        } else {
            echo '<INPUT name="'.$language,'" type="text" size=30 maxlength=100 value="'.
                    stripslashes($titem[$language]).'">';
        }
        echo '  </TD>
            </TR>';
    }

    echo '  </TABLE>
        <TABLE>
            <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT class="button" name=edit type=submit value="'.$button_title.'">
                </TD>
            </TR>
        </table>
        </FORM>
        <BR>';

    if ($id && check_allow($allow_cat.'_delete')) {
        echo '<BR><BR>
            '.button_link('lang_item_delete.php?id='.urlencode($id).'&item='.urlencode($item),
                            lang('delete'),'trash-o').'
            ';
    }

    echo '<BR><BR>
        <A href="lang_item_main.php?item='.$item.'"><i class="fa fa-level-up fa-lg" style="padding-right: 3px;"></i>'.lang('back').'</A><BR><BR>
        </center>';

}
include ("footer.php");
?>