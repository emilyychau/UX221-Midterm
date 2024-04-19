<?php
 require_once __DIR__ . '/admin.php'; if ( ! $taxnow ) { wp_die( __( 'Invalid taxonomy.' ) ); } $tax = get_taxonomy( $taxnow ); if ( ! $tax ) { wp_die( __( 'Invalid taxonomy.' ) ); } if ( ! in_array( $tax->name, get_taxonomies( array( 'show_ui' => true ) ), true ) ) { wp_die( __( 'Sorry, you are not allowed to edit terms in this taxonomy.' ) ); } if ( ! current_user_can( $tax->cap->manage_terms ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to manage terms in this taxonomy.' ) . '</p>', 403 ); } global $post_type; $wp_list_table = _get_list_table( 'WP_Terms_List_Table' ); $pagenum = $wp_list_table->get_pagenum(); $title = $tax->labels->name; if ( 'post' !== $post_type ) { $parent_file = ( 'attachment' === $post_type ) ? 'upload.php' : "edit.php?post_type=$post_type"; $submenu_file = "edit-tags.php?taxonomy=$taxonomy&amp;post_type=$post_type"; } elseif ( 'link_category' === $tax->name ) { $parent_file = 'link-manager.php'; $submenu_file = 'edit-tags.php?taxonomy=link_category'; } else { $parent_file = 'edit.php'; $submenu_file = "edit-tags.php?taxonomy=$taxonomy"; } add_screen_option( 'per_page', array( 'default' => 20, 'option' => 'edit_' . $tax->name . '_per_page', ) ); get_current_screen()->set_screen_reader_content( array( 'heading_pagination' => $tax->labels->items_list_navigation, 'heading_list' => $tax->labels->items_list, ) ); $location = false; $referer = wp_get_referer(); if ( ! $referer ) { $referer = wp_unslash( $_SERVER['REQUEST_URI'] ); } $referer = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'error', 'message', 'paged' ), $referer ); switch ( $wp_list_table->current_action() ) { case 'add-tag': check_admin_referer( 'add-tag', '_wpnonce_add-tag' ); if ( ! current_user_can( $tax->cap->edit_terms ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to create terms in this taxonomy.' ) . '</p>', 403 ); } $ret = wp_insert_term( $_POST['tag-name'], $taxonomy, $_POST ); if ( $ret && ! is_wp_error( $ret ) ) { $location = add_query_arg( 'message', 1, $referer ); } else { $location = add_query_arg( array( 'error' => true, 'message' => 4, ), $referer ); } break; case 'delete': if ( ! isset( $_REQUEST['tag_ID'] ) ) { break; } $tag_ID = (int) $_REQUEST['tag_ID']; check_admin_referer( 'delete-tag_' . $tag_ID ); if ( ! current_user_can( 'delete_term', $tag_ID ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to delete this item.' ) . '</p>', 403 ); } wp_delete_term( $tag_ID, $taxonomy ); $location = add_query_arg( 'message', 2, $referer ); $location = remove_query_arg( array( 'tag_ID', 'action' ), $location ); break; case 'bulk-delete': check_admin_referer( 'bulk-tags' ); if ( ! current_user_can( $tax->cap->delete_terms ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to delete these items.' ) . '</p>', 403 ); } $tags = (array) $_REQUEST['delete_tags']; foreach ( $tags as $tag_ID ) { wp_delete_term( $tag_ID, $taxonomy ); } $location = add_query_arg( 'message', 6, $referer ); break; case 'edit': if ( ! isset( $_REQUEST['tag_ID'] ) ) { break; } $term_id = (int) $_REQUEST['tag_ID']; $term = get_term( $term_id ); if ( ! $term instanceof WP_Term ) { wp_die( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?' ) ); } wp_redirect( sanitize_url( get_edit_term_link( $term_id, $taxonomy, $post_type ) ) ); exit; case 'editedtag': $tag_ID = (int) $_POST['tag_ID']; check_admin_referer( 'update-tag_' . $tag_ID ); if ( ! current_user_can( 'edit_term', $tag_ID ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to edit this item.' ) . '</p>', 403 ); } $tag = get_term( $tag_ID, $taxonomy ); if ( ! $tag ) { wp_die( __( 'You attempted to edit an item that does not exist. Perhaps it was deleted?' ) ); } $ret = wp_update_term( $tag_ID, $taxonomy, $_POST ); if ( $ret && ! is_wp_error( $ret ) ) { $location = add_query_arg( 'message', 3, $referer ); } else { $location = add_query_arg( array( 'error' => true, 'message' => 5, ), $referer ); } break; default: if ( ! $wp_list_table->current_action() || ! isset( $_REQUEST['delete_tags'] ) ) { break; } check_admin_referer( 'bulk-tags' ); $screen = get_current_screen()->id; $tags = (array) $_REQUEST['delete_tags']; $location = apply_filters( "handle_bulk_actions-{$screen}", $location, $wp_list_table->current_action(), $tags ); break; } if ( ! $location && ! empty( $_REQUEST['_wp_http_referer'] ) ) { $location = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); } if ( $location ) { if ( $pagenum > 1 ) { $location = add_query_arg( 'paged', $pagenum, $location ); } wp_redirect( apply_filters( 'redirect_term_location', $location, $tax ) ); exit; } $wp_list_table->prepare_items(); $total_pages = $wp_list_table->get_pagination_arg( 'total_pages' ); if ( $pagenum > $total_pages && $total_pages > 0 ) { wp_redirect( add_query_arg( 'paged', $total_pages ) ); exit; } wp_enqueue_script( 'admin-tags' ); if ( current_user_can( $tax->cap->edit_terms ) ) { wp_enqueue_script( 'inline-edit-tax' ); } if ( 'category' === $taxonomy || 'link_category' === $taxonomy || 'post_tag' === $taxonomy ) { $help = ''; if ( 'category' === $taxonomy ) { $help = '<p>' . sprintf( __( 'You can use categories to define sections of your site and group related posts. The default category is &#8220;Uncategorized&#8221; until you change it in your <a href="%s">writing settings</a>.' ), 'options-writing.php' ) . '</p>'; } elseif ( 'link_category' === $taxonomy ) { $help = '<p>' . __( 'You can create groups of links by using Link Categories. Link Category names must be unique and Link Categories are separate from the categories you use for posts.' ) . '</p>'; } else { $help = '<p>' . __( 'You can assign keywords to your posts using <strong>tags</strong>. Unlike categories, tags have no hierarchy, meaning there is no relationship from one tag to another.' ) . '</p>'; } if ( 'link_category' === $taxonomy ) { $help .= '<p>' . __( 'You can delete Link Categories in the Bulk Action pull-down, but that action does not delete the links within the category. Instead, it moves them to the default Link Category.' ) . '</p>'; } else { $help .= '<p>' . __( 'What&#8217;s the difference between categories and tags? Normally, tags are ad-hoc keywords that identify important information in your post (names, subjects, etc) that may or may not recur in other posts, while categories are pre-determined sections. If you think of your site like a book, the categories are like the Table of Contents and the tags are like the terms in the index.' ) . '</p>'; } get_current_screen()->add_help_tab( array( 'id' => 'overview', 'title' => __( 'Overview' ), 'content' => $help, ) ); if ( 'category' === $taxonomy || 'post_tag' === $taxonomy ) { if ( 'category' === $taxonomy ) { $help = '<p>' . __( 'When adding a new category on this screen, you&#8217;ll fill in the following fields:' ) . '</p>'; } else { $help = '<p>' . __( 'When adding a new tag on this screen, you&#8217;ll fill in the following fields:' ) . '</p>'; } $help .= '<ul>' . '<li>' . __( '<strong>Name</strong> &mdash; The name is how it appears on your site.' ) . '</li>'; $help .= '<li>' . __( '<strong>Slug</strong> &mdash; The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ) . '</li>'; if ( 'category' === $taxonomy ) { $help .= '<li>' . __( '<strong>Parent</strong> &mdash; Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have child categories for Bebop and Big Band. Totally optional. To create a subcategory, just choose another category from the Parent dropdown.' ) . '</li>'; } $help .= '<li>' . __( '<strong>Description</strong> &mdash; The description is not prominent by default; however, some themes may display it.' ) . '</li>' . '</ul>' . '<p>' . __( 'You can change the display of this screen using the Screen Options tab to set how many items are displayed per screen and to display/hide columns in the table.' ) . '</p>'; get_current_screen()->add_help_tab( array( 'id' => 'adding-terms', 'title' => 'category' === $taxonomy ? __( 'Adding Categories' ) : __( 'Adding Tags' ), 'content' => $help, ) ); } $help = '<p><strong>' . __( 'For more information:' ) . '</strong></p>'; if ( 'category' === $taxonomy ) { $help .= '<p>' . __( '<a href="https://wordpress.org/documentation/article/posts-categories-screen/">Documentation on Categories</a>' ) . '</p>'; } elseif ( 'link_category' === $taxonomy ) { $help .= '<p>' . __( '<a href="https://codex.wordpress.org/Links_Link_Categories_Screen">Documentation on Link Categories</a>' ) . '</p>'; } else { $help .= '<p>' . __( '<a href="https://wordpress.org/documentation/article/posts-tags-screen/">Documentation on Tags</a>' ) . '</p>'; } $help .= '<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'; get_current_screen()->set_help_sidebar( $help ); unset( $help ); } require_once ABSPATH . 'wp-admin/admin-header.php'; require_once ABSPATH . 'wp-admin/includes/edit-tag-messages.php'; $class = ( isset( $_REQUEST['error'] ) ) ? 'error' : 'updated'; if ( is_plugin_active( 'wpcat2tag-importer/wpcat2tag-importer.php' ) ) { $import_link = admin_url( 'admin.php?import=wpcat2tag' ); } else { $import_link = admin_url( 'import.php' ); } ?>

<div class="wrap nosubsub">
<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

<?php
if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) { echo '<span class="subtitle">'; printf( __( 'Search results for: %s' ), '<strong>' . esc_html( wp_unslash( $_REQUEST['s'] ) ) . '</strong>' ); echo '</span>'; } ?>

<hr class="wp-header-end">

<?php
if ( $message ) : wp_admin_notice( $message, array( 'id' => 'message', 'additional_classes' => array( $class ), 'dismissible' => true, ) ); $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message', 'error' ), $_SERVER['REQUEST_URI'] ); endif; ?>
<div id="ajax-response"></div>

<form class="search-form wp-clearfix" method="get">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />

<?php $wp_list_table->search_box( $tax->labels->search_items, 'tag' ); ?>

</form>

<?php
$can_edit_terms = current_user_can( $tax->cap->edit_terms ); if ( $can_edit_terms ) { ?>
<div id="col-container" class="wp-clearfix">

<div id="col-left">
<div class="col-wrap">

	<?php
 if ( 'category' === $taxonomy ) { do_action_deprecated( 'add_category_form_pre', array( (object) array( 'parent' => 0 ) ), '3.0.0', '{$taxonomy}_pre_add_form' ); } elseif ( 'link_category' === $taxonomy ) { do_action_deprecated( 'add_link_category_form_pre', array( (object) array( 'parent' => 0 ) ), '3.0.0', '{$taxonomy}_pre_add_form' ); } else { do_action_deprecated( 'add_tag_form_pre', array( $taxonomy ), '3.0.0', '{$taxonomy}_pre_add_form' ); } do_action( "{$taxonomy}_pre_add_form", $taxonomy ); ?>

<div class="form-wrap">
<h2><?php echo $tax->labels->add_new_item; ?></h2>
<form id="addtag" method="post" action="edit-tags.php" class="validate"
	<?php
 do_action( "{$taxonomy}_term_new_form_tag" ); ?>
>
<input type="hidden" name="action" value="add-tag" />
<input type="hidden" name="screen" value="<?php echo esc_attr( $current_screen->id ); ?>" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />
	<?php wp_nonce_field( 'add-tag', '_wpnonce_add-tag' ); ?>

<div class="form-field form-required term-name-wrap">
	<label for="tag-name"><?php _ex( 'Name', 'term name' ); ?></label>
	<input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true" aria-describedby="name-description" />
	<p id="name-description"><?php echo $tax->labels->name_field_description; ?></p>
</div>
<div class="form-field term-slug-wrap">
	<label for="tag-slug"><?php _e( 'Slug' ); ?></label>
	<input name="slug" id="tag-slug" type="text" value="" size="40" aria-describedby="slug-description" />
	<p id="slug-description"><?php echo $tax->labels->slug_field_description; ?></p>
</div>
	<?php if ( is_taxonomy_hierarchical( $taxonomy ) ) : ?>
<div class="form-field term-parent-wrap">
	<label for="parent"><?php echo esc_html( $tax->labels->parent_item ); ?></label>
		<?php
 $dropdown_args = array( 'hide_empty' => 0, 'hide_if_empty' => false, 'taxonomy' => $taxonomy, 'name' => 'parent', 'orderby' => 'name', 'hierarchical' => true, 'show_option_none' => __( 'None' ), ); $dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy, 'new' ); $dropdown_args['aria_describedby'] = 'parent-description'; wp_dropdown_categories( $dropdown_args ); ?>
		<?php if ( 'category' === $taxonomy ) : ?>
		<p id="parent-description"><?php _e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.' ); ?></p>
	<?php else : ?>
		<p id="parent-description"><?php echo $tax->labels->parent_field_description; ?></p>
	<?php endif; ?>
</div>
	<?php endif; ?>
<div class="form-field term-description-wrap">
	<label for="tag-description"><?php _e( 'Description' ); ?></label>
	<textarea name="description" id="tag-description" rows="5" cols="40" aria-describedby="description-description"></textarea>
	<p id="description-description"><?php echo $tax->labels->desc_field_description; ?></p>
</div>

	<?php
 if ( ! is_taxonomy_hierarchical( $taxonomy ) ) { do_action( 'add_tag_form_fields', $taxonomy ); } do_action( "{$taxonomy}_add_form_fields", $taxonomy ); ?>
	<p class="submit">
		<?php submit_button( $tax->labels->add_new_item, 'primary', 'submit', false ); ?>
		<span class="spinner"></span>
	</p>
	<?php
 if ( 'category' === $taxonomy ) { do_action_deprecated( 'edit_category_form', array( (object) array( 'parent' => 0 ) ), '3.0.0', '{$taxonomy}_add_form' ); } elseif ( 'link_category' === $taxonomy ) { do_action_deprecated( 'edit_link_category_form', array( (object) array( 'parent' => 0 ) ), '3.0.0', '{$taxonomy}_add_form' ); } else { do_action_deprecated( 'add_tag_form', array( $taxonomy ), '3.0.0', '{$taxonomy}_add_form' ); } do_action( "{$taxonomy}_add_form", $taxonomy ); ?>
</form></div>
</div>
</div><!-- /col-left -->

<div id="col-right">
<div class="col-wrap">
<?php } ?>

<?php $wp_list_table->views(); ?>

<form id="posts-filter" method="post">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />

<?php $wp_list_table->display(); ?>

</form>

<?php if ( 'category' === $taxonomy ) : ?>
<div class="form-wrap edit-term-notes">
<p>
	<?php
 printf( __( 'Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the default category %s. The default category cannot be deleted.' ), '<strong>' . apply_filters( 'the_category', get_cat_name( get_option( 'default_category' ) ), '', '' ) . '</strong>' ); ?>
</p>
	<?php if ( current_user_can( 'import' ) ) : ?>
	<p>
		<?php
 printf( __( 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.' ), esc_url( $import_link ) ); ?>
	</p>
	<?php endif; ?>
</div>
<?php elseif ( 'post_tag' === $taxonomy && current_user_can( 'import' ) ) : ?>
<div class="form-wrap edit-term-notes">
<p>
	<?php
 printf( __( 'Tags can be selectively converted to categories using the <a href="%s">tag to category converter</a>.' ), esc_url( $import_link ) ); ?>
	</p>
</div>
	<?php
endif; do_action( "after-{$taxonomy}-table", $taxonomy ); if ( $can_edit_terms ) { ?>
</div>
</div><!-- /col-right -->

</div><!-- /col-container -->
<?php } ?>

</div><!-- /wrap -->

<?php if ( ! wp_is_mobile() ) : ?>
<script type="text/javascript">
try{document.forms.addtag['tag-name'].focus();}catch(e){}
</script>
	<?php
endif; $wp_list_table->inline_edit(); require_once ABSPATH . 'wp-admin/admin-footer.php'; 