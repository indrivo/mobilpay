<?php

namespace Drupal\commerce_mobilpay\Plugin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class RedirectCheckoutForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $mobilpay_request_card = \Drupal::service('commerce_mobilpay.proccess')->buildCommerceMobilpayRequestCard($payment);
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];

    $redirect_live_mode = $payment_gateway_plugin->getConfiguration()['live_mode'];
    $redirect_test_mode = $payment_gateway_plugin->getConfiguration()['test_mode'];
    $mode = $payment_gateway_plugin->getConfiguration()['mode'];

//    $remove_js = ($redirect_method == 'post_manual');
    if (in_array($redirect_method, ['post', 'post_manual'])) {
      if ($mode == 'test') {
        $redirect_url = $redirect_test_mode;
      }
      else {
        $redirect_url = $redirect_live_mode;
      }
      $redirect_method = 'post';
    }
  //  else {
  //    // Gateways that use the GET redirect method usually perform an API call
  //    // that prepares the remote payment and provides the actual url to
  //    // redirect to. Any params received from that API call that need to be
  //    // persisted until later payment creation can be saved in $order->data.
  //    // Example: $order->setData('my_gateway', ['test' => '123']), followed
  //    // by an $order->save().
  //    $order = $payment->getOrder();
  //    // Simulate an API call failing and throwing an exception, for test purposes.
  //    // See PaymentCheckoutTest::testFailedCheckoutWithOffsiteRedirectGet().
  //    if ($order->getBillingProfile()->get('address')->family_name == 'FAIL') {
  //      throw new PaymentGatewayException('Could not get the redirect URL.');
  //    }
  //    $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_302', [], ['absolute' => TRUE])->toString();
  //  }

    $data = [
      'env_key' => $mobilpay_request_card->getEnvKey(),
      'data' => $mobilpay_request_card->getEncData()
    ];

    // Todo: uncomment this line
    $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    if ($remove_js) {
      // Disable the javascript that auto-clicks the Submit button.
      unset($form['#attached']['library']);
    }

    return $form;
  }

}