<?php
/*
Plugin Name: WP Time Progress
Plugin URI: https://ashrafali.net
Description: A beautiful and customizable WordPress plugin that displays real-time progress information for day, month, quarter, and year with live-updating circular progress indicators.
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
define('WP_TIME_PROGRESS_VERSION', '1.2.0');
define('WP_TIME_PROGRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_TIME_PROGRESS_PLUGIN_URL', plugin_dir_url(__FILE__));

class WP_Time_Progress_Plugin {
    private $options;

    public function __construct() {
        // Initialize hooks
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('wp-time-progress', array($this, 'wp_time_progress_shortcode'));

        // Set default options if not set
        add_option('wp_time_progress_options', array(
            'show_date' => true,
            'show_time' => true,
            'show_year' => true,
            'show_quarter' => true,
            'show_month' => true,
            'show_day' => true,
            'show_wheels' => true,
            'show_text' => true,
            'text_color' => '#333333',
            'date_color' => 'var(--wp-time-progress-date-color, #0073aa)',
            'time_color' => 'var(--wp-time-progress-time-color, #0073aa)',
            'number_color' => 'var(--wp-time-progress-number-color, #0073aa)',
            'wheel_bg_color' => 'var(--wp-time-progress-wheel-bg-color, #f0f0f0)',
            'wheel_progress_color' => 'var(--wp-time-progress-wheel-color, #0073aa)',
            'font_size' => '16',
            'font_family' => 'inherit',
            'date_font_family' => 'inherit',
            'time_font_family' => 'inherit',
            'number_font_family' => 'inherit',
            'custom_css' => ''
        ));

        // Get options
        $this->options = get_option('wp_time_progress_options');
    }

    public function init() {
        load_plugin_textdomain('wp-time-progress', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook === 'settings_page_wp-time-progress-settings') {
            // Enqueue WordPress color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Enqueue our admin styles and scripts
            wp_enqueue_style(
                'wp-time-progress-admin',
                WP_TIME_PROGRESS_PLUGIN_URL . 'css/wp-time-progress-admin.css',
                array(),
                WP_TIME_PROGRESS_VERSION
            );
            
            wp_enqueue_style(
                'wp-time-progress',
                WP_TIME_PROGRESS_PLUGIN_URL . 'css/wp-time-progress.css',
                array(),
                WP_TIME_PROGRESS_VERSION
            );
            
            wp_enqueue_script(
                'wp-time-progress',
                WP_TIME_PROGRESS_PLUGIN_URL . 'js/wp-time-progress.js',
                array('jquery'),
                WP_TIME_PROGRESS_VERSION,
                true
            );

            wp_localize_script(
                'wp-time-progress',
                'wpTimeProgressSettings',
                array(
                    'options' => $this->options
                )
            );
        }
    }

    public function add_plugin_page() {
        add_options_page(
            __('WP Time Progress', 'wp-time-progress'),
            __('WP Time Progress', 'wp-time-progress'),
            'manage_options',
            'wp-time-progress-settings',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('wp_time_progress_options');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="plugin-description">
                <p><?php _e('A beautiful and customizable WordPress plugin that displays real-time progress information for day, month, quarter, and year with live-updating circular progress indicators.', 'wp-time-progress'); ?></p>
                <p class="author-credit"><?php _e('Created by ', 'wp-time-progress'); ?><a href="https://ashrafali.net" target="_blank">Ashraf Ali</a></p>
            </div>
            <div class="wp-time-progress-preview">
                <h2><?php _e('Live Preview', 'wp-time-progress'); ?></h2>
                <div class="preview-content">
                    <div id="wp-time-progress-container"></div>
                </div>
                <div class="shortcode-info">
                    <p><?php _e('Use this shortcode to display the time progress:', 'wp-time-progress'); ?></p>
                    <code>[wp-time-progress]</code>
                </div>
            </div>
            <div class="wp-time-progress-admin-content">
                <div class="wp-time-progress-settings">
                    <form method="post" action="options.php" id="wp-time-progress-form">
                    <?php
                        settings_fields('wp_time_progress_options_group');
                        do_settings_sections('wp-time-progress-settings');
                        submit_button();
                    ?>
                    </form>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Function to update preview
            function updatePreview() {
                var formData = $('#wp-time-progress-form').serializeArray();
                var settings = {};
                
                // Process form data
                formData.forEach(function(item) {
                    var name = item.name.match(/\[([^\]]+)\]/);
                    if (name) {
                        if (item.name.includes('[show_')) {
                            settings[name[1]] = item.value === '1';
                        } else {
                            settings[name[1]] = item.value;
                        }
                    }
                });

                // Add missing checkbox values (unchecked boxes aren't included in formData)
                ['show_date', 'show_time', 'show_year', 'show_quarter', 'show_month', 'show_day', 'show_wheels', 'show_text'].forEach(function(key) {
                    if (!(key in settings)) {
                        settings[key] = false;
                    }
                });

                // Update preview with current settings
                window.wpTimeProgressSettings = {
                    options: settings
                };
                
                if (typeof TimeProgress === 'function') {
                    TimeProgress();
                }
            }

            // Initialize color pickers
            $('.color-input-group').each(function() {
                var $textInput = $(this).find('input[type="text"]');
                var $colorPicker = $(this).find('input[type="color"]');
                
                // Update text input when color picker changes
                $colorPicker.on('input change', function() {
                    $textInput.val($(this).val());
                    updatePreview();
                });
                
                // Update color picker when text input changes (if it's a valid hex color)
                $textInput.on('input change', function() {
                    var value = $(this).val();
                    if (value.match(/^#[0-9A-Fa-f]{6}$/)) {
                        $colorPicker.val(value);
                    }
                    updatePreview();
                });
            });

            // Update preview on any input change
            $('#wp-time-progress-form input, #wp-time-progress-form select').on('change input', function() {
                updatePreview();
            });

            // Initial preview
            setTimeout(updatePreview, 100);
            
            // Update preview periodically
            setInterval(updatePreview, 1000);
        });
        </script>
        <?php
    }

    public function page_init() {
        register_setting(
            'wp_time_progress_options_group',
            'wp_time_progress_options',
            array($this, 'sanitize')
        );

        // Content Settings Section
        add_settings_section(
            'content_section',
            __('Content Settings', 'wp-time-progress'),
            array($this, 'print_content_section_info'),
            'wp-time-progress-settings'
        );

        // Style Settings Section
        add_settings_section(
            'style_section',
            __('Style Settings', 'wp-time-progress'),
            array($this, 'print_style_section_info'),
            'wp-time-progress-settings'
        );

        // Advanced Style Settings Section
        add_settings_section(
            'advanced_style_section',
            __('Advanced Style Settings', 'wp-time-progress'),
            array($this, 'print_advanced_style_section_info'),
            'wp-time-progress-settings'
        );

        // Add all settings fields
        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        // Content fields
        $content_fields = array(
            'show_date' => array(
                'label' => __('Show Date', 'wp-time-progress'),
                'description' => __('Display today\'s date', 'wp-time-progress'),
                'example' => __('Example: January 18, 2025', 'wp-time-progress')
            ),
            'show_time' => array(
                'label' => __('Show Time', 'wp-time-progress'),
                'description' => __('Display current time', 'wp-time-progress'),
                'example' => __('Example: 10:15 AM in New York', 'wp-time-progress')
            ),
            'show_year' => array(
                'label' => __('Show Year Progress', 'wp-time-progress'),
                'description' => __('Display year progress wheel and text', 'wp-time-progress')
            ),
            'show_quarter' => array(
                'label' => __('Show Quarter Progress', 'wp-time-progress'),
                'description' => __('Display quarter progress wheel and text', 'wp-time-progress')
            ),
            'show_month' => array(
                'label' => __('Show Month Progress', 'wp-time-progress'),
                'description' => __('Display month progress wheel and text', 'wp-time-progress')
            ),
            'show_day' => array(
                'label' => __('Show Day Progress', 'wp-time-progress'),
                'description' => __('Display day progress wheel and text', 'wp-time-progress')
            ),
            'show_wheels' => array(
                'label' => __('Show Progress Wheels', 'wp-time-progress'),
                'description' => __('Display circular progress indicators', 'wp-time-progress')
            ),
            'show_text' => array(
                'label' => __('Show Text', 'wp-time-progress'),
                'description' => __('Display progress text descriptions', 'wp-time-progress')
            )
        );

        foreach ($content_fields as $field => $data) {
            add_settings_field(
                $field,
                $data['label'],
                array($this, 'checkbox_callback'),
                'wp-time-progress-settings',
                'content_section',
                array(
                    $field,
                    'description' => $data['description'],
                    'example' => isset($data['example']) ? $data['example'] : ''
                )
            );
        }

        // Style fields
        $style_fields = array(
            'text_color' => array(
                'label' => __('Text Color', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Main text color. Supports CSS variables (e.g., var(--primary))', 'wp-time-progress'),
                'example' => '<code>#333333</code> or <code>var(--primary)</code>'
            ),
            'font_size' => array(
                'label' => __('Font Size', 'wp-time-progress'),
                'type' => 'number',
                'min' => '10',
                'max' => '32',
                'description' => __('Base font size in pixels', 'wp-time-progress')
            ),
            'font_family' => array(
                'label' => __('Font Family', 'wp-time-progress'),
                'type' => 'text',
                'description' => __('Font family for all text. Use CSS font-family value or variable', 'wp-time-progress'),
                'example' => '<code>Arial, sans-serif</code> or <code>var(--system-font)</code>'
            )
        );

        foreach ($style_fields as $field => $data) {
            add_settings_field(
                $field,
                $data['label'],
                array($this, 'text_input_callback'),
                'wp-time-progress-settings',
                'style_section',
                array_merge(array('field' => $field), $data)
            );
        }

        // Advanced style fields
        $advanced_fields = array(
            'date_color' => array(
                'label' => __('Date Color', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Color for date text', 'wp-time-progress')
            ),
            'time_color' => array(
                'label' => __('Time Color', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Color for time text', 'wp-time-progress')
            ),
            'number_color' => array(
                'label' => __('Number Color', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Color for percentage numbers', 'wp-time-progress')
            ),
            'wheel_bg_color' => array(
                'label' => __('Wheel Background', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Background color of progress wheels', 'wp-time-progress')
            ),
            'wheel_progress_color' => array(
                'label' => __('Wheel Progress', 'wp-time-progress'),
                'type' => 'color',
                'description' => __('Progress color of wheels', 'wp-time-progress')
            )
        );

        foreach ($advanced_fields as $field => $data) {
            add_settings_field(
                $field,
                $data['label'],
                array($this, 'text_input_callback'),
                'wp-time-progress-settings',
                'advanced_style_section',
                array_merge(array('field' => $field), $data)
            );
        }

        // Custom CSS field
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'wp-time-progress'),
            array($this, 'textarea_callback'),
            'wp-time-progress-settings',
            'advanced_style_section',
            array(
                'custom_css',
                'description' => __('Add your custom CSS rules here', 'wp-time-progress'),
                'example' => '.wp-time-progress { /* your styles */ }'
            )
        );
    }

    public function sanitize($input) {
        $new_input = array();
        
        // Checkboxes
        $checkboxes = array('show_date', 'show_time', 'show_year', 'show_quarter', 'show_month', 'show_day', 'show_wheels', 'show_text');
        foreach ($checkboxes as $checkbox) {
            $new_input[$checkbox] = isset($input[$checkbox]) ? true : false;
        }
        
        // Text color
        $new_input['text_color'] = sanitize_text_field($input['text_color']);
        
        // Font size
        $new_input['font_size'] = absint($input['font_size']);
        
        // Font family
        $new_input['font_family'] = sanitize_text_field($input['font_family']);
        
        // Advanced style fields
        $advanced_color_fields = array('date_color', 'time_color', 'number_color', 'wheel_bg_color', 'wheel_progress_color');
        foreach ($advanced_color_fields as $field) {
            $new_input[$field] = sanitize_text_field($input[$field]);
        }

        $advanced_font_fields = array('date_font_family', 'time_font_family', 'number_font_family');
        foreach ($advanced_font_fields as $field) {
            $new_input[$field] = sanitize_text_field($input[$field]);
        }

        $new_input['custom_css'] = wp_kses_post($input['custom_css']);
        
        return $new_input;
    }

    public function print_content_section_info() {
        _e('Choose which information to display:', 'wp-time-progress');
    }

    public function print_style_section_info() {
        _e('Customize the appearance:', 'wp-time-progress');
    }

    public function print_advanced_style_section_info() {
        _e('Customize individual element styles. You can use CSS variables (e.g., var(--primary)) or standard CSS values.', 'wp-time-progress');
    }

    public function checkbox_callback($args) {
        $field = $args[0];
        printf(
            '<input type="checkbox" id="%s" name="wp_time_progress_options[%s]" %s />',
            esc_attr($field),
            esc_attr($field),
            checked($this->options[$field], true, false)
        );
    }

    public function text_input_callback($args) {
        $field = $args['field'];
        $type = $args['type'];
        $description = isset($args['description']) ? $args['description'] : '';
        $min = isset($args['min']) ? " min='{$args['min']}'" : '';
        $max = isset($args['max']) ? " max='{$args['max']}'" : '';

        if ($type === 'color') {
            echo '<div class="color-input-group">';
            printf(
                '<input type="text" id="%s_text" name="wp_time_progress_options[%s]" value="%s" class="color-text-input" placeholder="e.g., #000000 or var(--primary)" />',
                esc_attr($field),
                esc_attr($field),
                esc_attr($this->options[$field])
            );
            printf(
                '<input type="color" id="%s_picker" value="%s" class="wp-color-picker" />',
                esc_attr($field),
                esc_attr($this->options[$field])
            );
            echo '</div>';
        } else {
            printf(
                '<input type="%s" id="%s" name="wp_time_progress_options[%s]" value="%s"%s%s class="regular-text" />',
                esc_attr($type),
                esc_attr($field),
                esc_attr($field),
                esc_attr($this->options[$field]),
                $min,
                $max
            );
        }

        if ($description) {
            printf('<p class="description">%s</p>', wp_kses_post($description));
        }

        // Add example if it exists
        if (isset($args['example'])) {
            printf('<div class="setting-example">%s</div>', wp_kses_post($args['example']));
        }
    }

    public function textarea_callback($args) {
        $field = $args[0];
        printf(
            '<textarea id="%s" name="wp_time_progress_options[%s]" rows="10" cols="50" class="large-text code">%s</textarea>',
            esc_attr($field),
            esc_attr($field),
            esc_textarea($this->options[$field])
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'wp-time-progress-script',
            WP_TIME_PROGRESS_PLUGIN_URL . 'js/wp-time-progress.js',
            array(),
            WP_TIME_PROGRESS_VERSION,
            true
        );

        wp_localize_script(
            'wp-time-progress-script',
            'wpTimeProgressSettings',
            array('options' => $this->options)
        );

        wp_enqueue_style(
            'wp-time-progress-style',
            WP_TIME_PROGRESS_PLUGIN_URL . 'css/wp-time-progress.css',
            array(),
            WP_TIME_PROGRESS_VERSION
        );
    }

    public function wp_time_progress_shortcode() {
        return '<div id="wp-time-progress-container" class="wp-time-progress"></div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        TimeProgress();
                    });
                </script>';
    }
}

// Initialize the plugin
$wp_time_progress_plugin = new WP_Time_Progress_Plugin();