<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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
 *
 * New Version of isotope_germanize for Isotope 2
 * @copyright   2014 Monique Hahnefeld
 * @author      Monique Hahnefeld <info@monique-hahnefeld.de>
 * @package    isotope_legal
 * @license    LGPL
 */

/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotope']['iso_productlist']= 'MHAHNEFELD\Module\ProductList';
$GLOBALS['FE_MOD']['isotope']['iso_productvariantlist']= 'MHAHNEFELD\Module\ProductVariantList';
$GLOBALS['FE_MOD']['isotope']['iso_productreader']= 'MHAHNEFELD\Module\ProductReader';


/**
 * Settings
 */
$GLOBALS['isotope_legal']['testmode'] = false; // true always verifies the VAT id. Only for testing!

$GLOBALS['isotope_legal']['order_printed_verification'] = false; // auto-order a print document at the German authorities (beware in test cases!!)
$GLOBALS['isotope_legal']['loose_verification_street']  = false; // state a verfication as 'qualified' even if the street is not verified
$GLOBALS['isotope_legal']['loose_verification_postal']  = false; // state a verfication as 'qualified' even if the postal is not verified


/**
 * Set VAT-Id check as first checkout step for addresses
 */
array_insert($GLOBALS['ISO_CHECKOUT_STEPS']['address'], 0, array(array('IsotopeGermanize', 'updateVatCheck')));


/**
 * Hooks
 */

$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = array('IsotopeGermanize', 'injectNotes');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]     = array('IsotopeGermanize', 'isotopeGermanizeInsertTags');
$GLOBALS['ISO_HOOKS']['getOrderEmailData'][]    = array('IsotopeGermanize', 'isotopeGermanizeOrderEmailData'); 
