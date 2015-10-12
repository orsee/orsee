<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="languages";
include ("header.php");
if ($proceed) {

    // load languages
    $languages=get_languages();
    $lang_names=lang__get_language_names();

    if (isset($settings['language_enabled_participants']) && $settings['language_enabled_participants'])
        $enabled_part=explode(",",$settings['language_enabled_participants']);
    else $enabled_part=array();
    if (isset($settings['language_enabled_public']) && $settings['language_enabled_public'])
        $enabled_pub=explode(",",$settings['language_enabled_public']);
    else $enabled_pub=array();


    if (isset($_REQUEST['change_def']) && $_REQUEST['change_def']) {
        $allow=check_allow('lang_avail_edit','lang_main.php');

        if ($proceed) {
            $parts=array(); $pubs=array();
            foreach ($languages as $language) {
                if ((isset($_REQUEST['enabled_public'][$language]) && $_REQUEST['enabled_public'][$language]) || $language==$settings['public_standard_language']) $pubs[]=$language;
                if ((isset($_REQUEST['enabled_participants'][$language]) && $_REQUEST['enabled_participants'][$language]) || $language==$settings['public_standard_language']) $parts[]=$language;
            }
            $pubs_string=implode(",",$pubs);
            $parts_string=implode(",",$parts);

            $query="SELECT * FROM ".table('options')."
                    WHERE option_type='general' AND option_name='language_enabled_public'";
            $result=orsee_query($query); $now=time();
            if (isset($result['option_id'])) {
                $pars=array(':pubs_string'=>$pubs_string);
                $query="UPDATE ".table('options')." SET option_value= :pubs_string
                        WHERE option_type='general' AND option_name='language_enabled_public'";
                $done=or_query($query,$pars);
            } else {
                $pars=array(':pubs_string'=>$pubs_string,
                            ':option_id'=>$now+1);
                $query="INSERT INTO ".table('options')."
                        SET option_id=:option_id,
                        option_type='general',
                        option_name='language_enabled_public',
                        option_value= :pubs_string";
                $done=or_query($query,$pars);
            }

            $query="SELECT * FROM ".table('options')."
                    WHERE option_type='general' AND option_name='language_enabled_participants'";
            $result2=orsee_query($query);
            if (isset($result2['option_id'])) {
                $pars=array(':parts_string'=>$parts_string);
                $query="UPDATE ".table('options')." SET option_value= :parts_string
                        WHERE option_type='general' AND option_name='language_enabled_participants'";
                $done=or_query($query,$pars);
            } else {
                $pars=array(':parts_string'=>$parts_string,
                            ':option_id'=>$now+2);
                $query="INSERT INTO ".table('options')."
                        SET option_id=:option_id,
                        option_type='general',
                        option_name='language_enabled_participants',
                        option_value= :parts_string";
                $done=or_query($query,$pars);
            }
            log__admin("language_availability_edit");
            message(lang('changes_saved'));
            redirect("admin/lang_main.php");
        }
    }
}

if ($proceed) {
    echo '<center>';

    echo '  <BR><BR>
        <TABLE border=0 width=80%>
            <TR>';
        if (check_allow('lang_symbol_add')) echo '
            <TD>
                        '.button_link('lang_symbol_edit.php?go=true',
                        lang('add_symbol'),'plus-circle').'
            </TD>';
        if (check_allow('lang_lang_add')) echo '
            <TD>
                        '.button_link('lang_lang_add.php',
                        lang('add_language'),'plus').'
                        </TD>';
        if (check_allow('lang_lang_delete')) echo '
            <TD>
                    '.button_link('lang_lang_delete.php',
                        lang('delete_language'),'times').'
                        </TD>';
    echo '      </TR>
        </TABLE><BR><BR>
        ';


        // show languages

    echo '<FORM action="'.thisdoc().'">';
    echo '<TABLE class="or_listtable" style="width: 80%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD colspan=2>'.lang('installed_languages').'</TD>
                <TD>'.lang('available_in_public_area').'</TD>
                <TD>'.lang('available_for_participants').'</TD>
                <TD></TD>
                <TD></TD>
            </TR></thead>
            <tbody>';

    $shade=false;
    foreach ($languages as $language) {
        echo '<TR';
        if ($shade) { echo ' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
        else { echo ' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
        echo '>
                <TD>'.$lang_names[$language].' - '.$language.'</TD>
                <TD>';
        if ($language==$settings['admin_standard_language']) echo '[default admin] ';
        if ($language==$settings['public_standard_language']) echo '[default public] ';
        echo '  </TD>
                <TD>
                    <INPUT type=checkbox name="enabled_public['.$language.']" value="'.$language.'"';
        if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) echo ' DISABLED';
        if (in_array($language,$enabled_pub)) echo ' CHECKED';
        echo '>
                </TD>
                <TD>
                    <INPUT type=checkbox name="enabled_participants['.$language.']" value="'.$language.'"';
        if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) echo ' DISABLED';
        if (in_array($language,$enabled_part)) echo ' CHECKED';
        echo '>
                </TD>
                <TD>';
        if (check_allow('lang_lang_edit')) echo '<A HREF="lang_lang_edit.php?elang='.$language.'">'.lang('edit_basic_data').'</A>';
        echo '  </TD>
                <TD>';
        if (check_allow('lang_symbol_edit')) echo '<A HREF="lang_edit.php?el='.$language.'">'.lang('edit_words_for').' "'.$language.'"</A>';
        echo '  </TD>
                </TD>
            </TR>';
    }

    echo '</tbody>';
    if (check_allow('lang_avail_edit')) {
        echo '  <tfoot><TR><TD colspan=2></TD><TD align=center colspan=2>
                <INPUT class="button" type=submit name="change_def" value="'.lang('change').'">
                </TD><TD></TD>
                </TR></tfoot>';
    }

    echo '</TABLE></FORM>';
    echo '</center>';

}
include ("footer.php");
?>