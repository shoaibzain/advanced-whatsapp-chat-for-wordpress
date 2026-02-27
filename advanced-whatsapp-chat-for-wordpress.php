<?php
/**
 * Plugin Name: Advanced WhatsApp Chat for WordPress
 * Plugin URI: https://github.com/shoaibzain/advanced-whatsapp-chat-for-wordpress
 * Description: Advanced floating WhatsApp widget with team selection, form loading, and s_url tracking.
 * Version: 1.1.0
 * Author: Shoaib Zain
 * Author URI: https://github.com/shoaibzain
 * License: GPL-2.0+
 * Text Domain: advanced-whatsapp-chat
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AWCWP_VERSION', '1.1.0');
define('AWCWP_FILE', __FILE__);
define('AWCWP_PATH', plugin_dir_path(__FILE__));
define('AWCWP_URL', plugin_dir_url(__FILE__));

require_once AWCWP_PATH . 'includes/class-awcwp-settings.php';

class AWCWP_Plugin {
    private $rendered = false;

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'render_widget'));
        add_shortcode('awcwp_chat', array($this, 'shortcode'));
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'awcwp-google-font-poppins',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'awcwp-style',
            AWCWP_URL . 'assets/css/awcwp-chat.css',
            array('awcwp-google-font-poppins'),
            AWCWP_VERSION
        );

        wp_enqueue_script(
            'awcwp-script',
            AWCWP_URL . 'assets/js/awcwp-chat.js',
            array(),
            AWCWP_VERSION,
            true
        );

        $settings = AWCWP_Settings::get_settings();

        wp_localize_script('awcwp-script', 'awcwpData', array(
            'title' => $settings['title'],
            'intro' => $settings['intro'],
            'position' => in_array($settings['position'], array('left', 'right'), true) ? $settings['position'] : 'right',
            'members' => $settings['team_members'],
            'strings' => array(
                'noMembers' => 'No team members found.',
                'missingPhone' => 'Selected member has no WhatsApp number.',
            ),
        ));
    }

    public function render_widget() {
        $settings = AWCWP_Settings::get_settings();

        if (empty($settings['enabled']) || $this->rendered) {
            return;
        }

        echo $this->get_widget_markup('footer');
        $this->rendered = true;
    }

    public function shortcode() {
        $settings = AWCWP_Settings::get_settings();

        if (empty($settings['enabled']) || $this->rendered) {
            return '';
        }

        $this->rendered = true;

        return $this->get_widget_markup('shortcode');
    }

    private function get_widget_markup($context = 'footer') {
        $settings = AWCWP_Settings::get_settings();
        $members = AWCWP_Settings::sanitize_team_members($settings['team_members']);

        if (empty($members)) {
            $members = AWCWP_Settings::default_members();
        }

        $position = in_array($settings['position'], array('left', 'right'), true) ? $settings['position'] : 'right';
        $wrapper_classes = array('floating-whatsapp', 'awcwp-pos-' . $position);

        if ($context === 'shortcode') {
            $wrapper_classes[] = 'awcwp-shortcode';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <div class="floating-whatsapp__chat-box" aria-hidden="true">
                <button class="floating-whatsapp__close-btn" type="button" aria-label="Close chat">&times;</button>

                <div class="floating-whatsapp__heading">
                    <div class="floating-whatsapp__title"><?php echo esc_html($settings['title']); ?></div>
                    <div class="floating-whatsapp__intro"><?php echo esc_html($settings['intro']); ?></div>
                </div>

                <div class="floating-whatsapp__content">
                    <div class="floating-whatsapp__content-list">
                        <?php
                        $has_visible_members = false;

                        foreach ($members as $member):
                            if (!empty($member['status'])) {
                                continue;
                            }

                            $has_visible_members = true;
                            $name = isset($member['name']) ? $member['name'] : '';
                            $language = isset($member['language']) ? $member['language'] : '';
                            $number = isset($member['number']) ? preg_replace('/[^0-9]/', '', $member['number']) : '';
                            $predefined = isset($member['predefined_text']) ? $member['predefined_text'] : '';
                            $form_details = isset($member['form_details']) ? $member['form_details'] : '';
                            $form_url = isset($member['form_url']) ? $member['form_url'] : '';
                            $avatar_url = isset($member['avatar_url']) ? $member['avatar_url'] : '';
                            ?>
                            <div
                                class="floating-whatsapp__content-item-box"
                                data-member-name="<?php echo esc_attr($name); ?>"
                                data-account-number="<?php echo esc_attr($number); ?>"
                                data-member-predefinedtext="<?php echo esc_attr(urlencode($predefined)); ?>"
                                data-form-details="<?php echo esc_attr($form_details); ?>"
                                data-form-url="<?php echo esc_attr($form_url); ?>"
                            >
                                <div class="floating-whatsapp__avatar">
                                    <?php if (!empty($avatar_url)): ?>
                                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>">
                                    <?php else: ?>
                                        <span><?php echo esc_html($this->get_initials($name)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="floating-whatsapp__txt">
                                    <div class="floating-whatsapp__member-name"><?php echo esc_html($name); ?></div>
                                    <div class="floating-whatsapp__member-language"><?php echo esc_html($language); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$has_visible_members): ?>
                            <p class="floating-whatsapp__empty">No team members found.</p>
                        <?php endif; ?>
                    </div>

                    <div class="floating-whatsapp__form">
                        <div class="floating-whatsapp__form-content"></div>
                    </div>
                </div>
            </div>

            <div class="floating-whatsapp__btn">
                <button class="floating-whatsapp__icon" type="button" aria-label="Open WhatsApp Chat"></button>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function get_initials($name) {
        $clean_name = trim((string) $name);

        if ($clean_name === '') {
            return 'WA';
        }

        $parts = preg_split('/\s+/', $clean_name);
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return 'WA';
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        $first = strtoupper(substr($parts[0], 0, 1));
        $last = strtoupper(substr($parts[count($parts) - 1], 0, 1));

        return $first . $last;
    }
}

new AWCWP_Settings();
new AWCWP_Plugin();
