<?php
// part of orsee. see orsee.org
ob_start();
$title="send_bulk_mail";

include ("header.php");
if ($proceed) {
    $allow=check_allow('participants_bulk_mail','participants_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['send']) && $_REQUEST['send']) $send=true; else $send=false;

    // load invitation languages
    $inv_langs=lang__get_part_langs();
    $plist_ids=$_SESSION['plist_ids'];
    $number=count($plist_ids);

    if ($send) {
        if ((!is_array($plist_ids)) || count($plist_ids)<1) redirect ("admin/");
    }

}

if ($proceed) {
    if ($send) {
        // checks
        $bulk=$_REQUEST;
        $continue=true;

        foreach ($inv_langs as $inv_lang) {
             if (!$bulk[$inv_lang.'_subject']) {
                message (lang('subject').': '.lang('missing_language').": ".$inv_lang);
                $continue=false;
            }
            if (!$bulk[$inv_lang.'_body']) {
                message (lang('body_of_message').': '.lang('missing_language').": ".$inv_lang);
                $continue=false;
            }
        }

        if ($continue) {
            $bulk_id=time();
            $pars=array();
            foreach ($inv_langs as $inv_lang) {
                $pars[]=array(':bulk_id'=>$bulk_id,
                            ':inv_lang'=>$inv_lang,
                            ':subject'=>$bulk[$inv_lang.'_subject'],
                            ':body'=>$bulk[$inv_lang.'_body']);
            }
            $query="INSERT INTO ".table('bulk_mail_texts')."
                    SET bulk_id= :bulk_id,
                    lang= :inv_lang,
                    bulk_subject= :subject,
                    bulk_text= :body";
            $done=or_query($query,$pars);

            $done=experimentmail__send_bulk_mail_to_queue($bulk_id,$plist_ids);

            message ($number.' '.lang('xxx_bulk_mails_sent_to_mail_queue'));
            log__admin("bulk_mail","recipients:".$number);
            redirect ('admin/');
        }
    }
}

if ($proceed) {
    echo '<center>
            <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 80%">
                <TR><TD align="center">'.$number.' '.lang('recipients').'</TD></TR></TABLE>
            ';
    show_message();

    // form
    echo '<FORM action="'.thisdoc().'" method="post">
        <TABLE class="or_formtable" style="width: 80%">';

    foreach ($inv_langs as $inv_lang) {
        if (count($inv_langs) > 1) {
            echo '<TR><TD colspan=2>
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                    <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$inv_lang.':
                    </TD>
                    </TR></TABLE>
                </TD></TR>';
        }
        if (!isset($_REQUEST[$inv_lang.'_subject'])) $_REQUEST[$inv_lang.'_subject']="";
        if (!isset($_REQUEST[$inv_lang.'_body'])) $_REQUEST[$inv_lang.'_body']="";
        echo '
            <TR>
                <TD>'.lang('subject').':</TD>
                <TD><INPUT type=text name="'.$inv_lang.'_subject" size=30 maxlength=80 value="'.
                        $_REQUEST[$inv_lang.'_subject'].'"></TD>
            </TR>
            <TR><TD valign=top>'.lang('body_of_message').':</TD>
                <TD><textarea name="'.$inv_lang.'_body" wrap=virtual rows=20 cols=50>'.
                    $_REQUEST[$inv_lang.'_body'].'</textarea>
                </TD>
            </TR>';
        echo ' <TR><TD colspan=2>&nbsp;</TD></TR>';
    }
    echo '<TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="send" value="'.lang('send').'">
                </TD>
            </TR>';

    echo '
            </TABLE>
            </FORM>';

    echo '<BR><BR>

        </CENTER>';

}
include ("footer.php");
?>