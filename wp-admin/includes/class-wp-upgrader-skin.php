<?php
 #[AllowDynamicProperties]
 class WP_Upgrader_Skin { public $upgrader; public $done_header = false; public $done_footer = false; public $result = false; public $options = array(); public function __construct( $args = array() ) { $defaults = array( 'url' => '', 'nonce' => '', 'title' => '', 'context' => false, ); $this->options = wp_parse_args( $args, $defaults ); } public function set_upgrader( &$upgrader ) { if ( is_object( $upgrader ) ) { $this->upgrader =& $upgrader; } $this->add_strings(); } public function add_strings() { } public function set_result( $result ) { $this->result = $result; } public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) { $url = $this->options['url']; if ( ! $context ) { $context = $this->options['context']; } if ( ! empty( $this->options['nonce'] ) ) { $url = wp_nonce_url( $url, $this->options['nonce'] ); } $extra_fields = array(); return request_filesystem_credentials( $url, '', $error, $context, $extra_fields, $allow_relaxed_file_ownership ); } public function header() { if ( $this->done_header ) { return; } $this->done_header = true; echo '<div class="wrap">'; echo '<h1>' . $this->options['title'] . '</h1>'; } public function footer() { if ( $this->done_footer ) { return; } $this->done_footer = true; echo '</div>'; } public function error( $errors ) { if ( ! $this->done_header ) { $this->header(); } if ( is_string( $errors ) ) { $this->feedback( $errors ); } elseif ( is_wp_error( $errors ) && $errors->has_errors() ) { foreach ( $errors->get_error_messages() as $message ) { if ( $errors->get_error_data() && is_string( $errors->get_error_data() ) ) { $this->feedback( $message . ' ' . esc_html( strip_tags( $errors->get_error_data() ) ) ); } else { $this->feedback( $message ); } } } } public function feedback( $feedback, ...$args ) { if ( isset( $this->upgrader->strings[ $feedback ] ) ) { $feedback = $this->upgrader->strings[ $feedback ]; } if ( str_contains( $feedback, '%' ) ) { if ( $args ) { $args = array_map( 'strip_tags', $args ); $args = array_map( 'esc_html', $args ); $feedback = vsprintf( $feedback, $args ); } } if ( empty( $feedback ) ) { return; } show_message( $feedback ); } public function before() {} public function after() {} protected function decrement_update_count( $type ) { if ( ! $this->result || is_wp_error( $this->result ) || 'up_to_date' === $this->result ) { return; } if ( defined( 'IFRAME_REQUEST' ) ) { echo '<script type="text/javascript">
					if ( window.postMessage && JSON ) {
						window.parent.postMessage(
							JSON.stringify( {
								action: "decrementUpdateCount",
								upgradeType: "' . $type . '"
							} ),
							window.location.protocol + "//" + window.location.hostname
								+ ( "" !== window.location.port ? ":" + window.location.port : "" )
						);
					}
				</script>'; } else { echo '<script type="text/javascript">
					(function( wp ) {
						if ( wp && wp.updates && wp.updates.decrementCount ) {
							wp.updates.decrementCount( "' . $type . '" );
						}
					})( window.wp );
				</script>'; } } public function bulk_header() {} public function bulk_footer() {} public function hide_process_failed( $wp_error ) { return false; } } 