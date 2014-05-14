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
class ValidateDATA extends Module
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
     * Return true if a vat-no exists
     * @return bool
     */
    public static function hasVatNo()
    {
        return Isotope\Isotope::getCart()->shippingAddress->vat_no != '';
    }


    /**
     * Return true if a valid vat-no exists
     * @return bool
     */
    public static function hasValidVatNo()
    {
		// in test mode we always have a valif vat no
		if($GLOBALS['isotope_germanize']['testmode'])
		{
	        return true;
		}
		
        if (!self::hasVatNo() 
        	|| (FE_USER_LOGGED_IN !== true && !Isotope\Isotope::getConfig()->vatcheck_guests)
        	|| (Isotope\Isotope::getCart()->shippingAddress->vat_no_ok != 'ok_qualified' && Isotope\Isotope::getCart()->shippingAddress->vat_no_ok != 'ok_manual')) {
            return false;
        }

        return true;
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
	 * Check vat no and update the status
	 */
	public function updateVatCheck()
    {
		// Set the data to check as it is not yet stored in the object
		$addrType = (Isotope\Isotope::getConfig()->shippingAddress_id != -1 ? 'shipping_address' : 'billing_address');

		$this->arrCheckData = array(
			'addr_type'  => $addrType,
			'company'    => Input::getInstance()->post($addrType.'_company'),
			'street'     => Input::getInstance()->post($addrType.'_street_1'),
			'postal'     => Input::getInstance()->post($addrType.'_postal'),
			'city'       => Input::getInstance()->post($addrType.'_city'),
			'country'    => Input::getInstance()->post($addrType.'_country'),
			'vat_no'     => Input::getInstance()->post($addrType.'_vat_no')
			);

    	// check the vat no
		if($arrCheck = $this->checkVatNo())
		{
			// update member data
			$this->updateMember($arrCheck['status']);

			// mails for the vendor
			$this->sendMails($arrCheck);

			// log the action
			$this->logIt($arrCheck['status']);

			// update the session data
			Input::getInstance()->setPost($this->arrCheckData['addr_type'].'_vat_no_ok',$arrCheck['status']);
		}
	}


    /**
     * Return array with results of the online check
     * @return array
     */
    protected function checkVatNo()
    {
		if(!Isotope\Isotope::getConfig()->vat_no
			|| self::isGermany()
			|| !self::isEuropeanUnion()
			|| (!FE_USER_LOGGED_IN && !Isotope\Isotope::getConfig()->vatcheck_guests)
			|| (FE_USER_LOGGED_IN && !Isotope\Isotope::getConfig()->vatcheck_member)
			|| !$this->addressHasbeenModified()
			)
		{
			return false;
		}

		// Formal check vat_no
	   	if(!$strCustomerVatNo = self::preCheckVatNo($this->arrCheckData['vat_no']))
		{
			return array(
				'status' => 'nok',
				'error'  => 'vat_no'
				);
		}

		// Formal check own vat_no
	   	if(!$strOwnVatNo = self::preCheckVatNo(Isotope\Isotope::getConfig()->vat_no))
	   	{
			return array(
				'status' => 'nok_invalid',
				'error'  => 'own_vat_no'
				);
	   	}

		// Server is online
		if(!self::testConnection())
		{
			return array(
				'status' => 'nok_invalid',
				'error'  => 'server'
				);
		}

		// Verify the vat no online
		$arrCheck = $this->verifyVatNo($strCustomerVatNo, $strOwnVatNo);

		// don't activate if no member groups shall be auto-activated
		$arrCheck['status'] = ($arrCheck['status'] == 'ok_qualified' && FE_USER_LOGGED_IN && count(array_intersect(Isotope\Isotope::getConfig()->vatcheck_groups, FrontendUser::getInstance()->groups)) < 1) ? 'nok_qualified' : $arrCheck['status'];

		return $arrCheck;
	}


    /**
     * Verify the vat no online
     * @return array
     */
    protected function verifyVatNo($strCustomerVatNo, $strOwnVatNo)
    {
		require_once(TL_ROOT.'/system/modules/isotope_germanize/IXR_Library.php');

    	$client = new IXR_Client('https://'.self::$strHost);
		$UstId_1    = strtoupper($strOwnVatNo);
		$UstId_2    = strtoupper($strCustomerVatNo);
		$Firmenname = $this->arrCheckData['company'];
		$Ort        = $this->arrCheckData['city'];
		$PLZ        = $this->arrCheckData['postal'];
		$Strasse    = $this->arrCheckData['street'];
		$Druck      = $GLOBALS['isotope_germanize']['order_printed_verification'] ? 'ja' : 'nein';

		if (!$client->query('evatrRPC',
			$UstId_1,
			$UstId_2,
			$Firmenname,
			$Ort,
			$PLZ,
			$Strasse,
			$Druck))
		{
			return array(
				'status'     => 'nok_invalid',
				'error'      => 'server',
				'check_code' => $client->getErrorCode().': '.$client->getErrorMessage()
				);
		}

		$xml = $client->getResponse();

		preg_match('#(?<=ErrorCode</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrErrorCode);
		preg_match('#(?<=Datum</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrDatum);
		preg_match('#(?<=Uhrzeit</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrUhrzeit);
		preg_match('#(?<=Erg_Name</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrErg_Name);
		preg_match('#(?<=Erg_Str</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrErg_Str);
		preg_match('#(?<=Erg_PLZ</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrErg_PLZ);
		preg_match('#(?<=Erg_Ort</string></value>\n<value><string>).*(?=</string.*)#', $xml, $arrErg_Ort);

		$arrResponse = array(
			'check_date'    => $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrDatum[0]],
			'check_time'    => $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrUhrzeit[0]],
			'check_company' => $arrErg_Name[0] ? $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrErg_Name[0]] : '',
			'check_street'  => $arrErg_Str[0] ? $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrErg_Str[0]] : '',
			'check_postal'  => $arrErg_PLZ[0] ? $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrErg_PLZ[0]] : '',
			'check_city'    => $arrErg_Ort[0] ? $GLOBALS['TL_LANG']['iso_germanize']['bff'][$arrErg_Ort[0]] : '',
			'check_code'    => $arrErrorCode[0]
		);

		switch($arrErrorCode[0])
		{
			// ok
			case '200':
				if(($arrErg_Name[0] != 'A' && $arrErg_Name[0] != 'D')
					|| ($arrErg_Str[0] != 'A' && $arrErg_Str[0] != 'D' && !$GLOBALS['isotope_germanize']['loose_verification_street'])
					|| ($arrErg_PLZ[0] != 'A' && $arrErg_PLZ[0] != 'D' && !$GLOBALS['isotope_germanize']['loose_verification_postal'])
					|| ($arrErg_Ort[0] != 'A' && $arrErg_Ort[0] != 'D')
					)
				{
					// the vat no doesn't fit the address
					return array_merge($arrResponse, array(
						'status'        => 'nok_invalid',
						'error'         => 'general'
						));
				}

				// everything is fine
				return array_merge($arrResponse, array(
					'status'        => 'ok_qualified'
					));
				break;

			// only simple verification available
			case '216':
			case '218':
			case '219':
				return array_merge($arrResponse, array(
					'status'        => 'nok_simple',
					'error'         => 'general'
					));
				break;

			// (technical) error
			default:
				return array_merge($arrResponse, array(
					'status'        => 'nok_invalid',
					'error'         => 'general'
					));
				break;
		}
	}


    /**
     * Test connection to authority server (is only available 5:00 - 22:00
     * @return array
     */
    public static function testConnection()
    {
    	return is_array(gethostbynamel(self::$strHost)) ? true : false;
	}


    /**
     * Log the action 
     */
    protected function logIt($strStatus)
    {
		switch($strStatus)
		{
			case 'ok_qualified':
			case 'nok_qualified':
			case 'nok_simple':
				$this->log('VAT-ID '.$this->arrCheckData['vat_no'].' ('.(FE_USER_LOGGED_IN ? 'User '.FrontendUser::getInstance()->id : 'Guest').'): '.$GLOBALS['TL_LANG']['iso_germanize'][$strStatus],'updateVatCheck','VAT-CHECK');
				break;
			default:
				$this->log('VAT-ID '.$this->arrCheckData['vat_no'].' ('.(FE_USER_LOGGED_IN ? 'User '.FrontendUser::getInstance()->id : 'Guest').'): '.$GLOBALS['TL_LANG']['iso_germanize'][$strStatus],'updateVatCheck','ERROR');
				break;
		}
	}


    /**
     * Update the member data if address and vat_no match the cart version 
     */
    protected function updateMember($strStatus)
    {
    	if(!FE_USER_LOGGED_IN)
    	{
    		return false;
    	}

		$arrMember = array(
			FrontendUser::getInstance()->company,
			FrontendUser::getInstance()->street,
			FrontendUser::getInstance()->postal,
			FrontendUser::getInstance()->city,
			FrontendUser::getInstance()->country
			);
		
		$arrAddress = array(
			$this->arrCheckData['company'],
			$this->arrCheckData['street'],
			$this->arrCheckData['postal'],
			$this->arrCheckData['city'],
			$this->arrCheckData['country']
			);

    	if($arrMember != $arrAddress)
    	{
    		return false;
    	}

		Database::getInstance()->prepare("UPDATE tl_member SET vat_no=?, vat_no_ok=? WHERE id=?")
			->executeUncached($this->arrCheckData['vat_no'], $strStatus, FrontendUser::getInstance()->id);

   		return true;
	}


    /**
     * Confirmation mails
     */
    protected function sendMails($arrCheck)
    {
    	$arrCountries = $this->getCountries();

		$arrMailfields = array(
			'host'          => self::$strHost,
			'status'        => $GLOBALS['TL_LANG']['iso_germanize'][$arrCheck['status']],
			'error'         => $GLOBALS['TL_LANG']['iso_germanize']['error'][$arrCheck['error']],
			'vat_no'        => $this->arrCheckData['vat_no'],
			'date'          => date($GLOBALS['TL_CONFIG']['datimFormat'],time()),
			'company'       => $this->arrCheckData['company'],
			'street'        => $this->arrCheckData['street'],
			'postal'        => $this->arrCheckData['postal'],
			'city'          => $this->arrCheckData['city'],
			'country'       => $arrCountries[$this->arrCheckData['country']],
			'member_id'     => (FE_USER_LOGGED_IN ? FrontendUser::getInstance()->id : $GLOBALS['TL_LANG']['iso_germanize']['guest_order']),
			'address_id'    => (Isotope\Isotope::getCart()->shippingAddress_id > 0 ? Isotope\Isotope::getCart()->shippingAddress_id : ''),
			'check_date'    => $arrCheck['check_date'],
			'check_time'    => $arrCheck['check_time'],
			'check_company' => $arrCheck['check_company'],
			'check_street'  => $arrCheck['check_street'],
			'check_postal'  => $arrCheck['check_postal'],
			'check_city'    => $arrCheck['check_city'],
			'check_code'    => $arrCheck['check_code']
			);

		if($arrCheck['status'] == 'ok_qualified' || $arrCheck['status'] == 'nok_qualified')
		{
			$arrMailfields['inactive'] = $arrCheck['status'] == 'nok_qualified' ? $GLOBALS['TL_LANG']['iso_germanize']['inactive'] : '';

			$objEmail          = new Email();
			$objEmail->subject = $GLOBALS['TL_LANG']['iso_germanize']['mail_verfication_subject'];
			$objEmail->text    = $GLOBALS['TL_LANG']['iso_germanize']['mail_verfication_text'];

			foreach($arrMailfields as $k=>$v)
			{
				$objEmail->subject = str_replace('##'.$k.'##', $v, $objEmail->subject);
				$objEmail->text    = str_replace('##'.$k.'##', $v, $objEmail->text);
			}

			$objEmail->sendTo(Isotope\Isotope::getConfig()->email);
		}
		else
		{
			$objEmail = new Email();

			// Reminding e-mail to the vendor for manual cheack after salte, only for members
			if(1==1 || FE_USER_LOGGED_IN)
			{
				$objEmail->subject = $GLOBALS['TL_LANG']['iso_germanize']['mail_reminder_subject'];
				$objEmail->text    = $GLOBALS['TL_LANG']['iso_germanize']['mail_reminder_text'];

				$objEmail->subject = $GLOBALS['TL_LANG']['iso_germanize']['mail_reminder_subject'];
				$objEmail->text    = $GLOBALS['TL_LANG']['iso_germanize']['mail_reminder_text'];

				foreach($arrMailfields as $k=>$v)
				{
					$objEmail->subject = str_replace('##'.$k.'##', $v, $objEmail->subject);
					$objEmail->text    = str_replace('##'.$k.'##', $v, $objEmail->text);
				}

				$objEmail->sendTo(Isotope\Isotope::getConfig()->email);
			}
		}
	}


    /**
     * Return array with country-code and formally checked number
     * @return array
     */
    public static function preCheckVatNo($strVatNo)
    {
    	$strVatNo = str_replace(array(
			chr(0),
			chr(9),
			chr(10),
			chr(11),
			chr(13),
			chr(23),
			chr(92),
			' ',
			'.',
			'-',
			'_',
			'/',
			'>',
			'<',
			','
			), '', $strVatNo);

		if(strlen($strVatNo) < 8 || strlen($strVatNo) > 12)
		{
			return false;
		}

		return $strVatNo;
	}


    /**
     * Return true if an relevant address has been modified
     * @return bool
     */
    protected function addressHasbeenModified()
    {
    	if(Input::getInstance()->post('FORM_SUBMIT') != 'iso_mod_checkout_address')
    	{
    		return false;
    	}

		foreach(array('vat_no','company','street_1','postal','city','country') as $strRelevant)
		{
			if(Input::getInstance()->post($this->arrCheckData['addr_type'].'_'.$strRelevant) && Input::getInstance()->post($this->arrCheckData['addr_type'].'_'.$strRelevant) != Isotope\Isotope::getCart()->shippingAddress->$strRelevant)
			{
				$hasChanged = true;
			}
		}
		
		return $hasChanged;
    }
    
    /**
	 * Inject data via mail tags
	 * @param string
	 * @return string
	 */
	public function isotopeGermanizeOrderEmailData(IsotopeOrder $objOrder, $arrData)
	{
		return array_merge($arrData, array
		(
			'germ_shipping_note'		=> Isotope\Isotope::getConfig()->shipping_note,
			'germ_shipping_page'		=> $this->getShippingLink(),
			'germ_vat_no_ok'			=> Isotope\Isotope::getCart()->shippingAddress->vat_no_ok,
			'germ_vat_no'				=> $this->arrCheckData['vat_no'],
		));
	}

}
