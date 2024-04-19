<?php
 define( 'WP_INSTALLING', true ); define( 'WP_SETUP_CONFIG', true ); error_reporting( 0 ); if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', dirname( __DIR__ ) . '/' ); } require ABSPATH . 'wp-settings.php'; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; require_once ABSPATH . 'wp-admin/includes/translation-install.php'; nocache_headers(); if ( file_exists( ABSPATH . 'wp-config-sample.php' ) ) { $config_file = file( ABSPATH . 'wp-config-sample.php' ); } elseif ( file_exists( dirname( ABSPATH ) . '/wp-config-sample.php' ) ) { $config_file = file( dirname( ABSPATH ) . '/wp-config-sample.php' ); } else { wp_die( sprintf( __( 'Sorry, I need a %s file to work from. Please re-upload this file to your WordPress installation.' ), '<code>wp-config-sample.php</code>' ) ); } if ( file_exists( ABSPATH . 'wp-config.php' ) ) { wp_die( '<p>' . sprintf( __( 'The file %1$s already exists. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href="%2$s">installing now</a>.' ), '<code>wp-config.php</code>', 'install.php' ) . '</p>', 409 ); } if ( @file_exists( ABSPATH . '../wp-config.php' ) && ! @file_exists( ABSPATH . '../wp-settings.php' ) ) { wp_die( '<p>' . sprintf( __( 'The file %1$s already exists one level above your WordPress installation. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href="%2$s">installing now</a>.' ), '<code>wp-config.php</code>', 'install.php' ) . '</p>', 409 ); } $step = isset( $_GET['step'] ) ? (int) $_GET['step'] : -1; function setup_config_display_header( $body_classes = array() ) { $body_classes = (array) $body_classes; $body_classes[] = 'wp-core-ui'; $dir_attr = ''; if ( is_rtl() ) { $body_classes[] = 'rtl'; $dir_attr = ' dir="rtl"'; } header( 'Content-Type: text/html; charset=utf-8' ); ?>
<!DOCTYPE html>
<html<?php echo $dir_attr; ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow" />
	<title><?php _e( 'WordPress &rsaquo; Setup Configuration File' ); ?></title>
	<?php wp_admin_css( 'install', true ); ?>
</head>
<body class="<?php echo implode( ' ', $body_classes ); ?>">
<p id="logo"><?php _e( 'WordPress' ); ?></p>
	<?php
} $language = ''; if ( ! empty( $_REQUEST['language'] ) ) { $language = preg_replace( '/[^a-zA-Z0-9_]/', '', $_REQUEST['language'] ); } elseif ( isset( $GLOBALS['wp_local_package'] ) ) { $language = $GLOBALS['wp_local_package']; } switch ( $step ) { case -1: if ( wp_can_install_language_pack() && empty( $language ) ) { $languages = wp_get_available_translations(); if ( $languages ) { setup_config_display_header( 'language-chooser' ); echo '<h1 class="screen-reader-text">Select a default language</h1>'; echo '<form id="setup" method="post" action="?step=0">'; wp_install_language_form( $languages ); echo '</form>'; break; } } case 0: if ( ! empty( $language ) ) { $loaded_language = wp_download_language_pack( $language ); if ( $loaded_language ) { load_default_textdomain( $loaded_language ); $GLOBALS['wp_locale'] = new WP_Locale(); } } setup_config_display_header(); $step_1 = 'setup-config.php?step=1'; if ( isset( $_REQUEST['noapi'] ) ) { $step_1 .= '&amp;noapi'; } if ( ! empty( $loaded_language ) ) { $step_1 .= '&amp;language=' . $loaded_language; } ?>
<h1 class="screen-reader-text">
		<?php
 _e( 'Before getting started' ); ?>
</h1>
<p><?php _e( 'Welcome to WordPress. Before getting started, you will need to know the following items.' ); ?></p>
<ol>
	<li><?php _e( 'Database name' ); ?></li>
	<li><?php _e( 'Database username' ); ?></li>
	<li><?php _e( 'Database password' ); ?></li>
	<li><?php _e( 'Database host' ); ?></li>
	<li><?php _e( 'Table prefix (if you want to run more than one WordPress in a single database)' ); ?></li>
</ol>
<p>
		<?php
 printf( __( 'This information is being used to create a %s file.' ), '<code>wp-config.php</code>' ); ?>
	<strong>
		<?php
 printf( __( 'If for any reason this automatic file creation does not work, do not worry. All this does is fill in the database information to a configuration file. You may also simply open %1$s in a text editor, fill in your information, and save it as %2$s.' ), '<code>wp-config-sample.php</code>', '<code>wp-config.php</code>' ); ?>
	</strong>
		<?php
 printf( __( 'Need more help? <a href="%1$s">Read the support article on %2$s</a>.' ), __( 'https://wordpress.org/documentation/article/editing-wp-config-php/' ), '<code>wp-config.php</code>' ); ?>
</p>
<p><?php _e( 'In all likelihood, these items were supplied to you by your web host. If you do not have this information, then you will need to contact them before you can continue. If you are ready&hellip;' ); ?></p>

<p class="step"><a href="<?php echo $step_1; ?>" class="button button-large"><?php _e( 'Let&#8217;s go!' ); ?></a></p>
		<?php
 break; case 1: load_default_textdomain( $language ); $GLOBALS['wp_locale'] = new WP_Locale(); setup_config_display_header(); $autofocus = wp_is_mobile() ? '' : ' autofocus'; ?>
<h1 class="screen-reader-text">
		<?php
 _e( 'Set up your database connection' ); ?>
</h1>
<form method="post" action="setup-config.php?step=2">
	<p><?php _e( 'Below you should enter your database connection details. If you are not sure about these, contact your host.' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="dbname"><?php _e( 'Database Name' ); ?></label></th>
			<td><input name="dbname" id="dbname" type="text" aria-describedby="dbname-desc" size="25" placeholder="wordpress"<?php echo $autofocus; ?>/>
			<p id="dbname-desc"><?php _e( 'The name of the database you want to use with WordPress.' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="uname"><?php _e( 'Username' ); ?></label></th>
			<td><input name="uname" id="uname" type="text" aria-describedby="uname-desc" size="25" placeholder="<?php echo htmlspecialchars( _x( 'username', 'example username' ), ENT_QUOTES ); ?>" />
			<p id="uname-desc"><?php _e( 'Your database username.' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="pwd"><?php _e( 'Password' ); ?></label></th>
			<td>
				<div class="wp-pwd">
					<input name="pwd" id="pwd" type="password" class="regular-text" data-reveal="1" aria-describedby="pwd-desc" size="25" placeholder="<?php echo htmlspecialchars( _x( 'password', 'example password' ), ENT_QUOTES ); ?>" autocomplete="off" spellcheck="false" />
					<button type="button" class="button pwd-toggle hide-if-no-js" data-toggle="0" data-start-masked="1" aria-label="<?php esc_attr_e( 'Show password' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span class="text"><?php _e( 'Show' ); ?></span>
					</button>
				</div>
				<p id="pwd-desc"><?php _e( 'Your database password.' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbhost"><?php _e( 'Database Host' ); ?></label></th>
			<td><input name="dbhost" id="dbhost" type="text" aria-describedby="dbhost-desc" size="25" value="localhost" />
			<p id="dbhost-desc">
			<?php
 printf( __( 'You should be able to get this info from your web host, if %s does not work.' ), '<code>localhost</code>' ); ?>
			</p></td>
		</tr>
		<tr>
			<th scope="row"><label for="prefix"><?php _e( 'Table Prefix' ); ?></label></th>
			<td><input name="prefix" id="prefix" type="text" aria-describedby="prefix-desc" value="wp_" size="25" />
			<p id="prefix-desc"><?php _e( 'If you want to run multiple WordPress installations in a single database, change this.' ); ?></p></td>
		</tr>
	</table>
		<?php
 if ( isset( $_GET['noapi'] ) ) { ?>
<input name="noapi" type="hidden" value="1" /><?php } ?>
	<input type="hidden" name="language" value="<?php echo esc_attr( $language ); ?>" />
	<p class="step"><input name="submit" type="submit" value="<?php echo htmlspecialchars( __( 'Submit' ), ENT_QUOTES ); ?>" class="button button-large" /></p>
</form>
		<?php
 wp_print_scripts( 'password-toggle' ); break; case 2: load_default_textdomain( $language ); $GLOBALS['wp_locale'] = new WP_Locale(); $dbname = trim( wp_unslash( $_POST['dbname'] ) ); $uname = trim( wp_unslash( $_POST['uname'] ) ); $pwd = trim( wp_unslash( $_POST['pwd'] ) ); $dbhost = trim( wp_unslash( $_POST['dbhost'] ) ); $prefix = trim( wp_unslash( $_POST['prefix'] ) ); $step_1 = 'setup-config.php?step=1'; $install = 'install.php'; if ( isset( $_REQUEST['noapi'] ) ) { $step_1 .= '&amp;noapi'; } if ( ! empty( $language ) ) { $step_1 .= '&amp;language=' . $language; $install .= '?language=' . $language; } else { $install .= '?language=en_US'; } $tryagain_link = '</p><p class="step"><a href="' . $step_1 . '" onclick="javascript:history.go(-1);return false;" class="button button-large">' . __( 'Try Again' ) . '</a>'; if ( empty( $prefix ) ) { wp_die( __( '<strong>Error:</strong> "Table Prefix" must not be empty.' ) . $tryagain_link ); } if ( preg_match( '|[^a-z0-9_]|i', $prefix ) ) { wp_die( __( '<strong>Error:</strong> "Table Prefix" can only contain numbers, letters, and underscores.' ) . $tryagain_link ); } define( 'DB_NAME', $dbname ); define( 'DB_USER', $uname ); define( 'DB_PASSWORD', $pwd ); define( 'DB_HOST', $dbhost ); unset( $wpdb ); require_wp_db(); $wpdb->db_connect(); if ( ! empty( $wpdb->error ) ) { wp_die( $wpdb->error->get_error_message() . $tryagain_link ); } $errors = $wpdb->suppress_errors(); $wpdb->query( "SELECT $prefix" ); $wpdb->suppress_errors( $errors ); if ( ! $wpdb->last_error ) { wp_die( __( '<strong>Error:</strong> "Table Prefix" is invalid.' ) ); } try { $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|'; $max = strlen( $chars ) - 1; for ( $i = 0; $i < 8; $i++ ) { $key = ''; for ( $j = 0; $j < 64; $j++ ) { $key .= substr( $chars, random_int( 0, $max ), 1 ); } $secret_keys[] = $key; } } catch ( Exception $ex ) { $no_api = isset( $_POST['noapi'] ); if ( ! $no_api ) { $secret_keys = wp_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' ); } if ( $no_api || is_wp_error( $secret_keys ) ) { $secret_keys = array(); for ( $i = 0; $i < 8; $i++ ) { $secret_keys[] = wp_generate_password( 64, true, true ); } } else { $secret_keys = explode( "\n", wp_remote_retrieve_body( $secret_keys ) ); foreach ( $secret_keys as $k => $v ) { $secret_keys[ $k ] = substr( $v, 28, 64 ); } } } $key = 0; foreach ( $config_file as $line_num => $line ) { if ( str_starts_with( $line, '$table_prefix =' ) ) { $config_file[ $line_num ] = '$table_prefix = \'' . addcslashes( $prefix, "\\'" ) . "';\r\n"; continue; } if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',([ ]+)/', $line, $match ) ) { continue; } $constant = $match[1]; $padding = $match[2]; switch ( $constant ) { case 'DB_NAME': case 'DB_USER': case 'DB_PASSWORD': case 'DB_HOST': $config_file[ $line_num ] = "define( '" . $constant . "'," . $padding . "'" . addcslashes( constant( $constant ), "\\'" ) . "' );\r\n"; break; case 'DB_CHARSET': if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) { $config_file[ $line_num ] = "define( '" . $constant . "'," . $padding . "'utf8mb4' );\r\n"; } break; case 'AUTH_KEY': case 'SECURE_AUTH_KEY': case 'LOGGED_IN_KEY': case 'NONCE_KEY': case 'AUTH_SALT': case 'SECURE_AUTH_SALT': case 'LOGGED_IN_SALT': case 'NONCE_SALT': $config_file[ $line_num ] = "define( '" . $constant . "'," . $padding . "'" . $secret_keys[ $key++ ] . "' );\r\n"; break; } } unset( $line ); if ( ! is_writable( ABSPATH ) ) : setup_config_display_header(); ?>
<p>
			<?php
 printf( __( 'Unable to write to %s file.' ), '<code>wp-config.php</code>' ); ?>
</p>
<p id="wp-config-description">
			<?php
 printf( __( 'You can create the %s file manually and paste the following text into it.' ), '<code>wp-config.php</code>' ); $config_text = ''; foreach ( $config_file as $line ) { $config_text .= htmlentities( $line, ENT_COMPAT, 'UTF-8' ); } ?>
</p>
<p class="configuration-rules-label"><label for="wp-config">
			<?php
 printf( __( 'Configuration rules for %s:' ), '<code>wp-config.php</code>' ); ?>
	</label></p>
<textarea id="wp-config" cols="98" rows="15" class="code" readonly="readonly" aria-describedby="wp-config-description"><?php echo $config_text; ?></textarea>
<p><?php _e( 'After you&#8217;ve done that, click &#8220;Run the installation&#8221;.' ); ?></p>
<p class="step"><a href="<?php echo $install; ?>" class="button button-large"><?php _e( 'Run the installation' ); ?></a></p>
<script>
(function(){
if ( ! /iPad|iPod|iPhone/.test( navigator.userAgent ) ) {
	var el = document.getElementById('wp-config');
	el.focus();
	el.select();
}
})();
</script>
			<?php
 else : if ( file_exists( ABSPATH . 'wp-config-sample.php' ) ) { $path_to_wp_config = ABSPATH . 'wp-config.php'; } else { $path_to_wp_config = dirname( ABSPATH ) . '/wp-config.php'; } $error_message = ''; $handle = fopen( $path_to_wp_config, 'w' ); if ( false !== $handle ) { foreach ( $config_file as $line ) { fwrite( $handle, $line ); } fclose( $handle ); } else { $wp_config_perms = fileperms( $path_to_wp_config ); if ( ! empty( $wp_config_perms ) && ! is_writable( $path_to_wp_config ) ) { $error_message = sprintf( __( 'You need to make the file %1$s writable before you can save your changes. See <a href="%2$s">Changing File Permissions</a> for more information.' ), '<code>wp-config.php</code>', __( 'https://wordpress.org/documentation/article/changing-file-permissions/' ) ); } else { $error_message = sprintf( __( 'Unable to write to %s file.' ), '<code>wp-config.php</code>' ); } } chmod( $path_to_wp_config, 0666 ); setup_config_display_header(); if ( false !== $handle ) : ?>
<h1 class="screen-reader-text">
				<?php
 _e( 'Successful database connection' ); ?>
</h1>
<p><?php _e( 'All right, sparky! You&#8217;ve made it through this part of the installation. WordPress can now communicate with your database. If you are ready, time now to&hellip;' ); ?></p>

<p class="step"><a href="<?php echo $install; ?>" class="button button-large"><?php _e( 'Run the installation' ); ?></a></p>
				<?php
 else : printf( '<p>%s</p>', $error_message ); endif; endif; break; } ?>
<?php wp_print_scripts( 'language-chooser' ); ?>
</body>
</html>
