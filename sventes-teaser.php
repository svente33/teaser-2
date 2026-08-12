<?php
/**
 * Plugin Name:       ITN Teaser Carousel
 * Plugin URI:        https://itn-ol.de
 * Description:       Ein responsives Teaser-Carousel mit mehreren Teasermodulen und Gutenberg-Integration. Die Teaser können auch aus Beiträgen bestehen. Das Plugin darf nur mit ausdrücklicher Genehmigung des Autors weiterverbreitet oder verändert werden.
 * Version:           2.6.0
 * Requires at least: 7.0
 * Requires PHP:      8.0
 * Author:            IT & Netsolutions - Svente
 * Author URI:        https://itn-ol.de
 * Text Domain:       itn-teaser-carousel
 * Domain Path:       /languages
 *
 * @package ITN_Secure
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sventes_Teaser_Plugin {
    private const ACF_VISIBLE_MAX = 5;

    private $option_name      = 'sventes_teaser_data';
    private $legacy_shortcode = 'sventes_teaser';
    private $shortcode        = 'itn_teaser';
    private $menu_slug        = 'itn-teaser';
    private $parent_menu_slug = 'itn-modules';
    private $capability       = 'manage_itn_teaser';
    private $default_set_id   = 'default';
    private $version          = '1.5.0';
    private $max_auto_posts   = 50;
    private $page_hook_suffix = '';

    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'ensure_role_caps' ) );
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_post' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

        add_shortcode( $this->shortcode, array( $this, 'render_shortcode' ) );
        add_shortcode( $this->legacy_shortcode, array( $this, 'render_shortcode' ) );
    }

    public function activate() {
        $data = $this->get_data();
        update_option( $this->option_name, $data );
        $this->sync_role_caps( $data['allowed_roles'] );
        $this->send_activation_email();
    }

    private function get_default_teaser() {
        return array(
            'image_id'   => '',
            'image_url'  => '',
            'content'    => '',
            'url'        => '',
            'target'     => '_self',
            'link_mode'  => 'full',    // 'full', 'image', 'none'
            'btn1_label' => '',
            'btn1_url'   => '',
            'btn1_target'=> '_self',
            'btn2_label' => '',
            'btn2_url'   => '',
            'btn2_target'=> '_self',
        );
    }

    private function get_default_settings() {
        return array(
            'desktop_visible'      => 3,
            'tablet_visible'       => 2,
            'mobile_visible'       => 1,
            'tablet_breakpoint'    => 1024,
            'mobile_breakpoint'    => 768,
            'autoplay'             => false,
            'autoplay_interval'    => 5000,
            'loop'                 => true,
            'teaser_source_mode'   => 'manual', // 'manual', 'acf_posts'
            'teaser_source_post_type' => '',
            'teaser_source_category' => '',
            'teaser_source_orderby' => 'date',
            'teaser_source_order' => 'DESC',
            'teaser_auto_button_label' => __( 'Zum Beitrag', 'itn-teaser' ),
            'extra_classes'        => '',
            'border_enabled'       => false,
            'border_width'         => 1,
            'border_style'         => 'solid',
            'border_color'         => '#e5e5e5',
            'border_radius'        => 20,
            'background_color'     => '',
            'text_color'           => '#4d4d4d',
            // 4-way padding (replaces single item_padding)
            'padding_top'          => 12,
            'padding_right'        => 12,
            'padding_bottom'       => 12,
            'padding_left'         => 12,
            // 4-way margin
            'margin_top'           => 0,
            'margin_right'         => 0,
            'margin_bottom'        => 0,
            'margin_left'          => 0,
            'gap'                  => 16,
            // Hover effect
            'hover_effect'         => 'zoom',   // 'zoom', 'overlay', 'none'
            'overlay_color'        => '#000000',
            'overlay_opacity'      => 30,        // 0–100
            // Navigation arrows
            'arrow_enabled'        => true,
            'arrow_style'          => 'chevron', // 'chevron', 'arrow', 'caret', or custom
            'arrow_bg_shape'       => 'rounded', // 'rounded', 'round', 'square'
            'arrow_visibility'     => 'always',  // 'always', 'hover'
            'arrow_size'           => 44,
            'arrow_color'          => '#ffffff',
            'arrow_bg_color'       => '#000000',
            'arrow_bg_opacity'     => 60,        // 0–100
            'arrow_border_radius'  => 22,
            // Arrow positioning - desktop
            'arrow_position_top'   => 50,       // percentage from top, or -1 for auto
            'arrow_position_left'  => 10,       // left arrow from left, or -1 for auto
            'arrow_position_right' => 10,       // right arrow from right, or -1 for auto
            'arrow_offset_x'       => 0,        // horizontal offset in px (supports negative)
            'arrow_offset_y'       => 0,        // vertical offset in px (supports negative)
            // Arrow positioning - mobile overrides
            'arrow_mobile_enabled' => false,    // enable mobile-specific overrides
            'arrow_mobile_breakpoint' => 768,   // breakpoint for mobile overrides
            'arrow_mobile_position_top' => 50,  // mobile top position
            'arrow_mobile_position_left' => 5,  // mobile left position
            'arrow_mobile_position_right' => 5, // mobile right position
            'arrow_mobile_offset_x' => 0,       // mobile horizontal offset
            'arrow_mobile_offset_y' => 0,       // mobile vertical offset
            'arrow_mobile_size'    => 36,       // mobile arrow size
            'arrow_mobile_hide'    => false,    // hide arrows on mobile
            // Bullets / pagination dots
            'bullets_enabled'      => false,
            'bullets_style'        => 'dots',    // 'dots', 'squares', 'lines', 'hollow'
            'bullets_size'         => 10,
            'bullets_color'        => '#cccccc',
            'bullets_active_color' => '#333333',
            // Bullets side arrows
            'bullets_side_arrows'  => false,    // enable prev/next arrows beside bullets
            'bullets_arrow_left'   => '‹',      // left arrow character
            'bullets_arrow_right'  => '›',      // right arrow character
            'bullets_arrow_color'  => '#333333',
            'bullets_arrow_size'   => 18,       // font size for arrows
            'bullets_arrow_position_left' => -40,  // position from left edge (can be negative)
            'bullets_arrow_position_right' => -40, // position from right edge (can be negative)
            'bullets_arrow_offset_x' => 0,     // horizontal offset in px
            'bullets_arrow_offset_y' => 0,     // vertical offset in px
            // Custom CSS / Stylesheet
            'custom_css'           => '',
            'stylesheet_url'       => '',
        );
    }

    private function get_default_set( $name = '' ) {
        return array(
            'name'     => $name ? $name : __( 'Standard', 'itn-teaser' ),
            'teasers'  => array(
                $this->get_default_teaser(),
            ),
            'settings' => $this->get_default_settings(),
        );
    }

    private function get_default_data() {
        return array(
            'sets'          => array(
                $this->default_set_id => $this->get_default_set(),
            ),
            'assignments'   => array(),
            'allowed_roles' => array( 'administrator' ),
            'delete_on_uninstall' => false,
        );
    }

    private function get_plugin_label() {
        return 'ITN Teaser';
    }

    private function sanitize_css_classes( $classes ) {
        $classes = preg_split( '/\s+/', (string) $classes );
        $sanitized = array();

        foreach ( $classes as $class_name ) {
            $class_name = sanitize_html_class( $class_name );
            if ( '' !== $class_name ) {
                $sanitized[] = $class_name;
            }
        }

        return implode( ' ', array_unique( $sanitized ) );
    }

    private function get_auto_button_label_setting( $settings ) {
        $button_label = isset( $settings['teaser_auto_button_label'] ) ? sanitize_text_field( (string) $settings['teaser_auto_button_label'] ) : '';
        return '' !== $button_label ? $button_label : __( 'Zum Beitrag', 'itn-teaser' );
    }

    private function sanitize_custom_css( $css ) {
        // Remove all HTML/script tags first
        $css = wp_strip_all_tags( (string) $css );
        // Remove potentially dangerous CSS: expression(), javascript: URIs, -moz-binding, behavior:
        $css = preg_replace( '/expression\s*\(/i', '', $css );
        $css = preg_replace( '/javascript\s*:/i', '', $css );
        $css = preg_replace( '/-moz-binding\s*:/i', '', $css );
        $css = preg_replace( '/behaviour\s*:/i', '', $css );
        $css = preg_replace( '/behavior\s*:/i', '', $css );
        return $css;
    }

    private function sanitize_color_value( $color ) {
        $color = trim( (string) $color );
        
        // Try hex color first
        $sanitized_hex = sanitize_hex_color( $color );
        if ( $sanitized_hex ) {
            return $sanitized_hex;
        }
        
        // Try RGB format: rgb(r, g, b) or rgba(r, g, b, a)
        if ( preg_match( '/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/i', $color, $matches ) ) {
            $r = intval( $matches[1] );
            $g = intval( $matches[2] );
            $b = intval( $matches[3] );
            $a = isset( $matches[4] ) ? floatval( $matches[4] ) : 1;
            
            // Validate RGB values are 0-255
            if ( $r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255 && $a >= 0 && $a <= 1 ) {
                if ( $a < 1 ) {
                    return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
                } else {
                    return 'rgb(' . $r . ',' . $g . ',' . $b . ')';
                }
            }
        }
        
        return '';
    }

    private function hex_to_rgba( $hex, $opacity ) {
        $opacity = max( 0.0, min( 1.0, (float) $opacity ) );
        $hex = ltrim( (string) $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( 6 !== strlen( $hex ) ) {
            return 'rgba(0,0,0,' . $opacity . ')';
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $opacity . ')';
    }

    private function get_shortcode_for_set( $set_id ) {
        return sprintf( '[%1$s set="%2$s"]', $this->shortcode, $this->normalize_set_id( $set_id ) );
    }

    private function render_set_shortcode( $set_id ) {
        $shortcode = $this->get_shortcode_for_set( $set_id );
        ?>
        <div class="itn-teaser-shortcode-box">
            <label for="itn-teaser-shortcode-<?php echo esc_attr( $set_id ); ?>"><strong><?php esc_html_e( 'Shortcode dieses Teasermoduls', 'itn-teaser' ); ?></strong></label>
            <input type="text" readonly="readonly" id="itn-teaser-shortcode-<?php echo esc_attr( $set_id ); ?>" class="regular-text code" value="<?php echo esc_attr( $shortcode ); ?>" onclick="this.select();" />
            <p class="description"><?php esc_html_e( 'Diesen Shortcode kannst du direkt kopieren und in Seiten, Beiträgen oder Widgets verwenden.', 'itn-teaser' ); ?></p>
        </div>
        <?php
    }

    private function send_activation_email() {
        $recipient = apply_filters( 'itn_teaser_activation_email_recipient', get_option( 'admin_email' ) );

        if ( ! is_email( $recipient ) || ! function_exists( 'wp_mail' ) ) {
            return;
        }

        $current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
        $user_line    = __( 'Nicht verfügbar', 'itn-teaser' );

        if ( $current_user instanceof WP_User && $current_user->exists() ) {
            $user_parts = array_filter(
                array(
                    $current_user->display_name,
                    $current_user->user_login,
                    is_email( $current_user->user_email ) ? $current_user->user_email : '',
                )
            );
            $user_line = implode( ' | ', $user_parts );
        }

        $lines = array(
            sprintf( __( 'Plugin: %s', 'itn-teaser' ), sanitize_text_field( $this->get_plugin_label() ) ),
            sprintf( __( 'Version: %s', 'itn-teaser' ), sanitize_text_field( $this->version ) ),
            sprintf( __( 'Datum/Uhrzeit (Site-Lokalzeit): %s', 'itn-teaser' ), sanitize_text_field( current_time( 'mysql' ) ) ),
            sprintf( __( 'Domain: %s', 'itn-teaser' ), sanitize_text_field( wp_parse_url( home_url(), PHP_URL_HOST ) ?: __( 'Nicht verfügbar', 'itn-teaser' ) ) ),
            sprintf( __( 'Home-URL: %s', 'itn-teaser' ), esc_url_raw( home_url() ) ),
            sprintf( __( 'Site-URL: %s', 'itn-teaser' ), esc_url_raw( site_url() ) ),
            sprintf( __( 'WordPress-Version: %s', 'itn-teaser' ), sanitize_text_field( get_bloginfo( 'version' ) ) ),
            sprintf( __( 'PHP-Version: %s', 'itn-teaser' ), sanitize_text_field( PHP_VERSION ) ),
            sprintf( __( 'Benutzer: %s', 'itn-teaser' ), sanitize_text_field( $user_line ) ),
        );

        wp_mail(
            $recipient,
            sprintf( __( '[%s] Plugin aktiviert', 'itn-teaser' ), $this->get_plugin_label() ),
            implode( "\n", $lines )
        );
    }

    private function normalize_set_id( $set_id ) {
        $set_id = sanitize_title( (string) $set_id );
        return $set_id ? $set_id : $this->default_set_id;
    }

    private function normalize_set( $set, $fallback_name = '' ) {
        $default_set = $this->get_default_set( $fallback_name );

        $name = isset( $set['name'] ) ? sanitize_text_field( $set['name'] ) : $default_set['name'];
        if ( '' === $name ) {
            $name = $default_set['name'];
        }

        $teasers = array();
        if ( ! empty( $set['teasers'] ) && is_array( $set['teasers'] ) ) {
            foreach ( $set['teasers'] as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $teasers[] = array(
                    'image_id'    => isset( $item['image_id'] ) ? (string) intval( $item['image_id'] ) : '',
                    'image_url'   => isset( $item['image_url'] ) ? esc_url_raw( $item['image_url'] ) : '',
                    'content'     => isset( $item['content'] ) ? wp_kses_post( $item['content'] ) : '',
                    'url'         => isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '',
                    'target'      => ( isset( $item['target'] ) && '_blank' === $item['target'] ) ? '_blank' : '_self',
                    'link_mode'   => ( isset( $item['link_mode'] ) && in_array( $item['link_mode'], array( 'full', 'image', 'none' ), true ) ) ? $item['link_mode'] : 'full',
                    'btn1_label'  => isset( $item['btn1_label'] ) ? sanitize_text_field( $item['btn1_label'] ) : '',
                    'btn1_url'    => isset( $item['btn1_url'] ) ? esc_url_raw( $item['btn1_url'] ) : '',
                    'btn1_target' => ( isset( $item['btn1_target'] ) && '_blank' === $item['btn1_target'] ) ? '_blank' : '_self',
                    'btn2_label'  => isset( $item['btn2_label'] ) ? sanitize_text_field( $item['btn2_label'] ) : '',
                    'btn2_url'    => isset( $item['btn2_url'] ) ? esc_url_raw( $item['btn2_url'] ) : '',
                    'btn2_target' => ( isset( $item['btn2_target'] ) && '_blank' === $item['btn2_target'] ) ? '_blank' : '_self',
                );
            }
        }

        if ( empty( $teasers ) ) {
            $teasers[] = $this->get_default_teaser();
        }

        return array(
            'name'     => $name,
            'teasers'  => $teasers,
            'settings' => $this->sanitize_settings(
                isset( $set['settings'] ) && is_array( $set['settings'] ) ? $set['settings'] : array(),
                count( $teasers )
            ),
        );
    }

    private function normalize_data( $data ) {
        $normalized = $this->get_default_data();

        if ( ! is_array( $data ) ) {
            return $normalized;
        }

        if ( isset( $data['sets'] ) && is_array( $data['sets'] ) ) {
            foreach ( $data['sets'] as $set_id => $set ) {
                $set_id = $this->normalize_set_id( $set_id );
                $normalized['sets'][ $set_id ] = $this->normalize_set( $set, ucfirst( str_replace( '-', ' ', $set_id ) ) );
            }
        } elseif ( isset( $data['teasers'] ) || isset( $data['settings'] ) ) {
            $normalized['sets'][ $this->default_set_id ] = $this->normalize_set(
                array(
                    'name'     => __( 'Standard', 'itn-teaser' ),
                    'teasers'  => isset( $data['teasers'] ) && is_array( $data['teasers'] ) ? $data['teasers'] : array(),
                    'settings' => isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : array(),
                ),
                __( 'Standard', 'itn-teaser' )
            );
        } elseif ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $normalized['sets'][ $this->default_set_id ] = $this->normalize_set(
                array(
                    'name'     => __( 'Standard', 'itn-teaser' ),
                    'teasers'  => $data,
                    'settings' => $this->get_default_settings(),
                ),
                __( 'Standard', 'itn-teaser' )
            );
        }

        if ( empty( $normalized['sets'] ) ) {
            $normalized['sets'][ $this->default_set_id ] = $this->get_default_set();
        }

        if ( ! isset( $normalized['sets'][ $this->default_set_id ] ) ) {
            $normalized['sets'][ $this->default_set_id ] = $this->get_default_set();
        }

        $normalized['assignments'] = array();
        if ( ! empty( $data['assignments'] ) && is_array( $data['assignments'] ) ) {
            foreach ( $data['assignments'] as $page_id => $set_id ) {
                $page_id = intval( $page_id );
                $set_id  = $this->normalize_set_id( $set_id );
                if ( $page_id > 0 && isset( $normalized['sets'][ $set_id ] ) ) {
                    $normalized['assignments'][ $page_id ] = $set_id;
                }
            }
        }

        $allowed_roles = array( 'administrator' );
        if ( ! empty( $data['allowed_roles'] ) && is_array( $data['allowed_roles'] ) ) {
            $editable_roles = array_keys( wp_roles()->roles );
            foreach ( $data['allowed_roles'] as $role ) {
                $role = sanitize_key( $role );
                if ( in_array( $role, $editable_roles, true ) ) {
                    $allowed_roles[] = $role;
                }
            }
        }
        $normalized['allowed_roles'] = array_values( array_unique( $allowed_roles ) );
        $normalized['delete_on_uninstall'] = ! empty( $data['delete_on_uninstall'] );

        return $normalized;
    }

    private function get_data() {
        return $this->normalize_data( get_option( $this->option_name, array() ) );
    }

    private function save_data( $data ) {
        $normalized = $this->normalize_data( $data );
        update_option( $this->option_name, $normalized );
        return $normalized;
    }

    private function get_set_options() {
        $data = $this->get_data();
        $options = array();

        foreach ( $data['sets'] as $set_id => $set ) {
            $options[] = array(
                'label' => $set['name'],
                'value' => $set_id,
            );
        }

        return $options;
    }

    private function get_current_set_id( $sets ) {
        $requested = isset( $_GET['set'] ) ? $this->normalize_set_id( sanitize_text_field( wp_unslash( $_GET['set'] ) ) ) : $this->default_set_id;
        if ( isset( $sets[ $requested ] ) ) {
            return $requested;
        }

        reset( $sets );
        return key( $sets );
    }

    private function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'teaser';
        $allowed_tabs = array( 'teaser', 'navigationspfeile', 'bullets', 'allgemein', 'permissions', 'export' );
        return in_array( $tab, $allowed_tabs, true ) ? $tab : 'teaser';
    }

    public function ensure_role_caps() {
        $data = $this->get_data();
        $this->sync_role_caps( $data['allowed_roles'] );
    }

    private function sync_role_caps( $allowed_roles ) {
        $allowed_roles = array_values( array_unique( array_merge( array( 'administrator' ), array_map( 'sanitize_key', (array) $allowed_roles ) ) ) );
        $wp_roles = wp_roles();

        foreach ( $wp_roles->role_objects as $role_name => $role ) {
            if ( 'administrator' === $role_name || in_array( $role_name, $allowed_roles, true ) ) {
                $role->add_cap( $this->capability );
            } else {
                $role->remove_cap( $this->capability );
            }
        }
    }

    private function current_user_can_manage_plugin() {
        return current_user_can( 'manage_options' ) || current_user_can( $this->capability );
    }

    public function add_admin_menu() {
        global $menu;

        $parent_exists = false;
        if ( is_array( $menu ) ) {
            foreach ( $menu as $menu_item ) {
                if ( isset( $menu_item[2] ) && $this->parent_menu_slug === $menu_item[2] ) {
                    $parent_exists = true;
                    break;
                }
            }
        }

        if ( ! $parent_exists ) {
            add_menu_page(
                __( 'ITN Module', 'itn-teaser' ),
                __( 'ITN Module', 'itn-teaser' ),
                $this->capability,
                $this->parent_menu_slug,
                array( $this, 'render_modules_page' ),
                'dashicons-screenoptions',
                58
            );
        }

        $this->page_hook_suffix = add_submenu_page(
            $this->parent_menu_slug,
            __( 'ITN Teaser', 'itn-teaser' ),
            __( 'ITN Teaser', 'itn-teaser' ),
            $this->capability,
            $this->menu_slug,
            array( $this, 'render_admin_page' )
        );
    }

    public function render_modules_page() {
        if ( ! $this->current_user_can_manage_plugin() ) {
            wp_die( esc_html__( 'Du hast keine Berechtigung, auf ITN Module zuzugreifen.', 'itn-teaser' ) );
        }

        wp_safe_redirect( add_query_arg( 'page', $this->menu_slug, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function admin_assets( $hook ) {
        if ( $this->page_hook_suffix && $this->page_hook_suffix !== $hook ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_editor();
        wp_enqueue_style( 'itn-teaser-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), $this->version );
        wp_enqueue_script( 'itn-teaser-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
        wp_localize_script(
            'itn-teaser-admin-js',
            'SventesTeaserAdmin',
            array(
                'acf_visible_max' => self::ACF_VISIBLE_MAX,
                'strings' => array(
                    'choose_image' => __( 'Bild wählen', 'itn-teaser' ),
                    'remove_image' => __( 'Bild entfernen', 'itn-teaser' ),
                    'confirm_delete_set' => __( 'Dieses Teasermodul wirklich löschen?', 'itn-teaser' ),
                    'teaser_label' => __( 'Teaser', 'itn-teaser' ),
                ),
            )
        );
    }

    private function enqueue_frontend_assets() {
        wp_enqueue_style( 'itn-teaser-css', plugin_dir_url( __FILE__ ) . 'assets/css/sventes-teaser.css', array(), $this->version );
        wp_enqueue_script( 'itn-teaser-js', plugin_dir_url( __FILE__ ) . 'assets/js/sventes-teaser.js', array( 'jquery' ), $this->version, true );
        wp_localize_script( 'itn-teaser-js', 'SventesTeaserSettings', $this->get_default_settings() );
    }

    public function register_block() {
        wp_register_script(
            'itn-teaser-block',
            plugin_dir_url( __FILE__ ) . 'assets/js/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
            $this->version,
            true
        );

        wp_localize_script(
            'itn-teaser-block',
            'ITNTeaserBlockData',
            array(
                'sets' => $this->get_set_options(),
            )
        );

        wp_register_style( 'itn-teaser-css', plugin_dir_url( __FILE__ ) . 'assets/css/sventes-teaser.css', array(), $this->version );

        register_block_type(
            'itn/teaser',
            array(
                'api_version'     => 2,
                'title'           => __( 'ITN Teaser', 'itn-teaser' ),
                'description'     => __( 'Zeigt ein ausgewähltes Teasermodul oder die Seitenzuordnung an.', 'itn-teaser' ),
                'render_callback' => array( $this, 'render_block' ),
                'editor_script'   => 'itn-teaser-block',
                'style'           => 'itn-teaser-css',
                'editor_style'    => 'itn-teaser-css',
                'attributes'      => array(
                    'setId'             => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'usePageAssignment' => array(
                        'type'    => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );
    }

    private function sanitize_teasers( $input ) {
        $sanitized = array();

        foreach ( (array) $input as $raw_item ) {
            if ( ! is_array( $raw_item ) ) {
                continue;
            }

            $image_id = ! empty( $raw_item['image_id'] ) ? intval( $raw_item['image_id'] ) : '';
            $image_url = '';
            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'full' ) ?: '';
            } elseif ( ! empty( $raw_item['image_url'] ) ) {
                $image_url = esc_url_raw( $raw_item['image_url'] );
            }

            $content  = isset( $raw_item['content'] ) ? wp_kses_post( $raw_item['content'] ) : '';
            $url      = isset( $raw_item['url'] ) ? esc_url_raw( $raw_item['url'] ) : '';
            $target   = ( isset( $raw_item['target'] ) && '_blank' === $raw_item['target'] ) ? '_blank' : '_self';

            $allowed_link_modes = array( 'full', 'image', 'none' );
            $link_mode = ( isset( $raw_item['link_mode'] ) && in_array( $raw_item['link_mode'], $allowed_link_modes, true ) )
                ? $raw_item['link_mode'] : 'full';

            $btn1_label  = isset( $raw_item['btn1_label'] ) ? sanitize_text_field( $raw_item['btn1_label'] ) : '';
            $btn1_url    = isset( $raw_item['btn1_url'] ) ? esc_url_raw( $raw_item['btn1_url'] ) : '';
            $btn1_target = ( isset( $raw_item['btn1_target'] ) && '_blank' === $raw_item['btn1_target'] ) ? '_blank' : '_self';
            $btn2_label  = isset( $raw_item['btn2_label'] ) ? sanitize_text_field( $raw_item['btn2_label'] ) : '';
            $btn2_url    = isset( $raw_item['btn2_url'] ) ? esc_url_raw( $raw_item['btn2_url'] ) : '';
            $btn2_target = ( isset( $raw_item['btn2_target'] ) && '_blank' === $raw_item['btn2_target'] ) ? '_blank' : '_self';

            if ( '' === $image_url && '' === wp_strip_all_tags( $content ) && '' === $url ) {
                continue;
            }

            $sanitized[] = array(
                'image_id'    => $image_id,
                'image_url'   => $image_url,
                'content'     => $content,
                'url'         => $url,
                'target'      => $target,
                'link_mode'   => $link_mode,
                'btn1_label'  => $btn1_label,
                'btn1_url'    => $btn1_url,
                'btn1_target' => $btn1_target,
                'btn2_label'  => $btn2_label,
                'btn2_url'    => $btn2_url,
                'btn2_target' => $btn2_target,
            );
        }

        if ( empty( $sanitized ) ) {
            $sanitized[] = $this->get_default_teaser();
        }

        return $sanitized;
    }

    private function sanitize_settings( $settings_in, $count_teasers ) {
        $defaults    = $this->get_default_settings();
        $settings_in = is_array( $settings_in ) ? $settings_in : array();

        $desktop_visible = isset( $settings_in['desktop_visible'] ) ? intval( $settings_in['desktop_visible'] ) : $defaults['desktop_visible'];
        $tablet_visible  = isset( $settings_in['tablet_visible'] ) ? intval( $settings_in['tablet_visible'] ) : $defaults['tablet_visible'];
        $mobile_visible  = isset( $settings_in['mobile_visible'] ) ? intval( $settings_in['mobile_visible'] ) : $defaults['mobile_visible'];

        $tablet_breakpoint = isset( $settings_in['tablet_breakpoint'] ) ? intval( $settings_in['tablet_breakpoint'] ) : $defaults['tablet_breakpoint'];
        $mobile_breakpoint = isset( $settings_in['mobile_breakpoint'] ) ? intval( $settings_in['mobile_breakpoint'] ) : $defaults['mobile_breakpoint'];

        $tablet_breakpoint = max( 320, min( 5000, $tablet_breakpoint ) );
        $mobile_breakpoint = max( 320, min( 4999, $mobile_breakpoint ) );
        if ( $mobile_breakpoint >= $tablet_breakpoint ) {
            $mobile_breakpoint = max( 320, $tablet_breakpoint - 1 );
        }

        $source_mode = isset( $settings_in['teaser_source_mode'] ) ? sanitize_key( $settings_in['teaser_source_mode'] ) : $defaults['teaser_source_mode'];
        if ( ! in_array( $source_mode, array( 'manual', 'acf_posts' ), true ) ) {
            $source_mode = $defaults['teaser_source_mode'];
        }
        $source_post_type = isset( $settings_in['teaser_source_post_type'] ) ? sanitize_key( $settings_in['teaser_source_post_type'] ) : '';
        if ( $source_post_type && ! post_type_exists( $source_post_type ) ) {
            $source_post_type = '';
        }
        $source_category = isset( $settings_in['teaser_source_category'] ) ? sanitize_text_field( (string) $settings_in['teaser_source_category'] ) : '';
        $source_orderby = isset( $settings_in['teaser_source_orderby'] ) ? sanitize_key( $settings_in['teaser_source_orderby'] ) : $defaults['teaser_source_orderby'];
        if ( ! in_array( $source_orderby, array( 'date', 'title', 'menu_order' ), true ) ) {
            $source_orderby = $defaults['teaser_source_orderby'];
        }
        $source_order = isset( $settings_in['teaser_source_order'] ) ? strtoupper( sanitize_key( $settings_in['teaser_source_order'] ) ) : $defaults['teaser_source_order'];
        if ( ! in_array( $source_order, array( 'ASC', 'DESC' ), true ) ) {
            $source_order = $defaults['teaser_source_order'];
        }
        $auto_button_label = $this->get_auto_button_label_setting( $settings_in );

        $border_style = isset( $settings_in['border_style'] ) ? sanitize_key( $settings_in['border_style'] ) : $defaults['border_style'];
        if ( ! in_array( $border_style, array( 'solid', 'dashed', 'dotted' ), true ) ) {
            $border_style = $defaults['border_style'];
        }

        // 4-way padding – backward compat: if legacy item_padding is present, use as fallback
        $legacy_padding = isset( $settings_in['item_padding'] ) ? intval( $settings_in['item_padding'] ) : null;
        $pad_default    = null !== $legacy_padding ? $legacy_padding : $defaults['padding_top'];
        $padding_top    = isset( $settings_in['padding_top'] )    ? intval( $settings_in['padding_top'] )    : $pad_default;
        $padding_right  = isset( $settings_in['padding_right'] )  ? intval( $settings_in['padding_right'] )  : $pad_default;
        $padding_bottom = isset( $settings_in['padding_bottom'] ) ? intval( $settings_in['padding_bottom'] ) : $pad_default;
        $padding_left   = isset( $settings_in['padding_left'] )   ? intval( $settings_in['padding_left'] )   : $pad_default;
        $margin_top     = isset( $settings_in['margin_top'] )     ? intval( $settings_in['margin_top'] )     : ( isset( $defaults['margin_top'] ) ? intval( $defaults['margin_top'] ) : 0 );
        $margin_right   = isset( $settings_in['margin_right'] )   ? intval( $settings_in['margin_right'] )   : ( isset( $defaults['margin_right'] ) ? intval( $defaults['margin_right'] ) : 0 );
        $margin_bottom  = isset( $settings_in['margin_bottom'] )  ? intval( $settings_in['margin_bottom'] )  : ( isset( $defaults['margin_bottom'] ) ? intval( $defaults['margin_bottom'] ) : 0 );
        $margin_left    = isset( $settings_in['margin_left'] )    ? intval( $settings_in['margin_left'] )    : ( isset( $defaults['margin_left'] ) ? intval( $defaults['margin_left'] ) : 0 );

        // Hover effect
        $allowed_hover = array( 'zoom', 'overlay', 'none' );
        $hover_effect  = isset( $settings_in['hover_effect'] ) && in_array( $settings_in['hover_effect'], $allowed_hover, true )
            ? $settings_in['hover_effect'] : $defaults['hover_effect'];
        $overlay_color   = $this->sanitize_color_value( isset( $settings_in['overlay_color'] ) ? $settings_in['overlay_color'] : $defaults['overlay_color'] );
        if ( ! $overlay_color ) {
            $overlay_color = $defaults['overlay_color'];
        }
        $overlay_opacity = max( 0, min( 100, isset( $settings_in['overlay_opacity'] ) ? intval( $settings_in['overlay_opacity'] ) : $defaults['overlay_opacity'] ) );

        // Arrow settings
        $allowed_arrow_styles   = array( 'chevron', 'arrow', 'caret' );
        $allowed_arrow_bg_shapes = array( 'rounded', 'round', 'square' );
        $allowed_arrow_visibility = array( 'always', 'hover' );
        $arrow_style = isset( $settings_in['arrow_style'] ) && in_array( $settings_in['arrow_style'], $allowed_arrow_styles, true )
            ? $settings_in['arrow_style'] : $defaults['arrow_style'];
        $arrow_bg_shape = isset( $settings_in['arrow_bg_shape'] ) && in_array( $settings_in['arrow_bg_shape'], $allowed_arrow_bg_shapes, true )
            ? $settings_in['arrow_bg_shape'] : $defaults['arrow_bg_shape'];
        $arrow_visibility = isset( $settings_in['arrow_visibility'] ) && in_array( $settings_in['arrow_visibility'], $allowed_arrow_visibility, true )
            ? $settings_in['arrow_visibility'] : $defaults['arrow_visibility'];
        $arrow_size          = max( 20, min( 120, isset( $settings_in['arrow_size'] ) ? intval( $settings_in['arrow_size'] ) : $defaults['arrow_size'] ) );
        $arrow_color         = $this->sanitize_color_value( isset( $settings_in['arrow_color'] ) ? $settings_in['arrow_color'] : $defaults['arrow_color'] );
        if ( ! $arrow_color ) {
            $arrow_color = $defaults['arrow_color'];
        }
        $arrow_bg_color      = $this->sanitize_color_value( isset( $settings_in['arrow_bg_color'] ) ? $settings_in['arrow_bg_color'] : $defaults['arrow_bg_color'] );
        if ( ! $arrow_bg_color ) {
            $arrow_bg_color = $defaults['arrow_bg_color'];
        }
        $arrow_bg_opacity    = max( 0, min( 100, isset( $settings_in['arrow_bg_opacity'] ) ? intval( $settings_in['arrow_bg_opacity'] ) : $defaults['arrow_bg_opacity'] ) );
        $arrow_border_radius = max( 0, min( 100, isset( $settings_in['arrow_border_radius'] ) ? intval( $settings_in['arrow_border_radius'] ) : $defaults['arrow_border_radius'] ) );

        // Arrow positioning - desktop
        $arrow_position_top = isset( $settings_in['arrow_position_top'] ) ? intval( $settings_in['arrow_position_top'] ) : $defaults['arrow_position_top'];
        $arrow_position_left = isset( $settings_in['arrow_position_left'] ) ? intval( $settings_in['arrow_position_left'] ) : $defaults['arrow_position_left'];
        $arrow_position_right = isset( $settings_in['arrow_position_right'] ) ? intval( $settings_in['arrow_position_right'] ) : $defaults['arrow_position_right'];
        $arrow_offset_x = isset( $settings_in['arrow_offset_x'] ) ? intval( $settings_in['arrow_offset_x'] ) : $defaults['arrow_offset_x'];
        $arrow_offset_y = isset( $settings_in['arrow_offset_y'] ) ? intval( $settings_in['arrow_offset_y'] ) : $defaults['arrow_offset_y'];

        // Arrow mobile overrides
        $arrow_mobile_enabled = ! empty( $settings_in['arrow_mobile_enabled'] );
        $arrow_mobile_breakpoint = isset( $settings_in['arrow_mobile_breakpoint'] ) ? intval( $settings_in['arrow_mobile_breakpoint'] ) : $defaults['arrow_mobile_breakpoint'];
        $arrow_mobile_position_top = isset( $settings_in['arrow_mobile_position_top'] ) ? intval( $settings_in['arrow_mobile_position_top'] ) : $defaults['arrow_mobile_position_top'];
        $arrow_mobile_position_left = isset( $settings_in['arrow_mobile_position_left'] ) ? intval( $settings_in['arrow_mobile_position_left'] ) : $defaults['arrow_mobile_position_left'];
        $arrow_mobile_position_right = isset( $settings_in['arrow_mobile_position_right'] ) ? intval( $settings_in['arrow_mobile_position_right'] ) : $defaults['arrow_mobile_position_right'];
        $arrow_mobile_offset_x = isset( $settings_in['arrow_mobile_offset_x'] ) ? intval( $settings_in['arrow_mobile_offset_x'] ) : $defaults['arrow_mobile_offset_x'];
        $arrow_mobile_offset_y = isset( $settings_in['arrow_mobile_offset_y'] ) ? intval( $settings_in['arrow_mobile_offset_y'] ) : $defaults['arrow_mobile_offset_y'];
        $arrow_mobile_size = max( 20, min( 120, isset( $settings_in['arrow_mobile_size'] ) ? intval( $settings_in['arrow_mobile_size'] ) : $defaults['arrow_mobile_size'] ) );
        $arrow_mobile_hide = ! empty( $settings_in['arrow_mobile_hide'] );

        // Bullets settings
        $allowed_bullet_styles = array( 'dots', 'squares', 'lines', 'hollow' );
        $bullets_style = isset( $settings_in['bullets_style'] ) && in_array( $settings_in['bullets_style'], $allowed_bullet_styles, true )
            ? $settings_in['bullets_style'] : $defaults['bullets_style'];
        $bullets_size         = max( 4, min( 40, isset( $settings_in['bullets_size'] ) ? intval( $settings_in['bullets_size'] ) : $defaults['bullets_size'] ) );
        $bullets_color        = $this->sanitize_color_value( isset( $settings_in['bullets_color'] ) ? $settings_in['bullets_color'] : $defaults['bullets_color'] );
        if ( ! $bullets_color ) {
            $bullets_color = $defaults['bullets_color'];
        }
        $bullets_active_color = $this->sanitize_color_value( isset( $settings_in['bullets_active_color'] ) ? $settings_in['bullets_active_color'] : $defaults['bullets_active_color'] );
        if ( ! $bullets_active_color ) {
            $bullets_active_color = $defaults['bullets_active_color'];
        }

        // Bullets side arrows
        $bullets_side_arrows = ! empty( $settings_in['bullets_side_arrows'] );
        $bullets_arrow_left = isset( $settings_in['bullets_arrow_left'] ) ? sanitize_text_field( (string) $settings_in['bullets_arrow_left'] ) : $defaults['bullets_arrow_left'];
        $bullets_arrow_right = isset( $settings_in['bullets_arrow_right'] ) ? sanitize_text_field( (string) $settings_in['bullets_arrow_right'] ) : $defaults['bullets_arrow_right'];
        $bullets_arrow_color = $this->sanitize_color_value( isset( $settings_in['bullets_arrow_color'] ) ? $settings_in['bullets_arrow_color'] : $defaults['bullets_arrow_color'] );
        if ( ! $bullets_arrow_color ) {
            $bullets_arrow_color = $defaults['bullets_arrow_color'];
        }
        $bullets_arrow_size = max( 8, min( 48, isset( $settings_in['bullets_arrow_size'] ) ? intval( $settings_in['bullets_arrow_size'] ) : $defaults['bullets_arrow_size'] ) );
         
        // Bullet arrow positioning
        $bullets_arrow_position_left = isset( $settings_in['bullets_arrow_position_left'] ) ? intval( $settings_in['bullets_arrow_position_left'] ) : $defaults['bullets_arrow_position_left'];
        $bullets_arrow_position_right = isset( $settings_in['bullets_arrow_position_right'] ) ? intval( $settings_in['bullets_arrow_position_right'] ) : $defaults['bullets_arrow_position_right'];
        $bullets_arrow_offset_x = isset( $settings_in['bullets_arrow_offset_x'] ) ? intval( $settings_in['bullets_arrow_offset_x'] ) : $defaults['bullets_arrow_offset_x'];
        $bullets_arrow_offset_y = isset( $settings_in['bullets_arrow_offset_y'] ) ? intval( $settings_in['bullets_arrow_offset_y'] ) : $defaults['bullets_arrow_offset_y'];

        $max_visible_items = self::ACF_VISIBLE_MAX;

        return array(
            'desktop_visible'      => max( 1, min( $max_visible_items, $desktop_visible ) ),
            'tablet_visible'       => max( 1, min( $max_visible_items, $tablet_visible ) ),
            'mobile_visible'       => max( 1, min( $max_visible_items, $mobile_visible ) ),
            'tablet_breakpoint'    => $tablet_breakpoint,
            'mobile_breakpoint'    => $mobile_breakpoint,
            'autoplay'             => ! empty( $settings_in['autoplay'] ),
            'autoplay_interval'    => max( 1000, isset( $settings_in['autoplay_interval'] ) ? intval( $settings_in['autoplay_interval'] ) : $defaults['autoplay_interval'] ),
            'loop'                 => ! empty( $settings_in['loop'] ),
            'teaser_source_mode'   => $source_mode,
            'teaser_source_post_type' => $source_post_type,
            'teaser_source_category' => $source_category,
            'teaser_source_orderby' => $source_orderby,
            'teaser_source_order' => $source_order,
            'teaser_auto_button_label' => $auto_button_label,
            'extra_classes'        => $this->sanitize_css_classes( isset( $settings_in['extra_classes'] ) ? $settings_in['extra_classes'] : $defaults['extra_classes'] ),
            'border_enabled'       => ! empty( $settings_in['border_enabled'] ),
            'border_width'         => max( 0, min( 20, isset( $settings_in['border_width'] ) ? intval( $settings_in['border_width'] ) : $defaults['border_width'] ) ),
            'border_style'         => $border_style,
            'border_color'         => $this->sanitize_color_value( isset( $settings_in['border_color'] ) ? $settings_in['border_color'] : $defaults['border_color'] ),
            'border_radius'        => max( 0, min( 200, isset( $settings_in['border_radius'] ) ? intval( $settings_in['border_radius'] ) : $defaults['border_radius'] ) ),
            'background_color'     => $this->sanitize_color_value( isset( $settings_in['background_color'] ) ? $settings_in['background_color'] : $defaults['background_color'] ),
            'text_color'           => $this->sanitize_color_value( isset( $settings_in['text_color'] ) ? $settings_in['text_color'] : $defaults['text_color'] ),
            'padding_top'          => max( 0, min( 120, $padding_top ) ),
            'padding_right'        => max( 0, min( 120, $padding_right ) ),
            'padding_bottom'       => max( 0, min( 120, $padding_bottom ) ),
            'padding_left'         => max( 0, min( 120, $padding_left ) ),
            'margin_top'           => max( 0, min( 120, $margin_top ) ),
            'margin_right'         => max( 0, min( 120, $margin_right ) ),
            'margin_bottom'        => max( 0, min( 120, $margin_bottom ) ),
            'margin_left'          => max( 0, min( 120, $margin_left ) ),
            'gap'                  => max( 0, min( 80, isset( $settings_in['gap'] ) ? intval( $settings_in['gap'] ) : $defaults['gap'] ) ),
            'hover_effect'         => $hover_effect,
            'overlay_color'        => $overlay_color,
            'overlay_opacity'      => $overlay_opacity,
            'arrow_enabled'        => ! isset( $settings_in['arrow_enabled'] ) || ! empty( $settings_in['arrow_enabled'] ),
            'arrow_style'          => $arrow_style,
            'arrow_bg_shape'       => $arrow_bg_shape,
            'arrow_visibility'     => $arrow_visibility,
            'arrow_size'           => $arrow_size,
            'arrow_color'          => $arrow_color,
            'arrow_bg_color'       => $arrow_bg_color,
            'arrow_bg_opacity'     => $arrow_bg_opacity,
            'arrow_border_radius'  => $arrow_border_radius,
            'arrow_position_top'   => $arrow_position_top,
            'arrow_position_left'  => $arrow_position_left,
            'arrow_position_right' => $arrow_position_right,
            'arrow_offset_x'       => $arrow_offset_x,
            'arrow_offset_y'       => $arrow_offset_y,
            'arrow_mobile_enabled' => $arrow_mobile_enabled,
            'arrow_mobile_breakpoint' => $arrow_mobile_breakpoint,
            'arrow_mobile_position_top' => $arrow_mobile_position_top,
            'arrow_mobile_position_left' => $arrow_mobile_position_left,
            'arrow_mobile_position_right' => $arrow_mobile_position_right,
            'arrow_mobile_offset_x' => $arrow_mobile_offset_x,
            'arrow_mobile_offset_y' => $arrow_mobile_offset_y,
            'arrow_mobile_size'    => $arrow_mobile_size,
            'arrow_mobile_hide'    => $arrow_mobile_hide,
            'bullets_enabled'      => ! empty( $settings_in['bullets_enabled'] ),
            'bullets_style'        => $bullets_style,
            'bullets_size'         => $bullets_size,
            'bullets_color'        => $bullets_color,
            'bullets_active_color' => $bullets_active_color,
            'bullets_side_arrows'  => $bullets_side_arrows,
            'bullets_arrow_left'   => $bullets_arrow_left,
            'bullets_arrow_right'  => $bullets_arrow_right,
            'bullets_arrow_color'  => $bullets_arrow_color,
            'bullets_arrow_size'   => $bullets_arrow_size,
            'bullets_arrow_position_left' => $bullets_arrow_position_left,
            'bullets_arrow_position_right' => $bullets_arrow_position_right,
            'bullets_arrow_offset_x' => $bullets_arrow_offset_x,
            'bullets_arrow_offset_y' => $bullets_arrow_offset_y,
            'custom_css'           => $this->sanitize_custom_css( isset( $settings_in['custom_css'] ) ? (string) $settings_in['custom_css'] : '' ),
            'stylesheet_url'       => isset( $settings_in['stylesheet_url'] ) ? esc_url_raw( (string) $settings_in['stylesheet_url'] ) : '',
        );
    }

    public function handle_post() {
        if ( ! isset( $_POST['itn_teaser_action'] ) ) {
            return;
        }

        if ( ! $this->current_user_can_manage_plugin() ) {
            return;
        }

        check_admin_referer( 'itn_teaser_save', 'itn_teaser_nonce' );

        $action = sanitize_key( wp_unslash( $_POST['itn_teaser_action'] ) );
        $data   = $this->get_data();
        $tab    = $this->get_current_tab();
        $set_id = isset( $_POST['current_set_id'] ) ? $this->normalize_set_id( wp_unslash( $_POST['current_set_id'] ) ) : $this->default_set_id;

        if ( ! isset( $data['sets'][ $set_id ] ) ) {
            $set_id = $this->default_set_id;
        }

        switch ( $action ) {
            case 'create_set':
                $new_name = isset( $_POST['new_set_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_set_name'] ) ) : '';
                if ( '' === $new_name ) {
                    $new_name = __( 'Neues Teasermodul', 'itn-teaser' );
                }

                $new_set_id = $this->normalize_set_id( $new_name );
                $suffix = 2;
                while ( isset( $data['sets'][ $new_set_id ] ) && $suffix <= 1000 ) {
                    $new_set_id = $this->normalize_set_id( $new_name . '-' . $suffix );
                    $suffix++;
                }

                $data['sets'][ $new_set_id ] = $this->get_default_set( $new_name );
                $data = $this->save_data( $data );
                $set_id = $new_set_id;
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_created', __( 'Teasermodul erstellt.', 'itn-teaser' ), 'updated' );
                break;

            case 'duplicate_set':
                $source_set = $data['sets'][ $set_id ];
                /* translators: %s: original module name */
                $copy_name  = sprintf( __( '%s (Kopie)', 'itn-teaser' ), $source_set['name'] );
                $new_set_id = $this->normalize_set_id( $copy_name );
                $suffix     = 2;
                while ( isset( $data['sets'][ $new_set_id ] ) && $suffix <= 1000 ) {
                    $new_set_id = $this->normalize_set_id( $copy_name . '-' . $suffix );
                    $suffix++;
                }
                $data['sets'][ $new_set_id ] = array(
                    'name'     => $copy_name,
                    'teasers'  => $source_set['teasers'],
                    'settings' => $source_set['settings'],
                );
                $data   = $this->save_data( $data );
                $set_id = $new_set_id;
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_duplicated', __( 'Teasermodul dupliziert.', 'itn-teaser' ), 'updated' );
                break;

            case 'delete_set':
                if ( $this->default_set_id === $set_id ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_delete_default', __( 'Das Standard-Teasermodul kann nicht gelöscht werden.', 'itn-teaser' ), 'error' );
                    break;
                }

                unset( $data['sets'][ $set_id ] );
                foreach ( $data['assignments'] as $page_id => $assigned_set ) {
                    if ( $assigned_set === $set_id ) {
                        unset( $data['assignments'][ $page_id ] );
                    }
                }
                $data = $this->save_data( $data );
                $set_id = $this->default_set_id;
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_deleted', __( 'Teasermodul gelöscht.', 'itn-teaser' ), 'updated' );
                break;

            case 'save_set':
                $set_name = isset( $_POST['set_name'] ) ? sanitize_text_field( wp_unslash( $_POST['set_name'] ) ) : $data['sets'][ $set_id ]['name'];
                $teasers  = $this->sanitize_teasers( isset( $_POST['sventes_teaser'] ) ? wp_unslash( $_POST['sventes_teaser'] ) : array() );
                $source_input = isset( $_POST['sventes_teaser_settings'] ) ? wp_unslash( $_POST['sventes_teaser_settings'] ) : array();
                if ( ! is_array( $source_input ) ) {
                    $source_input = array();
                }
                $merged_settings = array_merge( $data['sets'][ $set_id ]['settings'], $source_input );
                $settings = $this->sanitize_settings( $merged_settings, count( $teasers ) );

                $data['sets'][ $set_id ]['name']    = $set_name ? $set_name : $data['sets'][ $set_id ]['name'];
                $data['sets'][ $set_id ]['teasers'] = $teasers;
                $data['sets'][ $set_id ]['settings'] = $settings;
                $data = $this->save_data( $data );
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_saved', __( 'Teasermodul gespeichert.', 'itn-teaser' ), 'updated' );
                break;

            case 'save_options':
                $settings_input = isset( $_POST['sventes_teaser_settings'] ) ? wp_unslash( $_POST['sventes_teaser_settings'] ) : array();
                if ( ! is_array( $settings_input ) ) {
                    $settings_input = array();
                }
                $settings = $this->sanitize_settings(
                    array_merge( $data['sets'][ $set_id ]['settings'], $settings_input ),
                    count( $data['sets'][ $set_id ]['teasers'] )
                );
                $data['sets'][ $set_id ]['settings'] = $settings;
                $data = $this->save_data( $data );
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_options_saved', __( 'Darstellungsoptionen gespeichert.', 'itn-teaser' ), 'updated' );
                break;

            case 'save_assignments':
                $assignments = array();
                $input_assignments = isset( $_POST['itn_teaser_assignments'] ) ? wp_unslash( $_POST['itn_teaser_assignments'] ) : array();
                if ( is_array( $input_assignments ) ) {
                    foreach ( $input_assignments as $page_id => $assigned_set ) {
                        $page_id = intval( $page_id );
                        $assigned_set = sanitize_title( (string) $assigned_set );
                        if ( $page_id > 0 && $assigned_set && isset( $data['sets'][ $assigned_set ] ) ) {
                            $assignments[ $page_id ] = $assigned_set;
                        }
                    }
                }
                $data['assignments'] = $assignments;
                $data = $this->save_data( $data );
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_assignments_saved', __( 'Zuordnungen gespeichert.', 'itn-teaser' ), 'updated' );
                break;

            case 'save_roles':
                $allowed_roles = array( 'administrator' );
                $requested_roles = isset( $_POST['allowed_roles'] ) ? wp_unslash( $_POST['allowed_roles'] ) : array();
                if ( is_array( $requested_roles ) ) {
                    $editable_roles = array_keys( wp_roles()->roles );
                    foreach ( $requested_roles as $role ) {
                        $role = sanitize_key( $role );
                        if ( in_array( $role, $editable_roles, true ) ) {
                            $allowed_roles[] = $role;
                        }
                    }
                }
                $data['allowed_roles'] = array_values( array_unique( $allowed_roles ) );
                $data['delete_on_uninstall'] = ! empty( $_POST['delete_on_uninstall'] );
                $data = $this->save_data( $data );
                $this->sync_role_caps( $data['allowed_roles'] );
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_roles_saved', __( 'Zugriffsrechte und Deinstallationsoptionen gespeichert.', 'itn-teaser' ), 'updated' );
                break;

            case 'export_sets':
                $selected_sets = isset( $_POST['export_set_ids'] ) ? wp_unslash( $_POST['export_set_ids'] ) : array();
                if ( ! is_array( $selected_sets ) ) {
                    $selected_sets = array();
                }
                
                $export_data = array( 'sets' => array() );
                foreach ( $selected_sets as $export_set_id ) {
                    $export_set_id = $this->normalize_set_id( $export_set_id );
                    if ( isset( $data['sets'][ $export_set_id ] ) ) {
                        $export_data['sets'][ $export_set_id ] = $data['sets'][ $export_set_id ];
                    }
                }
                
                if ( empty( $export_data['sets'] ) ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_export_empty', __( 'Keine Module zum Exportieren ausgewählt.', 'itn-teaser' ), 'error' );
                    break;
                }
                
                header( 'Content-Type: application/json' );
                header( 'Content-Disposition: attachment; filename="teaser-modules-' . current_time( 'Y-m-d-His' ) . '.json"' );
                echo wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                exit;

            case 'import_sets':
                if ( ! isset( $_FILES['import_file'] ) ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_import_missing', __( 'Keine Datei hochgeladen.', 'itn-teaser' ), 'error' );
                    break;
                }
                
                $file = wp_unslash( $_FILES['import_file'] );
                if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_import_upload', __( 'Fehler beim Hochladen der Datei.', 'itn-teaser' ), 'error' );
                    break;
                }
                
                $content = wp_remote_fopen( $file['tmp_name'] );
                if ( ! $content ) {
                    $content = file_get_contents( $file['tmp_name'] );
                }
                
                if ( ! $content ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_import_read', __( 'Datei konnte nicht gelesen werden.', 'itn-teaser' ), 'error' );
                    break;
                }
                
                $import_data = json_decode( $content, true );
                if ( ! is_array( $import_data ) || ! isset( $import_data['sets'] ) ) {
                    add_settings_error( 'itn_teaser_messages', 'itn_teaser_import_invalid', __( 'Ungültiges Dateiformat.', 'itn-teaser' ), 'error' );
                    break;
                }
                
                $import_count = 0;
                foreach ( $import_data['sets'] as $import_set_id => $import_set ) {
                    $import_set_id = $this->normalize_set_id( $import_set_id );
                    if ( ! isset( $data['sets'][ $import_set_id ] ) ) {
                        $data['sets'][ $import_set_id ] = $this->normalize_set( $import_set, ucfirst( str_replace( '-', ' ', $import_set_id ) ) );
                        $import_count++;
                    }
                }
                
                $data = $this->save_data( $data );
                /* translators: %d: number of imported modules */
                add_settings_error( 'itn_teaser_messages', 'itn_teaser_imported', sprintf( __( '%d Module importiert (vorhandene Module nicht überschrieben).', 'itn-teaser' ), $import_count ), 'updated' );
                break;
        }

        $redirect_args = array(
            'page' => $this->menu_slug,
            'tab'  => $tab,
        );

        if ( in_array( $tab, array( 'teaser', 'navigationspfeile', 'bullets', 'allgemein' ), true ) ) {
            $redirect_args['set'] = $set_id;
        }

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    private function get_tab_url( $tab, $set_id = '' ) {
        $args = array(
            'page' => $this->menu_slug,
            'tab'  => $tab,
        );

        if ( $set_id ) {
            $args['set'] = $set_id;
        }

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    private function render_set_switcher( $set_id, $sets, $tab ) {
        ?>
        <form method="get" class="itn-teaser-set-switcher js-itn-teaser-set-switcher">
            <input type="hidden" name="page" value="<?php echo esc_attr( $this->menu_slug ); ?>" />
            <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
            <label for="itn-teaser-current-set"><strong><?php esc_html_e( 'Teasermodul', 'itn-teaser' ); ?></strong></label>
            <select name="set" id="itn-teaser-current-set">
                <?php foreach ( $sets as $available_set_id => $set ) : ?>
                    <option value="<?php echo esc_attr( $available_set_id ); ?>" <?php selected( $set_id, $available_set_id ); ?>><?php echo esc_html( $set['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( __( 'Teasermodul wechseln', 'itn-teaser' ), 'secondary', '', false ); ?>
        </form>
        <?php
    }

    private function get_teaser_source_post_type_options() {
        $options = array(
            '' => __( 'Bitte auswählen', 'itn-teaser' ),
        );
        $post_types = get_post_types(
            array(
                'public' => true,
            ),
            'objects'
        );

        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type->name ) {
                continue;
            }
            $options[ $post_type->name ] = $post_type->labels->singular_name;
        }

        return $options;
    }

    private function get_teaser_category_choices( $post_type ) {
        $choices = array();

        if ( ! $post_type || ! post_type_exists( $post_type ) ) {
            return $choices;
        }

        $posts = get_posts(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'numberposts'    => $this->max_auto_posts,
                'fields'         => 'ids',
                'suppress_filters' => false,
            )
        );

        if ( empty( $posts ) ) {
            return $choices;
        }

        if ( function_exists( 'get_field_object' ) ) {
            foreach ( $posts as $post_id ) {
                $field_object = get_field_object( 'teaserkategorie', $post_id );
                if ( is_array( $field_object ) && ! empty( $field_object['choices'] ) && is_array( $field_object['choices'] ) ) {
                    foreach ( $field_object['choices'] as $value => $label ) {
                        $value = sanitize_text_field( (string) $value );
                        if ( '' !== $value ) {
                            $choices[ $value ] = sanitize_text_field( (string) $label );
                        }
                    }
                    break;
                }
            }
        }

        foreach ( $posts as $post_id ) {
            $raw_value = get_post_meta( $post_id, 'teaserkategorie', true );
            $values = is_array( $raw_value ) ? $raw_value : array( $raw_value );
            foreach ( $values as $value ) {
                $value = sanitize_text_field( (string) $value );
                if ( '' !== $value && ! isset( $choices[ $value ] ) ) {
                    $choices[ $value ] = $value;
                }
            }
        }

        return $choices;
    }

    private function get_auto_teasers_from_acf( $settings ) {
        $post_type = isset( $settings['teaser_source_post_type'] ) ? sanitize_key( $settings['teaser_source_post_type'] ) : '';
        if ( ! $post_type || ! post_type_exists( $post_type ) ) {
            return array();
        }

        $category_filter = isset( $settings['teaser_source_category'] ) ? sanitize_text_field( (string) $settings['teaser_source_category'] ) : '';
        $source_orderby = isset( $settings['teaser_source_orderby'] ) ? sanitize_key( $settings['teaser_source_orderby'] ) : 'date';
        if ( ! in_array( $source_orderby, array( 'date', 'title', 'menu_order' ), true ) ) {
            $source_orderby = 'date';
        }
        $source_order = isset( $settings['teaser_source_order'] ) ? strtoupper( sanitize_key( $settings['teaser_source_order'] ) ) : 'DESC';
        if ( ! in_array( $source_order, array( 'ASC', 'DESC' ), true ) ) {
            $source_order = 'DESC';
        }
        $query_args = array(
            'post_type'           => $post_type,
            'post_status'         => 'publish',
            'posts_per_page'      => $this->max_auto_posts,
            'orderby'             => $source_orderby,
            'order'               => $source_order,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        );

        if ( '' !== $category_filter ) {
            $like_category_filter = $category_filter;
            if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) && method_exists( $GLOBALS['wpdb'], 'esc_like' ) ) {
                $like_category_filter = $GLOBALS['wpdb']->esc_like( $category_filter );
            }
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'teaserkategorie',
                    'value'   => '"' . $like_category_filter . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'teaserkategorie',
                    'value'   => $category_filter,
                    'compare' => '=',
                ),
            );
        }

        $posts = get_posts( $query_args );
        if ( empty( $posts ) ) {
            return array();
        }

        $teasers = array();
        foreach ( $posts as $post ) {
            $post_id = $post->ID;
            $image_field = function_exists( 'get_field' ) ? get_field( 'teaserbild', $post_id ) : get_post_meta( $post_id, 'teaserbild', true );
            $image_url = '';

            if ( is_array( $image_field ) && ! empty( $image_field['url'] ) ) {
                $image_url = esc_url_raw( $image_field['url'] );
            } elseif ( is_numeric( $image_field ) ) {
                $image_url = wp_get_attachment_image_url( intval( $image_field ), 'full' ) ?: '';
            } elseif ( is_string( $image_field ) ) {
                $image_url = esc_url_raw( $image_field );
            }

            $text_field = function_exists( 'get_field' ) ? get_field( 'teasertext', $post_id ) : get_post_meta( $post_id, 'teasertext', true );
            $content = is_string( $text_field ) ? wp_kses_post( $text_field ) : '';
            $url = get_permalink( $post_id );

            if ( ! $image_url && ! $content ) {
                continue;
            }

            $teasers[] = array(
                'image_id'    => '',
                'image_url'   => $image_url,
                'content'     => $content,
                'url'         => $url ? esc_url_raw( $url ) : '',
                'target'      => '_self',
                'link_mode'   => 'image',
                'btn1_label'  => '',
                'btn1_url'    => $url ? esc_url_raw( $url ) : '',
                'btn1_target' => '_self',
                'btn2_label'  => '',
                'btn2_url'    => '',
                'btn2_target' => '_self',
                'auto_generated' => true,
            );
        }

        return $teasers;
    }


    public function render_admin_page() {
        if ( ! $this->current_user_can_manage_plugin() ) {
            wp_die( esc_html__( 'Du hast keine Berechtigung, auf ITN Teaser zuzugreifen.', 'itn-teaser' ) );
        }

        $data     = $this->get_data();
        $sets     = $data['sets'];
        $tab      = $this->get_current_tab();
        $set_id   = $this->get_current_set_id( $sets );
        $set      = $sets[ $set_id ];
        $settings = $set['settings'];
        $tabs     = array(
            'teaser'            => esc_html__( 'Teaser', 'itn-teaser' ),
            'navigationspfeile' => esc_html__( 'Navigationspfeile', 'itn-teaser' ),
            'bullets'           => esc_html__( 'Bullets', 'itn-teaser' ),
            'allgemein'         => esc_html__( 'Allgemein', 'itn-teaser' ),
            'export'            => esc_html__( 'Export / Import', 'itn-teaser' ),
            'permissions'       => esc_html__( 'Rechte', 'itn-teaser' ),
        );
        $set_tabs = array( 'teaser', 'navigationspfeile', 'bullets', 'allgemein' );

        settings_errors( 'itn_teaser_messages' );
        ?>
        <div class="wrap sventes-teaser-admin">
            <h1><?php esc_html_e( 'ITN Teaser', 'itn-teaser' ); ?></h1>
            <p><?php esc_html_e( 'Verwalte mehrere Teasermodule, ordne sie Seiten zu und nutze sie im Shortcode oder Gutenberg-Block.', 'itn-teaser' ); ?></p>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                    <?php $tab_set_id = in_array( $tab_key, $set_tabs, true ) ? $set_id : ''; ?>
                    <a href="<?php echo esc_url( $this->get_tab_url( $tab_key, $tab_set_id ) ); ?>" class="nav-tab <?php echo $tab_key === $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $tab_label ); ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="itn-teaser-tab-panel">
                <?php if ( in_array( $tab, $set_tabs, true ) ) : ?>
                    <?php $this->render_set_switcher( $set_id, $sets, $tab ); ?>
                    <?php $this->render_set_shortcode( $set_id ); ?>
                <?php endif; ?>

                <?php
                switch ( $tab ) {
                    case 'navigationspfeile':
                        $this->render_navigationspfeile_tab( $set_id, $settings );
                        break;
                    case 'bullets':
                        $this->render_bullets_tab( $set_id, $settings );
                        break;
                    case 'allgemein':
                        $this->render_allgemein_tab( $set_id, $set, $settings );
                        break;
                    case 'export':
                        $this->render_export_tab( $sets );
                        break;
                    case 'permissions':
                        $this->render_permissions_tab( $data );
                        break;
                    case 'teaser':
                    default:
                        $this->render_teaser_tab( $set_id, $set );
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_teaser_tab( $set_id, $set ) {
        $settings = isset( $set['settings'] ) && is_array( $set['settings'] ) ? $set['settings'] : $this->get_default_settings();
        $source_mode = isset( $settings['teaser_source_mode'] ) ? $settings['teaser_source_mode'] : 'manual';
        $source_post_type = isset( $settings['teaser_source_post_type'] ) ? $settings['teaser_source_post_type'] : '';
        $source_category = isset( $settings['teaser_source_category'] ) ? $settings['teaser_source_category'] : '';
        $source_orderby = isset( $settings['teaser_source_orderby'] ) ? $settings['teaser_source_orderby'] : 'date';
        $source_order = isset( $settings['teaser_source_order'] ) ? $settings['teaser_source_order'] : 'DESC';
        $auto_button_label = $this->get_auto_button_label_setting( $settings );
        $source_post_type_options = $this->get_teaser_source_post_type_options();
        $source_category_options = $this->get_teaser_category_choices( $source_post_type );
        ?>
        <div class="itn-teaser-actions-grid">
            <form method="post" action="" class="itn-teaser-inline-form">
                <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
                <input type="hidden" name="itn_teaser_action" value="create_set" />
                <input type="text" name="new_set_name" class="regular-text" placeholder="<?php esc_attr_e( 'Neuer Name für das Teasermodul', 'itn-teaser' ); ?>" />
                <?php submit_button( esc_html__( 'Neues Teasermodul anlegen', 'itn-teaser' ), 'secondary', '', false ); ?>
            </form>

            <form method="post" action="" class="itn-teaser-inline-form">
                <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
                <input type="hidden" name="itn_teaser_action" value="duplicate_set" />
                <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />
                <?php submit_button( esc_html__( 'Aktuelles Teasermodul duplizieren', 'itn-teaser' ), 'secondary', '', false ); ?>
            </form>

            <?php if ( $this->default_set_id !== $set_id ) : ?>
                <form method="post" action="" class="js-itn-teaser-delete-set" data-confirm-message="<?php echo esc_attr( __( 'Dieses Teasermodul wirklich löschen?', 'itn-teaser' ) ); ?>">
                    <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
                    <input type="hidden" name="itn_teaser_action" value="delete_set" />
                    <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />
                    <?php submit_button( esc_html__( 'Aktuelles Teasermodul löschen', 'itn-teaser' ), 'delete', '', false ); ?>
                </form>
            <?php endif; ?>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="save_set" />
            <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="itn-teaser-set-name"><?php esc_html_e( 'Name des Teasermoduls', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-set-name" name="set_name" class="regular-text" value="<?php echo esc_attr( $set['name'] ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-source-mode"><?php esc_html_e( 'Darstellungsart', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-source-mode" name="sventes_teaser_settings[teaser_source_mode]">
                            <option value="manual" <?php selected( $source_mode, 'manual' ); ?>><?php esc_html_e( 'Manuell gepflegte Teaser', 'itn-teaser' ); ?></option>
                            <option value="acf_posts" <?php selected( $source_mode, 'acf_posts' ); ?>><?php esc_html_e( 'Automatisch aus Beiträgen (ACF)', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-source-post-type"><?php esc_html_e( 'Inhaltstyp', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-source-post-type" name="sventes_teaser_settings[teaser_source_post_type]">
                            <?php foreach ( $source_post_type_options as $post_type_value => $post_type_label ) : ?>
                                <option value="<?php echo esc_attr( $post_type_value ); ?>" <?php selected( $source_post_type, $post_type_value ); ?>><?php echo esc_html( $post_type_label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-source-category"><?php esc_html_e( 'Teaserkategorie (optional)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-source-category" name="sventes_teaser_settings[teaser_source_category]">
                            <option value=""><?php esc_html_e( 'Keine Filterung', 'itn-teaser' ); ?></option>
                            <?php foreach ( $source_category_options as $category_value => $category_label ) : ?>
                                <option value="<?php echo esc_attr( $category_value ); ?>" <?php selected( $source_category, $category_value ); ?>><?php echo esc_html( $category_label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Optional nach dem ACF-Feld teaserkategorie (Auswahlkästchen) filtern.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr class="js-itn-teaser-auto-fields" <?php if ( 'acf_posts' !== $source_mode ) : ?>style="display:none;"<?php endif; ?>>
                    <th scope="row"><label for="itn-teaser-source-orderby"><?php esc_html_e( 'Sortieren nach', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-source-orderby" name="sventes_teaser_settings[teaser_source_orderby]">
                            <option value="date" <?php selected( $source_orderby, 'date' ); ?>><?php esc_html_e( 'Veröffentlichungsdatum', 'itn-teaser' ); ?></option>
                            <option value="title" <?php selected( $source_orderby, 'title' ); ?>><?php esc_html_e( 'Titel', 'itn-teaser' ); ?></option>
                            <option value="menu_order" <?php selected( $source_orderby, 'menu_order' ); ?>><?php esc_html_e( 'Manuelle Reihenfolge (WordPress)', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="js-itn-teaser-auto-fields" <?php if ( 'acf_posts' !== $source_mode ) : ?>style="display:none;"<?php endif; ?>>
                    <th scope="row"><label for="itn-teaser-source-order"><?php esc_html_e( 'Sortierreihenfolge', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-source-order" name="sventes_teaser_settings[teaser_source_order]">
                            <option value="DESC" <?php selected( $source_order, 'DESC' ); ?>>DESC</option>
                            <option value="ASC" <?php selected( $source_order, 'ASC' ); ?>>ASC</option>
                        </select>
                    </td>
                </tr>
                <tr class="js-itn-teaser-auto-fields" <?php if ( 'acf_posts' !== $source_mode ) : ?>style="display:none;"<?php endif; ?>>
                    <th scope="row"><label for="itn-teaser-auto-button-label"><?php esc_html_e( 'Button-Text (Automatik)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-auto-button-label" name="sventes_teaser_settings[teaser_auto_button_label]" class="regular-text" value="<?php echo esc_attr( $auto_button_label ); ?>" />
                    </td>
                </tr>
            </table>

            <p id="itn-teaser-acf-hint" <?php if ( 'acf_posts' !== $source_mode ) : ?>style="display:none;"<?php endif; ?>>
                <?php esc_html_e( 'Bitte zuerst einen Inhaltstyp mit der Feldgruppe Teaserbild (Bild-Array), Teasertext und Teaserkategorie (Auswahlkästchen) erstellen', 'itn-teaser' ); ?>
            </p>

            <div class="js-itn-teaser-manual-fields" <?php if ( 'manual' !== $source_mode ) : ?>style="display:none;"<?php endif; ?>>
            <p>
                <button type="button" class="button button-primary" id="sventes-add-teaser"><?php esc_html_e( 'Teaser hinzufügen', 'itn-teaser' ); ?></button>
            </p>

            <div id="sventes-teaser-list" class="sventes-teaser-list">
                <?php foreach ( $set['teasers'] as $i => $item ) : ?>
                    <?php $preview = $item['image_url'] ? esc_url( $item['image_url'] ) : ''; ?>
                    <?php $editor_id = sprintf( 'sventes_teaser_content_%s_%d', sanitize_html_class( $set_id ), $i ); ?>
                    <div class="sventes-teaser-item" data-index="<?php echo esc_attr( $i ); ?>">
                        <h3><?php printf( esc_html__( 'Teaser %d', 'itn-teaser' ), $i + 1 ); ?></h3>

                        <div class="sventes-field sventes-image-field">
                            <label><?php esc_html_e( 'Bild', 'itn-teaser' ); ?></label>
                            <div class="sventes-image-preview" data-index="<?php echo esc_attr( $i ); ?>">
                                <?php if ( $preview ) : ?>
                                    <img src="<?php echo $preview; ?>" alt="" style="max-width:200px;height:auto;" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][image_id]" value="<?php echo esc_attr( $item['image_id'] ); ?>" class="sventes-image-id" />
                            <input type="text" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][image_url]" value="<?php echo esc_attr( $item['image_url'] ); ?>" placeholder="<?php esc_attr_e( 'oder direkte Bild-URL', 'itn-teaser' ); ?>" class="regular-text sventes-image-url" />
                            <p>
                                <button type="button" class="button sventes-select-image"><?php esc_html_e( 'Bild wählen', 'itn-teaser' ); ?></button>
                                <button type="button" class="button sventes-remove-image"><?php esc_html_e( 'Bild entfernen', 'itn-teaser' ); ?></button>
                            </p>
                        </div>

                        <div class="sventes-field sventes-content-field">
                            <label><?php esc_html_e( 'Text', 'itn-teaser' ); ?></label>
                            <?php
                            wp_editor(
                                $item['content'],
                                $editor_id,
                                array(
                                    'textarea_name' => "sventes_teaser[$i][content]",
                                    'textarea_rows' => 6,
                                    'teeny'         => false,
                                )
                            );
                            ?>
                        </div>

                        <div class="sventes-field sventes-url-field">
                            <label><?php esc_html_e( 'Ziel-URL', 'itn-teaser' ); ?></label><br/>
                            <input type="url" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][url]" value="<?php echo esc_attr( $item['url'] ); ?>" class="regular-text" />
                            <select name="sventes_teaser[<?php echo esc_attr( $i ); ?>][target]">
                                <option value="_self" <?php selected( $item['target'], '_self' ); ?>><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                                <option value="_blank" <?php selected( $item['target'], '_blank' ); ?>><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                            </select>
                        </div>

                        <div class="sventes-field sventes-linkmode-field">
                            <label><?php esc_html_e( 'Verlinkung', 'itn-teaser' ); ?></label><br/>
                            <select name="sventes_teaser[<?php echo esc_attr( $i ); ?>][link_mode]">
                                <option value="full" <?php selected( isset( $item['link_mode'] ) ? $item['link_mode'] : 'full', 'full' ); ?>><?php esc_html_e( 'Ganzer Teaser verlinkt', 'itn-teaser' ); ?></option>
                                <option value="image" <?php selected( isset( $item['link_mode'] ) ? $item['link_mode'] : '', 'image' ); ?>><?php esc_html_e( 'Nur Bild verlinkt', 'itn-teaser' ); ?></option>
                                <option value="none" <?php selected( isset( $item['link_mode'] ) ? $item['link_mode'] : '', 'none' ); ?>><?php esc_html_e( 'Kein Link', 'itn-teaser' ); ?></option>
                            </select>
                        </div>

                        <div class="sventes-field sventes-buttons-field">
                            <label><strong><?php esc_html_e( 'Button 1 (optional)', 'itn-teaser' ); ?></strong></label><br/>
                            <input type="text" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn1_label]" value="<?php echo esc_attr( isset( $item['btn1_label'] ) ? $item['btn1_label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Beschriftung', 'itn-teaser' ); ?>" class="regular-text" />
                            <input type="url" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn1_url]" value="<?php echo esc_attr( isset( $item['btn1_url'] ) ? $item['btn1_url'] : '' ); ?>" placeholder="<?php esc_attr_e( 'URL', 'itn-teaser' ); ?>" class="regular-text" />
                            <select name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn1_target]">
                                <option value="_self" <?php selected( isset( $item['btn1_target'] ) ? $item['btn1_target'] : '_self', '_self' ); ?>><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                                <option value="_blank" <?php selected( isset( $item['btn1_target'] ) ? $item['btn1_target'] : '', '_blank' ); ?>><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                            </select>
                        </div>

                        <div class="sventes-field sventes-buttons-field">
                            <label><strong><?php esc_html_e( 'Button 2 (optional)', 'itn-teaser' ); ?></strong></label><br/>
                            <input type="text" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn2_label]" value="<?php echo esc_attr( isset( $item['btn2_label'] ) ? $item['btn2_label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Beschriftung', 'itn-teaser' ); ?>" class="regular-text" />
                            <input type="url" name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn2_url]" value="<?php echo esc_attr( isset( $item['btn2_url'] ) ? $item['btn2_url'] : '' ); ?>" placeholder="<?php esc_attr_e( 'URL', 'itn-teaser' ); ?>" class="regular-text" />
                            <select name="sventes_teaser[<?php echo esc_attr( $i ); ?>][btn2_target]">
                                <option value="_self" <?php selected( isset( $item['btn2_target'] ) ? $item['btn2_target'] : '_self', '_self' ); ?>><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                                <option value="_blank" <?php selected( isset( $item['btn2_target'] ) ? $item['btn2_target'] : '', '_blank' ); ?>><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                            </select>
                        </div>

                        <p>
                            <button type="button" class="button sventes-remove-teaser"><?php esc_html_e( 'Teaser entfernen', 'itn-teaser' ); ?></button>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>

            <?php submit_button( esc_html__( 'Teasermodul speichern', 'itn-teaser' ) ); ?>
        </form>

        <div id="sventes-teaser-template" style="display:none;">
            <div class="sventes-teaser-item" data-index="__INDEX__">
                <h3><?php esc_html_e( 'Neuer Teaser', 'itn-teaser' ); ?></h3>

                <div class="sventes-field sventes-image-field">
                    <label><?php esc_html_e( 'Bild', 'itn-teaser' ); ?></label>
                    <div class="sventes-image-preview" data-index="__INDEX__"></div>
                    <input type="hidden" name="sventes_teaser[__INDEX__][image_id]" value="" class="sventes-image-id" />
                    <input type="text" name="sventes_teaser[__INDEX__][image_url]" value="" placeholder="<?php esc_attr_e( 'oder direkte Bild-URL', 'itn-teaser' ); ?>" class="regular-text sventes-image-url" />
                    <p>
                        <button type="button" class="button sventes-select-image"><?php esc_html_e( 'Bild wählen', 'itn-teaser' ); ?></button>
                        <button type="button" class="button sventes-remove-image"><?php esc_html_e( 'Bild entfernen', 'itn-teaser' ); ?></button>
                    </p>
                </div>

                <div class="sventes-field sventes-content-field">
                    <label><?php esc_html_e( 'Text', 'itn-teaser' ); ?></label>
                    <textarea id="__EDITOR_ID__" name="sventes_teaser[__INDEX__][content]" rows="6"></textarea>
                </div>

                <div class="sventes-field sventes-url-field">
                    <label><?php esc_html_e( 'Ziel-URL', 'itn-teaser' ); ?></label><br/>
                    <input type="url" name="sventes_teaser[__INDEX__][url]" value="" class="regular-text" />
                    <select name="sventes_teaser[__INDEX__][target]">
                        <option value="_self"><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                    </select>
                </div>

                <div class="sventes-field sventes-linkmode-field">
                    <label><?php esc_html_e( 'Verlinkung', 'itn-teaser' ); ?></label><br/>
                    <select name="sventes_teaser[__INDEX__][link_mode]">
                        <option value="full"><?php esc_html_e( 'Ganzer Teaser verlinkt', 'itn-teaser' ); ?></option>
                        <option value="image"><?php esc_html_e( 'Nur Bild verlinkt', 'itn-teaser' ); ?></option>
                        <option value="none"><?php esc_html_e( 'Kein Link', 'itn-teaser' ); ?></option>
                    </select>
                </div>

                <div class="sventes-field sventes-buttons-field">
                    <label><strong><?php esc_html_e( 'Button 1 (optional)', 'itn-teaser' ); ?></strong></label><br/>
                    <input type="text" name="sventes_teaser[__INDEX__][btn1_label]" value="" placeholder="<?php esc_attr_e( 'Beschriftung', 'itn-teaser' ); ?>" class="regular-text" />
                    <input type="url" name="sventes_teaser[__INDEX__][btn1_url]" value="" placeholder="<?php esc_attr_e( 'URL', 'itn-teaser' ); ?>" class="regular-text" />
                    <select name="sventes_teaser[__INDEX__][btn1_target]">
                        <option value="_self"><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                    </select>
                </div>

                <div class="sventes-field sventes-buttons-field">
                    <label><strong><?php esc_html_e( 'Button 2 (optional)', 'itn-teaser' ); ?></strong></label><br/>
                    <input type="text" name="sventes_teaser[__INDEX__][btn2_label]" value="" placeholder="<?php esc_attr_e( 'Beschriftung', 'itn-teaser' ); ?>" class="regular-text" />
                    <input type="url" name="sventes_teaser[__INDEX__][btn2_url]" value="" placeholder="<?php esc_attr_e( 'URL', 'itn-teaser' ); ?>" class="regular-text" />
                    <select name="sventes_teaser[__INDEX__][btn2_target]">
                        <option value="_self"><?php esc_html_e( 'Selbes Fenster', 'itn-teaser' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'Neues Fenster', 'itn-teaser' ); ?></option>
                    </select>
                </div>

                <p>
                    <button type="button" class="button sventes-remove-teaser"><?php esc_html_e( 'Teaser entfernen', 'itn-teaser' ); ?></button>
                </p>
            </div>
        </div>
        <?php
    }

    private function render_navigationspfeile_tab( $set_id, $settings ) {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="save_options" />
            <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />

            <h2><?php esc_html_e( 'Navigationspfeile', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Pfeile anzeigen', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="hidden" name="sventes_teaser_settings[arrow_enabled]" value="0" /><input type="checkbox" name="sventes_teaser_settings[arrow_enabled]" value="1" <?php checked( ! isset( $settings['arrow_enabled'] ) || ! empty( $settings['arrow_enabled'] ) ); ?> /> <?php esc_html_e( 'Navigationspfeile anzeigen', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-style"><?php esc_html_e( 'Pfeil-Art', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-arrow-style" name="sventes_teaser_settings[arrow_style]">
                            <option value="chevron" <?php selected( isset( $settings['arrow_style'] ) ? $settings['arrow_style'] : 'chevron', 'chevron' ); ?>><?php esc_html_e( 'Chevron', 'itn-teaser' ); ?></option>
                            <option value="arrow" <?php selected( isset( $settings['arrow_style'] ) ? $settings['arrow_style'] : '', 'arrow' ); ?>><?php esc_html_e( 'Pfeil', 'itn-teaser' ); ?></option>
                            <option value="caret" <?php selected( isset( $settings['arrow_style'] ) ? $settings['arrow_style'] : '', 'caret' ); ?>><?php esc_html_e( 'Caret', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-bg-shape"><?php esc_html_e( 'Hintergrundform', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-arrow-bg-shape" name="sventes_teaser_settings[arrow_bg_shape]">
                            <option value="rounded" <?php selected( isset( $settings['arrow_bg_shape'] ) ? $settings['arrow_bg_shape'] : 'rounded', 'rounded' ); ?>><?php esc_html_e( 'Abgerundet', 'itn-teaser' ); ?></option>
                            <option value="round" <?php selected( isset( $settings['arrow_bg_shape'] ) ? $settings['arrow_bg_shape'] : '', 'round' ); ?>><?php esc_html_e( 'Rund', 'itn-teaser' ); ?></option>
                            <option value="square" <?php selected( isset( $settings['arrow_bg_shape'] ) ? $settings['arrow_bg_shape'] : '', 'square' ); ?>><?php esc_html_e( 'Eckig', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-visibility"><?php esc_html_e( 'Sichtbarkeit', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-arrow-visibility" name="sventes_teaser_settings[arrow_visibility]">
                            <option value="always" <?php selected( isset( $settings['arrow_visibility'] ) ? $settings['arrow_visibility'] : 'always', 'always' ); ?>><?php esc_html_e( 'Immer sichtbar', 'itn-teaser' ); ?></option>
                            <option value="hover" <?php selected( isset( $settings['arrow_visibility'] ) ? $settings['arrow_visibility'] : '', 'hover' ); ?>><?php esc_html_e( 'Nur bei Mouseover/Fokus', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-size"><?php esc_html_e( 'Größe (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-size" name="sventes_teaser_settings[arrow_size]" value="<?php echo esc_attr( isset( $settings['arrow_size'] ) ? $settings['arrow_size'] : 44 ); ?>" class="small-text" min="20" max="120" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-color"><?php esc_html_e( 'Icon-Farbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-arrow-color" name="sventes_teaser_settings[arrow_color]" value="<?php echo esc_attr( isset( $settings['arrow_color'] ) ? $settings['arrow_color'] : '#ffffff' ); ?>" class="regular-text" placeholder="#ffffff" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-bg-color"><?php esc_html_e( 'Hintergrundfarbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-arrow-bg-color" name="sventes_teaser_settings[arrow_bg_color]" value="<?php echo esc_attr( isset( $settings['arrow_bg_color'] ) ? $settings['arrow_bg_color'] : '#000000' ); ?>" class="regular-text" placeholder="#000000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-bg-opacity"><?php esc_html_e( 'Hintergrund-Deckkraft (%)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-bg-opacity" name="sventes_teaser_settings[arrow_bg_opacity]" value="<?php echo esc_attr( isset( $settings['arrow_bg_opacity'] ) ? $settings['arrow_bg_opacity'] : 60 ); ?>" class="small-text" min="0" max="100" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-border-radius"><?php esc_html_e( 'Border-Radius (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-border-radius" name="sventes_teaser_settings[arrow_border_radius]" value="<?php echo esc_attr( isset( $settings['arrow_border_radius'] ) ? $settings['arrow_border_radius'] : 22 ); ?>" class="small-text" min="0" max="100" />
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Positionierung Desktop', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="itn-arrow-position-top"><?php esc_html_e( 'Position oben (%)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-position-top" name="sventes_teaser_settings[arrow_position_top]" value="<?php echo esc_attr( isset( $settings['arrow_position_top'] ) ? $settings['arrow_position_top'] : 50 ); ?>" class="small-text" min="-1" max="200" />
                        <p class="description"><?php esc_html_e( '-1 übernimmt die automatische Positionierung.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-position-left"><?php esc_html_e( 'Linker Pfeil Abstand links (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-position-left" name="sventes_teaser_settings[arrow_position_left]" value="<?php echo esc_attr( isset( $settings['arrow_position_left'] ) ? $settings['arrow_position_left'] : 10 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-position-right"><?php esc_html_e( 'Rechter Pfeil Abstand rechts (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-position-right" name="sventes_teaser_settings[arrow_position_right]" value="<?php echo esc_attr( isset( $settings['arrow_position_right'] ) ? $settings['arrow_position_right'] : 10 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-offset-x"><?php esc_html_e( 'Horizontaler Offset (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-offset-x" name="sventes_teaser_settings[arrow_offset_x]" value="<?php echo esc_attr( isset( $settings['arrow_offset_x'] ) ? $settings['arrow_offset_x'] : 0 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-offset-y"><?php esc_html_e( 'Vertikaler Offset (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-offset-y" name="sventes_teaser_settings[arrow_offset_y]" value="<?php echo esc_attr( isset( $settings['arrow_offset_y'] ) ? $settings['arrow_offset_y'] : 0 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Mobile Overrides', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Eigene mobile Werte aktivieren', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[arrow_mobile_enabled]" value="1" <?php checked( ! empty( $settings['arrow_mobile_enabled'] ) ); ?> /> <?php esc_html_e( 'Mobile Pfeil-Positionen separat konfigurieren', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-breakpoint"><?php esc_html_e( 'Breakpoint (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-breakpoint" name="sventes_teaser_settings[arrow_mobile_breakpoint]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_breakpoint'] ) ? $settings['arrow_mobile_breakpoint'] : 768 ); ?>" class="small-text" min="320" max="5000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-position-top"><?php esc_html_e( 'Position oben (%)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-position-top" name="sventes_teaser_settings[arrow_mobile_position_top]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_position_top'] ) ? $settings['arrow_mobile_position_top'] : 50 ); ?>" class="small-text" min="-1" max="200" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-position-left"><?php esc_html_e( 'Linker Pfeil Abstand links (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-position-left" name="sventes_teaser_settings[arrow_mobile_position_left]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_position_left'] ) ? $settings['arrow_mobile_position_left'] : 5 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-position-right"><?php esc_html_e( 'Rechter Pfeil Abstand rechts (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-position-right" name="sventes_teaser_settings[arrow_mobile_position_right]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_position_right'] ) ? $settings['arrow_mobile_position_right'] : 5 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-offset-x"><?php esc_html_e( 'Horizontaler Offset (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-offset-x" name="sventes_teaser_settings[arrow_mobile_offset_x]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_offset_x'] ) ? $settings['arrow_mobile_offset_x'] : 0 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-offset-y"><?php esc_html_e( 'Vertikaler Offset (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-offset-y" name="sventes_teaser_settings[arrow_mobile_offset_y]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_offset_y'] ) ? $settings['arrow_mobile_offset_y'] : 0 ); ?>" class="small-text" min="-500" max="500" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-arrow-mobile-size"><?php esc_html_e( 'Größe mobil (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-arrow-mobile-size" name="sventes_teaser_settings[arrow_mobile_size]" value="<?php echo esc_attr( isset( $settings['arrow_mobile_size'] ) ? $settings['arrow_mobile_size'] : 36 ); ?>" class="small-text" min="20" max="120" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Pfeile mobil ausblenden', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[arrow_mobile_hide]" value="1" <?php checked( ! empty( $settings['arrow_mobile_hide'] ) ); ?> /> <?php esc_html_e( 'Navigationspfeile auf kleinen Geräten ausblenden', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
            </table>

            <?php submit_button( esc_html__( 'Navigationspfeile speichern', 'itn-teaser' ) ); ?>
        </form>
        <?php
    }

    private function render_bullets_tab( $set_id, $settings ) {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="save_options" />
            <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />

            <h2><?php esc_html_e( 'Bullets / Pagination', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Bullets anzeigen', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[bullets_enabled]" value="1" <?php checked( ! empty( $settings['bullets_enabled'] ) ); ?> /> <?php esc_html_e( 'Bullets/Pagination-Dots anzeigen', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-style"><?php esc_html_e( 'Bullets-Stil', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-bullets-style" name="sventes_teaser_settings[bullets_style]">
                            <option value="dots" <?php selected( isset( $settings['bullets_style'] ) ? $settings['bullets_style'] : 'dots', 'dots' ); ?>><?php esc_html_e( 'Kreise (Dots)', 'itn-teaser' ); ?></option>
                            <option value="squares" <?php selected( isset( $settings['bullets_style'] ) ? $settings['bullets_style'] : '', 'squares' ); ?>><?php esc_html_e( 'Quadrate', 'itn-teaser' ); ?></option>
                            <option value="lines" <?php selected( isset( $settings['bullets_style'] ) ? $settings['bullets_style'] : '', 'lines' ); ?>><?php esc_html_e( 'Linien', 'itn-teaser' ); ?></option>
                            <option value="hollow" <?php selected( isset( $settings['bullets_style'] ) ? $settings['bullets_style'] : '', 'hollow' ); ?>><?php esc_html_e( 'Hohler Punkt', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-size"><?php esc_html_e( 'Bullets-Größe (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-size" name="sventes_teaser_settings[bullets_size]" value="<?php echo esc_attr( isset( $settings['bullets_size'] ) ? $settings['bullets_size'] : 10 ); ?>" class="small-text" min="4" max="40" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-color"><?php esc_html_e( 'Bullets-Farbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-bullets-color" name="sventes_teaser_settings[bullets_color]" value="<?php echo esc_attr( isset( $settings['bullets_color'] ) ? $settings['bullets_color'] : '#cccccc' ); ?>" class="regular-text" placeholder="#cccccc" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-active-color"><?php esc_html_e( 'Aktive Farbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-bullets-active-color" name="sventes_teaser_settings[bullets_active_color]" value="<?php echo esc_attr( isset( $settings['bullets_active_color'] ) ? $settings['bullets_active_color'] : '#333333' ); ?>" class="regular-text" placeholder="#333333" />
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Seitliche Pfeile bei den Bullets', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Seitliche Pfeile aktivieren', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[bullets_side_arrows]" value="1" <?php checked( ! empty( $settings['bullets_side_arrows'] ) ); ?> /> <?php esc_html_e( 'Vor- und Zurück-Pfeile neben den Bullets anzeigen', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-left"><?php esc_html_e( 'Zeichen links', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-bullets-arrow-left" name="sventes_teaser_settings[bullets_arrow_left]">
                            <option value="‹" <?php selected( isset( $settings['bullets_arrow_left'] ) ? $settings['bullets_arrow_left'] : '‹', '‹' ); ?>><?php esc_html_e( '‹ Schmal', 'itn-teaser' ); ?></option>
                            <option value="❮" <?php selected( isset( $settings['bullets_arrow_left'] ) ? $settings['bullets_arrow_left'] : '', '❮' ); ?>><?php esc_html_e( '❮ Breit', 'itn-teaser' ); ?></option>
                            <option value="←" <?php selected( isset( $settings['bullets_arrow_left'] ) ? $settings['bullets_arrow_left'] : '', '←' ); ?>><?php esc_html_e( '← Pfeil', 'itn-teaser' ); ?></option>
                            <option value="«" <?php selected( isset( $settings['bullets_arrow_left'] ) ? $settings['bullets_arrow_left'] : '', '«' ); ?>><?php esc_html_e( '« Doppel', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-right"><?php esc_html_e( 'Zeichen rechts', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-bullets-arrow-right" name="sventes_teaser_settings[bullets_arrow_right]">
                            <option value="›" <?php selected( isset( $settings['bullets_arrow_right'] ) ? $settings['bullets_arrow_right'] : '›', '›' ); ?>><?php esc_html_e( '› Schmal', 'itn-teaser' ); ?></option>
                            <option value="❯" <?php selected( isset( $settings['bullets_arrow_right'] ) ? $settings['bullets_arrow_right'] : '', '❯' ); ?>><?php esc_html_e( '❯ Breit', 'itn-teaser' ); ?></option>
                            <option value="→" <?php selected( isset( $settings['bullets_arrow_right'] ) ? $settings['bullets_arrow_right'] : '', '→' ); ?>><?php esc_html_e( '→ Pfeil', 'itn-teaser' ); ?></option>
                            <option value="»" <?php selected( isset( $settings['bullets_arrow_right'] ) ? $settings['bullets_arrow_right'] : '', '»' ); ?>><?php esc_html_e( '» Doppel', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-color"><?php esc_html_e( 'Pfeilfarbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-bullets-arrow-color" name="sventes_teaser_settings[bullets_arrow_color]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_color'] ) ? $settings['bullets_arrow_color'] : '#333333' ); ?>" class="regular-text" placeholder="#333333" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-size"><?php esc_html_e( 'Pfeilgröße (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-arrow-size" name="sventes_teaser_settings[bullets_arrow_size]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_size'] ) ? $settings['bullets_arrow_size'] : 18 ); ?>" class="small-text" min="8" max="48" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-position-left"><?php esc_html_e( 'Position linker Pfeil (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-arrow-position-left" name="sventes_teaser_settings[bullets_arrow_position_left]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_position_left'] ) ? $settings['bullets_arrow_position_left'] : -40 ); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Negative Werte positionieren den Pfeil außerhalb. (z.B. -40)', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-position-right"><?php esc_html_e( 'Position rechter Pfeil (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-arrow-position-right" name="sventes_teaser_settings[bullets_arrow_position_right]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_position_right'] ) ? $settings['bullets_arrow_position_right'] : -40 ); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Negative Werte positionieren den Pfeil außerhalb. (z.B. -40)', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-offset-x"><?php esc_html_e( 'Horizontaler Versatz (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-arrow-offset-x" name="sventes_teaser_settings[bullets_arrow_offset_x]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_offset_x'] ) ? $settings['bullets_arrow_offset_x'] : 0 ); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Versatz in px (positive oder negative Werte)', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-bullets-arrow-offset-y"><?php esc_html_e( 'Vertikaler Versatz (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-bullets-arrow-offset-y" name="sventes_teaser_settings[bullets_arrow_offset_y]" value="<?php echo esc_attr( isset( $settings['bullets_arrow_offset_y'] ) ? $settings['bullets_arrow_offset_y'] : 0 ); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Versatz in px (positive oder negative Werte)', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( esc_html__( 'Bullets speichern', 'itn-teaser' ) ); ?>
        </form>
        <?php
    }

    private function render_allgemein_tab( $set_id, $set, $settings ) {
        $acf_visible_max = self::ACF_VISIBLE_MAX;
        $max_visible_options = self::ACF_VISIBLE_MAX;
        ?>
        <form method="post" action="" id="itn-teaser-options-form" data-max-visible="<?php echo esc_attr( $max_visible_options ); ?>" data-acf-max-visible="<?php echo esc_attr( $acf_visible_max ); ?>">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="save_options" />
            <input type="hidden" name="current_set_id" value="<?php echo esc_attr( $set_id ); ?>" />

            <h2><?php esc_html_e( 'Allgemeine Einstellungen', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Anzahl sichtbarer Elemente (Desktop)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select name="sventes_teaser_settings[desktop_visible]" id="sventes-desktop-visible" data-max-visible="<?php echo esc_attr( $max_visible_options ); ?>">
                            <?php for ( $i = 1; $i <= $max_visible_options; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['desktop_visible'], $i ); ?>><?php echo esc_html( $i ); ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Anzahl sichtbarer Elemente (Tablet)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select name="sventes_teaser_settings[tablet_visible]" id="sventes-tablet-visible" data-max-visible="<?php echo esc_attr( $max_visible_options ); ?>">
                            <?php for ( $i = 1; $i <= $max_visible_options; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['tablet_visible'], $i ); ?>><?php echo esc_html( $i ); ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Anzahl sichtbarer Elemente (Mobil)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select name="sventes_teaser_settings[mobile_visible]" id="sventes-mobile-visible" data-max-visible="<?php echo esc_attr( $max_visible_options ); ?>">
                            <?php for ( $i = 1; $i <= $max_visible_options; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['mobile_visible'], $i ); ?>><?php echo esc_html( $i ); ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-tablet-breakpoint"><?php esc_html_e( 'Tablet Breakpoint (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-tablet-breakpoint" name="sventes_teaser_settings[tablet_breakpoint]" value="<?php echo esc_attr( $settings['tablet_breakpoint'] ); ?>" class="small-text" min="320" max="5000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-mobile-breakpoint"><?php esc_html_e( 'Mobile Breakpoint (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-mobile-breakpoint" name="sventes_teaser_settings[mobile_breakpoint]" value="<?php echo esc_attr( $settings['mobile_breakpoint'] ); ?>" class="small-text" min="320" max="4999" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Autoplay', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[autoplay]" value="1" <?php checked( $settings['autoplay'], true ); ?> /> <?php esc_html_e( 'Autoplay aktivieren', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-autoplay-interval"><?php esc_html_e( 'Autoplay Intervall (ms)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-autoplay-interval" name="sventes_teaser_settings[autoplay_interval]" value="<?php echo esc_attr( $settings['autoplay_interval'] ); ?>" class="small-text" min="1000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Loop', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[loop]" value="1" <?php checked( $settings['loop'], true ); ?> /> <?php esc_html_e( 'Loop aktivieren (endlos)', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-extra-classes"><?php esc_html_e( 'Zusätzliche CSS-Klassen', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-extra-classes" name="sventes_teaser_settings[extra_classes]" value="<?php echo esc_attr( $settings['extra_classes'] ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Mehrere Klassen bitte mit Leerzeichen trennen.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Abstände und Rahmen', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Border', 'itn-teaser' ); ?></label></th>
                    <td>
                        <label><input type="checkbox" name="sventes_teaser_settings[border_enabled]" value="1" <?php checked( $settings['border_enabled'], true ); ?> /> <?php esc_html_e( 'Border für Teaser anzeigen', 'itn-teaser' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-border-width"><?php esc_html_e( 'Border-Breite (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-border-width" name="sventes_teaser_settings[border_width]" value="<?php echo esc_attr( $settings['border_width'] ); ?>" class="small-text" min="0" max="20" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-border-style"><?php esc_html_e( 'Border-Stil', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-teaser-border-style" name="sventes_teaser_settings[border_style]">
                            <option value="solid" <?php selected( $settings['border_style'], 'solid' ); ?>><?php esc_html_e( 'Durchgezogen', 'itn-teaser' ); ?></option>
                            <option value="dashed" <?php selected( $settings['border_style'], 'dashed' ); ?>><?php esc_html_e( 'Gestrichelt', 'itn-teaser' ); ?></option>
                            <option value="dotted" <?php selected( $settings['border_style'], 'dotted' ); ?>><?php esc_html_e( 'Gepunktet', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-border-color"><?php esc_html_e( 'Border-Farbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-border-color" name="sventes_teaser_settings[border_color]" value="<?php echo esc_attr( $settings['border_color'] ); ?>" class="regular-text" placeholder="#e5e5e5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-border-radius"><?php esc_html_e( 'Border-Radius (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-border-radius" name="sventes_teaser_settings[border_radius]" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" class="small-text" min="0" max="200" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-background-color"><?php esc_html_e( 'Hintergrundfarbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-background-color" name="sventes_teaser_settings[background_color]" value="<?php echo esc_attr( $settings['background_color'] ); ?>" class="regular-text" placeholder="#ffffff" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-text-color"><?php esc_html_e( 'Textfarbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-teaser-text-color" name="sventes_teaser_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>" class="regular-text" placeholder="#4d4d4d" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-padding-top"><?php esc_html_e( 'Innenabstand Teaser (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <span><?php esc_html_e( 'Oben:', 'itn-teaser' ); ?></span>
                        <input type="number" id="itn-teaser-padding-top" name="sventes_teaser_settings[padding_top]" value="<?php echo esc_attr( isset( $settings['padding_top'] ) ? $settings['padding_top'] : ( isset( $settings['item_padding'] ) ? $settings['item_padding'] : 12 ) ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Rechts:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[padding_right]" value="<?php echo esc_attr( isset( $settings['padding_right'] ) ? $settings['padding_right'] : ( isset( $settings['item_padding'] ) ? $settings['item_padding'] : 12 ) ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Unten:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[padding_bottom]" value="<?php echo esc_attr( isset( $settings['padding_bottom'] ) ? $settings['padding_bottom'] : ( isset( $settings['item_padding'] ) ? $settings['item_padding'] : 12 ) ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Links:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[padding_left]" value="<?php echo esc_attr( isset( $settings['padding_left'] ) ? $settings['padding_left'] : ( isset( $settings['item_padding'] ) ? $settings['item_padding'] : 12 ) ); ?>" class="small-text" min="0" max="120" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-margin-top"><?php esc_html_e( 'Außenabstand Teaser (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <span><?php esc_html_e( 'Oben:', 'itn-teaser' ); ?></span>
                        <input type="number" id="itn-teaser-margin-top" name="sventes_teaser_settings[margin_top]" value="<?php echo esc_attr( isset( $settings['margin_top'] ) ? $settings['margin_top'] : 0 ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Rechts:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[margin_right]" value="<?php echo esc_attr( isset( $settings['margin_right'] ) ? $settings['margin_right'] : 0 ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Unten:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[margin_bottom]" value="<?php echo esc_attr( isset( $settings['margin_bottom'] ) ? $settings['margin_bottom'] : 0 ); ?>" class="small-text" min="0" max="120" />
                        <span><?php esc_html_e( 'Links:', 'itn-teaser' ); ?></span>
                        <input type="number" name="sventes_teaser_settings[margin_left]" value="<?php echo esc_attr( isset( $settings['margin_left'] ) ? $settings['margin_left'] : 0 ); ?>" class="small-text" min="0" max="120" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-teaser-gap"><?php esc_html_e( 'Abstand zwischen Teasern (px)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-teaser-gap" name="sventes_teaser_settings[gap]" value="<?php echo esc_attr( $settings['gap'] ); ?>" class="small-text" min="0" max="80" />
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Mouseover-Effekt', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="itn-hover-effect"><?php esc_html_e( 'Hover-Effekt', 'itn-teaser' ); ?></label></th>
                    <td>
                        <select id="itn-hover-effect" name="sventes_teaser_settings[hover_effect]">
                            <option value="zoom" <?php selected( isset( $settings['hover_effect'] ) ? $settings['hover_effect'] : 'zoom', 'zoom' ); ?>><?php esc_html_e( 'Zoom', 'itn-teaser' ); ?></option>
                            <option value="overlay" <?php selected( isset( $settings['hover_effect'] ) ? $settings['hover_effect'] : '', 'overlay' ); ?>><?php esc_html_e( 'Farbfläche/Overlay', 'itn-teaser' ); ?></option>
                            <option value="none" <?php selected( isset( $settings['hover_effect'] ) ? $settings['hover_effect'] : '', 'none' ); ?>><?php esc_html_e( 'Kein Effekt', 'itn-teaser' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-overlay-color"><?php esc_html_e( 'Overlay-Farbe', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="text" id="itn-overlay-color" name="sventes_teaser_settings[overlay_color]" value="<?php echo esc_attr( isset( $settings['overlay_color'] ) ? $settings['overlay_color'] : '#000000' ); ?>" class="regular-text" placeholder="#000000" />
                        <p class="description"><?php esc_html_e( 'Hex-Farbe des Overlays (nur bei Farbfläche aktiv)', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-overlay-opacity"><?php esc_html_e( 'Overlay-Deckkraft (%)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="number" id="itn-overlay-opacity" name="sventes_teaser_settings[overlay_opacity]" value="<?php echo esc_attr( isset( $settings['overlay_opacity'] ) ? $settings['overlay_opacity'] : 30 ); ?>" class="small-text" min="0" max="100" />
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Eigenes CSS / Stylesheet', 'itn-teaser' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="itn-custom-css"><?php esc_html_e( 'Eigenes CSS', 'itn-teaser' ); ?></label></th>
                    <td>
                        <textarea id="itn-custom-css" name="sventes_teaser_settings[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea( isset( $settings['custom_css'] ) ? $settings['custom_css'] : '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'CSS-Regeln direkt eingeben. Wird nur für dieses Teasermodul angewendet.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="itn-stylesheet-url"><?php esc_html_e( 'Externes Stylesheet (URL)', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="url" id="itn-stylesheet-url" name="sventes_teaser_settings[stylesheet_url]" value="<?php echo esc_attr( isset( $settings['stylesheet_url'] ) ? $settings['stylesheet_url'] : '' ); ?>" class="regular-text" placeholder="https://example.com/style.css" />
                        <p class="description"><?php esc_html_e( 'URL zu einem externen Stylesheet (optional). Wird im Frontend geladen, wenn ein Teasermodul dieser Einstellung auf der Seite ausgegeben wird.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( esc_html__( 'Allgemeine Einstellungen speichern', 'itn-teaser' ) ); ?>
        </form>
        <?php
    }

    private function render_export_tab( $sets ) {
        $select_size = min( max( count( $sets ), 4 ), 12 );
        ?>
        <h2><?php esc_html_e( 'Teasermodule exportieren', 'itn-teaser' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="export_sets" />

            <p><?php esc_html_e( 'Wähle ein oder mehrere Teasermodule aus und lade sie als JSON-Datei herunter.', 'itn-teaser' ); ?></p>
            <select name="export_set_ids[]" multiple="multiple" size="<?php echo esc_attr( $select_size ); ?>" class="large-text">
                <?php foreach ( $sets as $set_id => $set ) : ?>
                    <option value="<?php echo esc_attr( $set_id ); ?>"><?php echo esc_html( $set['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Mehrfachauswahl ist mit Strg/Cmd oder Shift möglich.', 'itn-teaser' ); ?></p>

            <?php submit_button( esc_html__( 'Ausgewählte Teasermodule exportieren', 'itn-teaser' ), 'secondary' ); ?>
        </form>

        <hr />

        <h2><?php esc_html_e( 'Teasermodule importieren', 'itn-teaser' ); ?></h2>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="import_sets" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="itn-import-file"><?php esc_html_e( 'JSON-Datei', 'itn-teaser' ); ?></label></th>
                    <td>
                        <input type="file" id="itn-import-file" name="import_file" accept=".json,application/json" />
                        <p class="description"><?php esc_html_e( 'Vorhandene Teasermodule werden beim Import nicht überschrieben.', 'itn-teaser' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( esc_html__( 'Teasermodule importieren', 'itn-teaser' ) ); ?>
        </form>
        <?php
    }

    private function render_permissions_tab( $data ) {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'itn_teaser_save', 'itn_teaser_nonce' ); ?>
            <input type="hidden" name="itn_teaser_action" value="save_roles" />

            <p><?php esc_html_e( 'Lege fest, welche Rollen ITN Teaser im Admin sehen und bearbeiten dürfen. Administratoren bleiben immer berechtigt, damit keine Aussperrung entsteht.', 'itn-teaser' ); ?></p>

            <fieldset>
                <?php foreach ( get_editable_roles() as $role_key => $role_data ) : ?>
                    <label class="itn-teaser-role-option">
                        <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $data['allowed_roles'], true ) ); ?> <?php disabled( 'administrator' === $role_key ); ?> />
                        <span><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></span>
                        <?php if ( 'administrator' === $role_key ) : ?>
                            <em><?php esc_html_e( '(immer aktiv)', 'itn-teaser' ); ?></em>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <h2><?php esc_html_e( 'Deinstallation', 'itn-teaser' ); ?></h2>
            <fieldset>
                <label class="itn-teaser-role-option">
                    <input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( ! empty( $data['delete_on_uninstall'] ) ); ?> />
                    <span><?php esc_html_e( 'Daten bei Deinstallation löschen', 'itn-teaser' ); ?></span>
                </label>
                <p class="description"><?php esc_html_e( 'Wenn diese Option aktiv ist, werden beim Löschen des Plugins alle gespeicherten Teasermodule, Zuordnungen und Einstellungen entfernt.', 'itn-teaser' ); ?></p>
            </fieldset>

            <?php submit_button( esc_html__( 'Rechte und Deinstallation speichern', 'itn-teaser' ) ); ?>
        </form>
        <?php
    }

    private function get_current_context_page_id() {
        $page_id = get_queried_object_id();

        if ( $page_id > 0 ) {
            return $page_id;
        }

        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return 0;
        }

        $candidates = array();

        if ( isset( $_POST['post_id'] ) ) {
            $candidates[] = intval( wp_unslash( $_POST['post_id'] ) );
        }

        if ( isset( $_GET['post_id'] ) ) {
            $candidates[] = intval( wp_unslash( $_GET['post_id'] ) );
        }

        if ( isset( $_POST['post'] ) ) {
            $candidates[] = intval( wp_unslash( $_POST['post'] ) );
        }

        if ( isset( $_GET['post'] ) ) {
            $candidates[] = intval( wp_unslash( $_GET['post'] ) );
        }

        foreach ( array_filter( array_unique( $candidates ) ) as $candidate ) {
            if ( $candidate > 0 && current_user_can( 'edit_post', $candidate ) ) {
                return $candidate;
            }
        }

        return 0;
    }

    private function resolve_set_id( $requested_set_id = '', $use_page_assignment = true ) {
        $data = $this->get_data();
        $requested_set_id = $requested_set_id ? $this->normalize_set_id( $requested_set_id ) : '';

        if ( $requested_set_id && isset( $data['sets'][ $requested_set_id ] ) ) {
            return $requested_set_id;
        }

        if ( $use_page_assignment ) {
            $page_id = $this->get_current_context_page_id();
            if ( $page_id && isset( $data['assignments'][ $page_id ] ) && isset( $data['sets'][ $data['assignments'][ $page_id ] ] ) ) {
                return $data['assignments'][ $page_id ];
            }
        }

        return isset( $data['sets'][ $this->default_set_id ] ) ? $this->default_set_id : key( $data['sets'] );
    }

    private function get_wrapper_classes( $settings ) {
        $classes = array( 'sventes-teaser', 'js-sventes-teaser' );

        // Hover effect class
        $hover_effect = isset( $settings['hover_effect'] ) ? $settings['hover_effect'] : 'zoom';
        if ( 'zoom' === $hover_effect ) {
            $classes[] = 'sventes-hover-zoom';
        } elseif ( 'overlay' === $hover_effect ) {
            $classes[] = 'sventes-hover-overlay';
        }

        if ( ! empty( $settings['extra_classes'] ) ) {
            $classes = array_merge( $classes, preg_split( '/\s+/', $settings['extra_classes'] ) );
        }

        $arrow_bg_shape = isset( $settings['arrow_bg_shape'] ) ? sanitize_key( $settings['arrow_bg_shape'] ) : 'rounded';
        if ( ! in_array( $arrow_bg_shape, array( 'rounded', 'round', 'square' ), true ) ) {
            $arrow_bg_shape = 'rounded';
        }
        $classes[] = 'sventes-arrow-bg-' . $arrow_bg_shape;

        $arrow_visibility = isset( $settings['arrow_visibility'] ) ? sanitize_key( $settings['arrow_visibility'] ) : 'always';
        if ( 'hover' === $arrow_visibility ) {
            $classes[] = 'sventes-arrows-hover';
        } else {
            $classes[] = 'sventes-arrows-always';
        }

        return implode( ' ', array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) ) );
    }

    private function get_wrapper_style( $settings ) {
        // 4-way padding (with backward compat for legacy item_padding)
        $p_top    = isset( $settings['padding_top'] )    ? intval( $settings['padding_top'] )    : ( isset( $settings['item_padding'] ) ? intval( $settings['item_padding'] ) : 12 );
        $p_right  = isset( $settings['padding_right'] )  ? intval( $settings['padding_right'] )  : ( isset( $settings['item_padding'] ) ? intval( $settings['item_padding'] ) : 12 );
        $p_bottom = isset( $settings['padding_bottom'] ) ? intval( $settings['padding_bottom'] ) : ( isset( $settings['item_padding'] ) ? intval( $settings['item_padding'] ) : 12 );
        $p_left   = isset( $settings['padding_left'] )   ? intval( $settings['padding_left'] )   : ( isset( $settings['item_padding'] ) ? intval( $settings['item_padding'] ) : 12 );
        $m_top    = isset( $settings['margin_top'] )     ? intval( $settings['margin_top'] )     : 0;
        $m_right  = isset( $settings['margin_right'] )   ? intval( $settings['margin_right'] )   : 0;
        $m_bottom = isset( $settings['margin_bottom'] )  ? intval( $settings['margin_bottom'] )  : 0;
        $m_left   = isset( $settings['margin_left'] )    ? intval( $settings['margin_left'] )    : 0;

        $styles = array(
            '--itn-teaser-gap:'                  . intval( $settings['gap'] ) . 'px',
            '--itn-teaser-padding-top:'          . $p_top    . 'px',
            '--itn-teaser-padding-right:'        . $p_right  . 'px',
            '--itn-teaser-padding-bottom:'       . $p_bottom . 'px',
            '--itn-teaser-padding-left:'         . $p_left   . 'px',
            '--itn-teaser-margin-top:'           . $m_top    . 'px',
            '--itn-teaser-margin-right:'         . $m_right  . 'px',
            '--itn-teaser-margin-bottom:'        . $m_bottom . 'px',
            '--itn-teaser-margin-left:'          . $m_left   . 'px',
            '--itn-teaser-item-border-width:'    . ( ! empty( $settings['border_enabled'] ) ? intval( $settings['border_width'] ) : 0 ) . 'px',
            '--itn-teaser-item-border-style:'    . ( ! empty( $settings['border_enabled'] ) ? $settings['border_style'] : 'solid' ),
            '--itn-teaser-item-border-color:'    . ( $settings['border_color'] ? $settings['border_color'] : 'transparent' ),
            '--itn-teaser-item-border-radius:'   . intval( $settings['border_radius'] ) . 'px',
        );

        // Arrow styles
        $arrow_size        = isset( $settings['arrow_size'] ) ? intval( $settings['arrow_size'] ) : 44;
        $arrow_border_r    = isset( $settings['arrow_border_radius'] ) ? intval( $settings['arrow_border_radius'] ) : 22;
        $arrow_color       = ! empty( $settings['arrow_color'] ) ? $settings['arrow_color'] : '#ffffff';
        $arrow_bg_color    = ! empty( $settings['arrow_bg_color'] ) ? $settings['arrow_bg_color'] : '#000000';
        $arrow_bg_opacity  = isset( $settings['arrow_bg_opacity'] ) ? max( 0, min( 100, intval( $settings['arrow_bg_opacity'] ) ) ) : 60;
        $styles[] = '--itn-arrow-size:'          . $arrow_size . 'px';
        $styles[] = '--itn-arrow-border-radius:' . $arrow_border_r . 'px';
        $styles[] = '--itn-arrow-color:'         . $arrow_color;
        $styles[] = '--itn-arrow-bg:'            . $this->hex_to_rgba( $arrow_bg_color, (float) $arrow_bg_opacity / 100.0 );

        // Arrow positioning
        $arrow_pos_top    = isset( $settings['arrow_position_top'] ) ? intval( $settings['arrow_position_top'] ) : 50;
        $arrow_pos_left   = isset( $settings['arrow_position_left'] ) ? intval( $settings['arrow_position_left'] ) : 10;
        $arrow_pos_right  = isset( $settings['arrow_position_right'] ) ? intval( $settings['arrow_position_right'] ) : 10;
        $arrow_offset_x   = isset( $settings['arrow_offset_x'] ) ? intval( $settings['arrow_offset_x'] ) : 0;
        $arrow_offset_y   = isset( $settings['arrow_offset_y'] ) ? intval( $settings['arrow_offset_y'] ) : 0;
        $styles[] = '--itn-arrow-top:'     . $arrow_pos_top . '%';
        $styles[] = '--itn-arrow-left:'    . $arrow_pos_left . 'px';
        $styles[] = '--itn-arrow-right:'   . $arrow_pos_right . 'px';
        $styles[] = '--itn-arrow-offset-x:' . $arrow_offset_x . 'px';
        $styles[] = '--itn-arrow-offset-y:' . $arrow_offset_y . 'px';

        // Bullets side arrows
        if ( ! empty( $settings['bullets_side_arrows'] ) ) {
            $bullets_arrow_color = ! empty( $settings['bullets_arrow_color'] ) ? $settings['bullets_arrow_color'] : '#333333';
            $bullets_arrow_size  = isset( $settings['bullets_arrow_size'] ) ? intval( $settings['bullets_arrow_size'] ) : 18;
            $bullets_arrow_pos_left = isset( $settings['bullets_arrow_position_left'] ) ? intval( $settings['bullets_arrow_position_left'] ) : -40;
            $bullets_arrow_pos_right = isset( $settings['bullets_arrow_position_right'] ) ? intval( $settings['bullets_arrow_position_right'] ) : -40;
            $bullets_arrow_offset_x = isset( $settings['bullets_arrow_offset_x'] ) ? intval( $settings['bullets_arrow_offset_x'] ) : 0;
            $bullets_arrow_offset_y = isset( $settings['bullets_arrow_offset_y'] ) ? intval( $settings['bullets_arrow_offset_y'] ) : 0;
            $styles[] = '--itn-bullet-arrow-color:' . $bullets_arrow_color;
            $styles[] = '--itn-bullet-arrow-size:'  . $bullets_arrow_size . 'px';
            $styles[] = '--itn-bullet-arrow-position-left:' . $bullets_arrow_pos_left . 'px';
            $styles[] = '--itn-bullet-arrow-position-right:' . $bullets_arrow_pos_right . 'px';
            $styles[] = '--itn-bullet-arrow-offset-x:' . $bullets_arrow_offset_x . 'px';
            $styles[] = '--itn-bullet-arrow-offset-y:' . $bullets_arrow_offset_y . 'px';
        }

        // Bullet styles
        $bullets_size         = isset( $settings['bullets_size'] ) ? intval( $settings['bullets_size'] ) : 10;
        $bullets_color        = ! empty( $settings['bullets_color'] ) ? $settings['bullets_color'] : '#cccccc';
        $bullets_active_color = ! empty( $settings['bullets_active_color'] ) ? $settings['bullets_active_color'] : '#333333';
        $styles[] = '--itn-dot-size:'         . $bullets_size . 'px';
        $styles[] = '--itn-dot-color:'        . $bullets_color;
        $styles[] = '--itn-dot-active-color:' . $bullets_active_color;

        // Hover overlay color
        $overlay_color   = ! empty( $settings['overlay_color'] ) ? $settings['overlay_color'] : '#000000';
        $overlay_opacity = isset( $settings['overlay_opacity'] ) ? max( 0, min( 100, intval( $settings['overlay_opacity'] ) ) ) : 30;
        $styles[] = '--itn-hover-overlay-color:' . $this->hex_to_rgba( $overlay_color, (float) $overlay_opacity / 100.0 );

        if ( ! empty( $settings['background_color'] ) ) {
            $styles[] = '--itn-teaser-item-bg:' . $settings['background_color'];
        }

        if ( ! empty( $settings['text_color'] ) ) {
            $styles[] = '--itn-teaser-text-color:' . $settings['text_color'];
        }

        return implode( ';', $styles );
    }

    private function get_mobile_arrow_css( $settings, $set_id ) {
        if ( empty( $settings['arrow_mobile_enabled'] ) ) {
            return '';
        }

        $mobile_breakpoint = isset( $settings['arrow_mobile_breakpoint'] ) ? intval( $settings['arrow_mobile_breakpoint'] ) : 768;
        $mobile_pos_top    = isset( $settings['arrow_mobile_position_top'] ) ? intval( $settings['arrow_mobile_position_top'] ) : 50;
        $mobile_pos_left   = isset( $settings['arrow_mobile_position_left'] ) ? intval( $settings['arrow_mobile_position_left'] ) : 5;
        $mobile_pos_right  = isset( $settings['arrow_mobile_position_right'] ) ? intval( $settings['arrow_mobile_position_right'] ) : 5;
        $mobile_offset_x   = isset( $settings['arrow_mobile_offset_x'] ) ? intval( $settings['arrow_mobile_offset_x'] ) : 0;
        $mobile_offset_y   = isset( $settings['arrow_mobile_offset_y'] ) ? intval( $settings['arrow_mobile_offset_y'] ) : 0;
        $mobile_size       = isset( $settings['arrow_mobile_size'] ) ? intval( $settings['arrow_mobile_size'] ) : 36;

        $css = sprintf(
            '@media (max-width:%dpx) { [data-set-id="%s"] { --itn-arrow-top:%d%%;--itn-arrow-left:%dpx;--itn-arrow-right:%dpx;--itn-arrow-offset-x:%dpx;--itn-arrow-offset-y:%dpx;--itn-arrow-size:%dpx; }',
            $mobile_breakpoint,
            esc_attr( $set_id ),
            $mobile_pos_top,
            $mobile_pos_left,
            $mobile_pos_right,
            $mobile_offset_x,
            $mobile_offset_y,
            $mobile_size
        );

        if ( ! empty( $settings['arrow_mobile_hide'] ) ) {
            $css .= sprintf( ' [data-set-id="%s"] .sventes-nav { display:none; }', esc_attr( $set_id ) );
        }

        $css .= ' }';
        return $css;
    }

    private function should_show_edit_button() {
        if ( ! $this->current_user_can_manage_plugin() ) {
            return false;
        }

        if ( is_admin() || wp_doing_ajax() ) {
            return true;
        }

        if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
            return true;
        }

        return false;
    }

    private function render_output( $requested_set_id = '', $use_page_assignment = true ) {
        $data = $this->get_data();
        if ( empty( $data['sets'] ) ) {
            return '';
        }

        $set_id = $this->resolve_set_id( $requested_set_id, $use_page_assignment );
        if ( ! isset( $data['sets'][ $set_id ] ) ) {
            return '';
        }

        $set      = $data['sets'][ $set_id ];
        $teasers  = $set['teasers'];
        $settings = $set['settings'];
        $is_auto_source = isset( $settings['teaser_source_mode'] ) && 'acf_posts' === $settings['teaser_source_mode'];
        $auto_button_fallback = '';
        if ( $is_auto_source ) {
            $auto_button_fallback = $this->get_auto_button_label_setting( $settings );
            $teasers = $this->get_auto_teasers_from_acf( $settings );
        }
        $classes  = $this->get_wrapper_classes( $settings );
        $style    = $this->get_wrapper_style( $settings );
        $show_edit_button = $this->should_show_edit_button();
        $edit_url = $show_edit_button ? $this->get_tab_url( 'sets', $set_id ) : '';

        if ( empty( $teasers ) ) {
            return '';
        }

        $settings_json = wp_json_encode( $settings );
        if ( false === $settings_json ) {
            $settings_json = wp_json_encode( $this->get_default_settings() );
        }

        $this->enqueue_frontend_assets();

        // Enqueue per-set custom stylesheet if provided
        if ( ! empty( $settings['stylesheet_url'] ) ) {
            $handle = 'itn-teaser-custom-' . sanitize_html_class( $set_id );
            if ( ! wp_style_is( $handle, 'enqueued' ) ) {
                wp_enqueue_style( $handle, esc_url_raw( $settings['stylesheet_url'] ), array(), null );
            }
        }

        ob_start();
        $mobile_css = $this->get_mobile_arrow_css( $settings, $set_id );
        if ( $mobile_css ) {
            echo '<style type="text/css">' . wp_kses_post( $mobile_css ) . '</style>';
        }
        ?>
        <div
            class="<?php echo esc_attr( $classes ); ?>"
            role="region"
            aria-roledescription="carousel"
            aria-label="<?php echo esc_attr( $set['name'] ); ?>"
            data-settings="<?php echo esc_attr( $settings_json ); ?>"
            data-set-id="<?php echo esc_attr( $set_id ); ?>"
            style="<?php echo esc_attr( $style ); ?>"
        >
            <div class="sventes-teaser-inner" tabindex="0">
                <?php foreach ( $teasers as $index => $item ) :
                    $img       = ! empty( $item['image_url'] ) ? esc_url( $item['image_url'] ) : '';
                    $content   = wp_kses_post( isset( $item['content'] ) ? $item['content'] : '' );
                    $url       = ! empty( $item['url'] ) ? esc_url( $item['url'] ) : '';
                    $target    = ( isset( $item['target'] ) && '_blank' === $item['target'] ) ? '_blank' : '_self';
                    $rel       = '_blank' === $target ? 'noopener noreferrer' : '';
                    $link_mode = $is_auto_source ? 'image' : ( isset( $item['link_mode'] ) ? $item['link_mode'] : ( $url ? 'full' : 'none' ) );

                    $btn1_label  = isset( $item['btn1_label'] ) ? $item['btn1_label'] : '';
                    $btn1_url    = ! empty( $item['btn1_url'] ) ? esc_url( $item['btn1_url'] ) : '';
                    $btn1_target = ( isset( $item['btn1_target'] ) && '_blank' === $item['btn1_target'] ) ? '_blank' : '_self';
                    $btn2_label  = isset( $item['btn2_label'] ) ? $item['btn2_label'] : '';
                    $btn2_url    = ! empty( $item['btn2_url'] ) ? esc_url( $item['btn2_url'] ) : '';
                    $btn2_target = ( isset( $item['btn2_target'] ) && '_blank' === $item['btn2_target'] ) ? '_blank' : '_self';
                    if ( $is_auto_source ) {
                        $btn1_label = $auto_button_fallback;
                        $btn1_url = $url;
                        $btn1_target = '_self';
                        $btn2_label = '';
                        $btn2_url = '';
                        $btn2_target = '_self';
                    }
                    $has_btn1    = $btn1_label && $btn1_url;
                    $has_btn2    = $btn2_label && $btn2_url;
                    $has_buttons = $has_btn1 || $has_btn2;
                    ?>
                    <div class="sventes-teaser-item" id="sventes-slide-<?php echo esc_attr( $index ); ?>" data-index="<?php echo esc_attr( $index ); ?>" role="group" aria-roledescription="slide" aria-label="<?php printf( esc_attr__( 'Slide %1$d von %2$d', 'itn-teaser' ), $index + 1, count( $teasers ) ); ?>">

                        <?php if ( $img ) : ?>
                            <div class="sventes-teaser-image">
                                <?php if ( 'image' === $link_mode && $url ) : ?>
                                    <a href="<?php echo $url; ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel ) : ?> rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>>
                                        <img src="<?php echo $img; ?>" alt="" />
                                    </a>
                                <?php else : ?>
                                    <img src="<?php echo $img; ?>" alt="" />
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="sventes-teaser-text">
                            <?php echo $content; ?>
                        </div>

                        <?php if ( $has_buttons ) : ?>
                            <div class="sventes-teaser-buttons">
                                <?php if ( $has_btn1 ) : ?>
                                    <a href="<?php echo $btn1_url; ?>" class="sventes-btn sventes-btn-1" target="<?php echo esc_attr( $btn1_target ); ?>"<?php if ( '_blank' === $btn1_target ) : ?> rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $btn1_label ); ?></a>
                                <?php endif; ?>
                                <?php if ( $has_btn2 ) : ?>
                                    <a href="<?php echo $btn2_url; ?>" class="sventes-btn sventes-btn-2" target="<?php echo esc_attr( $btn2_target ); ?>"<?php if ( '_blank' === $btn2_target ) : ?> rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $btn2_label ); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( 'full' === $link_mode && $url ) : ?>
                            <a class="sventes-teaser-fulllink" href="<?php echo $url; ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel ) : ?> rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?> tabindex="-1" aria-hidden="true"></a>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
            <?php
            $arrow_style = isset( $settings['arrow_style'] ) ? sanitize_key( $settings['arrow_style'] ) : 'chevron';
            if ( ! in_array( $arrow_style, array( 'chevron', 'triangle', 'line' ), true ) ) {
                $arrow_style = 'chevron';
            }
            $arrow_icons = array(
                'chevron'  => array( 'left' => '‹', 'right' => '›' ),
                'triangle' => array( 'left' => '◀', 'right' => '▶' ),
                'line'     => array( 'left' => '←', 'right' => '→' ),
            );
            ?>
            <?php if ( ! isset( $settings['arrow_enabled'] ) || ! empty( $settings['arrow_enabled'] ) ) : ?>
                <button class="sventes-nav sventes-prev sventes-nav-style-<?php echo esc_attr( $arrow_style ); ?>" aria-label="<?php esc_attr_e( 'Vorheriger', 'itn-teaser' ); ?>"><span aria-hidden="true"><?php echo esc_html( $arrow_icons[ $arrow_style ]['left'] ); ?></span></button>
                <button class="sventes-nav sventes-next sventes-nav-style-<?php echo esc_attr( $arrow_style ); ?>" aria-label="<?php esc_attr_e( 'Nächster', 'itn-teaser' ); ?>"><span aria-hidden="true"><?php echo esc_html( $arrow_icons[ $arrow_style ]['right'] ); ?></span></button>
            <?php endif; ?>

            <?php if ( ! empty( $settings['bullets_enabled'] ) ) :
                $bullets_style_class = isset( $settings['bullets_style'] ) ? sanitize_html_class( $settings['bullets_style'] ) : 'dots';
                ?>
                <div class="sventes-dots" role="tablist" aria-label="<?php esc_attr_e( 'Folienpagination', 'itn-teaser' ); ?>">
                    <?php if ( ! empty( $settings['bullets_side_arrows'] ) ) : ?>
                        <button class="sventes-dots-prev" aria-label="<?php esc_attr_e( 'Vorherige Folie', 'itn-teaser' ); ?>"><span aria-hidden="true"><?php echo esc_html( isset( $settings['bullets_arrow_left'] ) ? $settings['bullets_arrow_left'] : '‹' ); ?></span></button>
                    <?php endif; ?>
                    <?php foreach ( $teasers as $dot_index => $dot_item ) : ?>
                        <button class="sventes-dot sventes-dot-<?php echo esc_attr( $bullets_style_class ); ?>" role="tab" data-index="<?php echo esc_attr( $dot_index ); ?>" aria-selected="false" aria-controls="sventes-slide-<?php echo esc_attr( $dot_index ); ?>"></button>
                    <?php endforeach; ?>
                    <?php if ( ! empty( $settings['bullets_side_arrows'] ) ) : ?>
                        <button class="sventes-dots-next" aria-label="<?php esc_attr_e( 'Nächste Folie', 'itn-teaser' ); ?>"><span aria-hidden="true"><?php echo esc_html( isset( $settings['bullets_arrow_right'] ) ? $settings['bullets_arrow_right'] : '›' ); ?></span></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="sventes-live sventes-visually-hidden" aria-live="polite" aria-atomic="true"></div>

            <?php if ( ! empty( $settings['custom_css'] ) ) : ?>
                <style><?php echo $this->sanitize_custom_css( $settings['custom_css'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS output is sanitized via sanitize_custom_css ?></style>
            <?php endif; ?>
        </div>
        <?php if ( $show_edit_button ) : ?>
            <p class="itn-teaser-edit-link-wrap">
                <a href="<?php echo esc_url( $edit_url ); ?>" class="itn-teaser-edit-link"><?php esc_html_e( 'Teasermodul bearbeiten', 'itn-teaser' ); ?></a>
            </p>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function render_shortcode( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'set'                  => '',
                'set_id'               => '',
                'use_page_assignment'  => '1',
            ),
            $atts,
            $this->shortcode
        );

        $requested_set_id   = $atts['set'] ? $atts['set'] : $atts['set_id'];
        $use_page_assignment = ! in_array( strtolower( (string) $atts['use_page_assignment'] ), array( '0', 'false', 'no' ), true );

        return $this->render_output( $requested_set_id, $use_page_assignment );
    }

    public function render_block( $attributes ) {
        $requested_set_id = isset( $attributes['setId'] ) ? $attributes['setId'] : '';
        $use_page_assignment = ! isset( $attributes['usePageAssignment'] ) || ! empty( $attributes['usePageAssignment'] );

        return $this->render_output( $requested_set_id, $use_page_assignment );
    }
}

new Sventes_Teaser_Plugin();
