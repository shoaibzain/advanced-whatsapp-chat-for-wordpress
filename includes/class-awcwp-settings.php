<?php

if (!defined('ABSPATH')) {
    exit;
}

class AWCWP_Settings {
    const TEAM_MEMBER_POST_TYPE = 'awcwp_team_member';

    const META_LANGUAGE = '_awcwp_member_language';
    const META_NUMBER = '_awcwp_member_number';
    const META_PREDEFINED_TEXT = '_awcwp_member_predefined_text';
    const META_FORM_DETAILS = '_awcwp_member_form_details';
    const META_FORM_URL = '_awcwp_member_form_url';
    const META_AVATAR_URL = '_awcwp_member_avatar_url';
    const META_STATUS = '_awcwp_member_status';
    const META_ORDER = '_awcwp_member_order';

    public function __construct() {
        add_action('init', array($this, 'register_team_member_post_type'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'maybe_migrate_legacy_team_members'));

        add_action('add_meta_boxes', array($this, 'add_team_member_meta_boxes'));
        add_action('save_post_' . self::TEAM_MEMBER_POST_TYPE, array($this, 'save_team_member_meta'), 10, 2);
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

    public static function get_team_members_from_cpt() {
        $posts = get_posts(array(
            'post_type' => self::TEAM_MEMBER_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => self::META_ORDER,
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'no_found_rows' => true,
        ));

        if (empty($posts)) {
            return array();
        }

        $members = array();

        foreach ($posts as $post) {
            $name = sanitize_text_field(get_the_title($post->ID));
            if ($name === '') {
                continue;
            }

            $members[] = array(
                'name' => $name,
                'language' => sanitize_text_field((string) get_post_meta($post->ID, self::META_LANGUAGE, true)),
                'number' => preg_replace('/[^0-9]/', '', (string) get_post_meta($post->ID, self::META_NUMBER, true)),
                'predefined_text' => sanitize_textarea_field((string) get_post_meta($post->ID, self::META_PREDEFINED_TEXT, true)),
                'form_details' => wp_kses_post((string) get_post_meta($post->ID, self::META_FORM_DETAILS, true)),
                'form_url' => esc_url_raw((string) get_post_meta($post->ID, self::META_FORM_URL, true)),
                'avatar_url' => esc_url_raw((string) get_post_meta($post->ID, self::META_AVATAR_URL, true)),
                'status' => absint(get_post_meta($post->ID, self::META_STATUS, true)),
                'order' => absint(get_post_meta($post->ID, self::META_ORDER, true)),
            );
        }

        usort($members, function ($a, $b) {
            return (int) $a['order'] <=> (int) $b['order'];
        });

        return $members;
    }

    public function register_team_member_post_type() {
        $labels = array(
            'name' => 'Team Members',
            'singular_name' => 'Team Member',
            'menu_name' => 'Team Members',
            'name_admin_bar' => 'Team Member',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Team Member',
            'new_item' => 'New Team Member',
            'edit_item' => 'Edit Team Member',
            'view_item' => 'View Team Member',
            'all_items' => 'All Team Members',
            'search_items' => 'Search Team Members',
            'not_found' => 'No team members found.',
            'not_found_in_trash' => 'No team members found in Trash.',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'has_archive' => false,
            'rewrite' => false,
            'menu_icon' => $this->get_team_member_menu_icon(),
            'supports' => array('title'),
            'show_in_rest' => false,
        );

        register_post_type(self::TEAM_MEMBER_POST_TYPE, $args);
    }

    private function get_team_member_menu_icon() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="currentColor" d="M13.601 2.326A7.854 7.854 0 0 0 8.003 0C3.58 0 0 3.58 0 8.003a7.958 7.958 0 0 0 1.147 4.12L0 16l3.997-1.132A7.965 7.965 0 0 0 8.003 16C12.425 16 16 12.425 16 8.003a7.93 7.93 0 0 0-2.399-5.677zM8.003 14.5a6.49 6.49 0 0 1-3.309-.908l-.236-.14-2.37.67.633-2.31-.154-.237A6.48 6.48 0 0 1 1.5 8.003 6.5 6.5 0 0 1 8.003 1.5c1.737 0 3.36.676 4.59 1.904A6.44 6.44 0 0 1 14.5 8.003 6.5 6.5 0 0 1 8.003 14.5z"/><path fill="currentColor" d="M11.34 9.613c-.183-.091-1.083-.534-1.25-.594-.168-.06-.29-.091-.413.091-.122.183-.473.594-.58.716-.106.122-.213.137-.396.046-.183-.091-.773-.285-1.472-.909-.544-.485-.912-1.084-1.02-1.267-.106-.183-.011-.282.08-.373.082-.082.183-.213.274-.32.091-.106.122-.183.183-.305.061-.122.03-.229-.015-.32-.046-.091-.413-.996-.565-1.365-.149-.36-.3-.31-.413-.316l-.351-.006c-.122 0-.32.046-.488.229-.168.183-.64.625-.64 1.525s.655 1.77.747 1.892c.091.122 1.287 1.965 3.12 2.757.436.188.776.3 1.041.384.437.139.835.12 1.15.073.351-.052 1.083-.442 1.235-.87.153-.427.153-.793.107-.87-.046-.076-.168-.122-.35-.213z"/></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function maybe_migrate_legacy_team_members() {
        if (!is_admin()) {
            return;
        }

        if (get_option('awcwp_team_members_migrated', false)) {
            return;
        }

        $existing = get_posts(array(
            'post_type' => self::TEAM_MEMBER_POST_TYPE,
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        if (!empty($existing)) {
            update_option('awcwp_team_members_migrated', 1, false);
            return;
        }

        $saved = get_option('awcwp_settings', array());
        $saved = is_array($saved) ? $saved : array();

        $legacy_members = isset($saved['team_members']) ? self::sanitize_team_members($saved['team_members']) : array();

        if (empty($legacy_members)) {
            update_option('awcwp_team_members_migrated', 1, false);
            return;
        }

        foreach ($legacy_members as $member) {
            $post_id = wp_insert_post(array(
                'post_type' => self::TEAM_MEMBER_POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $member['name'],
            ));

            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            update_post_meta($post_id, self::META_LANGUAGE, $member['language']);
            update_post_meta($post_id, self::META_NUMBER, $member['number']);
            update_post_meta($post_id, self::META_PREDEFINED_TEXT, $member['predefined_text']);
            update_post_meta($post_id, self::META_FORM_DETAILS, $member['form_details']);
            update_post_meta($post_id, self::META_FORM_URL, $member['form_url']);
            update_post_meta($post_id, self::META_AVATAR_URL, $member['avatar_url']);
            update_post_meta($post_id, self::META_STATUS, absint($member['status']));
            update_post_meta($post_id, self::META_ORDER, absint($member['order']));
        }

        update_option('awcwp_team_members_migrated', 1, false);
    }

    public function add_team_member_meta_boxes() {
        add_meta_box(
            'awcwp-team-member-meta',
            'WhatsApp Member Details',
            array($this, 'render_team_member_meta_box'),
            self::TEAM_MEMBER_POST_TYPE,
            'normal',
            'default'
        );
    }

    public function render_team_member_meta_box($post) {
        wp_nonce_field('awcwp_save_team_member_meta', 'awcwp_team_member_nonce');

        $language = get_post_meta($post->ID, self::META_LANGUAGE, true);
        $number = get_post_meta($post->ID, self::META_NUMBER, true);
        $predefined_text = get_post_meta($post->ID, self::META_PREDEFINED_TEXT, true);
        $form_details = get_post_meta($post->ID, self::META_FORM_DETAILS, true);
        $form_url = get_post_meta($post->ID, self::META_FORM_URL, true);
        $avatar_url = get_post_meta($post->ID, self::META_AVATAR_URL, true);
        $status = absint(get_post_meta($post->ID, self::META_STATUS, true));
        $order = absint(get_post_meta($post->ID, self::META_ORDER, true));
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="awcwp_member_language">Language</label></th>
                <td><input type="text" class="regular-text" id="awcwp_member_language" name="awcwp_member_language" value="<?php echo esc_attr($language); ?>" placeholder="English, Arabic"></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_number">WhatsApp Number</label></th>
                <td><input type="text" class="regular-text" id="awcwp_member_number" name="awcwp_member_number" value="<?php echo esc_attr($number); ?>" placeholder="971501112233"></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_predefined_text">Predefined Text</label></th>
                <td><textarea class="large-text" rows="3" id="awcwp_member_predefined_text" name="awcwp_member_predefined_text"><?php echo esc_textarea($predefined_text); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_form_url">Form URL (iframe)</label></th>
                <td><input type="url" class="regular-text" id="awcwp_member_form_url" name="awcwp_member_form_url" value="<?php echo esc_attr($form_url); ?>" placeholder="https://forms.example.com/..." /></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_form_details">Form Details (HTML fallback)</label></th>
                <td><textarea class="large-text code" rows="6" id="awcwp_member_form_details" name="awcwp_member_form_details"><?php echo esc_textarea($form_details); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_avatar_url">Avatar URL</label></th>
                <td><input type="url" class="regular-text" id="awcwp_member_avatar_url" name="awcwp_member_avatar_url" value="<?php echo esc_attr($avatar_url); ?>" placeholder="https://.../avatar.jpg" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="awcwp_member_order">Display Order</label></th>
                <td><input type="number" min="0" step="1" id="awcwp_member_order" name="awcwp_member_order" value="<?php echo esc_attr((string) $order); ?>"></td>
            </tr>
            <tr>
                <th scope="row">Visibility</th>
                <td>
                    <label>
                        <input type="checkbox" name="awcwp_member_status" value="1" <?php checked(1, $status); ?>>
                        Hide this member from widget
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_team_member_meta($post_id, $post) {
        if (!$post || $post->post_type !== self::TEAM_MEMBER_POST_TYPE) {
            return;
        }

        if (!isset($_POST['awcwp_team_member_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['awcwp_team_member_nonce'])), 'awcwp_save_team_member_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $language = isset($_POST['awcwp_member_language']) ? sanitize_text_field(wp_unslash($_POST['awcwp_member_language'])) : '';
        $number = isset($_POST['awcwp_member_number']) ? preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['awcwp_member_number'])) : '';
        $predefined_text = isset($_POST['awcwp_member_predefined_text']) ? sanitize_textarea_field(wp_unslash($_POST['awcwp_member_predefined_text'])) : '';
        $form_details = isset($_POST['awcwp_member_form_details']) ? wp_kses_post(wp_unslash($_POST['awcwp_member_form_details'])) : '';
        $form_url = isset($_POST['awcwp_member_form_url']) ? esc_url_raw(wp_unslash($_POST['awcwp_member_form_url'])) : '';
        $avatar_url = isset($_POST['awcwp_member_avatar_url']) ? esc_url_raw(wp_unslash($_POST['awcwp_member_avatar_url'])) : '';
        $order = isset($_POST['awcwp_member_order']) ? absint($_POST['awcwp_member_order']) : 0;
        $status = isset($_POST['awcwp_member_status']) ? 1 : 0;

        update_post_meta($post_id, self::META_LANGUAGE, $language);
        update_post_meta($post_id, self::META_NUMBER, $number);
        update_post_meta($post_id, self::META_PREDEFINED_TEXT, $predefined_text);
        update_post_meta($post_id, self::META_FORM_DETAILS, $form_details);
        update_post_meta($post_id, self::META_FORM_URL, $form_url);
        update_post_meta($post_id, self::META_AVATAR_URL, $avatar_url);
        update_post_meta($post_id, self::META_ORDER, $order);
        update_post_meta($post_id, self::META_STATUS, $status);
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
                echo '<p>Configure the advanced popup WhatsApp behavior.</p>';
            },
            'awcwp-settings'
        );

        add_settings_field('enabled', 'Enable Widget', array($this, 'field_enabled'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('position', 'Widget Position', array($this, 'field_position'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('title', 'Heading Title', array($this, 'field_title'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('intro', 'Heading Intro', array($this, 'field_intro'), 'awcwp-settings', 'awcwp_main_section');
        add_settings_field('team_members_source', 'Team Members', array($this, 'field_team_members_source'), 'awcwp-settings', 'awcwp_main_section');
    }

    public function sanitize($input) {
        $defaults = self::default_settings();
        $output = array();

        $output['enabled'] = isset($input['enabled']) ? 1 : 0;

        $position = isset($input['position']) ? sanitize_text_field(wp_unslash($input['position'])) : $defaults['position'];
        $output['position'] = in_array($position, array('left', 'right'), true) ? $position : 'right';

        $output['title'] = isset($input['title']) ? sanitize_text_field(wp_unslash($input['title'])) : $defaults['title'];
        $output['intro'] = isset($input['intro']) ? sanitize_text_field(wp_unslash($input['intro'])) : $defaults['intro'];

        $current = get_option('awcwp_settings', array());
        $current_members = isset($current['team_members']) ? self::sanitize_team_members($current['team_members']) : array();
        $output['team_members'] = !empty($current_members) ? $current_members : self::default_members();

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

    public function field_team_members_source() {
        $manage_url = admin_url('edit.php?post_type=' . self::TEAM_MEMBER_POST_TYPE);
        $add_url = admin_url('post-new.php?post_type=' . self::TEAM_MEMBER_POST_TYPE);
        ?>
        <p>
            Team members are now managed via Custom Post Type.
            <a href="<?php echo esc_url($manage_url); ?>">Manage Team Members</a>
            or
            <a href="<?php echo esc_url($add_url); ?>">Add New Member</a>.
        </p>
        <p class="description">
            Fields available per member: Name (title), language, WhatsApp number, predefined text, form URL, HTML fallback form, avatar URL, status, and order.
        </p>
        <?php
    }
}
