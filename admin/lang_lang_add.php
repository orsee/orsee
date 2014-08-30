<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="add language";
include ("header.php");

	$allow=check_allow('lang_lang_add','lang_main.php');

	echo '<center>
		<BR><BR>
	
			<H4>'.$lang['add_language'].'</H4>
		<BR>';

	// load languages
        $languages=get_languages();


        if (!isset($_REQUEST['nlang_sc'])) $_REQUEST['nlang_sc']="";
        if (!isset($_REQUEST['nlang_name'])) $_REQUEST['nlang_name']="";
        if (!isset($_REQUEST['nlang_base'])) $_REQUEST['nlang_base']="";


	if (isset($_REQUEST['add']) && $_REQUEST['add']) { 
		
		// check for errors
		$continue=true;

		if (!$_REQUEST['nlang_sc']) {
			message($lang['error_no_language_shortcut']);
			$continue=false;
			}

		if (in_array($_REQUEST['nlang_sc'],$languages)) {
                        message($lang['error_language_shortcut_exists']);
                        $continue=false;
                        }


		if (!$_REQUEST['nlang_name']) {
                        message($lang['error_no_language_name']);
                        $continue=false;
                        }

		// add language
		if ($continue) {

			$query="ALTER TABLE ".table('lang')." ADD COLUMN ".$_REQUEST['nlang_sc']." text";
			$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
			if ($done) message ($lang['language_created'].' '.$_REQUEST['nlang_sc']);

			$query="UPDATE ".table('lang')." SET ".$_REQUEST['nlang_sc']."=".$_REQUEST['nlang_base']." ";
                        $done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                        if ($done) message ($lang['language_items_copied_from_base_language'].' '.$_REQUEST['nlang_base']);

			$query="UPDATE ".table('lang')." SET ".$_REQUEST['nlang_sc']."='".$_REQUEST['nlang_sc']."' 
				WHERE content_type='lang' AND content_name='lang'";
			$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

			$query="UPDATE ".table('lang')." SET ".$_REQUEST['nlang_sc']."='".$_REQUEST['nlang_name']."' 
                                WHERE content_type='lang' AND content_name='lang_name'";
			$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
			log__admin("language_add","language:".$_REQUEST['nlang_sc']);
			redirect ("admin/lang_main.php");
			}

		}

	show_message();

	echo '<FORM action="lang_lang_add.php">

		<TABLE width=80%>
			<TR>
				<TD align=right>
					'.$lang['language_shortcut'].':&nbsp;&nbsp;
				</TD>
				<TD>
					<INPUT type=text name="nlang_sc" size=2 maxlength=2 value="'.$_REQUEST['nlang_sc'].'">
				</TD>
			</TR>';

	echo '		<TR>
				<TD align=right>
					'.$lang['language_name_in_lang'].':&nbsp;&nbsp;
				</TD>
				<TD>
					<INPUT type=text name="nlang_name" size=20 maxlength=50 value="'.$_REQUEST['nlang_name'].'">
				</TD>
			</TR>';

	echo '          <TR>
                                <TD align=right>
                                        '.$lang['language_based_on'].':&nbsp;&nbsp;
                                </TD>
                                <TD>';
					$lang_names=lang__get_language_names();
                                        if ($_REQUEST['nlang_base']) $blang=$_REQUEST['nlang_base'];
                                                else $blang=$settings['admin_standard_language'];
                                        echo '<SELECT name="nlang_base">';
                                        foreach ($languages as $language) {
                                                echo '<OPTION value="'.$language.'"';
                                                if ($language==$blang) echo ' SELECTED';
                                                echo '>'.$lang_names[$language].'</OPTION>
                                                        ';
                                                }
                                        echo '</SELECT>
                                </TD>
                        </TR>';


	echo '		<TR>
				<TD colspan=2 align=center>
					<INPUT type=submit name="add" value="'.$lang['add'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

	echo '<BR><BR>
                <A href="lang_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>
                </center>';


include ("footer.php");

?>
