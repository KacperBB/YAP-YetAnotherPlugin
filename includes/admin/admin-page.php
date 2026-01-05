<?php 


function yap_admin_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    
    // Get groups from multiple sources (same as Visual Builder)
    $groups = [];
    
    // 1. From location_rules (nowy system)
    $location_groups = $wpdb->get_col(
        "SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_location_rules WHERE group_name != '' ORDER BY group_name ASC"
    );
    $groups = array_merge($groups, $location_groups);
    
    // 2. From yap-schemas directory (Visual Builder saves)
    $schema_dir = WP_CONTENT_DIR . '/yap-schemas/';
    if (file_exists($schema_dir)) {
        $schema_files = glob($schema_dir . '*.json');
        foreach ($schema_files as $file) {
            $groups[] = basename($file, '.json');
        }
    }
    
    // 3. From existing wp_yap_* tables (stare grupy)
    $yap_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}yap_%_pattern'");
    
    // System tables to filter out
    $system_tables = ['location', 'options', 'field', 'sync', 'data', 'query', 'automations', 'automation'];
    
    foreach ($yap_tables as $table) {
        // Extract group name from wp_yap_GROUPNAME_pattern
        if (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_pattern$/', $table, $matches)) {
            $group_name = $matches[1];
            // Skip system tables
            if (!in_array($group_name, $system_tables)) {
                $groups[] = $group_name;
            }
        }
    }
    
    // Unique and sort
    $groups = array_unique($groups);
    sort($groups);
    
    // Format for group-list.php (expects $group_tables array)
    $filtered_tables = [];
    foreach ($groups as $group_name) {
        if (!empty($group_name) && $group_name !== '__unconfigured__') {
            $table_name = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
            $filtered_tables[] = (object)[
                'table_name' => $table_name,
                'group_name' => $group_name
            ];
        }
    }
    
    $group_tables = $filtered_tables;

    ?>
    <div class="wrap yap-admin-page">
        <h1>YAP - Yet Another Plugin</h1>
        
        <div class="yap-layout-grid">
            <!-- LEFT SIDEBAR: Create Group Form -->
            <div class="yap-sidebar">
                <div class="yap-card yap-sidebar-card">
                    <div class="yap-card-header">
                        <h2>➕ Utwórz Grupę</h2>
                    </div>
                    
                    <form id="yap-add-group-form" class="yap-create-form">
                        <div class="yap-form-group">
                            <label for="group_name">Nazwa grupy</label>
                            <input type="text" id="group_name" name="group_name" placeholder="np. dane_produktu" required>
                            <span class="yap-help-text">Bez spacji, tylko a-z, 0-9, _</span>
                        </div>
                        
                        <div class="yap-form-group">
                            <label for="post_type">Typ posta</label>
                            <select id="post_type" name="post_type">
                                <option value="">🌐 Wszystkie</option>
                                <?php
                                $post_types = get_post_types(['public' => true], 'objects');
                                foreach ($post_types as $post_type) {
                                    $icon = $post_type->name === 'post' ? '📝' : ($post_type->name === 'page' ? '📄' : '📦');
                                    echo '<option value="' . esc_attr($post_type->name) . '">' . $icon . ' ' . esc_html($post_type->label) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="yap-form-group">
                            <label for="category">Kategoria</label>
                            <select id="category" name="category">
                                <option value="">🏷️ Wszystkie</option>
                                <?php
                                $categories = get_categories();
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->term_id) . '">📁 ' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                            <span class="yap-help-text">Tylko taxonomy "category"</span>
                        </div>
                        
                        <button type="submit" class="button button-primary yap-submit-btn">
                            <span class="yap-btn-text">✨ Zapisz grupę</span>
                            <span class="yap-btn-loader" style="display: none;">
                                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- RIGHT CONTENT: Groups List -->
            <div class="yap-main-content">
                <div class="yap-card">
                    <div class="yap-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>📋 Twoje Grupy Pól</h2>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <label class="yap-toggle-label">
                                <input type="checkbox" id="yap-show-nested" class="yap-toggle-checkbox">
                                <span class="yap-toggle-text">📦 Pokaż zagnieżdżone</span>
                            </label>
                            <button class="button yap-refresh-btn" onclick="yapRefreshGroups()">
                                🔄 Odśwież
                            </button>
                        </div>
                    </div>
                    
                    <div id="yap-groups-list">
                        <?php 
                        $show_nested = false; // Domyślnie ukryte
                        include plugin_dir_path(__FILE__) . 'views/pattern/group-list.php'; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="yap-toast-container"></div>
    
    <script>
    jQuery(document).ready(function($) {
        // Toast Notification System
        window.yapShowToast = function(message, type = 'success') {
            const toast = $('<div class="yap-toast yap-toast-' + type + '">' + message + '</div>');
            $('#yap-toast-container').append(toast);
            
            setTimeout(() => toast.addClass('yap-toast-show'), 10);
            
            setTimeout(() => {
                toast.removeClass('yap-toast-show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };
        
        // Refresh Groups List
        window.yapRefreshGroups = function() {
            const showNested = $('#yap-show-nested').is(':checked');
            $('.yap-refresh-btn').prop('disabled', true).html('⏳ Ładowanie...');
            
            $.ajax({
                url: yap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'yap_refresh_groups',
                    nonce: yap_ajax.nonce,
                    show_nested: showNested
                },
                success: function(response) {
                    if (response.success) {
                        $('#yap-groups-list').html(response.data.html);
                    }
                },
                complete: function() {
                    $('.yap-refresh-btn').prop('disabled', false).html('🔄 Odśwież');
                }
            });
        };
        
        // Toggle Nested Groups
        $('#yap-show-nested').on('change', function() {
            yapRefreshGroups();
        });
        
        // Create Group Form Submit
        $('#yap-add-group-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $form.find('.yap-submit-btn');
            const $btnText = $btn.find('.yap-btn-text');
            const $btnLoader = $btn.find('.yap-btn-loader');
            
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoader.show();
            
            $.ajax({
                url: yap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'yap_save_group',
                    nonce: yap_ajax.nonce,
                    group_name: $('#group_name').val(),
                    post_type: $('#post_type').val(),
                    category: $('#category').val()
                },
                success: function(response) {
                    if (response.success) {
                        yapShowToast('✅ ' + response.data.message, 'success');
                        $form[0].reset();
                        yapRefreshGroups();
                    } else {
                        yapShowToast('❌ ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    yapShowToast('❌ Wystąpił błąd podczas tworzenia grupy', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            });
        });
        
        // Delete Group (delegated event)
        $(document).on('click', '.yap-delete-group', function(e) {
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
                                    yapRefreshGroups();
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
                            yapShowToast('🗑️ ' + response.data.message, 'success');
                            yapRefreshGroups();
                        } else {
                            yapShowToast('❌ ' + response.data.message, 'error');
                        }
                    },
                    error: function() {
                        yapShowToast('❌ Wystąpił błąd podczas usuwania grupy', 'error');
                    }
                });
            }
        });
    });
    </script>
    <?php
}

?>