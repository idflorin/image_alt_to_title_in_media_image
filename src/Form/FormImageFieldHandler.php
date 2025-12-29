<?php

namespace Drupal\image_alt_to_title_in_media_image\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Handles image field alterations.
 */
class FormImageFieldHandler {

  public function alterForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['field_media_image'])) {
      $this->hideTitleField($form['field_media_image']);
    }
    $form['#validate'][] = [$this, 'validateForm'];
  }

  public function processImageField(array &$element, FormStateInterface $form_state) {
    $this->hideTitleField($element);
    $element['#element_validate'][] = [$this, 'validateElement'];
  }

  public function alterMediaLibraryWidgetForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['media']) && is_array($form['media'])) {
      foreach (Element::children($form['media']) as $delta) {
        if (isset($form['media'][$delta]['field_media_image'])) {
          $this->hideTitleField($form['media'][$delta]['field_media_image']);
        }
      }
    }
    $form['#validate'][] = [$this, 'validateForm'];
  }

  public function hideTitleField(array &$element) {
    if (!is_array($element)) return;

    if (isset($element['title'])) $element['title']['#access'] = FALSE;
    if (isset($element['image']['title'])) $element['image']['title']['#access'] = FALSE;

    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['title'])) {
        $element[$key]['title']['#access'] = FALSE;
      }
      if (isset($element[$key]['image']['title'])) {
        $element[$key]['image']['title']['#access'] = FALSE;
      }
    }
  }

  public function afterBuildHideTitle(array $element, FormStateInterface $form_state) {
    $this->hideTitleField($element);
    return $element;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->syncAltToTitle($form_state);
  }

  public function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $this->syncAltToTitle($form_state);
  }

  protected function syncAltToTitle(FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Sync standard field_media_image structure
    if (!empty($values['field_media_image'])) {
      foreach ($values['field_media_image'] as $delta => $item) {
        if (is_numeric($delta) && isset($item['alt'])) {
          $form_state->setValue(['field_media_image', $delta, 'title'], $item['alt']);
        }
      }
    }

    // Sync Media Library structure
    if (!empty($values['media'])) {
      foreach ($values['media'] as $delta => $media_item) {
        if (isset($media_item['field_media_image'][0]['alt'])) {
          $form_state->setValue(['media', $delta, 'field_media_image', 0, 'title'], $media_item['field_media_image'][0]['alt']);
        }
      }
    }
  }
}
