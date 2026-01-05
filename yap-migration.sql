-- YAP Migration Script
-- Creates wp_yap_field_metadata table and groups all YAP tables

-- Create field metadata table
CREATE TABLE IF NOT EXISTS wp_yap_field_metadata (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    group_name varchar(255) NOT NULL,
    field_name varchar(255) NOT NULL,
    field_metadata longtext NOT NULL,
    field_order int DEFAULT 0,
    PRIMARY KEY  (id),
    KEY group_name (group_name),
    UNIQUE KEY unique_field (group_name, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- Add table comments for grouping in phpMyAdmin
-- Core System Tables
ALTER TABLE wp_yap_location_rules COMMENT = 'YAP Core: Location Rules - Assigns field groups to posts/pages';
ALTER TABLE wp_yap_options COMMENT = 'YAP Core: Options - Stores options page values';
ALTER TABLE wp_yap_field_metadata COMMENT = 'YAP Core: Field Metadata - Visual Builder field definitions';
ALTER TABLE wp_yap_sync_log COMMENT = 'YAP Core: Sync Log - Environment synchronization history';

-- Advanced Features Tables
ALTER TABLE wp_yap_data_history COMMENT = 'YAP Advanced: Data History - Git-like version control for field data';
ALTER TABLE wp_yap_query_fields COMMENT = 'YAP Advanced: Query Fields - SQL-powered dynamic fields';
ALTER TABLE wp_yap_automations COMMENT = 'YAP Advanced: Automations - Airtable-style automation rules';
ALTER TABLE wp_yap_automation_log COMMENT = 'YAP Advanced: Automation Log - Execution history for automations';

-- Note: Run this SQL in phpMyAdmin
-- After running this script:
-- 1. Go to WordPress Admin → YAP → Database Tables
-- 2. Click "Regroup All Tables" to add comments to field group tables
-- 3. Refresh phpMyAdmin to see grouped tables

SELECT 'Migration completed successfully!' as status;
