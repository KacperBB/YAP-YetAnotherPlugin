<?php 

function yap_manage_groups_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    
    // Get groups from multiple sources (same as admin-page.php)
    $groups = [];
    
    // 1. From location_rules
    $location_groups = $wpdb->get_col(
        "SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_location_rules WHERE group_name != '' ORDER BY group_name ASC"
    );
    $groups = array_merge($groups, $location_groups);
    
    // 2. From yap-schemas directory
    $schema_dir = WP_CONTENT_DIR . '/yap-schemas/';
    if (file_exists($schema_dir)) {
        $schema_files = glob($schema_dir . '*.json');
        foreach ($schema_files as $file) {
            $groups[] = basename($file, '.json');
        }
    }
    
    // 3. From existing wp_yap_* tables
    $yap_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}yap_%_pattern'");
    $system_tables = ['location', 'options', 'field', 'sync', 'data', 'query', 'automations', 'automation'];
    
    foreach ($yap_tables as $table) {
        if (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_pattern$/', $table, $matches)) {
            $group_name = $matches[1];
            if (!in_array($group_name, $system_tables)) {
                $groups[] = $group_name;
            }
        }
    }
    
    // Unique and sort
    $groups = array_unique($groups);
    sort($groups);
    
    // Format for display
    $group_tables = [];
    foreach ($groups as $group_name) {
        if (!empty($group_name) && $group_name !== '__unconfigured__') {
            $table_name = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
            $group_tables[] = (object)[
                'table_name' => $table_name,
                'display_name' => $group_name
            ];
        }
    }

    ?>
    <div class="wrap">
        <h1>Zarządzaj Grupami</h1>
        <h2 class="nav-tab-wrapper">
            <a href="#pattern-groups" class="nav-tab nav-tab-active">Pattern Groups</a>
            <a href="#data-groups" class="nav-tab">Data Groups</a>
        </h2>
        <div id="pattern-groups" class="tab-content">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nazwa Grupy</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($group_tables)): ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 40px; color: #999;">
                                <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span>
                                <p style="margin: 10px 0 0;">Brak grup. Utwórz nową grupę w <a href="<?php echo admin_url('admin.php?page=yap-admin-page'); ?>">panelu głównym</a> lub <a href="<?php echo admin_url('admin.php?page=yap-visual-builder'); ?>">Visual Builder</a>.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($group_tables as $table): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $table->display_name))); ?></strong>
                                    <br><code style="font-size: 11px; color: #999;"><?php echo esc_html($table->table_name); ?></code>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($table->table_name)); ?>" class="button button-small">✏️ Edytuj Pola</a>
                                    <a href="<?php echo admin_url('admin.php?page=yap-visual-builder&group=' . urlencode($table->display_name)); ?>" class="button button-small">🎨 Visual Builder</a>
                                    <a href="#" class="button button-small button-link-delete yap-manage-delete-group" data-group="<?php echo esc_attr($table->display_name); ?>" data-table="<?php echo esc_attr($table->table_name); ?>">🗑️ Usuń</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="data-groups" class="tab-content" style="display:none;">
            <div class="notice notice-info" style="margin: 20px 0;">
                <p><strong>ℹ️ Info:</strong> Tabele Data przechowują wartości pól przypisane do konkretnych postów. Nie edytuj ich bezpośrednio - zarządzaj nimi przez posty w WordPress lub przez Pattern Groups.</p>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nazwa Tabeli</th>
                        <th>Grupa</th>
                        <th>Liczba Rekordów</th>
                        <th>Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($group_tables as $table): ?>
                        <?php $table_name = array_values((array)$table)[0]; ?>
                        <?php if (strpos($table_name, 'data') !== false): ?>
                            <?php 
                            $display_name = preg_replace('/^' . preg_quote($wpdb->prefix, '/') . 'group_(.*?)_data$/', '$1', $table_name); 
                            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                            ?>
                            <tr>
                                <td><code><?php echo esc_html($table_name); ?></code></td>
                                <td><strong><?php echo esc_html($display_name); ?></strong></td>
                                <td><?php echo esc_html($count); ?> rekordów</td>
                                <td>
                                    <span style="color: var(--yap-gray-600); font-size: 13px;">
                                        Dane pól w postach - nie edytuj bezpośrednio
                                    </span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Tabs switching
            const tabs = document.querySelectorAll('.nav-tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function(event) {
                    event.preventDefault();
                    tabs.forEach(t => t.classList.remove('nav-tab-active'));
                    contents.forEach(c => c.style.display = 'none');

                    tab.classList.add('nav-tab-active');
                    const target = document.querySelector(tab.getAttribute('href'));
                    target.style.display = 'block';
                });
            });
            
            // Delete group handler
            $(document).on('click', '.yap-manage-delete-group', function(e) {
                e.preventDefault();
                
                const $link = $(this);
                const groupName = $link.data('group');
                const tableName = $link.data('table');
                
                // Użyj modalu z Visual Builder jeśli dostępny
                if (window.YAPBuilderExt && window.YAPBuilderExt.showDeleteModal) {
                    window.YAPBuilderExt.showDeleteModal(
                        tableName,
                        'grupę "' + groupName + '"',
                        function() {
                            // On confirm callback
                            $.ajax({
                                url: yap_ajax.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'yap_delete_group',
                                    nonce: yap_ajax.nonce,
                                    table: tableName,
                                    group_name: groupName
                                },
                                success: function(response) {
                                    if (response.success) {
                                        window.YAPBuilderExt.toast(response.data.message, 'success');
                                        // Reload page to refresh list
                                        setTimeout(() => location.reload(), 800);
                                    } else {
                                        window.YAPBuilderExt.toast(response.data.message, 'error');
                                    }
                                },
                                error: function() {
                                    window.YAPBuilderExt.toast('Wystąpił błąd podczas usuwania grupy', 'error');
                                }
                            });
                        }
                    );
                } else {
                    // Fallback do confirm jeśli modal niedostępny
                    if (!confirm('⚠️ Czy na pewno chcesz usunąć grupę "' + groupName + '"?\n\nUsunięte zostaną:\n- Wszystkie pola\n- Zagnieżdżone grupy\n- Dane w postach\n\nTej operacji nie można cofnąć!')) {
                        return;
                    }
                    
                    $.ajax({
                        url: yap_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'yap_delete_group',
                            nonce: yap_ajax.nonce,
                            table: tableName,
                            group_name: groupName
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ ' + response.data.message);
                                location.reload();
                            } else {
                                alert('❌ ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('❌ Wystąpił błąd podczas usuwania grupy');
                        }
                    });
                }
            });
        });
    </script>
    <?php
}

?>