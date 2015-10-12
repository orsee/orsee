<?php
// part of orsee. see orsee.org
ob_start();

if (isset($_REQUEST['export']) && $_REQUEST['export']) {
    include ("nonoutputheader.php");
    if ($proceed) {
        if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else $lang_id='';
        $languages=get_languages();
        if (!$lang_id || !in_array($lang_id,$languages)) redirect ("admin/lang_main.php");
    }
    if ($proceed) {
        $allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);
    }
    if ($proceed) {
        $query="SELECT * FROM ".table('lang')."
                WHERE content_type IN ('lang','mail','default_text')
                ORDER by lang_id";
        $result=or_query($query);
        $items="";
        while ($line=pdo_fetch_assoc($result)) {
            $items.=stripslashes($line['content_type']).'--:orsee_next:--'.
                stripslashes($line['content_name']).'--:orsee_next:--'.
                stripslashes($line[$lang_id]).'--:orsee_line:--';
        }
        $mime_type="text/*";
        $filename='orsee_'.$lang_id.'.orl';
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: ".$mime_type);
        header( "Content-Disposition: attachment; filename=\"$filename\"");
        header( "Content-Description: File Transfer");
        $file=chunk_split(base64_encode($items),60);
        echo $file;
    }
} else {

    $menu__area="options";
    $title="export_language";
    include ("header.php");
    if ($proceed) {
        if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else $lang_id='';
        $languages=get_languages();
        if (!$lang_id || !in_array($lang_id,$languages)) redirect ("admin/lang_main.php");
    }
    if ($proceed) {
        $allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);
    }
    if ($proceed) {
        $tlang_name=load_language_symbol('lang_name',$lang_id);

        echo '<center><H4></H4>';
        echo '
            <TABLE class="or_formtable" style="min-width: 50%;">
                <TR><TD colspan="2">
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                '.lang('export_language').' '.$tlang_name.' ('.$lang_id.')
                            </TD>
                    </TR></TABLE>
                </TD></TR>
                <TR>
                    <TD align=center>'.lang('language_export_explanation').'</TD>
                </TR>';
        echo '      <TR>
                    <TD align=center>
                        <A HREF="lang_lang_export.php?lang_id='.$lang_id.'&export=true">
                            orsee_'.$lang_id.'.orl</A>
                    </TD>
                </TR>';
        echo '
            </TABLE>
            ';
        echo '<BR><BR>
                    <A href="lang_lang_edit.php?elang='.$lang_id.'">'.icon('back').' '.lang('back').'</A><BR><BR>
                    </center>';
    }
    include ("footer.php");
}
?>