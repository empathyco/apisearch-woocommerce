<?php

// Function to register and initialize settings
function register_apisearch_settings() {
    // Register settings with validation callbacks
    register_setting('apisearch_settings', 'show_search_input', 'intval');
    register_setting('apisearch_settings', 'index_id', 'sanitize_text_field');
    register_setting('apisearch_settings', 'index_product_without_image', 'intval');
    register_setting('apisearch_settings', 'index_product_purchase_count', 'intval');
    register_setting('apisearch_settings', 'index_non_available_products', 'intval');
    register_setting('apisearch_settings', 'index_supplier_references', 'intval');
    register_setting('apisearch_settings', 'index_short_descriptions', 'intval');
    register_setting('apisearch_settings', 'uuid_auth_token', 'sanitize_text_field');
}

add_action('admin_init', 'register_apisearch_settings');

// Function to add a settings page to the WooCommerce settings menu
function add_apisearch_settings_page()
{
    add_submenu_page(
        'woocommerce', // Parent menu slug (WooCommerce)
        'Apisearch Settings',
        'Apisearch',
        'manage_options',
        'apisearch-settings',
        'render_apisearch_settings_page'
    );
}

// Function to set default plugin settings during activation
function set_default_apisearch_settings() {
    if (false === get_option('show_search_input')) {
        update_option('show_search_input', true);
    }

    if (false === get_option('index_id')) {
        update_option('index_id', '');
    }

    if (false === get_option('index_product_without_image')) {
        update_option('index_product_without_image', true);
    }

    if (false === get_option('index_product_purchase_count')) {
        update_option('index_product_purchase_count', true);
    }

    if (false === get_option('index_non_available_products')) {
        update_option('index_non_available_products', false);
    }

    if (false === get_option('index_supplier_references')) {
        update_option('index_supplier_references', false);
    }

    if (false === get_option('index_short_descriptions')) {
        update_option('index_short_descriptions', false);
    }

    // Set the default UUID auth token during activation
    $default_uuid = generateUUID();
    update_option('uuid_auth_token', $default_uuid);
}

// Function to generate a UUID
function generateUUID() {
    $d = new DateTime();
    $uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
    $uuid = preg_replace_callback('/[xy]/', function($c) use ($d) {
        $r = hexdec($d->format('u'))/10000 * 16;
        $v = $c[0] == 'x' ? $r : ($r&0x3|0x8);
        return dechex($v);
    }, $uuid);
    return $uuid;
}

// Hook the table creation and default settings functions to plugin activation
register_activation_hook(__FILE__, 'set_default_apisearch_settings');

// Function to render the settings page
function render_apisearch_settings_page() {
    // Retrieve the UUID auth token from the options
    $uuid_auth_token = get_option('uuid_auth_token');

    // Generate the feed URL with the UUID as a GET parameter
    $feed_url = site_url('/wp-json/apisearch/v1/feed');

    // If a UUID auth token is available, add it as a GET parameter
    if (!empty($uuid_auth_token)) {
        $feed_url .= '?auth_uuid=' . urlencode($uuid_auth_token);
    }

    ?>
    <div class="wrap">
        <h2>Apisearch Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('apisearch_settings'); ?>
            <?php do_settings_sections('apisearch_settings'); ?>

            <h3>General</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Show Search Input</th>
                    <td>
                        <label for="show_search_input">
                            <input type="checkbox" name="show_search_input" id="show_search_input" value="1" <?php checked(get_option('show_search_input'), 1); ?>>
                            Show the search input
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Index ID</th>
                    <td>
                        <input type="text" name="index_id" id="index_id" value="<?php echo esc_attr(get_option('index_id')); ?>" class="regular-text">
                    </td>
                </tr>
            </table>

            <h3>Indexing Options</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Index Products Without Images</th>
                    <td>
                        <label for="index_product_without_image">
                            <input type="checkbox" name="index_product_without_image" id="index_product_without_image" value="1" <?php checked(get_option('index_product_without_image'), 1); ?>>
                            Enable
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Index Product Purchase Count</th>
                    <td>
                        <label for="index_product_purchase_count">
                            <input type="checkbox" name="index_product_purchase_count" id="index_product_purchase_count" value="1" <?php checked(get_option('index_product_purchase_count'), 1); ?>>
                            Enable
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Index Non-Available Products</th>
                    <td>
                        <label for="index_non_available_products">
                            <input type="checkbox" name="index_non_available_products" id="index_non_available_products" value="1" <?php checked(get_option('index_non_available_products'), 1); ?>>
                            Enable
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Index Supplier References</th>
                    <td>
                        <label for="index_supplier_references">
                            <input type="checkbox" name="index_supplier_references" id="index_supplier_references" value="1" <?php checked(get_option('index_supplier_references'), 1); ?>>
                            Enable
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Index Short Descriptions</th>
                    <td>
                        <label for="index_short_descriptions">
                            <input type="checkbox" name="index_short_descriptions" id="index_short_descriptions" value="1" <?php checked(get_option('index_short_descriptions'), 1); ?>>
                            Enable
                        </label>
                    </td>
                </tr>

            </table>

            <h3>Authentication Options</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">UUID Auth Token</th>
                    <td>
                        <input type="text" name="uuid_auth_token" id="uuid_auth_token" value="<?php echo esc_attr(get_option('uuid_auth_token')); ?>" class="regular-text" readonly>
                        <button class="button button-secondary" id="regenerate_uuid">
                            <i class="dashicons dashicons-update" style="padding: 4px 4px 4px 0;"></i>
                            Regenerate
                        </button>
                    </td>
                </tr>
            </table>

            <!-- Display read-only feed URL field -->
            <h3>Feed URL</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Feed URL</th>
                    <td>
                        <input type="text" name="feed_url" id="feed_url" value="<?php echo esc_attr($feed_url); ?>" class="regular-text" readonly>
                        <button class="button button-secondary" id="copy_to_clipboard">
                            <i class="dashicons dashicons-admin-page"  style="padding: 4px 4px 4px 0;"></i> Copy to Clipboard
                        </button>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#regenerate_uuid').click(function() {
                // Generate a new UUID
                var newUuid = generateUUID();

                // Update the input field with the new UUID
                $('#uuid_auth_token').val(newUuid);
            });

            // Function to generate a UUID
            function generateUUID() {
                var d = new Date().getTime();
                var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = (d + Math.random() * 16) % 16 | 0;
                    d = Math.floor(d / 16);
                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
                return uuid;
            }

            $('#copy_to_clipboard').click(function() {
                // Copy the feed URL to the clipboard
                var feedUrlField = document.getElementById('feed_url');
                feedUrlField.select();
                document.execCommand('copy');
            });
        });
    </script>

    <?php
}


// Hook to add the settings page to the WooCommerce admin menu
add_action('admin_menu', 'add_apisearch_settings_page');