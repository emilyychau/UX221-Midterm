<?php
 function get_preferred_from_update_core() { $updates = get_core_updates(); if ( ! is_array( $updates ) ) { return false; } if ( empty( $updates ) ) { return (object) array( 'response' => 'latest' ); } return $updates[0]; } function get_core_updates( $options = array() ) { $options = array_merge( array( 'available' => true, 'dismissed' => false, ), $options ); $dismissed = get_site_option( 'dismissed_update_core' ); if ( ! is_array( $dismissed ) ) { $dismissed = array(); } $from_api = get_site_transient( 'update_core' ); if ( ! isset( $from_api->updates ) || ! is_array( $from_api->updates ) ) { return false; } $updates = $from_api->updates; $result = array(); foreach ( $updates as $update ) { if ( 'autoupdate' === $update->response ) { continue; } if ( array_key_exists( $update->current . '|' . $update->locale, $dismissed ) ) { if ( $options['dismissed'] ) { $update->dismissed = true; $result[] = $update; } } else { if ( $options['available'] ) { $update->dismissed = false; $result[] = $update; } } } return $result; } function find_core_auto_update() { $updates = get_site_transient( 'update_core' ); if ( ! $updates || empty( $updates->updates ) ) { return false; } require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; $auto_update = false; $upgrader = new WP_Automatic_Updater(); foreach ( $updates->updates as $update ) { if ( 'autoupdate' !== $update->response ) { continue; } if ( ! $upgrader->should_update( 'core', $update, ABSPATH ) ) { continue; } if ( ! $auto_update || version_compare( $update->current, $auto_update->current, '>' ) ) { $auto_update = $update; } } return $auto_update; } function get_core_checksums( $version, $locale ) { $http_url = 'http://api.wordpress.org/core/checksums/1.0/?' . http_build_query( compact( 'version', 'locale' ), '', '&' ); $url = $http_url; $ssl = wp_http_supports( array( 'ssl' ) ); if ( $ssl ) { $url = set_url_scheme( $url, 'https' ); } $options = array( 'timeout' => wp_doing_cron() ? 30 : 3, ); $response = wp_remote_get( $url, $options ); if ( $ssl && is_wp_error( $response ) ) { trigger_error( sprintf( __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ), __( 'https://wordpress.org/support/forums/' ) ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ), headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE ); $response = wp_remote_get( $http_url, $options ); } if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return false; } $body = trim( wp_remote_retrieve_body( $response ) ); $body = json_decode( $body, true ); if ( ! is_array( $body ) || ! isset( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) { return false; } return $body['checksums']; } function dismiss_core_update( $update ) { $dismissed = get_site_option( 'dismissed_update_core' ); $dismissed[ $update->current . '|' . $update->locale ] = true; return update_site_option( 'dismissed_update_core', $dismissed ); } function undismiss_core_update( $version, $locale ) { $dismissed = get_site_option( 'dismissed_update_core' ); $key = $version . '|' . $locale; if ( ! isset( $dismissed[ $key ] ) ) { return false; } unset( $dismissed[ $key ] ); return update_site_option( 'dismissed_update_core', $dismissed ); } function find_core_update( $version, $locale ) { $from_api = get_site_transient( 'update_core' ); if ( ! isset( $from_api->updates ) || ! is_array( $from_api->updates ) ) { return false; } $updates = $from_api->updates; foreach ( $updates as $update ) { if ( $update->current === $version && $update->locale === $locale ) { return $update; } } return false; } function core_update_footer( $msg = '' ) { if ( ! current_user_can( 'update_core' ) ) { return sprintf( __( 'Version %s' ), get_bloginfo( 'version', 'display' ) ); } $cur = get_preferred_from_update_core(); if ( ! is_object( $cur ) ) { $cur = new stdClass(); } if ( ! isset( $cur->current ) ) { $cur->current = ''; } if ( ! isset( $cur->response ) ) { $cur->response = ''; } require ABSPATH . WPINC . '/version.php'; $is_development_version = preg_match( '/alpha|beta|RC/', $wp_version ); if ( $is_development_version ) { return sprintf( __( 'You are using a development version (%1$s). Cool! Please <a href="%2$s">stay updated</a>.' ), get_bloginfo( 'version', 'display' ), network_admin_url( 'update-core.php' ) ); } switch ( $cur->response ) { case 'upgrade': return sprintf( '<strong><a href="%s">%s</a></strong>', network_admin_url( 'update-core.php' ), sprintf( __( 'Get Version %s' ), $cur->current ) ); case 'latest': default: return sprintf( __( 'Version %s' ), get_bloginfo( 'version', 'display' ) ); } } function update_nag() { global $pagenow; if ( is_multisite() && ! current_user_can( 'update_core' ) ) { return false; } if ( 'update-core.php' === $pagenow ) { return; } $cur = get_preferred_from_update_core(); if ( ! isset( $cur->response ) || 'upgrade' !== $cur->response ) { return false; } $version_url = sprintf( esc_url( __( 'https://wordpress.org/documentation/wordpress-version/version-%s/' ) ), sanitize_title( $cur->current ) ); if ( current_user_can( 'update_core' ) ) { $msg = sprintf( __( '<a href="%1$s">WordPress %2$s</a> is available! <a href="%3$s" aria-label="%4$s">Please update now</a>.' ), $version_url, $cur->current, network_admin_url( 'update-core.php' ), esc_attr__( 'Please update WordPress now' ) ); } else { $msg = sprintf( __( '<a href="%1$s">WordPress %2$s</a> is available! Please notify the site administrator.' ), $version_url, $cur->current ); } wp_admin_notice( $msg, array( 'type' => 'warning', 'additional_classes' => array( 'update-nag', 'inline' ), 'paragraph_wrap' => false, ) ); } function update_right_now_message() { $theme_name = wp_get_theme(); if ( current_user_can( 'switch_themes' ) ) { $theme_name = sprintf( '<a href="themes.php">%1$s</a>', $theme_name ); } $msg = ''; if ( current_user_can( 'update_core' ) ) { $cur = get_preferred_from_update_core(); if ( isset( $cur->response ) && 'upgrade' === $cur->response ) { $msg .= sprintf( '<a href="%s" class="button" aria-describedby="wp-version">%s</a> ', network_admin_url( 'update-core.php' ), sprintf( __( 'Update to %s' ), $cur->current ? $cur->current : __( 'Latest' ) ) ); } } $content = __( 'WordPress %1$s running %2$s theme.' ); $content = apply_filters( 'update_right_now_text', $content ); $msg .= sprintf( '<span id="wp-version">' . $content . '</span>', get_bloginfo( 'version', 'display' ), $theme_name ); echo "<p id='wp-version-message'>$msg</p>"; } function get_plugin_updates() { $all_plugins = get_plugins(); $upgrade_plugins = array(); $current = get_site_transient( 'update_plugins' ); foreach ( (array) $all_plugins as $plugin_file => $plugin_data ) { if ( isset( $current->response[ $plugin_file ] ) ) { $upgrade_plugins[ $plugin_file ] = (object) $plugin_data; $upgrade_plugins[ $plugin_file ]->update = $current->response[ $plugin_file ]; } } return $upgrade_plugins; } function wp_plugin_update_rows() { if ( ! current_user_can( 'update_plugins' ) ) { return; } $plugins = get_site_transient( 'update_plugins' ); if ( isset( $plugins->response ) && is_array( $plugins->response ) ) { $plugins = array_keys( $plugins->response ); foreach ( $plugins as $plugin_file ) { add_action( "after_plugin_row_{$plugin_file}", 'wp_plugin_update_row', 10, 2 ); } } } function wp_plugin_update_row( $file, $plugin_data ) { $current = get_site_transient( 'update_plugins' ); if ( ! isset( $current->response[ $file ] ) ) { return false; } $response = $current->response[ $file ]; $plugins_allowedtags = array( 'a' => array( 'href' => array(), 'title' => array(), ), 'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ), 'code' => array(), 'em' => array(), 'strong' => array(), ); $plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags ); $plugin_slug = isset( $response->slug ) ? $response->slug : $response->id; if ( isset( $response->slug ) ) { $details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&section=changelog' ); } elseif ( isset( $response->url ) ) { $details_url = $response->url; } else { $details_url = $plugin_data['PluginURI']; } $details_url = add_query_arg( array( 'TB_iframe' => 'true', 'width' => 600, 'height' => 800, ), $details_url ); $wp_list_table = _get_list_table( 'WP_Plugins_List_Table', array( 'screen' => get_current_screen(), ) ); if ( is_network_admin() || ! is_multisite() ) { if ( is_network_admin() ) { $active_class = is_plugin_active_for_network( $file ) ? ' active' : ''; } else { $active_class = is_plugin_active( $file ) ? ' active' : ''; } $requires_php = isset( $response->requires_php ) ? $response->requires_php : null; $compatible_php = is_php_version_compatible( $requires_php ); $notice_type = $compatible_php ? 'notice-warning' : 'notice-error'; printf( '<tr class="plugin-update-tr%s" id="%s" data-slug="%s" data-plugin="%s">' . '<td colspan="%s" class="plugin-update colspanchange">' . '<div class="update-message notice inline %s notice-alt"><p>', $active_class, esc_attr( $plugin_slug . '-update' ), esc_attr( $plugin_slug ), esc_attr( $file ), esc_attr( $wp_list_table->get_column_count() ), $notice_type ); if ( ! current_user_can( 'update_plugins' ) ) { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.' ), $plugin_name, esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) ) ), esc_attr( $response->new_version ) ); } elseif ( empty( $response->package ) ) { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this plugin.</em>' ), $plugin_name, esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) ) ), esc_attr( $response->new_version ) ); } else { if ( $compatible_php ) { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now</a>.' ), $plugin_name, esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) ) ), esc_attr( $response->new_version ), wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ), sprintf( 'class="update-link" aria-label="%s"', esc_attr( sprintf( _x( 'Update %s now', 'plugin' ), $plugin_name ) ) ) ); } else { printf( __( 'There is a new version of %1$s available, but it does not work with your version of PHP. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s">learn more about updating PHP</a>.' ), $plugin_name, esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) ) ), esc_attr( $response->new_version ), esc_url( wp_get_update_php_url() ) ); wp_update_php_annotation( '<br><em>', '</em>' ); } } do_action( "in_plugin_update_message-{$file}", $plugin_data, $response ); echo '</p></div></td></tr>'; } } function get_theme_updates() { $current = get_site_transient( 'update_themes' ); if ( ! isset( $current->response ) ) { return array(); } $update_themes = array(); foreach ( $current->response as $stylesheet => $data ) { $update_themes[ $stylesheet ] = wp_get_theme( $stylesheet ); $update_themes[ $stylesheet ]->update = $data; } return $update_themes; } function wp_theme_update_rows() { if ( ! current_user_can( 'update_themes' ) ) { return; } $themes = get_site_transient( 'update_themes' ); if ( isset( $themes->response ) && is_array( $themes->response ) ) { $themes = array_keys( $themes->response ); foreach ( $themes as $theme ) { add_action( "after_theme_row_{$theme}", 'wp_theme_update_row', 10, 2 ); } } } function wp_theme_update_row( $theme_key, $theme ) { $current = get_site_transient( 'update_themes' ); if ( ! isset( $current->response[ $theme_key ] ) ) { return false; } $response = $current->response[ $theme_key ]; $details_url = add_query_arg( array( 'TB_iframe' => 'true', 'width' => 1024, 'height' => 800, ), $current->response[ $theme_key ]['url'] ); $wp_list_table = _get_list_table( 'WP_MS_Themes_List_Table' ); $active = $theme->is_allowed( 'network' ) ? ' active' : ''; $requires_wp = isset( $response['requires'] ) ? $response['requires'] : null; $requires_php = isset( $response['requires_php'] ) ? $response['requires_php'] : null; $compatible_wp = is_wp_version_compatible( $requires_wp ); $compatible_php = is_php_version_compatible( $requires_php ); printf( '<tr class="plugin-update-tr%s" id="%s" data-slug="%s">' . '<td colspan="%s" class="plugin-update colspanchange">' . '<div class="update-message notice inline notice-warning notice-alt"><p>', $active, esc_attr( $theme->get_stylesheet() . '-update' ), esc_attr( $theme->get_stylesheet() ), $wp_list_table->get_column_count() ); if ( $compatible_wp && $compatible_php ) { if ( ! current_user_can( 'update_themes' ) ) { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.' ), $theme['Name'], esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme['Name'], $response['new_version'] ) ) ), $response['new_version'] ); } elseif ( empty( $response['package'] ) ) { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this theme.</em>' ), $theme['Name'], esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme['Name'], $response['new_version'] ) ) ), $response['new_version'] ); } else { printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now</a>.' ), $theme['Name'], esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme['Name'], $response['new_version'] ) ) ), $response['new_version'], wp_nonce_url( self_admin_url( 'update.php?action=upgrade-theme&theme=' ) . $theme_key, 'upgrade-theme_' . $theme_key ), sprintf( 'class="update-link" aria-label="%s"', esc_attr( sprintf( _x( 'Update %s now', 'theme' ), $theme['Name'] ) ) ) ); } } else { if ( ! $compatible_wp && ! $compatible_php ) { printf( __( 'There is a new version of %s available, but it does not work with your versions of WordPress and PHP.' ), $theme['Name'] ); if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) { printf( ' ' . __( '<a href="%1$s">Please update WordPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ), self_admin_url( 'update-core.php' ), esc_url( wp_get_update_php_url() ) ); wp_update_php_annotation( '</p><p><em>', '</em>' ); } elseif ( current_user_can( 'update_core' ) ) { printf( ' ' . __( '<a href="%s">Please update WordPress</a>.' ), self_admin_url( 'update-core.php' ) ); } elseif ( current_user_can( 'update_php' ) ) { printf( ' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ), esc_url( wp_get_update_php_url() ) ); wp_update_php_annotation( '</p><p><em>', '</em>' ); } } elseif ( ! $compatible_wp ) { printf( __( 'There is a new version of %s available, but it does not work with your version of WordPress.' ), $theme['Name'] ); if ( current_user_can( 'update_core' ) ) { printf( ' ' . __( '<a href="%s">Please update WordPress</a>.' ), self_admin_url( 'update-core.php' ) ); } } elseif ( ! $compatible_php ) { printf( __( 'There is a new version of %s available, but it does not work with your version of PHP.' ), $theme['Name'] ); if ( current_user_can( 'update_php' ) ) { printf( ' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ), esc_url( wp_get_update_php_url() ) ); wp_update_php_annotation( '</p><p><em>', '</em>' ); } } } do_action( "in_theme_update_message-{$theme_key}", $theme, $response ); echo '</p></div></td></tr>'; } function maintenance_nag() { require ABSPATH . WPINC . '/version.php'; global $upgrading; $nag = isset( $upgrading ); if ( ! $nag ) { $failed = get_site_option( 'auto_core_update_failed' ); $comparison = ! empty( $failed['critical'] ) ? '>=' : '>'; if ( isset( $failed['attempted'] ) && version_compare( $failed['attempted'], $wp_version, $comparison ) ) { $nag = true; } } if ( ! $nag ) { return false; } if ( current_user_can( 'update_core' ) ) { $msg = sprintf( __( 'An automated WordPress update has failed to complete - <a href="%s">please attempt the update again now</a>.' ), 'update-core.php' ); } else { $msg = __( 'An automated WordPress update has failed to complete! Please notify the site administrator.' ); } wp_admin_notice( $msg, array( 'type' => 'warning', 'additional_classes' => array( 'update-nag', 'inline' ), 'paragraph_wrap' => false, ) ); } function wp_print_admin_notice_templates() { ?>
	<script id="tmpl-wp-updates-admin-notice" type="text/html">
		<div <# if ( data.id ) { #>id="{{ data.id }}"<# } #> class="notice {{ data.className }}"><p>{{{ data.message }}}</p></div>
	</script>
	<script id="tmpl-wp-bulk-updates-admin-notice" type="text/html">
		<div id="{{ data.id }}" class="{{ data.className }} notice <# if ( data.errors ) { #>notice-error<# } else { #>notice-success<# } #>">
			<p>
				<# if ( data.successes ) { #>
					<# if ( 1 === data.successes ) { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
 printf( __( '%s plugin successfully updated.' ), '{{ data.successes }}' ); ?>
						<# } else { #>
							<?php
 printf( __( '%s theme successfully updated.' ), '{{ data.successes }}' ); ?>
						<# } #>
					<# } else { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
 printf( __( '%s plugins successfully updated.' ), '{{ data.successes }}' ); ?>
						<# } else { #>
							<?php
 printf( __( '%s themes successfully updated.' ), '{{ data.successes }}' ); ?>
						<# } #>
					<# } #>
				<# } #>
				<# if ( data.errors ) { #>
					<button class="button-link bulk-action-errors-collapsed" aria-expanded="false">
						<# if ( 1 === data.errors ) { #>
							<?php
 printf( __( '%s update failed.' ), '{{ data.errors }}' ); ?>
						<# } else { #>
							<?php
 printf( __( '%s updates failed.' ), '{{ data.errors }}' ); ?>
						<# } #>
						<span class="screen-reader-text">
							<?php
 _e( 'Show more details' ); ?>
						</span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
				<# } #>
			</p>
			<# if ( data.errors ) { #>
				<ul class="bulk-action-errors hidden">
					<# _.each( data.errorMessages, function( errorMessage ) { #>
						<li>{{ errorMessage }}</li>
					<# } ); #>
				</ul>
			<# } #>
		</div>
	</script>
	<?php
} function wp_print_update_row_templates() { ?>
	<script id="tmpl-item-update-row" type="text/template">
		<tr class="plugin-update-tr update" id="{{ data.slug }}-update" data-slug="{{ data.slug }}" <# if ( data.plugin ) { #>data-plugin="{{ data.plugin }}"<# } #>>
			<td colspan="{{ data.colspan }}" class="plugin-update colspanchange">
				{{{ data.content }}}
			</td>
		</tr>
	</script>
	<script id="tmpl-item-deleted-row" type="text/template">
		<tr class="plugin-deleted-tr inactive deleted" id="{{ data.slug }}-deleted" data-slug="{{ data.slug }}" <# if ( data.plugin ) { #>data-plugin="{{ data.plugin }}"<# } #>>
			<td colspan="{{ data.colspan }}" class="plugin-update colspanchange">
				<# if ( data.plugin ) { #>
					<?php
 printf( _x( '%s was successfully deleted.', 'plugin' ), '<strong>{{{ data.name }}}</strong>' ); ?>
				<# } else { #>
					<?php
 printf( _x( '%s was successfully deleted.', 'theme' ), '<strong>{{{ data.name }}}</strong>' ); ?>
				<# } #>
			</td>
		</tr>
	</script>
	<?php
} function wp_recovery_mode_nag() { if ( ! wp_is_recovery_mode() ) { return; } $url = wp_login_url(); $url = add_query_arg( 'action', WP_Recovery_Mode::EXIT_ACTION, $url ); $url = wp_nonce_url( $url, WP_Recovery_Mode::EXIT_ACTION ); $message = sprintf( __( 'You are in recovery mode. This means there may be an error with a theme or plugin. To exit recovery mode, log out or use the Exit button. <a href="%s">Exit Recovery Mode</a>' ), esc_url( $url ) ); wp_admin_notice( $message, array( 'type' => 'info' ) ); } function wp_is_auto_update_enabled_for_type( $type ) { if ( ! class_exists( 'WP_Automatic_Updater' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php'; } $updater = new WP_Automatic_Updater(); $enabled = ! $updater->is_disabled(); switch ( $type ) { case 'plugin': return apply_filters( 'plugins_auto_update_enabled', $enabled ); case 'theme': return apply_filters( 'themes_auto_update_enabled', $enabled ); } return false; } function wp_is_auto_update_forced_for_item( $type, $update, $item ) { return apply_filters( "auto_update_{$type}", $update, $item ); } function wp_get_auto_update_message() { $next_update_time = wp_next_scheduled( 'wp_version_check' ); if ( false === $next_update_time ) { $message = __( 'Automatic update not scheduled. There may be a problem with WP-Cron.' ); } else { $time_to_next_update = human_time_diff( (int) $next_update_time ); $overdue = ( time() - $next_update_time ) > 0; if ( $overdue ) { $message = sprintf( __( 'Automatic update overdue by %s. There may be a problem with WP-Cron.' ), $time_to_next_update ); } else { $message = sprintf( __( 'Automatic update scheduled in %s.' ), $time_to_next_update ); } } return $message; } 