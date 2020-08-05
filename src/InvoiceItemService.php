<?php

namespace Drupal\commerce_mobilpay;

/**
 * Class InvoiceItemService.
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 * 
 */
class InvoiceItemService {

  const ERROR_INVALID_PARAMETER = 0x11111001;
	const ERROR_INVALID_PROPERTY = 0x11110002;
	
	const ERROR_LOAD_FROM_XML_CODE_ELEM_MISSING = 0x40000001;
	const ERROR_LOAD_FROM_XML_NAME_ELEM_MISSING = 0x40000002;
	const ERROR_LOAD_FROM_XML_QUANTITY_ELEM_MISSING	= 0x40000003;
	const ERROR_LOAD_FROM_XML_QUANTITY_ELEM_EMPTY	= 0x40000004;
	const ERROR_LOAD_FROM_XML_PRICE_ELEM_MISSING = 0x40000005;
	const ERROR_LOAD_FROM_XML_PRICE_ELEM_EMPTY = 0x40000006;
	const ERROR_LOAD_FROM_XML_VAT_ELEM_MISSING = 0x40000007;
	
	public $code = null;
	public $name = null;
	public $measurment = null;
	public $quantity = null;
	public $price = null;
	public $vat = null;

  /**
   * Constructs a new InvoiceItemService object.
   */
  public function __construct(DOMNode $elem = null) {
    if($elem != null) {
			$this->loadFromXml($elem);
		}
  }

	protected function loadFromXml(DOMNode $elem) {
		$elems = $elem->getElementsByTagName('code');
		if($elems->length != 1) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid code element.', self::ERROR_LOAD_FROM_XML_CODE_ELEM_MISSING);
		}
		$this->code = urldecode($elems->item(0)->nodeValue);

		$elems = $elem->getElementsByTagName('name');
		if($elems->length != 1) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid name element.', self::ERROR_LOAD_FROM_XML_NAME_ELEM_MISSING);
		}
		$this->name = urldecode($elems->item(0)->nodeValue);

		$elems = $elem->getElementsByTagName('measurment');
		if($elems->length == 1) {
			$this->measurment = urldecode($elems->item(0)->nodeValue);
		}

		$elems = $elem->getElementsByTagName('quantity');
		if($elems->length != 1) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid quantity element.', self::ERROR_LOAD_FROM_XML_QUANTITY_ELEM_MISSING);
		}
		$this->quantity = doubleval(urldecode($elems->item(0)->nodeValue));
		if($this->quantity == 0) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid quantity value=' . $this->quantity, self::ERROR_LOAD_FROM_XML_QUANTITY_ELEM_EMPTY);
		}

		$elems = $elem->getElementsByTagName('price');
		if($elems->length != 1) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid price element.', self::ERROR_LOAD_FROM_XML_PRICE_ELEM_MISSING);
		}
		$this->price = doubleval(urldecode($elems->item(0)->nodeValue));
		if($this->price == 0) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid price value=' . $this->price, self::ERROR_LOAD_FROM_XML_PRICE_ELEM_EMPTY);
		}

		$elems = $elem->getElementsByTagName('vat');
		if($elems->length != 1) {
			throw new  \Exception('InvoiceItemService::loadFromXml failed! Invalid vat element.', self::ERROR_LOAD_FROM_XML_VAT_ELEM_MISSING);
		}
		$this->vat = doubleval(urldecode($elems->item(0)->nodeValue));
		
		return $this;
	}
	
}
