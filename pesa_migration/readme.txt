In den Scripten den Datenbanknamen orsee durch Datenbanknamen pesa und orsee_new durch den neuen Datenbankenamen ersetzen.

Reihenfolge der Abarbeitung:

1. pesa_transfer_admin
2. pesa_transfer_experiment_types
3. pesa_transfer_experiments
4. pesa_transfer_sessions

alles andere beliebig

Alle Querys, die auf die or_lang Tabelle verschieben den Index um 220004 damit es zu keinen Konflikten beim einfügen gibt, da der größte Index in eine frischen Orsee3 or_lang Tabelle 220003 ist.
Gegebenfalls muss die or_lang Tabelle noch angepasst werden (siehe pesa_transfer_rules).
