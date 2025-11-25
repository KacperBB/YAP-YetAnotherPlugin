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
                <?php $display_name = preg_replace('/^wp_group_(.*?)_data$/', '$1', $table_name); ?>
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