<?php
/**
 * Simple Field Renderer
 * 
 * Renderer dla prostych pól bez rekurencji (dla repeaterów i grup)
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderuj proste pole (bez rekurencji dla repeater/group/flexible_content)
 * Używane wewnątrz repeaterów i grup
 */
function yap_render_simple_field($field, $value, $input_name, $input_id) {
    $type = $field['type'] ?? 'text';
    $placeholder = $field['placeholder'] ?? '';
    
    switch ($type) {
        case 'text':
        case 'short_text':
            echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'textarea':
        case 'long_text':
            echo '<textarea name="' . esc_attr($input_name) . '" rows="3" placeholder="' . esc_attr($placeholder) . '" class="widefat">' . esc_textarea($value) . '</textarea>';
            break;
        case 'number':
            echo '<input type="number" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'email':
            echo '<input type="email" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'url':
            echo '<input type="url" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'tel':
        case 'phone':
            echo '<input type="tel" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'select':
            $choices = $field['choices'] ?? [];
            echo '<select name="' . esc_attr($input_name) . '" class="widefat">';
            echo '<option value="">-- Wybierz --</option>';
            foreach ($choices as $choice_value => $choice_label) {
                $selected = ($value == $choice_value) ? 'selected' : '';
                echo '<option value="' . esc_attr($choice_value) . '" ' . $selected . '>' . esc_html($choice_label) . '</option>';
            }
            echo '</select>';
            break;
        case 'checkbox':
            $checked = !empty($value) ? 'checked' : '';
            echo '<label><input type="checkbox" name="' . esc_attr($input_name) . '" value="1" ' . $checked . '> Tak</label>';
            break;
        case 'date':
            echo '<input type="date" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'time':
            echo '<input type="time" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'datetime':
        case 'datetime-local':
            echo '<input type="datetime-local" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'color':
            $color_value = $value ?: '#000000';
            $color_id = 'color_' . uniqid();
            echo '<div class="yap-color-picker-wrapper">';
            echo '<input type="color" id="' . esc_attr($color_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($color_value) . '" class="yap-repeater-color">';
            echo '<span class="yap-color-value" data-color-display="' . esc_attr($color_id) . '">' . strtoupper(esc_html($color_value)) . '</span>';
            echo '</div>';
            break;
        case 'radio':
            $choices = $field['choices'] ?? [];
            echo '<div class="yap-radio-group">';
            foreach ($choices as $choice_value => $choice_label) {
                $checked = ($value == $choice_value) ? 'checked' : '';
                $radio_id = $input_id . '_' . sanitize_key($choice_value);
                echo '<label style="display: block; margin-bottom: 5px;">';
                echo '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choice_value) . '" ' . $checked . '> ';
                echo esc_html($choice_label);
                echo '</label>';
            }
            echo '</div>';
            break;
        case 'password':
            echo '<input type="password" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'file':
            yap_render_simple_file_field($value, $input_name, $input_id);
            break;
        case 'gallery':
            yap_render_simple_gallery_field($value, $input_name, $input_id);
            break;
        case 'image':
            yap_render_simple_image_field($value, $input_name, $input_id);
            break;
        case 'wysiwyg':
            wp_editor($value, $input_id, [
                'textarea_name' => $input_name,
                'textarea_rows' => 5,
                'teeny' => false,
                'media_buttons' => true
            ]);
            break;
        case 'flexible_content':
            echo '<div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 11px;">';
            echo '⚠️ Flexible Content nie może być zagnieżdżony';
            echo '</div>';
            break;
        case 'repeater':
        case 'group':
            echo '<div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px;">';
            echo '⚠️ Zagnieżdżone repeatery/grupy nie są wspierane';
            echo '</div>';
            break;
        default:
            echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
    }
}

/**
 * Renderuj file w repeaterze
 */
function yap_render_simple_file_field($value, $input_name, $input_id) {
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
        echo '<div class="yap-file-preview" style="margin-top: 8px; padding: 6px; background: #f5f5f5; border-radius: 3px; font-size: 12px;">';
        echo '<a href="' . esc_url($file_url) . '" target="_blank">📄 ' . esc_html($file_name) . '</a>';
        echo '<button type="button" class="button yap-remove-file-button" style="margin-left: 8px; font-size: 11px;">Usuń</button>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Renderuj gallery w repeaterze
 */
function yap_render_simple_gallery_field($value, $input_name, $input_id) {
    $gallery_ids = is_array($value) ? $value : (!empty($value) ? explode(',', $value) : []);
    echo '<div class="yap-gallery-field-wrapper" data-field-id="' . esc_attr($input_id) . '">';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(is_array($value) ? implode(',', $value) : $value) . '" class="yap-gallery-ids">';
    echo '<button type="button" class="button yap-add-gallery-images" data-field="' . esc_attr($input_id) . '">Dodaj obrazy</button>';
    echo '<div class="yap-gallery-preview" style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">';
    foreach ($gallery_ids as $img_id) {
        if (is_numeric($img_id) && $img_id > 0) {
            $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
            if ($img_url) {
                echo '<div class="yap-gallery-item" data-id="' . esc_attr($img_id) . '" style="position: relative;">';
                echo '<img src="' . esc_url($img_url) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 3px;">';
                echo '<button type="button" class="yap-remove-gallery-item" style="position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; border-radius: 50%; width: 18px; height: 18px; cursor: pointer; font-size: 12px; line-height: 1;">×</button>';
                echo '</div>';
            }
        }
    }
    echo '</div>';
    echo '</div>';
}

/**
 * Renderuj image w repeaterze
 */
function yap_render_simple_image_field($value, $input_name, $input_id) {
    $image_id = $value;
    $image_url = '';
    if (is_numeric($image_id)) {
        $image_url = wp_get_attachment_url($image_id);
    }
    echo '<div class="yap-image-field-wrapper">';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-image-id">';
    echo '<button type="button" class="button yap-upload-image-button" data-field="' . esc_attr($input_id) . '">Wybierz obraz</button>';
    if ($image_url) {
        echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 100px; display: block;">';
        echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px;">Usuń</button>';
    } else {
        echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 100px; display: none;">';
        echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: none;">Usuń</button>';
    }
    echo '</div>';
}
