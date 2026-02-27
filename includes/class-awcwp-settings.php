<?php

if (!defined('ABSPATH')) {
    exit;
}

class AWCWP_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu() {
        add_options_page(
            'WhatsApp Chat',
            'WhatsApp Chat',
            'manage_options',
            'awcwp-settings',
            array($this, 'render_page')
        );
    }

    public function register_settings() {
        register_setting('awcwp_settings_group', 'awcwp_settings', array($this, 'sanitize'));

        add_settings_section(
            'awcwp_main_section',
            'Chat Button Settings',
            function () {
                echo '<p>Configure your floating WhatsApp chat button.</p>';
            },
            'awcwp-settings'
        );

        add_settings_field('enabled', 'Enable Button', array($this, 'field_enabled'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('phone', 'WhatsApp Number', array($this, 'field_phone'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('text', 'Button Text', array($this, 'field_text'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('message', 'Prefilled Message', array($this, 'field_message'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('position', 'Button Position', array($this, 'field_position'), 'awcwp-settings', 'awcwp_main_section');
    }

    public function sanitize($input) {
        $output = array();

        $output['enabled'] = isset($input['enabled']) ? 1 : 0;
        $output['phone'] = isset($input['phone']) ? preg_replace('/[^0-9]/', '', $input['phone']) : '';
        $output['text'] = isset($input['text']) ? sanitize_text_field($input['text']) : 'Chat on WhatsApp';
        $output['message'] = isset($input['message']) ? sanitize_textarea_field($input['message']) : 'Hello, I need help.';

        $position = isset($input['position']) ? sanitize_text_field($input['position']) : 'right';
        $output['position'] = in_array($position, array('left', 'right'), true) ? $position : 'right';

        return $output;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Advanced WhatsApp Chat</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('awcwp_settings_group');
                do_settings_sections('awcwp-settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    private function get_settings() {
        return get_option('awcwp_settings', array(
            'enabled' => 1,
            'phone' => '',
            'text' => 'Chat on WhatsApp',
            'message' => 'Hello, I need help.',
            'position' => 'right',
        ));
    }

    public function field_enabled() {
        $settings = $this->get_settings();
        $checked = !empty($settings['enabled']) ? 'checked' : '';
        echo '<label><input type="checkbox" name="awcwp_settings[enabled]" value="1" ' . esc_attr($checked) . '> Show floating chat button</label>';
    }

    public function field_phone() {
        $settings = $this->get_settings();
        echo '<input type="text" class="regular-text" name="awcwp_settings[phone]" value="' . esc_attr($settings['phone']) . '" placeholder="Country code + number">';
    }

    public function field_text() {
        $settings = $this->get_settings();
        echo '<input type="text" class="regular-text" name="awcwp_settings[text]" value="' . esc_attr($settings['text']) . '">';
    }

    public function field_message() {
        $settings = $this->get_settings();
        echo '<textarea class="large-text" rows="4" name="awcwp_settings[message]">' . esc_textarea($settings['message']) . '</textarea>';
    }

    public function field_position() {
        $settings = $this->get_settings();
        $left = $settings['position'] === 'left' ? 'selected' : '';
        $right = $settings['position'] === 'right' ? 'selected' : '';
        echo '<select name="awcwp_settings[position]">';
        echo '<option value="right" ' . esc_attr($right) . '>Right</option>';
        echo '<option value="left" ' . esc_attr($left) . '>Left</option>';
        echo '</select>';
    }
}
