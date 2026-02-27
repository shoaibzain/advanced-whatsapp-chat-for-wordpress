<?php
/**
 * Plugin Name: Advanced WhatsApp Chat for WordPress
 * Plugin URI: https://github.com/shoaibzain/advanced-whatsapp-chat-for-wordpress
 * Description: Floating WhatsApp chat button with customizable phone number, message, text, and position.
 * Version: 1.0.0
 * Author: Shoaib Zain
 * Author URI: https://github.com/shoaibzain
 * License: GPL-2.0+
 * Text Domain: advanced-whatsapp-chat
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AWCWP_VERSION', '1.0.0');
define('AWCWP_FILE', __FILE__);
define('AWCWP_PATH', plugin_dir_path(__FILE__));
define('AWCWP_URL', plugin_dir_url(__FILE__));

require_once AWCWP_PATH . 'includes/class-awcwp-settings.php';

class AWCWP_Plugin {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'render_button'));
        add_shortcode('awcwp_chat', array($this, 'shortcode'));
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'awcwp-style',
            AWCWP_URL . 'assets/css/awcwp-chat.css',
            array(),
            AWCWP_VERSION
        );

        wp_enqueue_script(
            'awcwp-script',
            AWCWP_URL . 'assets/js/awcwp-chat.js',
            array(),
            AWCWP_VERSION,
            true
        );

        $settings = get_option('awcwp_settings', array());

        $phone = isset($settings['phone']) ? preg_replace('/[^0-9]/', '', $settings['phone']) : '';
        $message = isset($settings['message']) ? $settings['message'] : 'Hello, I need help.';
        $text = isset($settings['text']) ? $settings['text'] : 'Chat on WhatsApp';
        $position = isset($settings['position']) ? $settings['position'] : 'right';

        wp_localize_script('awcwp-script', 'awcwpData', array(
            'phone' => $phone,
            'message' => $message,
            'text' => $text,
            'position' => in_array($position, array('left', 'right'), true) ? $position : 'right',
        ));
    }

    public function render_button() {
        $settings = get_option('awcwp_settings', array());
        $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : true;

        if (!$enabled) {
            return;
        }

        echo $this->get_button_markup();
    }

    public function shortcode() {
        return $this->get_button_markup(true);
    }

    private function get_button_markup($is_shortcode = false) {
        $mode = $is_shortcode ? 'awcwp-shortcode' : 'awcwp-floating';

        return '<div class="awcwp-wrap ' . esc_attr($mode) . '">' .
            '<a class="awcwp-button" href="#" aria-label="Open WhatsApp Chat">' .
            '<span class="awcwp-icon" aria-hidden="true">W</span>' .
            '<span class="awcwp-text"></span>' .
            '</a>' .
            '</div>';
    }
}

new AWCWP_Settings();
new AWCWP_Plugin();
