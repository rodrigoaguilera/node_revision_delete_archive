<?php

namespace Drupal\node_revision_delete_archive;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for the revision archive storage pluginss.
 * Class RevisionArchiveStoragePluginBase
 * @package Drupal\node_revision_delete_archive
 */
abstract class RevisionArchiveStoragePluginBase extends PluginBase implements RevisionArchiveStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['description']) ? $plugin_definition['description'] : '';
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [];
  }
}
