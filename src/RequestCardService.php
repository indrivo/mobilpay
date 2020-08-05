<?php

namespace Drupal\commerce_mobilpay;
use Drupal\commerce_mobilpay\PaimentInvoiceService;
use Drupal\commerce_mobilpay\ReccurenceService;
use Drupal\commerce_mobilpay\InstrumentCardService;
use Drupal\commerce_mobilpay\request\RequestCardServiceAbstract;

/**
 * Class RequestCardService.
 * This class can be used for accessing mobilpay.ro payment interface for your configured online services
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 *
 */
class RequestCardService extends RequestCardServiceAbstract {

  const ERROR_LOAD_FROM_XML_ORDER_INVOICE_ELEM_MISSING = 0x30000001;
  const CUSTOMER_TYPE_MERCHANT = 0x01;
  const CUSTOMER_TYPE_MOBILPAY = 0x02;

  /**
   * Drupal\commerce_mobilpay\PaimentInvoiceService definition.
   *
   * @var \Drupal\commerce_mobilpay\PaimentInvoiceService
   */
  protected $commerceMobilpayPaymentInvoice;

  /**
   * Drupal\commerce_mobilpay\ReccurenceService definition.
   *
   * @var \Drupal\commerce_mobilpay\ReccurenceService
   */
  protected $commerceMobilpayReccurence;
  
  /**
   * Drupal\commerce_mobilpay\InstrumentCardService definition.
   *
   * @var \Drupal\commerce_mobilpay\InstrumentCardService
   */
  protected $commerceMobilpayInstrumentCard;

  public $invoice = null;
  public $recurrence = null;

  /**
   * Constructs a new RequestCardService object.
   */
  public function __construct(PaimentInvoiceService $commerce_mobilpay_payment_invoice, ReccurenceService $commerce_mobilpay_reccurence, InstrumentCardService $commerce_mobilpay_instrument_card) {
    parent::__construct();

    $this->commerceMobilpayPaymentInvoice = $commerce_mobilpay_payment_invoice;
    $this->commerceMobilpayReccurence = $commerce_mobilpay_reccurence;
    $this->commerceMobilpayInstrumentCard = $commerce_mobilpay_instrument_card;
	  $this->type = self::PAYMENT_TYPE_CARD;
  }

  protected function _loadFromXml(\DOMElement $elem) {
    parent::_parseFromXml($elem);
    //card request specific data
    $elems = $elem->getElementsByTagName('invoice');
    if ($elems->length != 1) {
      throw new  \Exception('RequestCardService::loadFromXml failed; invoice element is missing', self::ERROR_LOAD_FROM_XML_ORDER_INVOICE_ELEM_MISSING);
    }
    $this->invoice = $this->commerceMobilpayPaymentInvoice; //($elems->item(0));
    $elems = $elem->getElementsByTagName('recurrence');
    if ($elems->length > 0) {
      $this->recurrence = $this->commerceMobilpayReccurence($elems->item(0));
    }
    $elems = $elem->getElementsByTagName('payment_instrument');
    if ($elems->length > 0) {
      $this->paymentInstrument = $this->commerceMobilpayInstrumentCard($elems->item(0));
    }

    return $this;
  }
  
  protected function _prepare() {
    if (is_null($this->signature) || is_null($this->orderId) || !($this->invoice instanceof PaimentInvoiceService)) {
      throw new  \Exception('One or more mandatory properties are invalid!', self::ERROR_PREPARE_MANDATORY_PROPERTIES_UNSET);
    }
  
    $this->_xmlDoc = new  \DOMDocument('1.0', 'utf-8');
    $rootElem = $this->_xmlDoc->createElement('order');
  
    //set payment type attribute
    $xmlAttr = $this->_xmlDoc->createAttribute('type');
    $xmlAttr->nodeValue = $this->type;
    $rootElem->appendChild($xmlAttr);
  
    //set id attribute
    $xmlAttr = $this->_xmlDoc->createAttribute('id');
    $xmlAttr->nodeValue = $this->orderId;
    $rootElem->appendChild($xmlAttr);
  
    //set timestamp attribute
    $xmlAttr = $this->_xmlDoc->createAttribute('timestamp');
    $xmlAttr->nodeValue = date('YmdHis');
    $rootElem->appendChild($xmlAttr);
  
    $xmlElem = $this->_xmlDoc->createElement('signature');
    $xmlElem->nodeValue = $this->signature;
    $rootElem->appendChild($xmlElem);
  
    $xmlElem = $this->_xmlDoc->createElement('service');
    $xmlElem->nodeValue = $this->service;
    $rootElem->appendChild($xmlElem);
  
    if ($this->secretCode) {
      $xmlAttr = $this->_xmlDoc->createAttribute('secretcode');
      $xmlAttr->nodeValue = $this->secretCode;
      $rootElem->appendChild($xmlAttr);
    }
  
    $xmlElem = $this->invoice->createXmlElement($this->_xmlDoc);
    $rootElem->appendChild($xmlElem);
  
    if ($this->recurrence instanceof Mobilpay_Payment_Recurrence) {
      $xmlElem = $this->recurrence->createXmlElement($this->_xmlDoc);
      $rootElem->appendChild($xmlElem);
    }
  
    if ($this->paymentInstrument instanceof \Mobilpay_Payment_Instrument_Card) {
      $xmlElem = $this->_xmlDoc->createElement('payment_instrument');
      $xmlElem2 = $this->paymentInstrument->createXmlElement($this->_xmlDoc);
      $xmlElem->appendChild($xmlElem2);
      $rootElem->appendChild($xmlElem);
    }
  
    if (is_array($this->params) && sizeof($this->params) > 0) {
      $xmlParams = $this->_xmlDoc->createElement('params');
      foreach ($this->params as $key => $value) {
        $xmlParam = $this->_xmlDoc->createElement('param');
    
        $xmlName = $this->_xmlDoc->createElement('name');
        $xmlName->nodeValue = trim($key);
        $xmlParam->appendChild($xmlName);
    
        $xmlValue = $this->_xmlDoc->createElement('value');
        $xmlValue->appendChild($this->_xmlDoc->createCDATASection($value));
        $xmlParam->appendChild($xmlValue);
    
        $xmlParams->appendChild($xmlParam);
      }
      $rootElem->appendChild($xmlParams);
    }
  
    if (!is_null($this->returnUrl) || !is_null($this->confirmUrl)) {
      $xmlUrl = $this->_xmlDoc->createElement('url');
      if (!is_null($this->returnUrl)) {
        $xmlElem = $this->_xmlDoc->createElement('return');
        $c = $this->_xmlDoc->createCDATASection($this->returnUrl);
        //$xmlElem->nodeValue = $this->returnUrl;
        $xmlElem->appendChild($c);
        $xmlUrl->appendChild($xmlElem);
        //var_dump($xmlElem->nodeValue);
      }
      if (!is_null($this->confirmUrl)) {
        $xmlElem = $this->_xmlDoc->createElement('confirm');
        $xmlElem->nodeValue = $this->confirmUrl;
        $xmlUrl->appendChild($xmlElem);
      }
      $rootElem->appendChild($xmlUrl);
    }
    $this->_xmlDoc->appendChild($rootElem);
  
    return $this;
  }
  
}
