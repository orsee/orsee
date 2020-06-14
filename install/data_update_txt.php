<?php
// part of orsee. see orsee.org

//////////////////////////////////////////////////////////////////
// UPDATING PARTICIPANT DATA FROM FROM TAB-SEPARATED TEXT DATA FILE    //


// PREPARATIONS:
// - Configure your participant data form. Make sure it contains the fields you want to import/update. 
// - Prepare a data file, where the columns contain the values for participant profile fields. If you update select_lang/radioline_lang items, the data needs to contain the ID of the respective value (e.g. the column for fields of studies should not contain values like "Economics", but the internal ORSEE Id number for "Economics", as listed in the respective table in Options/Items for profile fields of type "select_lang"/"radioline_lang"/Main field of studies.) For select/radioline fields (e.g. the original "gender"), the data needs to contain the option values (see Options/Participant profile fields/Edit ...).
// - The data file should *not* contain a header. You will need the respective column numbers below, column numbering starts with 0.
// - Save the file as "tab-separated txt file".
// - Put the participant data file into the install/ folder, next to this script.
// - Fill in the configuration section below.

// NOTES
// - This script will use the database configuration in config/settings.php to import the data.
// - Note that this script can only update participant data, but not participation data.
// - The script will not add new participants. If a participant key does not exist in the database, that row from the data file will be simply ignored.
// The script will attempt to convert any text to UTF8.

//////////////////////////////////////////////////////////////////
// CONFIG

$debug=true; // If set to true (rather than false), the script will run a dry test, not actually updating any data. Set to "false" to do the actual data update.

$txt_file_name="example_update_data.txt"; // Put the file name of your data file here.

$key_column_name="participant_id_crypt"; // Name of column to used as a key. Use participant_id_crypt (hashed participant id) or participant_id. This key must be provided in the first column (column 0) of data file.

// The mapping from your data columns (indexed 0 to ...) to the participant form fields
$pform_mapping=array();
$pform_mapping["participant_id_crypt"]=0; // column index number in your data file
$pform_mapping["begin_of_studies"]=1; 
$pform_mapping["phone_number"]=2;

// If you want to update the participant status ID or the subpool id of the affected participants (e.g. in order to mark those profiles which have been updated), you can include status_id / subpool_id as columns above and in your data file, or you simply set them below. Empty or non-existing settings below imply that participant status ID / subpool id will not be changed.
$participant_status_id=""; 
$subpool_id="";


$check_regexp=true; // check data field against regexp defined for ORSEE pform field. If some data is found in the update data file that does not comply with the requirement for the ORSEE pform field, then an error is issued.

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



// UPDATE STARTS HERE
$continue=true;

if ($continue) {
    // check form fields
    $formfields=participantform__load(); $pfields=array(); $allowed_values=array();
    foreach ($formfields as $f) {
        $pfields[]=$f['mysql_column_name'];
        if(preg_match("/(radioline|select_list)/",$f['type'])) {
            $allowed_values[$f['mysql_column_name']]=explode(",",$f['option_values']);
        } elseif (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
            $langvals=lang__load_lang_cat($f['mysql_column_name']);
            foreach ($langvals as $k=>$v) {
                $allowed_values[$f['mysql_column_name']][]=$k;
            }
        }
    }
    if (!($key_column_name=="participant_id" || $key_column_name=="participant_id_crypt")) {
        $continue=false;
        echo "Error: key column name must be either 'participant_id' or 'participant_id_crypt', but is '".$key_column_name."'. Please check configuration/data.\n";
    }
    foreach ($pform_mapping as $pfield=>$column) {
        if ($column>0 && !in_array($pfield,$pfields)) {
            $continue=false;
            echo "Error: field ".$pfield." defined in \$pform_mapping but not configured in ORSEE. Please check configuration/data.\n";
        }
        if ($column==0 && $pfield!=$key_column_name) {
            $continue=false;
            echo "Error: key column name (".$key_column_name.") is different to name of first column in data file (".$pfield."). Please check configuration/data.\n";
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
            if ($check_regexp) {
                foreach ($formfields as $f) {
                    if (isset($pform_mapping[$f['mysql_column_name']]) && $f['perl_regexp']!='') {
                        if(!preg_match($f['perl_regexp'],$p[$f['mysql_column_name']])) {
                            $continue=false;
                            echo "Error line ".$pcount.": field ".$f['mysql_column_name']." (column ".$pform_mapping[$f['mysql_column_name']].") has regexp that is not matched. Check data.\n";
                        }
                    }
                }
            }
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
        $participant['participant_id_crypt']=$new_id['participant_id_crypt'];
        $participant['last_profile_update']=time();

        if (isset($participant_status_id) && $participant_status_id) {
            $participant['status_id']=$participant_status_id;
        }
        if (isset($subpool_id) && $subpool_id) {
            $participant['subpool_id']=$subpool_id;
        }
        $participant=convert_array_to_UTF8($participant);
        //var_dump($participant);
        if (!$debug) {
            $done=orsee_db_save_array($participant,"participants",$participant[$key_column_name],$key_column_name);
        }
    }
    if (!$debug) {
        echo "Updated ".$pcount." participant profiles.\n";
    } else {
        echo "Debugging run. Nothing updated.\n";
    }
}

echo "\n";

?>
