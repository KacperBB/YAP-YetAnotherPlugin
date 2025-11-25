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
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Pełna Nazwa Grupy</th>
                        <th>Przycięta Nazwa Grupy</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($group_tables as $table): ?>
                        <?php $table_name = array_values((array)$table)[0]; ?>
                        <?php if (strpos($table_name, 'data') !== false): ?>
                            <?php $display_name = preg_replace('/^' . preg_quote($wpdb->prefix, '/') . 'group_(.*?)_data$/', '$1', $table_name); ?>
                            <tr>
                                <td><?php echo esc_html($table_name); ?></td>
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