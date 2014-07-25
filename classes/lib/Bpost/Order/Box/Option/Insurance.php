<?php
/**
 * bPost Insurance class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order\Box\Option;

use TijsVerkoyen\Bpost\Exception;

class Insurance extends Option
{
	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $value;

	/**
	 * @return array
	 */
	public static function getPossibleTypeValues()
	{
		return array(
			'basicInsurance',
			'additionalInsurance',
		);
	}

	/**
	 * @param string $type
	 * @throws Exception
	 */
	public function setType($type)
	{
		if (!in_array($type, self::getPossibleTypeValues()))
			throw new Exception(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleTypeValues())
				)
			);

		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $value
	 * @throws Exception
	 */
	public function setValue($value)
	{
		if (!in_array($value, self::getPossibleValueValues()))
			throw new Exception(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleValueValues())
				)
			);

		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return array
	 */
	public static function getPossibleValueValues()
	{
		return array(
			2,
			3,
			4,
			5,
			6,
			7,
			8,
			9,
			10,
			11
		);
	}

	/**
	 * @param string	  $type
	 * @param null|string $value
	 */
	public function __construct($type, $value = null)
	{
		$this->setType($type);
		if ($value !== null)
			$this->setValue($value);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null)
	{
		$tag_name = 'insured';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$insured = $document->createElement($tag_name);

		$tag_name = $this->getType();
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$insurance = $document->createElement($tag_name);
		$insured->appendChild($insurance);

		if ($this->getValue() !== null)
			$insurance->setAttribute('value', $this->getValue());

		return $insured;
	}
}
