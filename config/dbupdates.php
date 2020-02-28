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
    'content'=>array('en'=>'','de'=>'')    // *for new_lang_item: one expression for each language, first one is taked as default and filled in for non-existing languages
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'new_admin_right', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'right_name'=>'', // *for new_admin_right: shortcut for admin right
    'admin_types'=>array('admin','experimenter')    // *for new_admin_right: list of admin types for which this right should be set (if not exists yet)
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
'version'=>'2020022400',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_interface_language',
    'content_type'=>'lang',
    'content'=>array('en'=>'Interface language ...','de'=>'Interface-Sprache ...')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022400',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'where_interface_language_is',
    'content_type'=>'lang',
    'content'=>array('en'=>'where the interface language is','de'=>'wo die Interface-Sprache ist')
    )
);


$system__database_upgrades[]=array(
'version'=>'2020022500', 
'type'=>'new_admin_right',
'specs'=> array(
    'right_name'=>'participants_bulk_anonymization', 
    'admin_types'=>array('admin','developer','installer')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500', 
'type'=>'new_admin_right',
'specs'=> array(
    'right_name'=>'pform_anonymization_fields_edit', 
    'admin_types'=>array('admin','developer','installer')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'fields_to_anonymize_in_anonymization_bulk_action', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields to anonymize in anonymization bulk action','de'=>'Zu setzende Felder bei Profil-Anonymisierung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymized_dummy_value', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymized dummy value','de'=>'Zu setzender Dummy-Wert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymize_profiles', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymize profiles','de'=>'Anonymisiere Profile')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymize_profiles_for', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymize profiles for','de'=>'Anonymisiere Profile für')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'fields_will_be_anonymized_as_follows', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields will be anonymized as follows','de'=>'Felder werden wie folgt anonymisiert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'disclaimer_anonymize_profiles', 
    'content_type'=>'lang',
    'content'=>array('en'=>'<font color="red">Careful! This procedure is irreversible. Anonymized profiles cannot be recovered.</font>','de'=>'<font color="red">Vorsicht! Diese Aktion kann nicht rückgängig gemacht werden. Anonymiserte Profile können nicht wiederhergestellt werden.</font>')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'upon_anonymization_change_status_to', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Upon anonymization of the profile, change participant status to','de'=>'Nach der Anonymisierung, ändere Teilnehmer-Status zu')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'profile_anonymize', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Anonymize profiles','de'=>'Anonymisiere Profile')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'error_no_fields_to_anonymize_defined', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Error! There is no definition of fields to anonymize. See ORSEE options.','de'=>'Fehler! Es wurden keine Felder zur Anonymiserung definiert. Siehe ORSEE Optionen.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'xxx_participant_profiles_were_anonymized', 
        'content_type'=>'lang',
        'content'=>array('en'=>'participant profiles were anonymized.','de'=>'Teilnehmer-Profile wurden anonymisiert.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'hide_column_for_admin_types', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Hide this column for admin types','de'=>'Diese Spalte verbergen für Admin-Typen')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'enter_comma_separated_list_of_any_of', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Enter comma seperated list of any of these types:','de'=>'Geben Sie eine komma-separierte Liste aus folgenden Typen ein:')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'hidden_data_symbol', 
        'content_type'=>'lang',
        'content'=>array('en'=>'***','de'=>'***')
    )
);

 $system__database_upgrades[]=array(
    'version'=>'2020022700',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'bulk_updated_session_statuses', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Bulk-updated status of selected sessions.','de'=>'Session-Status der gewählten Sessions geändert.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022700',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'set_session_status_for_selected_sessions_to', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Set session status of selected sessions to:','de'=>'Setze Session-Status der selektierten Session auf:')
   )
);

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'download_as', 
        'content_type'=>'lang',
        'content'=>array('en'=>'DOWNLOAD AS','de'=>'HERUNTERLADEN ALS')
    )
);
  

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'pdf_file', 
        'content_type'=>'lang',
        'content'=>array('en'=>'PDF','de'=>'PDF')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'csv_file', 
        'content_type'=>'lang',
        'content'=>array('en'=>'CSV','de'=>'CSV')
    )
);

?>