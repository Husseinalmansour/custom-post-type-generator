<?php
/*
Plugin Name: Custom Post Type Generator
Plugin URI: https://hussein.pro
Description: A plugin to easily create and manage custom post types.
Version: 1.0.0
Author: Hussein Al-mansour
Author URI: https://hussein.pro
License: GPL2 or later
Text Domain: cptg
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Define the Singleton class
class CPTG_Singleton {
    
    private static $instance = null;

    // Constructor: Hooks for initializing plugin functionalities
    private function __construct() {
        add_action('admin_menu', [$this, 'cptg_add_admin_menu']);
        add_action('admin_init', [$this, 'cptg_settings_init']);
        add_action('init', [$this, 'cptg_register_custom_post_type']);
        add_action('plugins_loaded', [$this, 'cptg_load_textdomain']);
    }

    // Singleton pattern: Return single instance of the class
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Load the plugin's text domain for translations
    public function cptg_load_textdomain() {
        load_plugin_textdomain('cptg', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // Add admin menu for plugin settings page
    public function cptg_add_admin_menu() {
        add_menu_page(
            __('CPT Generator', 'cptg'), 
            __('CPT Generator', 'cptg'), 
            'manage_options', 
            'cpt_generator', 
            [$this, 'cptg_options_page']
        );
    }

    // Initialize plugin settings and sections
    public function cptg_settings_init() {
        register_setting('cptg_plugin', 'cptg_settings', ['sanitize_callback' => [$this, 'cptg_sanitize_settings']]);

        // Section for creating a new custom post type
        add_settings_section(
            'cptg_plugin_section',
            __('Create a New Custom Post Type', 'cptg'),
            [$this, 'cptg_settings_section_callback'],
            'cptg_plugin'
        );

        // Fields for post type, singular name, and plural name
        add_settings_field('cptg_post_type', __('Post Type', 'cptg'), [$this, 'cptg_post_type_render'], 'cptg_plugin', 'cptg_plugin_section');
        add_settings_field('cptg_singular_name', __('Singular Name', 'cptg'), [$this, 'cptg_singular_name_render'], 'cptg_plugin', 'cptg_plugin_section');
        add_settings_field('cptg_plural_name', __('Plural Name', 'cptg'), [$this, 'cptg_plural_name_render'], 'cptg_plugin', 'cptg_plugin_section');
    }

    // Sanitize settings before saving
    public function cptg_sanitize_settings($input) {
        return [
            'cptg_post_type' => sanitize_key($input['cptg_post_type']),
            'cptg_singular_name' => sanitize_text_field($input['cptg_singular_name']),
            'cptg_plural_name' => sanitize_text_field($input['cptg_plural_name'])
        ];
    }

    // Render input fields for settings
    public function cptg_post_type_render() {
        $options = get_option('cptg_settings');
        ?>
        <input type='text' name='cptg_settings[cptg_post_type]' value='<?php echo esc_attr($options['cptg_post_type'] ?? ''); ?>'>
        <?php
    }

    public function cptg_singular_name_render() {
        $options = get_option('cptg_settings');
        ?>
        <input type='text' name='cptg_settings[cptg_singular_name]' value='<?php echo esc_attr($options['cptg_singular_name'] ?? ''); ?>'>
        <?php
    }

    public function cptg_plural_name_render() {
        $options = get_option('cptg_settings');
        ?>
        <input type='text' name='cptg_settings[cptg_plural_name]' value='<?php echo esc_attr($options['cptg_plural_name'] ?? ''); ?>'>
        <?php
    }

    // Section description
    public function cptg_settings_section_callback() {
        echo __('Fill in the details to create a new custom post type.', 'cptg');
    }

    // Display the options page for the plugin
    public function cptg_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2><?php _e('Custom Post Type Generator', 'cptg'); ?></h2>
            <?php
            settings_fields('cptg_plugin');
            do_settings_sections('cptg_plugin');
            submit_button(__('Save Settings', 'cptg'));
            ?>
        </form>
        <h2><?php _e('Registered Custom Post Types', 'cptg'); ?></h2>
        <?php $this->cptg_display_registered_post_types(); ?>
        <?php
    }

    // Register custom post type based on settings
    public function cptg_register_custom_post_type() {
        $options = get_option('cptg_settings');
        if (!empty($options['cptg_post_type']) && !empty($options['cptg_singular_name']) && !empty($options['cptg_plural_name'])) {
            $labels = [
                'name'               => $options['cptg_plural_name'],
                'singular_name'      => $options['cptg_singular_name'],
                'menu_name'          => $options['cptg_plural_name'],
                'name_admin_bar'     => $options['cptg_singular_name'],
                'add_new'            => __('Add New', 'cptg'),
                'all_items'          => __('All ' . $options['cptg_plural_name'], 'cptg')
            ];
            $args = [
                'labels'             => $labels,
                'public'             => true,
                'has_archive'        => true,
                'rewrite'            => ['slug' => $options['cptg_post_type']],
                'supports'           => ['title', 'editor', 'thumbnail']
            ];
            register_post_type($options['cptg_post_type'], $args);
        }
    }

    // Display registered custom post types
    private function cptg_display_registered_post_types() {
        $post_types = get_post_types(['_builtin' => false], 'objects');
        if (!empty($post_types)) {
            foreach ($post_types as $pt) {
                if (isset($pt->_generated_by) && $pt->_generated_by === 'cptg_plugin') {
                    echo '<p>' . esc_html($pt->name) . '</p>';
                }
            }
        } else {
            echo '<p>' . __('No custom post types found.', 'cptg') . '</p>';
        }
    }
}

// Initialize the plugin instance
CPTG_Singleton::get_instance();
