<?php

namespace webvimark\modules\UserManagement;

use Yii;

class UserManagementModule extends \yii\base\Module
{
	const SESSION_LAST_ATTEMPT = '_um_last_attempt';
	const SESSION_ATTEMPT_COUNT = '_um_attempt_count';

	/**
	 * Permission that will be assigned automatically for everyone, so you can assign
	 * routes like "site/index" to this permission and those routes will be available for everyone
	 *
	 * Basically it's permission for guests (and of course for everyone else)
	 *
	 * @var string
	 */
	public $commonPermissionName = 'commonPermission';

	/**
	 * After how many seconds confirmation token will be invalid
	 *
	 * @var int
	 */
	public $confirmationTokenExpire = 3600; // 1 hour

	/**
	 * Roles that will be assigned to user registered via user-management/auth/registration
	 *
	 * @var array
	 */
	public $rolesAfterRegistration = [];

	/**
	 * Pattern that will be applied for names on registration.
	 * Default pattern allows user enter only numbers and letters.
	 *
	 * @var string
	 */
	public $registrationRegexp = '/^(\w|\d)+$/';

	/**
	 * Pattern that will be applied for names on registration. It contain regexp that should NOT be in username
	 * Default pattern doesn't allow anything having "admin"
	 *
	 * @var string
	 */
	public $registrationBlackRegexp = '/^(.)*admin(.)*$/i';

	/**
	 * How much attempts user can made to login or recover password in $attemptsTimeout seconds
	 *
	 * @var int
	 */
	public $maxAttempts = 5;

	/**
	 * Number of seconds after attempt counter to login or recover password will reset
	 *
	 * @var int
	 */
	public $attemptsTimeout = 60;

	/**
	 * Helps to check if translations have been registered already
	 *
	 * @var bool
	 */
	protected static $_translationsRegistered = false;

	public $controllerNamespace = 'webvimark\modules\UserManagement\controllers';

	/**
	 * For Menu
	 *
	 * @return array
	 */
	public static function menuItems()
	{
		return [
			['label' => '<i class="fa fa-angle-double-right"></i> ' . UserManagementModule::t('back', 'Users'), 'url' => ['/user-management/user/index']],
			['label' => '<i class="fa fa-angle-double-right"></i> ' . UserManagementModule::t('back', 'Roles'), 'url' => ['/user-management/role/index']],
			['label' => '<i class="fa fa-angle-double-right"></i> ' . UserManagementModule::t('back', 'Permissions'), 'url' => ['/user-management/permission/index']],
			['label' => '<i class="fa fa-angle-double-right"></i> ' . UserManagementModule::t('back', 'Permission groups'), 'url' => ['/user-management/auth-item-group/index']],
			['label' => '<i class="fa fa-angle-double-right"></i> ' . UserManagementModule::t('back', 'Visit log'), 'url' => ['/user-management/user-visit-log/index']],
		];
	}

	/**
	 * I18N helper
	 *
	 * @param string      $category
	 * @param string      $message
	 * @param array       $params
	 * @param null|string $language
	 *
	 * @return string
	 */
	public static function t($category, $message, $params = [], $language = null)
	{
		if ( !static::$_translationsRegistered )
		{
			Yii::$app->i18n->translations['modules/user-management/*'] = [
				'class'          => 'yii\i18n\PhpMessageSource',
				'sourceLanguage' => 'en',
				'basePath'       => '@vendor/webvimark/module-user-management/messages',
				'fileMap'        => [
					'modules/user-management/back' => 'back.php',
					'modules/user-management/front' => 'front.php',
				],
			];

			static::$_translationsRegistered = true;
		}

		return Yii::t('modules/user-management/' . $category, $message, $params, $language);
	}

	/**
	 * Check how much attempts user has been made in X seconds
	 *
	 * @return bool
	 */
	public function checkAttempts()
	{
		$lastAttempt = Yii::$app->session->get(static::SESSION_LAST_ATTEMPT);

		if ( $lastAttempt )
		{
			$attemptsCount = Yii::$app->session->get(static::SESSION_ATTEMPT_COUNT, 0);

			Yii::$app->session->set(static::SESSION_ATTEMPT_COUNT, ++$attemptsCount);

			// If last attempt was made more than X seconds ago then reset counters
			if ( ( $lastAttempt + $this->attemptsTimeout ) < time() )
			{
				Yii::$app->session->set(static::SESSION_LAST_ATTEMPT, time());
				Yii::$app->session->set(static::SESSION_ATTEMPT_COUNT, 1);

				return true;
			}
			elseif ( $attemptsCount > $this->maxAttempts )
			{
				return false;
			}

			return true;
		}

		Yii::$app->session->set(static::SESSION_LAST_ATTEMPT, time());
		Yii::$app->session->set(static::SESSION_ATTEMPT_COUNT, 1);

		return true;
	}
}
