<?php

/**
 * Helper functions for advanced field types
 */

/**
 * Get JSON field value (decoded)
 */
function yap_get_json($field_name, $post_id, $group_name, $as_array = true) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    if (!$value) return $as_array ? [] : null;
    
    $decoded = json_decode($value, $as_array);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : ($as_array ? [] : null);
}

/**
 * Update JSON field value (with validation)
 */
function yap_update_json($field_name, $value, $post_id, $group_name) {
    // Validate JSON
    if (is_string($value)) {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON: ' . json_last_error_msg());
        }
    } else {
        $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    return yap_update_field($field_name, $value, $post_id, $group_name);
}

/**
 * Get code field value (with syntax highlighting info)
 */
function yap_get_code($field_name, $post_id, $group_name) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    if (!$value) return null;
    
    // Try to decode if it's stored as JSON with language info
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['code'])) {
        return $decoded;
    }
    
    // Return as plain code
    return [
        'code' => $value,
        'language' => 'text'
    ];
}

/**
 * Get value for select field
 */
function yap_get_select($field_name, $post_id, $group_name) {
    return yap_get_field($field_name, $post_id, $group_name);
}

/**
 * Get values for checkbox field (returns array)
 */
function yap_get_checkbox($field_name, $post_id, $group_name) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    return $value ? json_decode($value, true) : [];
}

/**
 * Get value for radio field
 */
function yap_get_radio($field_name, $post_id, $group_name) {
    return yap_get_field($field_name, $post_id, $group_name);
}

/**
 * Get boolean value for true/false field
 */
function yap_get_true_false($field_name, $post_id, $group_name) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    return $value === '1' || $value === 'true' || $value === true;
}

/**
 * Get date value (formatted)
 */
function yap_get_date($field_name, $post_id, $group_name, $format = 'Y-m-d') {
    $value = yap_get_field($field_name, $post_id, $group_name);
    if (!$value) return null;
    
    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
}

/**
 * Get datetime value (formatted)
 */
function yap_get_datetime($field_name, $post_id, $group_name, $format = 'Y-m-d H:i:s') {
    $value = yap_get_field($field_name, $post_id, $group_name);
    if (!$value) return null;
    
    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
}

/**
 * Get time value (formatted)
 */
function yap_get_time($field_name, $post_id, $group_name, $format = 'H:i') {
    $value = yap_get_field($field_name, $post_id, $group_name);
    if (!$value) return null;
    
    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
}

/**
 * Get color value
 */
function yap_get_color($field_name, $post_id, $group_name) {
    return yap_get_field($field_name, $post_id, $group_name);
}

/**
 * Get range value
 */
function yap_get_range($field_name, $post_id, $group_name) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Get file (attachment ID or URL)
 */
function yap_get_file($field_name, $post_id, $group_name, $return_url = false) {
    $file_id = yap_get_field($field_name, $post_id, $group_name);
    
    if (!$file_id || !is_numeric($file_id)) {
        return false;
    }
    
    if ($return_url) {
        return wp_get_attachment_url($file_id);
    }
    
    return $file_id;
}

/**
 * Get file with metadata
 */
function yap_get_file_array($field_name, $post_id, $group_name) {
    $file_id = yap_get_field($field_name, $post_id, $group_name);
    
    if (!$file_id || !is_numeric($file_id)) {
        return false;
    }
    
    return [
        'id' => $file_id,
        'url' => wp_get_attachment_url($file_id),
        'title' => get_the_title($file_id),
        'filename' => basename(get_attached_file($file_id)),
        'filesize' => filesize(get_attached_file($file_id)),
        'mime_type' => get_post_mime_type($file_id)
    ];
}

/**
 * Get gallery (array of image IDs or URLs)
 */
function yap_get_gallery($field_name, $post_id, $group_name, $return_urls = false) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    $image_ids = $value ? json_decode($value, true) : [];
    
    if (!$return_urls) {
        return $image_ids;
    }
    
    $urls = [];
    foreach ($image_ids as $image_id) {
        if (is_numeric($image_id)) {
            $urls[] = wp_get_attachment_url($image_id);
        }
    }
    
    return $urls;
}

/**
 * Get WYSIWYG content
 */
function yap_get_wysiwyg($field_name, $post_id, $group_name) {
    return yap_get_field($field_name, $post_id, $group_name);
}

/**
 * Get oEmbed HTML
 */
function yap_get_oembed($field_name, $post_id, $group_name) {
    $url = yap_get_field($field_name, $post_id, $group_name);
    if (!$url) return '';
    
    return wp_oembed_get($url);
}

/**
 * Get post object (returns WP_Post)
 */
function yap_get_post_object($field_name, $post_id, $group_name) {
    $post_id = yap_get_field($field_name, $post_id, $group_name);
    return $post_id ? get_post($post_id) : null;
}

/**
 * Get relationship (array of post IDs or WP_Post objects)
 */
function yap_get_relationship($field_name, $post_id, $group_name, $return_posts = false) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    $post_ids = $value ? json_decode($value, true) : [];
    
    if (!$return_posts) {
        return $post_ids;
    }
    
    $posts = [];
    foreach ($post_ids as $id) {
        $post = get_post($id);
        if ($post) {
            $posts[] = $post;
        }
    }
    
    return $posts;
}

/**
 * Get taxonomy term
 */
function yap_get_taxonomy($field_name, $post_id, $group_name, $return_term = false) {
    $term_id = yap_get_field($field_name, $post_id, $group_name);
    
    if (!$term_id) return null;
    
    if ($return_term) {
        return get_term($term_id);
    }
    
    return $term_id;
}

/**
 * Get user (returns WP_User)
 */
function yap_get_user($field_name, $post_id, $group_name) {
    $user_id = yap_get_field($field_name, $post_id, $group_name);
    return $user_id ? get_user_by('id', $user_id) : null;
}

/**
 * Get Google Map data (returns array with lat, lng, address)
 */
function yap_get_google_map($field_name, $post_id, $group_name) {
    $value = yap_get_field($field_name, $post_id, $group_name);
    return $value ? json_decode($value, true) : null;
}

/**
 * Render Google Map HTML
 */
function yap_render_google_map($field_name, $post_id, $group_name, $width = '100%', $height = '400px', $zoom = 14) {
    $map_data = yap_get_google_map($field_name, $post_id, $group_name);
    
    if (!$map_data || !isset($map_data['lat']) || !isset($map_data['lng'])) {
        return '';
    }
    
    $lat = esc_attr($map_data['lat']);
    $lng = esc_attr($map_data['lng']);
    $address = isset($map_data['address']) ? esc_attr($map_data['address']) : '';
    
    return sprintf(
        '<div class="yap-google-map" data-lat="%s" data-lng="%s" data-zoom="%d" style="width: %s; height: %s;"></div>
        <div class="yap-map-address">%s</div>',
        $lat,
        $lng,
        $zoom,
        esc_attr($width),
        esc_attr($height),
        $address
    );
}
