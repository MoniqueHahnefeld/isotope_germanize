<?php /**
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
	'ValidateDATA' => 'system/modules/isotope_germanize/classes/ValidateDATA.php',
	'IsotopeGermanize' => 'system/modules/isotope_germanize/classes/IsotopeGermanize.php'
	
	
));
/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'iso_reader_default'    => 'system/modules/isotope_germanize/templates',
	'iso_list_default'    => 'system/modules/isotope_germanize/templates',
	'iso_list_variants'    => 'system/modules/isotope_germanize/templates'
	
));
?>
