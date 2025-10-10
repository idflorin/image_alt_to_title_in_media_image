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
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    // Hide Title field in known places first.
    if (isset($form['field_media_image'])) {
      $this->hideTitleField($form['field_media_image']);
    }
    // Conservative pass over top-level children only.
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]) && is_array($form[$key])) {
        $this->hideTitleField($form[$key]);
      }
    }

    // Ensure our validator runs once.
    if (!isset($form['#validate']) || !is_array($form['#validate'])) {
      $form['#validate'] = [];
    }
    $exists = FALSE;
    foreach ($form['#validate'] as $callback) {
      if (is_array($callback) && isset($callback[0], $callback[1]) && $callback[0] instanceof self && $callback[1] === 'validateForm') {
        $exists = TRUE;
        break;
      }
    }
    if (!$exists) {
      $form['#validate'][] = [$this, 'validateForm'];
    }
  }

  /**
   * Validation handler to sync 'alt' -> 'title' on submit.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->syncAltToTitle($form_state, $values);
  }

  /**
   * Hide the 'title' sub-element on image widgets.
   *
   * IMPORTANT: Only traverse render children via Element::children()
   * to avoid scanning misc keys that can create huge/recursive structures.
   *
   * @param array $element
   *   A subtree of the render array.
   */
  public function hideTitleField(array &$element) {
    if (!is_array($element)) {
      return;
    }

    // Common widget shapes: direct 'title' or nested under 'image'.
    if (isset($element['title']) && is_array($element['title'])) {
      $element['title']['#access'] = FALSE;
    }
    if (isset($element['image']['title']) && is_array($element['image']['title'])) {
      $element['image']['title']['#access'] = FALSE;
    }

    // Field widget containers often put items under ['widget'][delta].
    if (isset($element['widget']) && is_array($element['widget'])) {
      foreach (Element::children($element['widget']) as $delta) {
        if (isset($element['widget'][$delta]) && is_array($element['widget'][$delta])) {
          $item =& $element['widget'][$delta];
          if (isset($item['title'])) {
            $item['title']['#access'] = FALSE;
          }
          if (isset($item['image']['title'])) {
            $item['image']['title']['#access'] = FALSE;
          }
        }
      }
    }

    // Media form often has field_media_image at this level.
    if (isset($element['field_media_image']) && is_array($element['field_media_image'])) {
      $this->hideTitleField($element['field_media_image']);
    }

    // Recurse ONLY into render children of this element.
    foreach (Element::children($element) as $child_key) {
      if (isset($element[$child_key]) && is_array($element[$child_key])) {
        $this->hideTitleField($element[$child_key]);
      }
    }
  }

  /**
   * Walk submitted values and copy alt -> title where applicable.
   */
  protected function syncAltToTitle(FormStateInterface $form_state, $values, array $parents = []) {
    if (is_array($values)) {
      foreach ($values as $key => $value) {
        $current_parents = array_merge($parents, [$key]);
        if (is_array($value)) {
          $has_alt = array_key_exists('alt', $value);
          $has_title = array_key_exists('title', $value);

          if ($has_alt && $has_title) {
            $item_parents = $current_parents;
            $current_value = $form_state->getValue($item_parents);
            if (is_array($current_value) && !empty($current_value['alt'])) {
              $current_value['title'] = $current_value['alt'];
              $form_state->setValue($item_parents, $current_value);
            }
          }
          else {
            $this->syncAltToTitle($form_state, $value, $current_parents);
          }
        }
      }
    }
  }

  /**
   * Processes an image field to hide title and add validation.
   */
  public function processImageField(array &$element, FormStateInterface $form_state) {
    // Hide in typical widget structures.
    if (isset($element['widget']) && is_array($element['widget'])) {
      foreach (Element::children($element['widget']) as $delta) {
        $item =& $element['widget'][$delta];
        if (isset($item['title'])) {
          $item['title']['#access'] = FALSE;
        }
        elseif (isset($item['image']['title'])) {
          $item['image']['title']['#access'] = FALSE;
        }
      }
    }
    else {
      if (isset($element['title'])) {
        $element['title']['#access'] = FALSE;
      }
      elseif (isset($element['image']['title'])) {
        $element['image']['title']['#access'] = FALSE;
      }
    }

    // Ensure validator is registered on this element too.
    if (!isset($element['#validate']) || !is_array($element['#validate'])) {
      $element['#validate'] = [];
    }
    $element['#validate'][] = [$this, 'validateForm'];
  }

  /**
   * Alters the Media Library widget form to hide image Title fields.
   */
  public function alterMediaLibraryWidgetForm(array &$form, FormStateInterface $form_state): void {
    // Modal nests media items under 'media'.
    if (isset($form['media']) && is_array($form['media'])) {
      foreach ($form['media'] as &$media_subform) {
        if (!is_array($media_subform)) {
          continue;
        }
        if (isset($media_subform['field_media_image'][0]) && is_array($media_subform['field_media_image'][0])) {
          $widget =& $media_subform['field_media_image'][0];
          if (isset($widget['title'])) {
            $widget['title']['#access'] = FALSE;
          }
          elseif (isset($widget['image']['title'])) {
            $widget['image']['title']['#access'] = FALSE;
          }
        }
      }
    }

    // Conservative recursive hide over top-level children.
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]) && is_array($form[$key])) {
        $this->hideTitleField($form[$key]);
      }
    }
  }

  /**
   * Late-stage after_build callback to ensure Title stays hidden.
   */
  public function afterBuildHideTitle(array $element, FormStateInterface $form_state) {
    // Only traverse render children to avoid huge scans.
    if (isset($element['field_media_image'])) {
      $this->hideTitleField($element['field_media_image']);
    }
    foreach (\Drupal\Core\Render\Element::children($element) as $key) {
      if (isset($element[$key]) && is_array($element[$key])) {
        $this->hideTitleField($element[$key]);
      }
    }
    return $element;
  }

}
