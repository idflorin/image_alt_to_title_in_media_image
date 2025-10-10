# Image Alt to Title in Media Image

This Drupal 11 module enhances the Media Library and Image field experience by automatically managing image **Title** and **Alt** attributes for consistency, accessibility, and SEO.

## Overview

By default, Drupal exposes both *Alt text* and *Title* fields on image widgets within media entities. In most content workflows, these fields duplicate each other, and content editors often leave one blank or inconsistent.

This module enforces a simpler, cleaner approach:

- **Hides the "Title" field** in all Media Image forms — both in `/media/add/image` and inside the **Media Library modal**.
- **Copies the Alt text value** to the Title field automatically upon form submission.
- Works with **single and multiple image uploads**.
- Functions seamlessly in **Media Library**, **Media add/edit forms**, and **entity reference modals**.

## Features

✅ Hide the "Title" field on:
- `/media/add/image`
- Media edit forms
- Media Library modal

✅ Automatically copy *Alt text* → *Title* on form submission  
✅ Supports **single and multiple** image widgets  
✅ Lightweight, no dependencies  
✅ Safe for multilingual and validation-enabled setups

---

## Installation

1. Place the module folder in your Drupal installation under:

   ```bash
   web/modules/custom/image_alt_to_title_in_media_image
   ```

2. Enable it using Drush or the Drupal admin UI:

   ```bash
   drush en image_alt_to_title_in_media_image -y
   ```

3. Clear cache:

   ```bash
   drush cr
   ```

The module will begin hiding image Title fields and syncing Alt → Title immediately.

---

## Technical Details

### Key Components

| File | Purpose |
|------|----------|
| `image_alt_to_title_in_media_image.module` | Implements form and widget alter hooks for media image widgets and Media Library modals. |
| `src/Form/FormImageFieldHandler.php` | Service class handling recursive hiding and alt→title synchronization. |
| `image_alt_to_title_in_media_image.services.yml` | Registers the form handler service. |

### Hook Implementations

- `hook_field_widget_single_element_form_alter()` – hides the Title field for media image widgets.
- `hook_form_alter()` – ensures all image fields are processed and validated.
- `hook_form_BASE_FORM_ID_alter()` for:
  - `media_library_widget_form`
  - `media_form`

### Form Behavior Summary

| Scenario | Behavior |
|-----------|-----------|
| `/media/add/image` | Title hidden, Alt copied to Title |
| Media edit form | Title hidden, Alt copied to Title |
| Media Library modal | Title hidden, Alt copied to Title |
| Multiple image widgets | Each image handled individually |

---

## Troubleshooting

**Symptom:** Title field still appears in Media Library  
**Fix:** Clear caches (`drush cr`). Ensure the module is enabled and no other module re-adds the field via `hook_form_alter()`.

**Symptom:** PHP memory exhaustion  
**Fix:** Use the latest version of this module — recursion has been optimized to traverse only render children.

**Symptom:** Title field visible in custom image widgets  
**Fix:** Extend `FormImageFieldHandler::hideTitleField()` to include custom field widget keys.

---

## Compatibility

- Drupal 10.x and 11.x
- Works with both Classic and Claro admin themes
- Compatible with core Media and Image modules
- Tested with multiple image widgets and media bundles

---

## Maintainers

**Author:** Custom implementation built for production use.  
If adapting, please review and adjust field machine names (`field_media_image`) as needed for your site’s media bundle.

---

## License

This module is distributed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) license, consistent with Drupal core licensing.
