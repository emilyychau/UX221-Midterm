<?php
 function check_upload_size( $file ) { if ( get_site_option( 'upload_space_check_disabled' ) ) { return $file; } if ( $file['error'] > 0 ) { return $file; } if ( defined( 'WP_IMPORTING' ) ) { return $file; } $space_left = get_upload_space_available(); $file_size = filesize( $file['tmp_name'] ); if ( $space_left < $file_size ) { $file['error'] = sprintf( __( 'Not enough space to upload. %s KB needed.' ), number_format( ( $file_size - $space_left ) / KB_IN_BYTES ) ); } if ( $file_size > ( KB_IN_BYTES * get_site_option( 'fileupload_maxk', 1500 ) ) ) { $file['error'] = sprintf( __( 'This file is too big. Files must be less than %s KB in size.' ), get_site_option( 'fileupload_maxk', 1500 ) ); } if ( upload_is_user_over_quota( false ) ) { $file['error'] = __( 'You have used your space quota. Please delete files before uploading.' ); } if ( $file['error'] > 0 && ! isset( $_POST['html-upload'] ) && ! wp_doing_ajax() ) { wp_die( $file['error'] . ' <a href="javascript:history.go(-1)">' . __( 'Back' ) . '</a>' ); } return $file; } function wpmu_delete_blog( $blog_id, $drop = false ) { $blog_id = (int) $blog_id; $switch = false; if ( get_current_blog_id() !== $blog_id ) { $switch = true; switch_to_blog( $blog_id ); } $blog = get_site( $blog_id ); $current_network = get_network(); if ( $drop && ! $blog ) { $drop = false; } if ( $drop && ( 1 === $blog_id || is_main_site( $blog_id ) || ( $blog->path === $current_network->path && $blog->domain === $current_network->domain ) ) ) { $drop = false; } $upload_path = trim( get_option( 'upload_path' ) ); if ( $drop && get_site_option( 'ms_files_rewriting' ) && empty( $upload_path ) ) { $drop = false; } if ( $drop ) { wp_delete_site( $blog_id ); } else { do_action_deprecated( 'delete_blog', array( $blog_id, false ), '5.1.0' ); $users = get_users( array( 'blog_id' => $blog_id, 'fields' => 'ids', ) ); if ( ! empty( $users ) ) { foreach ( $users as $user_id ) { remove_user_from_blog( $user_id, $blog_id ); } } update_blog_status( $blog_id, 'deleted', 1 ); do_action_deprecated( 'deleted_blog', array( $blog_id, false ), '5.1.0' ); } if ( $switch ) { restore_current_blog(); } } function wpmu_delete_user( $id ) { global $wpdb; if ( ! is_numeric( $id ) ) { return false; } $id = (int) $id; $user = new WP_User( $id ); if ( ! $user->exists() ) { return false; } $_super_admins = get_super_admins(); if ( in_array( $user->user_login, $_super_admins, true ) ) { return false; } do_action( 'wpmu_delete_user', $id, $user ); $blogs = get_blogs_of_user( $id ); if ( ! empty( $blogs ) ) { foreach ( $blogs as $blog ) { switch_to_blog( $blog->userblog_id ); remove_user_from_blog( $id, $blog->userblog_id ); $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $id ) ); foreach ( (array) $post_ids as $post_id ) { wp_delete_post( $post_id ); } $link_ids = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $id ) ); if ( $link_ids ) { foreach ( $link_ids as $link_id ) { wp_delete_link( $link_id ); } } restore_current_blog(); } } $meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $id ) ); foreach ( $meta as $mid ) { delete_metadata_by_mid( 'user', $mid ); } $wpdb->delete( $wpdb->users, array( 'ID' => $id ) ); clean_user_cache( $user ); do_action( 'deleted_user', $id, null, $user ); return true; } function upload_is_user_over_quota( $display_message = true ) { if ( get_site_option( 'upload_space_check_disabled' ) ) { return false; } $space_allowed = get_space_allowed(); if ( ! is_numeric( $space_allowed ) ) { $space_allowed = 10; } $space_used = get_space_used(); if ( ( $space_allowed - $space_used ) < 0 ) { if ( $display_message ) { printf( __( 'Sorry, you have used your space allocation of %s. Please delete some files to upload more files.' ), size_format( $space_allowed * MB_IN_BYTES ) ); } return true; } else { return false; } } function display_space_usage() { $space_allowed = get_space_allowed(); $space_used = get_space_used(); $percent_used = ( $space_used / $space_allowed ) * 100; $space = size_format( $space_allowed * MB_IN_BYTES ); ?>
	<strong>
	<?php
 printf( __( 'Used: %1$s%% of %2$s' ), number_format( $percent_used ), $space ); ?>
	</strong>
	<?php
} function fix_import_form_size( $size ) { if ( upload_is_user_over_quota( false ) ) { return 0; } $available = get_upload_space_available(); return min( $size, $available ); } function upload_space_setting( $id ) { switch_to_blog( $id ); $quota = get_option( 'blog_upload_space' ); restore_current_blog(); if ( ! $quota ) { $quota = ''; } ?>
	<tr>
		<th><label for="blog-upload-space-number"><?php _e( 'Site Upload Space Quota' ); ?></label></th>
		<td>
			<input type="number" step="1" min="0" style="width: 100px"
				name="option[blog_upload_space]" id="blog-upload-space-number"
				aria-describedby="blog-upload-space-desc" value="<?php echo esc_attr( $quota ); ?>" />
			<span id="blog-upload-space-desc"><span class="screen-reader-text">
				<?php
 _e( 'Size in megabytes' ); ?>
			</span> <?php _e( 'MB (Leave blank for network default)' ); ?></span>
		</td>
	</tr>
	<?php
} function refresh_user_details( $id ) { $id = (int) $id; $user = get_userdata( $id ); if ( ! $user ) { return false; } clean_user_cache( $user ); return $id; } function format_code_lang( $code = '' ) { $code = strtolower( substr( $code, 0, 2 ) ); $lang_codes = array( 'aa' => 'Afar', 'ab' => 'Abkhazian', 'af' => 'Afrikaans', 'ak' => 'Akan', 'sq' => 'Albanian', 'am' => 'Amharic', 'ar' => 'Arabic', 'an' => 'Aragonese', 'hy' => 'Armenian', 'as' => 'Assamese', 'av' => 'Avaric', 'ae' => 'Avestan', 'ay' => 'Aymara', 'az' => 'Azerbaijani', 'ba' => 'Bashkir', 'bm' => 'Bambara', 'eu' => 'Basque', 'be' => 'Belarusian', 'bn' => 'Bengali', 'bh' => 'Bihari', 'bi' => 'Bislama', 'bs' => 'Bosnian', 'br' => 'Breton', 'bg' => 'Bulgarian', 'my' => 'Burmese', 'ca' => 'Catalan; Valencian', 'ch' => 'Chamorro', 'ce' => 'Chechen', 'zh' => 'Chinese', 'cu' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic', 'cv' => 'Chuvash', 'kw' => 'Cornish', 'co' => 'Corsican', 'cr' => 'Cree', 'cs' => 'Czech', 'da' => 'Danish', 'dv' => 'Divehi; Dhivehi; Maldivian', 'nl' => 'Dutch; Flemish', 'dz' => 'Dzongkha', 'en' => 'English', 'eo' => 'Esperanto', 'et' => 'Estonian', 'ee' => 'Ewe', 'fo' => 'Faroese', 'fj' => 'Fijjian', 'fi' => 'Finnish', 'fr' => 'French', 'fy' => 'Western Frisian', 'ff' => 'Fulah', 'ka' => 'Georgian', 'de' => 'German', 'gd' => 'Gaelic; Scottish Gaelic', 'ga' => 'Irish', 'gl' => 'Galician', 'gv' => 'Manx', 'el' => 'Greek, Modern', 'gn' => 'Guarani', 'gu' => 'Gujarati', 'ht' => 'Haitian; Haitian Creole', 'ha' => 'Hausa', 'he' => 'Hebrew', 'hz' => 'Herero', 'hi' => 'Hindi', 'ho' => 'Hiri Motu', 'hu' => 'Hungarian', 'ig' => 'Igbo', 'is' => 'Icelandic', 'io' => 'Ido', 'ii' => 'Sichuan Yi', 'iu' => 'Inuktitut', 'ie' => 'Interlingue', 'ia' => 'Interlingua (International Auxiliary Language Association)', 'id' => 'Indonesian', 'ik' => 'Inupiaq', 'it' => 'Italian', 'jv' => 'Javanese', 'ja' => 'Japanese', 'kl' => 'Kalaallisut; Greenlandic', 'kn' => 'Kannada', 'ks' => 'Kashmiri', 'kr' => 'Kanuri', 'kk' => 'Kazakh', 'km' => 'Central Khmer', 'ki' => 'Kikuyu; Gikuyu', 'rw' => 'Kinyarwanda', 'ky' => 'Kirghiz; Kyrgyz', 'kv' => 'Komi', 'kg' => 'Kongo', 'ko' => 'Korean', 'kj' => 'Kuanyama; Kwanyama', 'ku' => 'Kurdish', 'lo' => 'Lao', 'la' => 'Latin', 'lv' => 'Latvian', 'li' => 'Limburgan; Limburger; Limburgish', 'ln' => 'Lingala', 'lt' => 'Lithuanian', 'lb' => 'Luxembourgish; Letzeburgesch', 'lu' => 'Luba-Katanga', 'lg' => 'Ganda', 'mk' => 'Macedonian', 'mh' => 'Marshallese', 'ml' => 'Malayalam', 'mi' => 'Maori', 'mr' => 'Marathi', 'ms' => 'Malay', 'mg' => 'Malagasy', 'mt' => 'Maltese', 'mo' => 'Moldavian', 'mn' => 'Mongolian', 'na' => 'Nauru', 'nv' => 'Navajo; Navaho', 'nr' => 'Ndebele, South; South Ndebele', 'nd' => 'Ndebele, North; North Ndebele', 'ng' => 'Ndonga', 'ne' => 'Nepali', 'nn' => 'Norwegian Nynorsk; Nynorsk, Norwegian', 'nb' => 'Bokmål, Norwegian, Norwegian Bokmål', 'no' => 'Norwegian', 'ny' => 'Chichewa; Chewa; Nyanja', 'oc' => 'Occitan, Provençal', 'oj' => 'Ojibwa', 'or' => 'Oriya', 'om' => 'Oromo', 'os' => 'Ossetian; Ossetic', 'pa' => 'Panjabi; Punjabi', 'fa' => 'Persian', 'pi' => 'Pali', 'pl' => 'Polish', 'pt' => 'Portuguese', 'ps' => 'Pushto', 'qu' => 'Quechua', 'rm' => 'Romansh', 'ro' => 'Romanian', 'rn' => 'Rundi', 'ru' => 'Russian', 'sg' => 'Sango', 'sa' => 'Sanskrit', 'sr' => 'Serbian', 'hr' => 'Croatian', 'si' => 'Sinhala; Sinhalese', 'sk' => 'Slovak', 'sl' => 'Slovenian', 'se' => 'Northern Sami', 'sm' => 'Samoan', 'sn' => 'Shona', 'sd' => 'Sindhi', 'so' => 'Somali', 'st' => 'Sotho, Southern', 'es' => 'Spanish; Castilian', 'sc' => 'Sardinian', 'ss' => 'Swati', 'su' => 'Sundanese', 'sw' => 'Swahili', 'sv' => 'Swedish', 'ty' => 'Tahitian', 'ta' => 'Tamil', 'tt' => 'Tatar', 'te' => 'Telugu', 'tg' => 'Tajik', 'tl' => 'Tagalog', 'th' => 'Thai', 'bo' => 'Tibetan', 'ti' => 'Tigrinya', 'to' => 'Tonga (Tonga Islands)', 'tn' => 'Tswana', 'ts' => 'Tsonga', 'tk' => 'Turkmen', 'tr' => 'Turkish', 'tw' => 'Twi', 'ug' => 'Uighur; Uyghur', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'uz' => 'Uzbek', 've' => 'Venda', 'vi' => 'Vietnamese', 'vo' => 'Volapük', 'cy' => 'Welsh', 'wa' => 'Walloon', 'wo' => 'Wolof', 'xh' => 'Xhosa', 'yi' => 'Yiddish', 'yo' => 'Yoruba', 'za' => 'Zhuang; Chuang', 'zu' => 'Zulu', ); $lang_codes = apply_filters( 'lang_codes', $lang_codes, $code ); return strtr( $code, $lang_codes ); } function _access_denied_splash() { if ( ! is_user_logged_in() || is_network_admin() ) { return; } $blogs = get_blogs_of_user( get_current_user_id() ); if ( wp_list_filter( $blogs, array( 'userblog_id' => get_current_blog_id() ) ) ) { return; } $blog_name = get_bloginfo( 'name' ); if ( empty( $blogs ) ) { wp_die( sprintf( __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ), $blog_name ), 403 ); } $output = '<p>' . sprintf( __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ), $blog_name ) . '</p>'; $output .= '<p>' . __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ) . '</p>'; $output .= '<h3>' . __( 'Your Sites' ) . '</h3>'; $output .= '<table>'; foreach ( $blogs as $blog ) { $output .= '<tr>'; $output .= "<td>{$blog->blogname}</td>"; $output .= '<td><a href="' . esc_url( get_admin_url( $blog->userblog_id ) ) . '">' . __( 'Visit Dashboard' ) . '</a> | ' . '<a href="' . esc_url( get_home_url( $blog->userblog_id ) ) . '">' . __( 'View Site' ) . '</a></td>'; $output .= '</tr>'; } $output .= '</table>'; wp_die( $output, 403 ); } function check_import_new_users( $permission ) { if ( ! current_user_can( 'manage_network_users' ) ) { return false; } return true; } function mu_dropdown_languages( $lang_files = array(), $current = '' ) { $flag = false; $output = array(); foreach ( (array) $lang_files as $val ) { $code_lang = basename( $val, '.mo' ); if ( 'en_US' === $code_lang ) { $flag = true; $ae = __( 'American English' ); $output[ $ae ] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . $ae . '</option>'; } elseif ( 'en_GB' === $code_lang ) { $flag = true; $be = __( 'British English' ); $output[ $be ] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . $be . '</option>'; } else { $translated = format_code_lang( $code_lang ); $output[ $translated ] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . esc_html( $translated ) . '</option>'; } } if ( false === $flag ) { $output[] = '<option value=""' . selected( $current, '', false ) . '>' . __( 'English' ) . '</option>'; } uksort( $output, 'strnatcasecmp' ); $output = apply_filters( 'mu_dropdown_languages', $output, $lang_files, $current ); echo implode( "\n\t", $output ); } function site_admin_notice() { global $wp_db_version, $pagenow; if ( ! current_user_can( 'upgrade_network' ) ) { return false; } if ( 'upgrade.php' === $pagenow ) { return; } if ( (int) get_site_option( 'wpmu_upgrade_site' ) !== $wp_db_version ) { $upgrade_network_message = sprintf( __( 'Thank you for Updating! Please visit the <a href="%s">Upgrade Network</a> page to update all your sites.' ), esc_url( network_admin_url( 'upgrade.php' ) ) ); wp_admin_notice( $upgrade_network_message, array( 'type' => 'warning', 'additional_classes' => array( 'update-nag', 'inline' ), 'paragraph_wrap' => false, ) ); } } function avoid_blog_page_permalink_collision( $data, $postarr ) { if ( is_subdomain_install() ) { return $data; } if ( 'page' !== $data['post_type'] ) { return $data; } if ( ! isset( $data['post_name'] ) || '' === $data['post_name'] ) { return $data; } if ( ! is_main_site() ) { return $data; } if ( isset( $data['post_parent'] ) && $data['post_parent'] ) { return $data; } $post_name = $data['post_name']; $c = 0; while ( $c < 10 && get_id_from_blogname( $post_name ) ) { $post_name .= mt_rand( 1, 10 ); ++$c; } if ( $post_name !== $data['post_name'] ) { $data['post_name'] = $post_name; } return $data; } function choose_primary_blog() { ?>
	<table class="form-table" role="presentation">
	<tr>
	<?php  ?>
		<th scope="row"><label for="primary_blog"><?php _e( 'Primary Site' ); ?></label></th>
		<td>
		<?php
 $all_blogs = get_blogs_of_user( get_current_user_id() ); $primary_blog = (int) get_user_meta( get_current_user_id(), 'primary_blog', true ); if ( count( $all_blogs ) > 1 ) { $found = false; ?>
			<select name="primary_blog" id="primary_blog">
				<?php
 foreach ( (array) $all_blogs as $blog ) { if ( $blog->userblog_id === $primary_blog ) { $found = true; } ?>
					<option value="<?php echo $blog->userblog_id; ?>"<?php selected( $primary_blog, $blog->userblog_id ); ?>><?php echo esc_url( get_home_url( $blog->userblog_id ) ); ?></option>
					<?php
 } ?>
			</select>
			<?php
 if ( ! $found ) { $blog = reset( $all_blogs ); update_user_meta( get_current_user_id(), 'primary_blog', $blog->userblog_id ); } } elseif ( 1 === count( $all_blogs ) ) { $blog = reset( $all_blogs ); echo esc_url( get_home_url( $blog->userblog_id ) ); if ( $blog->userblog_id !== $primary_blog ) { update_user_meta( get_current_user_id(), 'primary_blog', $blog->userblog_id ); } } else { _e( 'Not available' ); } ?>
		</td>
	</tr>
	</table>
	<?php
} function can_edit_network( $network_id ) { if ( get_current_network_id() === (int) $network_id ) { $result = true; } else { $result = false; } return apply_filters( 'can_edit_network', $result, $network_id ); } function _thickbox_path_admin_subfolder() { ?>
<script type="text/javascript">
var tb_pathToImage = "<?php echo esc_js( includes_url( 'js/thickbox/loadingAnimation.gif', 'relative' ) ); ?>";
</script>
	<?php
} function confirm_delete_users( $users ) { $current_user = wp_get_current_user(); if ( ! is_array( $users ) || empty( $users ) ) { return false; } ?>
	<h1><?php esc_html_e( 'Users' ); ?></h1>

	<?php if ( 1 === count( $users ) ) : ?>
		<p><?php _e( 'You have chosen to delete the user from all networks and sites.' ); ?></p>
	<?php else : ?>
		<p><?php _e( 'You have chosen to delete the following users from all networks and sites.' ); ?></p>
	<?php endif; ?>

	<form action="users.php?action=dodelete" method="post">
	<input type="hidden" name="dodelete" />
	<?php
 wp_nonce_field( 'ms-users-delete' ); $site_admins = get_super_admins(); $admin_out = '<option value="' . esc_attr( $current_user->ID ) . '">' . $current_user->user_login . '</option>'; ?>
	<table class="form-table" role="presentation">
	<?php
 $allusers = (array) $_POST['allusers']; foreach ( $allusers as $user_id ) { if ( '' !== $user_id && '0' !== $user_id ) { $delete_user = get_userdata( $user_id ); if ( ! current_user_can( 'delete_user', $delete_user->ID ) ) { wp_die( sprintf( __( 'Warning! User %s cannot be deleted.' ), $delete_user->user_login ) ); } if ( in_array( $delete_user->user_login, $site_admins, true ) ) { wp_die( sprintf( __( 'Warning! User cannot be deleted. The user %s is a network administrator.' ), '<em>' . $delete_user->user_login . '</em>' ) ); } ?>
			<tr>
				<th scope="row"><?php echo $delete_user->user_login; ?>
					<?php echo '<input type="hidden" name="user[]" value="' . esc_attr( $user_id ) . '" />' . "\n"; ?>
				</th>
			<?php
 $blogs = get_blogs_of_user( $user_id, true ); if ( ! empty( $blogs ) ) { ?>
				<td><fieldset><p><legend>
				<?php
 printf( __( 'What should be done with content owned by %s?' ), '<em>' . $delete_user->user_login . '</em>' ); ?>
				</legend></p>
				<?php
 foreach ( (array) $blogs as $key => $details ) { $blog_users = get_users( array( 'blog_id' => $details->userblog_id, 'fields' => array( 'ID', 'user_login' ), ) ); if ( is_array( $blog_users ) && ! empty( $blog_users ) ) { $user_site = "<a href='" . esc_url( get_home_url( $details->userblog_id ) ) . "'>{$details->blogname}</a>"; $user_dropdown = '<label for="reassign_user" class="screen-reader-text">' . __( 'Select a user' ) . '</label>'; $user_dropdown .= "<select name='blog[$user_id][$key]' id='reassign_user'>"; $user_list = ''; foreach ( $blog_users as $user ) { if ( ! in_array( (int) $user->ID, $allusers, true ) ) { $user_list .= "<option value='{$user->ID}'>{$user->user_login}</option>"; } } if ( '' === $user_list ) { $user_list = $admin_out; } $user_dropdown .= $user_list; $user_dropdown .= "</select>\n"; ?>
						<ul style="list-style:none;">
							<li>
								<?php
 printf( __( 'Site: %s' ), $user_site ); ?>
							</li>
							<li><label><input type="radio" id="delete_option0" name="delete[<?php echo $details->userblog_id . '][' . $delete_user->ID; ?>]" value="delete" checked="checked" />
							<?php _e( 'Delete all content.' ); ?></label></li>
							<li><label><input type="radio" id="delete_option1" name="delete[<?php echo $details->userblog_id . '][' . $delete_user->ID; ?>]" value="reassign" />
							<?php _e( 'Attribute all content to:' ); ?></label>
							<?php echo $user_dropdown; ?></li>
						</ul>
						<?php
 } } echo '</fieldset></td></tr>'; } else { ?>
				<td><p><?php _e( 'User has no sites or content and will be deleted.' ); ?></p></td>
			<?php } ?>
			</tr>
			<?php
 } } ?>
	</table>
	<?php
 do_action( 'delete_user_form', $current_user, $allusers ); if ( 1 === count( $users ) ) : ?>
		<p><?php _e( 'Once you hit &#8220;Confirm Deletion&#8221;, the user will be permanently removed.' ); ?></p>
	<?php else : ?>
		<p><?php _e( 'Once you hit &#8220;Confirm Deletion&#8221;, these users will be permanently removed.' ); ?></p>
		<?php
 endif; submit_button( __( 'Confirm Deletion' ), 'primary' ); ?>
	</form>
	<?php
 return true; } function network_settings_add_js() { ?>
<script type="text/javascript">
jQuery( function($) {
	var languageSelect = $( '#WPLANG' );
	$( 'form' ).on( 'submit', function() {
		/*
		 * Don't show a spinner for English and installed languages,
		 * as there is nothing to download.
		 */
		if ( ! languageSelect.find( 'option:selected' ).data( 'installed' ) ) {
			$( '#submit', this ).after( '<span class="spinner language-install-spinner is-active" />' );
		}
	});
} );
</script>
	<?php
} function network_edit_site_nav( $args = array() ) { $links = apply_filters( 'network_edit_site_nav_links', array( 'site-info' => array( 'label' => __( 'Info' ), 'url' => 'site-info.php', 'cap' => 'manage_sites', ), 'site-users' => array( 'label' => __( 'Users' ), 'url' => 'site-users.php', 'cap' => 'manage_sites', ), 'site-themes' => array( 'label' => __( 'Themes' ), 'url' => 'site-themes.php', 'cap' => 'manage_sites', ), 'site-settings' => array( 'label' => __( 'Settings' ), 'url' => 'site-settings.php', 'cap' => 'manage_sites', ), ) ); $parsed_args = wp_parse_args( $args, array( 'blog_id' => isset( $_GET['blog_id'] ) ? (int) $_GET['blog_id'] : 0, 'links' => $links, 'selected' => 'site-info', ) ); $screen_links = array(); foreach ( $parsed_args['links'] as $link_id => $link ) { if ( ! current_user_can( $link['cap'], $parsed_args['blog_id'] ) ) { continue; } $classes = array( 'nav-tab' ); $aria_current = ''; if ( $parsed_args['selected'] === $link_id || $link['url'] === $GLOBALS['pagenow'] ) { $classes[] = 'nav-tab-active'; $aria_current = ' aria-current="page"'; } $esc_classes = implode( ' ', $classes ); $url = add_query_arg( array( 'id' => $parsed_args['blog_id'] ), network_admin_url( $link['url'] ) ); $screen_links[ $link_id ] = '<a href="' . esc_url( $url ) . '" id="' . esc_attr( $link_id ) . '" class="' . $esc_classes . '"' . $aria_current . '>' . esc_html( $link['label'] ) . '</a>'; } echo '<nav class="nav-tab-wrapper wp-clearfix" aria-label="' . esc_attr__( 'Secondary menu' ) . '">'; echo implode( '', $screen_links ); echo '</nav>'; } function get_site_screen_help_tab_args() { return array( 'id' => 'overview', 'title' => __( 'Overview' ), 'content' => '<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' . '<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' . '<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' . '<p>' . sprintf( __( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' . '<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>', ); } function get_site_screen_help_sidebar_content() { return '<p><strong>' . __( 'For more information:' ) . '</strong></p>' . '<p>' . __( '<a href="https://wordpress.org/documentation/article/network-admin-sites-screen/">Documentation on Site Management</a>' ) . '</p>' . '<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support forums</a>' ) . '</p>'; } 