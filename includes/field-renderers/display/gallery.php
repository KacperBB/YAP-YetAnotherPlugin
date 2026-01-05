<?php
/**
 * Gallery Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Gallery_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        
        $image_ids = is_array($this->value) ? $this->value : explode(',', $this->value);
        
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
        
        foreach ($image_ids as $image_id) {
            echo wp_get_attachment_image($image_id, 'thumbnail', false, ['style' => 'width: 100%; height: auto; object-fit: cover;']);
        }
        
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
