<?php
global $wpdb;

// Domyślnie $show_nested = false, jeśli nie ustawione
if (!isset($show_nested)) {
    $show_nested = false;
}

// Najpierw zbierz wszystkie grupy i ich zagnieżdżone dzieci
$all_groups = [];
$nested_relationships = []; // parent_table => [child_tables]
$nested_count = 0;

if (!empty($group_tables)) {
    // Krok 1: Zbierz wszystkie grupy
    foreach ($group_tables as $table) {
        $table_name = array_values((array)$table)[0];
        if (strpos($table_name, 'pattern') !== false) {
            $display_name = preg_replace('/^wp_group_(.*?)_pattern$/', '$1', $table_name);
            $is_nested = strpos($display_name, 'nested_group_') === 0;
            
            $all_groups[$table_name] = [
                'name' => $table_name,
                'display' => $display_name,
                'nested' => $is_nested,
                'children' => []
            ];
            
            if ($is_nested) {
                $nested_count++;
            }
        }
    }
    
    // Krok 2: Znajdź relacje rodzic-dziecko (sprawdzaj WSZYSTKIE grupy, nie tylko główne)
    foreach ($all_groups as $table_name => $group) {
        // Sprawdź pola nested_group w każdej grupie (głównej i zagnieżdżonej)
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT nested_field_ids FROM {$table_name} WHERE field_type = %s AND nested_field_ids IS NOT NULL",
            'nested_group'
        ));
        
        foreach ($fields as $field) {
            if (!empty($field->nested_field_ids)) {
                $nested_ids = json_decode($field->nested_field_ids, true);
                if (is_array($nested_ids)) {
                    foreach ($nested_ids as $nested_table) {
                        if (isset($all_groups[$nested_table])) {
                            if (!isset($nested_relationships[$table_name])) {
                                $nested_relationships[$table_name] = [];
                            }
                            $nested_relationships[$table_name][] = $nested_table;
                        }
                    }
                }
            }
        }
    }
}

// Funkcja rekurencyjna do dodawania dzieci
function add_children_recursive($table_name, &$all_groups, &$nested_relationships, &$result, $added = []) {
    if (in_array($table_name, $added)) {
        return $added; // Zapobiegnij zapętleniu
    }
    $added[] = $table_name;
    
    if (isset($nested_relationships[$table_name])) {
        foreach ($nested_relationships[$table_name] as $child_table) {
            if (isset($all_groups[$child_table])) {
                $child = $all_groups[$child_table];
                $child['is_child'] = true;
                $result[] = $child;
                
                // Rekurencyjnie dodaj dzieci tego dziecka
                $added = add_children_recursive($child_table, $all_groups, $nested_relationships, $result, $added);
            }
        }
    }
    
    return $added;
}

// Krok 3: Buduj hierarchiczną strukturę do wyświetlenia
$filtered_tables = [];
foreach ($all_groups as $table_name => $group) {
    if (!$group['nested']) {
        // Dodaj grupę główną
        $filtered_tables[] = $group;
        
        // Dodaj jej dzieci rekurencyjnie (jeśli pokazujemy zagnieżdżone)
        if ($show_nested) {
            add_children_recursive($table_name, $all_groups, $nested_relationships, $filtered_tables);
        }
    }
}
?>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th style="width: 40px;"></th>
            <th>Nazwa Grupy</th>
            <th style="width: 200px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($filtered_tables)): ?>
            <tr>
                <td colspan="3" style="text-align: center; padding: 40px; color: var(--yap-gray-600);">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <p style="font-size: 16px; margin: 0;">
                        <?php if ($nested_count > 0 && !$show_nested): ?>
                            Wszystkie grupy są zagnieżdżone. Włącz "Pokaż zagnieżdżone" aby je zobaczyć.
                        <?php else: ?>
                            Nie utworzono jeszcze żadnych grup pól.
                        <?php endif; ?>
                    </p>
                    <p style="font-size: 14px; color: var(--yap-gray-500); margin-top: 8px;">
                        <?php if ($nested_count == 0): ?>
                            Użyj formularza po lewej aby utworzyć pierwszą grupę!
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($filtered_tables as $group): 
                $is_child = isset($group['is_child']) && $group['is_child'];
            ?>
                <tr class="yap-group-row <?php echo $group['nested'] ? 'yap-nested-group-row' : ''; ?> <?php echo $is_child ? 'yap-child-group-row' : ''; ?>" 
                    data-group="<?php echo esc_attr($group['display']); ?>"
                    data-nested="<?php echo $group['nested'] ? '1' : '0'; ?>">
                    <td style="text-align: center; font-size: 20px; <?php echo $is_child ? 'padding-left: 30px;' : ''; ?>">
                        <?php echo $group['nested'] ? '📁' : '📦'; ?>
                    </td>
                    <td style="<?php echo $is_child ? 'padding-left: 20px;' : ''; ?>">
                        <?php if ($is_child): ?>
                            <span style="font-size: 14px; color: var(--yap-gray-500); margin-right: 8px;">↳</span>
                        <?php endif; ?>
                        <strong style="font-size: <?php echo $is_child ? '13px' : '14px'; ?>; color: <?php echo $is_child ? 'var(--yap-gray-700)' : 'var(--yap-gray-900)'; ?>;">
                            <?php echo esc_html($group['display']); ?>
                        </strong>
                        <?php if ($group['nested']): ?>
                            <span class="yap-nested-badge">Zagnieżdżona</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($group['name'])); ?>" 
                           class="button" style="margin-right: 8px;">
                            ✏️ Edytuj
                        </a>
                        <a href="#" 
                           class="button delete-field yap-delete-group" 
                           data-group="<?php echo esc_attr($group['display']); ?>"
                           data-table="<?php echo esc_attr($group['name']); ?>">
                            🗑️ Usuń
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($nested_count > 0): ?>
    <p class="yap-groups-info" style="margin-top: 12px; font-size: 13px; color: var(--yap-gray-600);">
        <?php if ($show_nested): ?>
            📊 Wyświetlono <strong><?php echo count($filtered_tables); ?></strong> grup 
            (<?php echo $nested_count; ?> zagnieżdżonych)
        <?php else: ?>
            📊 Wyświetlono <strong><?php echo count($filtered_tables); ?></strong> grup 
            (ukryto <?php echo $nested_count; ?> zagnieżdżonych)
        <?php endif; ?>
    </p>
<?php endif; ?>