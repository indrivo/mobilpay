<?php

// namespace Drupal\commerce_mobilpay\Form;

// use Drupal\Core\Form\FormStateInterface;

// /**
//  * Class mobilPayGatewayBaseForm.
//  */
// class mobilPayGatewayBaseForm extends PaymentGatewayBase {

//   public function defaultConfiguration() {
//     return [
//         'private_key' => '',
//         'api_key' => '',
//       ] + parent::defaultConfiguration();
//   }

//   public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//     $form = parent::buildConfigurationForm($form, $form_state);

//     $form['private_key'] = [
//       '#type' => 'textfield',
//       '#title' => $this->t('Private key'),
//       '#description' => $this->t('This is the private key from the Quickpay manager.'),
//       '#default_value' => $this->configuration['private_key'],
//       '#required' => TRUE,
//     ];

//     $form['api_key'] = [
//       '#type' => 'textfield',
//       '#title' => $this->t('API key'),
//       '#description' => $this->t('The API key for the same user as used in Agreement ID.'),
//       '#default_value' => $this->configuration['api_key'],
//       '#required' => TRUE,
//     ];

//     return $form;
//   }

//   public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
//     parent::submitConfigurationForm($form, $form_state);
//     $values = $form_state->getValue($form['#parents']);
//     $this->configuration['private_key'] = $values['private_key'];
//     $this->configuration['api_key'] = $values['api_key'];
//   }

// }
