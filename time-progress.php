<?php
/*
Plugin Name: WP Time Progress
Plugin URI: https://ashrafali.net/wp-time-progress
Description: Display beautiful progress wheels showing how much time has passed in the current day, month, quarter, and year.
Version: 1.2.0
Author: Ashraf Ali
Author URI: https://ashrafali.net
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: time-progress
Domain Path: /languages

MIT License

Copyright (c) 2025 Ashraf Ali

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TIME_PROGRESS_VERSION', '1.2.0');
define('TIME_PROGRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TIME_PROGRESS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Time_Progress_Plugin {
    private $options;

    public function __construct() {
        // Initialize hooks
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('time_progress', array($this, 'time_progress_shortcode'));

        // Set default options if not set
        add_option('time_progress_options', array(
            'show_date' => true,
            'show_time' => true,
            'show_year' => true,
            'show_quarter' => true,
            'show_month' => true,
            'show_day' => true,
            'show_wheels' => true,
            'show_text' => true,
            'text_color' => '#333333',
            'date_color' => '#007bff',
            'time_color' => '#28a745',
            'timezone_color' => '#6c757d',
            'days_color' => '#dc3545',
            'percent_color' => '#17a2b8',
            'wheel_bg_color' => '#eeeeee',
            'wheel_progress_color' => '#4a90e2',
            'wheel_text_color' => '#333333',
            'font_size' => '16',
            'font_family' => 'inherit'
        ));

        // Get options
        $this->options = get_option('time_progress_options');
    }

    public function init() {
        load_plugin_textdomain('time-progress', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook === 'settings_page_time-progress-settings') {
            wp_enqueue_style(
                'time-progress-admin', 
                TIME_PROGRESS_PLUGIN_URL . 'css/admin.css', 
                array(), 
                TIME_PROGRESS_VERSION
            );
            
            wp_enqueue_style(
                'time-progress', 
                TIME_PROGRESS_PLUGIN_URL . 'css/style.css', 
                array(), 
                TIME_PROGRESS_VERSION
            );
            
            wp_enqueue_script(
                'time-progress',
                TIME_PROGRESS_PLUGIN_URL . 'js/time-progress.js',
                array('jquery'),
                TIME_PROGRESS_VERSION,
                true
            );

            wp_localize_script(
                'time-progress',
                'timeProgressSettings',
                array(
                    'options' => $this->options
                )
            );
        }
    }

    public function add_plugin_page() {
        add_options_page(
            __('Time Progress', 'time-progress'),
            __('Time Progress', 'time-progress'),
            'manage_options',
            'time-progress-settings',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="time-progress-admin-content">
                <div class="time-progress-settings">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('time_progress_options_group');
                        do_settings_sections('time-progress-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                <div class="time-progress-preview">
                    <h2><?php _e('Preview', 'time-progress'); ?></h2>
                    <div id="time-progress-container" class="preview-container">
                        <!-- Preview will be populated by JavaScript -->
                    </div>
                    <div class="shortcode-info">
                        <p><?php _e('Use this shortcode to display the time progress:', 'time-progress'); ?></p>
                        <code>[time_progress]</code>
                    </div>
                </div>
                <div class="time-progress-author">
                    <p><?php _e('Created by', 'time-progress'); ?> <a href="https://ashrafali.net" target="_blank">Ashraf Ali</a></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'time_progress_options_group',
            'time_progress_options',
            array($this, 'sanitize')
        );

        // Content Settings Section
        add_settings_section(
            'content_section',
            __('Content Settings', 'time-progress'),
            array($this, 'print_content_section_info'),
            'time-progress-settings'
        );

        // Style Settings Section
        add_settings_section(
            'style_section',
            __('Style Settings', 'time-progress'),
            array($this, 'print_style_section_info'),
            'time-progress-settings'
        );

        // Add all settings fields
        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        // Content fields
        $content_fields = array(
            'show_date' => __('Show Current Date', 'time-progress'),
            'show_time' => __('Show Current Time', 'time-progress'),
            'show_year' => __('Show Year Progress', 'time-progress'),
            'show_quarter' => __('Show Quarter Progress', 'time-progress'),
            'show_month' => __('Show Month Progress', 'time-progress'),
            'show_day' => __('Show Day Progress', 'time-progress')
        );

        foreach ($content_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'checkbox_callback'),
                'time-progress-settings',
                'content_section',
                array($field)
            );
        }

        // Display options
        add_settings_field(
            'show_wheels',
            __('Show Progress Wheels', 'time-progress'),
            array($this, 'checkbox_callback'),
            'time-progress-settings',
            'content_section',
            array('show_wheels')
        );

        add_settings_field(
            'show_text',
            __('Show Progress Text', 'time-progress'),
            array($this, 'checkbox_callback'),
            'time-progress-settings',
            'content_section',
            array('show_text')
        );

        // Wheel Style Section
        add_settings_section(
            'wheel_section',
            __('Progress Wheel Settings', 'time-progress'),
            array($this, 'print_wheel_section_info'),
            'time-progress-settings'
        );

        add_settings_field(
            'wheel_bg_color',
            __('Wheel Background Color', 'time-progress'),
            array($this, 'color_text_callback'),
            'time-progress-settings',
            'wheel_section',
            array('wheel_bg_color')
        );

        add_settings_field(
            'wheel_progress_color',
            __('Wheel Progress Color', 'time-progress'),
            array($this, 'color_text_callback'),
            'time-progress-settings',
            'wheel_section',
            array('wheel_progress_color')
        );

        add_settings_field(
            'wheel_text_color',
            __('Wheel Text Color', 'time-progress'),
            array($this, 'color_text_callback'),
            'time-progress-settings',
            'wheel_section',
            array('wheel_text_color')
        );

        // Text Style Section
        add_settings_section(
            'style_section',
            __('Text Style Settings', 'time-progress'),
            array($this, 'print_style_section_info'),
            'time-progress-settings'
        );

        // Text colors
        $text_colors = array(
            'text_color' => __('Main Text Color', 'time-progress'),
            'date_color' => __('Date Color', 'time-progress'),
            'time_color' => __('Time Color', 'time-progress'),
            'timezone_color' => __('Timezone Color', 'time-progress'),
            'days_color' => __('Days Color', 'time-progress'),
            'percent_color' => __('Percentage Color', 'time-progress')
        );

        foreach ($text_colors as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'color_text_callback'),
                'time-progress-settings',
                'style_section',
                array($field)
            );
        }

        add_settings_field(
            'font_size',
            __('Font Size (px)', 'time-progress'),
            array($this, 'font_size_callback'),
            'time-progress-settings',
            'style_section'
        );

        add_settings_field(
            'font_family',
            __('Font Family', 'time-progress'),
            array($this, 'font_family_callback'),
            'time-progress-settings',
            'style_section'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        
        // Checkboxes
        $checkboxes = array('show_date', 'show_time', 'show_year', 'show_quarter', 'show_month', 'show_day', 'show_wheels', 'show_text');
        foreach ($checkboxes as $checkbox) {
            $new_input[$checkbox] = isset($input[$checkbox]) ? true : false;
        }
        
        // Colors - allow hex colors and CSS variables
        $colors = array(
            'text_color', 'date_color', 'time_color', 'timezone_color', 
            'days_color', 'percent_color', 'wheel_bg_color', 
            'wheel_progress_color', 'wheel_text_color'
        );
        foreach ($colors as $color) {
            if (isset($input[$color])) {
                // Allow hex colors and CSS variables
                if (preg_match('/^(#[a-f0-9]{6}|#[a-f0-9]{3}|var\(--[a-zA-Z0-9-_]+\))$/i', $input[$color])) {
                    $new_input[$color] = $input[$color];
                } else {
                    $new_input[$color] = $this->options[$color]; // Keep existing value if invalid
                }
            }
        }
        
        // Font size
        $new_input['font_size'] = absint($input['font_size']);
        
        // Font family
        if (isset($input['font_family'])) {
            $new_input['font_family'] = sanitize_text_field($input['font_family']);
        }
        
        return $new_input;
    }

    public function print_content_section_info() {
        _e('Choose which information to display:', 'time-progress');
    }

    public function print_style_section_info() {
        _e('Customize the appearance:', 'time-progress');
    }

    public function checkbox_callback($args) {
        $field = $args[0];
        printf(
            '<input type="checkbox" id="%s" name="time_progress_options[%s]" %s />',
            esc_attr($field),
            esc_attr($field),
            checked($this->options[$field], true, false)
        );
    }

    public function color_text_callback($args) {
        $field = $args[0];
        printf(
            '<input type="color" id="%1$s_color" name="time_progress_options[%1$s]" value="%2$s" />
             <input type="text" id="%1$s_text" name="time_progress_options[%1$s]" value="%2$s" 
                    placeholder="#hex or var(--name)" style="width: 150px; margin-left: 10px;" />
             <script>
                document.getElementById("%1$s_color").addEventListener("input", function(e) {
                    document.getElementById("%1$s_text").value = e.target.value;
                });
                document.getElementById("%1$s_text").addEventListener("input", function(e) {
                    if (e.target.value.match(/^#[a-f0-9]{6}$/i)) {
                        document.getElementById("%1$s_color").value = e.target.value;
                    }
                });
             </script>',
            esc_attr($field),
            esc_attr($this->options[$field])
        );
    }

    public function font_size_callback() {
        printf(
            '<input type="number" id="font_size" name="time_progress_options[font_size]" value="%s" min="10" max="32" /> px',
            esc_attr($this->options['font_size'])
        );
    }

    public function font_family_callback() {
        $fonts = array(
            'inherit' => __('Theme Default', 'time-progress'),
            'Arial, sans-serif' => 'Arial',
            'Helvetica, sans-serif' => 'Helvetica',
            'Georgia, serif' => 'Georgia',
            'Tahoma, sans-serif' => 'Tahoma',
            'Verdana, sans-serif' => 'Verdana',
            'Times New Roman, serif' => 'Times New Roman'
        );
        
        echo '<select id="font_family" name="time_progress_options[font_family]">';
        foreach ($fonts as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($this->options['font_family'], $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'time-progress-script',
            TIME_PROGRESS_PLUGIN_URL . 'js/time-progress.js',
            array(),
            TIME_PROGRESS_VERSION,
            true
        );

        // Add debug information
        $debug_info = array(
            'options' => $this->options,
            'debug' => true,
            'version' => TIME_PROGRESS_VERSION
        );

        wp_localize_script(
            'time-progress-script',
            'timeProgressSettings',
            $debug_info
        );

        wp_enqueue_style(
            'time-progress-style',
            TIME_PROGRESS_PLUGIN_URL . 'css/style.css',
            array(),
            TIME_PROGRESS_VERSION
        );
    }

    public function time_progress_shortcode() {
        return '<div id="time-progress-container" class="time-progress"></div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        TimeProgress();
                    });
                </script>';
    }

    public function print_wheel_section_info() {
        _e('Customize the appearance of progress wheels:', 'time-progress');
    }
}

// Initialize the plugin
$time_progress_plugin = new Time_Progress_Plugin();