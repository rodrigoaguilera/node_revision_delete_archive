<?php

namespace Drupal\node_revision_delete_archive\Plugin\RevisionArchiveStorage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\node_revision_delete_archive\RevisionArchiveStoragePluginBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @RevisionArchiveStorage(
 *   id = "revision_archive_storage_fs",
 *   label = @Translation("File storage"),
 *   description = @Translation("Stores the deleted revisions on a file storage."),
 * )
 */
class RevisionArchiveStorageFS extends RevisionArchiveStoragePluginBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $filesystem;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * RevisionArchiveStorageFS constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param StreamWrapperManagerInterface $stream_wrapper_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $filesystem, SerializerInterface $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->filesystem = $filesystem;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'stream_wrapper' => 'public',
        'base_folder' => '/content_archive',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    $form['stream_wrapper'] = [
      '#type' => 'radios',
      '#title' => $this->t('Stream wrapper'),
      '#options' => $options,
      '#default_value' => $this->configuration['stream_wrapper']
    ];
    $form['base_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Archive folder'),
      '#default_value' => $this->configuration['base_folder'],
      '#description' => $this->t('The folder where the archived content will be stored. Example: content_archive'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['stream_wrapper'] = $form_state->getValue('stream_wrapper');
    $this->configuration['base_folder'] = $form_state->getValue('base_folder');
  }

  /**
   * {@inheritDoc}
   */
  public function archive(EntityInterface $revision) {
    $destinationFolder = $this->prepareDestinationFolder($revision);
    $this->doArchive($revision, $destinationFolder);
  }

  /**
   * Prepares the destination folder of the revision.
   * @param EntityInterface $revision
   * @return FALSE|string
   * If the folder was prepared, it is returned. Otherwise, FALSE is returned.
   */
  protected function prepareDestinationFolder(EntityInterface $revision) {
    $streamPath = $this->configuration['stream_wrapper'] . "://";
    $baseFolder = $this->configuration['base_folder'];
    if (!empty($baseFolder)) {
      $baseFolder .= '/';
    }
    $destinationFolder = $streamPath . $baseFolder . $revision->getEntityTypeId();

    // Append the revision workspace to the destination folder.
    if ($revision instanceof FieldableEntityInterface && $revision->hasField('workspace')) {
      if (!$revision->get('workspace')->isEmpty()) {
        $destinationFolder .= '/' . $revision->get('workspace')->referencedEntities()[0]->id();
      }
    }

    // Append the language to the destination folder.
    $destinationFolder .= '/' . $revision->language()->getId();
    $prepared = $this->filesystem->prepareDirectory($destinationFolder, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$prepared) {
      return FALSE;
    }
    return $destinationFolder;
  }

  /**
   * Performs the actual archive of the entity revision.
   *
   * @param EntityInterface $revision
   * The entity revision to be archived.
   * @param $destinationFolder
   * The destination folder.
   * @param $filename
   * Optional, a filename. If empty, the id of the revision will be used.
   */
  protected function doArchive(EntityInterface $revision, $destinationFolder, $filename = NULL) {
    // Use the id of the entity if no filename is specified.
    if (empty($filename)) {
      $filename = $revision->id() . '.json';
    }
    // When serializing the content, we want to export all the possible fields,
    // so put the user 1 in the context.
    $context = [
      'account' => User::load(1),
    ];
    $output = $this->serializer->serialize($revision, 'json', $context);
    // Append the serialized output to the file.
    file_put_contents($destinationFolder . '/' . $filename, $output . PHP_EOL, FILE_APPEND | LOCK_EX);
  }
}
