<?php

/**
 * Class LinkValidator
 *
 * Description: This class handles the link validation.
 * 
 * @package We_Remote
 */

 if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not permitted.' );
}

if ( ! class_exists( 'LinkValidator' ) ) {

    class LinkValidator {

        /**
         * Class constructor.
         */
        function __construct() {
            $this->register();
            $this->define_actions();
            $this->set_cron();
        }

        /**
		 * Registers the class activation hooks.
		 */
		public function register() {
			register_activation_hook( __FILE__, array( $this, 'create_linkvalidator_table' ) );
		}

        /**
		 * Defines the class actions and hooks.
		 */
        function define_actions() {
            add_action('wp_ajax_validate_links', array( $this, 'validate_links' ) );
            add_action('wp_ajax_clear_table_ajax', array( $this, 'clear_table_ajax' ) );
            add_action('save_post_post', array( $this, 'validate_links_on_save' ) );

            // Link Validator Cron
            if( get_option('linkvalidator_cron_enable') === 'enable' ) {
                add_filter('cron_schedules', array( $this, 'linkvalidator_schedules' ) );
                add_action('linkvalidator_schedules', array( $this, 'validate_links' ) );
            }
        }
        
        /**
		 * Creates the linkvalidator table in the WordPress database if it doesn't exist.
		 */ 
        function create_linkvalidator_table() {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'linkvalidator';
        
            $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

            $charset_collate = $wpdb->get_charset_collate();

            // Create only if not exist
            if ( ! $wpdb->get_var( $query ) == $table_name ) {

                $sql = "CREATE TABLE $table_name (
                    id INT NOT NULL AUTO_INCREMENT,
                    post_id INT,
                    link VARCHAR(255),
                    status VARCHAR(50),
                    revision DATETIME,
                    PRIMARY KEY (id)
                ) $charset_collate;";

                $wpdb->query( $sql );
            }
        
        }

        /**
         * Displays the Link Validator page.
         */
        function link_validator_page() {

            // Get all table links
            $links = self::get_table_links();
            
            // Group if a link has more than one origin
            if( !empty( $links ) ) {
                $links = self::group_origins( $links );
            }

            ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline">Link Validator Page</h1>
                    <ul class="description">
                        <li>Unsafe link ( <code>href="http://..."</code> )</li>
                        <li>Unspecified protocol ( <code>href="google.com"</code> or <code>href="//google.com"</code> )</li>
                        <li>Malformed link ( <code>href="https://..."</code> or <code>href="https://google.com/Url that doesn't work"</code> )</li>
                        <li>Link that returns an incorrect Status Code ( <code>40X, 50X</code> )</li>
                    </ul>
                    <div style="display: flex; justify-content: flex-start">
                        <button id="validate-links-btn" class="button button-primary">Validate Links</button>
                        <span class="spinner is-active" style="display: none;"></span>
                    </div>

                    <!-- Display table -->
                    <?php self::display_links_table( $links ); ?>
                            
                </div>
            <?php
        }

        /**
         * Retrieves all links from the linkvalidator table.
         * 
         * @return $links - Array of links with their respective post_id, link, status, and revision.
         * 
         */
        function get_table_links() {

            global $wpdb;
            $table_name = $wpdb->prefix . 'linkvalidator';
        
            $query   = "SELECT post_id, link, status, revision FROM $table_name";
            $results = $wpdb->get_results( $query, ARRAY_A );
        
            $links = array();

            foreach ( $results as $row ) {
                $links[] = array(
                    'post_id'   => $row['post_id'],
                    'link'      => $row['link'],
                    'status'    => $row['status'],
                    'revision'  => $row['revision'],
                );
            }
        
            return $links;
        }

        /**
         * Groups links by their URL origin.
         * 
         * @param array $links Array of links with their respective post_id, link, status, and revision.
         * 
         * @return array $grouped_links - Array of links grouped by URL origin.
         */
        static function group_origins( $links ) {
            $grouped_links = array();
        
            foreach ( $links as $link ) {
                $url = $link['link'];
                $origin = '<a href="' . get_edit_post_link( $link['post_id'] ) . '">' . get_post( $link['post_id'] )->post_title . '</a>';
        
                if ( isset( $grouped_links[ $url ] ) ) {
                    $grouped_links[ $url ]['origin'] .= ', ' . $origin;
                } else {
                    $grouped_links[ $url ] = array(
                        'post_id'   => $link['post_id'],
                        'link'      => $url,
                        'status'    => $link['status'],
                        'revision'  => $link['revision'],
                        'origin'    => $origin,
                    );
                }
            }
        
            return array_values( $grouped_links );
        }
        
        /**
         * Displays a table of wrong post links.
         * 
         * @param array $links - An array of links to display in the table.
         * 
         * @return void
         */
        static function display_links_table( $links ) {
            ?>
            <div class="wrap">
                <table class="wp-list-table widefat fixed striped linkvalidator-table">
                    <thead>
                        <h1>List of all wrong posts links</h1>
                        <tr>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Origin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $links as $link_item ) : ?>
                            <?php
                                $link           = $link_item['link'];
                                $status         = $link_item['status'];
                                $origin         = $link_item['origin'];
                                
                                $status_color   = ($status === '404 Not Found') ? 'color-danger' : 'color-warning';
                                $status_icon    = ($status === '404 Not Found') ? 'table-icon icon-danger color-danger' : 'table-icon icon-warning color-warning';
                                $status_title    = ($status === '403 Forbidden') ? 'You do not have permission to access this resource' : '';
                                ?>
                            <tr>
                                <td>
                                    <i class="<?php echo $status_icon; ?> fa fa-exclamation-triangle"></i>
                                    <a href="<?php echo $link ?>" target="_blank"><?php echo $link ?></a>
                                </td>
                                <td><strong class="<?php echo $status_color; ?>" title="<?php echo $status_title ?>"><?php echo $status; ?></strong></td>
                                <td><strong><?php echo $origin; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php $display_button = empty( $links ) ? 'none': 'flex'; ?>
            
            <!-- Display "Clear Table" button -->
            <div style="display: <?php echo $display_button ?>; justify-content: flex-start; margin-top: 1rem;">
                <button id="clear-table-btn" class="button button-primary">Clear Table</button>
                <span class="spinner-clear is-active spinner-clear" style="display: none;"></span>
            </div>

            <?php

        }

        /**
         * Validates links rechecking the links table, retrieving links from all posts, and identifying wrong links.
         * 
         * This function runs by cron or mannually.
         * 
         * @return void
         */
        function validate_links() {
            $table_links = $this->recheck_links_table();
            $all_links   = $this->get_links_from_posts( $table_links );
            $bad_links   = $this->get_bad_links( $all_links );

            if( count( $bad_links ) > 0 ) {
                // Save links
                $new_links_count = $this->save_links( $bad_links );

                // Group to display in table
                $links = $this->group_origins( $bad_links );
                
                // Return message
                if( $links > 0 ) {
                    echo json_encode(
                        array(
                            'status'            => 'ADDED',
                            'message'           => 'All links added!',
                            'links'             => $links,
                            'new_links_count'   => $new_links_count,
                        )
                    );
                } else {
                    echo json_encode(
                        array(
                            'status'            => 'NO_ADDED',
                            'message'           => 'No one link added!',
                            'new_links_count'   => 0,
                        )
                    );
                }

            } else {
                echo json_encode(
                    array(
                        'status'            => 'NO_NEW_LINKS',
                        'message'           => 'No new links detected',
                        'new_links_count'   => 0,
                    )
                );
            }
                    
            wp_die();
        }

        /**
         * Validates links on post save hook.
         * 
         * @param int $post_id The ID of the post being saved.
         * 
         * @return void
         */
        function validate_links_on_save( $post_id ) {

            if( check_is_draft( $post_id ) ) {
                return;
            }

            if( get_option( 'linkvalidator_on_save_post_enable' ) == 'enable' ) {
                $post           = get_post($post_id);
                $post_content   = $post->post_content;

                // Usar expresión regular para buscar los enlaces en el contenido del post
                preg_match_all('/<a\s[^>]*href=["\'](.*?)["\'][^>]*>/', $post_content, $matches);
        
                // Obtener los enlaces encontrados y agregarlos al arreglo de resultados junto con el ID del post
                foreach ($matches[1] as $link) {

                    if( !empty($link) ) {
                        $links[] = array(
                            'post_id' => $post_id,
                            'link' => $link,
                        );
                    }
                }

                $bad_links = $this->get_bad_links( $links );
                $this->save_links( $bad_links, $post_id );

            }

        }

        /**
         * Saves the bad links to the linkvalidator table.
         * 
         * @param array $bad_links - An array of bad links.
         * 
         * @param int|null $save_post_id - The ID of the post being saved. Optional.
         * 
         * @return int $new_links_count - The number of new links saved.
         */
        function save_links( $bad_links, $save_post_id = null ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'linkvalidator';

            // On save_post hook, first delete all links, then save.
            if( !is_null( $save_post_id ) ) {
                $this->delete_links( $save_post_id );
            }

            $new_links_count = 0;
        
            foreach ( $bad_links as $link ) {
                $post_id    = $link['post_id'];
                $url        = $link['link'];
                $status     = $link['status'];
                $revision   = $link['revision'];
        
                $existing = $this->link_exist_in_table( $post_id, $url );
        
                if ( !$existing ) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'post_id' => $post_id,
                            'link' => $url,
                            'status' => $status,
                            'revision' => $revision,
                        ),
                        array(
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                        )
                    );
        
                    if ( $wpdb->insert_id ) {
                        $new_links_count++;
                    }

                }
            }
        
            return $new_links_count;
        }
        
        /**
         * Checks if a link already exists in the linkvalidator table for a specific post.
         * 
         * @param int $post_id - The ID of the post.
         * 
         * @param string $link - The link to check.
         * 
         * @return bool True if the link exists, false otherwise.
         */
        function link_exist_in_table( $post_id, $link ) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'linkvalidator';
            
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE link = %s AND post_id = %d",
                $link,
                $post_id
            );
            
            $count = $wpdb->get_var($query);
            
            return $count > 0;
        }

        /**
         * Rechecks the links revision date in the linkvalidator table.
         * 
         * @return array $remaining_post_ids - An array of remaining post IDs in the linkvalidator table.
         */
        function recheck_links_table() {

            global $wpdb;
            $table_name = $wpdb->prefix . 'linkvalidator';
        
            // Get all links with revision date > 4 days
            $query = $wpdb->prepare(
                "SELECT DISTINCT post_id FROM $table_name WHERE DATE_ADD(revision, INTERVAL 4 DAY) < CURDATE();"
            );
            $old_post_ids = $wpdb->get_col($query);
        
            // If a link has a revision > 4 days, it will be removed
            if ( !empty( $old_post_ids ) ) {
                foreach ( $old_post_ids as $post_id ) {
                    $this->delete_links( $post_id );
                }
            }
        
            // Get all remainings posts ids in table
            $query = $wpdb->prepare(
                "SELECT DISTINCT post_id FROM $table_name"
            );
            $remaining_post_ids = $wpdb->get_col($query);
        
            // Return without repeating
            return array_unique($remaining_post_ids);
        }

        /**
         * Get links from post_content.
         * 
         * @param array $table_links - All posts id that are already in the linkvalidator table.
         * 
         * @return array $links - All links and post id, from all posts that are not already in linkvalidator table.  
         */
        function get_links_from_posts( $table_links ) {
            
            // If they are already in our table, they will not be taken into account.
            $args = array(
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'post__not_in' => $table_links,
            );
        
            $posts = get_posts( $args );
            $links = array();
        
            foreach ( $posts as $post ) {
                
                $post_content   = $post->post_content;
                $post_id        = $post->ID;
        
                // Use regular expression to find links in post content
                preg_match_all('/<a\s[^>]*href=["\'](.*?)["\'][^>]*>/', $post_content, $matches);
        
                // If a link is detected, add the link and the post id
                foreach ( $matches[1] as $link ) {
                    if( !empty( $link ) ) {
                        $links[] = array(
                            'post_id' => $post_id,
                            'link'    => $link,
                        );

                    }
                }
            }

            return $links;
        }
      
        /**
         * Get all wrong links
         *
         * @param array $links - All posts links.
         *
         * @return array $bad_links - All posts wrong links. (post_id, url, status, revision)
        */
        function get_bad_links( $links ) {
            $bad_links = array();
        
            foreach ( $links as $link ) {
                $post_id  = $link['post_id'];
                $url      = $link['link'];
                $status   = '';
                $revision = date('Y-m-d H:i:s');
        
                switch (true) {
                    // Unsafe link
                    case ( strpos($url, 'http://' ) === 0 ):
                        $status = 'Unsafe link';
                        break;
                        
                    // Unspecified protocol
                    case ( strpos($url, 'https://' ) === false && strpos($url, '//') === false):
                        $status = 'Unspecified protocol';
                        break;
                        
                    // Unspecified protocol
                    case (!filter_var($url, FILTER_VALIDATE_URL)):
                        $status = 'Malformed link';
                        break;

                    // Get http url status
                    default:
                        $status = get_url_status( $url );
                        break;
                }
        
                // Status set, and is different than 200
                if ( !empty( $status ) && $status != "200 OK" && !empty( $url ) ) {
                    $bad_links[] = array(
                        'post_id'   => $post_id,
                        'link'      => $url,
                        'status'    => $status,
                        'revision'  => $revision,
                    );
                }
            }
        
            return $bad_links;
        }

        /**
         * Clear all linkvalidator table values
         * 
         * @return boolean $status - wpdb query status
         * 
         */
        private function clear_table() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'linkvalidator';
            $status = true;

            $wpdb->query("TRUNCATE TABLE $table_name");

            if ( $wpdb->last_error ) {
                $status = false;
            }
            
            return $status;
        }

        /**
         * Handles the AJAX request to clear the linkvalidator table.
         * 
         * @return void
         */
        function clear_table_ajax() {
        
            $result = $this->clear_table();
        
            if ( $result ) {
                echo json_encode(
                    array(
                        'status'    => 'OK',
                        'message'   => 'All urls deleted from table',
                    )
                );
            } else {
                echo json_encode(
                    array(
                        'status'    => 'Fail',
                        'message'   => 'Error deleting',
                    )
                );
            }
        
            wp_die();
        }

        /**
         * Deletes links associated with a specific post ID from the linkvalidator table.
         * 
         * @param int $post_id - The ID of the post. 
         * 
         * @return void
         */
        function delete_links( $post_id ) {
            global $wpdb;
        
            $table_name = $wpdb->prefix . 'linkvalidator';
        
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table_name WHERE post_id = %d",
                    $post_id
                )
            );
        }

        /**
         * Customizes the list of available schedules for the Link Validator.
         *
         * @param array $schedules - The existing list of schedules.
         *
         * @return array $schedules - The updated list of schedules.
         */
        function linkvalidator_schedules( $schedules ) {

            if ( !isset( $schedules["linkvalidator_hourly"] ) ) {
                $schedules["linkvalidator_hourly"] = array(
                    'interval' => 60 * 60,
                    'display'  => __('Hourly')
                );
            }

            return $schedules;
        }
        
        /**
         * Sets up the cron job for the Link Validator.
         */
        function set_cron() {
            if( get_option('linkvalidator_cron_enable') === 'enable' ) {               
                if ( !wp_next_scheduled('linkvalidator_schedules') ) {
                    wp_schedule_event( time(), 'linkvalidator_hourly', 'linkvalidator_schedules' );
                }
            }
        }
    }

    $linkValidator = new LinkValidator();
}
