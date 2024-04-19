<?php
 require_once __DIR__ . '/admin.php'; require_once ABSPATH . 'wp-admin/includes/nav-menu.php'; if ( ! current_theme_supports( 'menus' ) && ! current_theme_supports( 'widgets' ) ) { wp_die( __( 'Your theme does not support navigation menus or widgets.' ) ); } if ( ! current_user_can( 'edit_theme_options' ) ) { wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to edit theme options on this site.' ) . '</p>', 403 ); } $title = __( 'Menus' ); wp_enqueue_script( 'nav-menu' ); if ( wp_is_mobile() ) { wp_enqueue_script( 'jquery-touch-punch' ); } $messages = array(); $nav_menu_selected_title = ''; $nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0; $locations = get_registered_nav_menus(); $menu_locations = get_nav_menu_locations(); $num_locations = count( array_keys( $locations ) ); $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'edit'; _wp_expand_nav_menu_post_data(); switch ( $action ) { case 'add-menu-item': check_admin_referer( 'add-menu_item', 'menu-settings-column-nonce' ); if ( isset( $_REQUEST['nav-menu-locations'] ) ) { set_theme_mod( 'nav_menu_locations', array_map( 'absint', $_REQUEST['menu-locations'] ) ); } elseif ( isset( $_REQUEST['menu-item'] ) ) { wp_save_nav_menu_items( $nav_menu_selected_id, $_REQUEST['menu-item'] ); } break; case 'move-down-menu-item': check_admin_referer( 'move-menu_item' ); $menu_item_id = isset( $_REQUEST['menu-item'] ) ? (int) $_REQUEST['menu-item'] : 0; if ( is_nav_menu_item( $menu_item_id ) ) { $menus = isset( $_REQUEST['menu'] ) ? array( (int) $_REQUEST['menu'] ) : wp_get_object_terms( $menu_item_id, 'nav_menu', array( 'fields' => 'ids' ) ); if ( ! is_wp_error( $menus ) && ! empty( $menus[0] ) ) { $menu_id = (int) $menus[0]; $ordered_menu_items = wp_get_nav_menu_items( $menu_id ); $menu_item_data = (array) wp_setup_nav_menu_item( get_post( $menu_item_id ) ); $dbids_to_orders = array(); $orders_to_dbids = array(); foreach ( (array) $ordered_menu_items as $ordered_menu_item_object ) { if ( isset( $ordered_menu_item_object->ID ) ) { if ( isset( $ordered_menu_item_object->menu_order ) ) { $dbids_to_orders[ $ordered_menu_item_object->ID ] = $ordered_menu_item_object->menu_order; $orders_to_dbids[ $ordered_menu_item_object->menu_order ] = $ordered_menu_item_object->ID; } } } if ( isset( $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] + 1 ] ) ) { $next_item_id = $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] + 1 ]; $next_item_data = (array) wp_setup_nav_menu_item( get_post( $next_item_id ) ); if ( ! empty( $menu_item_data['menu_item_parent'] ) && ( empty( $next_item_data['menu_item_parent'] ) || (int) $next_item_data['menu_item_parent'] !== (int) $menu_item_data['menu_item_parent'] ) ) { if ( in_array( (int) $menu_item_data['menu_item_parent'], $orders_to_dbids, true ) ) { $parent_db_id = (int) $menu_item_data['menu_item_parent']; } else { $parent_db_id = 0; } $parent_object = wp_setup_nav_menu_item( get_post( $parent_db_id ) ); if ( ! is_wp_error( $parent_object ) ) { $parent_data = (array) $parent_object; $menu_item_data['menu_item_parent'] = $parent_data['menu_item_parent']; $menu_item_data = _wp_reset_invalid_menu_item_parent( $menu_item_data ); update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] ); } } else { $next_item_data['menu_order'] = $next_item_data['menu_order'] - 1; $menu_item_data['menu_order'] = $menu_item_data['menu_order'] + 1; $menu_item_data['menu_item_parent'] = $next_item_data['ID']; $menu_item_data = _wp_reset_invalid_menu_item_parent( $menu_item_data ); update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] ); wp_update_post( $menu_item_data ); wp_update_post( $next_item_data ); } } elseif ( ! empty( $menu_item_data['menu_item_parent'] ) && in_array( (int) $menu_item_data['menu_item_parent'], $orders_to_dbids, true ) ) { $menu_item_data['menu_item_parent'] = (int) get_post_meta( $menu_item_data['menu_item_parent'], '_menu_item_menu_item_parent', true ); $menu_item_data = _wp_reset_invalid_menu_item_parent( $menu_item_data ); update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] ); } } } break; case 'move-up-menu-item': check_admin_referer( 'move-menu_item' ); $menu_item_id = isset( $_REQUEST['menu-item'] ) ? (int) $_REQUEST['menu-item'] : 0; if ( is_nav_menu_item( $menu_item_id ) ) { if ( isset( $_REQUEST['menu'] ) ) { $menus = array( (int) $_REQUEST['menu'] ); } else { $menus = wp_get_object_terms( $menu_item_id, 'nav_menu', array( 'fields' => 'ids' ) ); } if ( ! is_wp_error( $menus ) && ! empty( $menus[0] ) ) { $menu_id = (int) $menus[0]; $ordered_menu_items = wp_get_nav_menu_items( $menu_id ); $menu_item_data = (array) wp_setup_nav_menu_item( get_post( $menu_item_id ) ); $dbids_to_orders = array(); $orders_to_dbids = array(); foreach ( (array) $ordered_menu_items as $ordered_menu_item_object ) { if ( isset( $ordered_menu_item_object->ID ) ) { if ( isset( $ordered_menu_item_object->menu_order ) ) { $dbids_to_orders[ $ordered_menu_item_object->ID ] = $ordered_menu_item_object->menu_order; $orders_to_dbids[ $ordered_menu_item_object->menu_order ] = $ordered_menu_item_object->ID; } } } if ( ! empty( $dbids_to_orders[ $menu_item_id ] ) && ! empty( $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ] ) ) { if ( ! empty( $menu_item_data['menu_item_parent'] ) && in_array( (int) $menu_item_data['menu_item_parent'], array_keys( $dbids_to_orders ), true ) && isset( $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ] ) && ( (int) $menu_item_data['menu_item_parent'] === $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ] ) ) { if ( in_array( (int) $menu_item_data['menu_item_parent'], $orders_to_dbids, true ) ) { $parent_db_id = (int) $menu_item_data['menu_item_parent']; } else { $parent_db_id = 0; } $parent_object = wp_setup_nav_menu_item( get_post( $parent_db_id ) ); if ( ! is_wp_error( $parent_object ) ) { $parent_data = (array) $parent_object; if ( ! empty( $dbids_to_orders[ $parent_db_id ] ) && ! empty( $orders_to_dbids[ $dbids_to_orders[ $parent_db_id ] - 1 ] ) && ! empty( $parent_data['menu_item_parent'] ) ) { $menu_item_data['menu_item_parent'] = $parent_data['menu_item_parent']; } elseif ( ! empty( $dbids_to_orders[ $parent_db_id ] ) && ! empty( $orders_to_dbids[ $dbids_to_orders[ $parent_db_id ] - 1 ] ) ) { $_possible_parent_id = (int) get_post_meta( $orders_to_dbids[ $dbids_to_orders[ $parent_db_id ] - 1 ], '_menu_item_menu_item_parent', true ); if ( in_array( $_possible_parent_id, array_keys( $dbids_to_orders ), true ) ) { $menu_item_data['menu_item_parent'] = $_possible_parent_id; } else { $menu_item_data['menu_item_parent'] = 0; } } else { $menu_item_data['menu_item_parent'] = 0; } $parent_data['menu_order'] = $parent_data['menu_order'] + 1; $menu_item_data['menu_order'] = $menu_item_data['menu_order'] - 1; update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] ); wp_update_post( $menu_item_data ); wp_update_post( $parent_data ); } } elseif ( empty( $menu_item_data['menu_order'] ) || empty( $menu_item_data['menu_item_parent'] ) || ! in_array( (int) $menu_item_data['menu_item_parent'], array_keys( $dbids_to_orders ), true ) || empty( $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ] ) || $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ] !== (int) $menu_item_data['menu_item_parent'] ) { $menu_item_data['menu_item_parent'] = (int) $orders_to_dbids[ $dbids_to_orders[ $menu_item_id ] - 1 ]; $menu_item_data = _wp_reset_invalid_menu_item_parent( $menu_item_data ); update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] ); wp_update_post( $menu_item_data ); } } } } break; case 'delete-menu-item': $menu_item_id = (int) $_REQUEST['menu-item']; check_admin_referer( 'delete-menu_item_' . $menu_item_id ); if ( is_nav_menu_item( $menu_item_id ) && wp_delete_post( $menu_item_id, true ) ) { $messages[] = wp_get_admin_notice( __( 'The menu item has been successfully deleted.' ), array( 'id' => 'message', 'additional_classes' => array( 'updated' ), 'dismissible' => true, ) ); } break; case 'delete': check_admin_referer( 'delete-nav_menu-' . $nav_menu_selected_id ); if ( is_nav_menu( $nav_menu_selected_id ) ) { $deletion = wp_delete_nav_menu( $nav_menu_selected_id ); } else { $nav_menu_selected_id = 0; unset( $_REQUEST['menu'] ); } if ( ! isset( $deletion ) ) { break; } if ( is_wp_error( $deletion ) ) { $messages[] = wp_get_admin_notice( $deletion->get_error_message(), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); } else { $messages[] = wp_get_admin_notice( __( 'The menu has been successfully deleted.' ), array( 'id' => 'message', 'additional_classes' => array( 'updated' ), 'dismissible' => true, ) ); } break; case 'delete_menus': check_admin_referer( 'nav_menus_bulk_actions' ); foreach ( $_REQUEST['delete_menus'] as $menu_id_to_delete ) { if ( ! is_nav_menu( $menu_id_to_delete ) ) { continue; } $deletion = wp_delete_nav_menu( $menu_id_to_delete ); if ( is_wp_error( $deletion ) ) { $messages[] = wp_get_admin_notice( $deletion->get_error_message(), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); $deletion_error = true; } } if ( empty( $deletion_error ) ) { $messages[] = wp_get_admin_notice( __( 'Selected menus have been successfully deleted.' ), array( 'id' => 'message', 'additional_classes' => array( 'updated' ), 'dismissible' => true, ) ); } break; case 'update': check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' ); $new_menu_locations = array(); if ( isset( $_POST['menu-locations'] ) ) { $new_menu_locations = array_map( 'absint', $_POST['menu-locations'] ); $menu_locations = array_merge( $menu_locations, $new_menu_locations ); } if ( 0 === $nav_menu_selected_id ) { $new_menu_title = trim( esc_html( $_POST['menu-name'] ) ); if ( $new_menu_title ) { $_nav_menu_selected_id = wp_update_nav_menu_object( 0, array( 'menu-name' => $new_menu_title ) ); if ( is_wp_error( $_nav_menu_selected_id ) ) { $messages[] = wp_get_admin_notice( $_nav_menu_selected_id->get_error_message(), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); } else { $_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id ); $nav_menu_selected_id = $_nav_menu_selected_id; $nav_menu_selected_title = $_menu_object->name; if ( isset( $_REQUEST['menu-item'] ) ) { wp_save_nav_menu_items( $nav_menu_selected_id, absint( $_REQUEST['menu-item'] ) ); } if ( isset( $_REQUEST['zero-menu-state'] ) || ! empty( $_POST['auto-add-pages'] ) ) { wp_nav_menu_update_menu_items( $nav_menu_selected_id, $nav_menu_selected_title ); } if ( isset( $_REQUEST['zero-menu-state'] ) ) { $locations = get_nav_menu_locations(); foreach ( $locations as $location => $menu_id ) { $locations[ $location ] = $nav_menu_selected_id; break; } set_theme_mod( 'nav_menu_locations', $locations ); } elseif ( count( $new_menu_locations ) > 0 ) { $locations = get_nav_menu_locations(); foreach ( array_keys( $new_menu_locations ) as $location ) { $locations[ $location ] = $nav_menu_selected_id; } set_theme_mod( 'nav_menu_locations', $locations ); } if ( isset( $_REQUEST['use-location'] ) ) { $locations = get_registered_nav_menus(); $menu_locations = get_nav_menu_locations(); if ( isset( $locations[ $_REQUEST['use-location'] ] ) ) { $menu_locations[ $_REQUEST['use-location'] ] = $nav_menu_selected_id; } set_theme_mod( 'nav_menu_locations', $menu_locations ); } wp_redirect( admin_url( 'nav-menus.php?menu=' . $_nav_menu_selected_id ) ); exit; } } else { $messages[] = wp_get_admin_notice( __( 'Please enter a valid menu name.' ), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); } } else { foreach ( $locations as $location => $description ) { if ( ( empty( $_POST['menu-locations'] ) || empty( $_POST['menu-locations'][ $location ] ) ) && isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] === $nav_menu_selected_id ) { unset( $menu_locations[ $location ] ); } } set_theme_mod( 'nav_menu_locations', $menu_locations ); $_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id ); $menu_title = trim( esc_html( $_POST['menu-name'] ) ); if ( ! $menu_title ) { $messages[] = wp_get_admin_notice( __( 'Please enter a valid menu name.' ), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); $menu_title = $_menu_object->name; } if ( ! is_wp_error( $_menu_object ) ) { $_nav_menu_selected_id = wp_update_nav_menu_object( $nav_menu_selected_id, array( 'menu-name' => $menu_title ) ); if ( is_wp_error( $_nav_menu_selected_id ) ) { $_menu_object = $_nav_menu_selected_id; $messages[] = wp_get_admin_notice( $_nav_menu_selected_id->get_error_message(), array( 'id' => 'message', 'additional_classes' => array( 'error' ), 'dismissible' => true, ) ); } else { $_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id ); $nav_menu_selected_title = $_menu_object->name; } } if ( ! is_wp_error( $_menu_object ) ) { $messages = array_merge( $messages, wp_nav_menu_update_menu_items( $_nav_menu_selected_id, $nav_menu_selected_title ) ); if ( $nav_menu_selected_id !== $_nav_menu_selected_id ) { wp_redirect( admin_url( 'nav-menus.php?menu=' . (int) $_nav_menu_selected_id ) ); exit; } } } break; case 'locations': if ( ! $num_locations ) { wp_redirect( admin_url( 'nav-menus.php' ) ); exit; } add_filter( 'screen_options_show_screen', '__return_false' ); if ( isset( $_POST['menu-locations'] ) ) { check_admin_referer( 'save-menu-locations' ); $new_menu_locations = array_map( 'absint', $_POST['menu-locations'] ); $menu_locations = array_merge( $menu_locations, $new_menu_locations ); set_theme_mod( 'nav_menu_locations', $menu_locations ); $messages[] = wp_get_admin_notice( __( 'Menu locations updated.' ), array( 'id' => 'message', 'additional_classes' => array( 'updated' ), 'dismissible' => true, ) ); } break; } $nav_menus = wp_get_nav_menus(); $menu_count = count( $nav_menus ); $add_new_screen = ( isset( $_GET['menu'] ) && 0 === (int) $_GET['menu'] ) ? true : false; $locations_screen = ( isset( $_GET['action'] ) && 'locations' === $_GET['action'] ) ? true : false; $page_count = wp_count_posts( 'page' ); if ( 1 === count( get_registered_nav_menus() ) && ! $add_new_screen && empty( $nav_menus ) && ! empty( $page_count->publish ) ) { $one_theme_location_no_menus = true; } else { $one_theme_location_no_menus = false; } $nav_menus_l10n = array( 'oneThemeLocationNoMenus' => $one_theme_location_no_menus, 'moveUp' => __( 'Move up one' ), 'moveDown' => __( 'Move down one' ), 'moveToTop' => __( 'Move to the top' ), 'moveUnder' => __( 'Move under %s' ), 'moveOutFrom' => __( 'Move out from under %s' ), 'under' => __( 'Under %s' ), 'outFrom' => __( 'Out from under %s' ), 'menuFocus' => __( '%1$s. Menu item %2$d of %3$d.' ), 'subMenuFocus' => __( '%1$s. Sub item number %2$d under %3$s.' ), 'menuItemDeletion' => __( 'item %s' ), 'itemsDeleted' => __( 'Deleted menu item: %s.' ), 'itemAdded' => __( 'Menu item added' ), 'itemRemoved' => __( 'Menu item removed' ), 'movedUp' => __( 'Menu item moved up' ), 'movedDown' => __( 'Menu item moved down' ), 'movedTop' => __( 'Menu item moved to the top' ), 'movedLeft' => __( 'Menu item moved out of submenu' ), 'movedRight' => __( 'Menu item is now a sub-item' ), ); wp_localize_script( 'nav-menu', 'menus', $nav_menus_l10n ); if ( 0 === $menu_count && ! $add_new_screen && ! $one_theme_location_no_menus ) { wp_redirect( admin_url( 'nav-menus.php?action=edit&menu=0' ) ); } $recently_edited = absint( get_user_option( 'nav_menu_recently_edited' ) ); if ( empty( $recently_edited ) && is_nav_menu( $nav_menu_selected_id ) ) { $recently_edited = $nav_menu_selected_id; } if ( empty( $nav_menu_selected_id ) && ! isset( $_GET['menu'] ) && is_nav_menu( $recently_edited ) ) { $nav_menu_selected_id = $recently_edited; } if ( ! $add_new_screen && $menu_count > 0 && isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) { $nav_menu_selected_id = $nav_menus[0]->term_id; } if ( $one_theme_location_no_menus ) { $nav_menu_selected_id = 0; } elseif ( empty( $nav_menu_selected_id ) && ! empty( $nav_menus ) && ! $add_new_screen ) { $nav_menu_selected_id = $nav_menus[0]->term_id; } if ( $nav_menu_selected_id !== $recently_edited && is_nav_menu( $nav_menu_selected_id ) ) { update_user_meta( $current_user->ID, 'nav_menu_recently_edited', $nav_menu_selected_id ); } if ( ! $nav_menu_selected_title && is_nav_menu( $nav_menu_selected_id ) ) { $_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id ); $nav_menu_selected_title = ! is_wp_error( $_menu_object ) ? $_menu_object->name : ''; } foreach ( (array) $nav_menus as $key => $_nav_menu ) { $nav_menus[ $key ]->truncated_name = wp_html_excerpt( $_nav_menu->name, 40, '&hellip;' ); } if ( current_theme_supports( 'menus' ) ) { $locations = get_registered_nav_menus(); $menu_locations = get_nav_menu_locations(); } global $_wp_nav_menu_max_depth; $_wp_nav_menu_max_depth = 0; if ( is_nav_menu( $nav_menu_selected_id ) ) { $menu_items = wp_get_nav_menu_items( $nav_menu_selected_id, array( 'post_status' => 'any' ) ); $edit_markup = wp_get_nav_menu_to_edit( $nav_menu_selected_id ); } function wp_nav_menu_max_depth( $classes ) { global $_wp_nav_menu_max_depth; return "$classes menu-max-depth-$_wp_nav_menu_max_depth"; } add_filter( 'admin_body_class', 'wp_nav_menu_max_depth' ); wp_nav_menu_setup(); wp_initial_nav_menu_meta_boxes(); if ( ! current_theme_supports( 'menus' ) && ! $num_locations ) { $message_no_theme_support = sprintf( __( 'Your theme does not natively support menus, but you can use them in sidebars by adding a &#8220;Navigation Menu&#8221; widget on the <a href="%s">Widgets</a> screen.' ), admin_url( 'widgets.php' ) ); $messages[] = wp_get_admin_notice( $message_no_theme_support, array( 'id' => 'message', 'additional_classes' => array( 'updated' ), 'dismissible' => true, ) ); } if ( ! $locations_screen ) : $overview = '<p>' . __( 'This screen is used for managing your navigation menus.' ) . '</p>'; $overview .= '<p>' . sprintf( __( 'Menus can be displayed in locations defined by your theme, even used in sidebars by adding a &#8220;Navigation Menu&#8221; widget on the <a href="%1$s">Widgets</a> screen. If your theme does not support the navigation menus feature (the default themes, %2$s and %3$s, do), you can learn about adding this support by following the documentation link to the side.' ), admin_url( 'widgets.php' ), 'Twenty Twenty', 'Twenty Twenty-One' ) . '</p>'; $overview .= '<p>' . __( 'From this screen you can:' ) . '</p>'; $overview .= '<ul><li>' . __( 'Create, edit, and delete menus' ) . '</li>'; $overview .= '<li>' . __( 'Add, organize, and modify individual menu items' ) . '</li></ul>'; get_current_screen()->add_help_tab( array( 'id' => 'overview', 'title' => __( 'Overview' ), 'content' => $overview, ) ); $menu_management = '<p>' . __( 'The menu management box at the top of the screen is used to control which menu is opened in the editor below.' ) . '</p>'; $menu_management .= '<ul><li>' . __( 'To edit an existing menu, <strong>choose a menu from the dropdown and click Select</strong>' ) . '</li>'; $menu_management .= '<li>' . __( 'If you have not yet created any menus, <strong>click the &#8217;create a new menu&#8217; link</strong> to get started' ) . '</li></ul>'; $menu_management .= '<p>' . __( 'You can assign theme locations to individual menus by <strong>selecting the desired settings</strong> at the bottom of the menu editor. To assign menus to all theme locations at once, <strong>visit the Manage Locations tab</strong> at the top of the screen.' ) . '</p>'; get_current_screen()->add_help_tab( array( 'id' => 'menu-management', 'title' => __( 'Menu Management' ), 'content' => $menu_management, ) ); $editing_menus = '<p>' . __( 'Each navigation menu may contain a mix of links to pages, categories, custom URLs or other content types. Menu links are added by selecting items from the expanding boxes in the left-hand column below.' ) . '</p>'; $editing_menus .= '<p>' . __( '<strong>Clicking the arrow to the right of any menu item</strong> in the editor will reveal a standard group of settings. Additional settings such as link target, CSS classes, link relationships, and link descriptions can be enabled and disabled via the Screen Options tab.' ) . '</p>'; $editing_menus .= '<ul><li>' . __( 'Add one or several items at once by <strong>selecting the checkbox next to each item and clicking Add to Menu</strong>' ) . '</li>'; $editing_menus .= '<li>' . __( 'To add a custom link, <strong>expand the Custom Links section, enter a URL and link text, and click Add to Menu</strong>' ) . '</li>'; $editing_menus .= '<li>' . __( 'To reorganize menu items, <strong>drag and drop items with your mouse or use your keyboard</strong>. Drag or move a menu item a little to the right to make it a submenu' ) . '</li>'; $editing_menus .= '<li>' . __( 'Delete a menu item by <strong>expanding it and clicking the Remove link</strong>' ) . '</li></ul>'; get_current_screen()->add_help_tab( array( 'id' => 'editing-menus', 'title' => __( 'Editing Menus' ), 'content' => $editing_menus, ) ); else : $locations_overview = '<p>' . __( 'This screen is used for globally assigning menus to locations defined by your theme.' ) . '</p>'; $locations_overview .= '<ul><li>' . __( 'To assign menus to one or more theme locations, <strong>select a menu from each location&#8217;s dropdown</strong>. When you are finished, <strong>click Save Changes</strong>' ) . '</li>'; $locations_overview .= '<li>' . __( 'To edit a menu currently assigned to a theme location, <strong>click the adjacent &#8217;Edit&#8217; link</strong>' ) . '</li>'; $locations_overview .= '<li>' . __( 'To add a new menu instead of assigning an existing one, <strong>click the &#8217;Use new menu&#8217; link</strong>. Your new menu will be automatically assigned to that theme location' ) . '</li></ul>'; get_current_screen()->add_help_tab( array( 'id' => 'locations-overview', 'title' => __( 'Overview' ), 'content' => $locations_overview, ) ); endif; get_current_screen()->set_help_sidebar( '<p><strong>' . __( 'For more information:' ) . '</strong></p>' . '<p>' . __( '<a href="https://wordpress.org/documentation/article/appearance-menus-screen/">Documentation on Menus</a>' ) . '</p>' . '<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>' ); require_once ABSPATH . 'wp-admin/admin-header.php'; ?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Menus' ); ?></h1>
	<?php
 if ( current_user_can( 'customize' ) ) : $focus = $locations_screen ? array( 'section' => 'menu_locations' ) : array( 'panel' => 'nav_menus' ); printf( ' <a class="page-title-action hide-if-no-customize" href="%1$s">%2$s</a>', esc_url( add_query_arg( array( array( 'autofocus' => $focus ), 'return' => urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), ), admin_url( 'customize.php' ) ) ), __( 'Manage with Live Preview' ) ); endif; $nav_tab_active_class = ''; $nav_aria_current = ''; if ( ! isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'locations' !== $_GET['action'] ) { $nav_tab_active_class = ' nav-tab-active'; $nav_aria_current = ' aria-current="page"'; } ?>

	<hr class="wp-header-end">

	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" class="nav-tab<?php echo $nav_tab_active_class; ?>"<?php echo $nav_aria_current; ?>><?php esc_html_e( 'Edit Menus' ); ?></a>
		<?php
 if ( $num_locations && $menu_count ) { $active_tab_class = ''; $aria_current = ''; if ( $locations_screen ) { $active_tab_class = ' nav-tab-active'; $aria_current = ' aria-current="page"'; } ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>" class="nav-tab<?php echo $active_tab_class; ?>"<?php echo $aria_current; ?>><?php esc_html_e( 'Manage Locations' ); ?></a>
			<?php
 } ?>
	</nav>
	<?php
 foreach ( $messages as $message ) : echo $message . "\n"; endforeach; ?>
	<?php
 if ( $locations_screen ) : if ( 1 === $num_locations ) { echo '<p>' . __( 'Your theme supports one menu. Select which menu you would like to use.' ) . '</p>'; } else { echo '<p>' . sprintf( _n( 'Your theme supports %s menu. Select which menu appears in each location.', 'Your theme supports %s menus. Select which menu appears in each location.', $num_locations ), number_format_i18n( $num_locations ) ) . '</p>'; } ?>
	<div id="menu-locations-wrap">
		<form method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>">
			<table class="widefat fixed" id="menu-locations-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column column-locations"><?php _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus"><?php _e( 'Assigned Menu' ); ?></th>
				</tr>
				</thead>
				<tbody class="menu-locations">
				<?php foreach ( $locations as $_location => $_name ) { ?>
					<tr class="menu-locations-row">
						<td class="menu-location-title"><label for="locations-<?php echo $_location; ?>"><?php echo $_name; ?></label></td>
						<td class="menu-location-menus">
							<select name="menu-locations[<?php echo $_location; ?>]" id="locations-<?php echo $_location; ?>">
								<option value="0"><?php printf( '&mdash; %s &mdash;', esc_html__( 'Select a Menu' ) ); ?></option>
								<?php
 foreach ( $nav_menus as $menu ) : $data_orig = ''; $selected = isset( $menu_locations[ $_location ] ) && $menu_locations[ $_location ] === $menu->term_id; if ( $selected ) { $data_orig = 'data-orig="true"'; } ?>
									<option <?php echo $data_orig; ?> <?php selected( $selected ); ?> value="<?php echo $menu->term_id; ?>">
										<?php echo wp_html_excerpt( $menu->name, 40, '&hellip;' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="locations-row-links">
								<?php if ( isset( $menu_locations[ $_location ] ) && 0 !== $menu_locations[ $_location ] ) : ?>
								<span class="locations-edit-menu-link">
									<a href="
									<?php
 echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => $menu_locations[ $_location ], ), admin_url( 'nav-menus.php' ) ) ); ?>
									">
										<span aria-hidden="true"><?php _ex( 'Edit', 'menu' ); ?></span><span class="screen-reader-text">
											<?php
 _e( 'Edit selected menu' ); ?>
										</span>
									</a>
								</span>
								<?php endif; ?>
								<span class="locations-add-menu-link">
									<a href="
									<?php
 echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0, 'use-location' => $_location, ), admin_url( 'nav-menus.php' ) ) ); ?>
									">
										<?php _ex( 'Use new menu', 'menu' ); ?>
									</a>
								</span>
							</div><!-- .locations-row-links -->
						</td><!-- .menu-location-menus -->
					</tr><!-- .menu-locations-row -->
				<?php } ?>
				</tbody>
			</table>
			<p class="button-controls wp-clearfix"><?php submit_button( __( 'Save Changes' ), 'primary left', 'nav-menu-locations', false ); ?></p>
			<?php wp_nonce_field( 'save-menu-locations' ); ?>
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
		</form>
	</div><!-- #menu-locations-wrap -->
		<?php
 do_action( 'after_menu_locations_table' ); ?>
	<?php else : ?>
	<div class="manage-menus">
		<?php if ( $menu_count < 1 ) : ?>
		<span class="first-menu-message">
			<?php _e( 'Create your first menu below.' ); ?>
			<span class="screen-reader-text">
				<?php
 _e( 'Fill in the Menu Name and click the Create Menu button to create your first menu.' ); ?>
			</span>
		</span><!-- /first-menu-message -->
		<?php elseif ( $menu_count < 2 ) : ?>
		<span class="add-edit-menu-action">
			<?php
 printf( __( 'Edit your menu below, or <a href="%s">create a new menu</a>. Do not forget to save your changes!' ), esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0, ), admin_url( 'nav-menus.php' ) ) ) ); ?>
			<span class="screen-reader-text">
				<?php
 _e( 'Click the Save Menu button to save your changes.' ); ?>
			</span>
		</span><!-- /add-edit-menu-action -->
		<?php else : ?>
			<form method="get" action="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">
			<input type="hidden" name="action" value="edit" />
			<label for="select-menu-to-edit" class="selected-menu"><?php _e( 'Select a menu to edit:' ); ?></label>
			<select name="menu" id="select-menu-to-edit">
				<?php if ( $add_new_screen ) : ?>
					<option value="0" selected="selected"><?php _e( '&mdash; Select &mdash;' ); ?></option>
				<?php endif; ?>
				<?php foreach ( (array) $nav_menus as $_nav_menu ) : ?>
					<option value="<?php echo esc_attr( $_nav_menu->term_id ); ?>" <?php selected( $_nav_menu->term_id, $nav_menu_selected_id ); ?>>
						<?php
 echo esc_html( $_nav_menu->truncated_name ); if ( ! empty( $menu_locations ) && in_array( $_nav_menu->term_id, $menu_locations, true ) ) { $locations_assigned_to_this_menu = array(); foreach ( array_keys( $menu_locations, $_nav_menu->term_id, true ) as $menu_location_key ) { if ( isset( $locations[ $menu_location_key ] ) ) { $locations_assigned_to_this_menu[] = $locations[ $menu_location_key ]; } } $locations_listed_per_menu = absint( apply_filters( 'wp_nav_locations_listed_per_menu', 3 ) ); $assigned_locations = array_slice( $locations_assigned_to_this_menu, 0, $locations_listed_per_menu ); if ( ! empty( $assigned_locations ) ) { printf( ' (%1$s%2$s)', implode( ', ', $assigned_locations ), count( $locations_assigned_to_this_menu ) > count( $assigned_locations ) ? ' &hellip;' : '' ); } } ?>
					</option>
				<?php endforeach; ?>
			</select>
			<span class="submit-btn"><input type="submit" class="button" value="<?php esc_attr_e( 'Select' ); ?>"></span>
			<span class="add-new-menu-action">
				<?php
 printf( __( 'or <a href="%s">create a new menu</a>. Do not forget to save your changes!' ), esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0, ), admin_url( 'nav-menus.php' ) ) ) ); ?>
				<span class="screen-reader-text">
					<?php
 _e( 'Click the Save Menu button to save your changes.' ); ?>
				</span>
			</span><!-- /add-new-menu-action -->
		</form>
			<?php
 endif; $metabox_holder_disabled_class = ''; if ( isset( $_GET['menu'] ) && 0 === (int) $_GET['menu'] ) { $metabox_holder_disabled_class = ' metabox-holder-disabled'; } ?>
	</div><!-- /manage-menus -->
	<div id="nav-menus-frame" class="wp-clearfix">
	<div id="menu-settings-column" class="metabox-holder<?php echo $metabox_holder_disabled_class; ?>">

		<div class="clear"></div>

		<form id="nav-menu-meta" class="nav-menu-meta" method="post" enctype="multipart/form-data">
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
			<input type="hidden" name="action" value="add-menu-item" />
			<?php wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' ); ?>
			<h2><?php _e( 'Add menu items' ); ?></h2>
			<?php do_accordion_sections( 'nav-menus', 'side', null ); ?>
		</form>

	</div><!-- /#menu-settings-column -->
	<div id="menu-management-liquid">
		<div id="menu-management">
			<form id="update-nav-menu" method="post" enctype="multipart/form-data">
				<h2><?php _e( 'Menu structure' ); ?></h2>
				<div class="menu-edit">
					<input type="hidden" name="nav-menu-data">
					<?php
 wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); wp_nonce_field( 'update-nav_menu', 'update-nav-menu-nonce' ); $menu_name_aria_desc = $add_new_screen ? ' aria-describedby="menu-name-desc"' : ''; if ( $one_theme_location_no_menus ) { $menu_name_val = 'value="' . esc_attr( 'Menu 1' ) . '"'; ?>
						<input type="hidden" name="zero-menu-state" value="true" />
						<?php
 } else { $menu_name_val = 'value="' . esc_attr( $nav_menu_selected_title ) . '"'; } ?>
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="menu" id="menu" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
					<div id="nav-menu-header">
						<div class="major-publishing-actions wp-clearfix">
							<label class="menu-name-label" for="menu-name"><?php _e( 'Menu Name' ); ?></label>
							<input name="menu-name" id="menu-name" type="text" class="menu-name regular-text menu-item-textbox form-required" required="required" <?php echo $menu_name_val . $menu_name_aria_desc; ?> />
							<div class="publishing-action">
								<?php submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Menu' ) : __( 'Save Menu' ), 'primary large menu-save', 'save_menu', false, array( 'id' => 'save_menu_header' ) ); ?>
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- END .nav-menu-header -->
					<div id="post-body">
						<div id="post-body-content" class="wp-clearfix">
							<?php if ( ! $add_new_screen ) : ?>
								<?php
 $hide_style = ''; if ( isset( $menu_items ) && 0 === count( $menu_items ) ) { $hide_style = 'style="display: none;"'; } if ( $one_theme_location_no_menus ) { $starter_copy = __( 'Edit your default menu by adding or removing items. Drag the items into the order you prefer. Click Create Menu to save your changes.' ); } else { $starter_copy = __( 'Drag the items into the order you prefer. Click the arrow on the right of the item to reveal additional configuration options.' ); } ?>
								<div class="drag-instructions post-body-plain" <?php echo $hide_style; ?>>
									<p><?php echo $starter_copy; ?></p>
								</div>

								<?php if ( ! $add_new_screen ) : ?>
									<div id="nav-menu-bulk-actions-top" class="bulk-actions" <?php echo $hide_style; ?>>
										<label class="bulk-select-button" for="bulk-select-switcher-top">
											<input type="checkbox" id="bulk-select-switcher-top" name="bulk-select-switcher-top" class="bulk-select-switcher">
											<span class="bulk-select-button-label"><?php _e( 'Bulk Select' ); ?></span>
										</label>
									</div>
								<?php endif; ?>

								<?php
 if ( isset( $edit_markup ) && ! is_wp_error( $edit_markup ) ) { echo $edit_markup; } else { ?>
									<ul class="menu" id="menu-to-edit"></ul>
								<?php } ?>

							<?php endif; ?>

							<?php if ( $add_new_screen ) : ?>
								<p class="post-body-plain" id="menu-name-desc"><?php _e( 'Give your menu a name, then click Create Menu.' ); ?></p>
								<?php if ( isset( $_GET['use-location'] ) ) : ?>
									<input type="hidden" name="use-location" value="<?php echo esc_attr( $_GET['use-location'] ); ?>" />
								<?php endif; ?>

								<?php
 endif; $no_menus_style = ''; if ( $one_theme_location_no_menus ) { $no_menus_style = 'style="display: none;"'; } ?>

							<?php if ( ! $add_new_screen ) : ?>
								<div id="nav-menu-bulk-actions-bottom" class="bulk-actions" <?php echo $hide_style; ?>>
									<label class="bulk-select-button" for="bulk-select-switcher-bottom">
										<input type="checkbox" id="bulk-select-switcher-bottom" name="bulk-select-switcher-top" class="bulk-select-switcher">
										<span class="bulk-select-button-label"><?php _e( 'Bulk Select' ); ?></span>
									</label>
									<input type="button" class="deletion menu-items-delete disabled" value="<?php esc_attr_e( 'Remove Selected Items' ); ?>">
									<div id="pending-menu-items-to-delete">
										<p><?php _e( 'List of menu items selected for deletion:' ); ?></p>
										<ul></ul>
									</div>
								</div>
							<?php endif; ?>

							<div class="menu-settings" <?php echo $no_menus_style; ?>>
								<h3><?php _e( 'Menu Settings' ); ?></h3>
								<?php
 if ( ! isset( $auto_add ) ) { $auto_add = get_option( 'nav_menu_options' ); if ( ! isset( $auto_add['auto_add'] ) ) { $auto_add = false; } elseif ( false !== array_search( $nav_menu_selected_id, $auto_add['auto_add'], true ) ) { $auto_add = true; } else { $auto_add = false; } } ?>

								<fieldset class="menu-settings-group auto-add-pages">
									<legend class="menu-settings-group-name howto"><?php _e( 'Auto add pages' ); ?></legend>
									<div class="menu-settings-input checkbox-input">
										<input type="checkbox"<?php checked( $auto_add ); ?> name="auto-add-pages" id="auto-add-pages" value="1" /> <label for="auto-add-pages"><?php printf( __( 'Automatically add new top-level pages to this menu' ), esc_url( admin_url( 'edit.php?post_type=page' ) ) ); ?></label>
									</div>
								</fieldset>

								<?php if ( current_theme_supports( 'menus' ) ) : ?>

									<fieldset class="menu-settings-group menu-theme-locations">
										<legend class="menu-settings-group-name howto"><?php _e( 'Display location' ); ?></legend>
										<?php
 foreach ( $locations as $location => $description ) : $checked = false; if ( isset( $menu_locations[ $location ] ) && 0 !== $nav_menu_selected_id && $menu_locations[ $location ] === $nav_menu_selected_id ) { $checked = true; } ?>
											<div class="menu-settings-input checkbox-input">
												<input type="checkbox"<?php checked( $checked ); ?> name="menu-locations[<?php echo esc_attr( $location ); ?>]" id="locations-<?php echo esc_attr( $location ); ?>" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
												<label for="locations-<?php echo esc_attr( $location ); ?>"><?php echo $description; ?></label>
												<?php if ( ! empty( $menu_locations[ $location ] ) && $menu_locations[ $location ] !== $nav_menu_selected_id ) : ?>
													<span class="theme-location-set">
													<?php
 printf( _x( '(Currently set to: %s)', 'menu location' ), wp_get_nav_menu_object( $menu_locations[ $location ] )->name ); ?>
													</span>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</fieldset>

								<?php endif; ?>

							</div>
						</div><!-- /#post-body-content -->
					</div><!-- /#post-body -->
					<div id="nav-menu-footer">
						<div class="major-publishing-actions">
							<div class="publishing-action">
								<?php submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Menu' ) : __( 'Save Menu' ), 'primary large menu-save', 'save_menu', false, array( 'id' => 'save_menu_footer' ) ); ?>
							</div><!-- END .publishing-action -->
							<?php if ( $menu_count > 0 ) : ?>

								<?php if ( $add_new_screen ) : ?>
								<span class="cancel-action">
									<a class="submitcancel cancellation menu-cancel" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php _e( 'Cancel' ); ?></a>
								</span><!-- END .cancel-action -->
								<?php else : ?>
								<span class="delete-action">
									<a class="submitdelete deletion menu-delete" href="
									<?php
 echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'menu' => $nav_menu_selected_id, ), admin_url( 'nav-menus.php' ) ), 'delete-nav_menu-' . $nav_menu_selected_id ) ); ?>
									"><?php _e( 'Delete Menu' ); ?></a>
								</span><!-- END .delete-action -->
								<?php endif; ?>

							<?php endif; ?>
						</div><!-- END .major-publishing-actions -->
					</div><!-- /#nav-menu-footer -->
				</div><!-- /.menu-edit -->
			</form><!-- /#update-nav-menu -->
		</div><!-- /#menu-management -->
	</div><!-- /#menu-management-liquid -->
	</div><!-- /#nav-menus-frame -->
	<?php endif; ?>
</div><!-- /.wrap-->
<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
