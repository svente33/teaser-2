<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$option_name = 'sventes_teaser_data';
$capability  = 'manage_itn_teaser';
$data        = get_option( $option_name, array() );

if ( function_exists( 'wp_roles' ) ) {
    $wp_roles = wp_roles();
    if ( $wp_roles && ! empty( $wp_roles->role_objects ) ) {
        foreach ( $wp_roles->role_objects as $role ) {
            $role->remove_cap( $capability );
        }
    }
}

if ( ! empty( $data['delete_on_uninstall'] ) ) {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}accs_sliders" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}accs_teasers" );
    delete_option( $option_name );
}
