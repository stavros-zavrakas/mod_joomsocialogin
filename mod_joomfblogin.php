<?php
/**
 * Hello World! Module Entry Point
 * 
 * @package    Joomla.Tutorials
 * @subpackage Modules
 * @link http://dev.joomla.org/component/option,com_jd-wiki/Itemid,31/id,tutorials:modules/
 * @license        GNU/GPL, see LICENSE.php
 * mod_joomfblogin is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access');
// Include the syndicate functions only once
require_once(dirname(__FILE__).'/helper.php');
require_once(dirname(__FILE__).'/facebookHelper.php');
require_once(dirname(__FILE__).'/src/facebook.php');
 
$socialEnabled = array();
$socialEnabled['facebook'] = modJoomHelper::getParamName($params, 'fb_is_enabled');
$socialEnabled['google'] = modJoomHelper::getParamName($params, 'google_is_enabled');

// If we have at least on of the social logins enabled, we need the logic in the if statement.
if(in_array("1", $socialEnabled, true)) {
	$jInput = JFactory::getApplication()->input;
	$loginType = $jInput->get('type', null, 'STRING');
	$accessToken = $jInput->get('accessToken', null, 'STRING');

	$user = JFactory::getUser();
	$referer = modJoomHelper::getReferer();

	if(isset($socialEnabled['facebook'])) {
		$fbAppId = modJoomHelper::getParamName($params, 'fb_app_id');
		$fbAppSecret = modJoomHelper::getParamName($params, 'fb_app_secret');
		if($loginType == "facebook") {

			$facebook = new Facebook(array(
				'appId'  => $fbAppId,
				'secret' => $fbAppSecret,
				'allowSignedRequest' => false
			));

			if($accessToken) {
				$facebook->setAccessToken($accessToken);
			}

			$fbuser = $facebook->getUser();

			if ($fbuser && $accessToken && $user->guest)
			{
				
				try {
					$fbuser = $facebook->api('/me');

					$isJoomlaUser = modJoomHelper::getUserIdByParam('email', $fbuser['email']);

					if(empty($isJoomlaUser)) 
					{
						// Store the user object in the DB (register)
						jimport('joomla.user.helper');
						$password = JUserHelper::genRandomPassword(5);
						$joomlaUser = modJoomHelper::registerUser($fbuser['name'], $fbuser['username'], $password, $fbuser['email']);
					}
					else 
					{
						// Retrieve the user object from DB
						$joomlaUser = JFactory::getUser($isJoomlaUser);
					}
					// Login the User

					modJoomHelper::login($joomlaUser, $referer, $fbuser, $accessToken);
				}
				catch (FacebookApiException $e) {
					// error_log($e);
					$fbuser = null;
				}
			}
		}
		else 
		{
			$fbButton = modJoomFacebookLoginHelper::generateFacebookButton($params);
		}
		require(JModuleHelper::getLayoutPath('mod_joomfblogin'));
	}
}
?>