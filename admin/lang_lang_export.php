<?php
ob_start();



if ($_REQUEST['export']) {

	include ("nonoutputheader.php");

	if ($_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else redirect ("admin/lang_main.php");

	$allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);

	$query="SELECT * FROM ".table('lang')." 
		WHERE content_type IN ('lang','mail','default_text','help')
		ORDER by lang_id";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

	$items="";
	while ($line=mysql_fetch_assoc($result)) {
		$items.=stripslashes($line['content_type']).'--:orsee_next:--'.
			stripslashes($line['content_name']).'--:orsee_next:--'.
			stripslashes($line[$lang_id]).'--:orsee_line:--';
		}
	$file=chunk_split(base64_encode($items),60);

	echo $file;

	}

   else {

	$menu__area="options";
	$title="export language";
	include ("header.php");

	if ($_REQUEST['lang_id']) $lang_id=$_REQUEST['lang_id']; else redirect ("admin/lang_main.php");
	
	$allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);

	echo '<center>
		<BR><BR>
	
			<H4>'.$lang['export_language'].'</H4>
		<BR>';

	echo '

		<TABLE width=50%>
			<TR>
				<TD align=center>
					'.$lang['language_export_explanation'].'
				</TD>
			</TR>';

	echo '		<TR>
				<TD align=center>
					<A HREF="lang_lang_export.php?lang_id='.$lang_id.'&export=true">
						orsee_'.$lang_id.'.orl</A>
				</TD>
			</TR>';

	echo '		
		</TABLE>
		';

	echo '<BR><BR>
                <A href="lang_lang_edit.php?elang='.$lang_id.'">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';


	include ("footer.php");


	}
?>
