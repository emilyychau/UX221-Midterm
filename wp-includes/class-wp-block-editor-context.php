<?php
 #[AllowDynamicProperties]
 final class WP_Block_Editor_Context { public $name = 'core/edit-post'; public $post = null; public function __construct( array $settings = array() ) { if ( isset( $settings['name'] ) ) { $this->name = $settings['name']; } if ( isset( $settings['post'] ) ) { $this->post = $settings['post']; } } } 