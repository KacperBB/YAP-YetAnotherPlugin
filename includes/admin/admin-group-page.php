<?php 

function yap_manage_groups_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $group_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%'");

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
                    <?php foreach ($group_tables as $table): ?>
                        <?php $table_name = array_values((array)$table)[0]; ?>
                        <?php if (strpos($table_name, 'pattern') !== false): ?>
                            <?php $display_name = preg_replace('/^' . preg_quote($wpdb->prefix, '/') . 'group_(.*?)_pattern$/', '$1', $table_name); ?>
                            <tr>
                                <td><?php echo esc_html($display_name); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($table_name)); ?>">Edytuj</a> |
                                    <a href="<?php echo admin_url('admin.php?page=yap-delete-group&table=' . urlencode($table_name)); ?>" onclick="return confirm('Czy na pewno chcesz usunąć tę grupę?');">Usuń</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
    <?php
}

?>