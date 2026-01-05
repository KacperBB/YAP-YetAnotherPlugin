<?php
/**
 * YAP Frontend Form Renderer
 * 
 * Generuje formularze front-endowe dla grup pól YAP.
 * Obsługuje: kontakt, rejestrację, edycję profilu, dodawanie postów.
 * 
 * @package YetAnotherPlugin
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Form_Renderer {
    
    private static $instance = null;
    private $submissions = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handler dla submission
        add_action('wp_ajax_yap_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_yap_submit_form', [$this, 'handle_form_submission']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Register shortcode
        add_shortcode('yap_form', [$this, 'form_shortcode']);
    }
    
    /**
     * Renderuje formularz dla grupy pól
     * 
     * @param string $group_name Nazwa grupy pól
     * @param array $args Argumenty formularza
     * @return string HTML formularza
     */
    public function render_form($group_name, $args = []) {
        global $wpdb;
        
        $defaults = [
            'post_id' => 'new_post',           // ID posta lub 'new_post'
            'post_type' => 'post',              // Typ posta dla new_post
            'post_status' => 'draft',           // Status dla nowego posta
            'success_message' => 'Dziękujemy za przesłanie formularza!',
            'error_message' => 'Wystąpił błąd. Spróbuj ponownie.',
            'submit_text' => 'Wyślij',
            'redirect_url' => '',               // URL przekierowania po sukcesie
            'ajax' => true,                     // AJAX submission
            'honeypot' => true,                 // Ochrona przed botami
            'recaptcha' => false,               // Google reCAPTCHA
            'form_class' => 'yap-form',
            'field_wrapper_class' => 'yap-field-wrapper',
            'button_class' => 'yap-submit-button',
            'before_submit' => null,            // Callback przed zapisem
            'after_submit' => null,             // Callback po zapisie
            'email_notification' => false,      // Wysyłanie emaili
            'email_to' => get_option('admin_email'),
            'email_subject' => 'Nowe przesłanie formularza',
            'required_fields' => [],            // Pola wymagane
            'validation_rules' => [],           // Własne reguły walidacji
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Pobierz strukturę grupy
        $table_name = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return '<div class="yap-form-error">Grupa pól "' . esc_html($group_name) . '" nie istnieje.</div>';
        }
        
        $fields = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY field_order ASC")
        );
        
        if (empty($fields)) {
            return '<div class="yap-form-error">Brak pól w grupie "' . esc_html($group_name) . '".</div>';
        }
        
        // Pobierz obecne wartości jeśli edycja
        $current_values = [];
        if ($args['post_id'] !== 'new_post') {
            $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
            $current_values = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT user_field_name, field_value FROM {$data_table} WHERE post_id = %d",
                    $args['post_id']
                ),
                OBJECT_K
            );
        }
        
        // Generuj unique form ID
        $form_id = 'yap_form_' . uniqid();
        
        // Start output
        ob_start();
        
        ?>
        <form id="<?php echo esc_attr($form_id); ?>" 
              class="<?php echo esc_attr($args['form_class']); ?>" 
              method="post" 
              <?php if ($args['ajax']): ?>data-ajax="true"<?php endif; ?>
              data-group="<?php echo esc_attr($group_name); ?>"
              data-post-id="<?php echo esc_attr($args['post_id']); ?>"
              data-post-type="<?php echo esc_attr($args['post_type']); ?>"
              data-post-status="<?php echo esc_attr($args['post_status']); ?>"
              enctype="multipart/form-data">
            
            <?php wp_nonce_field('yap_form_submit_' . $group_name, 'yap_form_nonce'); ?>
            
            <input type="hidden" name="yap_group_name" value="<?php echo esc_attr($group_name); ?>">
            <input type="hidden" name="yap_post_id" value="<?php echo esc_attr($args['post_id']); ?>">
            <input type="hidden" name="yap_post_type" value="<?php echo esc_attr($args['post_type']); ?>">
            <input type="hidden" name="yap_post_status" value="<?php echo esc_attr($args['post_status']); ?>">
            
            <?php if ($args['honeypot']): ?>
                <input type="text" name="yap_honeypot" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
            <?php endif; ?>
            
            <div class="yap-form-fields">
                <?php foreach ($fields as $field): ?>
                    <?php 
                    $field_name = $field->user_field_name;
                    $field_value = isset($current_values[$field_name]) ? $current_values[$field_name]->field_value : '';
                    $is_required = in_array($field_name, $args['required_fields']);
                    ?>
                    
                    <div class="<?php echo esc_attr($args['field_wrapper_class']); ?> yap-field-type-<?php echo esc_attr($field->field_type); ?>">
                        <?php echo $this->render_field($field, $field_value, $is_required); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($args['recaptcha']): ?>
                <div class="yap-recaptcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(get_option('yap_recaptcha_site_key')); ?>"></div>
                </div>
            <?php endif; ?>
            
            <div class="yap-form-messages" style="display:none;">
                <div class="yap-success-message" data-message="<?php echo esc_attr($args['success_message']); ?>"></div>
                <div class="yap-error-message" data-message="<?php echo esc_attr($args['error_message']); ?>"></div>
            </div>
            
            <div class="yap-form-submit">
                <button type="submit" class="<?php echo esc_attr($args['button_class']); ?>">
                    <?php echo esc_html($args['submit_text']); ?>
                </button>
            </div>
            
            <?php if ($args['redirect_url']): ?>
                <input type="hidden" name="yap_redirect_url" value="<?php echo esc_url($args['redirect_url']); ?>">
            <?php endif; ?>
        </form>
        
        <?php if ($args['ajax']): ?>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo $form_id; ?>').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $form.find('button[type="submit"]');
                var $messages = $form.find('.yap-form-messages');
                
                $button.prop('disabled', true).text('Wysyłanie...');
                $messages.hide().find('div').hide();
                
                var formData = new FormData(this);
                formData.append('action', 'yap_submit_form');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $messages.show().find('.yap-success-message').text(response.data.message).show();
                            $form[0].reset();
                            
                            if (response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 1500);
                            }
                        } else {
                            $messages.show().find('.yap-error-message').text(response.data.message).show();
                        }
                    },
                    error: function() {
                        $messages.show().find('.yap-error-message').text($messages.find('.yap-error-message').data('message')).show();
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php echo esc_js($args['submit_text']); ?>');
                    }
                });
            });
        });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderuje pojedyncze pole
     */
    private function render_field($field, $value = '', $required = false) {
        $field_name = $field->user_field_name;
        $field_type = $field->field_type;
        $field_id = 'yap_field_' . $field_name;
        
        $label = ucfirst(str_replace('_', ' ', $field_name));
        $required_html = $required ? ' <span class="required">*</span>' : '';
        $required_attr = $required ? 'required' : '';
        
        ob_start();
        
        echo '<label for="' . esc_attr($field_id) . '">' . esc_html($label) . $required_html . '</label>';
        
        switch ($field_type) {
            case 'text':
            case 'email':
            case 'url':
            case 'tel':
            case 'number':
                echo '<input type="' . esc_attr($field_type) . '" 
                             id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             value="' . esc_attr($value) . '" 
                             ' . $required_attr . '>';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($field_id) . '" 
                                name="yap_fields[' . esc_attr($field_name) . ']" 
                                rows="5" 
                                ' . $required_attr . '>' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             ' . $required_attr . '>';
                echo '<option value="">-- Wybierz --</option>';
                // TODO: opcje z metadata lub callbacka
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" 
                             id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             value="1" 
                             ' . checked($value, '1', false) . ' 
                             ' . $required_attr . '>';
                break;
                
            case 'file':
                echo '<input type="file" 
                             id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             ' . $required_attr . '>';
                if ($value) {
                    echo '<div class="current-file">Obecny plik: <a href="' . esc_url($value) . '" target="_blank">Zobacz</a></div>';
                }
                break;
                
            case 'date':
                echo '<input type="date" 
                             id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             value="' . esc_attr($value) . '" 
                             ' . $required_attr . '>';
                break;
                
            case 'wysiwyg':
                wp_editor($value, $field_id, [
                    'textarea_name' => 'yap_fields[' . $field_name . ']',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                ]);
                break;
                
            default:
                echo '<input type="text" 
                             id="' . esc_attr($field_id) . '" 
                             name="yap_fields[' . esc_attr($field_name) . ']" 
                             value="' . esc_attr($value) . '" 
                             ' . $required_attr . '>';
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Obsługa submission formularza (AJAX)
     */
    public function handle_form_submission() {
        global $wpdb;
        
        // Verify nonce
        $group_name = sanitize_text_field($_POST['yap_group_name']);
        
        if (!wp_verify_nonce($_POST['yap_form_nonce'], 'yap_form_submit_' . $group_name)) {
            wp_send_json_error([
                'message' => 'Nieprawidłowy token zabezpieczający.'
            ]);
        }
        
        // Honeypot check
        if (!empty($_POST['yap_honeypot'])) {
            wp_send_json_error([
                'message' => 'Wykryto próbę spamu.'
            ]);
        }
        
        // Pobierz dane
        $post_id = sanitize_text_field($_POST['yap_post_id']);
        $post_type = sanitize_text_field($_POST['yap_post_type']);
        $post_status = sanitize_text_field($_POST['yap_post_status']);
        $fields = isset($_POST['yap_fields']) ? $_POST['yap_fields'] : [];
        
        // Utwórz nowy post jeśli potrzeba
        if ($post_id === 'new_post') {
            $new_post = wp_insert_post([
                'post_type' => $post_type,
                'post_status' => $post_status,
                'post_author' => get_current_user_id(),
                'post_title' => isset($fields['title']) ? $fields['title'] : 'Nowy post',
            ]);
            
            if (is_wp_error($new_post)) {
                wp_send_json_error([
                    'message' => 'Nie udało się utworzyć posta: ' . $new_post->get_error_message()
                ]);
            }
            
            $post_id = $new_post;
        } else {
            $post_id = intval($post_id);
        }
        
        // Sanitizuj pola
        if (class_exists('YAP_Sanitizers')) {
            foreach ($fields as $field_name => $field_value) {
                $fields[$field_name] = yap_sanitize($field_value, $field_name, '');
            }
        }
        
        // Zapisz pola do bazy
        $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
        
        foreach ($fields as $field_name => $field_value) {
            // Obsługa file upload
            if (isset($_FILES['yap_fields']['name'][$field_name])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                
                $file = [
                    'name' => $_FILES['yap_fields']['name'][$field_name],
                    'type' => $_FILES['yap_fields']['type'][$field_name],
                    'tmp_name' => $_FILES['yap_fields']['tmp_name'][$field_name],
                    'error' => $_FILES['yap_fields']['error'][$field_name],
                    'size' => $_FILES['yap_fields']['size'][$field_name],
                ];
                
                $upload = wp_handle_upload($file, ['test_form' => false]);
                
                if (isset($upload['url'])) {
                    $field_value = $upload['url'];
                }
            }
            
            // Sprawdź czy pole istnieje
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$data_table} WHERE post_id = %d AND user_field_name = %s",
                $post_id,
                $field_name
            ));
            
            if ($exists) {
                // Update
                $wpdb->update(
                    $data_table,
                    ['field_value' => $field_value],
                    [
                        'post_id' => $post_id,
                        'user_field_name' => $field_name
                    ],
                    ['%s'],
                    ['%d', '%s']
                );
            } else {
                // Insert
                $wpdb->insert(
                    $data_table,
                    [
                        'post_id' => $post_id,
                        'user_field_name' => $field_name,
                        'field_value' => $field_value
                    ],
                    ['%d', '%s', '%s']
                );
            }
        }
        
        // Trigger hooks
        do_action('yap_form_submitted', $group_name, $post_id, $fields);
        do_action('yap_form_submitted_' . $group_name, $post_id, $fields);
        
        // Wysłanie emaila jeśli włączone
        // TODO: Email notification
        
        wp_send_json_success([
            'message' => 'Formularz został przesłany pomyślnie!',
            'post_id' => $post_id,
            'redirect_url' => isset($_POST['yap_redirect_url']) ? esc_url($_POST['yap_redirect_url']) : ''
        ]);
    }
    
    /**
     * Enqueue scripts i styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Basic styling
        wp_add_inline_style('wp-block-library', '
            .yap-form { max-width: 600px; margin: 0 auto; }
            .yap-field-wrapper { margin-bottom: 20px; }
            .yap-field-wrapper label { display: block; margin-bottom: 5px; font-weight: bold; }
            .yap-field-wrapper input[type="text"],
            .yap-field-wrapper input[type="email"],
            .yap-field-wrapper input[type="url"],
            .yap-field-wrapper input[type="tel"],
            .yap-field-wrapper input[type="number"],
            .yap-field-wrapper input[type="date"],
            .yap-field-wrapper textarea,
            .yap-field-wrapper select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            .yap-field-wrapper .required { color: red; }
            .yap-submit-button { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
            .yap-submit-button:hover { background: #005177; }
            .yap-submit-button:disabled { opacity: 0.5; cursor: not-allowed; }
            .yap-form-messages { margin: 20px 0; }
            .yap-success-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; border: 1px solid #c3e6cb; }
            .yap-error-message { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; border: 1px solid #f5c6cb; }
        ');
    }
    
    /**
     * Shortcode handler
     */
    public function form_shortcode($atts) {
        $atts = shortcode_atts([
            'group' => '',
            'post_id' => 'new_post',
            'post_type' => 'post',
            'success_message' => 'Dziękujemy!',
            'submit_text' => 'Wyślij',
        ], $atts);
        
        if (empty($atts['group'])) {
            return '<div class="yap-form-error">Brak parametru "group" w shortcode.</div>';
        }
        
        return $this->render_form($atts['group'], $atts);
    }
}

// Helper functions
function yap_render_form($group_name, $args = []) {
    return YAP_Form_Renderer::get_instance()->render_form($group_name, $args);
}

function yap_form($group_name, $args = []) {
    echo yap_render_form($group_name, $args);
}
