<?php

defined( 'ABSPATH' ) or exit;
$date_format = get_option( 'date_format' );
$datetime_format = sprintf('%s %s', $date_format, get_option( 'time_format' ) );

add_action( 'hf_admin_form_submissions_table_output_column_header', function( $field, $column ) {
   echo $column;
}, 10, 2 );

$bulk_actions = apply_filters( 'hf_admin_form_submissions_bulk_actions', array(
  'bulk_delete_submissions' => __( 'Delete Permanently' ),
));

function tablenav_pages( $total_items, $current_page, $total_pages ) {
	?>
	<div class="tablenav-pages">
		<span class="displaying-num"><?php echo sprintf( __( '%d items' ), $total_items ); ?></span>

		<?php if ($total_pages > 1) { ?>
		<span class="pagination-links">
				<a class="first-page button <?php if ($current_page == 1) { echo 'disabled'; } ?>" href="<?php esc_attr_e( add_query_arg( array( 'paged' => 1 ) ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'First page' ); ?></span><span aria-hidden="true">«</span></a>
				<a class="previous-page button <?php if ($current_page == 1) { echo 'disabled'; } ?>" href="<?php esc_attr_e( add_query_arg( array( 'paged' => $current_page - 1 ) ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Previous page' ); ?></span><span aria-hidden="true">‹</span></a>
				<span class="screen-reader-text"><?php esc_html_e( 'Current Page' ); ?></span>
				<span id="table-paging" class="paging-input"><span class="tablenav-paging-text"><?php echo sprintf( esc_html__( '%d of %d' ), $current_page, $total_pages ); ?></span></span>
				<a class="next-page button <?php if ($current_page == $total_pages) { echo 'disabled'; } ?>" href="<?php esc_attr_e( add_query_arg( array( 'paged' => $current_page + 1 ) ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Next page' ); ?></span><span aria-hidden="true">›</span></a>
				<a class="last-page button <?php if ($current_page == $total_pages) { echo 'disabled'; } ?>" href="<?php esc_attr_e( add_query_arg( array( 'paged' => $total_pages ) ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Last page' ); ?></span><span aria-hidden="true">»</span></a>
			</span>
		<?php } ?>
	</div>
	<br class="clear">
	<?php
}

/**
 * @var array $hidden_columns
 * @var array $submissions
 * @var int $total_items
 * @var int $current_page
 * @var int $total_pages
 */
?>

<h2><?php _e( 'Form Submissions', 'html-forms' ); ?></h2>

</form><?php // close main form. This means this always has to be the last tab or it will break stuff. ?>
<form method="post">
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action' ); ?></label>
            <select name="_hf_admin_action" id="bulk-action-selector-top">
                <option value=""><?php _e( 'Bulk Actions' ); ?></option>
                <?php foreach( $bulk_actions as $key => $label ) {
                  echo sprintf( '<option value="%s">%s</option>', esc_attr( $key ), $label );
                } ?>
            </select>
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('_hf_admin_action') ); ?>" />
            <input type="submit" class="button action" value="<?php _e( 'Apply' ); ?>">
        </div>

		<?php tablenav_pages( $total_items, $current_page, $total_pages ); ?>

        <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" /></td>
                <th scope="col" class="hf-column manage-column column-primary" style="width: 160px;">
                  <?php _e( 'Timestamp', 'html-forms' ); ?>
                </th>
                <?php foreach( $columns as $field => $column ) {
                    $hidden_class = in_array( $field, $hidden_columns ) ? 'hidden' : '';
                    echo sprintf( '<th scope="col" class="hf-column hf-column-%s manage-column column-%s %s">', esc_attr( $field ), esc_attr( $field ), $hidden_class );
                    do_action( 'hf_admin_form_submissions_table_output_column_header', $field, $column );
                    echo '</th>';
                } ?>
            </tr>
        </thead>
        <tbody>

        <?php foreach( $submissions as $s ) { ?>
           <tr id="hf-submissions-item-<?php echo $s->id; ?>">
               <th scope="row" class="check-column">
                   <input type="checkbox" name="id[]" value="<?php echo esc_attr( $s->id ); ?>"/>
               </th>
               <td class="has-row-actions column-primary">
                   <strong><abbr title="<?php echo date( $datetime_format, strtotime( $s->submitted_at ) ); ?>">
                       <?php echo sprintf( '<a href="%s">%s</a>', esc_attr( add_query_arg( array( 'tab' => 'submissions', 'submission_id' => $s->id ) ) ), esc_html( $s->submitted_at ) ); ?>
                   </abbr></strong>
                  <div class="row-actions">
                    <?php do_action( 'hf_admin_form_submissions_table_output_row_actions', $s ); ?>
                  </div>
               </td>

               <?php foreach( $columns as $field => $column ) {
                  $hidden_class = in_array( $field, $hidden_columns ) ? 'hidden' : '';
                  echo sprintf( '<td class="column-%s %s">', esc_attr( $field ), $hidden_class );

                  // because some columns don't have a value, check if it's set here
                  if( ! empty( $s->data[$field] ) ) {
                    echo hf_field_value( $s->data[$field], 100 );
                  }

                  echo '</td>';
                } ?>
            </tr>
        <?php } ?>
        <?php if ( empty( $submissions ) ) {
            printf( '<tr><td colspan="2">%s</td></tr>', __( 'Nothing to see here, yet!', 'html-forms' ) );
        } ?>
        </tbody>
    </table>
	<div class="tablenav">
		<?php tablenav_pages( $total_items, $current_page, $total_pages ); ?>
	</div>


</form>


<form><?php // open new main form. This means this always has to be the last tab or it will break stuff. ?>
