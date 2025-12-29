<?php

namespace Drupal\image_alt_to_title_in_media_image\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Handles image field alterations in forms.
 */
class FormImageFieldHandler {

  /**
   * Alters forms to hide title field and sync alt to title.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    // SAFETY: Only run if this is an actual Media entity form.
    $form_object = $form_state->getFormObject();
    if (method_exists($form_object, 'getEntity')) {
      $entity = $form_object->getEntity();
      if ($entity->getEntityTypeId() !== 'media') {
        return; 
      }
    }

    if (isset($form['field_media_image'])) {
      $this->hideTitleField($form['field_media_image']);
    }

    // Attach validator for syncing.
    $form['#validate'][] = [$this, 'validateForm'];
  }

  /**
   * Recursively hides the title field in a targeted manner.
   */
  public function hideTitleField(array &$element): void {
    if (!is_array($element)) {
      return;
    }

    // Hide Title if found.
    if (isset($element['title'])) {
      $element['title']['#access'] = FALSE;
    }
    elseif (isset($element['widget'][0]['title'])) {
      $element['widget'][0]['title']['#access'] = FALSE;
    }

    // Targeted recursion: Avoid scanning every child in large admin forms.
    foreach (Element::children($element) as $key) {
      if (is_array($element[$key]) && $key !== 'container') {
        $this->hideTitleField($element[$key]);
      }
    }
  }

  /**
   * Validation handler to sync 'alt' -> 'title'.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->syncValues($values, $form_state);
  }

  private function syncValues(array $values, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if ($key === 'field_media_image' && is_array($value)) {
        foreach ($value as $delta => $item) {
          if (isset($item['alt'])) {
            $form_state->setValue([$key, $delta, 'title'], $item['alt']);
          }
        }
      }
    }
  }

  /**
   * Specifically handles the Media Library widget modal.
   */
  public function alterMediaLibraryWidgetForm(array &$form, FormStateInterface $form_state): void {
    if (isset($form['media']) && is_array($form['media'])) {
      foreach (Element::children($form['media']) as $delta) {
        if (isset($form['media'][$delta]['field_media_image'])) {
          $this->hideTitleField($form['media'][$delta]['field_media_image']);
        }
      }
    }
  }

  /**
   * Late-stage after_build callback.
   */
  public function afterBuildHideTitle(array $element, FormStateInterface $form_state) {
    if (isset($element['title'])) {
      $element['title']['#access'] = FALSE;
    }
    return $element;
  }
}
