<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase implements SerializerAwareInterface, CacheableNormalizerInterface {

  use SerializerAwareTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass;

  /**
   * List of formats which supports (de-)normalization.
   *
   * @var string|string[]
   */
  protected $format;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    $supported = (array) $this->supportedInterfaceOrClass;

    return (bool) array_filter($supported, function ($name) use ($data) {
      return $data instanceof $name;
    });
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   *
   * This class doesn't implement DenormalizerInterface, but most of its child
   * classes do, so this method is implemented at this level to reduce code
   * duplication.
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    // If the format is not supported return now.
    if (!$this->checkFormat($format)) {
      return FALSE;
    }

    $supported = (array) $this->supportedInterfaceOrClass;

    $subclass_check = function ($name) use ($type) {
      return (class_exists($name) || interface_exists($name)) && is_subclass_of($type, $name, TRUE);
    };

    return in_array($type, $supported) || array_filter($supported, $subclass_check);
  }

  /**
   * Checks if the provided format is supported by this normalizer.
   *
   * @param string $format
   *   The format to check.
   *
   * @return bool
   *   TRUE if the format is supported, FALSE otherwise. If no format is
   *   specified this will return TRUE.
   */
  protected function checkFormat($format = NULL) {
    if (!isset($format) || !isset($this->format)) {
      return TRUE;
    }

    return in_array($format, (array) $this->format, TRUE);
  }

  /**
   * Adds cacheability if applicable.
   *
   * @param array $context
   *   Context options for the normalizer.
   * @param $data
   *   The data that might have cacheability information.
   */
  protected function addCacheableDependency(array $context, $data) {
    if ($data instanceof CacheableDependencyInterface && isset($context[static::SERIALIZATION_CONTEXT_CACHEABILITY])) {
      $context[static::SERIALIZATION_CONTEXT_CACHEABILITY]->addCacheableDependency($data);
    }
  }
  
  /**
     * Checks if there is a serialized string for a column.
     *
     * @param mixed $data
     *   The field item data to denormalize.
     * @param string $class
     *   The expected class to instantiate.
     * @param \Drupal\Core\Field\FieldItemInterface $field_item
     *   The field item.
     */
  protected function pmCheckForSerializedStrings($data, $class, FieldItemInterface $field_item) {
    // Require specialized denormalizers for fields with 'serialize' columns.
    // Note: this cannot be checked in ::supportsDenormalization() because at
    //       that time we only have the field item class. ::hasSerializeColumn()
    //       must be able to call $field_item->schema(), which requires a field
    //       storage definition. To determine that, the entity type and bundle
    //       must be known, which is contextual information that the Symfony
    //       serializer does not pass to ::supportsDenormalization().
    if (!is_array($data)) {
      $data = [$field_item->getDataDefinition()->getMainPropertyName() => $data];
    }
    if ($this->pmDataHasStringForSerializeColumn($field_item, $data)) {
      $field_name = $field_item->getParent() ? $field_item->getParent()->getName() : $field_item->getName();
      throw new \LogicException(sprintf('The generic FieldItemNormalizer cannot denormalize string values for "%s" properties of the "%s" field (field item class: %s).', implode('", "', $this->pmGetSerializedPropertyNames($field_item)), $field_name, $class));
    }
  }

  /**
     * Checks if the data contains string value for serialize column.
     *
     * @param \Drupal\Core\Field\FieldItemInterface $field_item
     *   The field item.
     * @param array $data
     *   The data being denormalized.
     *
     * @return bool
     *   TRUE if there is a string value for serialize column, otherwise FALSE.
     */
  protected function pmDataHasStringForSerializeColumn(FieldItemInterface $field_item, array $data) {
    foreach ($this->pmGetSerializedPropertyNames($field_item) as $property_name) {
      if (isset($data[$property_name]) && is_string($data[$property_name])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
     * Gets the names of all serialized properties.
     *
     * @param \Drupal\Core\Field\FieldItemInterface $field_item
     *   The field item.
     *
     * @return string[]
     *   The property names for serialized properties.
     */
  protected function pmGetSerializedPropertyNames(FieldItemInterface $field_item) {
    $field_storage_definition = $field_item->getFieldDefinition()->getFieldStorageDefinition();

    if ($custom_property_names = $this->pmGetCustomSerializedPropertyNames($field_item)) {
      return $custom_property_names;
    }

    $field_storage_schema = $field_item->schema($field_storage_definition);
    // If there are no columns then there are no serialized properties.
    if (!isset($field_storage_schema['columns'])) {
      return [];
    }
    $serialized_columns = array_filter($field_storage_schema['columns'], function ($column_schema) {
      return isset($column_schema['serialize']) && $column_schema['serialize'] === TRUE;
    });
    return array_keys($serialized_columns);
  }

  /**
     * Gets the names of all properties the plugin treats as serialized data.
     *
     * This allows the field storage definition or entity type to provide a
     * setting for serialized properties. This can be used for fields that
     * handle serialized data themselves and do not rely on the serialized schema
     * flag.
     *
     * @param \Drupal\Core\Field\FieldItemInterface $field_item
     *   The field item.
     *
     * @return string[]
     *   The property names for serialized properties.
     */
  protected function pmGetCustomSerializedPropertyNames(FieldItemInterface $field_item) {
    if ($field_item instanceof PluginInspectionInterface) {
      $definition = $field_item->getPluginDefinition();
      $serialized_fields = $field_item->getEntity()->getEntityType()->get('serialized_field_property_names');
      $field_name = $field_item->getFieldDefinition()->getName();
      if (is_array($serialized_fields) && isset($serialized_fields[$field_name]) && is_array($serialized_fields[$field_name])) {
        return $serialized_fields[$field_name];
      }
      if (isset($definition['serialized_property_names']) && is_array($definition['serialized_property_names'])) {
        return $definition['serialized_property_names'];
      }
    }
    return [];
  }

}
