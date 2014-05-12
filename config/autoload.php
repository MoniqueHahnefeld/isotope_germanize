/**
 * 
 * @package   isotope_germize
 * @author    Monique Hahnefeld
 * @license   LGPL
 * @copyright 2014 Monique Hahnefeld
 */


/**
 * Register the namespaces

ClassLoader::addNamespaces(array
(
	'Contao'
));
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	
	'IXR_Library' => 'system/modules/isotope_germanize/classes/IXR_Library.php',
	'IsotopeGermanize' => 'system/modules/isotope_germanize/classes/IsotopeGermanize.php'
	
	
));
