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
                <?php $display_name = preg_replace('/^wp_group_(.*?)_pattern$/', '$1', $table_name); ?>
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