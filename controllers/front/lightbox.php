<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class BpostShmLightboxModuleFrontController extends ModuleFrontController
{
	const GEO6_PARTNER = 999999;
	const GEO6_APP_ID = '';

	public function initContent()
	{
		$shipping_method = Tools::getValue('shipping_method');
		$token = Tools::getValue('token');

		if (!is_numeric($shipping_method) || $token != Tools::getToken('bpostshm'))
		{
			Tools::redirect('/');
			return;
		}

		parent::initContent();

		$service = new Service($this->context);

		// Reset selected bpost service point
		$this->context->cart->service_point_id = 0;
		$this->context->cart->update();

		// Looking for AJAX requests
		if (Tools::getValue('get_nearest_service_points'))
		{
			$search_params = array('zone' => '',);
			$postcode = Tools::getValue('postcode');
			$city = Tools::getValue('city');
			if ($postcode)
				$search_params['zone'] .= (int)$postcode.($city ? ' ' : '');
			if ($city)
				$search_params['zone'] .= (string)$city;

			$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
			$this->jsonEncode($service_points);
		}
		elseif (Tools::getValue('get_service_point_hours') && $service_point_id = (int)Tools::getValue('service_point_id'))
		{
			$service_point_hours = $service->getServicePointHours($service_point_id, $shipping_method);
			$this->jsonEncode($service_point_hours);
		}
		elseif (Tools::getValue('set_service_point') && $service_point_id = (int)Tools::getValue('service_point_id'))
		{
			$this->context->cart->service_point_id = $service_point_id;
			$this->jsonEncode($this->context->cart->update());
		}
		elseif (Tools::getValue('post_bpack247_register'))
		{
			$params = array();
			if ($id_gender = (int)Tools::getValue('id_gender'))
			{
				$gender = new Gender($id_gender, $this->context->language->id, $this->context->shop->id);
				if ($gender->type)
					$params['title'] = 'Ms.';
				else
					$params['title'] = 'Mr.';
			}
			if ($firstname = (string)Tools::getValue('firstname'))
				$params['firstname'] = $firstname;
			if ($lastname = (string)Tools::getValue('lastname'))
				$params['lastname'] = $lastname;
			if ($street = (string)Tools::getValue('street'))
				$params['street'] = $street;
			if ($nr = (int)Tools::getValue('number'))
				$params['number'] = $nr;
			if ($postal_code = (int)Tools::getValue('postal_code'))
				$params['postal_code'] = $postal_code;
			if ($town = (string)Tools::getValue('town'))
				$params['town'] = $town;
			if ($date_of_birth = (string)Tools::getValue('date_of_birth'))
				$params['date_of_birth'] = $date_of_birth;
			if ($email = (string)Tools::getValue('email'))
				$params['email'] = Tools::strtoupper($email);
			if ($mobile_number = (string)Tools::getValue('mobile_number'))
				// int cast removes leading zero
				$params['mobile_number'] = (int)$mobile_number;
			if ($preferred_language = (string)Tools::getValue('preferred_language'))
				$params['preferred_language'] = $preferred_language;

			$customer = $service->makerBpack247Customer($params);
			exit();
			//$this->context->cart->bpack247_customer = serialize($params);
			//return $this->context->cart->update();
		}

		// Building display page
		self::$smarty->assign('version', (Service::isPrestashop16() ? 1.6 : (Service::isPrestashopFresherThan14() ? 1.5 : 1.4)), true);

		switch ($shipping_method)
		{
			case BpostShm::SHIPPING_METHOD_AT_SHOP:
				self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
				self::$smarty->assign('shipping_method', $shipping_method, true);

				$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);

				self::$smarty->assign('city', $delivery_address->city, true);
				self::$smarty->assign('postcode', $delivery_address->postcode, true);

				$search_params = array(
					'street' 	=> '',
					'nr' 		=> '',
					'zone'		=> $delivery_address->postcode.' '.$delivery_address->city,
				);
				$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
				self::$smarty->assign('servicePoints', $service_points, true);

				self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'							=> true,
					'get_nearest_service_points' 	=> true,
					'shipping_method'				=> $shipping_method,
					'token'							=> Tools::getToken('bpostshm'),
				)));
				self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'						=> true,
					'get_service_point_hours' 	=> true,
					'shipping_method'			=> $shipping_method,
					'token'						=> Tools::getToken('bpostshm'),
				)));
				self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'				=> true,
					'set_service_point' => true,
					'shipping_method'	=> $shipping_method,
					'token'				=> Tools::getToken('bpostshm'),
				)));

				$this->addJqueryPlugin('scrollTo');
				$this->setTemplate('lightbox-point-list.tpl');
				break;

			case BpostShm::SHIPPING_METHOD_AT_24_7:
				$step = (int)Tools::getValue('step', 1);
				switch ($step)
				{
					default:
					case 1:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);
						self::$smarty->assign('step', 1, true);

						$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);

						self::$smarty->assign('gender', $this->context->customer->id_gender);
						self::$smarty->assign('genders', Gender::getGenders($this->context->language->id));
						self::$smarty->assign('firstname', $delivery_address->firstname, true);
						self::$smarty->assign('lastname', $delivery_address->lastname, true);

						preg_match('#([0-9]+)?[, ]*([a-zA-Z ]+)[, ]*([0-9]+)?#i', $delivery_address->address1, $matches);
						if (!empty($matches[1]) && is_numeric($matches[1]))
							$nr = $matches[1];
						elseif (!empty($matches[3]) && is_numeric($matches[3]))
							$nr = $matches[3];
						else
							$nr = (!empty($delivery_address->address2) && is_numeric($delivery_address->address2) ? $delivery_address->address2 : '');
						$street = !empty($matches[2]) ? $matches[2] : $delivery_address->address1;

						self::$smarty->assign('street', $street, true);
						self::$smarty->assign('number', $nr, true);

						self::$smarty->assign('postal_code', $delivery_address->postcode, true);
						self::$smarty->assign('locality', $delivery_address->city, true);
						self::$smarty->assign('birthday',
							'0000-00-00' != $this->context->customer->birthday ? $this->context->customer->birthday : '', true);
						self::$smarty->assign('email', $this->context->customer->email, true);
						self::$smarty->assign('mobile_phone',
							!empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile, true);
						self::$smarty->assign('language', $this->context->language->iso_code, true);
						self::$smarty->assign('languages', array(
							'en' 	=> array(
								'lang' 	=> 'en-US',
								'name' 	=> $this->module->l('English'),
							),
							'fr' 	=> array(
								'lang' 	=> 'fr-BE',
								'name' 	=> $this->module->l('French'),
							),
							'nl' 	=> array(
								'lang' 	=> 'nl-BE',
								'name' 	=> $this->module->l('Dutch'),
							),
						));

						self::$smarty->assign('url_post_bpack247_register', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'						=> true,
							'post_bpack247_register' 	=> true,
							'shipping_method'			=> $shipping_method,
							'token'						=> Tools::getToken('bpostshm'),
						)));

						self::$smarty->assign('url_get_point_list', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'content_only'		=> true,
							'shipping_method'	=> $shipping_method,
							'step'				=> 2,
							'token'				=> Tools::getToken('bpostshm'),
						)));

						$this->addJqueryPlugin('fancybox');
						$this->setTemplate('lightbox-at-247.tpl');
						break;

					case 2:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);

						if (!$customer = $this->context->cart->bpack247_customer)
							return false;

						$customer = unserialize($customer);
						self::$smarty->assign('city', $customer['town'], true);
						self::$smarty->assign('postcode', '', true);

						$search_params = array(
							'street' 	=> $customer['street'],
							'nr' 		=> $customer['number'],
							'zone'		=> $customer['town'],
						);
						$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
						self::$smarty->assign('servicePoints', $service_points, true);

						self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'							=> true,
							'get_nearest_service_points' 	=> true,
							'shipping_method'				=> $shipping_method,
							'token'							=> Tools::getToken('bpostshm'),
						)));
						self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'						=> true,
							'get_service_point_hours' 	=> true,
							'shipping_method'			=> $shipping_method,
							'token'						=> Tools::getToken('bpostshm'),
						)));
						self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'				=> true,
							'set_service_point' => true,
							'shipping_method'	=> $shipping_method,
							'token'				=> Tools::getToken('bpostshm'),
						)));

						$this->setTemplate('lightbox-point-list.tpl');
						break;
				}
				break;
		}
	}

	public function setMedia()
	{
		parent::setMedia();

		$this->addCSS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/css/lightbox.css');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/bpostshm.js');
		$this->addJS('https://maps.googleapis.com/maps/api/js?v=3.16&key=AIzaSyAa4S8Br_5of6Jb_Gjv1WLldkobgExB2KY&sensor=false&language='.$this->context->language->iso_code);
	}

	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

}