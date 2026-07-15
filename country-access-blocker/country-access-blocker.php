<?php
/*
Plugin Name: Country Access Blocker
Description: Block or allow traffic from specific countries using a clean, compliant country list.
Version: 1.6
Author: Valeri Kluger
Author URI: https://premium-plugin.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: country-access-blocker
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;


// === Block visitors based on their country ===
add_action('wp_loaded', function () {
    if (is_admin()) return;

    $enabled = (bool) get_option('cab_enable_ip_check', false);
    if (!$enabled) return;

    $blocked = get_option('cab_blocked_countries', []);
    if (!is_array($blocked)) $blocked = [];

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $country = cab_get_country($ip);
    $country = sanitize_text_field($country);

    if (in_array($country, $blocked, true)) {

        $message = sprintf(
            /* translators: %s is the visitor's country code, e.g. 'DE' or 'US'. */
            esc_html__('Access from your country (%s) has been blocked.', 'country-access-blocker'),
            $country
        );

        nocache_headers();
        status_header(403);
        exit(esc_html($message));
    }
});

// === Get country code via ip-api.com using wp_remote_get ===
function cab_get_country($ip) {
    if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
        return 'XX';
    }

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=countryCode';
    $response = wp_remote_get($url, ['timeout' => 3]);

    if (is_wp_error($response)) return 'XX';

    $body = wp_remote_retrieve_body($response);
    if (!$body) return 'XX';

    $data = json_decode($body, true);
    if (empty($data['countryCode']) || $data['countryCode'] === 'XX') return 'XX';

    return sanitize_text_field($data['countryCode']);
}

// === Add admin menu page for the plugin ===
add_action('admin_menu', function () {
    add_menu_page(
        esc_html__('Country Blocker', 'country-access-blocker'),
        esc_html__('Country Blocker', 'country-access-blocker'),
        'manage_options',
        'cab',
        'cab_admin_page'
    );
});

// === Enqueue admin CSS/JS only on this plugin page ===
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_cab') return;

    // CSS
    $css_file = plugin_dir_path(__FILE__) . 'assets/css/admin.css';
    $ver_css  = file_exists($css_file) ? (string) filemtime($css_file) : '1.5';

    wp_enqueue_style(
        'cab-admin',
        plugins_url('assets/css/admin.css', __FILE__),
        [],
        $ver_css
    );

    // JS
    $js_file = plugin_dir_path(__FILE__) . 'assets/js/admin.js';
    $ver_js  = file_exists($js_file) ? (string) filemtime($js_file) : '1.5';

    wp_enqueue_script(
        'cab-admin',
        plugins_url('assets/js/admin.js', __FILE__),
        [],
        $ver_js,
        true
    );
});

// === Render the admin settings page ===
function cab_admin_page() {
    $countries = cab_get_country_list();

    $blocked = get_option('cab_blocked_countries', []);
    if (!is_array($blocked)) $blocked = [];

    $prev_enabled   = (bool) get_option('cab_enable_ip_check', false); // before POST
    $ipCheckEnabled = $prev_enabled;

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

    $post_data = wp_unslash($_POST);
    $post_block_raw_unslashed = isset($post_data['block']) ? $post_data['block'] : [];

    $just_enabled = false;

    if (
        isset($_SERVER['REQUEST_METHOD']) &&
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        check_admin_referer('cab_save')
    ) {
        $raw_block = is_array($post_block_raw_unslashed) ? $post_block_raw_unslashed : [];

        $allowed_codes = array_keys($countries);
        $new = [];

        foreach ($raw_block as $code => $val) {
            $code = sanitize_text_field($code);
            if (in_array($code, $allowed_codes, true)) {
                $new[] = $code;
            }
        }

        $ipCheck = isset($post_data['ip_check']) ? 1 : 0;

        update_option('cab_blocked_countries', $new);
        update_option('cab_enable_ip_check', $ipCheck);

        $blocked = $new;
        $ipCheckEnabled = (bool) $ipCheck;

        $just_enabled = (!$prev_enabled && $ipCheckEnabled);

        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'country-access-blocker') . '</p></div>';
    }

    // keep $mine as plain value (no translation / no placeholders)
    $mine = 'unknown';
    if ($ipCheckEnabled) {
        $mine = cab_get_country($ip);
        $mine = sanitize_text_field($mine);
        if ($mine === '') $mine = 'unknown';
    }

    $page_class = $ipCheckEnabled ? 'cab-enabled' : 'cab-disabled';

    echo '<div class="wrap cab-page ' . esc_attr($page_class) . '" data-mine="' . esc_attr($mine) . '" data-just-enabled="' . esc_attr($just_enabled ? '1' : '0') . '">';
    echo '<h1>' . esc_html__('Country Blocker', 'country-access-blocker') . '</h1>';

    echo '<form method="post">';
    wp_nonce_field('cab_save');

    if (!$ipCheckEnabled) {

        echo '<div class="cab-enable-gate" role="region" aria-label="' . esc_attr__('Enable country blocking', 'country-access-blocker') . '">';
        echo '  <div class="cab-enable-card">';

        echo '    <h2 class="cab-enable-title">' . esc_html__('Enable IP-based country blocking', 'country-access-blocker') . '</h2>';
        echo '    <p class="cab-enable-sub">' . esc_html__('This plugin works only when IP-based blocking is enabled.', 'country-access-blocker') . '</p>';

        echo '    <label class="cab-enable-toggle">';
        echo '      <input class="cab-enable-checkbox" type="checkbox" name="ip_check" value="1" ' . checked($ipCheckEnabled, true, false) . '>';
        echo '      <span class="cab-enable-label">' . esc_html__('Enable IP-based country blocking (uses ip-api.com | 45 requests per minute | Free Version)', 'country-access-blocker') . '</span>';
        echo '    </label>';

        echo '    <div class="cab-enable-actions">';
        echo '      <input type="submit" class="button button-primary cab-enable-save" value="' . esc_attr__('Save settings', 'country-access-blocker') . '">';
        echo '    </div>';

        echo '  </div>';
        echo '</div>';

        echo '</form></div>';
        return;
    }

    $valeri_img = plugins_url('assets/image/valeri.png', __FILE__);
    $cab_dismiss_key = 'cab_dev_card_dismiss_until_v1';
    $premium_url = 'https://premium-plugin.com';
    $review_url  = 'https://wordpress.org/support/plugin/country-access-blocker/reviews/#new-post';

    echo '<div class="cab-card cab-card--delayed" id="cab-dev-card" data-dismiss-key="' . esc_attr($cab_dismiss_key) . '" style="display:none;">';
    echo '  <button type="button" class="cab-close" aria-label="' . esc_attr__('Dismiss', 'country-access-blocker') . '">×</button>';
    echo '  <img src="' . esc_url($valeri_img) . '" alt="' . esc_attr__('Valeri', 'country-access-blocker') . '">';
    echo '  <div class="cab-content">';
    echo '    <p>';
    echo '      <strong>' . esc_html__("Hi, I'm Valeri 👋", 'country-access-blocker') . '</strong><br>';
    echo '      ' . esc_html__('Thanks for using this plugin.', 'country-access-blocker') . ' ';
    echo '      ' . esc_html__('If you are looking for more helpful plugins, you can check out my website.', 'country-access-blocker') . ' ';
    echo '      ' . esc_html__('If you like this plugin, I would be happy about a review.', 'country-access-blocker');
    echo '    </p>';
    echo '    <div class="cab-actions">';
    echo '      <a class="cab-btn" href="' . esc_url($premium_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('premium-plugin.com', 'country-access-blocker') . '</a>';
    echo '      <a class="cab-btn-secondary" href="' . esc_url($review_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Rate this plugin', 'country-access-blocker') . '</a>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    echo '<p class="cab-ip-check"><label><input type="checkbox" name="ip_check" value="1" ' . checked($ipCheckEnabled, true, false) . '> ';
    echo esc_html__('Enable IP-based country blocking (uses ip-api.com | 45 requests per minute | Free Version)', 'country-access-blocker') . '</label></p>';

    echo '<div class="cab-row cab-row--right">';
    echo '<input type="submit" class="button button-primary" value="' . esc_attr__('Save settings', 'country-access-blocker') . '">';
    echo '</div>';

    echo '<p class="cab-detected"><strong>' . esc_html__('Your detected country:', 'country-access-blocker') . ' ' . esc_html($mine) . '</strong></p>';

    echo '<div class="cab-row">';
    echo '  <div class="cab-bulk-actions">';
    echo '    <button type="button" id="cab-btn-block-except-mine" class="button cab-btn-secondary-action">' . esc_html__('Block all except my country', 'country-access-blocker') . '</button> ';
    echo '    <button type="button" id="cab-btn-unblock-all" class="button cab-btn-secondary-action">' . esc_html__('Unblock all', 'country-access-blocker') . '</button>';
    echo '  </div>';
    echo '</div>';

    echo '<table class="widefat striped cab-table"><thead><tr>';
    echo '<th>' . esc_html__('Country', 'country-access-blocker') . '</th>';
    echo '<th>' . esc_html__('Status', 'country-access-blocker') . '</th>';
    echo '<th>' . esc_html__('Block', 'country-access-blocker') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($countries as $code => $label) {
        $isBlocked = in_array($code, $blocked, true);

        $status_text = $isBlocked
            ? esc_html__('Blocked', 'country-access-blocker')
            : esc_html__('Allowed', 'country-access-blocker');

        $status_class = $isBlocked ? 'cab-status cab-status--blocked' : 'cab-status cab-status--allowed';

        echo '<tr>';
        echo '<td>' . esc_html($label) . ' (' . esc_html($code) . ')</td>';
        echo '<td><span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span></td>';
        echo '<td><input type="checkbox" name="block[' . esc_attr($code) . ']" ' . checked($isBlocked, true, false) . '></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '</form></div>';
}

// === Get WordPress-compatible country list ===
function cab_get_country_list() {
    return [
        'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AD'=>'Andorra','AO'=>'Angola','AG'=>'Antigua and Barbuda',
        'AR'=>'Argentina','AM'=>'Armenia','AU'=>'Australia','AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain',
        'BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BT'=>'Bhutan',
        'BO'=>'Bolivia','BA'=>'Bosnia and Herzegovina','BW'=>'Botswana','BR'=>'Brazil','BN'=>'Brunei','BG'=>'Bulgaria',
        'BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CV'=>'Cape Verde','CF'=>'Central African Republic',
        'TD'=>'Chad','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','KM'=>'Comoros','CG'=>'Congo','CD'=>'Congo (DRC)',
        'CR'=>'Costa Rica','CI'=>'Côte d’Ivoire','HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic',
        'DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador',
        'GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','SZ'=>'Eswatini','ET'=>'Ethiopia','FJ'=>'Fiji','FI'=>'Finland',
        'FR'=>'France','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','GD'=>'Grenada',
        'GT'=>'Guatemala','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary',
        'IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy',
        'JM'=>'Jamaica','JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KW'=>'Kuwait','KG'=>'Kyrgyzstan',
        'LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya','LI'=>'Liechtenstein','LT'=>'Lithuania',
        'LU'=>'Luxembourg','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands',
        'MR'=>'Mauritania','MU'=>'Mauritius','MX'=>'Mexico','FM'=>'Micronesia','MD'=>'Moldova','MC'=>'Monaco','MN'=>'Mongolia',
        'ME'=>'Montenegro','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru','NP'=>'Nepal','NL'=>'Netherlands',
        'NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','MK'=>'North Macedonia','NO'=>'Norway','OM'=>'Oman',
        'PK'=>'Pakistan','PW'=>'Palau','PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines',
        'PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania','RU'=>'Russia','RW'=>'Rwanda','KN'=>'Saint Kitts and Nevis',
        'LC'=>'Saint Lucia','VC'=>'Saint Vincent and the Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'Sao Tome and Principe',
        'SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia',
        'SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa',
        'KP'=>'North Korea',
        'KR'=>'South Korea','SS'=>'South Sudan',
        'ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan',
        'TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TO'=>'Tonga','TT'=>'Trinidad and Tobago',
        'TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan','TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates',
        'GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VE'=>'Venezuela',
        'VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe',

        // === Special Cases ===
        'HK'=>'Hong Kong',
        'MO'=>'Macao',
        'XK'=>'Kosovo',
        'PS'=>'Palestine',
        'VA'=>'Vatican City'
    ];
}
