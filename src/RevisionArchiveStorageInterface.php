<?php

namespace Drupal\node_revision_delete_archive;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface RevisionArchiveStorageInterface
 * @package Drupal\node_revision_delete_archive
 */
interface RevisionArchiveStorageInterface {

  /**
   * Returns the label of the plugin.
   * @return string
   */
  public function label();

  /**
   * Returns the description of the plugin.
   * @return string
   */
  public function getDescription();

  /**
   * Returns the configuration of the plugin instance.
   * @return array
   */
  public function getConfiguration();

  /**
   * Sets the configuration of the plugin instance.
   * @param array $configuration
   */
  public function setConfiguration(array $configuration);

  /**
   * Returns the default configuration of the plugin.
   * @return array
   */
  public function defaultConfiguration();

  /**
   * Archives an entity revision.
   * @param EntityInterface $revision
   * @return boolean
   */
  public function archive(EntityInterface $revision);
}
