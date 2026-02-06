<?php

declare(strict_types=1);

/**
 * Civix helper bootstrap (minimal scaffold for this extension).
 */
function _civirulesfellowshipaccepted_civix_civicrm_config(&$config): void {
  // No runtime config customization required.
}

/**
 * Civix helper: install.
 */
function _civirulesfellowshipaccepted_civix_civicrm_install(): void {
  CRM_Civirulesfellowshipaccepted_Upgrader::instance()->install();
}

/**
 * Civix helper: enable.
 */
function _civirulesfellowshipaccepted_civix_civicrm_enable(): void {
  CRM_Civirulesfellowshipaccepted_Upgrader::instance()->enable();
}

/**
 * Civix helper: disable.
 */
function _civirulesfellowshipaccepted_civix_civicrm_disable(): void {
  CRM_Civirulesfellowshipaccepted_Upgrader::instance()->disable();
}

/**
 * Civix helper: uninstall.
 */
function _civirulesfellowshipaccepted_civix_civicrm_uninstall(): void {
  CRM_Civirulesfellowshipaccepted_Upgrader::instance()->uninstall();
}

/**
 * Civix helper: upgrade.
 */
function _civirulesfellowshipaccepted_civix_civicrm_upgrade($op, ?CRM_Queue_Queue $queue = null): bool {
  return CRM_Civirulesfellowshipaccepted_Upgrader::instance()->upgrade($op, $queue);
}
