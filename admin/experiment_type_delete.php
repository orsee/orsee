<?php
ob_start();

$menu__area="options";
$title="delete experiment type";
include ("header.php");

	if (isset($_REQUEST['exptype_id'])) $exptype_id=$_REQUEST['exptype_id']; else $exptype_id="";

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/experiment_type_edit.php?exptype_id='.$exptype_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('experimenttype_delete','experiment_type_edit.php?exptype_id='.$exptype_id);

	$query="SELECT * from ".table('lang')." WHERE content_type='experiment_type' AND content_name='".$exptype_id."'";
        $selfdesc=orsee_query($query);

	// load subject pool
	$exptype=orsee_db_load_array("experiment_types",$exptype_id,"exptype_id");

        // load languages
        $languages=get_languages();

	foreach ($languages as $language) $exptype[$language]=$selfdesc[$language];

        echo '<center><BR>
                        <h4>'.$lang['delete_experiment_type'].' "'.$exptype['exptype_name'].'"</h4>';


        if ($reallydelete) {

		if ($_REQUEST['merge_with']) $merge_with=$_REQUEST['merge_with']; else $merge_with='';

		$query="DELETE FROM ".table('experiment_types')." 
                 	WHERE exptype_id='".$exptype_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('lang')."
                        WHERE content_name='".$exptype_id."'
			AND content_type='experiment_type'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="UPDATE ".table('participants')." 
                 	SET subscriptions=concat(subscriptions,',','".$merge_with."')
                 	WHERE subscriptions LIKE '%".$exptype['exptype_name']."%'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

		$query="UPDATE ".table('participants_temp')."
                        SET subscriptions=concat(subscriptions,',','".$merge_with."')
                        WHERE subscriptions LIKE '%".$exptype['exptype_name']."%'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

		$query="UPDATE ".table('participants_os')."
                        SET subscriptions=concat(subscriptions,',','".$merge_with."')
                        WHERE subscriptions LIKE '%".$exptype['exptype_name']."%'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

		$query="UPDATE ".table('experiments')."
                        SET experiment_ext_type='".$merge_with."'
                        WHERE experiment_ext_type='".$exptype['exptype_name']."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());
	
		log__admin("experimenttype_delete","experimenttype:".$exptype['exptype_name']);
                message ($lang['experimenttype_deleted_partexp_moved_to'].' "'.$merge_with.'".');
                redirect ("admin/experiment_type_main.php");

                }

        // form

        echo '  <CENTER>
                <FORM action="experiment_type_delete.php">
                <INPUT type=hidden name="exptype_id" value="'.$exptype_id.'">

                <TABLE width="80%">
                        <TR>
                                <TD colspan=2>
                                        '.$lang['do_you_really_want_to_delete'].'
                                        <BR><BR>';
					dump_array($exptype);
			echo '
                                </TD>
                        </TR>
                        <TR>
                                <TD align=left>
                                        <INPUT type=submit name=reallydelete value="'.$lang['yes_delete'].'">
					<BR>
					'.$lang['replace_experimenttype_with'].' ';
					experiment__exptype_select_field("merge_with","exptype_name","exptype_name",
							"",$exptype['exptype_name']);
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
