<?php

namespace Drupal\commerce_mobilpay\Controller;

use Drupal\file\Entity\File;
use Drupal\user\UserInterface;
use Drupal\media\Entity\Media;
use Drupal\commerce_price\Price;
use Drupal\taxonomy\Entity\Term;
use Drupal\profile\Entity\Profile;
use Drupal\isf_appeal\Entity\Appeal;
use Drupal\isf_credit\Entity\Credit;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OrderProcessController.
 */
class OrderProcessController extends ControllerBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * Order Success.
   *
   * @return array
   *   Return Order data.
   */
  public function returnUrl() {
    $page = [
      '#type' => 'markup',
      '#markup' => t('Empty page'),
    ];

    $order_id = is_numeric(\Drupal::request()->query->get('orderId')) ? \Drupal::request()->query->get('orderId') : $this->order;
    $order = Order::load($order_id);
    /** @var UserInterface $customer */
    $customer = $order->getCustomer();
    $user_name = $this->t('Anonymous');
    if (!$customer->isAnonymous()) {
      $user_name = $customer->getDisplayName();
    }

    $query = \Drupal::entityQuery('commerce_payment');
    $query->condition('order_id', $order->id());
    $query->condition('type', 'payment_default');
    $query->condition('payment_gateway', 'mobilpay');
    $ids = $query->execute();

    $payments = Payment::loadMultiple($ids);
    /** @var PaymentInterface $payment */
    foreach ($payments as $payment) {
      if ($account = user_load_by_mail($order->getEmail())) {
        $payment_state = empty($payment->getState()) ? '' : t($payment->getState()->getLabel()) . ' ';
        $page = [
          '#type' => 'markup',
          '#markup' => $payment_state . t('Total Price: %totalPrice. Customer: %customer. Customer mail: %customer_mail.', [
            '%totalPrice' => $order->getTotalPrice(),
            '%customer' => $user_name,
            '%customer_mail' => $order->getEmail(),
          ]),
        ];
      }
    }

    return $page;
  }

  /**
   * Order Cancel.
   */
  public function orderCancel() {
    if (!empty(\Drupal::request()->request->all())) {
      $envKey = \Drupal::request()->request->get('env_key');
      $encData = \Drupal::request()->request->get('data');

      $mobilpay_config = \Drupal::config('commerce_mobilpay.mobilpayconfig');
      $privateKeyPassword = NULL;
      $media_private_files = Media::load($mobilpay_config->get('private_files')[0]['target_id']);
      $private_key_fid = $media_private_files->get('field_private_key')->getValue()[0]['target_id'];
      if ($private_key_file = File::load($private_key_fid)) {
        $privateKeyFilePath = $private_key_file->getFileUri();
      }
      else {
        $privateKeyFilePath = '';
      }

      $decoded_data = \Drupal::service('commerce_mobilpay.request_card')->factoryFromEncrypted($envKey, $encData, $privateKeyFilePath);
      $xml = simplexml_load_string($decoded_data->getRequestInfo()->reqData) or die("Error: Cannot create object");

      if (is_array(get_class_methods(get_class($xml->mobilpay->error)))) {
        if (in_array('__toString', get_class_methods(get_class($xml->mobilpay->error)))) {
          $payment_status = empty($xml->mobilpay->error->__toString()) ? 'Refuzat' : $xml->mobilpay->error->__toString();
        }
      }
      else {
        $payment_status = empty($xml->mobilpay->error) ? 'Refuzat' : $xml->mobilpay->error;
      }

      $order_id = is_numeric(\Drupal::request()->query->get('orderId')) ? \Drupal::request()->query->get('orderId') : $this->order;
      $order = Order::load($order_id);

      $this->statusProcess($order, $payment_status);
    }

    if ($payment_status == 'Tranzactia aprobata') {
      $merchants_response = "<crc>$payment_status</crc>";
    }
    else {
      $merchants_response = "<crc error_type='1' error_code='38'>$payment_status</crc>";
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
    $response->setContent($merchants_response);

    return $response;
  }

  /**
   * Order Success.
   */
  public function confirmUrl() {
    if (!empty(\Drupal::request()->request->all())) {
      $envKey = \Drupal::request()->request->get('env_key');
      $encData = \Drupal::request()->request->get('data');

      $mobilpay_config = \Drupal::config('commerce_mobilpay.mobilpayconfig');
      $privateKeyPassword = NULL;
      $media_private_files = Media::load($mobilpay_config->get('private_files')[0]['target_id']);
      $private_key_fid = $media_private_files->get('field_private_key')->getValue()[0]['target_id'];
      if ($private_key_file = File::load($private_key_fid)) {
        $privateKeyFilePath = $private_key_file->getFileUri();
      }
      else {
        $privateKeyFilePath = '';
      }

      $decoded_data = \Drupal::service('commerce_mobilpay.request_card')->factoryFromEncrypted($envKey, $encData, $privateKeyFilePath);
      $xml = simplexml_load_string($decoded_data->getRequestInfo()->reqData) or die("Error: Cannot create object");

      if (is_array(get_class_methods(get_class($xml->mobilpay->error)))) {
        if (in_array('__toString', get_class_methods(get_class($xml->mobilpay->error)))) {
          $payment_status = empty($xml->mobilpay->error->__toString()) ? 'Refuzat' : $xml->mobilpay->error->__toString();
        }
      }
      else {
        $payment_status = empty($xml->mobilpay->error) ? 'Refuzat' : $xml->mobilpay->error;
      }

      $order_id = is_numeric(\Drupal::request()->query->get('orderId')) ? \Drupal::request()->query->get('orderId') : $this->order;
      $order = Order::load($order_id);

      $this->statusProcess($order, $payment_status);
    }

    if ($payment_status == 'Tranzactia aprobata') {
      $merchants_response = "<crc>$payment_status</crc>";
    }
    else {
      $merchants_response = "<crc error_type='1' error_code='38'>$payment_status</crc>";
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
    $response->setContent($merchants_response);

    return $response;
  }

  /**
   * @param OrderInterface $order
   * @param $payment_status
   */
  protected function statusProcess(OrderInterface $order, $payment_status) {
    $order_status = 'canceled';
    if ($payment_status == 'Tranzactia aprobata') {
      $order_status = 'completed';
    }

    $this->setOrderStatus($order, $order_status);
    $this->setPaymentMethod($order, $payment_status);
  }

  /**
   * {@inheritdoc}
   */
  public static function setOrderStatus(OrderInterface $order, $status) {
    switch ($status) {
      case 'completed':
        if (empty($order->getCompletedTime())) {
          $order->setCompletedTime(\Drupal::time()->getRequestTime());
          if ($order->isLocked()) {
            $order->unlock();
          }
        }
        break;

      case 'canceled':
        if (!$order->isLocked()) {
          $order->lock();
        }
        break;
    }

    $order->set('state', $status);
    $order->save();

    return TRUE;
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param $state
   */
  public function setPaymentMethod(OrderInterface $order, $state) {
    $logger = \Drupal::logger('MobilPay Log');
    $logger->notice($state);
    $query = \Drupal::entityQuery('commerce_payment');
    $query->condition('order_id', $order->id());
    $query->condition('type', 'payment_default');
    $query->condition('payment_gateway', 'mobilpay');
    $query->sort('payment_id', 'DESC');
    $ids = $query->execute();

    if (!empty($ids)) {
      $payment = Payment::load(reset($ids));
    }
    else {
      $payment = Payment::create([
        'type' => 'payment_default',
        'payment_gateway' => 'mobilpay',
        'order_id' => $order->id(),
        'amount' => new Price($order->getSubtotalPrice()->getNumber(), $order->getSubtotalPrice()->getCurrencyCode()),
      ]);
    }
    $payment->set('state', $state);
    $current_time = \Drupal::time()->getCurrentTime();
    $payment->set('completed', $current_time);
    if ($payment->hasField('commerce_payment_date')) {
      $payment->set('commerce_payment_date', $current_time);
    }
    $payment->save();
  }

}
