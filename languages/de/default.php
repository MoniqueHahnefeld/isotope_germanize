<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Provides several functionality for German shops:
 * VAT-handling, gross- and net-prices, tax-notes at several places
 *
 * This extension depends on the Contao-Extension Isotope eCommerce
 *
 * @copyright  2013 de la Haye Kommunikationsdesign <http://www.delahaye.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @package    isotope_germanize
 * @license    LGPL
 * @filesource
 * @package    isotope_legal
 * @author 2014  Monique Hahnefeld <info@monique-hahnefeld.de> update to contao 3, isotope 2
 */


/**
 * Order button
 */

$GLOBALS['TL_LANG']['MSC']['confirmOrder']                          = 'Kaufen';


/**
 * Notes at the products, in the cart etc
 */


$GLOBALS['TL_LANG']['iso_legal']['priceNotes']['taxfree']       = 'Steuerfrei';
$GLOBALS['TL_LANG']['iso_legal']['priceNotes']['linkname']       = 'Versand';
$GLOBALS['TL_LANG']['iso_legal']['priceNotes']['shipping']       = 'zzgl. ';
$GLOBALS['TL_LANG']['iso_legal']['priceNotes']['noShipping']     = ', kein Versandartikel';


$GLOBALS['TL_LANG']['iso_legal']['notes']['nonEuGuest']        = 'Die Preise werden unabhängig vom Lieferland %s inkl. MwSt. angezeigt. Bei Lieferung in nicht-EU-Länder wird diese in der Bestellübersicht nicht berücksichtigt.';
$GLOBALS['TL_LANG']['iso_legal']['notes']['nonEu']             = 'Als Lieferung an einen Leistungsempfänger in dem nicht-EU-Land %s ist der Umsatz nicht steuerbar. Es wird daher keine MwSt. berechnet.';
$GLOBALS['TL_LANG']['iso_legal']['notes']['confirmedVatNo']    = 'Die USt.-Id %s ist bestätigt. Der Leistungsempfänger entspricht der Lieferadresse, daher wird bei dieser innergemeinschaftlichen Leistung keine MwSt in Rechnung gestellt.';
$GLOBALS['TL_LANG']['iso_legal']['notes']['unconfirmedVatNo']  = 'Die USt.-Id %s wurde bisher leider noch nicht bestätigt. Daher wird unabhängig davon in das Lieferland %s inkl. MwSt. berechnet.';
$GLOBALS['TL_LANG']['iso_legal']['notes']['noVatNo']           = 'Die Preise werden unabhängig vom Lieferland %s inkl. MwSt. angezeigt. Geben Sie bei der Bestellung eine gültige USt.-Id. an, wird entsprechend einer innergemeinschaftlichen Leistung keine MwSt in Rechnung gestellt.';

/**
 * VAT-Id check legends
 */

$GLOBALS['TL_LANG']['iso_legal']['error']['vat_no']            = 'Fehler in der USt.-ID';
$GLOBALS['TL_LANG']['iso_legal']['error']['own_vat_no']        = 'Fehler in der eigenen USt.-ID';
$GLOBALS['TL_LANG']['iso_legal']['error']['server']            = 'Server nicht erreichbar';
$GLOBALS['TL_LANG']['iso_legal']['error']['general']           = 'Fehler in der Abfrage, siehe Fehlercodes';

$GLOBALS['TL_LANG']['iso_legal']['vat_no_confirmed']           = 'bestätigt';
$GLOBALS['TL_LANG']['iso_legal']['vat_no_notconfirmed']        = 'USt.-ID nicht bestätigt';

$GLOBALS['TL_LANG']['iso_legal']['bff']['A']                   = 'stimmt überein';
$GLOBALS['TL_LANG']['iso_legal']['bff']['B']                   = 'stimmt nicht überein';
$GLOBALS['TL_LANG']['iso_legal']['bff']['C']                   = 'nicht angefragt';
$GLOBALS['TL_LANG']['iso_legal']['bff']['D']                   = 'vom EU-Mitgliedsstaat nicht mitgeteilt';


/**
 * Notification mails
 */

$GLOBALS['TL_LANG']['iso_legal']['nok']            = 'nicht freigeschaltet - ungeprüft';
$GLOBALS['TL_LANG']['iso_legal']['nok_invalid']    = 'nicht freigeschaltet - nicht verfizierbar';
$GLOBALS['TL_LANG']['iso_legal']['nok_simple']     = 'nicht freigeschaltet - gültig';
$GLOBALS['TL_LANG']['iso_legal']['nok_qualified']  = 'nicht freigeschaltet - verifiziert';
$GLOBALS['TL_LANG']['iso_legal']['ok_qualified']   = 'automatisch freigeschaltet';
$GLOBALS['TL_LANG']['iso_legal']['ok_manual']      = 'manuell freigeschaltet';

$GLOBALS['TL_LANG']['iso_legal']['guest_order']                 = 'Gast-Bestellung';
$GLOBALS['TL_LANG']['iso_legal']['inactive']                    = 'INAKTIV: ';

$GLOBALS['TL_LANG']['iso_legal']['mail_verfication_subject']   = '##inactive##USt.-ID Verifizierung ##vat_no## (##company##)';

$GLOBALS['TL_LANG']['iso_legal']['mail_verfication_text']      = '
Automatisierte USt.-ID-Prüfung
=======================================

Status       : ##status##

USt.-ID-Nr.  : ##vat_no##
Datum        : ##date##
Server       : ##host##

Kundendaten des Leistungsempfängers
--------------------------------------
Firma        : ##company##
Straße       : ##street##
PLZ          : ##postal##
Ort          : ##city##
Land         : ##country##

Empfangene Server-Antwort
--------------------------------------
Code         : ##check_code##
Datum        : ##check_date##
Zeit         : ##check_time##
Firma        : ##check_company##
Straße       : ##check_street##
PLZ          : ##check_postal##
Ort          : ##check_city##

(aufgrund länderspezifischer Regelungen sind ggf. nicht alle Angaben abrufbar)

Speicherung in der Contao-Installation
--------------------------------------
Mitglieds-ID : ##member_id##
Adressen-ID  : ##address_id##
';

$GLOBALS['TL_LANG']['iso_legal']['mail_reminder_subject']      = 'Fehlende USt.-ID Verifizierung ##vat_no## (##company##)';

$GLOBALS['TL_LANG']['iso_legal']['mail_reminder_text']         = '
Fehlerhafte USt.-ID-Prüfung
=======================================

Status       : ##status##
Fehler       : ##error##

USt.-ID-Nr.  : ##vat_no##
Mitglieds-ID : ##member_id##
Adressen-ID  : ##address_id##

Datum Versuch: ##date##
Server       : ##host##

Kundendaten des Leistungsempfängers
--------------------------------------
Firma        : ##company##
Straße       : ##street##
PLZ          : ##postal##
Ort          : ##city##
Land         : ##country##

Empfangene Server-Antwort
--------------------------------------
Code         : ##check_code##
Datum        : ##check_date##
Zeit         : ##check_time##
Firma        : ##check_company##
Straße       : ##check_street##
PLZ          : ##check_postal##
Ort          : ##check_city##
';
?>
