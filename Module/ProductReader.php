<?php

/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2014 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @package    Isotope
 * @link       http://isotopeecommerce.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace MHAHNEFELD\Module;


use Haste\Http\Response\HtmlResponse;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\Product;
use Isotope\Model\TaxClass;

/**
 * Class ProductReader
 *
 * Front end module Isotope "product reader".
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 */
class ProductReader extends \Module
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_iso_productreader';

    /**
     * Product
     * @var IsotopeProduct
     */
    protected $objProduct = null;


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: PRODUCT READER ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id    = $this->id;
            $objTemplate->link  = $this->name;
            $objTemplate->href  = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Return if no product has been specified
        if (\Haste\Input\Input::getAutoItem('product') == '') {
            if ($this->iso_display404Page) {
                global $objPage;
                $objHandler = new $GLOBALS['TL_PTY']['error_404']();
                $objHandler->generate($objPage->id);
                exit;
            } else {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate module
     * @return void
     */
    protected function compile()
    {
        global $objPage;
        global $objIsotopeListPage;

        $objProduct = Product::findAvailableByIdOrAlias(\Haste\Input\Input::getAutoItem('product'));

        if (null === $objProduct) {
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($objPage->id);
            exit;
        }
		
			 /*
			*********************
			EXTENDED from Isotope Legal by Monique Hahnefeld 
			*********************
			*/
			
			if ($objProduct===null) {
			continue;	
			}
			$is_Variant=$objProduct->isVariant();
			$hasVariant=$objProduct->hasVariantPrices();
			$hasAdvancedPrices=$objProduct->hasAdvancedPrices(); 
			
			$taxID = $objProduct->getPrice()->tax_class;
			$taxModel = TaxClass::findByID($taxID);
			if ($taxModel ===null) {
				$taxLabel=$GLOBALS['TL_LANG']['iso_legal']['priceNotes']['taxfree']; //Taxfree Global
			}else {
				$taxArr = $taxModel->row();
				$taxLabel = $taxArr['label'];
			}
			
			$blnShippingExempt=($objProduct->isExemptFromShipping()) ? 'true' : 'false'; 
			
			 /*
			*********************
			EXTENDED from Isotope Legal END
			*********************
			*/
		
        $arrConfig = array(
            'module'      => $this,
            'template'    => ($this->iso_reader_layout ? : $objProduct->getRelated('type')->reader_template),
            'gallery'     => ($this->iso_gallery ? : $objProduct->getRelated('type')->reader_gallery),
            'taxlabel' => $taxLabel, //germanize
            'shippingLink' => $this->getShippingLink(),
            'noShipping' => $objProduct->isExemptFromShipping(),
            'buttons'     => deserialize($this->iso_buttons, true),
            'useQuantity' => $this->iso_use_quantity,
            'jumpTo'      => ($objIsotopeListPage ? : $objPage),
        );

        if (\Environment::get('isAjaxRequest') && \Input::post('AJAX_MODULE') == $this->id && \Input::post('AJAX_PRODUCT') == $objProduct->getProductId()) {
            $objResponse = new HtmlResponse($objProduct->generate($arrConfig));
            $objResponse->send();
        }

        $arrCSS = deserialize($objProduct->cssID, true);

        $this->Template->product       = $objProduct->generate($arrConfig);
        $this->Template->product_id    = ($arrCSS[0] != '') ? ' id="' . $arrCSS[0] . '"' : '';
        $this->Template->product_class = trim('product ' . ($objProduct->isNew() ? 'new ' : '') . $arrCSS[1]);
        $this->Template->referer       = 'javascript:history.go(-1)';
        $this->Template->back          = $GLOBALS['TL_LANG']['MSC']['goBack'];

        $this->addMetaTags($objProduct);
        $this->addCanonicalProductUrls($objProduct);
    }

    /**
     * Add meta header fields to the current page
     * @param   IsotopeProduct
     */
    protected function addMetaTags(IsotopeProduct $objProduct)
    {
        global $objPage;

        $objPage->pageTitle   = $this->prepareMetaDescription($objProduct->meta_title ? : $objProduct->name);
        $objPage->description = $this->prepareMetaDescription($objProduct->meta_description ? : ($objProduct->teaser ? : $objProduct->description));

        if ($objProduct->meta_keywords) {
            $GLOBALS['TL_KEYWORDS'] .= ($GLOBALS['TL_KEYWORDS'] != '' ? ', ' : '') . $objProduct->meta_keywords;
        }
    }

    /**
     * Adds canonical product URLs to the document
     * @param   IsotopeProduct
     */
    protected function addCanonicalProductUrls(IsotopeProduct $objProduct)
    {
        global $objPage;
        $arrPageIds   = \Database::getInstance()->getChildRecords($objPage->rootId, \PageModel::getTable());
        $arrPageIds[] = $objPage->rootId;

        // Find the categories in the current root
        $arrCategories = array_intersect($objProduct->getCategories(), $arrPageIds);

        foreach ($arrCategories as $intPage) {

            // Do not use the index page as canonical link
            if ($objPage->alias == 'index' && count($arrCategories) > 1) {
                continue;
            }

            // Current page is the primary one, do not generate canonical link
            if ($intPage == $objPage->id) {
                break;
            }

            if (($objJumpTo = \PageModel::findWithDetails($intPage)) !== null) {

                $strDomain = \Environment::get('base');

                // Overwrite the domain
                if ($objJumpTo->dns != '') {
                    $strDomain = ($objJumpTo->useSSL ? 'https://' : 'http://') . $objJumpTo->dns . TL_PATH . '/';
                }

                $GLOBALS['TL_HEAD'][] = sprintf('<link rel="canonical" href="%s">', $strDomain . $objProduct->generateUrl($objJumpTo));

                break;
            }
        }
    }

    
     	/*
	*********************
	EXTENDED from Isotope Legal by Monique Hahnefeld 
	*********************
	*/
    /**
         * Return a link to the shipping costs page
         * @return string
         */
     protected function getShippingLink()
        {
            global $objPage;
    		
    		if(Isotope::getConfig()->shipping_page < 1)
    		{
    			return false;
    		}
    
    		// Build link to the shipping costs page
    			
    		
    										
    		$objTarget =  \PageModel::findByPk(Isotope::getConfig()->shipping_page);
    		
    		 
    		if ($objTarget !== null) {
    				
    			
    		
    			if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
    			{
    				$strUrl = $this->generateFrontendUrl($objTarget->row(), null, $objTarget->language);
    			}
    			else
    			{
    				$strUrl = $this->generateFrontendUrl($objTarget->row());
    			}
    			
    			$strLink = '<a href="'.$strUrl.'"';
    	
    			if (strncmp(Isotope::getConfig()->shipping_rel, 'lightbox', 8) !== 0 || $objPage->outputFormat == 'xhtml')
    			{
    				$strLink .= ' rel="'. Isotope::getConfig()->shipping_rel .'"';
    			}
    			else
    			{
    				$strLink .= ' data-lightbox="'. Isotope::getConfig()->shipping_rel .'"';
    			}
    			
    			if(Isotope::getConfig()->shipping_target)
    			{
    				$strLink .= ($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"';
    			}
    	
    			$strLink .= '>';
    			$strLink .= $GLOBALS['TL_LANG']['iso_legal']['priceNotes']['linkname'];
    			$strLink .= '</a>';
    			
    			return $strLink;
    		}else {
    			return false;
    		}
    	}
    
}
