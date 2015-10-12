<?php
// upgrade orsee3 database to UTF-8
// run from commandline in <yourorseepath>/install/
// when your characters are not correctly displayed when upgrading
// from ORSEE <= 3.0.2 to ORSEE >=3.0.3
// Make sure to make a backup copy of your database before

// SEE UPGRADE.howto FOR DETAILS


// get orsee functions
include("../admin/cronheader.php");

// get participant table fields to be converted
$pfields=participant__userdefined_columns();
$tfields=array();
foreach ($pfields as $k=>$f) {
    $tfields[]=$f['Field'];
}
$tfields[]='remarks';

// load languages
$langs=get_languages();

// define all other fields to be converted
$table_fields=array(
    'admin'=>array('id'=>'admin_id','fields'=>array('fname','lname','email','adminname','admin_type')),
    'admin_log'=>array('id'=>'log_id','fields'=>array('target')),
    'admin_types'=>array('id'=>'type_id','fields'=>array('type_name')),
    'budgets'=>array('id'=>'budget_id', 'fields'=>array('budget_name','budget_limit')),
    'bulk_mail_texts'=>array('id'=>'bulktext_id','fields'=>array('bulk_subject','bulk_text')),
    'cron_log'=>array('id'=>'log_id','fields'=>array('target')),
    'emails'=>array('id'=>'message_id','fields'=>array('from_address','from_name','reply_to_address','to_address','cc_address','subject','body')),
    'events'=>array('id'=>'event_id','fields'=>array('event_category','reason','reason_public','number_of_participants')),
    'experiment_types'=>array('id'=>'exptype_id','fields'=>array('exptype_name','exptype_description')),
    'experiments'=>array('id'=>'experiment_id','fields'=>array('experiment_name','experiment_public_name','experiment_description','public_experiment_note','ethics_by','ethics_number','payment_types','payment_budgets','experiment_link_to_paper')),
    'lang'=>array('id'=>'lang_id','fields'=>$langs),
    'objects'=>array('id'=>'item_id','fields'=>array('item_type','item_name','item_details')),
    'options'=>array('id'=>'option_id','fields'=>array('option_value')),
    'participants'=>array('id'=>'participant_id','fields'=>$tfields),
    'participants_log'=>array('id'=>'log_id','fields'=>array('target')),
    'participate_at'=>array('id'=>'participate_id','fields'=>array('payment_amt')),
    'profile_fields'=>array('id'=>'mysql_column_name','fields'=>array('properties')),
    'sessions'=>array('id'=>'session_id','fields'=>array('session_remarks','public_session_note')),
    'subpools'=>array('id'=>'subpool_id','fields'=>array('subpool_name','subpool_description')),
    'uploads'=>array('id'=>'upload_id','fields'=>array('upload_name'))
);

// function to connect with different charsets
function pdoconnect($charset='UTF8') {
    global $site__database_host, $site__database_admin_username,
        $site__database_admin_password, $site__database_database, $site__database_port;

    if (preg_match("/^([^:]+):([0-9]+)$/",trim($site__database_host),$matches)) {
        $host='host='.$matches[1].';'; $port='port='.$matches[2].';';
    } else {
        $host='host='.$site__database_host.';';
        if (isset($site__database_port) && $site__database_port) {
            $port='port='.$site__database_port.';';
        } else {
            $port="";
        }
    }
    if (!defined('PHP_VERSION_ID')) {
        $version = explode('.', PHP_VERSION);
        define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
    }
    if (PHP_VERSION_ID < 50306) {
        $construct_options=array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '".$charset."'");
        $charset='';
    } else {
        $construct_options=array();
        $charset='charset='.$charset.';';
    }
    $dbname='dbname='.$site__database_database;
    $dsn='mysql:'.$host.$port.$charset.$dbname;
    try {
        $db = new PDO($dsn, $site__database_admin_username, $site__database_admin_password,$construct_options);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
        return $db;
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}

// function to run query on chosen connection
function run_query($db,$query,$pars=array()) {
    try {
        if (is_array($pars) && count($pars)>0) {
            // parametrized query
            $stmt = $db->prepare($query);
            if (isset($pars[0]) && is_array($pars[0])) {
                foreach ($pars as $tpars) $stmt->execute($tpars);
            } else {
                $stmt->execute($pars);
            }
        } else {
            // non-parametrized query
            $stmt = $db->query($query);
        }
    } catch (PDOException $e) {
        show_message('<pre>Query error: ' . $e->getMessage(). "\nQuery: ".$query."</pre>");
    }
    return $stmt;
}

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

// ACTION STARTS HERE

// create latin1 and UTF8 connection
$db1=pdoconnect($charset='latin1');
$db2=pdoconnect($charset='UTF8');

// for all defined tables
foreach ($table_fields as $table=>$tabdata) {
    // read in table data through latin1 connection
    $query="SELECT * FROM ".table($table);
    $result=run_query($db1,$query);
    $i=0;
    while ($line = pdo_fetch_assoc($result)) {
        // check whether selected fields (read via latin1) contain any valid UTF8 code
        $has_utf8=false;
        foreach($tabdata['fields'] as $f) {
            if (detectUTF8($line[$f])) {
                $has_utf8=true;
            }
        }
        // if so, write back through UTF8 connection
        if ($has_utf8) {
            $i++;
            // write data back through the utf8 connection, for selected fields
            $pars=array(); $parstring=array();
            foreach($tabdata['fields'] as $f) {
                $pars[':'.$f]=$line[$f];
                $parstring[]=$f."=:".$f;
            }
            $pars[':'.$tabdata['id']]=$line[$tabdata['id']];
            $query2="UPDATE ".table($table)."
                    SET ".implode(", ",$parstring)."
                    WHERE ".$tabdata['id']."=:".$tabdata['id'];
            $done=run_query($db2,$query2,$pars);
        }
    }
    echo "Converted ".$i." rows in table ".table($table)." to UTF-8.\n";
}

?>
