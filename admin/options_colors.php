<?php
// part of orsee. see orsee.org
ob_start();

$title="edit_colors";
$jquery=array('colorpicker');
$menu__area="options_main";
include ("header.php");
if ($proceed) {
    $allow=check_allow('settings_view_colors','options_main.php');
}

if ($proceed) {
    $color_styles=options__get_color_styles();
    $styles=options__get_styles();
}


if ($proceed) {
    if (isset($_REQUEST['style']) && $_REQUEST['style'] ) {
        $style=trim($_REQUEST['style']);
    } else $style="";

    if (!$style && isset($settings['orsee_admin_style']) && $settings['orsee_admin_style'])
        $style=$settings['orsee_admin_style'];

    if ($style && !in_array($style,explode(',',$styles))) $style='orsee';

    if (!$style) {
        if (isset($color_styles[0]) && $color_styles[0]) $style=$color_styles[0];
        else $style='orsee';
    }
}


if ($proceed) {
    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 80%;">
            <TR><TD align="center">
            '.lang('style').' '.$style.'
            </TD>
            </TR></TABLE>';
    echo '
            <TABLE style="width: 80%;">
            <TR><TD align="right">
            <FORM id="styleform" action="'.thisdoc().'" method="GET">'.lang('edit_colors_for_style').survey__render_select_list(array('submitvarname'=>'style',
                                            'option_values'=>$styles,
                                            'option_values_lang'=>$styles,
                                            'value'=>$style,
                                            'include_none_option'=>'n')).
                        '<INPUT class="button" type="submit" value="'.lang('go').'"></FORM>
            </TD>
            </TR></TABLE>';

    $pars=array(':style'=>$style);
    $query="select * from ".table('options')."
            where option_type='color'
            and option_style= :style
            order by option_name";
    $result=or_query($query,$pars);
    $mycolors=array();
    while ($line=pdo_fetch_assoc($result)) {
        $mycolors[$line['option_name']]=$line['option_value'];
    }

    if (check_allow('settings_edit_colors') && isset($_REQUEST['change']) && $_REQUEST['change']) {
        $newcolors=$_REQUEST['mycolors']; $now=time();
        $pars_new=array(); $pars_update=array();
        foreach ($newcolors as $oname => $ovalue) {
            if (isset($mycolors[$oname])) {
                $pars_update[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':style'=>$style);
            } else {
                $pars_new[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':style'=>$style,
                                    ':now'=>$now);
                $now++;
            }
        }
        if (count($pars_update)>0) {
            $query="UPDATE ".table('options')."
                    SET option_value= :value
                    WHERE option_name= :name
                    AND option_style= :style
                    AND option_type= 'color'";
            $done=or_query($query,$pars_update);
        }
        if (count($pars_new)>0) {
            $query="INSERT INTO ".table('options')." SET
                option_id= :now,
                option_name= :name,
                option_value= :value,
                option_style= :style,
                option_type= 'color'";
            $done=or_query($query,$pars_new);
        }
        message(lang('changes_saved'));
        log__admin("options_colors_edit","style:".$style);
        redirect ('admin/options_colors.php?style='.$style);
    }
}

if ($proceed) {
    if (check_allow('settings_edit_colors')) echo '
        <FORM action="options_colors.php" method=post>
        <INPUT type=hidden name="style" value="'.$style.'">';
    echo '<TABLE class="or_formtable" style="width: 80%;">';
    if (check_allow('settings_edit_colors')) echo '
            <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="change" value="'.lang('change').'">
                </TD>
            </TR>';

    foreach ($system__colors as $c) {
        $done=options__show_color_option($c);
    }


    if (check_allow('settings_edit_colors')) echo '
            <TR>
                <TD colspan=2 align=center>
                    <INPUT class="button" type=submit name="change" value="'.lang('change').'">
                </TD>
            </TR>';
    echo '</TABLE>';
    if (check_allow('settings_edit_colors')) echo '</FORM>';

    echo "<script type='text/javascript'>
            $('.colorpickerinput').colpick({
                layout:'hex',
                colorScheme:'dark',
                onChange:function(hsb,hex,rgb,el,bySetColor) {
                    /* $(el).css('border-color','#'+hex);
                        seems to be confusing to change color on spot*/
                },
                onSubmit:function(hsb,hex,rgb,el) {
                    $(el).css('border-color', '#'+hex);
                    $(el).val('#'+hex);
                    $(el).colpickHide();
                },
                onBeforeShow:function(colpkr) {
                    $(this).colpickSetColor(this.value);
                }
            }).keyup(function(){
                $(this).colpickSetColor(this.value);
            }); ";

    if (!check_allow('settings_edit_colors')) echo '
            $(":input").attr("disabled", true);
            $("#styleform :input").attr("disabled", false);
            ';

    echo "</script>";

    echo '</center>';

}
include ("footer.php");
?>