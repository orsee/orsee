<?php
// part of orsee. see orsee.org
ob_start();

$title="faq_long";
$menu__area="options_main";
include ("header.php");

if ($proceed) {
    $allow=check_allow('faq_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';
    if (check_allow('faq_add')) {
        echo '
                <BR>
                '.button_link('faq_edit.php?addit=true',
                        lang('create_new'),'plus-circle');
    }

    // load languages
    $languages=get_languages();
    echo '<BR><BR>
        <table class="or_listtable" style="width: 80%;"><thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
    foreach ($languages as $language) {
        echo '<td class="small">'.$language.'</td>';
    }
    echo '<TD>'.lang('this_faq_answered_questions_of_xxx').'</TD>
            <TD></TD>
            </TR></thead>
                <tbody>';
    $query="SELECT *
            FROM ".table('faqs').", ".table('lang')."
            WHERE content_type='faq_question'
            AND ".table('faqs').".faq_id=".table('lang').".content_name
            ORDER BY ".lang('lang');
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>';
        foreach ($languages as $language) {
            echo '  <td class="small">'.stripslashes($line[$language]).'</td>';
        }
        echo '<TD>'.$line['evaluation'].' '.lang('persons').'</TD>
                <TD><A HREF="faq_edit.php?faq_id='.$line['faq_id'].'">'.lang('edit').'</A>
                </TD>
                </tr>';
        if ($shade) $shade=false; else $shade=true;
    }

    echo '</tbody></table>
           </CENTER>';

}
include ("footer.php");
?>