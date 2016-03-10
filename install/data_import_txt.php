<?php
// part of orsee. see orsee.org

//////////////////////////////////////////////////////////////////
// PARTICIPANT DATA IMPORT FROM TAB-SEPARATED TEXT DATA FILE    //


// PREPARATIONS:
// - Set your authentication method in Options/General Settings to either "URL token" or "Migration". Passwords are not imported. If set to "URL token", the system will just work with the encrypted participant IDs as tokens. If set to "Migration", participants will be asked to choose a password upon their first encounter with the system (which is initially authenticated with the token).
// - Configure your participant data form. Make sure that your data file will contain all the data marked as "compulsory" in the data form.
// - Create all needed external experiment types (options/Experiment types), note down the ID numbers of these types.
// - In particular, also add all items for profile fields of type "select_lang"/"radioline_lang".
// - Prepare a data file, where the columns contain the values for participant profile fields. For select_lang/radioline_lang items, the data needs to contain the ID of the respective value (e.g. the column for fields of studies should not contain values like "Economics", but the internal ORSEE Id number for "Economics", as listed in the repesctive table in Options/Items for profile fields of type "select_lang"/"radioline_lang"/Main field of studies.) For select/radioline fields (e.g. the original "gender"), the data needs to contain the option values (see Options/Participant profile fields/Edit ...).
// - The data file should *not* contain a header. You will need the respective column numbers below, column numbering starts with 0.
// - Save the file as "tab-separated txt file".
// - Put the participant data file into the install/ folder, next to this script.
// - Fill in the configuration section below.

// NOTES
// - This script will use the database configuration in config/settings.php to import the data.
// - Note that this script can only import participant data, but not participation data. Importing past experiments and their participation details is much more complex. If you are upgrading from an older ORSEE version, please use the data_impot.php script.
// - The script will not affect any existing participant data. If you want to start fresh, you will have to empty the exiting or_participants table (in SQL "DELETE FROM or_participants;").
// If there is no "language" field defined in the data file and the configuration below ("en", "de", etc.), the import script will assume the "public standard language" from the ORSEE configuration for all imported participant profiles.
// The script will attempt to convert any text to UTF8.

//////////////////////////////////////////////////////////////////
// CONFIG

$debug=true; // If set to true (rather than false), the script will run a dry test, not actually importing any data. Set to "false" to do the actual import.

$txt_file_name="example_import_data.txt"; // Put the file name of your data file here.

$participant_status_id="1"; // The default status ID in ORSEE is 1 for "active".
$subpool_id="1"; // The ID of the sub-subjectpool to which these participants are to be assigned.
$experiment_types="1,2"; // Enter a comma-separated list of the IDs of the experiment types to which the participants should be subscribed.

// The mapping from your data columns (indexed 0 to ...) to the participant form fields
$pform_mapping=array();
$pform_mapping["email"]=0; // column index number in your data file
$pform_mapping["lname"]=1;
$pform_mapping["fname"]=2;
$pform_mapping["gender"]=3;
$pform_mapping["phone_number"]=4;
$pform_mapping["begin_of_studies"]=5;
$pform_mapping["field_of_studies"]=6;
$pform_mapping["profession"]=7;

$check_compulsory=true; // check if data field is empty when ORSEE pform field is compulsory
$check_regexp=true; // check data field against regexp defined for ORSEE pform field

// END CONFIG
//////////////////////////////////////////////////////////////////


// THE IMPORT SCRIPT
include("../admin/cronheader.php");

function detectUTF8($string) {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

function convert_array_to_UTF8($arr) {
    foreach($arr as $k=>$v) {
        if (!detectUTF8(stripslashes($v))) {
            $arr[$k]=utf8_encode(stripslashes($v));
        }
    }
    return $arr;
}



// IMPORT STARTS HERE
$continue=true;

if ($continue) {
    // check participant status
    $statuses=participant_status__get_statuses();
    if (!isset($statuses[$participant_status_id])) {
        $continue=false;
        echo "Error: participant_status_id not valid. Please check ORSEE configuration.\n";
    }
}

if ($continue) {
    // check subpool id
    $subpools=subpools__get_subpools();
    if (!isset($subpools[$subpool_id])) {
        $continue=false;
        echo "Error: subpool_id not valid. Please check ORSEE configuration.\n";
    }
}

if ($continue) {
    // check experiment types
    $exptypes=load_external_experiment_types();
    $texptypes=explode(",",$experiment_types); $cexptypes=array();
    foreach ($texptypes as $id) {
        $id=trim($id);
        if (!isset($exptypes[$id])) {
            $continue=false;
            echo "Error: experiment type ID ".$id." unknown. Please check ORSEE configuration.\n";
        } else {
            $cexptypes[]=$id;
        }
    }
}

if ($continue) {
    // check form fields
    $formfields=participantform__load(); $pfields=array(); $allowed_values=array();
    foreach ($formfields as $f) {
        $pfields[]=$f['mysql_column_name'];
        if ($check_compulsory) {
            if($f['subpools']=='all' | in_array($subpool_id,explode(",",$f['subpools']))) {
                if ($f['compulsory']=='y' && !isset($pform_mapping[$f['mysql_column_name']])) {
                    $continue=false;
                    echo "Error: form field ".$f['mysql_column_name']." is compulsory but not defined in \$pform_mapping. Please check configuration/data.\n";
                }
            }
        }
        if(preg_match("/(radioline|select_list)/",$f['type'])) {
            $allowed_values[$f['mysql_column_name']]=explode(",",$f['option_values']);
        } elseif (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
            $langvals=lang__load_lang_cat($f['mysql_column_name']);
            foreach ($langvals as $k=>$v) {
                $allowed_values[$f['mysql_column_name']][]=$k;
            }
        }
    }
    foreach ($pform_mapping as $pfield=>$column) {
        if (!in_array($pfield,$pfields)) {
            $continue=false;
            echo "Error: field ".$pfield." defined in \$pform_mapping but not configured in ORSEE. Please check configuration/data.\n";
        }
    }
}

if ($continue) {
    $pcount=0; $taberror=false; $colerror=false; $col_count=-1;
    $data_file=file($txt_file_name);
    $participants=array(); $unique_values=array();
    
    foreach ($data_file as $line) {
        $pcount++;
        $l=explode("\t",trim($line));
        if (!$taberror && count($l)<=1) {
            $continue=false; $taberror=true;
            echo "Error: Only one column found in data file. No tabs used?\n";
        }
        if (!$colerror && $col_count!=-1 && count($l)!=$col_count) {
            $continue=false; $taberror=true;
            echo "Error line ".$pcount.": Rows have different numbers of columns. Check data file.\n";
        } else {
            $col_count=count($l);
        }
        if ($continue) {
            $p=array();
            foreach ($pform_mapping as $pfield=>$column) {
                if (!isset($l[$column])) {
                    $continue=false;
                    echo "Error line ".$pcount.": Column ".$column." for field ".$pfield." does not exist. Check data file/config.\n";
                }
                if ($continue) {
                    $p[$pfield]=$l[$column];
                }
            }
        }
        if ($continue) {
            if ($check_compulsory || $check_regexp) {
                foreach ($formfields as $f) {
                    if($f['subpools']=='all' | in_array($subpool_id,explode(",",$f['subpools']))) {
                        if ($check_compulsory && $f['compulsory']=='y') {
                            if(!isset($p[$f['mysql_column_name']]) || !$p[$f['mysql_column_name']]) {
                                $continue=false;
                                echo "Error line ".$pcount.": field ".$f['mysql_column_name']." (column ".$pform_mapping[$f['mysql_column_name']].") is compulsory but empty. Check data.\n";
                            }
                        }
                        if ($check_regexp && $f['perl_regexp']!='') {
                            if(!preg_match($f['perl_regexp'],$p[$f['mysql_column_name']])) {
                                $continue=false;
                                echo "Error line ".$pcount.": field ".$f['mysql_column_name']." (column ".$pform_mapping[$f['mysql_column_name']].") has regexp that is not matched. Check data.\n";
                            }
                        }
                    }
                    if($f['require_unique_on_create_page']=='y' && isset($p[$f['mysql_column_name']]) && $p[$f['mysql_column_name']]) {
                        if (isset($unique_values[$f['mysql_column_name']][$p[$f['mysql_column_name']]])) {
                            $continue=false;
                            echo "Error line ".$pcount.": field ".$f['mysql_column_name']." (column ".$pform_mapping[$f['mysql_column_name']].") needs to be unique but value was used before in row ".$unique_values[$f['mysql_column_name']][$p[$f['mysql_column_name']]].". Check data.\n";
                        } else {
                            $unique_values[$f['mysql_column_name']][$p[$f['mysql_column_name']]]=$pcount;
                        }
                    }
                }
            }
            // todo: check whether ids/option values for select_lang/radion_lang/select/radioline exist ...
            foreach ($p as $k=>$v) {
                if (isset($allowed_values[$k])) {
                    if(!in_array($v,$allowed_values[$k])) {
                        $continue=false;
                        echo "Error line ".$pcount.": The value ".$v." in field ".$k." (column ".$pform_mapping[$k].") is not defined as an ID for this field in ORSEE. Check data/ORSEE config.\n";
                    }
                }
            }
        }
        if ($continue) {
            $participants[]=$p;
        }
    }
}

if ($continue) {
    echo "All checks succeeded.\n";
    foreach ($participants as $participant) {
        $new_id=participant__create_participant_id($participant);
        $participant['participant_id']=$new_id['participant_id'];
        $participant['participant_id_crypt']=$new_id['participant_id_crypt'];
        $participant['status_id']=$participant_status_id;
        $participant['creation_time']=time();
        $participant['deletion_time']=0;
        $participant['last_profile_update']=$participant['creation_time'];
        $participant['last_activity']=$participant['creation_time'];
        $participant['last_enrolment']=0;
        $participant['subpool_id']=$subpool_id;
        $participant['subscriptions']=id_array_to_db_string($cexptypes);
        if (!isset($participant['language']) || !$participant['language']) {
            $participant['language']=$settings['public_standard_language'];
        }
        $participant=convert_array_to_UTF8($participant);
        //var_dump($participant);
        if (!$debug) {
            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
        }
    }
    if (!$debug) {
        echo "Imported ".$pcount." participant profiles.\n";
    } else {
        echo "Debugging run. Nothing imported.\n";
    }
}

echo "\n";

?>
