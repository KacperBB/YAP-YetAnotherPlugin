<?php
/**
 * WYSIWYG Field - Builder Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Wysiwyg_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        $editor_id = 'wysiwyg_' . uniqid();
        
        echo '<div class="yap-wysiwyg-editor" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: white; margin-top: 8px;">';
        echo '<div class="editor-toolbar" style="background: #f5f5f5; border-bottom: 1px solid #ddd; padding: 8px; display: flex; gap: 4px; flex-wrap: wrap;">';
        echo '<button type="button" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;"><strong>B</strong></button>';
        echo '<button type="button" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;"><em>I</em></button>';
        echo '</div>';
        echo '<textarea id="' . esc_attr($editor_id) . '" name="' . esc_attr($this->input_name) . '" style="width: 100%; min-height: 200px; border: none; padding: 15px; font-family: inherit; outline: none;">' . esc_textarea($this->value) . '</textarea>';
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
