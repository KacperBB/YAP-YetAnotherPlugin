<?php
/**
 * Field Input Renderer
 * 
 * Uniwersalny renderer dla wszystkich typów pól
 * Obsługuje: text, textarea, email, url, tel, date, time, color, select, checkbox, radio, itp.
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderuj input dla pola na podstawie typu
 */
function yap_render_field_input($field, $value, $input_name, $input_id, $group_name = '') {
    $type = $field['type'] ?? 'text';
    $placeholder = $field['placeholder'] ?? '';
    $css_class = !empty($field['css_class']) ? $field['css_class'] : 'regular-text';
    
    switch ($type) {
        case 'text':
            echo '<input type="text" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'textarea':
            echo '<textarea id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" rows="5" placeholder="' . esc_attr($placeholder) . '" class="large-text">' . esc_textarea($value) . '</textarea>';
            break;
        case 'number':
            echo '<input type="number" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'email':
            echo '<input type="email" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'url':
            echo '<input type="url" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'tel':
        case 'phone':
            echo '<input type="tel" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'date':
            echo '<input type="date" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'time':
            echo '<input type="time" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'datetime':
        case 'datetime-local':
            echo '<input type="datetime-local" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'color':
            $color_value = $value ?: '#000000';
            echo '<div class="yap-color-picker-wrapper">';
            echo '<input type="color" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($color_value) . '" class="' . esc_attr($css_class) . '" title="Pick a color">';
            echo '<span class="yap-color-value" data-color-display="' . esc_attr($input_id) . '">' . strtoupper(esc_html($color_value)) . '</span>';
            echo '</div>';
            echo '<script>
            (function() {
                var input = document.getElementById("' . esc_js($input_id) . '");
                var display = document.querySelector("[data-color-display=\"' . esc_js($input_id) . '\"]");
                if (input && display) {
                    input.addEventListener("change", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                    input.addEventListener("input", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                }
            })();
            </script>';
            break;
        case 'select':
            $choices = $field['choices'] ?? [];
            if (empty($choices)) {
                error_log("   ⚠️ SELECT pole bez opcji: " . ($field['name'] ?? 'unknown'));
            }
            echo '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" class="' . esc_attr($css_class) . '">';
            echo '<option value="">-- Wybierz --</option>';
            foreach ($choices as $choice_value => $choice_label) {
                $selected = ($value == $choice_value) ? 'selected' : '';
                echo '<option value="' . esc_attr($choice_value) . '" ' . $selected . '>' . esc_html($choice_label) . '</option>';
            }
            echo '</select>';
            break;
        case 'checkbox':
            $checked = !empty($value) ? 'checked' : '';
            echo '<label><input type="checkbox" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="1" ' . $checked . '> ' . esc_html($field['label'] ?? 'Tak') . '</label>';
            break;
        case 'radio':
            $choices = $field['choices'] ?? [];
            echo '<div class="yap-radio-group">';
            foreach ($choices as $choice_value => $choice_label) {
                $checked = ($value == $choice_value) ? 'checked' : '';
                $radio_id = $input_id . '_' . sanitize_key($choice_value);
                echo '<label style="display: inline-block; margin-right: 15px;">';
                echo '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choice_value) . '" ' . $checked . '> ';
                echo esc_html($choice_label);
                echo '</label>';
            }
            echo '</div>';
            break;
        case 'wysiwyg':
            wp_editor($value, $input_id, [
                'textarea_name' => $input_name,
                'textarea_rows' => 10,
                'teeny' => false
            ]);
            break;
        case 'file':
            yap_render_file_field($value, $input_name, $input_id);
            break;
        case 'image':
            yap_render_image_field($value, $input_name, $input_id);
            break;
        case 'gallery':
            yap_render_gallery_field($value, $input_name, $input_id);
            break;
        case 'flexible_content':
            $field['group_name'] = $group_name;
            error_log("🎨 Field Input: Rendering flexible_content field - {$input_id}");
            if (class_exists('YAP_Flexible_Content')) {
                error_log("✅ YAP_Flexible_Content class found - rendering");
                YAP_Flexible_Content::render_field($field, $value, $input_name, $input_id);
            } else {
                error_log("❌ YAP_Flexible_Content class NOT found - falling back to placeholder");
                // Fallback: render as text if class not available
                echo '<div style="border: 1px solid #ffb81c; padding: 10px; border-radius: 4px; background: #fffbea;">';
                echo '<p style="margin: 0; color: #856404;"><strong>⚠️ Flexible Content Not Available</strong></p>';
                echo '<p style="margin: 5px 0 0; font-size: 12px; color: #856404;">The YAP_Flexible_Content class is not loaded. Please ensure flexible-content.php is properly included.</p>';
                echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '">';
                echo '</div>';
            }
            break;
        case 'repeater':
            yap_render_repeater_field($field, $value, $input_name, $input_id);
            break;
        case 'group':
            yap_render_group_field($field, $value, $input_name, $input_id);
            break;
        default:
            echo '<input type="text" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
    }
}

/**
 * Renderuj pole typu file
 */
function yap_render_file_field($value, $input_name, $input_id) {
    $file_id = $value;
    $file_url = '';
    $file_name = '';
    if (is_numeric($file_id)) {
        $file_url = wp_get_attachment_url($file_id);
        $file_name = basename(get_attached_file($file_id));
    }
    echo '<div class="yap-file-field-wrapper">';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-file-id">';
    echo '<button type="button" class="button yap-upload-file-button" data-field="' . esc_attr($input_id) . '">Wybierz plik</button>';
    if ($file_url) {
        echo '<div class="yap-file-preview" style="margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">';
        echo '<a href="' . esc_url($file_url) . '" target="_blank" style="text-decoration: none;">📄 ' . esc_html($file_name) . '</a>';
        echo '<button type="button" class="button yap-remove-file-button" style="margin-left: 10px;">Usuń</button>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Renderuj pole typu image
 */
function yap_render_image_field($value, $input_name, $input_id) {
    $image_id = $value;
    $image_url = '';
    if (is_numeric($image_id)) {
        $image_url = wp_get_attachment_url($image_id);
    }
    echo '<div class="yap-image-field-wrapper">';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-image-id">';
    echo '<button type="button" class="button yap-upload-image-button" data-field="' . esc_attr($input_id) . '">Wybierz obraz</button>';
    if ($image_url) {
        echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: block;">';
        echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px;">Usuń obraz</button>';
    } else {
        echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: none;">';
        echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: none;">Usuń obraz</button>';
    }
    echo '</div>';
}

/**
 * Renderuj pole typu gallery
 */
function yap_render_gallery_field($value, $input_name, $input_id) {
    $gallery_ids = is_array($value) ? $value : (!empty($value) ? explode(',', $value) : []);
    echo '<div class="yap-gallery-field-wrapper" data-field-id="' . esc_attr($input_id) . '">';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(is_array($value) ? implode(',', $value) : $value) . '" class="yap-gallery-ids">';
    echo '<button type="button" class="button yap-add-gallery-images" data-field="' . esc_attr($input_id) . '">Dodaj obrazy</button>';
    echo '<div class="yap-gallery-preview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($gallery_ids as $img_id) {
        if (is_numeric($img_id) && $img_id > 0) {
            $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
            if ($img_url) {
                echo '<div class="yap-gallery-item" data-id="' . esc_attr($img_id) . '" style="position: relative;">';
                echo '<img src="' . esc_url($img_url) . '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">';
                echo '<button type="button" class="yap-remove-gallery-item" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1;">×</button>';
                echo '</div>';
            }
        }
    }
    echo '</div>';
    echo '</div>';
}
