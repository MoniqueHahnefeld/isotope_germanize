#Erweiterung Isotope Legal

	Diese Erweiterung baut auf der bekannten Erweiterung Isotope Germanize von Christian De La Haye auf.
##Nutzung
In der Erweiterung Isotope Legal werden die Steuerklassen-Labels die im Feld "Beschriftung einbinden" eingetragen werden für die Hinweiße im Frontend verwendet.

###Hinweiß unter dem Produktpreis
So setzt sich der **Hinweiß unter dem Produktpreis** in der Produkteliste sowie in der Detailsansicht aus der Steuerklassen-Beschriftung und den globalen Versandhinweiß zusammen.


Für den **Versandhinweiß** unter den Produkten gibt es nur 2 Informationstypen. Zum einen "kein Versandartikel" und zum Anderem "inkl. Versand", wobei "Versand" ein Link zu der Versandinformationen-Seiten ist.

Diese Bezeichnungen können in den Language-Dateien der Erweiterung geändert werden.

###Versandhinweiß im Bestellprozess – Schritt Versand
Die **Isotope Legal Erweiterung** fügt der Shopkonfiguration eine Palette an.
Darin kann eine Seite mit Versandinformationen ausgewählt werden. Diese wird dann bei der Produktpreisinformation verlinkt.

Es kann auch ein Artikel mit **wichtigen Informationen zum Versand** ausgewählt werden, welcher dann beim Bestellvorgang,im Kassenmodul, im Abschnitt Versand angezeigt werden soll.

###Steuerhinweiß NichtEu-Land 

muss noch gelöst werden

macht eigentlich nur Sinn wenn man bei einer Migliedergruppe abfragen kann ob das Land des Mitgliedes in der EU ist. Damit es mit einer Steuerklasse des Isotope core verknüpft werden kann. Damit in der Kasse dann auch wirklich ohne Steuer berechnet wird. Beim Registrieren müsste das Mitglied direkt der Gruppe zugeordnet werden aufgrund seiner Länderangabe


###Ust.-ID-Prüfung 

muss noch umgesetzt werden...

###Kassenseiten auswählen
Auf Kassenseiten bei können auch Gästen aufgrund ihrer Länderzuordnung und/oder Ust-ID Netto-Preise angezeigt werden.
Dafür müsste noch eine Auswahl der angelegten Steuerklassen integriert werden, damit zugewiesen werden kann welche Netto ist.

muss noch umgesetzt werden...

##Changes for update

extent modul Checkout->canCheckout();
validate UST-ID

###Functions
extend ProduktList-Modul and ProductReaderModul with full Tax String,
Use Globals from Germanizr + tax_class->label

overwrite ProduktVariantList-Modul, because it extends ProduktList-Modul

extend iso_reader_default.html5 + iso_list_default + iso_list_variants
###BE


###FE
The shipping link opens the shipping page always blank now.


2013
=================
isotope_germanize
=================

Enable German settings for Isotope

-

TODO: English text version contains German text. Translators needed please.

-


Spezifikationen
===============

Produkt-Ansicht oder Warenkorb
------------------------------

1. Mitglied + !EU: STEUERFREI `(c)`
2. Mitglied + EU-Land + nicht DE + VAT-NR bestätigt: STEUERFREI `(d)`
3. Mitglied "Nettopreise": Produktpreise NETTO, Steuer DAZU
	- `(a)` wenn DE
    - `(e)` wenn VAT-NR unbestätigt und EU
	- `(b)` sonst
4. Alle anderen: Produktpreise BRUTTO, Steuer INKL
	- `(a)` wenn kein Land oder DE
	- `(f)` wenn Land innerhalb EU
	- `(b)` sonst



Kasse
-----

1. (EU-Land + !DE + VAT-NR bestätigt) || !EU: STEUERFREI
	- `(c)` ausserhalb EU
	- `(d)` sonst
2. EU-Land + Mitglied "Nettopreise": dann NETTOPREISE, Steuer DAZU
	- `(a)` wenn DE
	- `(e)` wenn VAT-NR unbestätigt
	- `(a)` sonst
3. Alle andere: BRUTTOPREISE, Steuer INKL
	- `(a)` wenn DE
	- `(e)` wenn VAT-NR unbestätigt
	- `(a)` sonst



Sätze
-----

- `(a)` = kein Satz
- `(b)` = Die Preise werden unabhängig vom Lieferland %s inkl. MwSt. angezeigt. Bei Lieferung in nicht-EU-Länder wird diese in der Bestellübersicht nicht berücksichtigt.
- `(c)` = Als Lieferung an einen Leistungsempfänger in dem nicht-EU-Land %s ist der Umsatz nicht steuerbar. Es wird daher keine MwSt. berechnet.
- `(d)` = Die USt.-Id %s ist bestätigt. Der Leistungsempfänger entspricht der Lieferadresse, daher wird bei dieser innergemeinschaftlichen Leistung keine MwSt in Rechnung gestellt.
- `(e)` = Die USt.-Id %s wurde bisher leider noch nicht bestätigt. Daher wird unabhängig davon in das Lieferland %s inkl. MwSt. berechnet.
- `(f)` = Als Gast sehen Sie immer Bruttopreise. Die effektive Steuer sehen sie bei der Bestellung...




Insert-Tags
===============

{{isotopeGerman::noteShipping}}
- liefert den Inhalt des Artikels, der in der Konfiguration zur Anzeige von Versandinfos im Warenkorb festgelegt ist.


{{isotopeGerman::noteVat}}
- liefert je nach ermitteltem Status einen der o.g. Sätze a)-f). Kann z.B. am Fuß von Produktlisten genutzt werden.


{{isotopeGerman::notePricing}}
- ist für die Preis- und Steuerangaben am Produkt zuständig. Mehrere Varianten zur Nutzung in eigenen Templates:

  {{isotopeGerman::notePricing::1}} : baut die %-Angabe der Steuerklasse ID 1 ein

  {{isotopeGerman::notePricing::1::true}} : baut die %-Angabe der Steuerklasse ID 1 ein, kennzeichnet als Nicht-Versandprodukt
  
Das Einfügen von {{isotopeGerman::notePricing::<?php echo $this->raw['tax_class']; ?>}} in das Produkt-Template iso_reader_default.html5
fügt also den Preis-Hinweis mit der für dieses Produkt geltenden Steuer- und Versandoption in die Website ein.


Automatische Insert-Tags: 
---------
In die Standardtemplates für die Produkte, den Warenkorb und den Checkout werden die o.g. Angaben automatisch eingebaut. 
Allerdings nur, solange das Template nicht umbeannt wurde und auch den jeweiligen Isert-Tag noch nicht enthält.
Hier stehen aber für notePricing keine erweiterten Möglichkeiten zur Verfügung. 

