<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * This controller is used by all versions
 *
 * @author    Serge <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

const _CONFIG_FILE_ = '../../../../config/config.inc.php';
const _CONFIG_FILE_DEV_ = '../../../../sites/bpost/sps14/config/config.inc.php';
const _CONTROLLER_FILE_ = 'controllers/front/servicepoint.php';

if (file_exists(_CONFIG_FILE_))
	require_once(_CONFIG_FILE_);
elseif (file_exists(_CONFIG_FILE_DEV_))
	require_once(_CONFIG_FILE_DEV_);
else
	die('Cannot locate config');

require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class ServicePointController extends FrontController
{
	public function __construct()
	{
		$this->is_ps14 = Tools::getValue('ps14');
		if (!isset($this->context) && !$this->is_ps14)
			$this->context = \Context::getContext();

	}

	public function run()
	{
		$this->init();
		//$this->preProcess();
		//$this->displayHeader();
		$this->process();
		//$this->displayContent();
		//$this->displayFooter();
	}

	public function init()
	{
		parent::init();

		if (!isset($this->context))
		{
			// $bpostshm = new BpostShm();
			new BpostShm();
			$this->context = Context::getContext();
		}
	}

	public function process()
	{
		parent::process();

		$shop_id = Tools::getValue('shop_id');
		if (!$this->is_ps14 && isset($shop_id))
			Shop::setContext(Shop::CONTEXT_SHOP, (int)$shop_id);

		$service = new Service($this->context);

		// Looking for AJAX requests
		if (Tools::getValue('get_available_countries'))
		{
			$this->checkToken(true);

			$available_countries = $service->getProductCountries();
			$this->terminate($available_countries);
		}
	}


	private function checkToken($admin = false)
	{
		$token_key = 'bpostshm';
		$token = Tools::getValue('token');
		$gen_token = $admin ? Tools::getAdminToken($token_key) : Tools::getToken($token_key);

		if (!isset($token) || $token !== $gen_token)
		{
			$this->errors[] = Tools::displayError('You do not have permission to view this.');
			$this->terminate( array( 'Error' => 'Permission denied' ) );
		}
	}

	/**
	 * @param mixed $content
	 */
	private function terminate($content)
	{
		if (is_array($content))
			$content = Tools::jsonEncode($content);
		header('Content-Type: application/json');
		die($content);
	}

}

$controller = new ServicePointController();
$controller->run();
