<?php
// part of orsee. see orsee.org
ob_start();
$title="mail_preview";

include("header.php");
if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
            else { $experiment_id=""; redirect ("admin/"); }
}

if ($proceed) {
    $allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);
}
if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    $inv_langs=lang__get_part_langs();

    echo '<center>';

    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 80%;">
            <TR><TD align="center">
            '.$experiment['experiment_name'].'
            </TD>';
    echo '</TR></TABLE>';

    echo '<TABLE class="or_formtable" style="width: 80%;">';

    echo '<TR><TD colspan=2>
            '.button_link('experiment_mail_participants.php?experiment_id='.urlencode($experiment_id),
                            lang('back_to_mail_page'),'backward','font-size: 8pt;').'
            </TD></TR>';

    foreach ($inv_langs as $inv_lang) {
        // split in subject and text
        $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
        $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

        if ($experiment['experiment_type']=="laboratory") {
            $sessionlist=experimentmail__get_session_list($experiment_id,$inv_lang);
        } else $sessionlist='';

        $pform_fields=participant__load_participant_email_fields($inv_lang);
        $experimentmail=experimentmail__preview_fake_participant_details($pform_fields);
        $experimentmail=experimentmail__get_invitation_mail_details($experimentmail,$experiment,$sessionlist);
        if ($experiment['sender_mail']) $sendermail=$experiment['sender_mail']; else $sendermail=$settings['support_mail'];
        $email_text=process_mail_template(stripslashes($body),$experimentmail);

        if (count($inv_langs) > 1) {
            echo '<TR><TD colspan=2>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$inv_lang.':
                        </TD>
                        </TR></TABLE>
                    </TD></TR>';
        }

        echo '<TR><TD colspan=2><TABLE class="or_panel" style="background: '.$color['list_shade2'].'; width: 100%;">
            <TR>
                <TD>'.load_language_symbol('email_from',$inv_lang).':</TD>
                <TD>'.$sendermail.'</TD>
            </TR>
                    <TR>
                <TD>'.load_language_symbol('email_to',$inv_lang).':</TD>
                <TD>'.$experimentmail['email'].'</TD>
            </TR>
                    <TR bgcolor="'.$color['list_shade2'].'">
                <TD>'.load_language_symbol('subject',$inv_lang).':</TD>
                <TD>'.stripslashes($subject).'</TD>
            </TR>
            <TR>
                <TD valign=top bgcolor="'.$color['content_background_color'].'" colspan=2>
                    '.nl2br($email_text);
                if (isset($experimentmail['include_footer']) && $experimentmail['include_footer']=="y")
                    echo nl2br(stripslashes(experimentmail__get_mail_footer(0)));
        echo '      </TD>
            </TR>
                </TABLE></TD></TR>';
        echo '<TR><TD colspan=2>&nbsp;</TD></TR>';

    }

        echo '<TR><TD colspan=2>
            '.button_link('experiment_mail_participants.php?experiment_id='.urlencode($experiment_id),
                            lang('back_to_mail_page'),'backward','font-size: 8pt;').'
            </TD></TR>';
        echo '</TABLE>';

    echo '<BR><BR>
                </CENTER>';

}
include("footer.php");
?>