<?php /**
 * 
 * @package   isotope_legal
 * @author    Monique Hahnefeld <info@monique-hahnefeld.de>
 * @license   LGPL
 * @copyright 2014 Monique Hahnefeld
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'MHAHNEFELD\Module'
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	
	//Modules
	'MHAHNEFELD\Module\ProductVariantList' => 'system/modules/isotope_legal/Module/ProductVariantList.php',
	'MHAHNEFELD\Module\ProductList' => 'system/modules/isotope_legal/Module/ProductList.php',
	'MHAHNEFELD\Module\ProductReader' => 'system/modules/isotope_legal/Module/ProductReader.php',
	// Classes
	
	'IXR_Library' => 'system/modules/isotope_legal/classes/IXR_Library.php',
	'ValidateDATA' => 'system/modules/isotope_legal/classes/ValidateDATA.php',
	'IsotopeGermanize' => 'system/modules/isotope_legal/classes/IsotopeGermanize.php'
	
	
));
/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'iso_reader_default'    => 'system/modules/isotope_legal/templates',
	'iso_list_default'    => 'system/modules/isotope_legal/templates',
	'iso_list_variants'    => 'system/modules/isotope_legal/templates'
	
));
?>
