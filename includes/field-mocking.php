<?php
/**
 * YAP Field Mocking
 * Generate fake data for testing and demos
 * 
 * Usage:
 * wp yap:seed --group=products --count=20
 * wp yap:seed --group=posts --count=50 --locale=pl_PL
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Mocking {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register WP-CLI command if available
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('yap:seed', [$this, 'seed_command']);
        }
    }
    
    /**
     * WP-CLI command: Seed field group with fake data
     * 
     * ## OPTIONS
     * 
     * --group=<group_name>
     * : Name of the group to seed
     * 
     * [--count=<number>]
     * : Number of records to generate (default: 10)
     * 
     * [--locale=<locale>]
     * : Locale for fake data (default: en_US, available: pl_PL, de_DE, fr_FR, es_ES)
     * 
     * [--clear]
     * : Clear existing data before seeding
     * 
     * ## EXAMPLES
     * 
     *     wp yap:seed --group=products --count=20
     *     wp yap:seed --group=posts --count=50 --locale=pl_PL
     *     wp yap:seed --group=users --count=100 --clear
     * 
     * @when after_wp_load
     */
    public function seed_command($args, $assoc_args) {
        global $wpdb;
        
        if (!isset($assoc_args['group'])) {
            WP_CLI::error('--group parameter is required.');
        }
        
        $group_name = sanitize_key($assoc_args['group']);
        $count = isset($assoc_args['count']) ? intval($assoc_args['count']) : 10;
        $locale = isset($assoc_args['locale']) ? $assoc_args['locale'] : 'en_US';
        
        // Check if group exists
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM yap_groups WHERE group_name = %s", $group_name));
        
        if (!$group) {
            WP_CLI::error(sprintf('Group not found: %s', $group_name));
        }
        
        // Clear existing data if requested
        if (isset($assoc_args['clear'])) {
            $wpdb->query("TRUNCATE TABLE {$group_name}");
            WP_CLI::line('Cleared existing data.');
        }
        
        // Get field structure
        $fields = $wpdb->get_results("DESCRIBE {$group_name}");
        
        WP_CLI::line(sprintf('Seeding %d records to %s...', $count, $group_name));
        
        $progress = \WP_CLI\Utils\make_progress_bar('Generating records', $count);
        
        for ($i = 0; $i < $count; $i++) {
            $data = $this->generate_fake_data($fields, $locale);
            $wpdb->insert($group_name, $data);
            $progress->tick();
        }
        
        $progress->finish();
        
        WP_CLI::success(sprintf('Generated %d fake records in %s', $count, $group_name));
    }
    
    /**
     * Generate fake data for all fields
     */
    private function generate_fake_data($fields, $locale = 'en_US') {
        $data = [];
        
        foreach ($fields as $field) {
            if ($field->Field === 'id') continue;
            
            $data[$field->Field] = $this->generate_field_value($field->Field, $field->Type, $locale);
        }
        
        return $data;
    }
    
    /**
     * Generate fake value based on field name and type
     */
    private function generate_field_value($field_name, $field_type, $locale) {
        // Field name patterns
        $patterns = [
            // Names
            'first_name|firstname' => fn() => $this->faker_name($locale, 'first'),
            'last_name|lastname|surname' => fn() => $this->faker_name($locale, 'last'),
            'full_name|name' => fn() => $this->faker_name($locale, 'full'),
            'company|company_name' => fn() => $this->faker_company($locale),
            
            // Contact
            'email|mail' => fn() => $this->faker_email(),
            'phone|telephone|tel|mobile' => fn() => $this->faker_phone($locale),
            'website|url|site' => fn() => $this->faker_url(),
            
            // Address
            'address|street|street_address' => fn() => $this->faker_address($locale),
            'city|town' => fn() => $this->faker_city($locale),
            'state|province|region' => fn() => $this->faker_state($locale),
            'zip|zipcode|postal|postcode' => fn() => $this->faker_zip($locale),
            'country' => fn() => $this->faker_country($locale),
            
            // Content
            'title|headline' => fn() => $this->faker_title($locale),
            'description|desc|summary' => fn() => $this->faker_text(200, $locale),
            'content|body|text' => fn() => $this->faker_text(500, $locale),
            'bio|about' => fn() => $this->faker_text(150, $locale),
            
            // Social Media
            'facebook|fb' => fn() => 'https://facebook.com/' . $this->faker_username(),
            'twitter' => fn() => 'https://twitter.com/' . $this->faker_username(),
            'instagram|insta' => fn() => 'https://instagram.com/' . $this->faker_username(),
            'linkedin' => fn() => 'https://linkedin.com/in/' . $this->faker_username(),
            'youtube' => fn() => 'https://youtube.com/@' . $this->faker_username(),
            'tiktok' => fn() => 'https://tiktok.com/@' . $this->faker_username(),
            
            // E-commerce
            'price|amount|cost' => fn() => rand(10, 1000) . '.99',
            'sku|product_code' => fn() => strtoupper($this->faker_alphanumeric(8)),
            'stock|quantity|qty' => fn() => rand(0, 100),
            'discount|sale' => fn() => rand(5, 50),
            
            // Images
            'image|photo|picture|avatar|thumbnail' => fn() => $this->faker_image(),
            'gallery|images|photos' => fn() => json_encode([$this->faker_image(), $this->faker_image(), $this->faker_image()]),
            
            // Dates
            'date|created|published' => fn() => $this->faker_date(),
            'datetime|timestamp' => fn() => $this->faker_datetime(),
            'time' => fn() => $this->faker_time(),
            'birth_date|birthdate|dob' => fn() => $this->faker_birthdate(),
            
            // Misc
            'color|colour' => fn() => $this->faker_color(),
            'status' => fn() => $this->faker_choice(['active', 'inactive', 'pending', 'draft']),
            'category|cat' => fn() => $this->faker_choice(['Technology', 'Business', 'Health', 'Education', 'Entertainment']),
            'tag|tags' => fn() => implode(', ', $this->faker_words(3)),
            'rating|score' => fn() => rand(1, 5),
            'username|login' => fn() => $this->faker_username(),
            'password|pass' => fn() => $this->faker_password(),
            'slug|permalink' => fn() => $this->faker_slug(),
            'ip|ip_address' => fn() => $this->faker_ip(),
            'uuid|guid' => fn() => $this->faker_uuid(),
        ];
        
        // Match field name pattern
        foreach ($patterns as $pattern => $generator) {
            if (preg_match('/(' . $pattern . ')/i', $field_name)) {
                return $generator();
            }
        }
        
        // Fallback based on MySQL type
        return $this->generate_by_type($field_type, $locale);
    }
    
    /**
     * Generate value based on MySQL type
     */
    private function generate_by_type($field_type, $locale) {
        if (strpos($field_type, 'int') !== false) {
            return rand(1, 1000);
        }
        
        if (strpos($field_type, 'decimal') !== false || strpos($field_type, 'float') !== false) {
            return round(rand(10, 1000) + (rand(0, 99) / 100), 2);
        }
        
        if (strpos($field_type, 'date') !== false && strpos($field_type, 'datetime') === false) {
            return $this->faker_date();
        }
        
        if (strpos($field_type, 'datetime') !== false || strpos($field_type, 'timestamp') !== false) {
            return $this->faker_datetime();
        }
        
        if (strpos($field_type, 'time') !== false) {
            return $this->faker_time();
        }
        
        if (strpos($field_type, 'text') !== false) {
            return $this->faker_text(200, $locale);
        }
        
        // Default: short text
        return $this->faker_text(50, $locale);
    }
    
    // ====== FAKER GENERATORS ======
    
    private function faker_name($locale, $type = 'full') {
        $first_names = [
            'en_US' => ['James', 'John', 'Robert', 'Michael', 'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara'],
            'pl_PL' => ['Jan', 'Andrzej', 'Piotr', 'Krzysztof', 'Anna', 'Maria', 'Katarzyna', 'Małgorzata', 'Agnieszka', 'Barbara'],
        ];
        
        $last_names = [
            'en_US' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'],
            'pl_PL' => ['Nowak', 'Kowalski', 'Wiśniewski', 'Dąbrowski', 'Lewandowski', 'Wójcik', 'Kamiński', 'Kowalczyk', 'Zieliński', 'Szymański'],
        ];
        
        $loc = isset($first_names[$locale]) ? $locale : 'en_US';
        
        if ($type === 'first') {
            return $first_names[$loc][array_rand($first_names[$loc])];
        }
        
        if ($type === 'last') {
            return $last_names[$loc][array_rand($last_names[$loc])];
        }
        
        return $first_names[$loc][array_rand($first_names[$loc])] . ' ' . $last_names[$loc][array_rand($last_names[$loc])];
    }
    
    private function faker_company($locale) {
        $suffixes = ['Inc', 'LLC', 'Corp', 'Ltd', 'Group', 'Solutions', 'Technologies', 'Systems'];
        $names = ['Tech', 'Global', 'Prime', 'Digital', 'Smart', 'Innovative', 'Advanced', 'Creative'];
        
        return $names[array_rand($names)] . ' ' . $suffixes[array_rand($suffixes)];
    }
    
    private function faker_email() {
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com', 'test.com'];
        return strtolower($this->faker_username()) . '@' . $domains[array_rand($domains)];
    }
    
    private function faker_phone($locale) {
        if ($locale === 'pl_PL') {
            return '+48 ' . rand(500, 999) . ' ' . rand(100, 999) . ' ' . rand(100, 999);
        }
        return '+1 (' . rand(200, 999) . ') ' . rand(100, 999) . '-' . rand(1000, 9999);
    }
    
    private function faker_url() {
        $domains = ['example.com', 'test.com', 'demo.com', 'sample.org', 'placeholder.net'];
        return 'https://www.' . $domains[array_rand($domains)];
    }
    
    private function faker_address($locale) {
        if ($locale === 'pl_PL') {
            return 'ul. ' . $this->faker_choice(['Główna', 'Kwiatowa', 'Polna', 'Leśna', 'Słoneczna']) . ' ' . rand(1, 99);
        }
        return rand(100, 9999) . ' ' . $this->faker_choice(['Main', 'Oak', 'Maple', 'Elm', 'Pine']) . ' Street';
    }
    
    private function faker_city($locale) {
        $cities = [
            'en_US' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego'],
            'pl_PL' => ['Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk', 'Szczecin', 'Bydgoszcz', 'Lublin'],
        ];
        
        $loc = isset($cities[$locale]) ? $locale : 'en_US';
        return $cities[$loc][array_rand($cities[$loc])];
    }
    
    private function faker_state($locale) {
        $states = [
            'en_US' => ['California', 'Texas', 'Florida', 'New York', 'Pennsylvania', 'Illinois', 'Ohio', 'Georgia'],
            'pl_PL' => ['Mazowieckie', 'Śląskie', 'Wielkopolskie', 'Małopolskie', 'Dolnośląskie', 'Łódzkie'],
        ];
        
        $loc = isset($states[$locale]) ? $locale : 'en_US';
        return $states[$loc][array_rand($states[$loc])];
    }
    
    private function faker_zip($locale) {
        if ($locale === 'pl_PL') {
            return rand(10, 99) . '-' . rand(100, 999);
        }
        return rand(10000, 99999);
    }
    
    private function faker_country($locale) {
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Poland', 'Spain', 'Italy', 'Japan'];
        return $countries[array_rand($countries)];
    }
    
    private function faker_title($locale) {
        $adjectives = ['Amazing', 'Incredible', 'Awesome', 'Fantastic', 'Great', 'Wonderful', 'Perfect', 'Ultimate'];
        $nouns = ['Product', 'Service', 'Solution', 'Experience', 'Journey', 'Adventure', 'Story', 'Guide'];
        
        return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
    }
    
    private function faker_text($max_length, $locale) {
        $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';
        
        if (strlen($lorem) > $max_length) {
            return substr($lorem, 0, $max_length) . '...';
        }
        
        return $lorem;
    }
    
    private function faker_username() {
        return strtolower($this->faker_alphanumeric(8));
    }
    
    private function faker_password() {
        return $this->faker_alphanumeric(12) . '!@#';
    }
    
    private function faker_slug() {
        $words = ['amazing', 'awesome', 'great', 'super', 'cool', 'best', 'top', 'new'];
        return $words[array_rand($words)] . '-' . $words[array_rand($words)] . '-' . rand(100, 999);
    }
    
    private function faker_alphanumeric($length) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
    }
    
    private function faker_image() {
        $services = [
            'https://picsum.photos/800/600?random=' . rand(1, 1000),
            'https://via.placeholder.com/800x600?text=Sample+Image',
            'https://dummyimage.com/800x600/cccccc/000000&text=Demo+Image',
        ];
        
        return $services[array_rand($services)];
    }
    
    private function faker_date() {
        $timestamp = strtotime('-' . rand(0, 365) . ' days');
        return date('Y-m-d', $timestamp);
    }
    
    private function faker_datetime() {
        $timestamp = strtotime('-' . rand(0, 365) . ' days');
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    private function faker_time() {
        return sprintf('%02d:%02d:%02d', rand(0, 23), rand(0, 59), rand(0, 59));
    }
    
    private function faker_birthdate() {
        $timestamp = strtotime('-' . rand(18, 80) . ' years');
        return date('Y-m-d', $timestamp);
    }
    
    private function faker_color() {
        return sprintf('#%06X', rand(0, 0xFFFFFF));
    }
    
    private function faker_ip() {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }
    
    private function faker_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            rand(0, 0xffff), rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffff), rand(0, 0xffff), rand(0, 0xffff)
        );
    }
    
    private function faker_choice($options) {
        return $options[array_rand($options)];
    }
    
    private function faker_words($count) {
        $words = ['technology', 'business', 'innovation', 'digital', 'modern', 'creative', 'professional', 'global', 'smart', 'advanced'];
        shuffle($words);
        return array_slice($words, 0, $count);
    }
}

YAP_Field_Mocking::get_instance();
