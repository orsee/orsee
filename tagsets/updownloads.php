<?php

// up and download functions for orsee. part of orsee. see orsee.org

function downloads__list_files($experiment_id='',$showsize=false,$showtype=false,$showdate=false) {
	global $lang;

	$allow_dl=check_allow('download_download');

	if ($experiment_id!='') $where_clause=" WHERE experiment_id='".$experiment_id."' ";
		else $where_clause='';

	$uptype='';
	$cols=3;
	if ($showsize) $cols++;
	if ($showtype) $cols++;
	if ($showdate) $cols++;

     	$query="SELECT *
      		FROM ".table('uploads')." ".
		$where_clause.
		" ORDER BY upload_type, upload_name, upload_id";

	$result=mysql_query($query);
	if (mysql_num_rows($result) > 0) {
		$shade=true;
		echo '<TABLE width="100%" border=0>';
		while ($upload = mysql_fetch_assoc($result)) {
			if ($shade) {
                        	$bgcolor=' bgcolor="'.$color['list_shade1'].'"';
                        	$shade=false;
                        	}
                   	else {
                        	$bgcolor=' bgcolor="'.$color['list_shade2'].'"';
                        	$shade=true;
                        	}
			if ($upload['upload_type']!=$uptype) {
				$uptype=$upload['upload_type'];
				echo '<TR bgcolor="'.$color['list_options_background'].'">
					<TD colspan='.$cols.'>'.$lang[$uptype].'</TD></TR>';
				}
			echo '<TR'.$bgcolor.'><TD>&nbsp;&nbsp;</TD><TD>';
			if ($allow_dl) 
				echo '<A HREF="download_file.php?t=d&i='.$upload['upload_id'].'" target="_blank">';
			echo $upload['upload_name'];
			if ($allow_dl) echo '</A>';
			echo '</TD>';
			if ($showsize) {
				echo '<TD>'.number_format(round($upload['upload_filesize']/1024),0).' KB</TD>';
				}
			if ($showtype) {
				echo '<TD>'.$upload['upload_suffix'].'</TD>';
				}
			if ($showdate) {
				echo '<TD>'.
				time__format($lang['lang'],"",false,false,true,false,$upload['upload_id']).
				'</TD>';
				}
			echo '	<TD>';
			if (($experiment_id==0 && check_allow('download_general_delete')) ||
				($experiment_id!=0 && check_allow('download_experiment_delete'))) {
				echo '	<A HREF="download_delete.php?dl='.$upload['upload_id'];
					if ($experiment_id) echo '&experiment_id='.$experiment_id;
					echo '"><FONT class="small">['.$lang['delete'].']</FONT></A>';
				}
			echo '	</TD>';
			echo '</TR>';
			}
		echo '</TABLE>';
		}
}

function downloads__files_downloadable($experiment_id=0) {

        if ($experiment_id>0) $where_clause=" WHERE experiment_id='".$experiment_id."' ";
                else $where_clause='';

     	$query="SELECT experiment_id
      		FROM ".table('uploads')." ".
		$where_clause.
		" LIMIT 1";
	$result=orsee_query($query);
	if (isset($result['experiment_id']))
		return true;
	   else return false;
}

function downloads__list_experiments($showsize=false,$showtype=false,$showdate=false) {
	global $lang;
	echo '<TABLE width=100% border=0>';

	$query="SELECT DISTINCT ".table('experiments').".*, ".table('uploads').".*
      		FROM ".table('experiments').", ".table('uploads')."
      		WHERE ".table('experiments').".experiment_id=".table('uploads').".experiment_id
		GROUP BY ".table('experiments').".experiment_id
		ORDER BY experiment_name";
	$result=mysql_query($query);
	$shade=true;
	while ($exp = mysql_fetch_assoc($result)) {
		if ($shade) {
			$bgcolor=' bgcolor="'.$color['list_shade1'].'"';
			$shade=false;
			}
		   else {
			$bgcolor=' bgcolor="'.$color['list_shade2'].'"';
			$shade=true;
			}
		echo '<TR'.$bgcolor.'><TD>';
		echo $exp['experiment_name'].'</TD><TD>(';
		if ($exp['experiment_type']=="laboratory") {
                	echo $lang['from'].' '.sessions__get_first_date($exp['experiment_id']).' ';
                	echo $lang['to'].' '.sessions__get_last_date($exp['experiment_id']);
                }
          	elseif ($exp['experiment_type']=="online-survey") {
                	echo $lang['from'].' '.survey__print_start_time($exp['experiment_id'],true).' ';
                	echo $lang['to'].' '.survey__print_stop_time($exp['experiment_id'],true);
                }
		echo ')</TD><TD>by ';
		echo experiment__list_experimenters($exp['experimenter'],true,true);
		echo '</TD><TD><A HREF="download_main.php?experiment_id='.$exp['experiment_id'].'">'.
			$lang['show_files'].'</A>';
		echo '</TD></TR>';
		}
	echo '</TABLE>';
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
