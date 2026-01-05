<?php
/**
 * Image Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Image_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        
        if (is_numeric($this->value)) {
            echo wp_get_attachment_image($this->value, 'medium');
        } else {
            echo '<img src="' . esc_url($this->value) . '" style="max-width: 100%; height: auto;" alt="Image">';
        }
        
        $this->render_wrapper_close();
    }
}
