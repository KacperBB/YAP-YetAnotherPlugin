<?php
/**
 * Image Field - Builder Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Image_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<div class="yap-image-uploader" style="border: 2px dashed #0073aa; padding: 30px; text-align: center; border-radius: 8px; background: #f9fafb; cursor: pointer; margin-top: 8px;">';
        echo '<div style="font-size: 32px; margin-bottom: 10px;">🖼️</div>';
        echo '<p style="margin: 0 0 15px; color: #333;">Kliknij aby wybrać obraz</p>';
        echo '<input type="file" name="' . esc_attr($this->input_name) . '" style="display: none;" accept="image/*">';
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
