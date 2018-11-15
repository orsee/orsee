<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="faqs";
$title="faq_long";
$jquery=array('popup');
include("header.php");
if ($proceed) {
    if ($settings['show_public_faqs']!='y') redirect("public/");
}
if ($proceed) {

    //if (thisdoc()=="faq.php") script__open_faq_new();

    $query="SELECT * FROM ".table('faqs').", ".table('lang')."
            WHERE ".table('lang').".content_name=".table('faqs').".faq_id
            AND ".table('lang').".content_type='faq_answer'";
    $result=or_query($query); $answers=array();
    while ($line=pdo_fetch_assoc($result)) $answers[$line['faq_id']]=$line;

    $query="SELECT * FROM ".table('faqs').", ".table('lang')."
            WHERE ".table('lang').".content_name=".table('faqs').".faq_id
            AND ".table('lang').".content_type='faq_question'
            ORDER BY ".table('faqs').".evaluation DESC, ".table('lang').".".lang('lang');
    $result=or_query($query);

    $shade=false; $table_text=''; $faq_divs=array();
    while ($line=pdo_fetch_assoc($result)) {
        if ($shade) $shade=false; else $shade=true;
        $table_text.='<TR';
        if ($shade) $table_text.=' bgcolor="'.$color['list_shade1'].'"';
        else $table_text.=' bgcolor="'.$color['list_shade2'].'"';
        $table_text.='>
            <TD>
                    <A HREF="javascript:open_faq('.$line['faq_id'].')">';
        $table_text.=stripslashes($line[lang('lang')]);
        $table_text.='</FONT></A>
                </TD>
            <TD>
                '.$line['evaluation'].' '.lang('persons').'
            </TD>
              </TR>';

        $this_faq_div= '<TR><TD valign="top"><B>'.lang('question').'</B></TD><TD valign="top">'.$line[lang('lang')].'</TD></TR>
                        <TR><TD valign="top"><B>'.lang('answer').'</B></TD><TD valign="top">'.$answers[$line['faq_id']][lang('lang')].'</TD></TR>';
        if (!(isset($_SESSION['vote'][$line['faq_id']]) && $_SESSION['vote'][$line['faq_id']])) $this_faq_div.='<TR><TD></TD><TD>
                        '.button_link('#',lang('this_faq_answered_my_question'),'check-square bicongreen','',' id="faq_vote"').'</TD></TR>';
        $faq_divs[$line['faq_id']]=$this_faq_div;
    }

    $faq_html = '
            <center>
            <TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
                <TR BGCOLOR="'.$color['html_header_top_bar_background'].'"><TD HEIGHT="10">&nbsp;</TD></TR>
                <TR bgcolor="'.$color['html_header_logo_bar_background'].'"><TD valign="middle" align="left">
                    &nbsp;&nbsp;&nbsp;<i class="fa fa-question-circle fa-2x" style="color: white;"></i>&nbsp;<FONT style="font-size: 20pt; font-style: bold; color: white;">FAQ</FONT>
                </TD></TR>
                <TR BGCOLOR="'.$color['html_header_top_bar_background'].'"><TD HEIGHT="10">&nbsp;</TD></TR>
                <TR><TD bgcolor="'.$color['content_background_color'].'">
                    <TABLE width="100%">
                        #faq_question#
                    </TABLE>
                </TD></TR>
            </TABLE>
            </center>
            <BR>';

    echo '
        <div id="faqPopupDiv" class="faqpopupDiv" style=" background: '.$color['popup_bgcolor'].'; color: '.$color['popup_text'].';">
            <div align="right"><button class="b-close button fa-backward popupBack">'.lang('back').'</button></div>
            <div id="faqPopupContent" style="margin: 0px;"></div>
        </div>
        <script type="text/javascript">
            var faq_divs = ';
    echo json_encode($faq_divs);
    echo ';
            var faq_html= ';
    echo json_encode($faq_html);
    echo ';
                function open_faq(faq_id){
                    var faq_question = faq_divs[faq_id];
                    var str = faq_html;
                    str = str.replace("#faq_question#", faq_question);
                    $("#faqPopupContent").html("");
                    $("#faqPopupContent").append($.parseHTML(str));
                    faqBpopup = $("#faqPopupDiv").bPopup({
                        contentContainer: "#faqPopupContent",
                        amsl: 50,
                        positionStyle: "fixed",
                        modalColor: "'.$color['popup_modal_color'].'",
                        opacity: 0.8
                        });
                    $("#faq_vote").click(function(event){
                        event.preventDefault();
                        var vote_url="faq_vote.php?eval=true&id=" + faq_id;
                        $.ajax({
                            url: vote_url
                        });
                        faqBpopup.close();
                    });
                }

            </script>';

    echo '<center>
        <BR>
        <TABLE class="or_listtable" style="width: 80%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>
                </TD>
                <TD width=20% class="small">
                    '.lang('this_faq_answered_questions_of_xxx').'
                </TD>
            </TR></thead>
            <tbody>';

    echo $table_text;

    echo '  </tbody></TABLE>
        </center>';

}
include ("footer.php");
?>