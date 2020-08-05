<?php

namespace Drupal\commerce_mobilpay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;

/**
 * Class MobilPayConfigForm.
 */
class MobilPayConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_mobilpay.mobilpayconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobil_pay_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_mobilpay.mobilpayconfig');
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
    $form['succes_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Succes URL'),
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

    $form_private_file = $form_state->getValue('private_key', 0);
    if (isset($form_private_file[0]) && !empty($form_private_file[0])) {
      $file = File::load($form_private_file[0]);
      $file->setPermanent();
      $file->save();
    }
    $form_public_file = $form_state->getValue('public_key', 0);
    if (isset($form_public_file[0]) && !empty($form_public_file[0])) {
      $file = File::load($form_public_file[0]);
      $file->setPermanent();
      $file->save();
    }

    $this->config('commerce_mobilpay.mobilpayconfig')
      ->set('private_files', $form_state->getValue('private_files'))
      ->set('succes_url', $form_state->getValue('succes_url'))
      ->set('cancel_url', $form_state->getValue('cancel_url'))
      ->set('signature', $form_state->getValue('signature'))
      ->save();
  }

}
