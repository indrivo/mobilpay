<?php

namespace Drupal\commerce_mobilpay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_mobilpay\InstrumentCardService;
use Drupal\commerce_mobilpay\RequestCardService;
use Drupal\commerce_mobilpay\PaimentInvoiceService;
use Drupal\commerce_mobilpay\PaymentAddressService;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Class MobilPayForm.
 */
class MobilPayForm extends ConfigFormBase {

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
   * Constructs a new MobilPayForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    InstrumentCardService $commerce_mobilpay_instrument_card,
    RequestCardService $commerce_mobilpay_request_card,
    PaimentInvoiceService $commerce_mobilpay_payment_invoice,
    PaymentAddressService $commerce_mobilpay_payment_address
    ) {
    parent::__construct($config_factory);
    $this->commerceMobilpayInstrumentCard = $commerce_mobilpay_instrument_card;
    $this->commerceMobilpayRequestCard = $commerce_mobilpay_request_card;
    $this->commerceMobilpayPaymentInvoice = $commerce_mobilpay_payment_invoice;
    $this->commerceMobilpayPaymentAddress = $commerce_mobilpay_payment_address;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('commerce_mobilpay.instrument_card'),
      $container->get('commerce_mobilpay.request_card'),
      $container->get('commerce_mobilpay.payment_invoice'),
      $container->get('commerce_mobilpay.payment_address')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_mobilpay.mobilpay',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobil_pay_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_mobilpay.mobilpay');
    $mobilpayconfig = $this->config('commerce_mobilpay.mobilpayconfig');
    $objPmReqCard = $this->commerceMobilpayRequestCard;
    $media_private_files = Media::load($mobilpayconfig->get('private_files')[0]['target_id']);
    $public_key_fid = $media_private_files->get('field_public_key')->getValue()[0]['target_id'];
    if ($public_key_file = File::load($public_key_fid)) {
      $x509FilePath = $public_key_file->getFileUri();
    }
    else {
      $x509FilePath = '';
    }

    try {
      $objPmReqCard->signature = $mobilpayconfig->get('signature'); //'GWV4-L8MP-TG2Y-6L42-FMQ7'
      $objPmReqCard->orderId = md5(uniqid(rand())); // Todo: real order ID
      $objPmReqCard->confirmUrl = Url::fromRoute('commerce_mobilpay.confirmUrl', ['commerce_order' => 181], ['absolute' => TRUE])->toString();
      $objPmReqCard->cancelUrl = Url::fromRoute('commerce_mobilpay.orderCancel', ['commerce_order' => 181], ['absolute' => TRUE])->toString();
      $objPmReqCard->returnUrl = Url::fromRoute($mobilpayconfig->get('return_url'), ['commerce_order' => 181], ['absolute' => TRUE])->toString();

      // #payment details: currency, amount, description
      $objPmReqCard->invoice = $this->commerceMobilpayPaymentInvoice;
      $objPmReqCard->invoice->currency = 'RON';
      $objPmReqCard->invoice->amount = '1.00';
      //$objPmReqCard->invoice->installments= '2,3';
      $objPmReqCard->invoice->details = 'Plata cu card-ul prin suma';

      // #detalii cu privire la adresa posesorului cardului
      $billingAddress = $this->commerceMobilpayPaymentAddress;
      $billingAddress->type = 'person';
      $billingAddress->firstName = 'firstName';
      $billingAddress->lastName = 'lastName';
      $billingAddress->fiscalNumber	= 'fiscalNumber';
      $billingAddress->identityNumber	= 'identityNumber';
      $billingAddress->country = 'country';
      $billingAddress->county = 'county';
      $billingAddress->city = 'city';
      $billingAddress->zipCode = 'zipCode';
      $billingAddress->address = 'address';
      $billingAddress->email = 'email@email.com';
      $billingAddress->mobilePhone = '654987654';
      $billingAddress->bank = '654987321';
      $billingAddress->iban = '321654987';
      $objPmReqCard->invoice->setBillingAddress($billingAddress);

      // #details on the shipping address
      $shippingAddress = $this->commerceMobilpayPaymentAddress;
      $shippingAddress->type = 'person';
      $shippingAddress->firstName = 'firstName';
      $shippingAddress->lastName = 'lastName';
      $shippingAddress->fiscalNumber	= 'fiscalNumber';
      $shippingAddress->identityNumber	= 'identityNumber';
      $shippingAddress->country = 'country';
      $shippingAddress->county = 'county';
      $shippingAddress->city = 'city';
      $shippingAddress->zipCode = 'zipCode';
      $shippingAddress->address = 'address';
      $shippingAddress->email = 'email@email.com';
      $shippingAddress->mobilePhone = '654987654';
      $shippingAddress->bank = '654987321';
      $shippingAddress->iban = '321654987';
      $objPmReqCard->invoice->setShippingAddress($shippingAddress);

      // #card detail
      $objPmi = $this->commerceMobilpayInstrumentCard;
      $objPmi->number = '98741';
      $objPmi->expYear = '2016';
      $objPmi->expMonth = '12';
      $objPmi->cvv2 = '123';
      $objPmi->name = 'John Doe';
      $objPmReqCard->paymentInstrument = $objPmi;
      $objPmReqCard->encrypt($x509FilePath);
    } catch (\Throwable $th) {
      dpm($th);
      \Drupal::logger('MobilPay Log')->warning($th);
    }

    if (!($th instanceof \Throwable)) {
      $form['#action'] = 'http://sandboxsecure.mobilpay.ro';
      $form['env_key'] = ['#type' => 'hidden', '#value' => $objPmReqCard->getEnvKey()];
      $form['data'] = ['#type' => 'hidden', '#value' => $objPmReqCard->getEncData()];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // $this->config('commerce_mobilpay.mobilpay')
    //   ->save();
  }

}
