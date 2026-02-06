<?php

declare(strict_types=1);

/**
 * Condition: pass only when Fellowship accepted (custom field 115) changed to Yes.
 */
class CRM_Civirulesfellowshipaccepted_CivirulesConditions_Contact_FellowshipAcceptedChangedToYes extends CRM_Civirules_Condition {

  private const TARGET_CUSTOM_FIELD_ID = 115;
  private const TARGET_CUSTOM_GROUP_ID = 4;
  private const TARGET_ENTITY = 'Custom_Fellowships';
  private const TARGET_FIELD_NAME = 'Fellowship_accepted_In_use_from_2026_';

  /**
   * No condition UI config required.
   *
   * @param int $ruleConditionId
   *
   * @return bool|string
   */
  public function getExtraDataInputUrl($ruleConditionId): bool|string {
    return false;
  }

  /**
   * Restrict to Custom Data changed triggers on Contact.
   */
  public function doesWorkWithTrigger(CRM_Civirules_Trigger $trigger, CRM_Civirules_BAO_Rule $rule): bool {
    if (!$trigger->doesProvideEntity('Contact')) {
      return false;
    }

    return $this->isCustomDataContactChangedTrigger($trigger);
  }

  /**
   * Evaluate whether field 115 changed to a truthy Yes value.
   *
   * For "Custom Data on Contact (of any Type) Changed", CiviRules stores payload data in
   * trigger context arrays (entity/original data). Depending on trigger internals, the payload
   * can expose:
   * - explicit field identifiers (e.g. custom_field_id/field_id)
   * - direct field keys (e.g. custom_115 or API field name)
   * - optional row id for the edited multi-record row (id/row_id/entity_id).
   */
  public function isConditionValid(CRM_Civirules_TriggerData_TriggerData $triggerData): bool {
    $context = $this->extractChangedFieldContext($triggerData);

    if (!$context['targetFieldDetected']) {
      return false;
    }

    if ($context['hasNewValue']) {
      return $this->isTruthyNewValue($context['newValue']);
    }

    // Fallback only if payload omitted the new value but we did find a row id.
    // The row id is expected from the custom data row context (id/row_id/entity_id).
    if ($context['rowId'] === null) {
      return false;
    }

    $value = $this->fetchCurrentValueFromApiV4($context['rowId']);
    return $value === true;
  }

  /**
   * Collect field id/new value/row id from the trigger payload.
   *
   * Pass 1 (trusted): current entity payloads, where direct field keys may represent NEW values.
   * Pass 2 (defensive): original/object-dump payloads, used only for field detection + row id;
   * NEW values are read only from explicit transition keys to avoid mixing in original values.
   *
   * @return array{targetFieldDetected:bool,hasNewValue:bool,newValue:mixed,rowId:?int}
   */
  private function extractChangedFieldContext(CRM_Civirules_TriggerData_TriggerData $triggerData): array {
    $context = [
      'targetFieldDetected' => false,
      'hasNewValue' => false,
      'newValue' => null,
      'rowId' => null,
    ];

    $currentSources = [];
    foreach ([self::TARGET_ENTITY, 'CustomValue', 'CustomData', 'Contact'] as $entityName) {
      $entityData = $this->safeGetEntityData($triggerData, $entityName);
      if (is_array($entityData)) {
        $currentSources[] = $entityData;
      }
    }

    foreach ($currentSources as $source) {
      // Trusted sources: allow direct field-key values as NEW values.
      $this->collectContextFromNode($source, $context, [], true, true);
      if ($context['targetFieldDetected'] && $context['hasNewValue']) {
        return $context;
      }
    }

    $fallbackSources = [];
    foreach ([self::TARGET_ENTITY, 'CustomValue', 'CustomData', 'Contact'] as $entityName) {
      $originalEntityData = $this->safeGetOriginalEntityData($triggerData, $entityName);
      if (is_array($originalEntityData)) {
        $fallbackSources[] = $originalEntityData;
      }
    }

    $originalData = $this->safeGetOriginalData($triggerData);
    if (is_array($originalData)) {
      $fallbackSources[] = $originalData;
    }

    $fallbackSources[] = $this->normalizeArrayKeys((array) $triggerData);

    foreach ($fallbackSources as $source) {
      // Defensive sources: do not treat plain field-key values as NEW values.
      $this->collectContextFromNode($source, $context, [], false, false);

      if ($context['targetFieldDetected'] && $context['hasNewValue']) {
        break;
      }
    }

    return $context;
  }

  /**
   * Recursive payload scan.
   *
   * - Reads explicit field identifiers from payload keys like custom_field_id/field_id.
   * - Reads inferred field identifiers from keys like custom_115 and API field key names.
   * - Reads row ids when the node/path indicates Fellowship custom-row context.
   *
   * @param array{targetFieldDetected:bool,hasNewValue:bool,newValue:mixed,rowId:?int} $context
   * @param array<int,string> $path
   * @param bool $allowDirectFieldValueAsNewValue
   * @param bool $allowGenericValueKey
   */
  private function collectContextFromNode(
    mixed $node,
    array &$context,
    array $path,
    bool $allowDirectFieldValueAsNewValue,
    bool $allowGenericValueKey
  ): void {
    if (!is_array($node)) {
      return;
    }

    $node = $this->normalizeArrayKeys($node);

    $nodeTargetsField = false;

    foreach ($node as $key => $value) {
      if (!is_string($key)) {
        continue;
      }

      $explicitFieldId = $this->extractExplicitFieldId($key, $value);
      if ($explicitFieldId === self::TARGET_CUSTOM_FIELD_ID) {
        $nodeTargetsField = true;
      }

      $inferredFieldId = $this->extractInferredFieldId($key, $value);
      if ($inferredFieldId === self::TARGET_CUSTOM_FIELD_ID) {
        $nodeTargetsField = true;
      }
    }

    if ($nodeTargetsField) {
      $context['targetFieldDetected'] = true;
      $valueKeys = ['new_value', 'newValue', 'to'];
      if ($allowGenericValueKey) {
        $valueKeys[] = 'value';
      }

      foreach ($valueKeys as $valueKey) {
        if (array_key_exists($valueKey, $node)) {
          $context['hasNewValue'] = true;
          $context['newValue'] = $node[$valueKey];
          break;
        }
      }
    }

    if ($allowDirectFieldValueAsNewValue && !$context['hasNewValue']) {
      foreach ($node as $key => $value) {
        if (!is_string($key)) {
          continue;
        }

        if ($this->extractInferredFieldId($key, $value) === self::TARGET_CUSTOM_FIELD_ID) {
          $context['targetFieldDetected'] = true;
          $context['hasNewValue'] = true;
          $context['newValue'] = $value;
          break;
        }
      }
    }

    if ($context['rowId'] === null) {
      $context['rowId'] = $this->extractRowIdFromNode($node, $path);
    }

    foreach ($node as $key => $value) {
      if (!is_array($value) || !is_string($key)) {
        continue;
      }

      $nextPath = $path;
      $nextPath[] = $key;
      $this->collectContextFromNode(
        $value,
        $context,
        $nextPath,
        $allowDirectFieldValueAsNewValue,
        $allowGenericValueKey
      );
    }
  }

  /**
   * Explicit field identifiers from trigger payload metadata.
   */
  private function extractExplicitFieldId(string $key, mixed $value): ?int {
    $normalizedKey = strtolower($key);
    if (!in_array($normalizedKey, ['custom_field_id', 'field_id', 'customfieldid', 'field'], true)) {
      return null;
    }

    if (is_string($value) && strcasecmp(trim($value), self::TARGET_FIELD_NAME) === 0) {
      return self::TARGET_CUSTOM_FIELD_ID;
    }

    if (is_string($value) && preg_match('/^custom_(\d+)(?:$|_)/i', trim($value), $matches) === 1) {
      return (int) $matches[1];
    }

    // "field" is often generic metadata and should not parse arbitrary numeric values.
    if ($normalizedKey === 'field') {
      return null;
    }

    return $this->parseInt($value);
  }

  /**
   * Inferred field identifiers from key names.
   */
  private function extractInferredFieldId(string $key, mixed $value): ?int {
    $trimmedKey = trim($key);
    if (preg_match('/^custom_(\d+)(?:$|_)/i', $trimmedKey, $matches) === 1) {
      return (int) $matches[1];
    }

    if (strcasecmp($trimmedKey, self::TARGET_FIELD_NAME) === 0) {
      return self::TARGET_CUSTOM_FIELD_ID;
    }

    return null;
  }

  /**
   * Identify the specific CiviRules trigger family this condition is intended for.
   */
  private function isCustomDataContactChangedTrigger(CRM_Civirules_Trigger $trigger): bool {
    $candidates = [strtolower(get_class($trigger))];

    foreach (['getName', 'getLabel', 'getDescription'] as $method) {
      if (!method_exists($trigger, $method)) {
        continue;
      }
      try {
        $value = $trigger->{$method}();
        if (is_scalar($value)) {
          $candidates[] = strtolower((string) $value);
        }
      }
      catch (Throwable $exception) {
        // Ignore and continue matching with other metadata.
      }
    }

    foreach ($candidates as $candidate) {
      $hasCustom = str_contains($candidate, 'custom');
      $hasData = str_contains($candidate, 'data');
      $hasContact = str_contains($candidate, 'contact');
      $hasChange = str_contains($candidate, 'chang');
      if ($hasCustom && $hasData && $hasContact && $hasChange) {
        return true;
      }
    }

    return false;
  }

  /**
   * Detect row id for the edited Fellowship custom-data row when present.
   */
  private function extractRowIdFromNode(array $node, array $path): ?int {
    // Be strict: only accept row ids from nodes that explicitly identify the target entity.
    if (!$this->nodeHasExplicitTargetEntityMarker($node, $path)) {
      return null;
    }

    foreach (['row_id', 'id', 'entity_id', 'record_id', 'custom_value_id'] as $idKey) {
      if (!array_key_exists($idKey, $node)) {
        continue;
      }

      $candidate = $this->parseInt($node[$idKey]);
      if ($candidate !== null) {
        return $candidate;
      }
    }

    return null;
  }

  /**
   * Strictly identify whether a payload node/path explicitly represents Fellowship row context.
   */
  private function nodeHasExplicitTargetEntityMarker(array $node, array $path): bool {
    if (isset($node['custom_group_id']) && (int) $node['custom_group_id'] === self::TARGET_CUSTOM_GROUP_ID) {
      return true;
    }

    foreach (array_keys($node) as $key) {
      if (is_string($key) && strcasecmp($key, self::TARGET_ENTITY) === 0) {
        return true;
      }
    }

    foreach (['entity', 'entity_name', 'entityName', 'custom_entity'] as $entityKey) {
      if (!isset($node[$entityKey]) || !is_scalar($node[$entityKey])) {
        continue;
      }

      if (strcasecmp((string) $node[$entityKey], self::TARGET_ENTITY) === 0) {
        return true;
      }
    }

    return $this->pathHasExactTargetEntity($path);
  }

  private function pathHasExactTargetEntity(array $path): bool {
    foreach ($path as $part) {
      if (strcasecmp((string) $part, self::TARGET_ENTITY) === 0) {
        return true;
      }
    }

    return false;
  }

  private function safeGetEntityData(CRM_Civirules_TriggerData_TriggerData $triggerData, string $entityName): mixed {
    if (!method_exists($triggerData, 'getEntityData')) {
      return null;
    }

    try {
      return $triggerData->getEntityData($entityName);
    }
    catch (Throwable $exception) {
      return null;
    }
  }

  private function safeGetOriginalEntityData(CRM_Civirules_TriggerData_TriggerData $triggerData, string $entityName): mixed {
    if (!method_exists($triggerData, 'getOriginalEntityData')) {
      return null;
    }

    try {
      return $triggerData->getOriginalEntityData($entityName);
    }
    catch (Throwable $exception) {
      return null;
    }
  }

  private function safeGetOriginalData(CRM_Civirules_TriggerData_TriggerData $triggerData): mixed {
    if (!method_exists($triggerData, 'getOriginalData')) {
      return null;
    }

    try {
      return $triggerData->getOriginalData();
    }
    catch (Throwable $exception) {
      return null;
    }
  }

  private function normalizeArrayKeys(array $node): array {
    $normalized = [];
    foreach ($node as $key => $value) {
      if (is_string($key) && str_contains($key, "\0")) {
        $parts = explode("\0", $key);
        $key = (string) end($parts);
      }

      if (is_array($value)) {
        $value = $this->normalizeArrayKeys($value);
      }

      $normalized[$key] = $value;
    }

    return $normalized;
  }

  private function parseInt(mixed $value): ?int {
    if (is_int($value)) {
      return $value;
    }

    if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
      return (int) trim($value);
    }

    return null;
  }

  private function isTruthyNewValue(mixed $value): bool {
    if ($value === true || $value === 1) {
      return true;
    }

    if (!is_string($value)) {
      return false;
    }

    $normalized = strtolower(trim($value));
    return $normalized === '1' || $normalized === 'true';
  }

  private function fetchCurrentValueFromApiV4(int $rowId): ?bool {
    try {
      $result = civicrm_api4(self::TARGET_ENTITY, 'get', [
        'select' => [self::TARGET_FIELD_NAME],
        'where' => [['id', '=', $rowId]],
        'limit' => 1,
        'checkPermissions' => true,
      ]);
    }
    catch (Throwable $exception) {
      return null;
    }

    $row = $this->extractFirstApiRow($result);
    if ($row === null || !array_key_exists(self::TARGET_FIELD_NAME, $row)) {
      return null;
    }

    $value = $row[self::TARGET_FIELD_NAME];
    if (is_bool($value)) {
      return $value;
    }

    return $this->isTruthyNewValue($value);
  }

  private function extractFirstApiRow(mixed $result): ?array {
    if (is_array($result)) {
      if ($result === []) {
        return null;
      }

      if (array_is_list($result)) {
        $row = $result[0] ?? null;
        return is_array($row) ? $row : null;
      }

      if (isset($result['values']) && is_array($result['values']) && $result['values'] !== []) {
        $values = array_values($result['values']);
        return is_array($values[0] ?? null) ? $values[0] : null;
      }
    }

    if ($result instanceof Traversable) {
      foreach ($result as $row) {
        if (is_array($row)) {
          return $row;
        }
      }
    }

    if (is_object($result) && method_exists($result, 'first')) {
      $row = $result->first();
      return is_array($row) ? $row : null;
    }

    return null;
  }

}
