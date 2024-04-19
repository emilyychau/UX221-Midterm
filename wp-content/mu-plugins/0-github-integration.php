<?php
/*
 * Plugin Name: github integration
 */
add_action( 'wp_dashboard_setup', function() {
	wp_add_dashboard_widget(
		'test_admin',
		'Github Integration',
        function() {
            echo '<x-githubintegration />';
        }
	);
});

add_action( 'admin_enqueue_scripts', function($hook){
    if( 'index.php' != $hook ) {
		return;
	}
    wp_enqueue_script('custom_javascript', 'https://preview.wp-mfe.pages.dev/bundle.js');
} );
