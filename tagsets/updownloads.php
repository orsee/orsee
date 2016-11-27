<?php
// part of orsee. see orsee.org

function downloads__list_files_general($showsize=false,$showtype=false,$showdate=false) {
    global $lang, $color;
    $out='';

    $continue=true;
    if ($continue) {
            if(!check_allow('file_view_general')) $continue=false;
    }

    if ($continue) {
        $allow_dl=check_allow('file_download_general');
        $allow_delete=check_allow('file_delete_general');
        $allow_edit=check_allow('file_edit_general');


        $query="SELECT *
                FROM ".table('uploads')."
                WHERE experiment_id = 0
                ORDER BY upload_type, upload_name, upload_id";
        $result=or_query($query);
        if (pdo_num_rows($result) > 0) {
            $shade=true;
            $categories=lang__load_lang_cat('file_upload_category');
            $uptype='';
            $cols=2;
            if ($showsize) $cols++;
            if ($showtype) $cols++;
            if ($showdate) $cols++;
            if ($allow_edit) $cols++;
            if ($allow_delete) $cols++;
            $out.= '<TABLE width="100%" border=0 cellspacing="0">';
            while ($upload = pdo_fetch_assoc($result)) {
                if ($shade) { $bgcolor=' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
                else { $bgcolor=' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
                if ($upload['upload_type']!=$uptype) {
                    $uptype=$upload['upload_type'];
                    $out.= '<TR bgcolor="'.$color['list_shade_subtitle'].'">
                        <TD colspan='.$cols.'>';
                    if (isset($categories[$uptype])) $out.=$categories[$uptype];
                    else $out.='???';
                    $out.='</TD></TR>';
                }
                $out.= '<TR'.$bgcolor.'><TD>&nbsp;&nbsp;</TD><TD>';
                if ($allow_dl) $out.= '<A HREF="download_file.php'.
                        '/'.rawurlencode($upload['upload_name'].'.'.$upload['upload_suffix']).
                        '?t=d&i='.$upload['upload_id'].'">';
                $out.= $upload['upload_name'];
                if ($allow_dl) $out.= '</A>';
                $out.= '</TD>';
                if ($showsize) $out.= '<TD>'.number_format(round($upload['upload_filesize']/1024),0).' KB</TD>';
                if ($showtype) $out.= '<TD>'.$upload['upload_suffix'].'</TD>';
                if ($showdate) $out.= '<TD>'.ortime__format($upload['upload_id'],'',lang('lang')).'</TD>';
                if ($allow_edit) {
                    $out.= '<TD>';
                    $out.= '<A HREF="download_edit.php?file='.$upload['upload_id'].
                            '"><FONT class="small">['.lang('edit').']</FONT></A>';
                    $out.= '</TD>';
                }
                if ($allow_delete) {
                    $out.= '<TD>';
                    $out.= '<A HREF="download_delete.php?dl='.$upload['upload_id'].
                            '"><FONT class="small">['.lang('delete').']</FONT></A>';
                    $out.= '</TD>';
                }
                $out.= '</TR>';
            }
            $out.= '</TABLE>';
        }
    }
    return $out;
}

function downloads__list_files_experiment($experiment_id,$showsize=false,$showtype=false,$showdate=false) {
    global $lang, $color, $expadmindata;
    $out='';

    $continue=true;
    if ($continue) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!isset($experiment['experiment_id'])) $continue=false;
    }

    if ($continue) {
        $experimenters=db_string_to_id_array($experiment['experimenter']);
        if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_view_experiment_my'))
                    || check_allow('file_view_experiment_all')) ) $continue=false;
    }

    if ($continue) {
        if (check_allow('file_download_experiment_all')) $allow_dl=true;
        elseif (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_download_experiment_my'))  $allow_dl=true;
        else  $allow_dl=false;
        if (check_allow('file_delete_experiment_all')) $allow_delete=true;
        elseif (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_delete_experiment_my'))  $allow_delete=true;
        else  $allow_delete=false;
        if (check_allow('file_edit_experiment_all')) $allow_edit=true;
        elseif (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_edit_experiment_my'))  $allow_edit=true;
        else  $allow_edit=false;

        $query="SELECT ".table('uploads').".*, ".table('sessions').".session_start
                FROM ".table('uploads')." LEFT JOIN ".table('sessions')."
                ON ".table('uploads').".session_id = ".table('sessions').".session_id
                WHERE ".table('uploads').".experiment_id= :experiment_id
                ORDER BY session_start, upload_type, upload_name, upload_id";
        $pars=array(':experiment_id'=>$experiment_id);
        $result=or_query($query,$pars);
        if (pdo_num_rows($result) > 0) {
            $shade=true;
            $categories=lang__load_lang_cat('file_upload_category');
            $uptype=-1; $tsession_id=-1;
            $cols=3;
            if ($showsize) $cols++;
            if ($showtype) $cols++;
            if ($showdate) $cols++;
            if ($allow_edit) $cols++;
            if ($allow_delete) $cols++;
            $out.= '<TABLE width="100%" border=0 cellspacing="0">';
            while ($upload = pdo_fetch_assoc($result)) {
                if ($shade) { $bgcolor=' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
                else { $bgcolor=' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
                if ($upload['session_id']!=$tsession_id) {
                    $tsession_id=$upload['session_id'];
                    $uptype=0;
                    $out.= '<TR bgcolor="'.$color['list_shade_subtitle'].'">
                        <TD colspan='.$cols.'>';
                    if ($upload['session_id']>0) $out.='<i>'.lang('session').' '.ortime__format(ortime__sesstime_to_unixtime($upload['session_start'])).'</i>';
                    else $out.='<i>'.lang('no_session').'</i>';
                    $out.='</TD></TR>';
                }
                if ($upload['upload_type']!=$uptype) {
                    $uptype=$upload['upload_type'];
                    $out.= '<TR bgcolor="'.$color['list_shade_subtitle'].'">
                        <TD>&nbsp;</TD>
                        <TD colspan='.($cols-1).'>';
                    if (isset($categories[$uptype])) $out.=$categories[$uptype];
                    else $out.='???';
                    $out.='</TD></TR>';
                }
                $out.= '<TR'.$bgcolor.'><TD>&nbsp;&nbsp;</TD><TD>';
                if ($allow_dl) $out.= '<A HREF="download_file.php'.
                            '/'.rawurlencode($upload['upload_name'].'.'.$upload['upload_suffix']).
                            '?t=d&i='.$upload['upload_id'].'">';
                $out.= $upload['upload_name'];
                if ($allow_dl) $out.= '</A>';
                $out.= '</TD>';
                if ($showsize) $out.= '<TD>'.number_format(round($upload['upload_filesize']/1024),0).' KB</TD>';
                if ($showtype) $out.= '<TD>'.$upload['upload_suffix'].'</TD>';
                if ($showdate) $out.= '<TD>'.ortime__format($upload['upload_id'],'',lang('lang')).'</TD>';
                if ($allow_edit) {
                    $out.= '    <TD>';
                    $out.= '    <A HREF="download_edit.php?file='.$upload['upload_id'].
                                '"><FONT class="small">['.lang('edit').']</FONT></A>';
                    $out.= '    </TD>';
                }
                if ($allow_delete) {
                    $out.= '    <TD>';
                    $out.= '    <A HREF="download_delete.php?dl='.$upload['upload_id'].
                                '"><FONT class="small">['.lang('delete').']</FONT></A>';
                    $out.= '    </TD>';
                }
                $out.= '</TR>';
            }
            $out.= '</TABLE>';
        }
    }
    return $out;
}

function downloads__list_experiments($showsize=false,$showtype=false,$showdate=false) {
    global $lang, $color, $expadmindata;

    $out='';
    $continue=true;
    if (check_allow('file_view_experiment_all')) {
        $experimenter_clause = '';
        $pars=array();
    } elseif (check_allow('file_view_experiment_my')) {
        $experimenter_clause = " AND ".table('experiments').".experimenter LIKE :experimenter ";
        $pars=array(':experimenter'=>'%|'.$expadmindata['admin_id'].'|%');
    } else $continue=false;

    if ($continue) {
        $query="SELECT ".table('experiments').".*,
                (SELECT min(session_start) from or_sessions as s1 WHERE s1.experiment_id=".table('experiments').".experiment_id) as first_session_date,
                (SELECT max(session_start) from or_sessions as s2 WHERE s2.experiment_id=".table('experiments').".experiment_id) as last_session_date
                FROM ".table('experiments')."
                WHERE ".table('experiments').".experiment_id IN
                (SELECT DISTINCT experiment_id FROM ".table('uploads').")
                ".$experimenter_clause."
                ORDER BY last_session_date DESC";
        $result=or_query($query,$pars); $experiments=array();
        while ($line = pdo_fetch_assoc($result)) {
            $experiments[]=$line;
        }
        
        if (count($experiments)>0) {
            $out.='<TABLE width=100% border=0>';
            $shade=true;
            foreach($experiments as $exp) {
                if ($shade) { $bgcolor=' bgcolor="'.$color['list_shade1'].'"'; $shade=false; }
                else { $bgcolor=' bgcolor="'.$color['list_shade2'].'"'; $shade=true; }
                $out.='<TR'.$bgcolor.'><TD>';
                $out.=$exp['experiment_name'].'</TD><TD>(';
                $out.=lang('from').' ';
                if ($exp['first_session_date']==0) {
                    $out.='???';
                } else {
                    $out.=ortime__format(ortime__sesstime_to_unixtime($exp['first_session_date']),'hide_time:true');
                }
                $out.=' '.lang('to').' ';
                if ($exp['last_session_date']==0) {
                    $out.='???';
                } else {
                    $out.=ortime__format(ortime__sesstime_to_unixtime($exp['last_session_date']),'hide_time:true');
                }
                $out.=')</TD><TD>';
                $out.=experiment__list_experimenters($exp['experimenter'],true,true);
                $out.='</TD><TD><A HREF="download_main.php?experiment_id='.$exp['experiment_id'].'">'.
                    lang('show_files').'</A>';
                $out.='</TD></TR>';
            }
            $out.='</TABLE>';
        }
    }
    return $out;
}

function downloads__mime_type($extension) {

$mime_mappings='
application/activemessage
application/andrew-inset
application/applefile
application/atomicmail
application/dca-rft
application/dec-dx
application/x-java            class
application/x-javascript      js
application/mac-binhex40      hqx
application/macwriteii        mii
application/vnd.ms-powerpoint pot pps ppt ppz
application/msword            doc
application/news-message-id
application/news-transmission
application/octet-stream      bat BAT bin class dbf lha lzh lzx sjf
application/oda               oda
application/data              exe
application/pdf               pdf
application/postscript        ai eps ps
application/remote-printing
application/rtf               rtf
application/slate
application/smil              smil smi sml
application/x-mif             mif
application/wita
application/wordperfect5.1
application/x-csh             csh
application/x-dvi             dvi
application/x-hdf             hdf
application/x-latex           latex
application/x-netcdf          nc cdf
application/x-sh              sh
application/x-tcl             tcl
application/x-tex             tex
application/x-texinfo         texinfo texi
application/x-troff           t tr roff
application/x-troff-man       man
application/x-troff-me        me
application/x-troff-ms        ms
application/x-wais-source     src
application/zip               zip
application/x-bcpio           bcpio
application/x-cpio            cpio
application/x-gtar            gtar
application/x-shar            shar
application/x-sv4cpio         sv4cpio
application/x-sv4crc          sv4crc
application/x-tar             tar tgz gz
application/x-ustar           ustar
application/vnd.ms-excel      xls
application/xls
audio/basic                   au snd
audio/x-aiff                  aif aiff aifc
audio/x-wav                   wav
audio/midi                    mid
image/gif                     gif
image/ief                     ief
image/jpeg                    jpeg jpg jpe
image/tiff                    tiff tif
image/x-cmu-raster            ras
image/x-portable-anymap       pnm
image/x-portable-bitmap       pbm
image/x-portable-graymap      pgm
image/x-portable-pixmap       ppm
image/x-rgb                   rgb
image/x-xbitmap               xbm
image/x-xpixmap               xpm
image/x-xwindowdump           xwd
message/external-body
message/news
message/partial
message/rfc822
metahtml/interpreted          mhtml
metahtml/verbatim             vmhtml
multipart/alternative
multipart/appledouble
multipart/digest
multipart/mixed
multipart/parallel
text/html                     html htm php php3 php4 phtml
text/html                     default
text/plain                    txt java
text/richtext                 rtx
text/tab-separated-values     tsv
text/x-setext                 etx
video/mpeg                    mpeg mpg mpe
video/quicktime               qt mov
video/x-msvideo               avi
video/x-sgi-movie             movie
application/x-director        dcr dir dxr
application/x-flash           swf
application/ztree             ztt
';

    $mappings=explode("\n",$mime_mappings);

    foreach ($mappings as $mapping) {
        if ($mapping) {
            $valuestr=preg_replace("/[ \t]+/","\n",trim($mapping));
            $valuearr=explode("\n",$valuestr);
            $i=0;
            $this_mime_type=$valuearr[0];
            foreach ($valuearr as $this_extension) {
                if ($i>0) $mime_type[$this_extension]=$this_mime_type;
                $i++;
                }
            }
        }
    if (isset($mime_type[$extension]))
        return $mime_type[$extension];
    else return '';
}

?>
