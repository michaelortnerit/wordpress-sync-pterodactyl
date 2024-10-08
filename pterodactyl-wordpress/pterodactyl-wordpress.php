<?php
function pterodactyl_install() {
    add_option('pterodactyl_api_key', '');
    add_option('pterodactyl_base_url', '');
    add_option('pterodactyl_enable_name_fields', 'yes');
}

register_activation_hook(__FILE__, 'pterodactyl_install');

function pterodactyl_uninstall() {
    delete_option('pterodactyl_api_key');
    delete_option('pterodactyl_base_url');
    delete_option('pterodactyl_enable_name_fields');
}

register_deactivation_hook(__FILE__, 'pterodactyl_uninstall');

function pterodactyl_add_admin_menu() {
    add_menu_page(
        'Pterodactyl Integration',
        'Pterodactyl Settings',
        'manage_options',
        'pterodactyl_integration',
        'pterodactyl_options_page'
    );
}

add_action('admin_menu', 'pterodactyl_add_admin_menu');

function pterodactyl_register_settings() {
    register_setting('pterodactyl_settings_group', 'pterodactyl_api_key');
    register_setting('pterodactyl_settings_group', 'pterodactyl_base_url');
    register_setting('pterodactyl_settings_group', 'pterodactyl_enable_name_fields');
}

add_action('admin_init', 'pterodactyl_register_settings');

function pterodactyl_options_page() {
    ?>
    <div class="wrap">
        <h1>Pterodactyl Integration Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pterodactyl_settings_group');
            do_settings_sections('pterodactyl_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Pterodactyl API Key</th>
                    <td><input type="text" name="pterodactyl_api_key" value="<?php echo esc_attr(get_option('pterodactyl_api_key')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Pterodactyl Base URL</th>
                    <td><input type="text" name="pterodactyl_base_url" value="<?php echo esc_attr(get_option('pterodactyl_base_url')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable name fields in registration form</th>
                    <td>
                        <input type="checkbox" name="pterodactyl_enable_name_fields" value="yes" <?php checked(get_option('pterodactyl_enable_name_fields'), 'yes'); ?> />
                        <label for="pterodactyl_enable_name_fields">Enable "First Name" and "Last Name" fields</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function pterodactyl_show_extra_register_fields() {
    $enable_name_fields = get_option('pterodactyl_enable_name_fields');

    if ($enable_name_fields === 'yes') {
        ?>
        <p>
            <label for="first_name"><?php _e('First Name', 'mydomain') ?><br />
                <input type="text" name="first_name" id="first_name" class="input" size="25" />
            </label>
        </p>
        <p>
            <label for="last_name"><?php _e('Last Name', 'mydomain') ?><br />
                <input type="text" name="last_name" id="last_name" class="input" size="25" />
            </label>
        </p>
        <?php
    }
}

add_action('register_form', 'pterodactyl_show_extra_register_fields');

function pterodactyl_save_extra_register_fields($user_id) {
    if (isset($_POST['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
    if (isset($_POST['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }
}

add_action('user_register', 'pterodactyl_save_extra_register_fields');

function pterodactyl_user_registration($user_id, $password = '') {
    $user_info = get_userdata($user_id);
    $api_url = get_option('pterodactyl_base_url') . '/api/application/users';
    $api_key = get_option('pterodactyl_api_key');

    if (empty($api_key) || empty($api_url)) {
        error_log('Pterodactyl API settings not configured.');
        return;
    }

    $first_name = !empty($user_info->first_name) ? $user_info->first_name : 'No First Name';
    $last_name = !empty($user_info->last_name) ? $user_info->last_name : 'No Last Name';

    if (empty($password)) {
        $password = wp_generate_password(12, false);
    }

    $body = json_encode([
        'username' => $user_info->user_login,
        'email' => $user_info->user_email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'password' => $password
    ]);

    $response = wp_remote_post($api_url, [
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => $body
    ]);

    if (is_wp_error($response)) {
        error_log('Error registering user in Pterodactyl: ' . $response->get_error_message());
        return;
    }

    $response_body = wp_remote_retrieve_body($response);
    $user_data = json_decode($response_body, true);

    if ($user_data && isset($user_data['attributes']['id'])) {
        update_user_meta($user_id, 'pterodactyl_user_id', $user_data['attributes']['id']);
    }
}

function pterodactyl_user_profile_update($user_id, $old_user_data) {
    $user_info = get_userdata($user_id);
    if (!empty($user_info->user_pass)) {
        if (!get_user_meta($user_id, 'pterodactyl_user_id', true)) {
            pterodactyl_user_registration($user_id, $user_info->user_pass);
        }
    }
}

add_action('profile_update', 'pterodactyl_user_profile_update', 10, 2);

function pterodactyl_add_manual_registration_button($user) {
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }

    $pterodactyl_user_id = get_user_meta($user->ID, 'pterodactyl_user_id', true);
    if (!$pterodactyl_user_id) {
        echo '<h3>Pterodactyl Account</h3>';
        echo '<form method="post">';
        echo '<input type="hidden" name="pterodactyl_register_user" value="' . esc_attr($user->ID) . '">';
        echo '<p>The user is not yet registered in Pterodactyl.</p>';
        submit_button('Register User in Pterodactyl');
        echo '</form>';
    }
}

add_action('show_user_profile', 'pterodactyl_add_manual_registration_button');
add_action('edit_user_profile', 'pterodactyl_add_manual_registration_button');

function pterodactyl_manual_user_registration() {
    if (isset($_POST['pterodactyl_register_user']) && current_user_can('edit_user', intval($_POST['pterodactyl_register_user']))) {
        $user_id = intval($_POST['pterodactyl_register_user']);
        pterodactyl_user_registration($user_id);
        wp_redirect(add_query_arg(['updated' => 'true'], wp_get_referer()));
        exit;
    }
}

add_action('admin_init', 'pterodactyl_manual_user_registration');
?>

