<?php

/**
 * Repeater Field Handler
 * Obsługa powtarzalnych pól (jak ACF Repeater)
 */
class YAP_Repeater {
    
    /**
     * Pobierz dane repeatera
     * 
     * @param string $field_name Nazwa pola repeatera
     * @param int $post_id ID posta
     * @param string $group_name Nazwa grupy
     * @return array Tablica rzędów repeatera
     */
    public static function get_repeater($field_name, $post_id, $group_name) {
        global $wpdb;
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
        $safe_table = esc_sql($data_table);
        
        // Pobierz główne pole repeatera
        $repeater_field = $wpdb->get_row($wpdb->prepare(
            "SELECT field_value, nested_field_ids FROM `{$safe_table}` 
             WHERE user_name = %s AND associated_id = %d AND field_type = 'repeater'",
            $field_name,
            $post_id
        ));
        
        if (!$repeater_field) {
            return [];
        }
        
        // Dekoduj dane
        $rows = json_decode($repeater_field->field_value, true);
        return is_array($rows) ? $rows : [];
    }
    
    /**
     * Zapisz dane repeatera
     */
    public static function update_repeater($field_name, $post_id, $group_name, $rows) {
        global $wpdb;
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
        
        $encoded_rows = wp_json_encode($rows);
        $generated_name = 'repeater_' . sanitize_title($field_name);
        
        // Sprawdź czy repeater już istnieje
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$data_table} 
             WHERE generated_name = %s AND associated_id = %d",
            $generated_name,
            $post_id
        ));
        
        if ($existing) {
            // UPDATE istniejącego repeatera
            $result = $wpdb->update(
                $data_table,
                [
                    'field_value' => $encoded_rows,
                    'user_name' => $field_name,
                    'field_type' => 'repeater'
                ],
                ['id' => $existing]
            );
        } else {
            // INSERT nowego repeatera
            $result = $wpdb->insert(
                $data_table,
                [
                    'generated_name' => $generated_name,
                    'user_name' => $field_name,
                    'associated_id' => $post_id,
                    'field_type' => 'repeater',
                    'field_value' => $encoded_rows
                ]
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Dodaj rząd do repeatera
     */
    public static function add_row($field_name, $post_id, $group_name, $row_data) {
        $rows = self::get_repeater($field_name, $post_id, $group_name);
        $rows[] = $row_data;
        return self::update_repeater($field_name, $post_id, $group_name, $rows);
    }
    
    /**
     * Usuń rząd z repeatera
     */
    public static function delete_row($field_name, $post_id, $group_name, $row_index) {
        $rows = self::get_repeater($field_name, $post_id, $group_name);
        if (isset($rows[$row_index])) {
            unset($rows[$row_index]);
            $rows = array_values($rows); // Re-index
            return self::update_repeater($field_name, $post_id, $group_name, $rows);
        }
        return false;
    }
    
    /**
     * Zaktualizuj rząd repeatera
     */
    public static function update_row($field_name, $post_id, $group_name, $row_index, $row_data) {
        $rows = self::get_repeater($field_name, $post_id, $group_name);
        if (isset($rows[$row_index])) {
            $rows[$row_index] = $row_data;
            return self::update_repeater($field_name, $post_id, $group_name, $rows);
        }
        return false;
    }
    
    /**
     * Pobierz liczbę rzędów
     */
    public static function count_rows($field_name, $post_id, $group_name) {
        $rows = self::get_repeater($field_name, $post_id, $group_name);
        return count($rows);
    }
    
    /**
     * Czy repeater ma rządy
     */
    public static function has_rows($field_name, $post_id, $group_name) {
        return self::count_rows($field_name, $post_id, $group_name) > 0;
    }
}

/**
 * Helper Functions
 */

/**
 * Pobierz repeater (ACF-like API)
 */
function yap_get_repeater($field_name, $post_id, $group_name) {
    return YAP_Repeater::get_repeater($field_name, $post_id, $group_name);
}

/**
 * Czy repeater ma rządy (ACF-like API)
 */
function yap_have_rows($field_name, $post_id, $group_name) {
    return YAP_Repeater::has_rows($field_name, $post_id, $group_name);
}

/**
 * Pobierz liczbę rzędów (ACF-like API)
 */
function yap_count_rows($field_name, $post_id, $group_name) {
    return YAP_Repeater::count_rows($field_name, $post_id, $group_name);
}

/**
 * Dodaj rząd do repeatera
 */
function yap_add_row($field_name, $post_id, $group_name, $row_data) {
    return YAP_Repeater::add_row($field_name, $post_id, $group_name, $row_data);
}

/**
 * Usuń rząd repeatera
 */
function yap_delete_row($field_name, $post_id, $group_name, $row_index) {
    return YAP_Repeater::delete_row($field_name, $post_id, $group_name, $row_index);
}

/**
 * Przykład użycia - Repeater (ACF-like loop)
 * 
 * if (yap_have_rows('team_members', get_the_ID(), 'about_page')) {
 *     $rows = yap_get_repeater('team_members', get_the_ID(), 'about_page');
 *     foreach ($rows as $row) {
 *         echo '<h3>' . $row['name'] . '</h3>';
 *         echo '<p>' . $row['position'] . '</p>';
 *         echo '<img src="' . $row['photo'] . '">';
 *     }
 * }
 * 
 * NOTE: Flexible Content moved to includes/flexible-content.php
 * Use: yap_flexible($group_name, $field_name) or yap_get_flexible($group_name, $field_name)
 */
