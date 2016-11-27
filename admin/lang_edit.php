<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_language";
include ("header.php");
if ($proceed) {
    $allow=check_allow('lang_symbol_edit','lang_main.php');
}

if ($proceed) {
    echo '<center>';

    // load languages
    $languages=get_languages();

    if (isset($_REQUEST['el']) && $_REQUEST['el'] && in_array($_REQUEST['el'],$languages)) {
        $el=$_REQUEST['el'];
    } else {
        $el=$settings['admin_standard_language'];
    }

    if (isset($_REQUEST['search']) && $_REQUEST['search']) $search=$_REQUEST['search']; else $search='';

    if (isset($_REQUEST['letter']) && $_REQUEST['letter']) $letter=$_REQUEST['letter']; else $letter='a';

    if (isset($_REQUEST['alter_lang']) && $_REQUEST['alter_lang'] && isset($_REQUEST['symbols']) && is_array($_REQUEST['symbols'])) {
        $pars=array();
        foreach ($_REQUEST['symbols'] as $symbol => $content) {
            $pars[]=array(':content'=>trim($content),':symbol'=>$symbol);
        }
        $query="UPDATE ".table('lang')."
                SET ".$el."= :content
                WHERE content_name= :symbol
                AND content_type='lang'";
        $done=or_query($query,$pars);
        message(lang('changes_saved'));
        log__admin("language_edit_symbols","language:".$edlang);
        redirect ('admin/lang_edit.php?el='.$el.'&letter='.$letter.'&search='.$search);
    }
}

if ($proceed) {

    if ($search) {
        $letter="";
        $lpars=array(':search1'=>'%'.$search.'%',
                    ':search2'=>'%'.$search.'%',
                    ':search3'=>'%'.$search.'%');
        $lquery="select * from ".table('lang')."
                where content_type='lang'
                and (content_name LIKE :search1
                or ".lang('lang')." LIKE :search2
                or ".$el." LIKE :search3)
                AND content_name NOT IN ('lang','lang_name','lang_icon_base64')
                order by content_name";
    } else {
        $search="";
        $lpars=array(':letter'=>$letter);
        $lquery="select * from ".table('lang')."
                where content_type='lang'
                and left(content_name,1)= :letter
                AND content_name NOT IN ('lang','lang_name','lang_icon_base64')
                order by content_name";
    }

    echo '<FORM action="lang_edit.php">
        <INPUT type=hidden name="el" value="'.$el.'">
        <INPUT type=hidden name="letter" value="'.$letter.'">
        <INPUT type=text name="search" size=20 maxlength=200 value="'.$search.'">
        <INPUT class="button" type=submit name=dosearch value="'.lang('search').'">
        </FORM><BR>';


    $query="select lower(left(content_name,1)) as letter,
            count(lang_id) as number
            from ".table('lang')."
            where content_type='lang' GROUP BY letter";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['letter']!=$letter) echo '<A HREF="lang_edit.php?el='.$el.'&letter='.$line['letter'].'">'.$line['letter'].'</A>&nbsp; ';
        else echo $letter.'&nbsp; ';
    }

    $result=or_query($lquery,$lpars);
    $number=pdo_num_rows($result);

    echo '<BR><BR>'.lang('symbols').': '.$number.'<BR><BR>

        <FORM action="lang_edit.php" method=post>
        <INPUT type=hidden name="el" value="'.$el.'">
        <INPUT type=hidden name="letter" value="'.$letter.'">
        <INPUT type=hidden name="search" value="'.$search.'">
        <TABLE class="or_listtable" style="width: 95%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD colspan=4 align=center>
                    <INPUT class="button" type=submit name="alter_lang" value="'.lang('change').'">
                </TD>
            </TR>
            <TR  style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD><B>'.lang('symbol').'</B></TD>
                <TD><B>'.lang('lang').'</B></TD>
                <TD><B>'.$el.'</B></TD>
                <TD></TD>
            </TR>
            </thead>
            <tbody>';

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '  <TR';
        if ($shade) { echo ' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
        else { echo ' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
        echo '>
                <TD>'.$line['content_name'].'</TD>
                <TD>'.$lang[$line['content_name']].'</TD>
                <TD>
                    <textarea rows=2 cols=30 wrap=virtual name="symbols['.$line['content_name'].']">'.
                        trim(stripslashes($line[$el])).'</textarea>
                </TD>
                <TD>
                    <A HREF="lang_symbol_edit.php?lang_id='.$line['lang_id'].'">'.lang('edit').'</A>
                </TD>
            </TR>
            ';
    }

    echo '      </tbody>
                <tfoot><TR>
                <TD colspan=3 align=center>
                    <INPUT class="button" type=submit name=alter_lang value="'.lang('change').'">
                </TD>
            </TR></tfoot>
        </TABLE>
        </FORM>';

    echo '  <BR><BR>'.button_link('lang_symbol_edit.php?go=true',
                        lang('add_symbol'),'plus-circle');

    echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';
}
include ("footer.php");
?>