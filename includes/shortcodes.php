<?php
/**
 * YAP Shortcodes and Template Functions
 * Funkcje do wyświetlania pól w szablonach i content
 */

/**
 * Shortcode do wyświetlania pojedynczego pola
 * Użycie: [yap_field group="GroupName" field="field_name"]
 */
function yap_field_shortcode($atts) {
    $atts = shortcode_atts([
        'group' => '',
        'field' => '',
        'post_id' => null,
        'format' => 'html' // html, text, raw
    ], $atts);
    
    if (empty($atts['group']) || empty($atts['field'])) {
        return '<!-- YAP Error: group and field attributes are required -->';
    }
    
    $post_id = $atts['post_id'] ?? get_the_ID();
    if (!$post_id) {
        return '<!-- YAP Error: No post ID -->';
    }
    
    // Pobierz wartość z post meta
    $group_data = get_post_meta($post_id, 'yap_' . $atts['group'], true);
    
    if (empty($group_data) || !isset($group_data[$atts['field']])) {
        return ''; // Puste pole
    }
    
    $value = $group_data[$atts['field']];
    
    // Formatowanie wyjścia
    switch ($atts['format']) {
        case 'raw':
            return $value;
        case 'text':
            return esc_html($value);
        case 'html':
        default:
            return wp_kses_post($value);
    }
}
add_shortcode('yap_field', 'yap_field_shortcode');

/**
 * Template function do wyświetlania pola
 * Użycie: <?php yap_field('GroupName', 'field_name'); ?>
 */
function yap_field($group, $field, $post_id = null, $echo = true) {
    $post_id = $post_id ?? get_the_ID();
    
    if (!$post_id) {
        return '';
    }
    
    $group_data = get_post_meta($post_id, 'yap_' . $group, true);
    
    if (empty($group_data) || !isset($group_data[$field])) {
        return '';
    }
    
    $value = wp_kses_post($group_data[$field]);
    
    if ($echo) {
        echo $value;
    } else {
        return $value;
    }
}

/**
 * Template function do wyświetlania repeatera
 * Użycie: <?php yap_repeater('GroupName', 'repeater_field'); ?>
 */
function yap_repeater($group, $field, $post_id = null) {
    $post_id = $post_id ?? get_the_ID();
    
    if (!$post_id) {
        return [];
    }
    
    $group_data = get_post_meta($post_id, 'yap_' . $group, true);
    
    if (empty($group_data) || !isset($group_data[$field])) {
        return [];
    }
    
    $rows = $group_data[$field];
    
    // Jeśli to nie jest tablica, spróbuj zdekodować JSON
    if (!is_array($rows)) {
        $rows = json_decode($rows, true);
    }
    
    if (!is_array($rows)) {
        return [];
    }
    
    return $rows;
}

/**
 * Shortcode do wyświetlania repeatera z szablonem
 * Użycie: [yap_repeater group="Test" field="repeater_name"]<div>{text_1}</div>[/yap_repeater]
 */
function yap_repeater_shortcode($atts, $content = null) {
    $atts = shortcode_atts([
        'group' => '',
        'field' => '',
        'post_id' => null,
    ], $atts);
    
    if (empty($atts['group']) || empty($atts['field'])) {
        return '<!-- YAP Error: group and field attributes are required -->';
    }
    
    $rows = yap_repeater($atts['group'], $atts['field'], $atts['post_id']);
    
    if (empty($rows)) {
        return '';
    }
    
    $output = '';
    
    foreach ($rows as $index => $row) {
        $row_content = $content;
        
        // Zamień placeholdery {field_name} na wartości
        foreach ($row as $key => $value) {
            $row_content = str_replace('{' . $key . '}', esc_html($value), $row_content);
        }
        
        // Dodaj index
        $row_content = str_replace('{index}', $index, $row_content);
        $row_content = str_replace('{index1}', $index + 1, $row_content);
        
        $output .= $row_content;
    }
    
    return $output;
}
add_shortcode('yap_repeater', 'yap_repeater_shortcode');

/**
 * Template function do wyświetlania grupy
 * Użycie: <?php $group = yap_group('GroupName', 'group_field'); ?>
 */
function yap_group($group, $field, $post_id = null) {
    $post_id = $post_id ?? get_the_ID();
    
    if (!$post_id) {
        return [];
    }
    
    $group_data = get_post_meta($post_id, 'yap_' . $group, true);
    
    if (empty($group_data) || !isset($group_data[$field])) {
        return [];
    }
    
    $data = $group_data[$field];
    
    // Jeśli to nie jest tablica, spróbuj zdekodować JSON
    if (!is_array($data)) {
        $data = json_decode($data, true);
    }
    
    if (!is_array($data)) {
        return [];
    }
    
    return $data;
}

/**
 * Shortcode do wyświetlania grupy
 * Użycie: [yap_group group="Test" field="group_name" subfield="text_1"]
 */
function yap_group_shortcode($atts) {
    $atts = shortcode_atts([
        'group' => '',
        'field' => '',
        'subfield' => '',
        'post_id' => null,
        'format' => 'html'
    ], $atts);
    
    if (empty($atts['group']) || empty($atts['field']) || empty($atts['subfield'])) {
        return '<!-- YAP Error: group, field, and subfield attributes are required -->';
    }
    
    $group_data = yap_group($atts['group'], $atts['field'], $atts['post_id']);
    
    if (empty($group_data) || !isset($group_data[$atts['subfield']])) {
        return '';
    }
    
    $value = $group_data[$atts['subfield']];
    
    // Formatowanie wyjścia
    switch ($atts['format']) {
        case 'raw':
            return $value;
        case 'text':
            return esc_html($value);
        case 'html':
        default:
            return wp_kses_post($value);
    }
}
add_shortcode('yap_group', 'yap_group_shortcode');

/**
 * Helper function - sprawdź czy pole ma wartość
 */
function yap_has_field($group, $field, $post_id = null) {
    $post_id = $post_id ?? get_the_ID();
    
    if (!$post_id) {
        return false;
    }
    
    $group_data = get_post_meta($post_id, 'yap_' . $group, true);
    
    return !empty($group_data) && isset($group_data[$field]) && !empty($group_data[$field]);
}

/**
 * Helper function - pobierz wszystkie pola z grupy
 */
function yap_get_group($group, $post_id = null) {
    $post_id = $post_id ?? get_the_ID();
    
    if (!$post_id) {
        return [];
    }
    
    $group_data = get_post_meta($post_id, 'yap_' . $group, true);
    
    return is_array($group_data) ? $group_data : [];
}
