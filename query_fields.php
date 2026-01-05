<?php
// Quick database query to check field structure
$mysqli = new mysqli('localhost', 'root', '', 'fagpress');
if ($mysqli->connect_error) {
    die('Connection error: ' . $mysqli->connect_error);
}

echo "=== FIELDS IN DATABASE ===\n\n";
$result = $mysqli->query('SELECT field_id, field_name, field_type, field_config FROM wp_yap_field_metadata LIMIT 20');
while ($row = $result->fetch_assoc()) {
    echo "Field: " . $row['field_name'] . "\n";
    echo "  Type: " . $row['field_type'] . "\n";
    echo "  Config (first 100 chars): " . substr($row['field_config'], 0, 100) . "\n";
    
    if (!empty($row['field_config'])) {
        $config = json_decode($row['field_config'], true);
        if (is_array($config)) {
            echo "  Config type: " . ($config['type'] ?? 'NONE') . "\n";
            echo "  Has choices: " . (isset($config['choices']) ? 'YES (' . count($config['choices']) . ')' : 'NO') . "\n";
            echo "  Has sub_fields: " . (isset($config['sub_fields']) ? 'YES (' . count($config['sub_fields']) . ')' : 'NO') . "\n";
        }
    }
    echo "\n";
}
?>
