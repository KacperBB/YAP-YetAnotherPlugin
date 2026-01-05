<?php

/**
 * Field Validation Class
 * Obsługa walidacji pól z różnymi regułami
 */
class YAP_Field_Validator {
    
    private $errors = [];
    
    /**
     * Waliduj wartość pola na podstawie reguł
     * 
     * @param mixed $value Wartość do walidacji
     * @param array $rules Reguły walidacji
     * @param string $field_name Nazwa pola (dla komunikatów błędów)
     * @return bool True jeśli walidacja przeszła
     */
    public function validate($value, $rules, $field_name = 'Pole') {
        if (!is_array($rules)) {
            $rules = json_decode($rules, true);
        }
        
        if (empty($rules)) {
            return true;
        }
        
        $this->errors = [];
        
        // Required
        if (isset($rules['required']) && $rules['required']) {
            if (empty($value) && $value !== '0') {
                $this->errors[] = "{$field_name} jest wymagane.";
            }
        }
        
        // Min length
        if (isset($rules['min_length']) && !empty($value)) {
            $min = (int)$rules['min_length'];
            if (strlen($value) < $min) {
                $this->errors[] = "{$field_name} musi mieć minimum {$min} znaków.";
            }
        }
        
        // Max length
        if (isset($rules['max_length']) && !empty($value)) {
            $max = (int)$rules['max_length'];
            if (strlen($value) > $max) {
                $this->errors[] = "{$field_name} może mieć maksymalnie {$max} znaków.";
            }
        }
        
        // Min value (dla liczb)
        if (isset($rules['min_value']) && is_numeric($value)) {
            $min = (float)$rules['min_value'];
            if ((float)$value < $min) {
                $this->errors[] = "{$field_name} musi być większe lub równe {$min}.";
            }
        }
        
        // Max value (dla liczb)
        if (isset($rules['max_value']) && is_numeric($value)) {
            $max = (float)$rules['max_value'];
            if ((float)$value > $max) {
                $this->errors[] = "{$field_name} musi być mniejsze lub równe {$max}.";
            }
        }
        
        // Pattern (regex)
        if (isset($rules['pattern']) && !empty($rules['pattern']) && !empty($value)) {
            if (!preg_match($rules['pattern'], $value)) {
                $message = isset($rules['pattern_message']) ? $rules['pattern_message'] : "{$field_name} ma nieprawidłowy format.";
                $this->errors[] = $message;
            }
        }
        
        // Email
        if (isset($rules['email']) && $rules['email'] && !empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "{$field_name} musi być prawidłowym adresem email.";
            }
        }
        
        // URL
        if (isset($rules['url']) && $rules['url'] && !empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[] = "{$field_name} musi być prawidłowym adresem URL.";
            }
        }
        
        // Date format
        if (isset($rules['date_format']) && !empty($rules['date_format']) && !empty($value)) {
            $date = \DateTime::createFromFormat($rules['date_format'], $value);
            if (!$date || $date->format($rules['date_format']) !== $value) {
                $this->errors[] = "{$field_name} musi być w formacie {$rules['date_format']}.";
            }
        }
        
        // Custom validation
        if (isset($rules['custom']) && is_callable($rules['custom'])) {
            $result = call_user_func($rules['custom'], $value, $field_name);
            if ($result !== true) {
                $this->errors[] = is_string($result) ? $result : "{$field_name} nie przeszło walidacji niestandardowej.";
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Pobierz błędy walidacji
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Pobierz pierwszy błąd
     */
    public function get_first_error() {
        return !empty($this->errors) ? $this->errors[0] : null;
    }
    
    /**
     * Czy są błędy
     */
    public function has_errors() {
        return !empty($this->errors);
    }
    
    /**
     * Wyczyść błędy
     */
    public function clear_errors() {
        $this->errors = [];
    }
}

/**
 * Conditional Logic Handler
 * Obsługa logiki warunkowej wyświetlania pól
 */
class YAP_Conditional_Logic {
    
    /**
     * Sprawdź czy pole powinno być widoczne
     * 
     * @param array $conditions Warunki do sprawdzenia
     * @param array $values Wartości wszystkich pól
     * @return bool True jeśli pole powinno być widoczne
     */
    public static function should_show_field($conditions, $values) {
        if (empty($conditions)) {
            return true;
        }
        
        if (!is_array($conditions)) {
            $conditions = json_decode($conditions, true);
        }
        
        if (empty($conditions['rules'])) {
            return true;
        }
        
        $logic = isset($conditions['logic']) ? $conditions['logic'] : 'and';
        $results = [];
        
        foreach ($conditions['rules'] as $rule) {
            $field = $rule['field'] ?? '';
            $operator = $rule['operator'] ?? '==';
            $value = $rule['value'] ?? '';
            
            if (!isset($values[$field])) {
                $results[] = false;
                continue;
            }
            
            $field_value = $values[$field];
            
            switch ($operator) {
                case '==':
                    $results[] = ($field_value == $value);
                    break;
                case '!=':
                    $results[] = ($field_value != $value);
                    break;
                case '>':
                    $results[] = ($field_value > $value);
                    break;
                case '<':
                    $results[] = ($field_value < $value);
                    break;
                case '>=':
                    $results[] = ($field_value >= $value);
                    break;
                case '<=':
                    $results[] = ($field_value <= $value);
                    break;
                case 'contains':
                    $results[] = (strpos($field_value, $value) !== false);
                    break;
                case 'not_contains':
                    $results[] = (strpos($field_value, $value) === false);
                    break;
                case 'starts_with':
                    $results[] = (strpos($field_value, $value) === 0);
                    break;
                case 'ends_with':
                    $results[] = (substr($field_value, -strlen($value)) === $value);
                    break;
                case 'empty':
                    $results[] = empty($field_value);
                    break;
                case 'not_empty':
                    $results[] = !empty($field_value);
                    break;
                case 'in':
                    $value_array = is_array($value) ? $value : explode(',', $value);
                    $results[] = in_array($field_value, $value_array);
                    break;
                case 'not_in':
                    $value_array = is_array($value) ? $value : explode(',', $value);
                    $results[] = !in_array($field_value, $value_array);
                    break;
                default:
                    $results[] = false;
            }
        }
        
        // Zastosuj logikę AND lub OR
        if ($logic === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }
    
    /**
     * Generuj JSON z warunkami dla JavaScript
     */
    public static function to_js_conditions($conditions) {
        if (!is_array($conditions)) {
            $conditions = json_decode($conditions, true);
        }
        
        return wp_json_encode($conditions);
    }
}

/**
 * Helper functions
 */

/**
 * Waliduj pole
 */
function yap_validate_field($value, $rules, $field_name = 'Pole') {
    $validator = new YAP_Field_Validator();
    return $validator->validate($value, $rules, $field_name);
}

/**
 * Pobierz błędy walidacji
 */
function yap_get_validation_errors() {
    global $yap_validator;
    if (!isset($yap_validator)) {
        $yap_validator = new YAP_Field_Validator();
    }
    return $yap_validator->get_errors();
}

/**
 * Sprawdź logikę warunkową
 */
function yap_check_conditional($conditions, $values) {
    return YAP_Conditional_Logic::should_show_field($conditions, $values);
}
