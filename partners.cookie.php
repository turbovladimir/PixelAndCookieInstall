<?php
/*
 Make sure this code is included at any page of your site before any output is started.
 The good way is to place it in the beginning of your index.php
*/

$postbacksSettingsByPartner = [
	'cityads' => [ // contact cityads's integration team to configure this part
		'pixel-url' => 'https://hskwq.com/service/newtrack/{orderId}/ct/{targetCode}/c/{campaignId}?click_id={clickId}',
		'postback-url' => 'https://cityads.ru/service/postback?order_id={orderId}&status={status}&click_id={clickId}',
	], 
	/*'anotherPartner' => [
		'pixel-url' => 'anotherUrl',
		'placeholders' => [
			// contact partner's integration team to configure this part
		]
	]*/
];
$cpaCounter = new CPACounter($postbacksSettingsByPartner);
$cpaCounter->setPartnersCookies();

class CPACounter {
	
	
	private static $postbacksSettingsByPartner;
	
	public function __construct($postbacksSettingsByPartner = null) {
		if (isset($postbacksSettingsByPartner) && !isset(self::$postbacksSettingsByPartner)) {
			self::$postbacksSettingsByPartner = $postbacksSettingsByPartner;
		} else {
			trigger_error('CPACounter should be firstly instanced with  postbacksSettingsByPartner argument, then it musql be used with no argument.', E_ERROR);
		}
	}
	
	public function setPartnersCookies($expirationDays = 120) {
		if (!isset($_GET['utm_source'])) {
			return;
		}
		
		foreach (array_keys(self::$postbacksSettingsByPartner) as $partnerName) {
			if ($_GET['utm_source'] === $partnerName) {
				ob_start();
				setcookie($partnerName . 'ClickId', isset($_GET['utm_source']) ? $_GET['utm_source'] : '', time() + 86400 * $expirationDays, '/');
				ob_end_flush();
			}
		}
	}

	public function getPixels($orderId, $targetCode, $partnerNames = ['cityads']) {
		
		if (!isset($partnerNames)) {
			$partnerNames = array_keys(self::$postbacksSettingsByPartner);
		}
		
		$urls = [];
		foreach ($partnerNames as $partnerName) {
			if (!isset($_COOKIE[$partnerName . 'ClickId'])) {
				continue;
			}
			
			$placeholders = [
				'{orderId}' => urlencode($orderId),
				'{targetCode}' => $targetCode,
				'{campaignId}' => $campaignId,
				'{clickId}' => $_COOKIE[$partnerName . 'ClickId'],
			];
					
			
			$urls[] = '<img src="' . str_replace(
				array_keys($placeholders), 
				$placeholders, 
				self::$postbacksSettingsByPartner[$partnerName]['pixel-url']
				. '" style="display:none">'
			);
		}
		
		return $urls ?: null;
	}
	
	public function sendPostback($orderId, $status, $partnerNames = ['cityads']) {
		if (!isset($partnerNames)) {
			$partnerNames = array_keys(self::$postbacksSettingsByPartner);
		}
		
		foreach ($partnerNames as $partnerName) {
			if (!isset($_COOKIE[$partnerName . 'ClickId'])) {
				continue;
			}
			
			$placeholders = [
				'{orderId}' => urlencode($orderId),
				'{status}' => $status,
				'{clickId}' => $_COOKIE[$partnerName . 'ClickId'],
			];
			
			$url = str_replace(
				array_keys($placeholders),
				$placeholders, 
				self::$postbacksSettingsByPartner[$partnerName]['postback-url']
			);
			var_dump($url);
			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_close($ch);
			} else {
				file_get_contents($url) || `wget "$url"`;
			}
		}
		
		return null;
	}

	
	
}

/**********************************************************************************/

/*
  Place this code after the order is successfully done. Postback will be sent to the CPA partner whose cookie were installed before;
*/
$cpaCounter = new CPACounter();
$cpaCounter->sendPostback($orderId, 'open'); // $orderId is an ID of the order in your system. Status is 'open' when order is just created. Contact integration team to talk about approving commission.


/*
  Or place this code after the order is successfully done
*/
$cpaCounter = new CPACounter();
$pixels = $cpaCounter->getPixels($orderId, $targetCode); // $orderId is an ID of the order in your system. Contact integration team to talk about $targetCode
// Next you should give these $pixel to your web page and draw them. It very depends on your framework/CMS



var_dump($_COOKIE, $_GET, $pixels);
