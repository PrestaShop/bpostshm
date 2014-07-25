<?php

class ParentOrderController extends ParentOrderControllerCore
{
	protected function _processCarrier()
	{
		$this->context->cart->recyclable = (int)(Tools::getValue('recyclable'));
		$this->context->cart->gift = (int)(Tools::getValue('gift'));
		if ((int)(Tools::getValue('gift')))
		{
			if (!Validate::isMessage($_POST['gift_message']))
				$this->errors[] = Tools::displayError('Invalid gift message.');
			else
				$this->context->cart->gift_message = strip_tags($_POST['gift_message']);
		}

		if (isset($this->context->customer->id) && $this->context->customer->id)
		{
			$address = new Address((int)($this->context->cart->id_address_delivery));
			if (!($id_zone = Address::getZoneById($address->id)))
				$this->errors[] = Tools::displayError('No zone matches your address.');
		}
		else
			$id_zone = Country::getIdZone((int)Configuration::get('PS_COUNTRY_DEFAULT'));
		
		if (Tools::getIsset('delivery_option'))
		{
			if ($this->validateDeliveryOption(Tools::getValue('delivery_option')))
				$this->context->cart->setDeliveryOption(Tools::getValue('delivery_option'));
		}
		elseif (Tools::getIsset('id_carrier'))
		{
			// For retrocompatibility reason, try to transform carrier to an delivery option list
			$delivery_option_list = $this->context->cart->getDeliveryOptionList();
			if (count($delivery_option_list) == 1)
			{
				$delivery_option = reset($delivery_option_list);
				$key = Cart::desintifier(Tools::getValue('id_carrier'));
				foreach ($delivery_option_list as $id_address => $options)
					if (isset($options[$key]))
					{
						$this->context->cart->id_carrier = (int)Tools::getValue('id_carrier');
						$this->context->cart->setDeliveryOption(array($id_address => $key));
						if(isset($this->context->cookie->id_country))
							unset($this->context->cookie->id_country);
						if(isset($this->context->cookie->id_state))
							unset($this->context->cookie->id_state);							
							
					}
			}
		}

		Hook::exec('actionCarrierProcess', array(
			'cart' => $this->context->cart,
			'passthrough' => Tools::getValue('passthrough', false)
		));

		if (!$this->context->cart->update())
			return false;

		// Carrier has changed, so we check if the cart rules still apply
		CartRule::autoRemoveFromCart($this->context);
		CartRule::autoAddToCart($this->context);
		
		return true;
	}
}

