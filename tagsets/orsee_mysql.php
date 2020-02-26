<?php
// part of orsee. see orsee.org

// creates databae connection
function site__database_config() {
    global $db, $site__database_host, $site__database_admin_username,
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
        $construct_options=array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
        $charset='';
    } else {
        $construct_options=array();
        $charset='charset=UTF8;';
    }
    $dbname='dbname='.$site__database_database;

    $dsn='mysql:'.$host.$port.$charset.$dbname;

    if (isset($site__database_use_ssl) && $site__database_use_ssl) {
        if ($site__database_ssl_key) {
            $construct_options[PDO::MYSQL_ATTR_SSL_KEY]=$site__database_ssl_key;
        }
        if ($site__database_ssl_cert) {
            $construct_options[PDO::MYSQL_ATTR_SSL_CERT]=$site__database_ssl_cert;
        }
        if ($site__database_ssl_ca) {
            $construct_options[PDO::MYSQL_ATTR_SSL_CA]=$site__database_ssl_ca;
        }
    }

    try {
        $db = new PDO($dsn, $site__database_admin_username, $site__database_admin_password,$construct_options);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }

}

// adds prefix to table name
function table($table) {
    global $site__database_table_prefix;
    $tablename=$site__database_table_prefix.$table;
    return $tablename;
}

function id_array_to_par_array($id_array,$parname='id') {
    $pars=array(); $keys=array(); $i=0;
    foreach ($id_array as $id) {
        $i++;
        $key=':'.$parname.$i;
        $pars[$key]=$id;
        $keys[]=$key;
    }
    return array('pars'=>$pars,'keys'=>$keys);
}

// general query wrapper and query timer
function or_query($query,$pars=array()) {
    global $db;
    $id=start_query_timer($query,$pars);
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
    $end=stop_query_timer($id);
    return $stmt;
}

// build nonparametrized query from parametrized query
function or_nonparam_query($query,$pars=array()) {
    if (isset($pars[0]) && is_array($pars[0])) {
        $query=$query.' with '.count($pars).' sets of parameters';
    } else {
        foreach($pars as $k=>$v) $query=str_replace($k,pdo_escape_string($v),$query);
    }
    return $query;
}


function start_query_timer($query,$params=array()) {
    global $debug__query_time, $debug__query_array, $settings__query_debugging_enabled;
    if (isset($settings__query_debugging_enabled) && $settings__query_debugging_enabled=='y') {
        if (!isset($debug__query_time)) $debug__query_time=0;
        if (!isset($debug__query_array)) $debug__query_array=array();
        $id=uniqid();
        $start=getmicrotime();
        $debug__query_array[$id]['start']=$start;
        if (count($params)>0) $query='<B>Parametrized: </B>'.or_nonparam_query($query,$params);
        $debug__query_array[$id]['query']=$query;
        //if (count($params)>0) {
        //  $pars=array();
        //  foreach ($params as $k=>$v) $pars[]=$k.'='.$v;
        //  $debug__query_array[$id]['query'].=' <b>with params:</b> '.implode(", ",$pars);
        //}
        return $id;
    } else return false;
}

function stop_query_timer($id) {
    global $debug__query_time, $debug__query_array, $settings__query_debugging_enabled;
    if (isset($settings__query_debugging_enabled) && $settings__query_debugging_enabled=='y') {
            $tq_time=getmicrotime()-$debug__query_array[$id]['start'];
            $debug__query_time+=$tq_time;
            $debug__query_array[$id]['time']=$tq_time;
            return true;
    } else return false;
}


function pdo_fetch_assoc($stmt) {
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function pdo_num_rows($stmt) {
    return $stmt->rowCount();
}

function pdo_free_result(&$stmt) {
    $stmt->closeCursor();
}

function pdo_escape_string($string) {
    global $db;
    return $db->quote($string,PDO::PARAM_STR);
}

function pdo_insert_id() {
    global $db;
    return $db->lastInsertId();
}

function pdo_transaction($queries) { // works only with innoDB. Should we move to inno?
    global $db;
    try {
        $db->beginTransaction();
        foreach ($queries as $q) {
            if(isset($q['pars']) && is_array($q['pars'])) {
                $stmt = $db->prepare($q['query']);
                if (isset($q['pars'][0]) && is_array($q['pars'][0])) {
                    foreach ($q['pars'] as $tpars) $stmt->execute($tpars);
                } else {
                    $stmt->execute($q['pars']);
                }
            } else {
                $stmt = $db->query($q['query']);
            }
        }
        $db->commit();
    } catch(PDOException $e) {
        //Something went wrong, rollback!
        $db->rollBack();
        die('<pre>Could not complete transaction: ' . $e->getMessage());
    }
}


// very convenient functions translated from metahtml

function orsee_query($query,$pars=array()) {
    $result=or_query($query,$pars);
    $line=pdo_fetch_assoc($result);
    pdo_free_result($result);
    return $line;
}

function orsee_db_load_array($table,$key,$keyname) {
        $query="SELECT * FROM ".table($table)." where ".$keyname."=:key";
        $pars=array(':key'=>$key);
        $line=orsee_query($query,$pars);
        return $line;
}


function orsee_db_save_array($array,$table,$key,$keyname) {
    global $site__database_database;

    // find out which fields i can save
    $query="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name= :table
            AND table_schema = :table_schema";
    $pars=array(':table'=>table($table),'table_schema'=>$site__database_database);
    $result=or_query($query,$pars); $columns=array();
    while ($line = pdo_fetch_assoc($result)) {
        $columns[]=$line['COLUMN_NAME'];
    }

    // delete key
    if (isset($array[$keyname])) unset($array[$keyname]);
    $arraykeys=array_keys($array);
    $fields_to_save=array_intersect($arraykeys,$columns);
    // build set phrase and param array
    $first=true; $set_phrase=""; $pars=array();
    foreach ($fields_to_save as $field) {
        if ($first) $first=false; else $set_phrase=$set_phrase.", ";
        $set_phrase=$set_phrase.$field."=:".$field;
        $pars[':'.$field]=$array[$field];
    }
    $pars[':key']=$key;

    // check if already saved
    $query="SELECT ".$keyname." FROM ".table($table)." WHERE ".$keyname."=:key";
    $result=or_query($query,array(':key'=>$key));
    $num_rows = pdo_num_rows($result);

    if ($num_rows>0) {
        // update query
            $query="UPDATE ".table($table)." SET ".$set_phrase." WHERE ".$keyname."=:key";
         } else {
        // insert query
            $query="INSERT INTO ".table($table)." SET ".$keyname."=:key, ".$set_phrase;
        }
    $result=or_query($query,$pars);
    return $result;
}

function dump_array($array,$title="",$dolang=true) {
    echo '<TABLE border=0>';
    if ($title) echo '<TR><TD colspan=2 align="center"><B>'.$title.'</B></TD></TR>';
    foreach ($array as $key => $value) {
        echo '<TR><TD align="right" valign="top">';
        if ($dolang) echo lang($key); else echo stripslashes($key);
        echo ':</TD><TD>&nbsp;</TD><TD align=left valign="top">';
        if (is_array($value)) dump_array($value,$title,$dolang);
        else {
            if ($dolang) echo lang($value); else echo stripslashes($value);
        }
        echo "</TD></TR>\n";
        }
    echo '</TABLE>';
}

function check_database_upgrade() {
    global $settings, $system__database_version;

    if (!isset($settings['database_version'])) {
        $settings['database_version']=0;
        $done=or_query("INSERT INTO ".table('options')." VALUES (1,'general',NULL,'database_version','".$settings['database_version']."')");
    }
    if ((int)$settings['database_version']<$system__database_version) {
        $done=or_upgrade_database();
        return $done;
    } else {
        return false;
    }
}

function upgrade_database_version($new_version) {
    global $settings;
    $settings['database_version']=(int)$new_version;
    $done=orsee_db_save_array(array('option_value'=>$new_version),'options',1,'option_id');
    if(!$done) log__admin("Database upgrade error. Could not set database version to new version number ".$new_version."!");
    return $done;
}


function or_upgrade_database() {
    global $settings, $system__database_version, $system__database_upgrades;
    
    if (!isset($system__database_upgrades)) {
        $system__database_upgrades=array();
    }

    // check $system__database_upgrades for syntax errors, resort database upgrades by version number
    $database_upgrades=array(); $i=0; $continue=true;
    foreach ($system__database_upgrades as $upgr) {
        $i++;
        if ($continue) {
            $error=or_upgrade_check_syntax($upgr);
            if ($error) {
                $continue=false;
                log__admin("Error in upgrade syntax for $system__database_upgrades item nb ".$i.": ".$error." Upgrade aborted.");
            } else {
                $upgr['item_nb']=$i;
                $database_upgrades[$upgr['version']][]=$upgr;
            }
        }
    }
    if ($continue) {
        $done=ksort($database_upgrades,SORT_NUMERIC);
    }
    
    // run the updates, stop if there is an error
    $continue=true;
    foreach ($database_upgrades as $this_version=>$vupgrades) {
        if ((int)$this_version>(int)$settings['database_version']) {
            foreach ($vupgrades as $upgr) {
                if ($continue) {
                   if ($upgr['type']=='new_lang_item') {
                        $done=lang__upgrade_symbol_if_not_exists($upgr['specs']);
                    } elseif ($upgr['type']=='new_admin_right') {
                        $done=admin__update_admin_rights_if_not_exists($upgr['specs']);
                    } elseif ($upgr['type']=='query') {
                        $query=preg_replace('/TABLE\(([^)]+)\)/',table("$1"),$upgr['specs']['query_code']);
                        $done=or_query($query);
                        if ($done) {
                            log__admin("Automatic database upgrade: executed query for \$system__database_upgrades item nb ".$i.".");
                        } else {
                            $continue=false;
                            log__admin("Error in upgrade: Query for \$system__database_upgrades item nb ".$i." could not be executed. Upgrade aborted.");
                        }
                    }
                }
            }
            if ($continue) {
                $done=upgrade_database_version((int)$this_version);
                log__admin('Automatic database upgrade: Database upgraded to version '.$this_version.'.');
            }
        }
    }
    message("Ran automatic database upgrades. See Statistics/Logs/Experimenter actions for report.");
    return $continue;
}


function or_upgrade_check_syntax($upgr) {
    $continue=true; $error="";
    
    if ($continue && !isset($upgr['version'])) {
        $continue=false;
        $error="No upgrade version number given.";
    }
    if ($continue && !isset($upgr['type'])) {
        $continue=false;
        $error="No upgrade type given.";
    }
    if ($continue && !in_array($upgr['type'],array('new_lang_item','new_admin_right','query'))) {
        $continue=false;
        $error="Given upgrade type not valid.";
    }
    if ($continue && $upgr['type']=='new_lang_item' && (!isset($upgr['specs']['content_name']) || !isset($upgr['specs']['content']) || !is_array($upgr['specs']['content']))) {
        $continue=false;
        $error="Upgrade syntax not correct for upgrade type 'new_lang_item'.";
    }
    if ($continue && $upgr['type']=='new_admin_right' && (!isset($upgr['specs']['right_name']) || !isset($upgr['specs']['admin_types']) || !is_array($upgr['specs']['admin_types']))) {
        $continue=false;
        $error="Upgrade syntax not correct for upgrade type 'new_admin_right'.";
    }
    if ($continue && $upgr['type']=='query' && (!isset($upgr['specs']['query_code']) || !$upgr['specs']['query_code'])) {
        $continue=false;
        $error="Upgrade syntax not correct for upgrade type 'query'.";
    }
    if ($continue==false && !$error) {
        $error="Unknown error.";
    }
    return $error;
}


?>
