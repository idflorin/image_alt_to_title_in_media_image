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
    // SAFETY CHECK: Only proceed if this is a media-related form.
    // This prevents breaking Menu, Taxonomy, or Views UI forms.
    $form_object = $form_state->getFormObject();
    if (method_exists($form_object, 'getEntity')) {
      $entity = $form_object->getEntity();
      if ($entity->getEntityTypeId() !== 'media') {
        return;
      }
    }

    // Hide Title field in known places.
    if (isset($form['field_media_image'])) {
      $this->hideTitleField($form['field_media_image']);
    }

    // Limit recursion to avoid breaking complex admin UIs like Menus.
    foreach (Element::children($form) as $key) {
      if ($key === 'field_media_image' && is_array($form[$key])) {
         $this->hideTitleField($form[$key]);
      }
    }

    // Ensure our validator runs once.
    if (!isset($form['#validate']) || !is_array($form['#validate'])) {
      $form['#validate'] = [];
    }
    
    $form['#validate'][] = [$this, 'validateForm'];
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
   * Recursively hides the title field in a targeted manner.
   */
  public function hideTitleField(array &$element): void {
    if (isset($element['title'])) {
      $element['title']['#access'] = FALSE;
    }
    elseif (isset($element['widget'][0]['title'])) {
       $element['widget'][0]['title']['#access'] = FALSE;
    }

    foreach (Element::children($element) as $key) {
      // Only dive deeper if we haven't found the title yet 
      // and we aren't in a dangerous recursive loop.
      if (is_array($element[$key]) && $key !== 'container') {
        $this->hideTitleField($element[$key]);
      }
    }
  }

  /**
   * Form validator to copy Alt text to Title.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->copyAltToTitle($values, $form_state);
  }

  /**
   * Logic to sync Alt value to Title value.
   */
  private function copyAltToTitle(array $values, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if ($key === 'field_media_image' && is_array($value)) {
        foreach ($value as $delta => $item) {
          if (isset($item['alt'])) {
            $path = [$key, $delta, 'title'];
            $form_state->setValue($path, $item['alt']);
          }
        }
      }
      elseif (is_array($value)) {
        // Targeted recursion for nested media items.
        $this->copyAltToTitle($value, $form_state);
      }
    }
  }

  /**
   * Late-stage after_build callback.
   */
  public function afterBuildHideTitle(array $element, FormStateInterface $form_state) {
    if (isset($element['field_media_image'])) {
      $this->hideTitleField($element['field_media_image']);
    }
    return $element;
  }
}
