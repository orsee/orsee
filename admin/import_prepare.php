<?php
// part of orsee. see orsee.org
ob_start();

    $old_versions=array('orsee2'=>'versions <3.0');

$title="prepare_data_import";
$menu__area="options_main";
include ("header.php");
if ($proceed) {
    check_allow('import_data','options_main.php');
}
if ($proceed) {
    $continue=true;

    echo '<center>';

    if ($continue) {
        $databases=array();
        $query="SELECT `SCHEMA_NAME`
                FROM `INFORMATION_SCHEMA`.`SCHEMATA`
                WHERE `SCHEMA_NAME` NOT IN ('information_schema','mysql')";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['SCHEMA_NAME']!=$site__database_database) $databases[]=$line['SCHEMA_NAME'];
        }

        // first step:
        if (!isset($_REQUEST['old_version']) || !isset($old_versions[$_REQUEST['old_version']])
            || !isset($_REQUEST['old_database']) || !in_array($_REQUEST['old_database'],$databases)) {
            $continue=false;

            echo '<FORM action="'.thisdoc().'" method="POST">';
            echo '<TABLE class="or_formtable">';

            echo '      <TR><TD>From which ORSEE version do you want to import data?</TD>
                        <TD><SELECT name="old_version">';
            foreach ($old_versions as $ov=>$text) echo '<OPTION value="'.$ov.'">'.$text.'</OPTION>';
            echo '</SELECT></TD></TR>';

            echo '      <TR><TD>From which database should the data be imported?</TD>
                        <TD><SELECT name="old_database">';
            foreach ($databases as $db) echo '<OPTION value="'.$db.'">'.$db.'</OPTION>';
            echo '</SELECT></TD></TR>';

            echo '<TR><TD colspan="2" align="center">
                    <INPUT type="submit" class="button" name="submit1" value="Next">
                </TD></TR>';
            echo '</TABLE>';
            echo '</FORM>';
        }
    }

    if ($continue) {

        $old_version=$_REQUEST['old_version'];
        $old_database=$_REQUEST['old_database'];


/////////////////////////
        if ($old_version=="orsee2") {

            if (isset($_REQUEST['submit1'])) {



                echo '<FORM action="'.thisdoc().'" method="POST">';
                echo '<INPUT type="hidden" name="old_version" value="'.$old_version.'">';
                echo '<INPUT type="hidden" name="old_database" value="'.$old_database.'">';
                echo '<TABLE class="or_formtable" style="max-width: 90%;">';


                echo '      <TR><TD colspan=2>Do you want to import
                            <UL><LI><B>all data</B> (admins, admin types, experiment types, faqs, language items, subpools, files, plus all what\'s listed below)</LI>
                                <LI>or only <B>participation data</B> (participants, experiments, sessions, participations, lab bookings, admin log, cron log, participant log)?</LI>
                            </UL>
                                 </TD></TR>
                            <TD colspan=2 align="center"><SELECT name="import_type">
                            <OPTION value="all">all data</OPTION>
                            <OPTION value="participation">participation data</OPTION>
                            </SELECT></TD></TR>';
                echo '      <TR><TD colspan=2><hr></TD></TR>';

                $statuses=participant_status__get_statuses();
                echo '<TR><TD colspan=2>There are '.count($statuses).' participant statuses defined in this system.<BR>
                            Please select how previous participant properties should be assigned to these states.
                         </TD></TR>
                            <TD colspan=2 align="center"><TABLE>';
                $prev_statuses=array('n_n'=>array('not deleted, not excluded',1),
                                    'y_n'=>array('deleted, not excluded',2),
                                    'y_y'=>array('deleted and excluded',3));
                foreach ($prev_statuses as $ps=>$arr) {
                    echo '<TR><TD>'.$arr[0].'</TD>
                            <TD><SELECT name="status_'.$ps.'">';
                        foreach ($statuses as $status) {
                            echo '<OPTION value="'.$status['status_id'].'"';
                            if ($status['status_id']==$arr[1]) echo ' SELECTED';
                            echo '>'.$status['name'].'</OPTION>';
                        }
                    echo '</SELECT></TD></TR>';
                }
                echo '</TABLE></TD></TR>';
                echo '      <TR><TD colspan=2><hr></TD></TR>';

                $pstatuses=expregister__get_participation_statuses();
                echo '<TR><TD colspan=2>There are '.count($pstatuses).' experiment participation statuses defined in this system.<BR>
                            Please select how previous participation states should be assigned to these states.
                         </TD></TR>
                            <TD colspan=2 align="center"><TABLE>';
                $prev_pstatuses=array('n_n'=>array('not shownup, not participated',3),
                                    'y_n'=>array('shownup, not participated',2),
                                    'y_y'=>array('shownup and participated',1));
                foreach ($prev_pstatuses as $ps=>$arr) {
                    echo '<TR><TD>'.$arr[0].'</TD>
                            <TD><SELECT name="pstatus_'.$ps.'">';
                        foreach ($pstatuses as $status) {
                            echo '<OPTION value="'.$status['pstatus_id'].'"';
                            if ($status['pstatus_id']==$arr[1]) echo ' SELECTED';
                            echo '>'.$status['internal_name'].'</OPTION>';
                        }
                    echo '</SELECT></TD></TR>';
                }
                echo '</TABLE></TD></TR>';
                echo '      <TR><TD colspan=2><hr></TD></TR>';

                $old_fields=array();
                $pars=array(':dbname'=>$old_database);
                $query="SELECT `COLUMN_NAME`
                FROM `INFORMATION_SCHEMA`.`COLUMNS`
                WHERE `TABLE_SCHEMA`= :dbname
                AND `TABLE_NAME`='or_participants'";
                $result=or_query($query,$pars);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_fields[]=$line['COLUMN_NAME'];
                }
                $unset_in_old=array('participant_id','participant_id_crypt','creation_time','subpool_id',
                    'number_reg','number_noshowup','language','remarks','rules_signed','deleted','excluded','subscriptions');
                $old_fields=or_array_delete_values($old_fields,$unset_in_old);

                $new_fields=participant__userdefined_columns();
                $query="SELECT * FROM ".table('profile_fields');
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    if (isset($new_fields[$line['mysql_column_name']])) {
                        $new_fields[$line['mysql_column_name']]['fieldtype']=$line['type'];
                    }
                }

                echo '<TR><TD colspan="2">
                        The following profile fields are defined in the current (new) system.
                        Please select from which column in the old system the data should be imported.<BR>
                        <TABLE width="100%">
                        <TR style="font-weight: bold;"><TD>Profile field</TD><TD>Column type</TD>
                                <TD>Form field type</TD><TD>Import from '.$old_database.'</TD></TR>';
                foreach ($new_fields as $field=>$f) {
                    echo '<TR><TD>'.$field.'</TD><TD>'.$f['Type'].'</TD><TD>';
                    if (isset($f['fieldtype'])) echo $f['fieldtype']; else echo '???';
                    echo '</TD><TD>';
                    echo '<SELECT name="map_'.$field.'">';
                    echo '<OPTION value="">Don\'t import</OPTION>';
                    foreach ($old_fields as $of) {
                        echo '<OPTION value="'.$of.'"';
                        if ($of==$field) echo ' SELECTED';
                        echo '>'.$of.'</OPTION>';
                    }
                    echo '</SELECT>';
                    echo '</TD></TR>';
                }
                echo '</TABLE>';
                echo '</TD></TR>';

                echo '      <TR><TD colspan=2><hr></TD></TR>';

                echo '      <TR><TD colspan=2>Do you want to create new (more secure) tokens for participant URLs?
                            <UL>
                            <LI>The disadvantage of this is that the old URL\'s which participants used to access their
                            ORSEE enrolment page won\'t work anymore.<BR>The new URLs (with the newly created tokens)
                            will be included in any email sent out by the system. </LI>
                            <LI>If you plan to migrate to a username/password authentication for participants in the new system,<BR>
                            you could also leave the tokens as they are, because once fully migrated they will be irrelevant for access.</LI>
                            </UL>
                            </TD></TR>
                            <TD colspan=2 align="center"><SELECT name="replace_tokens">
                            <OPTION value="n">Keep old tokens</OPTION>
                            <OPTION value="y">Replace tokens</OPTION>
                            </SELECT></TD></TR>';

                echo '<TR><TD colspan="2" align="center">
                        <INPUT type="submit" class="button" name="submit2" value="Next">
                    </TD></TR>';
                echo '</TABLE>';
                echo '</FORM>';


            }

            if (isset($_REQUEST['submit2'])) {

                $code='';
                $code.='$old_db_name="'.$old_database.'";'."\n";
                $code.='$new_db_name="'.$site__database_database.'";'."\n";
                $code.=''."\n";
                $code.='// mapping of participant statuses'."\n";
                $code.='// participant_status_mapping[deleted y/n][excluded y/n]=status_id'."\n";
                $code.='$participant_status_mapping=array();'."\n";
                $code.='$participant_status_mapping["n"]["n"]="'.$_REQUEST['status_n_n'].'";'."\n";
                $code.='$participant_status_mapping["n"]["y"]="'.$_REQUEST['status_y_y'].'";'."\n";
                $code.='$participant_status_mapping["y"]["n"]="'.$_REQUEST['status_y_n'].'";'."\n";
                $code.='$participant_status_mapping["y"]["y"]="'.$_REQUEST['status_y_y'].'";'."\n";
                $code.=''."\n";
                $code.='// mapping of participation statuses'."\n";
                $code.='// participation_mapping[shownup y/n][participated y/n]=pstatus_id'."\n";
                $code.='$participation_mapping=array();'."\n";
                $code.='$participation_mapping["n"]["n"]="'.$_REQUEST['pstatus_n_n'].'";'."\n";
                $code.='$participation_mapping["n"]["y"]="'.$_REQUEST['pstatus_n_n'].'";'."\n";
                $code.='$participation_mapping["y"]["n"]="'.$_REQUEST['pstatus_y_n'].'";'."\n";
                $code.='$participation_mapping["y"]["y"]="'.$_REQUEST['pstatus_y_y'].'";'."\n";
                $code.=''."\n";
                $code.='// mapping from old participant profile form to new form'."\n";
                $code.='// empty value for new column name implies no import'."\n";
                $code.='// pform_mapping[old column name]=new column name'."\n";
                $code.='$pform_mapping=array();'."\n";
                $new_fields=participant__userdefined_columns();
                foreach ($new_fields as $field=>$f) {
                    if (isset($_REQUEST['map_'.$field]) && $_REQUEST['map_'.$field]) {
                        $code.='$pform_mapping["'.$_REQUEST['map_'.$field].'"]="'.$field.'";'."\n";
                    }
                }
                $code.=''."\n";
                $code.='// other settings'."\n";
                $code.='$import_type="'.$_REQUEST['import_type'].'";'."\n";
                $code.='$replace_tokens="'.$_REQUEST['replace_tokens'].'";'."\n";
                $code.=''."\n";
                $code.=''."\n";

                echo '<TABLE>';
                echo '<TR><TD>';

                echo 'Below you find the code to copy over to install/data_import.php.
                        <BR>';

                echo '<TEXTAREA rows=40 cols=80>'.$code.'</TEXTAREA>';
                echo '</TD></TR>';
                echo '</TABLE>';

            }
        }
/////////////////////////



    }
    echo '</center>';
}
include ("footer.php");
?>