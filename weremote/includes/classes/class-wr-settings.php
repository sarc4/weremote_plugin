<?php
/**
 * Class WR_Settings
 *
 * Description: This class handles the plugin's settings.
 * 
 * @package We_Remote
 */

if ( ! class_exists( 'WR_Settings' ) ) {
    class WR_Settings {
    
        function __construct() {
            $this->register();
        }
    
        /**
         * Register activation hooks
        */
		public function register() {
            register_setting( 'linkvalidator_settings_group', 'linkvalidator_on_save_post_enable' );
            register_setting( 'linkvalidator_settings_group', 'linkvalidator_cron_enable' );
            register_setting( 'linkvalidator_settings_group', 'wr_endpoint_access_token' );
		}

        public function init_settings() {
            
            // Link Validator Settings Section
            add_settings_section(
                'linkvalidator_section',
                'Link Validator',
                '',
                'linkvalidator_settings_page'
            );
            
            // WR Endpoint Settings Section
            add_settings_section(
                'we_endpoint_section',
                'WR Endpoint',
                '',
                'linkvalidator_settings_page'
            );
        
        }

        function linkvalidator_settings_section_callback() {
            ?>
            <div class="settings-section-description">
                <p class="settings-section-text">
                    <?php esc_html_e('Configurable options for Link Validator', 'your-text-domain'); ?>
                </p>
            </div>
            <?php
        }

        function wr_endpoint_settings_section_callback() {
            ?>
            <div class="settings-section-description">
                <p class="settings-section-text">
                    <?php esc_html_e('Configurable options for Link Validator', 'your-text-domain'); ?>
                </p>
            </div>
            <?php
        }

        function init_fields() {

            // Link Validator "Save Post" Settings Field
            add_settings_field(
                'linkvalidator_enable_field',
                'Validate on Save Post',
                array( 'WR_Settings', 'linkvalidator_enable_field' ),
                'linkvalidator_settings_page',
                'linkvalidator_section'
            );
            
            // Link Validator "Cron" Settings Field
            add_settings_field(
                'linkvalidator_cron_field',
                'Validate Cron',
                array( 'WR_Settings', 'linkvalidator_cron_field' ),
                'linkvalidator_settings_page',
                'linkvalidator_section'
            );

            // WR Endpoint "Access Token" Settings Field
            add_settings_field(
                'wr_endpoint_access_token_field',
                'Access Token',
                array( 'WR_Settings', 'wr_endpoint_access_token_field' ),
                'linkvalidator_settings_page',
                'we_endpoint_section'
            );
        }
    
        /**
         * Callback function for the Settings page.
        */
        function settings_page() {
            ?>
            <div class="wrap">
                <h1>WR Settings</h1>
                <form action="options.php" method="post">
                    <?php
                        settings_fields('linkvalidator_settings_group');
                        do_settings_sections('linkvalidator_settings_page');
                        submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        function linkvalidator_enable_field() {
            $value = get_option('linkvalidator_on_save_post_enable', 'disable');
            ?>
            <select name="linkvalidator_on_save_post_enable" class="regular-text">
                <option value="enable" <?php selected($value, 'enable'); ?>><?php esc_html_e('Enable', 'your-text-domain'); ?></option>
                <option value="disable" <?php selected($value, 'disable'); ?>><?php esc_html_e('Disable', 'your-text-domain'); ?></option>
            </select>
            <?php
        }

        function linkvalidator_cron_field() {
            $value = get_option('linkvalidator_cron_enable', 'disable');
            ?>
            <select name="linkvalidator_cron_enable" class="regular-text">
                <option value="enable" <?php selected($value, 'enable'); ?>><?php esc_html_e('Enable', 'your-text-domain'); ?></option>
                <option value="disable" <?php selected($value, 'disable'); ?>><?php esc_html_e('Disable', 'your-text-domain'); ?></option>
            </select>
            <?php
        }

        function wr_endpoint_access_token_field() {
            $value = get_option('wr_endpoint_access_token', '');
            ?>
            <input type="text" name="wr_endpoint_access_token" id="wr_endpoint_access_token" value="<?php echo esc_attr($value); ?>" class="regular-text">
            <?php
        }
    }

    $wr_settings = new WR_Settings();
 }

?>