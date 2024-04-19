<?php
// Needed because gethostbyname( 'wordpress.org' ) returns
// a private network IP address for some reason.
add_filter( 'allowed_redirect_hosts', function( $deprecated = '' ) {
    return array(
        'wordpress.org',
        'api.wordpress.org',
        'downloads.wordpress.org',
    );
} );
// Clean up admin home page
add_action('admin_init', function(){
remove_action('welcome_panel', 'wp_welcome_panel');

remove_meta_box('dashboard_primary',       'dashboard', 'side');
remove_meta_box('dashboard_secondary',     'dashboard', 'side');
remove_meta_box('dashboard_quick_press',   'dashboard', 'side');
remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
remove_meta_box('dashboard_site_health', 'dashboard', 'normal'); // Remove site health wizard

remove_meta_box('dashboard_php_nag',           'dashboard', 'normal');
remove_meta_box('dashboard_browser_nag',       'dashboard', 'normal');
remove_meta_box('health_check_status',         'dashboard', 'normal');
remove_meta_box('dashboard_activity',          'dashboard', 'normal');
remove_meta_box('network_dashboard_right_now', 'dashboard', 'normal');
remove_meta_box('dashboard_recent_comments',   'dashboard', 'normal');
remove_meta_box('dashboard_incoming_links',    'dashboard', 'normal');
remove_meta_box('dashboard_plugins',           'dashboard', 'normal');
});
// don't need comments on a pamphlet site and can't support on gh-pages    
add_filter( 'comments_open', function(){
return false;
});