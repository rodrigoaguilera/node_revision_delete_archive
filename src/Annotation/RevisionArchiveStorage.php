<?php

namespace Drupal\node_revision_delete_archive\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RevisionArchiveStorage annotation object.
 *
 * @see \Drupal\Core\Field\FormatterPluginManager
 * @see \Drupal\Core\Field\FormatterInterface
 *
 * @Annotation
 */
class RevisionArchiveStorage extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the archive storage
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the archive storage.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $description;

}
