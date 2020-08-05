<?php

namespace Drupal\commerce_mobilpay;

use \Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Class RedirectCheckoutService.
 */
class RedirectCheckoutService {

  /**
   * Drupal\commerce_mobilpay\InstrumentCardService definition.
   *
   * @var \Drupal\commerce_mobilpay\InstrumentCardService
   */
  protected $commerceMobilpayInstrumentCard;
  /**
   * Drupal\commerce_mobilpay\RequestCardService definition.
   *
   * @var \Drupal\commerce_mobilpay\RequestCardService
   */
  protected $commerceMobilpayRequestCard;
  /**
   * Drupal\commerce_mobilpay\PaimentInvoiceService definition.
   *
   * @var \Drupal\commerce_mobilpay\PaimentInvoiceService
   */
  protected $commerceMobilpayPaymentInvoice;
  /**
   * Drupal\commerce_mobilpay\PaymentAddressService definition.
   *
   * @var \Drupal\commerce_mobilpay\PaymentAddressService
   */
  protected $commerceMobilpayPaymentAddress;
  /**
   * Constructs a new RedirectCheckoutService object.
   */
  public function __construct(
    InstrumentCardService $commerce_mobilpay_instrument_card,
    RequestCardService $commerce_mobilpay_request_card,
    PaimentInvoiceService $commerce_mobilpay_payment_invoice,
    PaymentAddressService $commerce_mobilpay_payment_address
  ) {
    $this->commerceMobilpayInstrumentCard = $commerce_mobilpay_instrument_card;
    $this->commerceMobilpayRequestCard = $commerce_mobilpay_request_card;
    $this->commerceMobilpayPaymentInvoice = $commerce_mobilpay_payment_invoice;
    $this->commerceMobilpayPaymentAddress = $commerce_mobilpay_payment_address;
  }

  public function buildCommerceMobilpayRequestCard(PaymentInterface $payment) {
    $order = $payment->getOrder();
    $mobilpay_config = \Drupal::config('commerce_mobilpay.mobilpayconfig');
    $mobil_config = \Drupal::config('commerce_mobilpay.mobilpay');
    $objPmReqCard = $this->commerceMobilpayRequestCard;


    $media_private_files = Media::load($mobilpay_config->get('private_files')[0]['target_id']);
    $public_key_fid = $media_private_files->get('field_public_key')->getValue()[0]['target_id'];
    if ($public_key_file = File::load($public_key_fid)) {
      $x509FilePath = $public_key_file->getFileUri();
    }
    else {
      $x509FilePath = '';
    }

    try {
      $objPmReqCard->signature = $mobilpay_config->get('signature'); //Ex. GWV4-L8MP-TG2Y-6L42-FMQ7
      $objPmReqCard->orderId = $order->id();
      $objPmReqCard->confirmUrl = Url::fromRoute('commerce_mobilpay.confirmUrl', ['commerce_order' => $order->id()], ['absolute' => TRUE])->toString();
      $objPmReqCard->cancelUrl = Url::fromRoute('commerce_mobilpay.orderCancel', ['commerce_order' => $order->id()], ['absolute' => TRUE])->toString();
      $objPmReqCard->returnUrl = Url::fromRoute($mobilpay_config->get('return_url'), ['commerce_order' => $order->id()], ['absolute' => TRUE])->toString();

      if ($order->hasItems()) {
        $objPmReqCard->invoice = $this->commerceMobilpayPaymentInvoice;

        $order_total_summary = \Drupal::service('commerce_order.order_total_summary');
        $totals = $order_total_summary->buildTotals($order);

        $objPmReqCard->invoice->currency = $totals['total']->getCurrencyCode();
        $objPmReqCard->invoice->amount = $totals['total']->getNumber();
        $objPmReqCard->invoice->details = 'Plata cu card-ul.';
      }

      $billingAddress = $this->commerceMobilpayPaymentAddress;
//      $billingAddress->type = '';
//      $billingAddress->firstName = '';
//      $billingAddress->lastName = '';
//      $billingAddress->fiscalNumber	= '';
//      $billingAddress->identityNumber	= '';
//      $billingAddress->country = '';
//      $billingAddress->county = '';
//      $billingAddress->city = '';
//      $billingAddress->zipCode = '';
//      $billingAddress->address = '';
//      $billingAddress->email = '';
//      $billingAddress->mobilePhone = '';
//      $billingAddress->bank = '';
//      $billingAddress->iban = '';
      $objPmReqCard->invoice->setBillingAddress($billingAddress);

      $shippingAddress = $this->commerceMobilpayPaymentAddress;
//      $shippingAddress->type = '';
//      $shippingAddress->firstName = '';
//      $shippingAddress->lastName = '';
//      $shippingAddress->fiscalNumber	= '';
//      $shippingAddress->identityNumber	= '';
//      $shippingAddress->country = '';
//      $shippingAddress->county = '';
//      $shippingAddress->city = '';
//      $shippingAddress->zipCode = '';
//      $shippingAddress->address = '';
//      $shippingAddress->email = '';
//      $shippingAddress->mobilePhone = '';
//      $shippingAddress->bank = '';
//      $shippingAddress->iban = '';
      $objPmReqCard->invoice->setShippingAddress($shippingAddress);

      // #card detail
      $objPmi = $this->commerceMobilpayInstrumentCard;
//      $objPmi->number = '';
//      $objPmi->expYear = '';
//      $objPmi->expMonth = '';
//      $objPmi->cvv2 = '';
//      $objPmi->name = '';
      $objPmReqCard->paymentInstrument = $objPmi;
      $objPmReqCard->encrypt($x509FilePath);
    } catch (\Throwable $th) {
      \Drupal::logger('MobilPay Log')->warning($th);
    }
    return $objPmReqCard;
  }

}
