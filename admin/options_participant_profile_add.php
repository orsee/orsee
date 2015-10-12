<?php
// part of orsee. see orsee.org
ob_start();

$title="create_new_mysql_table_column";
$menu__area="options_main";
$jquery=array();
include ("header.php");

if ($proceed) {
    $allow=check_allow('pform_config_field_add','options_participant_profile.php');
}

if ($proceed) {
    $type_specs=array(1=>array('spec'=>'varchar(250)','fullspec'=>"varchar(250) collate utf8_unicode_ci default ''"),
                    2=>array('spec'=>'mediumtext','fullspec'=>'mediumtext collate utf8_unicode_ci'),
                    3=>array('spec'=>'integer','fullspec'=>'bigint(30) default NULL'));
    $index_specs=array(1=>'#name#_index (#name#)',
                    2=>'#name#_index (#name#(250))',
                    3=>'#name#_index (#name#)');

    if (isset($_REQUEST['mysql_column_name']) && $_REQUEST['mysql_column_name']) {
        $continue=true;
        if ($continue) {
            if (!preg_match("/^[a-z][a-z_]+[a-z]$/",trim($_REQUEST['mysql_column_name']))) {
                $continue=false;
                message(lang('error_column_name_does_not_match_requirements').': <b>'.$_REQUEST['mysql_column_name'].'</b>');
            }
        }
        if ($continue) {
            $user_columns=participant__userdefined_columns();
            if (isset($user_columns[$_REQUEST['mysql_column_name']])) {
                $continue=false;
                message(lang('error_column_of_this_name_exists').': <b>'.$_REQUEST['mysql_column_name'].'</b>');
            }
        }
        if ($continue) {
            if (isset($_REQUEST['mysql_column_name']) && trim($_REQUEST['mysql_column_type'])=='3') {
                $ttypespec=$type_specs[3]['fullspec'];
                $tindexspec=$index_specs[3];
            } elseif (isset($_REQUEST['mysql_column_name']) && trim($_REQUEST['mysql_column_type'])=='2') {
                $ttypespec=$type_specs[2]['fullspec'];
                $tindexspec=$index_specs[2];
            } else {
                $ttypespec=$type_specs[1]['fullspec'];
                $tindexspec=$index_specs[1];
            }
            $name=trim($_REQUEST['mysql_column_name']);

            $create_query="ALTER TABLE ".table('participants')."
                            ADD COLUMN ".$name." ".$ttypespec.",
                            ADD INDEX ".str_replace("#name#",$name,$tindexspec);
            $done=or_query($create_query);
            if ($done) {
                message(lang('mysql_column_created'));
                redirect('admin/options_participant_profile.php');
            } else {
                message(lang('database_error'));
            }
        }
    }
}


if ($proceed) {

    if (isset($_REQUEST['mysql_column_name'])) $mysql_column_name=trim($_REQUEST['mysql_column_name']);
    else $mysql_column_name='';

    if (isset($_REQUEST['mysql_column_type'])) $mysql_column_type=trim($_REQUEST['mysql_column_type']);
    else $mysql_column_type=1;


    echo '<center>';

    show_message();

    javascript__tooltip_prepare();

    echo '<FORM id="columnform" action="'.thisdoc().'" method="POST">';
    echo '<TABLE class="or_formtable">';

    echo '<TR class="tooltip" title="Name of the new MySQL column. Name must start and end with a lowercase letter, and can only contain lower case letters and underscore (_).">
            <TD>MySQL column name (a-z_):</TD>
            <TD><INPUT type="text" name="mysql_column_name" size="30" maxlength="50" value="'.$mysql_column_name.'"></TD></TR>';

    echo '<TR class="tooltip" title="Type of the new MySQL column. &quot;varchar(250)&quot; is the most versatile type
            for numbers or shorter text. Must be chosen for &quot;select_lang&quot; and &quot;radioline_lang&quot; lists.
            If the field needs to store longer text, then &quot;mediumtext&quot; might be appropriate.
            &quot;integer&quot; can be chosen if the field will only hold integer numbers (but &quot;varchar(250)&quot;
            is recommended also in this case).">
            <TD>MySQL column type:</TD>
            <TD><SELECT name="mysql_column_type">';
    foreach ($type_specs as $k=>$arr) {
        echo '<OPTION value="'.$k.'"';
        if ($k==$mysql_column_type) echo ' SELECTED';
        echo '>'.$arr['spec'].'</option>';
    }
    echo '</SELECT></TD></TR>';

    echo '<TR><TD colspan="2" align="center">';
    echo '<P id="submit_message"></p>';
    echo '<INPUT class="button" type="submit" name="create" value="'.lang('create_column').'">';
    echo '</TD></TR>';
    echo '</TABLE>';

            echo '<script type="text/javascript">
                $("#columnform").submit(function () {
                    if($(this).data("is_submitted")){
                        return false;
                    } else {
                        $("input[type=submit]", this).attr("disabled","disabled");
                        $(this).data("is_submitted", true);
                        $("#submit_message").html("Creating ...");
                    }
                })
            </script>';


    echo '</FORM>';


    echo '<BR><BR><A href="options_participant_profile.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>