<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Provides several functionality for German shops:
 * VAT-handling, gross- and net-prices, tax-notes at several places
 *
 * This extension depends on the Contao-Extension Isotope eCommerce
 *
 * @copyright  2013 de la Haye Kommunikationsdesign <http://www.delahaye.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @package    isotope_germanize
 * @license    LGPL
 * @filesource
 */

use Haste\Haste;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Model\Product;
use Isotope\Model\ProductType;
use Isotope\Model\ProductPrice;
use Isotope\Model\TaxClass;

/**
 * German shop functionality
 *
 * @package	   isotope_germanize
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 */
class IsotopeGermanize extends FrontendTemplate
{

    protected static $arrEuCountries = array('at', 'be', 'bg', 'cy', 'cz', 'de', 'dk', 'es', 'fi', 'fr', 'gb', 'gr', 'hu', 'ie', 'it', 'je', 'lt', 'lu', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'se', 'si', 'sk');

	protected static $strHost = 'evatr.bff-online.de';

	protected $arrCheckData = array();

    /**
     * Return true if german tax management is active
     * @return bool
     */
    public static function isActive()
    {
        return (bool) Isotope\Isotope::getConfig()->germanize;
    }


    /**
     * Return true if user is on a checkout page
     * @return bool
     */
    public static function isOnCheckoutPage()
    {
        global $objPage;

        $arrPages = deserialize(Isotope\Isotope::getConfig()->checkout_pages, true);

        return in_array($objPage->id, $arrPages);
    }


    /**
     * Return true if user is in germany or country is unknown
     * @return bool
     */
    public static function isGermany()
    {
        $strCountry = Isotope\Isotope::getCart()->shippingAddress->country;

        return ($strCountry == 'de' || $strCountry == '');
    }


    /**
     * Return true if address is in the EU
     * @return bool
     */
    public static function isEuropeanUnion()
    {
        return in_array(Isotope\Isotope::getCart()->shippingAddress->country, self::$arrEuCountries);
    }




    /**
     * Return true if all prices have to be tax free
     * @return bool
     */
    public static function hasTaxFreePrices()
    {
        $blnGuestCheck = (FE_USER_LOGGED_IN === true || self::isOnCheckoutPage());

        // Situation 1
        if ($blnGuestCheck && !self::isEuropeanUnion()) {
            return true;
        }

        // Situation 2
        if ($blnGuestCheck && self::isEuropeanUnion() && !self::isGermany() && self::hasValidVatNo()) {
            return true;
        }

        return false;
    }



    /**
     * Return a notice about the vat status of the order, is displayed in the cart and checkout
     * @return string
     */
    public static function getTaxNotice()
    {
        $objAddress = Isotope\Isotope::getCart()->shippingAddress;
        $arrCountries = Haste::getInstance()->call('getCountries');
        $strCountry = $arrCountries[$objAddress->country];

        $nonEUGuest = sprintf($GLOBALS['TL_LANG']['iso_germanize']['notes']['nonEuGuest'], $strCountry);
        $nonEU = sprintf($GLOBALS['TL_LANG']['iso_germanize']['notes']['nonEu'], $strCountry);
        $confirmedVatNo = sprintf($GLOBALS['TL_LANG']['iso_germanize']['notes']['confirmedVatNo'], $objAddress->vat_no);
        $unconfirmedVatNo = sprintf($GLOBALS['TL_LANG']['iso_germanize']['notes']['unconfirmedVatNo'], $objAddress->vat_no, $strCountry);
        $noVatNo = sprintf($GLOBALS['TL_LANG']['iso_germanize']['notes']['noVatNo'], $strCountry);

        if (self::isGermany()) {
            return '';
        }

        if (!self::isOnCheckoutPage()) {

            if (FE_USER_LOGGED_IN === true && !self::isEuropeanUnion()) {
                return $nonEU;
            } elseif (FE_USER_LOGGED_IN === true && self::isEuropeanUnion() && self::hasValidVatNo()) {
                return $confirmedVatNo;
            } elseif (self::hasNetPriceGroup()) {

                if (self::isEuropeanUnion() && self::hasVatNo() && !self::hasValidVatNo()) {
                    return $unconfirmedVatNo;
                } else {
                    return $nonEUGuest;
                }

            } elseif (self::isEuropeanUnion()) {
                return $noVatNo;
            } else {
                return $nonEUGuest;
            }

        } else {

            if (!self::isEuropeanUnion()) {
                return $nonEU;
            } elseif (self::isEuropeanUnion() && self::hasValidVatNo()) {
                return $confirmedVatNo;
            } elseif (self::isEuropeanUnion() && self::hasVatNo()) {
                return $unconfirmedVatNo;
            } else {
                return '';
            }
        }
    }



	/**
	 * Inject notes in default templates automatically
	 * @param string
	 * @param string
	 * @return string
	 */
	public function injectNotes($strBuffer, $strTemplate)
	{
	
		
        // Use only if the shop is defined as German
        if (!IsotopeGermanize::isActive()) {
            return $strBuffer;
        }
//var_dump($strBuffer);
//echo $strTemplate.'<br>';

		switch($strTemplate)
		{
			case 'mod_iso_productlist':
			
			
			if(strpos($strBuffer,'notePricing')===false)
			{
				// Inject the pricing note after the baseprice or the price
				$strSearchTag = 'price';
//				
				$newBuffer= preg_replace('#\<div class="'.$strSearchTag.'">(.*)\</div>(.*)#Uis', '<div class="'.$strSearchTag.'">\1</div>'.$this->isotopeGermanizeInsertTags('isotopeGerman::notePricing').'\2', $strBuffer);
				
				
				return $newBuffer;
			}
			

			case 'iso_reader_default':
				// Pricing note at the product
				
				$objProduct = Product::findAvailableByIdOrAlias(\Haste\Input\Input::getAutoItem('product'));
				
				$productPrice = ProductPrice::findAll();
				$is_Variant=$objProduct->isVariant();
				$hasVariant=$objProduct->hasVariantPrices();
				$hasAdvancedPrices=$objProduct->hasAdvancedPrices(); 
				
				$taxID = $objProduct->getPrice()->tax_class;
				$taxModel = TaxClass::findByID($taxID)->row();
				$taxLabel = $taxModel['label'];
				
				$blnShippingExempt=($objProduct->isExemptFromShipping()) ? 'true' : 'false'; 				
				
				if(strpos($strBuffer,'notePricing')===false)
				{
					// Inject the pricing note after the baseprice or the price
					
//					
					if(strpos($strBuffer,'class="baseprice')===false)
					{
						$strSearchTag = 'price';
					$newBuffer= preg_replace('#\<div class="'.$strSearchTag.'" itemprop="'.$strSearchTag.'">(.*)\</div>(.*)#Uis', '<div class="'.$strSearchTag.'" itemprop="'.$strSearchTag.'">\1</div>'.$this->isotopeGermanizeInsertTags('isotopeGerman::notePricing::'.$taxLabel.'::'.$blnShippingExempt.'').'\2', $strBuffer);
					}
					elseif(strpos($strBuffer,'class="baseprice')!==false)
					{
						$strSearchTag = 'baseprice';
						
						$newBuffer= preg_replace('#\<div class="'.$strSearchTag.'">(.*)\</div>(.*)#Uis', '<div class="'.$strSearchTag.'">\1</div>'.$this->isotopeGermanizeInsertTags('isotopeGerman::notePricing::'.$taxLabel.'::'.$blnShippingExempt.'').'\2', $strBuffer);
						
					}
					
								
					return $newBuffer;
					
				}
				break;

			case 'mod_iso_cart':
				// VAT note in the main cart
				
				if(strpos($strBuffer,'noteVat')===false)
				{
					$strBuffer = str_replace('<div class="submit_container">',$this->isotopeGermanizeInsertTags('isotopeGerman::noteVat').'<div class="submit_container">',$strBuffer);
				}
				// shipping note in the main cart
				if(strpos($strBuffer,'noteShipping')===false)
				{
					$strBuffer = str_replace('<div class="submit_container">',$this->isotopeGermanizeInsertTags('isotopeGerman::noteShipping').'<div class="submit_container">',$strBuffer);
				}
				return $strBuffer;
				break;

			case 'iso_checkout_shipping_method':
			case 'iso_checkout_payment_method':
				// VAT note in the other checkout steps
				if(strpos($strBuffer,'noteVat')===false)
				{
					return $strBuffer.$this->isotopeGermanizeInsertTags('isotopeGerman::noteVat');
				}
				break;
			case 'iso_collection_mini':
			case 'iso_collection_default':
				// VAT note in the checkout product overview (has to be above the products/total)
				
//				var_dump(FrontendUser::getInstance()->groups);
//				echo '<pre>'.$strBuffer.'</pre>';
				
				if(strpos($strBuffer,'noteVat')===false)
				{
					return $this->isotopeGermanizeInsertTags('isotopeGerman::noteVat').$strBuffer;
				}
				break;

		}

		return $strBuffer;
	}


	/**
	 * Inject notes via insert tags
	 * @param string
	 * @return string
	 */
	public function isotopeGermanizeInsertTags($strTag)
	{
        // Use only if the shop is defined as German
        if (!IsotopeGermanize::isActive()) {
            return $strTag;
        }

		$arrTag = trimsplit('::', $strTag);
		//var_dump($arrTag );
		if($arrTag[0] == 'isotopeGerman')
		{
			switch($arrTag[1])
			{
				case 'noteShipping':
					return '<div class="noteShipping">'.self::getShippingNotice().'</div>';
					break;
				case 'notePricing':
					// [2]: # tax class, [3]: shipping exempt, [4]: txt for text version
					return '<div class="notePricing">'.$this->getPriceNotice($arrTag[2], $arrTag[3], $arrTag[4]).'</div>';
					break;
				case 'noteVat':
					return '<div class="noteVat">'.self::getTaxNotice().'</div>';
					break;
			}
		}

		return false;
	}



    /**
     * Return the shipping notice built from an article
     * @return string
     */
    protected function getShippingNotice()
    {
	    return Isotope\Isotope::getConfig()->shipping_note ? Controller::replaceInsertTags('{{insert_article::'.Isotope\Isotope::getConfig()->shipping_note.'}}') : false;
    }


    /**
     * Return a price notice to be displayed at a single product
     * @return string
     */
    protected function getPriceNotice($TaxClassLabel=false, $blnShippingExempt=false, $txt=false)
    {
		if ((FE_USER_LOGGED_IN === true && !self::isEuropeanUnion()) || (FE_USER_LOGGED_IN === true && !self::isGermany() && self::isEuropeanUnion() && self::hasValidVatNo())) {
			if (!$blnShippingExempt||$blnShippingExempt!=='true')
			{
				$strNote = $GLOBALS['TL_LANG']['iso_germanize']['priceNotes']['taxfree_shipping'];

				$strShippingLink = $this->getShippingLink();
				
				$note = $strShippingLink ? str_replace('<a>',$strShippingLink, $strNote) : str_replace('<a>','',str_replace('</a>','', $strNote));
			} else {
				$strNote = $GLOBALS['TL_LANG']['iso_germanize']['priceNotes']['taxfree_noShipping'];
			}
		} else {

			
			$strTax = ($strTax!==false &&$strTax!=='false')? $TaxClassLabel.' ' : '';

			if (!$blnShippingExempt||$blnShippingExempt!=='true')
			{
				$strNote = $GLOBALS['TL_LANG']['iso_germanize']['priceNotes']['tax_shipping'];

				$strShippingLink = $this->getShippingLink();
				
				
				$strNote = sprintf(($strShippingLink ? str_replace('<a>',$strShippingLink, $strNote) : str_replace('<a>','',str_replace('</a>','', $strNote))), $strTax);
				
			} else {

				$strNote = sprintf($GLOBALS['TL_LANG']['iso_germanize']['priceNotes']['tax_noShipping'], $strTax);
				
			}
			
		}

		// Optional parameter txt for text only
		if($txt)
		{
			$strNote = trim(strip_tags($strNote)).$txt;
		}
		
		return $strNote;
    }


   
    /**
     * Return a link to the shipping costs page
     * @return string
     */
    protected function getShippingLink()
    {
        global $objPage;
		
		if(Isotope\Isotope::getConfig()->shipping_page < 1)
		{
			return false;
		}

		// Build link to the shipping costs page
			
		
										
		$objTarget =  \PageModel::findByPk(Isotope\Isotope::getConfig()->shipping_page);
		
		 
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
	
			if (strncmp(Isotope\Isotope::getConfig()->shipping_rel, 'lightbox', 8) !== 0 || $objPage->outputFormat == 'xhtml')
			{
				$strLink .= ' rel="'. Isotope\Isotope::getConfig()->shipping_rel .'"';
			}
			else
			{
				$strLink .= ' data-lightbox="'. Isotope\Isotope::getConfig()->shipping_rel .'"';
			}
			
			if(Isotope\Isotope::getConfig()->shipping_target)
			{
				$strLink .= ($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"';
			}
	
			$strLink .= '>';
			
			return $strLink;
		}else {
			return false;
		}
	}


}
