<?php

namespace Drupal\node_revision_delete_archive\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\node_revision_delete_archive\RevisionArchiveStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArchiveStorageSettingsForm extends ConfigFormBase {

  /**
   * The revision archive storage plugin manager.
   *
   * @var \Drupal\node_revision_delete_archive\RevisionArchiveStorageManager
   */
  protected $revisionArchiveStorageManager;

  /**
   * ArchiveStorageSettingsForm constructor.
   * @param ConfigFactoryInterface $config_factory
   * @param RevisionArchiveStorageManager $revision_archive_storage_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, RevisionArchiveStorageManager $revision_archive_storage_manager) {
    $this->revisionArchiveStorageManager = $revision_archive_storage_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.node_revision_delete_archive_storage')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_revision_delete_archive.settings'
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'node_revision_delete_archive_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_revision_delete_archive.settings');
    $current_storage = $config->get('storage');

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'node_revision_delete_archive/settings';
    // Add vertical tabs containing the settings for the plugins.
    $form['plugin_settings'] = [
      '#title' => $this->t('Storage settings'),
      '#type' => 'vertical_tabs',
    ];

    $plugins = $this->getAllRevisionArchiveStoragePlugins($current_storage);
    $form['storage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Archive storages'),
      '#description' => $this->t('Select the storages where the deleted content revisions will be archived.'),
      '#weight' => -1,
      '#attributes' => [
        'class' => [
          'archive-storage-wrapper',
        ],
      ]
    ];
    foreach ($plugins as $plugin_id => $plugin) {
      $clean_css_id = Html::cleanCssIdentifier($plugin->getPluginId());
      $form['storage'][$plugin->getPluginId()] = [
        '#type' => 'checkbox',
        '#title' => $plugin->label(),
        '#description' => $plugin->getDescription(),
        '#default_value' => !empty($current_storage[$plugin_id]),
        '#attributes' => [
          'class' => [
            'archive-storage-' . $clean_css_id,
          ],
          'data-id' => $clean_css_id,
        ],
      ];

      // If the plugin support settings, show the settings form.
      if ($plugin instanceof PluginFormInterface) {
        $form['settings'][$plugin_id] = [
          '#type' => 'details',
          '#title' => $plugin->label(),
          '#group' => 'plugin_settings',
          '#attributes' => [
            'class' => [
              'archive-storage-settings--' . $clean_css_id,
            ],
          ],
        ];
        $plugin_form_state = SubformState::createForSubform($form['settings'][$plugin_id], $form, $form_state);
        $form['settings'][$plugin_id] += $plugin->buildConfigurationForm($form['settings'][$plugin_id], $plugin_form_state);
      }
      else {
        unset($form['settings'][$plugin_id]);
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = [];
    $plugins = $this->getAllRevisionArchiveStoragePlugins();
    foreach ($plugins as $plugin_id => $plugin) {
      if (empty($values['storage'][$plugin_id])) {
        continue;
      }
      if ($plugin instanceof PluginFormInterface) {
        $plugin_form_state = SubformState::createForSubform($form['settings'][$plugin_id], $form, $form_state);
        $plugin->submitConfigurationForm($form['settings'][$plugin_id], $plugin_form_state);
      }
      $storage[$plugin_id] = $plugin->getConfiguration();
    }

    $this->configFactory->getEditable('node_revision_delete_archive.settings')
      ->set('storage', $storage)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns all the available archive storage plugins.
   */
  protected function getAllRevisionArchiveStoragePlugins($plugins_configuration = []) {
    $plugins = [];
    foreach ($this->revisionArchiveStorageManager->getDefinitions() as $id => $definition) {
      $plugin_conf = !empty($plugins_configuration[$id]) ? $plugins_configuration[$id] : [];
      $plugins[$id] = $this->revisionArchiveStorageManager->createInstance($id, $plugin_conf);
    }
    return $plugins;
  }
}
