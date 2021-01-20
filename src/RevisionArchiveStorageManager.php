<?php

namespace Drupal\node_revision_delete_archive;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Archiver plugin manager.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class RevisionArchiveStorageManager extends DefaultPluginManager implements RevisionArchiveStorageManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a RevisionArchiveStorageManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct(
      'Plugin/RevisionArchiveStorage',
      $namespaces,
      $module_handler,
      'Drupal\node_revision_delete_archive\RevisionArchiveStorageInterface',
      'Drupal\node_revision_delete_archive\Annotation\RevisionArchiveStorage'
    );
    $this->alterInfo('revision_archive_storage_info');
    $this->setCacheBackend($cache_backend, 'revision_archive_storage_info_plugins');
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritDocs}
   */
  public function archive(EntityInterface $revision) {
    $config = $this->configFactory->get('node_revision_delete_archive.settings');
    $storage_settings = $config->get('storage');
    foreach ($this->getDefinitions() as $id => $definition) {
      // If the plugin is not enabled, ignore it.
      if (!isset($storage_settings[$id])) {
        continue;
      }
      // Create an instance of the plugin and call the archive method.
      $plugin_conf = !empty($storage_settings[$id]) ? $storage_settings[$id] : [];
      $plugin = $this->createInstance($id, $plugin_conf);
      if ($revision instanceof TranslatableInterface) {
        foreach ($this->languageManager->getLanguages() as $language) {
          if ($revision->hasTranslation($language->getId())) {
            $plugin->archive($revision->getTranslation($language->getId()));
          }
        }
      } else {
        $plugin->archive($revision);
      }
    }
  }
}
