<?php
// part of orsee. see orsee.org


function navigation($orientation="vertical") {
    global $expadmindata, $lang, $navigation_disabled, $color;
    $out="";
   if (!(isset($navigation_disabled) && $navigation_disabled)) {
        if (isset($expadmindata['adminname'])) {
            $icons=true;
            $now=time();
            $current_user_data_box=
                lang('admin_area').'<BR>'.
                lang('user').': <FONT color="'.$color['menu_item'].'">'.
                $expadmindata['adminname'].'</FONT><BR>'.
                lang('date').': <FONT color="'.$color['menu_item'].'">'.
                ortime__format($now,'hide_time:true,hide_year:true',$expadmindata['language']).'</FONT><BR>'.
                lang('time').': <FONT color="'.$color['menu_item'].'">'.
                ortime__format($now,'hide_date:true',$expadmindata['language']).'</FONT>';
            $navfile=file ("../admin/navigation.php");
        } else {
            $icons=false;
            $current_user_data_box="";
            $navfile=file ("../public/navigation.php");
        }

        foreach ($navfile as $entry) if (trim($entry) && substr(trim($entry),0,2)!="//") $menuitems[]=trim($entry);
        $out=tab_menu($menuitems,$orientation,$current_user_data_box,$icons);
    }
    return $out;
}

function html__mobile_header() {
    global $pagetitle;
    echo '<!DOCTYPE html>
    <html>
        <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta http-equiv="expires" content="0">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>'.$pagetitle.'</title>
        <link rel="stylesheet" href="../tagsets/jquery/jquery.mobile.css" />
        <script src="../tagsets/jquery/jquery.js"></script>
        <script>
            $(document).bind("mobileinit", function(){
                $.mobile.loadingMessageTheme = "b";
                $.mobile.loadingMessageTextVisible = true;
                $.mobile.loadingMessage = "";
            });
        </script>
        <script src="../tagsets/jquery/jquery.mobile.js"></script>

    </head>
    <body>';

}

function html__mobile_footer() {
    echo '
    </body>
    </html>';
}

function include_jquery($name,$inc_css=true) {
    $use_min=true;
    if ($name) $name.='.';
    if ($use_min) $name=$name.'min.';
    if ($inc_css) echo '<link rel="stylesheet" type="text/css" href="../tagsets/jquery/jquery.'.$name.'css" />';
    echo '<script src="../tagsets/jquery/jquery.'.$name.'js"></script>'."\n";
}

function html__header() {
    global $pagetitle,$settings, $color, $lang_icons_prepare;
    global $jquery;

echo '<HTML>
<HEAD>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta http-equiv="expires" content="0">
<TITLE>'.$pagetitle.'</TITLE>
<link rel="stylesheet" type="text/css" href="../style/'.$settings['style'].'/stylesheet.css">
<link rel="stylesheet" href="../tagsets/icons.fa.css">
<link rel="stylesheet" href="../tagsets/icons.css">
';


if (thisdoc()=="admin_login.php" && (!(isset($settings['disable_admin_login_js']) && $settings['disable_admin_login_js']=='y'))) {
    script__login_page();
}

if (isset($jquery) && is_array($jquery)) {
    include_jquery('',false);
    include_jquery('json2',false);
    if (in_array('datepicker',$jquery)) {
        include_jquery('datepick');
    }
    if (in_array('clockpicker',$jquery)) {
        include_jquery('clockpick');
    }
    if (in_array('arraypicker',$jquery)) {
        include_jquery('arraypick');
    }
    if (in_array('colorpicker',$jquery)) {
        include_jquery('colorpicker');
    }
    if (in_array('textext',$jquery)) {
        include_jquery('textext');
    }
    if (in_array('dropit',$jquery)) {
        include_jquery('dropit');
    }
    if (in_array('listtool',$jquery)) {
        include_jquery('listtool');
    }
    if (in_array('switchy',$jquery)) {
        include_jquery('switchy');
    }
    if (in_array('queryform',$jquery)) {
        include_jquery('queryform');
    }
    if (in_array('popup',$jquery)) {
        include_jquery('bpopup');
    }
}

    echo '<style type="text/css">';
    if (isset($lang_icons_prepare) && $lang_icons_prepare) {
        lang_icons_prepare();
    }
    echo '</style>';

echo '
</HEAD>
<body';
if (isset($color['body_text'])) echo ' text="'.$color['body_text'].'"';
if (isset($color['body_link'])) echo ' link="'.$color['body_link'].'"';
if (isset($color['body_vlink'])) echo ' vlink="'.$color['body_vlink'].'"';
if (isset($color['body_alink'])) echo ' alink="'.$color['body_alink'].'"';
if (isset($color['shade_around_content'])) echo ' bgcolor="'.$color['shade_around_content'].'"';
echo ' TOPMARGIN=0 LEFTMARGIN=0 MARGINWIDTH=0 MARGINHEIGHT=0';
if (thisdoc()=="admin_login.php" && (!(isset($settings['disable_admin_login_js']) && $settings['disable_admin_login_js']=='y'))) {
    echo ' onload="gotoUsername();"';
}
echo '>
';

}


function html__footer() {

echo '
</BODY>
</HTML>';

}

function html__show_style_header($area='public',$title="") {
    global $settings, $lang, $color, $expadmindata, $authdata, $navigation_disabled, $show_logged_in_menu;

    $tpl=file_get_contents('../style/'.$settings['style'].'/html_header.php');

    // fill colors
    foreach ($color as $k=>$o) $tpl=str_replace("#".$k."#",$o,$tpl);

    // add title
    $tpl=str_replace("#title#",$title,$tpl);


    // prepare menu
   if (!(isset($navigation_disabled) && $navigation_disabled)) {
        if ($area=='admin' && isset($expadmindata['adminname'])) {
            $logged_in=true;
            $now=time();
            $current_user_data_box=
                lang('admin_area').'<BR>'.
                lang('user').': <FONT color="'.$color['menu_item'].'">'.
                $expadmindata['adminname'].'</FONT><BR>'.
                lang('date').': <FONT color="'.$color['menu_item'].'">'.
                ortime__format($now,'hide_time:true,hide_year:true',$expadmindata['language']).'</FONT><BR>'.
                lang('time').': <FONT color="'.$color['menu_item'].'">'.
                ortime__format($now,'hide_date:true',$expadmindata['language']).'</FONT>';
            $menu=html__get_admin_menu();
        } else {
            $current_user_data_box="";
            if((isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in'])
                || $show_logged_in_menu) $logged_in=true;
            else $logged_in=false;
            $menu=html__get_public_menu();
        }
        $tpl=str_replace("#navigation#",html__build_menu($menu,$logged_in,$current_user_data_box,'vertical'),$tpl);
        $tpl=str_replace("#navigation_horizontal#",html__build_menu($menu,$logged_in,$current_user_data_box,'horizontal'),$tpl);
    } else {
        $tpl=str_replace("#navigation#",'',$tpl);
        $tpl=str_replace("#navigation_horizontal#",'',$tpl);
    }
    // fill in language terms if any
        $pattern="/lang\[([^\]]+)\]/i";
        $replacement = "\$lang['$1']";
        $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

    echo $tpl;
}

function html__show_style_footer($area='public') {
    global $settings, $lang, $color, $expadmindata, $authdata, $navigation_disabled, $show_logged_in_menu;

    $tpl=file_get_contents('../style/'.$settings['style'].'/html_footer.php');

    // fill colors
    foreach ($color as $k=>$o) $tpl=str_replace("#".$k."#",$o,$tpl);

    // fill in language terms if any
        $pattern="/lang\[([^\]]+)\]/i";
        $replacement = "\$lang['$1']";
        $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

    echo $tpl;
}



function html__get_admin_menu() {
    global $settings;
    $menu=array();
    $menu[]=    array(
                            'menu_area'=>'current_user_data_box',
                            'entrytype'=>'head',
                            'lang_item'=>'admindata',
                            'link'=>'',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=                    array(
                            'menu_area'=>'mainpage',
                            'entrytype'=>'headlink',
                            'lang_item'=>'mainpage',
                            'link'=>'/admin/',
                            'icon'=>'home',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'experiments',
                            'entrytype'=>'head',
                            'lang_item'=>'experiments',
                            'link'=>'',
                            'icon'=>'cogs',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'experiments_main',
                            'entrytype'=>'link',
                            'lang_item'=>'overview',
                            'link'=>'/admin/experiment_main.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'experiments_my',
                            'entrytype'=>'link',
                            'lang_item'=>'my_experiments',
                            'link'=>'/admin/experiment_my.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'experiments_new',
                            'entrytype'=>'link',
                            'lang_item'=>'create_new',
                            'link'=>'/admin/experiment_edit.php?addit=true',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'experiments_old',
                            'entrytype'=>'link',
                            'lang_item'=>'finished_experiments',
                            'link'=>'/admin/experiment_old.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'participants',
                            'entrytype'=>'head',
                            'lang_item'=>'participants',
                            'link'=>'',
                            'icon'=>'users',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'participants_main',
                            'entrytype'=>'link',
                            'lang_item'=>'overview',
                            'link'=>'/admin/participants_main.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'participants_create',
                            'entrytype'=>'link',
                            'lang_item'=>'create_new',
                            'link'=>'/admin/participants_edit.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=                    array(
                            'menu_area'=>'experiment_calendar',
                            'entrytype'=>'headlink',
                            'lang_item'=>'calendar',
                            'link'=>'/admin/calendar_main.php',
                            'icon'=>'calendar',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
if ($settings['enable_email_module']=='y') {
$menu[]=                    array(
                            'menu_area'=>'emails',
                            'entrytype'=>'headlink',
                            'lang_item'=>'emails',
                            'link'=>'/admin/emails_main.php',
                            'icon'=>'envelope-o',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
}
$menu[]=
                    array(
                            'menu_area'=>'files',
                            'entrytype'=>'headlink',
                            'lang_item'=>'files',
                            'link'=>'/admin/download_main.php',
                            'icon'=>'download',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'options',
                            'entrytype'=>'headlink',
                            'lang_item'=>'options',
                            'link'=>'/admin/options_main.php',
                            'icon'=>'gavel',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=
                    array(
                            'menu_area'=>'statistics',
                            'entrytype'=>'headlink',
                            'lang_item'=>'statistics',
                            'link'=>'/admin/statistics_main.php',
                            'icon'=>'bar-chart-o',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
$menu[]=                    array(
                            'menu_area'=>'logout',
                            'entrytype'=>'headlink',
                            'lang_item'=>'logout',
                            'link'=>'/admin/admin_logout.php',
                            'icon'=>'sign-out',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
    return $menu;
}

function html__get_public_menu() {
    global $settings;
    $menu=array();
    $menu[]=        array(
                            'menu_area'=>'mainpage',
                            'entrytype'=>'headlink',
                            'lang_item'=>'mainpage',
                            'link'=>'/public/index.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
    $menu[]=        array(
                            'menu_area'=>'public_register',
                            'entrytype'=>'link',
                            'lang_item'=>'register',
                            'link'=>'/public/participant_create.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>0
                            );
if ($settings['subject_authentication']!='token') {
    $menu[]=        array(
                            'menu_area'=>'login',
                            'entrytype'=>'link',
                            'lang_item'=>'login',
                            'link'=>'/public/participant_login.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>0
                            );
}

    $menu[]=        array(
                            'menu_area'=>'my_data',
                            'entrytype'=>'link',
                            'lang_item'=>'my_data',
                            'link'=>'/public/participant_edit.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
    $menu[]=        array(
                            'menu_area'=>'my_registrations',
                            'entrytype'=>'link',
                            'lang_item'=>'my_registrations',
                            'link'=>'/public/participant_show.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
if ($settings['subject_authentication']!='token') {
    $menu[]=        array(
                            'menu_area'=>'logout',
                            'entrytype'=>'link',
                            'lang_item'=>'logout',
                            'link'=>'/public/participant_logout.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
}
    $menu[]=            array(
                            'menu_area'=>'calendar',
                            'entrytype'=>'headlink',
                            'lang_item'=>'calendar',
                            'link'=>'/public/show_calendar.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
$menu[]=            array(
                            'menu_area'=>'rules',
                            'entrytype'=>'headlink',
                            'lang_item'=>'rules',
                            'link'=>'/public/rules.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
$menu[]=            array(
                            'menu_area'=>'privacy',
                            'entrytype'=>'headlink',
                            'lang_item'=>'privacy_policy',
                            'link'=>'/public/privacy.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
$menu[]=            array(
                            'menu_area'=>'faqs',
                            'entrytype'=>'headlink',
                            'lang_item'=>'faqs',
                            'link'=>'/public/faq.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
$menu[]=            array(
                            'entrytype'=>'space'
                            );
$menu[]=            array(
                            'menu_area'=>'impressum',
                            'entrytype'=>'link',
                            'lang_item'=>'impressum',
                            'link'=>'/public/impressum.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
$menu[]=            array(
                            'menu_area'=>'contact',
                            'entrytype'=>'link',
                            'lang_item'=>'contact',
                            'link'=>'/public/contact.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
    return $menu;
}


function html__build_menu($menu,$logged_in,$current_user_data_box,$orientation="vertical") {
    global $settings__root_url, $color, $lang, $menu__area, $settings;

    $addp="";
    $ignore_p=array('participant_create.php','participant_confirm.php','participant_forgot.php');
    if (in_array($settings['subject_authentication'],array('token','migration'))) {
        if (isset($_REQUEST['p']) && !(in_array(thisdoc(),$ignore_p))) $addp="?p=".urlencode($_REQUEST['p']);
    }

    $list='';

    $final_menu=array();
    foreach ($menu as $item) {
        $continue=true;
        if ($continue && $item['entrytype']!='space') {
            $continue=false;
            if ($item['show_if_not_logged_in'] && !$logged_in) $continue=true;
            if ($item['show_if_logged_in'] && $logged_in) $continue=true;
        }
        if ($continue && $item['entrytype']!='space') {
            if ($item['menu_area']=='calendar' && $settings['show_public_calendar']!='y') $continue=false;
            elseif ($item['menu_area']=='rules' && $settings['show_public_rules_page']!='y') $continue=false;
            elseif ($item['menu_area']=='privacy' && $settings['show_public_privacy_policy']!='y') $continue=false;
            elseif ($item['menu_area']=='faqs' && $settings['show_public_faqs']!='y') $continue=false;
            elseif ($item['menu_area']=='impressum' && $settings['show_public_legal_notice']!='y') $continue=false;
            elseif ($item['menu_area']=='contact' && $settings['show_public_contact']!='y') $continue=false;
        }
        if ($continue) {
            if ($item['entrytype']=='space') {
                $item['entrytype']='head';
                $item['menu_area']='';
                $item['content']='';
                $item['bg']='';
            } elseif ($item['menu_area']=='current_user_data_box') {
                $item['entrytype']='head';
                $item['content']='<FONT class="menu_title" color="'.$color['menu_title'].'">'.$current_user_data_box.'</FONT>';
                $item['bg']='';
            } else {
                if (preg_match("/^".$item['menu_area']."/i",$menu__area)) $item['bg']=' bgcolor="'.$color['menu_item_highlighted_background'].'"';
                else $item['bg']="";
                if (!isset($item['link'])) $link='';
                elseif (substr($item['link'],0,1)=='/') $link=$settings__root_url.$item['link'].$addp;
                else $link=$item['link'];
                if ($item['entrytype']=='link') $item['content']='<A HREF="'.$link.'" class="menu_item"><FONT color="'.$color['menu_item'].'">';
                elseif ($item['entrytype']=='headlink') $item['content']='<A HREF="'.$link.'" class="menu_title"><FONT color="'.$color['menu_title'].'">';
                elseif ($item['entrytype']=='head') $item['content']='<FONT class="menu_title" color="'.$color['menu_title'].'">';
                $item['content'].=lang($item['lang_item']);
                if ($item['entrytype']=='link') $item['content'].= '</FONT></A>';
                elseif ($item['entrytype']=='headlink') $item['content'].= '</FONT></A>';
                elseif ($item['entrytype']=='head') $item['content'].= '</FONT>';
                if ($item['entrytype']!='link') $item['entrytype']='head';
            }
        }
        if ($continue) {
            $final_menu[]=$item;
        }
    }
    if ($orientation=="vertical") {
        $list.='<TABLE border=0>';
        foreach ($final_menu as $item) {
            if (!isset($item['link'])) $link='';
            elseif (substr($item['link'],0,1)=='/') $link=$settings__root_url.$item['link'].$addp;
            else $link=$item['link'];
            if ($item['entrytype']=='head') $list.='<tr><td colspan="3">&nbsp;</td></tr>';
            $list.='<TR>';
            if ($item['entrytype']!='head') $list.='<TD>&nbsp;</TD>';
            if (isset($item['icon']) && $item['icon']) $list.= '<TD '.$item['bg'].'>'.micon($item['icon'],$link).'</TD>';
            else $list.= '<TD></TD>';
            $list.='<TD '.$item['bg'];
            if ($item['entrytype']=='head') $list.=' colspan="2"';
            $list.='>'.$item['content'].'</TD></TR>';
        }
        $list.= '</TABLE>';
    } else {
        $user_data_box='';
        $list1=''; $list2=''; $head_up=false;
        foreach ($final_menu as $item) {
            if (!isset($item['link'])) $link='';
            elseif (substr($item['link'],0,1)=='/') $link=$settings__root_url.$item['link'].$addp;
            else $link=$item['link'];
            if ($item['menu_area']=='current_user_data_box') {
//              if ($head_up) $list2.='<TD colspan=2></TD>';
//              $head_up=false;
//              $list1.='<TD rowspan="2">'.$item['content'].'</TD>';
                $user_data_box=str_replace('<BR>','&nbsp;&nbsp;',$item['content']);
            } elseif ($item['entrytype']=='head') {
                if ($head_up) $list2.='<TD colspan=2></TD>';
                $list1.='<TD>&nbsp;&nbsp;&nbsp;</TD>'; $list2.='<TD>&nbsp;&nbsp;&nbsp;</TD>';
                if (isset($item['icon']) && $item['icon']) $list1.= '<TD '.$item['bg'].'>'.micon($item['icon'],$link).'</TD>';
                else $list1.= '<TD></TD>';
                $list1.='<TD '.$item['bg'].'>'.$item['content'].'</TD>';
                $head_up=true;
            } else {
                if (!$head_up) $list1.='<TD colspan=2></TD>';
                if (isset($item['icon']) && $item['icon']) $list2.= '<TD '.$item['bg'].'>'.micon($item['icon'],$link).'</TD>';
                else $list2.= '<TD></TD>';
                $list2.='<TD '.$item['bg'].'>'.$item['content'].'</TD>';
                $head_up=false;
            }
        }
        $list.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$user_data_box.'<BR>';
        $list.='<TABLE border=0>';
        $list.='<TR>'.$list1.'</TR><TR>'.$list2.'</TR>';
        $list.= '</TABLE>';
    }
    return $list;
}

function get_style_array() {
    global $settings__root_directory, $settings__root_to_server;

    // $path=$settings__root_to_server.$settings__root_directory."/style";
    $path="../style";

    $dir_arr = array () ;
    $handle=opendir($path);
    while ($file = readdir($handle)) {
            if ($file != "." && $file != ".." && is_dir($path."/".$file)) {
                $dir_arr[] = $file ;
                }
        }
    return $dir_arr ;
}

function button_link($link,$text,$icon="",$button_style="",$aextra="") {
    $out='<A HREF="'.$link.'" class="button';
    if ($icon) $out.=' fa-'.$icon;
    $out.='"';
    if ($icon || $button_style) {
        $out.=' style="';
        if ($icon) $out.='padding: 0 0.5em 0 1.5em; ';
        if ($button_style) $out.=$button_style;
        $out.='"';
    }
    if ($aextra) $out.=' '.$aextra;
    $out.='>'.$text.'</A>';
    return $out;
}

?>
