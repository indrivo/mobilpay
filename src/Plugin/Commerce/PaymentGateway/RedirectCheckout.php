<?php

namespace Drupal\commerce_mobilpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the mobilPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "mobilpay_redirect_checkout",
 *   label = @Translation("mobilPay (Redirect to mobilPay)"),
 *   display_label = @Translation("mobilPay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_mobilpay\Plugin\PluginForm\RedirectCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase {

  public function defaultConfiguration() {
    return [
        'confirm_url' => '',
        'cancel_url' => '',
      ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = \Drupal::config('commerce_mobilpay.mobilpayconfig');
  
    $form['test_mode'] = array(
      '#type' => 'textfield',
      '#title' => t('test mode'),
      '#description' => t('standard payment, test mode.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['test_mode'],
    );
    $form['live_mode'] = array(
      '#type' => 'textfield',
      '#title' => t('live mode'),
      '#description' => t('standard payment, live mode.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['live_mode'],
    );

    $form['redirect_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect method'),
      '#options' => [
        'post' => $this->t('Redirect via POST (automatic)'),
        'post_manual' => $this->t('Redirect via POST (manual)'),
      ],
      '#default_value' => $this->configuration['redirect_method'],
    ];

    $form['private_files'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => $this->t("MobilPay's keys"),
      '#target_type' => 'media',
      '#selection_settings' => ['target_bundles' => ['private_files']],
      '#default_value' => Media::load($config->get('private_files')[0]['target_id']),
      '#tags' => TRUE,
      '#size' => 30,
      '#maxlength' => 1024,
    );
    $form['return_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return URL'),
      '#description' => $this->t('URL to the page when payment is pending or approved.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('succes_url'),
    ];
    $form['cancel_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel Url'),
      '#description' => $this->t('URL to the page when payment is canceled'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('cancel_url'),
    ];
    $form['signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#description' => $this->t('signature received from mobilpay.ro that identifies merchant account'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('signature'),
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('commerce_mobilpay.mobilpayconfig');
    $values = $form_state->getValue($form['#parents']);

    $this->configuration['test_mode'] = $values['test_mode'];
    $this->configuration['live_mode'] = $values['live_mode'];
    $this->configuration['redirect_method'] = $values['redirect_method'];

    $config
      ->set('private_files', $values['private_files'])
      ->set('return_url', $values['return_url'])
      ->set('cancel_url', $values['cancel_url'])
      ->set('signature', $values['signature'])
      ->save();
  }

}