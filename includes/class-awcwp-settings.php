<?php

if (!defined('ABSPATH')) {
    exit;
}

class AWCWP_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function default_members() {
        return array(
            array(
                'name' => 'Sara Ahmed',
                'language' => 'English, Arabic',
                'number' => '971501112233',
                'predefined_text' => 'Hi Sara, I need help with company setup in UAE.',
                'form_details' => '<form class="awcwp-fallback-form"><p>Tell us your requirement and continue to WhatsApp.</p><p><input type="text" name="name" placeholder="Your Name" required></p><p><input type="email" name="email" placeholder="Email"></p><p><input type="text" name="phone" placeholder="Phone"></p><p><textarea name="message" placeholder="Message"></textarea></p><p><button type="submit">Continue to WhatsApp</button></p></form>',
                'form_url' => '',
                'avatar_url' => '',
                'status' => 0,
                'order' => 1,
            ),
            array(
                'name' => 'Ali Khan',
                'language' => 'English, Urdu, Hindi',
                'number' => '971502224466',
                'predefined_text' => 'Hello Ali, I want a consultation for tax and accounting.',
                'form_details' => '<form class="awcwp-fallback-form"><p>Share your details and continue to WhatsApp.</p><p><input type="text" name="name" placeholder="Your Name" required></p><p><input type="email" name="email" placeholder="Email"></p><p><input type="text" name="phone" placeholder="Phone"></p><p><textarea name="message" placeholder="Message"></textarea></p><p><button type="submit">Continue to WhatsApp</button></p></form>',
                'form_url' => '',
                'avatar_url' => '',
                'status' => 0,
                'order' => 2,
            ),
        );
    }

    public static function default_settings() {
        return array(
            'enabled' => 1,
            'position' => 'right',
            'title' => 'Hi, how can we help?',
            'intro' => 'Choose a consultant and continue on WhatsApp.',
            'team_members' => self::default_members(),
        );
    }

    public static function sanitize_team_members($members_input) {
        $members = $members_input;

        if (is_string($members_input)) {
            $decoded = json_decode($members_input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $members = $decoded;
            } else {
                $members = array();
            }
        }

        if (!is_array($members)) {
            return array();
        }

        $sanitized = array();

        foreach ($members as $index => $member) {
            if (!is_array($member)) {
                continue;
            }

            $name = isset($member['name']) ? sanitize_text_field($member['name']) : '';
            if ($name === '') {
                continue;
            }

            $sanitized[] = array(
                'name' => $name,
                'language' => isset($member['language']) ? sanitize_text_field($member['language']) : '',
                'number' => isset($member['number']) ? preg_replace('/[^0-9]/', '', (string) $member['number']) : '',
                'predefined_text' => isset($member['predefined_text']) ? sanitize_textarea_field($member['predefined_text']) : 'Hi, I need help.',
                'form_details' => isset($member['form_details']) ? wp_kses_post($member['form_details']) : '',
                'form_url' => isset($member['form_url']) ? esc_url_raw($member['form_url']) : '',
                'avatar_url' => isset($member['avatar_url']) ? esc_url_raw($member['avatar_url']) : '',
                'status' => isset($member['status']) ? absint($member['status']) : 0,
                'order' => isset($member['order']) ? absint($member['order']) : (int) $index,
            );
        }

        usort($sanitized, function ($a, $b) {
            return (int) $a['order'] <=> (int) $b['order'];
        });

        return $sanitized;
    }

    public static function get_settings() {
        $defaults = self::default_settings();
        $saved = get_option('awcwp_settings', array());
        $saved = is_array($saved) ? $saved : array();

        $settings = wp_parse_args($saved, $defaults);

        $settings['enabled'] = !empty($settings['enabled']) ? 1 : 0;
        $settings['position'] = in_array($settings['position'], array('left', 'right'), true) ? $settings['position'] : 'right';
        $settings['title'] = sanitize_text_field($settings['title']);
        $settings['intro'] = sanitize_text_field($settings['intro']);

        $settings['team_members'] = self::sanitize_team_members(isset($settings['team_members']) ? $settings['team_members'] : array());
        if (empty($settings['team_members'])) {
            $settings['team_members'] = self::default_members();
        }

        return $settings;
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
            'Advanced Widget Settings',
            function () {
                echo '<p>Configure the same advanced popup WhatsApp behavior as g12-child.</p>';
            },
            'awcwp-settings'
        );

        add_settings_field('enabled', 'Enable Widget', array($this, 'field_enabled'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('position', 'Widget Position', array($this, 'field_position'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('title', 'Heading Title', array($this, 'field_title'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('intro', 'Heading Intro', array($this, 'field_intro'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('team_members_json', 'Team Members (JSON)', array($this, 'field_team_members_json'), 'awcwp-settings', 'awcwp_main_section');
    }

    public function sanitize($input) {
        $defaults = self::default_settings();
        $output = array();

        $output['enabled'] = isset($input['enabled']) ? 1 : 0;

        $position = isset($input['position']) ? sanitize_text_field(wp_unslash($input['position'])) : $defaults['position'];
        $output['position'] = in_array($position, array('left', 'right'), true) ? $position : 'right';

        $output['title'] = isset($input['title']) ? sanitize_text_field(wp_unslash($input['title'])) : $defaults['title'];
        $output['intro'] = isset($input['intro']) ? sanitize_text_field(wp_unslash($input['intro'])) : $defaults['intro'];

        $members_json = isset($input['team_members_json']) ? wp_unslash($input['team_members_json']) : '';
        $members = self::sanitize_team_members($members_json);

        if (empty($members)) {
            $legacy_phone = isset($input['phone']) ? preg_replace('/[^0-9]/', '', (string) wp_unslash($input['phone'])) : '';
            $legacy_message = isset($input['message']) ? sanitize_textarea_field(wp_unslash($input['message'])) : 'Hello, I need help.';

            if ($legacy_phone !== '') {
                $members = array(
                    array(
                        'name' => 'Support Team',
                        'language' => 'Online',
                        'number' => $legacy_phone,
                        'predefined_text' => $legacy_message,
                        'form_details' => '<form class="awcwp-fallback-form"><p>Share your details and continue to WhatsApp.</p><p><input type="text" name="name" placeholder="Your Name" required></p><p><input type="email" name="email" placeholder="Email"></p><p><input type="text" name="phone" placeholder="Phone"></p><p><textarea name="message" placeholder="Message"></textarea></p><p><button type="submit">Continue to WhatsApp</button></p></form>',
                        'form_url' => '',
                        'avatar_url' => '',
                        'status' => 0,
                        'order' => 1,
                    ),
                );
            }
        }

        if (empty($members)) {
            $members = self::default_members();
        }

        $output['team_members'] = $members;

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

    public function field_enabled() {
        $settings = self::get_settings();
        ?>
        <label>
            <input type="checkbox" name="awcwp_settings[enabled]" value="1" <?php checked(1, (int) $settings['enabled']); ?>>
            Show advanced floating WhatsApp widget
        </label>
        <?php
    }

    public function field_position() {
        $settings = self::get_settings();
        ?>
        <select name="awcwp_settings[position]">
            <option value="right" <?php selected($settings['position'], 'right'); ?>>Right</option>
            <option value="left" <?php selected($settings['position'], 'left'); ?>>Left</option>
        </select>
        <?php
    }

    public function field_title() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="regular-text" name="awcwp_settings[title]" value="<?php echo esc_attr($settings['title']); ?>">
        <?php
    }

    public function field_intro() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="regular-text" name="awcwp_settings[intro]" value="<?php echo esc_attr($settings['intro']); ?>">
        <?php
    }

    public function field_team_members_json() {
        $settings = self::get_settings();
        $json = wp_json_encode($settings['team_members'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        ?>
        <textarea
            name="awcwp_settings[team_members_json]"
            rows="18"
            class="large-text code"
            spellcheck="false"
        ><?php echo esc_textarea($json); ?></textarea>
        <p class="description">
            JSON keys per member: <code>name</code>, <code>language</code>, <code>number</code>, <code>predefined_text</code>, <code>form_details</code>, <code>form_url</code>, <code>avatar_url</code>, <code>status</code>, <code>order</code>.
        </p>
        <p class="description">
            Set <code>status</code> to <code>1</code> to hide a member. Use <code>form_url</code> for iframe forms or <code>form_details</code> for inline HTML forms.
        </p>
        <?php
    }
}
