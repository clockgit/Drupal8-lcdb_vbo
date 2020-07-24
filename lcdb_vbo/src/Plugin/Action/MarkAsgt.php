<?php


namespace Drupal\lcdb_vbo\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\vbo_export\Plugin\Action\VboExportCsv;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;

//TODO track who/when for each entity
/**
 * Marks Enrollment as ASGT and Generates csv
 *
 * @Action(
 *   id = "lcdb_vbo_mark_asgt",
 *   label = @Translation("Export CSV & Tag"),
 *   type = "kids_data",
 *   pass_context = TRUE,
 *   pass_view = TRUE
 * )
 */
class MarkAsgt extends VboExportCsv {
  const THEME = 'lcdb_vbo_export_content_csv';
  const EXTENSION = 'csv';

  /**
   * Execute multiple handler.
   *
   * Execute action on multiple entities to generate csv output
   * and display a download link.
   */
  public function executeMultiple(array $entities) {
    $dt = date('Y-m-d\T00:00:00', time());
    //ksm($entities);
    //ksm([$this, $entities]);
    foreach ($entities as $entity) {
      if($entity->hasfield('field_kids')) {
        $entity->set('field_kids', $this->configuration['step']);
        if($entity->hasfield('field_kids_date')) {
          $entity->set('field_kids_date', $dt);
        }
        $entity->save();
      }
    }
    // Build output header array.
    if (!isset($this->context['sandbox']['header'])) {
      $this->context['sandbox']['header'] = [];
    }
    $header = &$this->context['sandbox']['header'];

    if (empty($header)) {
      foreach ($this->view->field as $id => $field) {
        // Skip Views Bulk Operations field and excluded fields.
        if ($field->options['plugin_id'] === 'views_bulk_operations_bulk_form' || $field->options['exclude'] || $field->options['label'][0] == '#') {
          continue;
        }
        $header[$id] = $field->options['label'];
      }
    }

    if (!empty($header) && !empty($this->view->result)) {
      // Render rows.
      $this->view->style_plugin->preRender($this->view->result);

      if (!isset($this->context['sandbox']['rows'])) {
        $this->context['sandbox']['rows'] = [];
      }

      $index = count($this->context['sandbox']['rows']);
      foreach ($this->view->result as $num => $row) {
        foreach ($header as $field_id => $label) {
          $this->context['sandbox']['rows'][$index][$field_id] = (string) $this->view->style_plugin->getField($num, $field_id);
        }
        $index++;
      }

      // Generate the output file if the last row has been processed.
      if (!isset($this->context['sandbox']['total']) || $index >= $this->context['sandbox']['total']) {
        $output = $this->generateOutput();
        $this->sendToFile($output);
      }
    }
    //parent::executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   *
   * Add step setting to preliminary config.
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $form = parent::buildPreConfigurationForm($form, $values, $form_state);
    $form['step'] = [
      '#title' => $this->t('Step'),
      '#type' => 'radios',
      '#options' => [
        'ASGT' => $this->t('ASGT Submitted'),
        'ENROL' => $this->t('ENROL Submitted'),
        'EXIT' => $this->t('Student Exited'),
        'DROP' => $this->t('Drop'),
      ],
      '#default_value' => isset($values['step']) ? $values['step'] : 'ASGT',
    ];
    return $form;
  }

  /**
   * Generate output string.
   */
  protected function generateOutput() {
    $footer = count($this->context['sandbox']['rows'])+2;
    $render = [
      '#theme' => static::THEME,
      '#columns' => '',
      '#first_row' => ['TH ' . date("m/d/Y H:i:s") . ' 0000000001 15.0 delimiter=0X2C'],
      '#last_row' => ["TT 0000000001 " . $footer ],
      '#header' => $this->context['sandbox']['header'],
      '#rows' => $this->context['sandbox']['rows'],
      '#configuration' => $this->configuration,
    ];
    return $this->renderer->render($render);
  }
}
