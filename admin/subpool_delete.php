<?php
ob_start();

$menu__area="options";
$title="delete subject pool";
include ("header.php");

	if (isset($_REQUEST['subpool_id'])) $subpool_id=$_REQUEST['subpool_id']; else $subpool_id="";

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/subpool_edit.php?subpool_id='.$subpool_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('subjectpool_delete','subpool_edit.php?subpool_id='.$subpool_id);

	$query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name='".$subpool_id."'";
        $selfdesc=orsee_query($query);

	// load subject pool
	$subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");

        // load languages
        $languages=get_languages();

	foreach ($languages as $language) $subpool[$language]=$selfdesc[$language];

        echo '<center><BR>
                        <h4>'.$lang['delete_subpool'].' "'.$subpool['subpool_name'].'"</h4>';


        if ($reallydelete) {

		if ($_REQUEST['merge_with']) $merge_with=$_REQUEST['merge_with']; else $merge_with=1;

		$query="DELETE FROM ".table('subpools')." 
                 	WHERE subpool_id='".$subpool_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('lang')."
                        WHERE content_name='".$subpool_id."'
			AND content_type='subjectpool'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="UPDATE ".table('participants')." 
                 	SET subpool_id='".$merge_with."'
                 	WHERE subpool_id='".$subpool_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());
	
                $query="UPDATE ".table('participants_temp')."
                        SET subpool_id='".$merge_with."'
                        WHERE subpool_id='".$subpool_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="UPDATE ".table('participants_os')."
                        SET subpool_id='".$merge_with."'
                        WHERE subpool_id='".$subpool_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

		log__admin("subjectpool_delete","subjectpool:".$subpool['subpool_name']);
                message ($lang['subpool_deleted_part_moved_to'].' "'.subpools__get_subpool_name($merge_with).'".');
                redirect ("admin/subpool_main.php");

                }

        // form

        echo '  <CENTER>
                <FORM action="subpool_delete.php">
                <INPUT type=hidden name="subpool_id" value="'.$subpool_id.'">

                <TABLE>
                        <TR>
                                <TD colspan=2>
                                        '.$lang['really_delete_subpool?'].'
                                        <BR><BR>';
					dump_array($subpool);
			echo '
                                </TD>
                        </TR>
                        <TR>
                                <TD align=left>
                                        <INPUT type=submit name=reallydelete value="'.$lang['yes_delete'].'">
					<BR>
					'.$lang['merge_subject_pool_with'].' ';
					subpools__select_field("merge_with","subpool_id","subpool_name","1",$subpool_id);
		echo '		</TD>
                                </TD>
                                <TD align=right>
                                        <INPUT type=submit name=betternot value="'.$lang['no_sorry'].'">
                                </TD>
                        </TR>
                </TABLE>

                </FORM>
                </center>';

include ("footer.php");

?>
