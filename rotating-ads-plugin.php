<?php
/**
 * Plugin Name:       Rotating Ads Deluxe
 * Plugin URI:        https://example.com/rotating-ads-deluxe
 * Description:       Displays rotating advertisements for non-logged-in users with session-based view limits and daily reset.
 * Version:           1.0.0
 * Author:            AI Developer
 * Author URI:        https://example.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rotating-ads-deluxe
 * Domain Path:       /languages
 */

// If this file is called directly, abort. Prevents direct access to the file.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin activation hook.
 * Sets default values for plugin options if they don't already exist.
 * This function is called once when the plugin is activated.
 */
function rad_activate() {
    // Set default value for 'rad_views_before_rotation' if not already set.
    if (false === get_option('rad_views_before_rotation')) {
        add_option('rad_views_before_rotation', 5); // Default to 5 views.
    }
}
register_activation_hook(__FILE__, 'rad_activate');

/**
 * Plugin deactivation hook.
 * Placeholder for any cleanup needed when the plugin is deactivated.
 * For example, deleting options or custom tables. Currently, no specific deactivation tasks are implemented.
 */
function rad_deactivate() {
    // Placeholder for deactivation code.
    // Example: Remove options on deactivation if desired by uncommenting below.
    // delete_option('rad_ad_scripts');
    // delete_option('rad_views_before_rotation');
}
register_deactivation_hook(__FILE__, 'rad_deactivate');

/**
 * Add admin menu item for the plugin settings page.
 * This function hooks into 'admin_menu' to add a submenu page under 'Settings'.
 */
function rad_add_admin_menu() {
    add_submenu_page(
        'options-general.php', // Parent slug (Under 'Settings' menu)
        __('Rotating Ads Deluxe Settings', 'rotating-ads-deluxe'), // Page title that appears in <title> tag
        __('Rotating Ads', 'rotating-ads-deluxe'), // Menu title displayed in the admin sidebar
        'manage_options', // Capability required for users to access this menu item
        'rotating-ads-deluxe-settings', // Menu slug (unique identifier for the page)
        'rad_render_settings_page' // Callback function to render the HTML content of the page
    );
}
add_action('admin_menu', 'rad_add_admin_menu');

/**
 * Initialize plugin settings using the WordPress Settings API.
 * This function hooks into 'admin_init'. It registers settings, sections, and fields.
 */
function rad_settings_init() {
    // Register the main setting group.
    // All options will be part of this group and handled by options.php.
    register_setting(
        'rad_settings_group', // Option group name
        'rad_ad_scripts',     // Option name (database key)
        [
            'sanitize_callback' => 'sanitize_textarea_field', // Sanitize function for ad scripts.
            'type'              => 'string',                  // Data type.
            'default'           => '',                        // Default value.
        ]
    );
    register_setting(
        'rad_settings_group', // Option group name
        'rad_views_before_rotation', // Option name (database key)
        [
            'sanitize_callback' => 'absint', // Sanitize function for view count (absolute integer).
            'type'              => 'integer',// Data type.
            'default'           => 5,        // Default value.
        ]
    );

    // Add a settings section to the page.
    // Sections are groups of related settings fields.
    add_settings_section(
        'rad_main_section', // Unique ID for the section
        __('Ad Rotation Settings', 'rotating-ads-deluxe'), // Title of the section displayed to the user
        'rad_main_section_callback', // Callback function to render introductory text for the section (optional)
        'rotating-ads-deluxe-settings' // Page slug on which to display this section
    );

    // Add the 'Ad Scripts' field to the main section.
    add_settings_field(
        'rad_ad_scripts_field', // Unique ID for the field
        __('Ad Scripts (one per line)', 'rotating-ads-deluxe'), // Title of the field
        'rad_render_ad_scripts_field', // Callback function to render the field's HTML
        'rotating-ads-deluxe-settings', // Page slug
        'rad_main_section', // Section ID where this field will be placed
        ['label_for' => 'rad_ad_scripts_id'] // Associates the label with the input field for accessibility
    );

    // Add the 'Views Before Rotation' field to the main section.
    add_settings_field(
        'rad_views_before_rotation_field', // Unique ID for the field
        __('Views Per Ad Before Rotation', 'rotating-ads-deluxe'), // Title of the field
        'rad_render_views_field', // Callback function to render the field's HTML
        'rotating-ads-deluxe-settings', // Page slug
        'rad_main_section', // Section ID
        ['label_for' => 'rad_views_before_rotation_id'] // Associates label with input
    );
}
add_action('admin_init', 'rad_settings_init');

/**
 * Callback function for the main settings section.
 * Outputs a brief description under the section title.
 */
function rad_main_section_callback() {
    echo '<p>' . esc_html__('Configure the ad scripts and rotation behavior for non-logged-in users.', 'rotating-ads-deluxe') . '</p>';
}

/**
 * Render the settings page HTML structure.
 * This function is the callback for `add_submenu_page`.
 */
function rad_render_settings_page() {
    // Check if the current user has the required capability.
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'rotating-ads-deluxe'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output nonce, action, and option_page fields for a settings page.
            settings_fields('rad_settings_group');
            // Display the settings sections and fields for the specified page.
            do_settings_sections('rotating-ads-deluxe-settings');
            // Output the submit button.
            submit_button(__('Save Settings', 'rotating-ads-deluxe'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Render the Ad Scripts textarea field.
 * This is a callback function for `add_settings_field`.
 */
function rad_render_ad_scripts_field() {
    // Get the saved value of 'rad_ad_scripts' option.
    $ad_scripts = get_option('rad_ad_scripts', '');
    ?>
    <textarea id="rad_ad_scripts_id" name="rad_ad_scripts" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($ad_scripts); // Safely escape the content for display in a textarea ?></textarea>
    <p class="description"><?php esc_html_e( 'Enter each ad script or HTML snippet on a new line. These are typically provided by ad networks (e.g., <script>...</script>). Malicious scripts could pose a security risk; only add scripts from trusted sources.', 'rotating-ads-deluxe' ); ?></p>
    <?php
}

/**
 * Render the Views Before Rotation number input field.
 * This is a callback function for `add_settings_field`.
 */
function rad_render_views_field() {
    // Get the saved value of 'rad_views_before_rotation' option, default to 5.
    $views = get_option('rad_views_before_rotation', 5);
    ?>
    <input type="number" id="rad_views_before_rotation_id" name="rad_views_before_rotation" value="<?php echo esc_attr($views); // Safely escape the value for an HTML attribute ?>" min="1" />
    <p class="description"><?php esc_html_e( 'Number of times an ad is shown to a user before rotating to the next ad in the same session. Minimum 1.', 'rotating-ads-deluxe' ); ?></p>
    <?php
}

/**
 * Class RAD_Ad_Rotator
 * Handles the core ad rotation logic, session management, and script injection.
 * This class encapsulates all the frontend logic of the plugin.
 */
class RAD_Ad_Rotator {
    /**
     * The ad script (HTML/JS) that has been selected for display on the current page load.
     * Null if no ad should be displayed (e.g., user logged in, bot detected, session exhausted).
     * @var string|null
     */
    public static $ad_script_to_show = null;

    /**
     * Stores plugin options fetched from the database.
     * Includes 'ad_scripts' (raw string), 'views_before_rotation' (integer),
     * and 'parsed_ad_scripts' (array of trimmed script strings).
     * @var array
     */
    public static $options = [];

    /**
     * Initialize the ad rotator.
     * This static method is the main entry point for the class's functionality.
     * It loads options, checks various conditions (logged-in status, bots), processes ad logic,
     * and hooks the script injection if an ad is selected.
     * Hooked to 'wp_loaded' to ensure all WP functions, user status, and theme are available.
     */
    public static function init() {
        // Load options from the database. Default values are used if options not yet saved.
        self::$options['ad_scripts'] = get_option('rad_ad_scripts', '');
        self::$options['views_before_rotation'] = get_option('rad_views_before_rotation', 5);

        // Parse the ad scripts string into an array of individual scripts.
        $scripts_string = self::$options['ad_scripts'];
        // 1. Explode string by newline. 2. Trim whitespace from each script. 3. Filter out any empty lines.
        $raw_scripts = explode("\n", $scripts_string);
        $trimmed_scripts = array_map('trim', $raw_scripts);
        $parsed_scripts = array_filter($trimmed_scripts);
        // Re-index the array to ensure keys are sequential (0, 1, 2...).
        self::$options['parsed_ad_scripts'] = array_values($parsed_scripts);

        // Core conditions for not displaying ads:
        // 1. No ad scripts are configured.
        // 2. The user is currently logged in.
        if (empty(self::$options['parsed_ad_scripts']) || is_user_logged_in()) {
            return; // Exit early if no ads to show or user is logged in.
        }

        // Attempt to start a PHP session if one doesn't exist. Required for tracking views per session.
        self::maybe_start_session();

        // Perform a check for common bot user agents. If detected, do not show ads.
        if (self::perform_bot_check()) {
            return; // Exit early if a bot is detected.
        }

        // Process the main logic for ad selection, view counting, and rotation.
        self::process_ad_logic();

        // If an ad script has been selected by process_ad_logic(), hook its injection into wp_head.
        // Priority 1 ensures it fires early in the head.
        if (self::$ad_script_to_show) {
            add_action('wp_head', ['RAD_Ad_Rotator', 'inject_ad_script'], 1);
        }
    }

    /**
     * Injects the selected ad script into the page (via wp_head action).
     * This method is called by the 'wp_head' action if an ad script is determined to be shown.
     */
    public static function inject_ad_script() {
        if (!empty(self::$ad_script_to_show)) {
            // Ad scripts are expected to be full HTML/JS (e.g., <script>...</script>).
            // These scripts are supplied by an administrator with 'manage_options' capability.
            // Direct output is used here. A `phpcs:ignore` is used to acknowledge this,
            // as typical escaping functions (esc_html, esc_js) would break the script.
            // Adding HTML comments around the script for easier identification in the page source.
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "\n<!-- Rotating Ads Deluxe Script Start -->\n";
            echo self::$ad_script_to_show;
            echo "\n<!-- Rotating Ads Deluxe Script End -->\n";
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    /**
     * Start session if not already started and headers haven't been sent.
     * This is necessary for tracking ad views and rotation per user session.
     * It checks `session_status()` and `headers_sent()` to avoid errors/warnings.
     */
    private static function maybe_start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // session_start() can sometimes fail or issue warnings if output has already started,
            // or if session configurations on the server are problematic.
            // Using @ to suppress errors if session_start fails, though ideally server config should be correct.
            @session_start();
        }
    }

    /**
     * Check if the current visitor is a known bot based on its User-Agent string.
     * This is a basic check and may not catch all bots, but covers common ones.
     *
     * @return bool True if a bot keyword is found in the User-Agent, false otherwise.
     */
    private static function perform_bot_check() {
        // Retrieve and sanitize User-Agent string. strtolower for case-insensitive matching.
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(trim($_SERVER['HTTP_USER_AGENT'])) : '';
        if (empty($user_agent)) {
            return false; // Cannot determine User-Agent, assume not a bot to be safe (ads will show).
        }

        // List of common keywords found in bot User-Agent strings. Expanded for better coverage.
        $bot_keywords = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners-google', // Common generic and Google
            'googlebot', 'bingbot', 'yahoo! slurp', 'duckduckbot', 'baiduspider', // Major search engines
            'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver', 'ahrefsbot', // Other crawlers
            'semrushbot', 'megaindex', 'mj12bot', 'dotbot', 'lipperhey', // SEO and other utility bots
            'serpstat', 'seokicks', 'nutch', 'lighthouse' // More crawlers and auditing tools
        ];

        foreach ($bot_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true; // Bot detected
            }
        }
        return false; // Not identified as a bot
    }

    /**
     * Process the core ad rotation logic.
     * This method manages session data for ad views, current ad index, and daily reset.
     * It determines which ad script (if any) from the `parsed_ad_scripts` array should be shown
     * based on view counts and rotation rules defined in plugin settings.
     */
    private static function process_ad_logic() {
        // Ensure session is active before trying to use $_SESSION variables.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // This might happen if session_start() failed (e.g., headers already sent elsewhere, or server misconfig).
            // In this case, ad rotation based on session won't work correctly.
            return;
        }

        // Retrieve our plugin's session data. If it doesn't exist or isn't an array,
        // initialize with defaults to prevent errors.
        $rad_data = isset($_SESSION['rad_data']) && is_array($_SESSION['rad_data']) ? $_SESSION['rad_data'] : [];

        // Define default structure for our session data to avoid undefined index errors.
        $defaults = [
            'current_ad_list_index'        => 0,     // Index in the parsed_ad_scripts array for the current ad.
            'views_for_current_ad_in_list' => 0,     // How many times the ad at current_ad_list_index has been "viewed".
            'session_exhausted'            => false, // True if all ads shown their max times for this session/day.
            'last_daily_reset_day'         => ''     // Stores 'Y-m-d' to track when the daily reset last occurred.
        ];
        // Merge defaults with existing session data. Existing values override defaults.
        $_SESSION['rad_data'] = array_merge($defaults, $rad_data);

        // --- Daily Reset Logic ---
        // Get current date using WordPress's timezone settings for consistency across users/servers.
        $current_date_string = wp_date('Y-m-d'); // Format: YYYY-MM-DD
        if ($current_date_string !== $_SESSION['rad_data']['last_daily_reset_day']) {
            // It's a new day (or the first run for this session), so reset session counters for ad rotation.
            $_SESSION['rad_data']['current_ad_list_index'] = 0;
            $_SESSION['rad_data']['views_for_current_ad_in_list'] = 0;
            $_SESSION['rad_data']['session_exhausted'] = false;
            // Update the last reset day to today.
            $_SESSION['rad_data']['last_daily_reset_day'] = $current_date_string;
        }

        // If session is marked as exhausted (all ads shown their quota for the day/session), do nothing further.
        if ($_SESSION['rad_data']['session_exhausted']) {
            self::$ad_script_to_show = null; // Ensure no ad is marked for showing.
            return;
        }

        // This check is also in init(), but as a safeguard within this method.
        if (empty(self::$options['parsed_ad_scripts'])) {
            self::$ad_script_to_show = null; // No ads available.
            return;
        }

        $max_views_per_ad = (int)self::$options['views_before_rotation'];
        // Ensure max_views_per_ad is at least 1 to prevent issues like division by zero or infinite loops.
        if ($max_views_per_ad <= 0) {
            $max_views_per_ad = 1;
        }
        
        $number_of_available_ads = count(self::$options['parsed_ad_scripts']);

        // Increment views for the ad currently supposed to be shown.
        // This happens on each page load where an ad *could* be shown (i.e., not logged in, not a bot).
        $_SESSION['rad_data']['views_for_current_ad_in_list']++;
        
        // Check if the current ad has reached its view limit for this rotation cycle.
        if ($_SESSION['rad_data']['views_for_current_ad_in_list'] > $max_views_per_ad) {
            // Current ad's quota met. Time to move to the next ad in the list.
            $_SESSION['rad_data']['current_ad_list_index']++;
            // Reset view counter for the new ad. This is its first "technical" view in this cycle.
            $_SESSION['rad_data']['views_for_current_ad_in_list'] = 1;
        }

        // Check if we've cycled through all available ads.
        if ($_SESSION['rad_data']['current_ad_list_index'] >= $number_of_available_ads) {
            // All ads have been shown their allotted times for this session/day.
            $_SESSION['rad_data']['session_exhausted'] = true;
            self::$ad_script_to_show = null; // Ensure no ad is assigned to be shown.
            return;
        }
        
        // Assign the selected ad script to the static property for injection.
        // This check ensures the index is valid. While previous logic should prevent out-of-bounds,
        // it's a good safeguard before accessing an array element.
        if (isset(self::$options['parsed_ad_scripts'][$_SESSION['rad_data']['current_ad_list_index']])) {
            self::$ad_script_to_show = self::$options['parsed_ad_scripts'][$_SESSION['rad_data']['current_ad_list_index']];
        } else {
            // Fallback: if index is somehow invalid (e.g., ads were removed mid-session but count didn't update),
            // mark session exhausted to prevent errors or unexpected behavior.
            $_SESSION['rad_data']['session_exhausted'] = true;
            self::$ad_script_to_show = null;
        }
    }
}

// Hook the main initialization method of the ad rotator to 'wp_loaded'.
// 'wp_loaded' fires after WordPress is fully loaded, all plugins are loaded, and the theme is initialized.
// It's a suitable hook for logic that might involve options, user status, or starting sessions (before headers are sent).
add_action('wp_loaded', ['RAD_Ad_Rotator', 'init']);

// Note on Internationalization (i18n):
// Admin UI strings use WordPress i18n functions like __() and esc_html__().
// The 'rotating-ads-deluxe' text domain is defined in the plugin header.
// A /languages folder is specified for .mo/.po files.

/**
 * =====================================================================================
 * TESTING CHECKLIST for Rotating Ads Deluxe
 * =====================================================================================
 *
 * This checklist is intended to guide manual testing of the plugin's functionality.
 *
 * ---
 * 1. Initial Setup & Configuration:
 * ---
 *   a. Fresh Install:
 *      - Install and activate the 'Rotating Ads Deluxe' plugin.
 *      - Expected: Plugin activates without errors. 'Rotating Ads' submenu appears under 'Settings'.
 *   b. Access Settings Page:
 *      - Navigate to Settings > Rotating Ads.
 *      - Expected: Settings page loads with "Ad Rotation Settings", "Ad Scripts" textarea, and "Views Per Ad Before Rotation" number input. Default views should be 5.
 *   c. Configure Ads:
 *      - In "Ad Scripts", add 3-4 unique ad scripts. For testing, simple console logs are good:
 *          <script>console.log('Ad 1 - View %view_count% from session');</script>
 *          <script>console.log('Ad 2 - View %view_count% from session');</script>
 *          <script>console.log('Ad 3 - View %view_count% from session');</script>
 *        (Note: %view_count% is a mental placeholder for the tester to track, not actual plugin functionality that replaces this string.)
 *      - Set "Views Per Ad Before Rotation" to 3 (or a low number for faster testing).
 *      - Click "Save Settings".
 *      - Expected: Settings save. Page reloads with saved values.
 *
 * ---
 * 2. Non-Logged-In User Experience (Primary Test Case):
 * ---
 *   a. Preparation:
 *      - Log out of WordPress.
 *      - Open your website in a new browser, incognito/private window, or a browser where you are not logged in (to ensure a clean session).
 *   b. Ad Rotation & View Counting:
 *      - Page 1 Visit: Navigate to any page on your site.
 *        - Expected: Open browser console (F12 or Right-click > Inspect > Console). You should see "Ad 1 - View 1 from session" (or your equivalent script).
 *      - Page 2 Visit: Navigate to a different page (or refresh).
 *        - Expected: Console shows "Ad 1 - View 2 from session".
 *      - Page 3 Visit: Navigate/Refresh.
 *        - Expected: Console shows "Ad 1 - View 3 from session". (This ad has now met its view quota for this cycle).
 *      - Page 4 Visit: Navigate/Refresh.
 *        - Expected: Console shows "Ad 2 - View 1 from session". (Rotation to the next ad).
 *      - Continue Pattern: Repeat navigation/refreshing, expecting:
 *          - "Ad 2 - View 2 from session"
 *          - "Ad 2 - View 3 from session"
 *          - "Ad 3 - View 1 from session"
 *          - "Ad 3 - View 2 from session"
 *          - "Ad 3 - View 3 from session"
 *   c. Session Exhaustion:
 *      - After the last ad ("Ad 3") has shown for its 3rd view:
 *      - Page X Visit: Navigate/Refresh.
 *        - Expected: No new ad script messages appear in the console. The session is "exhausted" for ads.
 *   d. Verify Script Injection & Placement:
 *      - On a page where an ad is expected (e.g., "Ad 1 - View 1 from session" is in the console):
 *      - View page source (Ctrl+U or Right-click > View Page Source).
 *      - Search for `<!-- Rotating Ads Deluxe Script Start -->`.
 *      - Expected:
 *          - The comment and the subsequent ad script (e.g., `<script>console.log('Ad 1 - View 1 from session');</script>`) are found.
 *          - This entire block (comment + script + end comment) is located *within* the `<head>...</head>` section of the HTML, not in the `<body>`.
 *
 * ---
 * 3. Daily Reset Functionality:
 * ---
 *   a. Simulation (Session Clearing - Easiest to Test):
 *      - While still in the non-logged-in browser where the session was exhausted:
 *      - Close the incognito/private browser window completely and reopen it. (This clears session cookies for most browsers).
 *      - OR, manually clear cookies for your site in the browser settings.
 *      - Navigate to a page on your site.
 *      - Expected: Ad rotation starts again from "Ad 1 - View 1 from session". This simulates a new user session, and also what happens for a returning user after the daily reset time has passed.
 *   b. Actual Daily Reset (More Complex to Test):
 *      - Explain: The plugin is designed to reset `current_ad_list_index`, `views_for_current_ad_in_list`, and `session_exhausted` status when the date changes (based on WordPress's configured timezone - see `wp_date('Y-m-d')`).
 *      - To test this directly without waiting:
 *          1. Have an active session (e.g., partially through ad rotation, or session exhausted).
 *          2. If possible, change the server's system date forward by one day (requires server access, often impractical).
 *          3. OR, if WordPress timezone is manually set to UTC, you might be able to test by aligning your local time with just before/after midnight UTC.
 *          4. Upon the next page load after the date (as perceived by `wp_date()`) changes, the ad rotation should restart from "Ad 1 - View 1 from session", even if the PHP session cookie itself is the same.
 *
 * ---
 * 4. Logged-In User Experience:
 * ---
 *   a. Log in to WordPress as any user (admin, editor, subscriber, etc.).
 *   b. Navigate to various pages on the frontend of the site.
 *   c. Check the browser console and page source.
 *   d. Expected: No ad scripts from this plugin should appear in the console or page source. Ads are exclusively for non-logged-in users.
 *
 * ---
 * 5. Bot Filtering:
 * ---
 *   a. Explain: The plugin includes a basic bot filter by checking for common keywords in the User-Agent string.
 *   b. Simulation (Using Browser Developer Tools):
 *      - Open browser developer tools (F12).
 *      - Go to the "Network Conditions" tab (in Chrome, may vary by browser) or a similar feature that allows User-Agent overriding.
 *      - Uncheck "Select automatically" and choose "Custom..." or enter a custom User-Agent string that includes a known bot keyword, e.g., "MyTestBrowser Googlebot/2.1".
 *      - As a non-logged-in user (new incognito window recommended), navigate pages on your site.
 *      - Expected: No ad scripts should appear in the console or page source, as the request should be identified as a bot.
 *      - Remember to revert User-Agent settings in developer tools after testing.
 *
 * ---
 * 6. Admin Settings & Behavior Changes:
 * ---
 *   a. Change "Views Per Ad":
 *      - Go to Settings > Rotating Ads.
 *      - Change "Views Per Ad Before Rotation" to 1. Click "Save Settings".
 *      - Retest as a non-logged-in user (clear session first by closing/reopening incognito window).
 *      - Expected: Each ad should now only show once before rotating to the next. ("Ad 1 - View 1", then "Ad 2 - View 1", etc.).
 *   b. Empty Ad Scripts:
 *      - Go to Settings > Rotating Ads.
 *      - Delete all scripts from the "Ad Scripts" textarea. Click "Save Settings".
 *      - Test as a non-logged-in user.
 *      - Expected: No ad scripts should appear on any page. `RAD_Ad_Rotator::init()` should return early.
 *   c. Single Ad Script:
 *      - Add only one ad script (e.g., `<script>console.log('Single Ad - View %view_count%');</script>`).
 *      - Set "Views Per Ad Before Rotation" to 2. Click "Save Settings".
 *      - Test as non-logged-in user (clear session).
 *      - Expected: "Single Ad - View 1", then on next page "Single Ad - View 2". On the third page load, no ad should appear (session exhausted as there's only one ad and it has met its view quota).
 *
 * ---
 * 7. Caching Compatibility (Conceptual Overview):
 * ---
 *   a. Explain: If a page caching plugin (e.g., WP Rocket, LiteSpeed Cache, WP Super Cache) is active, it can affect dynamic content.
 *   b. Observation:
 *      - With a caching plugin active, test the ad rotation for non-logged-in users.
 *      - Expected Behavior: The current design injects scripts via `wp_head` and relies on PHP sessions. PHP sessions are generally incompatible with full page caching for non-logged-in users unless the caching plugin has a specific mechanism to handle them (e.g., cache per session, or bypass cache if session cookie detected).
 *      - If ads are cached: You might see the same ad repeatedly despite navigation, or an ad might not appear when it should.
 *      - Mitigation: For sites with aggressive caching for non-logged-in users, this plugin's session-based rotation might not work as expected without specific cache configuration (e.g., excluding pages from cache, or using AJAX to fetch ad info - which is beyond current scope). The bot check and logged-in check should still work.
 *
 * ---
 * 8. Plugin Deactivation/Reactivation & Option Persistence:
 * ---
 *   a. Deactivate: In the WordPress admin, deactivate the 'Rotating Ads Deluxe' plugin.
 *      - Expected: No ads show on the frontend. Settings (`rad_ad_scripts`, `rad_views_before_rotation`) should remain in the `wp_options` table in the database.
 *   b. Reactivate: Reactivate the plugin.
 *      - Expected: Ads should function as previously configured (using the saved settings). The default "Views Per Ad" (5) set in `rad_activate()` should NOT override an existing saved value.
 *
 * =====================================================================================
 */
?>
