<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/*
** Some tools using used in the module
*/
class YRCtools
{
	
	
	public static function xmlRequest($in)
	{
	

	    $url="https://my.yrc.com/dynamic/national/servlet?CONTROLLER=com.rdwy.ec.rexcommon.proxy.http.controller.ProxyApiController&redir=/tfq561&LOGIN_USERID=".Configuration::get('LOGIN_USERID')."&LOGIN_PASSWORD=".Configuration::get('LOGIN_PASSWORD')."&BusId=".Configuration::get('BusId')."&BusRole=".Configuration::get('BusRole')."&PaymentTerms=".Configuration::get('PaymentTerms')."&OrigCityName=".Configuration::get('OrigCityName')."&OrigStateCode=".Configuration::get('OrigStateCode')."&OrigZipCode=".Configuration::get('OrigZipCode')."&OrigNationCode=".Configuration::get('OrigNationCode')."&DestCityName=".$in['DestCityName']."&DestStateCode=".$in['DestStateCode']."&DestZipCode=".$in['DestZipCode']."&DestNationCode=USA&ServiceClass=STD&PickupDate=".$in['PickupDate']."&TypeQuery=QUOTE&LineItemWeight1=".$in['LineItemWeight1']."&LineItemNmfcClass1=50&LineItemCount=1&AccOption1=NTFY&AccOptionCount=1";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
$result = curl_exec($curl);



	  return 100;
	}
	
	
	public static function xmlRequestUrge($zipcode)
	{
	
	  return 300;
	}
}

?>
