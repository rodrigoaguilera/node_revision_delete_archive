<?php

namespace Drupal\node_revision_delete_archive;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface RevisionArchiveStorageManagerInterface
 * @package Drupal\node_revision_delete_archive
 */
interface RevisionArchiveStorageManagerInterface {

  /**
   * Archives an entity revision.
   * @param EntityInterface $revision
   * @return boolean
   */
  public function archive(EntityInterface $revision);
}
