<?php

declare(strict_types=1);

require_once __DIR__ . '/civirulesfellowshipaccepted.civix.php';

/**
 * Implements hook_civicrm_config().
 */
function civirulesfellowshipaccepted_civicrm_config(&$config): void {
  _civirulesfellowshipaccepted_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 */
function civirulesfellowshipaccepted_civicrm_install(): void {
  _civirulesfellowshipaccepted_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 */
function civirulesfellowshipaccepted_civicrm_enable(): void {
  _civirulesfellowshipaccepted_civix_civicrm_enable();
}
