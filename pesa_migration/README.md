# Anleitung zur Migration  von PESA

1. hu-psy Branch nach Anleitung (http://www.orsee.org/web/install_notes.php) installieren
   * Achtung: Die neue Datenbank muss pesa2019 heißen (wird in den Skripten so angenommen)
2. Die alte Pesa-Datenbank als pesa importieren (wird in den Skripten so angenommen)
3. Die .htaccess Datei des alten PESA-Servers in dieses Verzeichnis kopieren (nur diese Admins werden übernommen)
4. Das Skript pesa_transfer.sh ausführen (gegebenfalls als root)
5. Als Administrator einloggen und weitere Konfigurationen vornehmen (alternativ in der Datenbank anpassen), z.B.:
   * General Settings 
     * Allow subjects to cancel their session enrolment? -> yes
     * How should subjects authenticate with the system? -> Email address and password
     * Do subjects have to accept the lab rules when enroling? -> yes
     * Do subjects have to accept the privacy policy when enroling? -> yes
     * System support email address (used as sender for most emails)? -> richtige Supportadresse?
   * Participant Profile Field
     * Diverse als Gender hinzufügen (Gender -> edit)
   * Languages -> add symbol -> Texte für neues Gender in den gegebenen Sprachen hinzufügen

### Sonstiges:
* Alle Querys, die auf der or_lang Tabelle arbeiten, verschieben den Index um 220004 damit es zu keinen Konflikten beim einfügen gibt, da der größte Index in eine frischen Orsee3 or_lang Tabelle 220003 ist.

