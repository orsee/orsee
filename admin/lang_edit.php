<?php
ob_start();

$menu__area="options";
$title="edit language";
include ("header.php");

	$allow=check_allow('lang_symbol_edit','lang_main.php');

	echo '<center>
		<BR><BR>

			<H4>'.$lang['edit_language'].'</H4>
		<BR>';

	if (!$_REQUEST['el']) { 
		
		// load languages
		$languages=get_languages();

		// show languages
		foreach ($languages as $language) { 
			echo '	<A HREF="lang_edit.php?el='.$language.'">'.$language.'</A>
				<BR><BR>';
			}

		echo '	<BR><BR>
			<FORM action="lang_symbol_edit.php">
			<INPUT type=submit name=go value="'.$lang['add_symbol'].'">
			</FORM>';

		}
	   else {

		$query_exclusion=" AND content_name NOT IN ('lang','lang_name')";

		$edlang=$_REQUEST['el'];

		if ($_REQUEST['search']) {
                	$letter="";
                	$search=$_REQUEST['search'];

                	$lquery="select * from ".table('lang')."
                        	 where content_type='lang'
                        	 and (content_name LIKE '%".mysql_escape_string($search)."%'
                        	 or ".$lang['lang']." LIKE '%".mysql_escape_string($search)."%'
                        	 or ".$edlang." LIKE '%".mysql_escape_string($search)."%')
                        	 order by content_name";
                	}
        	   else {
                	$search="";
                	if ($_REQUEST['letter']) $letter=$_REQUEST['letter']; else $letter='a';
                	$lquery="select * from ".table('lang')."
                        	 where content_type='lang' and left(content_name,1)='".$letter."'
				 order by content_name";
                	} 


		if ($_REQUEST['alter_lang']) {

			$newwords=$_REQUEST['symbols'];
			foreach ($newwords as $symbol => $content) {
				$query="UPDATE ".table('lang')." 
					SET ".$edlang."='".mysql_escape_string($content)."' 
					WHERE content_name='".$symbol."'
					AND content_type='lang'";
				$done=mysql_query($query) or die("Database error: " . mysql_error());
				}
			message($lang['changes_saved']);
			log__admin("language_edit_symbols","language:".$edlang);
			redirect ('admin/lang_edit.php?el='.$edlang.'&letter='.$letter.'&search='.$search);
			}

	echo '<FORM action="lang_edit.php">
		<INPUT type=hidden name="el" value="'.$edlang.'">
		<INPUT type=text name="search" size=20 maxlength=200 value="'.$search.'">
		<INPUT type=submit name=dosearch value="'.$lang['search'].'">
		</FORM><BR>';


        $query="select left(content_name,1) as letter, count(lang_id) as number, content_name 
		from ".table('lang')." 
		where content_type='lang' GROUP BY letter";
        $result=mysql_query($query) or die("Database error: " . mysql_error());
	while ($line=mysql_fetch_assoc($result)) {
		if ($line['letter']!=$letter) 
			echo '<A HREF="lang_edit.php?el='.$edlang.'&letter='.$line['letter'].'">'.$line['letter'].'</A>&nbsp; ';
		   else echo $letter.'&nbsp; ';
		}

	$result=mysql_query($lquery) or die("Database error: " . mysql_error());
	$number=mysql_num_rows($result);

	echo '<BR><BR>'.$lang['symbols'].': '.$number.'<BR><BR>

		<FORM action="lang_edit.php" method=post>
		<INPUT type=hidden name="el" value="'.$edlang.'">
		<INPUT type=hidden name="letter" value="'.$letter.'">
		<INPUT type=hidden name="search" value="'.$search.'">
		<TABLE with=100% border=1>
			<TR>
				<TD colspan=3 align=center>
					<INPUT type=submit name="alter_lang" value="'.$lang['change'].'">
				</TD>
			</TR>
			<TR>
				<TD>
					SYMBOL
				</TD>
				<TD>
					'.$lang['lang'].'
				</TD>
				<TD>
					'.$edlang.'
				</TD>
				<TD>
				</TD>
			</TR>';

	while ($line=mysql_fetch_assoc($result)) {

		echo '	<TR>
				<TD>
					'.$line['content_name'].'
				</TD>
				<TD>
					'.$lang[$line['content_name']].'
				</TD>
				<TD>
					<textarea rows=2 cols=30 wrap=virtual name="symbols['.$line['content_name'].']">'.
						trim(stripslashes($line[$edlang])).'</textarea>
				</TD>
				<TD>
					<A HREF="lang_symbol_edit.php?lang_id='.$line['lang_id'].'">'.$lang['edit'].'</A>
				</TD>
			</TR>
			';
		}

	echo '		<TR>
				<TD colspan=3 align=center>
					<INPUT type=submit name=alter_lang value="'.$lang['change'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

                echo '  <BR><BR>
                        <FORM action="lang_symbol_edit.php">
                        <INPUT type=submit name=go value="'.$lang['add_symbol'].'">
                        </FORM>';

		}

	echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';

include ("footer.php");

?>
