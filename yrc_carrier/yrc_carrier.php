<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_'))
	exit;

//
class yrc_carrier extends CarrierModule
{

	public  $id_carrier;
	private $_html = '';
	private $_postErrors = array();
	private $_moduleName = 'yrc_carrier';


	/*
	** Construct Method
	**
	*/

	public function __construct()
	{
		$this->name = 'yrc_carrier';
		$this->tab = 'shipping_logistics';
		$this->version = '1.0';
		$this->author = 'Faiz Khan';
		$this->limited_countries = array('fr', 'us');

		parent::__construct ();

		$this->displayName = $this->l('YRC Carrier');
		$this->description = $this->l('Offer your customers, different delivery methods that you want');

		if (self::isInstalled($this->name))
		{
			// Getting carrier list
			global $cookie;
			$carriers = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

			// Saving id carrier list
			$id_carrier_list = array();
			foreach($carriers as $carrier)
				$id_carrier_list[] .= $carrier['id_carrier'];

			// Testing if Carrier Id exists
			
		}
	}


	/*
	** Install / Uninstall Methods
	**
	*/

	public function install()
	{
		$carrierConfig = array(
			0 => array('name' => 'YRC Carrier Standard',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'YRC Carrier Standard Delivery', 'en' => 'YRC Worldwide Inc. is the holding company for brands including YRC, YRC Reimer, New Penn, USF Holland and USF Reddaway. YRC Worldwide has a comprehensive network in North America, and offers shipping of industrial, commercial and retail goods', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Reach in time'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'yrc_carrier',
				'need_range' => true
			)
			/*,
			1 => array('name' => 'YRC Carrier Standard Critical Time',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'YRC Carrier Standard Critical Time', 'en' => 'Description 2', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 2'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'yrc_carrier',
				'need_range' => true
			),*/
		);

		$id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
		//$id_carrier2 = $this->installExternalCarrier($carrierConfig[1]);
		Configuration::updateValue('YRS_STANDARD_CARRIER_ID', (int)$id_carrier1);
		//Configuration::updateValue('YRS_STANDARD_URG_CARRIER_ID', (int)$id_carrier2);
		if (!parent::install() ||
			!$this->registerHook('extraCarrier'))
			return false;
		return true;
	}
	
	public function uninstall()
	{
		// Uninstall
		if (!parent::uninstall() ||
			!$this->unregisterHook('extraCarrier'))
			return false;
		
		// Delete External Carrier
		$Carrier1 = new Carrier((int)(Configuration::get('YRS_STANDARD_CARRIER_ID')));
		//$Carrier2 = new Carrier((int)(Configuration::get('YRS_STANDARD_URG_CARRIER_ID')));

		// If external carrier is default set other one as default
		if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id) || Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier2->id))
		{
			global $cookie;
			$carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
			foreach($carriersD as $carrierD)
				if ($carrierD['active'] AND !$carrierD['deleted'] AND ($carrierD['name'] != $this->_config['name']))
					Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
		}

		// Then delete Carrier
		$Carrier1->deleted = 1;
		//$Carrier2->deleted = 1;
		//if (!$Carrier1->update() || !$Carrier2->update())
		if (!$Carrier1->update())
			return false;
		

		return true;
	}

	public static function installExternalCarrier($config)
	{
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
		$carrier->id_zone = $config['id_zone'];
		$carrier->active = $config['active'];
		$carrier->deleted = $config['deleted'];
		$carrier->delay = $config['delay'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->range_behavior = $config['range_behavior'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];

		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if ($language['iso_code'] == 'fr')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == 'en')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
		}

		if ($carrier->add())
		{
			$groups = Group::getGroups(true);
			foreach ($groups as $group)
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

			$rangePrice = new RangePrice();
			$rangePrice->id_carrier = $carrier->id;
			$rangePrice->delimiter1 = '0';
			$rangePrice->delimiter2 = '10000';
			$rangePrice->add();

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000';
			$rangeWeight->add();

			$zones = Zone::getZones(true);
			foreach ($zones as $zone)
			{
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_zone', array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($zone['id_zone'])), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => NULL, 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => NULL, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
			}

			// Copy Logo
			if (!copy(dirname(__FILE__).'/carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
				return false;

			// Return ID Carrier
			return (int)($carrier->id);
		}

		return false;
	}
	
	
	
	
	private function xmlRequest($in)
	{
	
	
	    $url="https://my.yrc.com/dynamic/national/servlet?CONTROLLER=com.rdwy.ec.rexcommon.proxy.http.controller.ProxyApiController&redir=/tfq561&LOGIN_USERID=".Configuration::get('LOGIN_USERID')."&LOGIN_PASSWORD=".Configuration::get('LOGIN_PASSWORD')."&BusId=".Configuration::get('BusId')."&BusRole=".Configuration::get('BusRole')."&PaymentTerms=".Configuration::get('PaymentTerms')."&OrigCityName=".Configuration::get('OrigCityName')."&OrigStateCode=".Configuration::get('OrigStateCode')."&OrigZipCode=".Configuration::get('OrigZipCode')."&OrigNationCode=".Configuration::get('OrigNationCode')."&DestCityName=".$in['DestCityName']."&DestStateCode=".$in['DestStateCode']."&DestZipCode=".$in['DestZipCode']."&DestNationCode=USA&ServiceClass=STD&PickupDate=".$in['PickupDate']."&TypeQuery=QUOTE&LineItemWeight1=".$in['LineItemWeight1']."&LineItemNmfcClass1=150&LineItemCount=1&AccOption1=NTFY&AccOptionCount=1";

//echo $url;

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
$result = curl_exec($curl);

$xml = simplexml_load_string($result);
$status=$xml->PageRoot;

$price=(int)$xml->BodyMain->RateQuote->RatedCharges->TotalCharges;


if($price>0 && $in['LineItemWeight1']>50)
{
	$price=70+($price/100);
}
else
{
  $price=30;
}

 return $price;
}
	
	


	
	public function getContent()
	{
		$this->_html .= '<h2>' . $this->l('YRC Carrier').'</h2>';
		if (!empty($_POST) AND Tools::isSubmit('submitSave'))
		{
			
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error"><img src="'._PS_IMG_.'admin/forbbiden.gif" alt="nok" />&nbsp;'.$err.'</div>';
		}
		$this->_displayForm();
		return $this->_html;
	}

	private function _displayForm()
	{
		$this->_html .= '<fieldset>
		<legend><img src="'.$this->_path.'logo.gif" alt="" /> '.$this->l('My Carrier Module Status').'</legend>';

		$option_type=Configuration::get('option_type_yrc');
		
		
		if($option_type=="W")
		{
			$wselected="checked";
		}
		else
		{
			$whwselected="checked";
		}

		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>
			<style>
				#tabList { clear: left; }
				.tabItem { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }
			</style>
			<div id="tabList">
				<div class="tabItem">
					<form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=1&section=general" method="post" class="form" id="configForm">

					<fieldset style="border: 0px;">
						<h4>'.$this->l('General configuration').' :</h4>
						
						<label>'.$this->l('LOGIN USERID').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="LOGIN_USERID" value="'.Tools::getValue('LOGIN_USERID', Configuration::get('LOGIN_USERID')).'" />
						</div>

  
 
 <label>'.$this->l('LOGIN_PASSWORD').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="LOGIN_PASSWORD" value="'.Tools::getValue('LOGIN_PASSWORD', Configuration::get('LOGIN_PASSWORD')).'" />
						</div>
						 
 
 <label>'.$this->l('BusRole').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="BusRole" value="'.Tools::getValue('BusRole', Configuration::get('BusRole')).'" />
						</div>
						 
 
 <label>'.$this->l('BusId').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="BusId" value="'.Tools::getValue('BusId', Configuration::get('BusId')).'" />
						</div>
						 
 
 
 <label>'.$this->l('PaymentTerms').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="PaymentTerms" value="'.Tools::getValue('PaymentTerms', Configuration::get('PaymentTerms')).'" />
						</div>
						 
 
 <label>'.$this->l('OrigCityName').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="OrigCityName" value="'.Tools::getValue('OrigCityName', Configuration::get('OrigCityName')).'" />
						</div>
						 
 
 <label>'.$this->l('OrigStateCode').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="OrigStateCode" value="'.Tools::getValue('OrigStateCode', Configuration::get('OrigStateCode')).'" />
						</div>
						 
 
 <label>'.$this->l('OrigZipCode').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="OrigZipCode" value="'.Tools::getValue('OrigZipCode', Configuration::get('OrigZipCode')).'" />
						</div>
						
						 <label>'.$this->l('OrigNationCode').' : </label>
						<div class="margin-form">
						<input type="text" size="20" name="OrigNationCode" value="'.Tools::getValue('OrigNationCode', Configuration::get('OrigNationCode')).'" />
						</div>';
						
				/*<label>'.$this->l('Option selected').' : </label>
						<div class="margin-form">
						<input type="radio" name="option_type_yrc" value="W" '.$wselected.'/> Standard(Weight only)
						<input type="radio" name="option_type_yrc" value="WHW" '.$whwselected.'/> All(Weight+height+width)
						</div>*/
						
				$this->_html .='</div>
					<br /><br />
				</fieldset>				
				<div class="margin-form"><input class="button" name="submitSave" type="submit"></div>
			</form>
		</div></div>';
	}

	

	private function _postProcess()
	{
	
			foreach($_POST as $keys=>$values)
			{
				
				Configuration::updateValue($keys, $values);
				
			}
	

	}


	



	/*
	** Front Methods
	**
	** If you set need_range at true when you created your carrier (in install method), the method called by the cart will be getOrderShippingCost
	** If not, the method called will be getOrderShippingCostExternal
	**
	** $params var contains the cart, the customer, the address
	** $shipping_cost var contains the price calculated by the range in carrier tab
	**
	*/
	
	public function getOrderShippingCost($params, $shipping_cost)
	{
	 
	    $address = new Address($this->context->cart->id_address_delivery);
		$id_zone = Address::getZoneById((int)($address->id));
		
		$sql="SELECT iso_code
		FROM `"._DB_PREFIX_."state`
		WHERE `id_state` ='".$address->id_state."'";
				
		$statcode = Db::getInstance()->getRow($sql);
         
		
		 $cart = Context::getContext()->cart;
		 
		
	
		 $yrcInfo=array();		

		$yrcInfo['DestCityName']=$address->city;
	    $yrcInfo['DestStateCode']=$statcode['iso_code'];
		$yrcInfo['DestZipCode']=$address->postcode;
		$yrcInfo['DestNationCode']='USA';
		$yrcInfo['PickupDate']=date('Ymd');	

		$products = $cart->getProducts();
		
		$support_weight=0;
		foreach($products as $product)
		{
		   $sql=DB::getInstance()->getRow("select main_product from "._DB_PREFIX_."supportpro where main_product=".$product['id_product']);
		   
		   if(is_array($sql))
		   {
		     $support_weight=Product::getWeightGroupProduct($product['id_product'],$cart->id);
		   }
		   
		}
		
		$weights=$cart->getTotalWeight()+$support_weight;
		
		$yrcInfo['LineItemWeight1']=round($weights,0);
		
		
		
	
	  $price=$this->xmlRequest($yrcInfo);
	
		
		
		if($price>0)		
			return $price;
			
		

		return false;
	}
	
	public function getOrderShippingCostExternal($params)
	{
		
		if ($this->id_carrier == (int)(Configuration::get('YRS_STANDARD_CARRIER_ID')))
			$price=$this->xmlRequest($yrcInfo);
		
		if($price>0)		
			return $price;
			
		

		return false;
	}
	
	
	
	/**
	 * Get a carrier list liable to the module
	 *
	 * @return array
	 */
	public function getYrcCarriers()
	{

		
		
		
		$query = 'SELECT c.id_carrier, c.range_behavior, cl.delay FROM `'._DB_PREFIX_.'carrier` c LEFT JOIN `'._DB_PREFIX_.'carrier_lang` cl ON c.`id_carrier` = cl.`id_carrier` WHERE  c.`deleted` = 0	AND cl.`id_shop` = 1 AND cl.id_lang = '.$this->context->language->id .' AND c.`active` = 1	AND c.id_carrier IN ('.Configuration::get('YRS_STANDARD_URG_CARRIER_ID').','.Configuration::get('YRS_STANDARD_CARRIER_ID').')';


		$carriers = Db::getInstance()->executeS($query);

		if (!is_array($carriers))
			$carriers = array();
		return $carriers;
	}

	
	
	public function hookExtraCarrier($params)
	{
	   
	   return $this->display(__FILE__, 'help.tpl');
	  
	}
	
	
	
	
}


