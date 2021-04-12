<?php

namespace Drupal\status_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration and set the submitted configuration setting(s)
    $this->configFactory->getEditable(static::SETTINGS)->set('cachetime', $form_state->getValue('cachetime'))->save();
    $this->configFactory->getEditable(static::SETTINGS)->set('activate_cron', $form_state->getValue('activate_cron'))->save();

    parent::submitForm($form, $form_state);
  }

}
