<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PDF_Bundler_Library_Setup')) {
class PDF_Bundler_Library_Setup {
        private $plugin_path;
        private $wpdb;
        private $tables;
        private $charset_collate;

        public function __construct() {
            $this->plugin_path = plugin_dir_path(dirname(__FILE__));
            global $wpdb;
            $this->wpdb = $wpdb;
            $this->charset_collate = $wpdb->get_charset_collate();
            
            // Define all tables and their schemas
            $this->tables = array(
                'merge_queue' => array(
                    'name' => $wpdb->prefix . 'pdf_bundler_merge_queue',
                    'schema' => array(
                        'id' => 'mediumint(9) NOT NULL AUTO_INCREMENT',
                        'user_id' => 'bigint(20) NOT NULL',
                        'customer_bio_pdf' => 'varchar(255) DEFAULT NULL',
                        'custom_pdf_path' => 'varchar(255) DEFAULT NULL',
                        'city' => 'varchar(100) DEFAULT NULL',
                        'flipbook_url' => 'varchar(255) DEFAULT NULL',
                        'status' => "varchar(50) NOT NULL DEFAULT 'pending'",
                        'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
                        'merged_at' => 'datetime DEFAULT NULL',
                        'PRIMARY KEY' => '(id)'
                    )
                ),
                'cities' => array(
                    'name' => $wpdb->prefix . 'pdf_bundler_cities',
                    'schema' => array(
                        'id' => 'mediumint(9) NOT NULL AUTO_INCREMENT',
                        'city' => 'varchar(100) NOT NULL',
                        'pdf_path' => 'varchar(255) NOT NULL',
                        'status' => "varchar(50) NOT NULL DEFAULT 'active'",
                        'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
                        'updated_at' => 'datetime DEFAULT NULL',
                        'PRIMARY KEY' => '(id)',
                        'UNIQUE KEY city' => '(city)'
                    )
                )
            );
        }

        public function init() {
            return $this->verify_libraries();
        }

        public function setup_libraries() {
            return $this->verify_libraries();
        }

        public function verify_libraries() {
            // Define the required library paths
            $tcpdf_path = $this->plugin_path . 'lib/tcpdf/';
            $fpdi_path = $this->plugin_path . 'lib/fpdi/src/';
            
            // Verify libraries are present
            if (!file_exists($tcpdf_path . 'tcpdf.php')) {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>';
                    echo 'PDF Bundler: TCPDF library is missing. Please reinstall the plugin.';
                    echo '</p></div>';
                });
            return false;
            }
            
            if (!file_exists($fpdi_path . 'autoload.php')) {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>';
                    echo 'PDF Bundler: FPDI library is missing. Please reinstall the plugin.';
                    echo '</p></div>';
                });
                return false;
            }

            return true;
        }

        public function check_pdf_compatibility($pdf_path) {
            try {
                // Basic PDF validation
                $f = fopen($pdf_path, 'rb');
                if (!$f) {
                    return false;
                }
                
                $header = fread($f, 4);
                fclose($f);
                
                return ($header === '%PDF');
            } catch (Exception $e) {
                return false;
            }
        }

        public function push_to_flipbook($pdf_path, $user_id) {
            try {
                // Log the input parameters
                error_log('Pushing to flipbook - PDF Path: ' . $pdf_path . ', User ID: ' . $user_id);
                
                // Your existing flipbook creation code here
                // For example, if you're using a third-party service:
                $flipbook_url = $this->create_flipbook($pdf_path);
                
                if ($flipbook_url) {
                    // Update both user meta and database
                    update_user_meta($user_id, '_flipbook_url', $flipbook_url);
                    
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->prefix . 'pdf_bundler_merge_queue',
                        array('flipbook_url' => $flipbook_url),
                        array('user_id' => $user_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    error_log('Flipbook created successfully: ' . $flipbook_url);
                    return $flipbook_url;
                }
                
                error_log('Failed to create flipbook');
                return false;
                
            } catch (Exception $e) {
                error_log('Error in push_to_flipbook: ' . $e->getMessage());
                return false;
            }
        }

        private function create_flipbook($pdf_path) {
            // This is a placeholder - implement your actual flipbook creation logic
            // For example, you might upload to a service like iPaper or FlipHTML5
            
            // For testing, return a dummy URL
            return site_url('/wp-content/uploads/pdf-bundler/') . basename($pdf_path);
        }

        public function upgrade_database($from_version) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Create or update tables
            foreach ($this->tables as $table) {
                $this->create_or_update_table($table['name'], $table['schema']);
            }
            
            // Version-specific upgrades
            if (version_compare($from_version, '1.0.0', '<')) {
                $this->upgrade_to_1_0_0();
            }
            
            // Create directories
            $this->create_directories();
        }

        private function create_or_update_table($table_name, $schema) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (\n";
            foreach ($schema as $column => $definition) {
                if (!preg_match('/^(PRIMARY|UNIQUE)/', $column)) {
                    $sql .= "$column $definition,\n";
                }
            }
            
            // Add keys
            foreach ($schema as $column => $definition) {
                if (preg_match('/^(PRIMARY|UNIQUE)/', $column)) {
                    $sql .= "$column $definition,\n";
                }
            }
            
            // Remove trailing comma and close parenthesis
            $sql = rtrim($sql, ",\n") . "\n) " . $this->charset_collate . ";";
            
            dbDelta($sql);
        }

        private function upgrade_to_1_0_0() {
            // Add any specific upgrade tasks for version 1.0.0
            foreach ($this->tables as $table) {
                $this->verify_columns($table['name'], $table['schema']);
            }
        }

        private function verify_columns($table_name, $schema) {
            $existing_columns = $this->wpdb->get_col("SHOW COLUMNS FROM $table_name");
            
            foreach ($schema as $column => $definition) {
                if (!preg_match('/^(PRIMARY|UNIQUE)/', $column) && !in_array($column, $existing_columns)) {
                    $sql = "ALTER TABLE $table_name ADD COLUMN $column $definition";
                    $this->wpdb->query($sql);
                }
            }
        }

        private function create_directories() {
            $upload_dir = wp_upload_dir();
            $pdf_dirs = array(
                '/pdf-bundler',
                '/pdf-bundler/customer-pdfs',
                '/pdf-bundler/city-pdfs',
                '/pdf-bundler/merged-pdfs'
            );

            foreach ($pdf_dirs as $dir) {
                $full_path = $upload_dir['basedir'] . $dir;
                if (!file_exists($full_path)) {
                    wp_mkdir_p($full_path);
                }
            }
        }

        public function get_table_name($table) {
            return isset($this->tables[$table]) ? $this->tables[$table]['name'] : '';
        }

        public function create_tables() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            // Drop existing tables to ensure clean installation
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pdf_bundler_cities");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pdf_bundler_merge_queue");

            // Create merge queue table with new columns
            $sql_merge_queue = "CREATE TABLE {$wpdb->prefix}pdf_bundler_merge_queue (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                customer_bio_pdf varchar(255) DEFAULT NULL,
                city varchar(255) DEFAULT NULL,
                status varchar(50) DEFAULT 'pending',
                needs_update tinyint(1) DEFAULT 0,
                is_merged tinyint(1) DEFAULT 0,
                merged_pdf_path varchar(255) DEFAULT NULL,
                flipbook_url varchar(255) DEFAULT NULL,
                last_error text() DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                merged_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY city (city),
                KEY status (status),
                KEY needs_update (needs_update),
                KEY is_merged (is_merged)
            ) $charset_collate;";

            // Create cities table with merge flag
            $sql_cities = "CREATE TABLE {$wpdb->prefix}pdf_bundler_cities (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                city varchar(255) NOT NULL,
                pdf_path varchar(255) NOT NULL,
                status varchar(50) DEFAULT 'active',
                last_merged_at datetime DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY city (city),
                KEY status (status)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Execute the SQL directly
            $wpdb->query($sql_merge_queue);
            $wpdb->query($sql_cities);

            // Verify tables were created
            $tables_exist = true;
            $tables = array(
                $wpdb->prefix . 'pdf_bundler_merge_queue',
                $wpdb->prefix . 'pdf_bundler_cities'
            );

            foreach ($tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                    $tables_exist = false;
                    error_log("Failed to create table: $table");
                }
            }

            if (!$tables_exist) {
                throw new Exception('Failed to create required database tables');
            }
        }
    }
} 