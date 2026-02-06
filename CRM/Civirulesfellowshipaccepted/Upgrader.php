<?php

declare(strict_types=1);

/**
 * Extension upgrader.
 */
class CRM_Civirulesfellowshipaccepted_Upgrader extends CRM_Extension_Upgrader_Base {

  public function install(): void {
    parent::install();

    $this->registerCiviRulesCondition();
  }

  public function enable(): void {
    parent::enable();

    // Re-register on enable in case CiviRules was installed/enabled later.
    $this->registerCiviRulesCondition();
  }

  /**
   * Upgrade hook to keep condition registration synchronized.
   */
  public function upgrade_1001(): bool {
    $this->registerCiviRulesCondition();
    return true;
  }

  private function registerCiviRulesCondition(): void {
    if (!class_exists('CRM_Civirules_Utils_Upgrader')) {
      return;
    }

    CRM_Civirules_Utils_Upgrader::insertConditionsFromJson(
      $this->extensionDir . DIRECTORY_SEPARATOR . 'civirules_conditions.json'
    );
  }

}
