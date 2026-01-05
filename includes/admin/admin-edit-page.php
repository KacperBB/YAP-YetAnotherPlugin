<?php 

function yap_edit_group_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    error_log("🟢 yap_edit_group_page_html() called");
    error_log("🟢 POST keys: " . implode(', ', array_keys($_POST)));
    error_log("🟢 GET table: " . ($_GET['table'] ?? 'NOT SET'));

    global $wpdb;
    $table_name = isset($_GET['table']) ? sanitize_text_field($_GET['table']) : '';
    
    // Check if this is a new-style group (no table, only JSON schema)
    if (empty($table_name) || !$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
        // Try to extract group name from table parameter
        if (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_pattern$/', $table_name, $matches)) {
            $group_name = $matches[1];
        } else {
            $group_name = str_replace([$wpdb->prefix . 'yap_', '_pattern'], '', $table_name);
        }
        
        // Check if JSON schema exists
        $schema_file = WP_CONTENT_DIR . '/yap-schemas/' . $group_name . '.json';
        
        if (file_exists($schema_file)) {
            ?>
            <div class="wrap">
                <h1>Edytuj Grupę: <?php echo esc_html(ucwords(str_replace('_', ' ', $group_name))); ?></h1>
                <div class="notice notice-info">
                    <p><strong>ℹ️ Ta grupa została utworzona w Visual Builder</strong></p>
                    <p>Aby edytować pola tej grupy, użyj Visual Buildera:</p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=yap-visual-builder&group=' . urlencode($group_name)); ?>" class="button button-primary">
                            🎨 Otwórz w Visual Builder
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=yap-manage-groups'); ?>" class="button">
                            ← Powrót do Listy Grup
                        </a>
                    </p>
                </div>
                
                <div class="yap-card" style="margin-top: 20px;">
                    <h2>📋 Podgląd Pól</h2>
                    <?php
                    $schema = json_decode(file_get_contents($schema_file), true);
                    if (!empty($schema['fields'])) {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Pole</th><th>Typ</th><th>Wymagane</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($schema['fields'] as $field) {
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($field['label']) . '</strong><br><code>' . esc_html($field['name']) . '</code></td>';
                            echo '<td>' . esc_html($field['type']) . '</td>';
                            echo '<td>' . ($field['required'] ? '✓ Tak' : '—') . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                    }
                    ?>
                </div>
            </div>
            <?php
            return;
        } else {
            ?>
            <div class="wrap">
                <h1>❌ Grupa nie znaleziona</h1>
                <div class="notice notice-error">
                    <p><strong>Nie można znaleźć grupy:</strong> <?php echo esc_html($group_name); ?></p>
                    <p>Tabela: <code><?php echo esc_html($table_name); ?></code> nie istnieje.</p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=yap-manage-groups'); ?>" class="button button-primary">
                            ← Powrót do Listy Grup
                        </a>
                    </p>
                </div>
            </div>
            <?php
            return;
        }
    }
    
    // 🚨 BLOKADA: Nie pozwalaj edytować tabel _data bezpośrednio!
    if (strpos($table_name, '_data') !== false) {
        ?>
        <div class="wrap">
            <h1>❌ Błąd: Nieprawidłowa Tabela</h1>
            <div class="notice notice-error">
                <p><strong>Nie można edytować tabeli DATA bezpośrednio!</strong></p>
                <p>Tabela <code><?php echo esc_html($table_name); ?></code> przechowuje wartości pól przypisane do postów.</p>
                <p>Aby zarządzać polami:</p>
                <ul>
                    <li>✏️ Edytuj odpowiednią tabelę PATTERN (definicje pól)</li>
                    <li>📝 Edytuj wartości pól bezpośrednio w postach WordPress</li>
                </ul>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=yap'); ?>" class="button button-primary">
                        ← Powrót do Listy Grup
                    </a>
                </p>
            </div>
        </div>
        <?php
        return; // Zatrzymaj dalsze przetwarzanie
    }
    
    $fields = $wpdb->get_results("SELECT * FROM $table_name");

    if (isset($_POST['yap_update_group']) && !empty($_POST['field_id']) && is_array($_POST['field_id'])) {
        error_log("🔄 Rozpoczynam aktualizację grupy...");
        error_log("🔄 POST field_id: " . print_r($_POST['field_id'], true));
        error_log("🔄 POST field_name: " . print_r($_POST['field_name'], true));
        error_log("🔄 POST field_type: " . print_r($_POST['field_type'], true));
        error_log("🔄 POST field_value: " . print_r($_POST['field_value'], true));
        
        foreach ($_POST['field_id'] as $index => $field_id) {
            // Użyj $field_id jako klucza w tablicach field_name, field_type, field_value
            $field_name = sanitize_text_field($_POST['field_name'][$field_id] ?? '');
            $field_type = sanitize_text_field($_POST['field_type'][$field_id] ?? '');
            $field_value = sanitize_text_field($_POST['field_value'][$field_id] ?? '');
            
            error_log("🔍 Przetwarzam pole ID {$field_id}: name='{$field_name}', type='{$field_type}', value='{$field_value}'");
            
            // Pobierz aktualne dane pola żeby zachować nested_field_ids
            $current_field = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $field_id
            ));
            
            if (!empty($field_name) && !empty($field_type)) {
                // Przygotuj dane do aktualizacji
                $update_data = [
                    'user_name' => $field_name,
                    'field_type' => $field_type,
                    'field_value' => $field_value
                ];
                
                // WAŻNE: Zachowaj nested_field_ids jeśli pole je ma
                if ($current_field && !empty($current_field->nested_field_ids)) {
                    error_log("✅ Zachowuję nested_field_ids dla pola ID {$field_id}: " . $current_field->nested_field_ids);
                    $update_data['nested_field_ids'] = $current_field->nested_field_ids;
                }
                
                $wpdb->update(
                    $table_name,
                    $update_data,
                    ['id' => $field_id]
                );
                
                error_log("✅ Zaktualizowano pole ID {$field_id}: {$field_name} (typ: {$field_type}, wartość: {$field_value})");
            }
        }
        echo '<div class="updated"><p>Grupa została zaktualizowana.</p></div>';
        echo '<script>setTimeout(function(){ window.location.reload(); }, 500);</script>';
    }

    // Usunięto obsługę POST dla yap_add_field - teraz używamy tylko AJAX
    // Sprawdź admin.js i ajax-add-field.php dla implementacji AJAX

    if (isset($_GET['delete_field'])) {
        $field_id = intval($_GET['delete_field']);
        $wpdb->delete($table_name, ['id' => $field_id]);
        echo '<div class="updated"><p>Pole zostało usunięte.</p></div>';
    }

    if (isset($_POST['yap_add_nested_group'])) {
        $parent_field_id = intval($_POST['parent_field_id']);
        $nested_table_name = yap_add_nested_group($table_name, $parent_field_id);
        echo '<div class="updated"><p>Zagnieżdżona grupa została dodana.</p></div>';
    }

    if (strpos($table_name, 'pattern') !== false) {
        include plugin_dir_path(__FILE__) . 'views/pattern/edit-group-form.php';
        // Formularze add-field, add-nested-group i add-nested-field są już w edit-group-form.php
    } elseif (strpos($table_name, 'data') !== false) {
        include plugin_dir_path(__FILE__) . 'views/data/edit-group-form.php';
        include plugin_dir_path(__FILE__) . 'views/data/add-field-form.php';
        include plugin_dir_path(__FILE__) . 'views/data/add-nested-group-form.php';
        include plugin_dir_path(__FILE__) . 'views/data/add-nested-field-form.php';
    }
}

?>