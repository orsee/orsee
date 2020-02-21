<?php
// part of orsee. see orsee.org
// THIS FILE WILL CHANGE FROM VERSION TO VERSION. BETTER NOT EDIT.


// DATABASE UPGRADE DEFINITIONS //
// add entries to array $system__database_upgrades


/* SAMPLE CODE FOR UPGRADES

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'new_lang_item', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'content_name'=>'', // *for new_lang_item: shortcut for item
    'content_type'=>'', // for new_lang_item: type for item, default: lang
    'content'=>array('en'=>'','de'=>''),    // *for new_lang_item: one expression for each language, first one is taked as default and filled in for non-existing languages
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'new_admin_right', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'right_name'=>'', // *for new_admin_right: shortcut for admin right
    'admin_types'=>array('admin','experimenter'),    // *for new_admin_right: list of admin types for which this right should be set (if not exists yet)
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'query', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'query_code'=>'' // *for query: SQL statement to be executed. You can use "TABLE(tablename)" to have "or_" or the respective ORSEE table rpefix automatically prepended
    )
);

END SAMPLE CODE
*/

$system__database_upgrades[]=array(
'version'=>'2020021100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_interface_language',
    'content_type'=>'lang',
    'content'=>array('en'=>'Interface language ...','de'=>'Interface-Sprache ...')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'where_interface_language_is',
    'content_type'=>'lang',
    'content'=>array('en'=>'where the interface language is','de'=>'wo die Interface-Sprache ist')
    )
);


?>