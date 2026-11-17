<?php

namespace Drupal\status_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\status_feed\WebhookSender;

/**
 * Configure Status Feed settings for this site.
 *
 * @package Drupal\status_feed\Form
 */
class MonitorSettingsForm extends ConfigFormBase {

  /**
   * Config settings key.
   *
   * @var string
   */
  const SETTINGS = 'status_feed.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'status_feed_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['cachetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cachetime'),
      '#default_value' => $config->get('cachetime'),
      '#description' => 'Number of seconds the data is cached',
    ];

    $form['activate_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate cron'),
      '#default_value' => $config->get('activate_cron'),
    ];

    $webhook = \Drupal::state()->get(WebhookSender::SETTINGS, []);
    $form['webhook'] = [
      '#type' => 'details',
      '#title' => $this->t('Send status feed to Drools'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('For sites behind a firewall. Copy the URL and token from the website’s Status webhook tab in Drools. These environment-specific settings are stored outside configuration exports.'),
    ];
    $form['webhook']['enabled'] = ['#type' => 'checkbox', '#title' => $this->t('Send feeds on cron'), '#default_value' => $webhook['enabled'] ?? FALSE];
    $form['webhook']['url'] = ['#type' => 'url', '#title' => $this->t('Webhook URL'), '#default_value' => $webhook['url'] ?? '', '#maxlength' => 2048];
    $form['webhook']['token'] = [
      '#type' => 'password',
      '#title' => $this->t('Secret token'),
      '#description' => $this->t('Leave empty to keep the saved token.'),
      '#attributes' => ['autocomplete' => 'new-password'],
    ];
    $form['webhook']['interval'] = ['#type' => 'number', '#title' => $this->t('Send interval (seconds)'), '#default_value' => $webhook['interval'] ?? 3600, '#min' => 60, '#max' => 2592000, '#required' => TRUE];
    $last = \Drupal::state()->get('status_feed.webhook_last_success');
    $form['webhook']['last_success'] = ['#type' => 'item', '#title' => $this->t('Last successful delivery'), '#plain_text' => $last ? \Drupal::service('date.formatter')->format($last) : $this->t('Never')];
    $form = parent::buildForm($form, $form_state);
    $form['actions']['send'] = ['#type' => 'submit', '#value' => $this->t('Save and send now'), '#submit' => ['::submitForm', '::sendNow']];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValue('webhook');
    $saved = \Drupal::state()->get(WebhookSender::SETTINGS, []);
    if (!empty($values['enabled'])) {
      $url = parse_url($values['url']);
      if (!$url || ($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['fragment'])) {
        $form_state->setErrorByName('webhook][url', $this->t('Enter an HTTPS webhook URL without embedded credentials or a fragment.'));
      }
      if (!preg_match('/^[a-f0-9]{64}$/D', $values['token'] ?: ($saved['token'] ?? ''))) {
        $form_state->setErrorByName('webhook][token', $this->t('Enter the 64-character secret token from Drools.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('webhook');
    $saved = \Drupal::state()->get(WebhookSender::SETTINGS, []);
    \Drupal::state()->set(WebhookSender::SETTINGS, [
      'enabled' => (bool) $values['enabled'],
      'url' => $values['url'],
      'token' => $values['token'] ?: ($saved['token'] ?? ''),
      'interval' => (int) $values['interval'],
    ]);
    \Drupal::state()->delete('status_feed.webhook_next_attempt');
    // Retrieve the configuration and set the submitted configuration setting(s)
    $this->configFactory->getEditable(static::SETTINGS)->set('cachetime', $form_state->getValue('cachetime'))->save();
    $this->configFactory->getEditable(static::SETTINGS)->set('activate_cron', $form_state->getValue('activate_cron'))->save();

    parent::submitForm($form, $form_state);
  }

  public function sendNow(array &$form, FormStateInterface $form_state) {
    if (\Drupal::service('status_feed.webhook_sender')->send(TRUE)) {
      $this->messenger()->addStatus($this->t('Status feed accepted by Drools. It will be processed on the next surveillance import.'));
    }
    else {
      $this->messenger()->addError($this->t('Feed was not sent. Enable sending and check the webhook settings and Drupal logs.'));
    }
  }

}
