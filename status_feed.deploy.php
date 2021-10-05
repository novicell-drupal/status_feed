<?php

use Drupal\atoms\Atom;
use Drupal\dds_global_content\GlobalContent;

/**
 * Migrate from DDS Monitor to Status Feed if exists
 */
function status_feed_deploy_migrate_monitor() {
  /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
  $module_handler = \Drupal::service('module_handler');
  if ($module_handler->moduleExists('dds_monitor')) {
    $config_factory = \Drupal::configFactory();
    $old_config = \Drupal::config('dds_monitor.settings');
    if (!$old_config->isNew()) {
      $new_config = $config_factory->getEditable('status_feed.settings');
      foreach ($old_config->getRawData() as $key => $value) {
        $new_config->set($key, $value);
      }
      $new_config->save();
    }

    $config = $config_factory->getEditable('user.role.monitor');
    if (!$config->isNew()) {
      $config->set('dependencies', ['enforced' => ['module' => ['status_feed']]]);
      $config->save();
    }

    $list = $config_factory->listAll('user.role.');
    foreach ($list as $config_id) {
      $config = $config_factory->getEditable($config_id);
      $permissions = $config->get('permissions');
      $updated = FALSE;
      foreach ($permissions as $key => $permission) {
        switch($permission) {
          case 'administer dds monitor data':
            $permissions[$key] = 'administer status feed data';
            $updated = TRUE;
            break;

          case 'access dds monitor feed':
            $permissions[$key] = 'access status feed';
            $updated = TRUE;
            break;
        }
      }
      if ($updated) {
        $config->set('permissions', $permissions);
        $config->save();
      }
    }

    $uids = \Drupal::entityQuery('user')->condition('mail', 'phpmonitor@novicell.dk')->execute();
    if ($uids) {
      $users = \Drupal\user\Entity\User::loadMultiple($uids);
      foreach ($users as $user) {
        $user->delete();
      }
    }

    /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->uninstall(['dds_monitor']);
    return t('Migrated from DDS Monitor');
  }
}
