<?php
/**
	Plugin Name: Push Notification Exporter
	Plugin URI: http://wordapp.co
	Description: Export specific to save them just in case.
	Author: wordapp
	Version: 0.0.3
	Author URI: http://wordapp.co
	License: Under GPL2
 
	Based on the plugin created by zourbuth.com 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * Plugins filters and actions
 */
add_action( 'plugins_loaded', 'wp_exporter_plugin_loaded' );


/**
 * Plugins loaded function
 * 
 * @since 0.0.1
 */	
function wp_exporter_plugin_loaded() {
	remove_action( 'admin_head', 'export_add_js' ); // not working
    add_action( 'export_filters', 'wordpress_exporter_filters' );	
	add_action( 'admin_head', 'wordpress_exporter_add_js', 99 );
	add_action( 'wp_ajax_exportform', 'wordpress_exporter_form_ajax' );
	add_action( 'wp_exporter_form', 'wordpress_exporter_form' ); // custom action
	add_action( 'export_wp', 'export_wp_action' );
	add_filter( 'export_post_ids', 'wp_exporter_post_ids', 1, 2 ); // custom filter
}


/**
 * Export posts id function
 * @param $post_ids (int) current posts ids
 * @param $args (array) query arguments
 * @since 0.0.1
 */	
function wp_exporter_post_ids( $post_ids, $args ) {
	if ( 'advanced' == $args['content'] && isset( $_GET['query'] ) ) {
		$query = esc_attr( $_GET['query'] );
		switch ( $query ) :
			case 'wa_pns_messages' :
			case 'wa_pns' :
				if( isset( $_GET['post-ids'] ) )
					$post_ids = (array) $_GET['post-ids'];
			break;
		endswitch;
		
		$post_ids = apply_filters( 'wp_exporter_post_ids', $post_ids, $query, $args );
	}
	
	return $post_ids;
}


/**
 * Export action
 * @param $args (array) query arguments
 * @since 0.0.1
 */	
function export_wp_action( $args ) {
	if ( 'advanced' == $args['content'] ) {
		require_once( plugin_dir_path( __FILE__ ) . 'wxr.php' );

		_export_wp( $args );
		die();		
	}
}


/**
 * Export AJAX function
 * @param (none)
 * @since 0.0.1
 */
function wordpress_exporter_form_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['query'] ) || ! wp_verify_nonce( $_POST['nonce'], 'export-queries' ) )
		die();
		
	do_action( 'wp_exporter_form', esc_attr( $_POST['query'] ) );
	exit;
}


/**
 * Lists all posts/pages
 * @param $query (array) query argument
 * @since 0.0.1
 */
function wordpress_exporter_form( $query ) {
	if ( 'wa_pns_messages' == $query || 'wa_pns' == $query ) {
		
		$posts = get_posts( array(
			'posts_per_page' => 9999,
			'post_type'	=> $query
		));
		
		echo "<label>". __( 'Select one or more posts','wp-exporter' ) ."<br />
			  <select name='post-ids[]' size='8' multiple='multiple'>";
			foreach( $posts as $post )
				echo "<option value='{$post->ID}'>{$post->post_title}</option>";
		echo "</select></label>";
	}
}


/**
 * Lists all posts/pages
 * @param $query (array) query argument
 * @since 0.0.1
 */
function wordpress_exporter_filters() {
	$export_queries = apply_filters( 'wp_exporter_queries', array(
		''		=> __( '&mdash; Select Query', 'wp-exporter' ),
		'post'	=> __( 'Select Post(s)', 'wp-exporter' ),
		'page'	=> __( 'Select Page(s)', 'wp-exporter' )
	));
	?>
	<p><label><input type="radio" name="content" value="advanced" /> <?php _e( 'Export Push Notifications', 'wp-exporter' ); ?></label></p>
	<div class="export-filters" id="advanced-filters" style="margin-left: 23px;">
		<p>
			<select class="smallfat" id="query" name="query" data-nonce="<?php echo wp_create_nonce( 'export-queries' ); ?>">
				<option value="wa_pns">--- Select Type ----</option>
				<option value="wa_pns">PN Users</option>
				<option value="wa_pns_messages">PN Messages</option>
				
			</select>
			<span style="display: inline-block; vertical-align: middle;"><span class="spinner"></span></span>
		</p>
		<div><!-- custom form starts here --></div>
	</div>
	<?php
}


/**
 * JavaScript animation function
 * copy export_add_js() wp-admin\export.php lin 24
 * @since 0.0.1
 */
function wordpress_exporter_add_js() {
?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
 		var form = $('#export-filters'),
 			filters = form.find('.export-filters');
 		filters.hide();
 		form.find('input:radio').off('change').change(function() {
			filters.slideUp('fast');
			switch ( $(this).val() ) {
				case 'posts': $('#post-filters').slideDown(); break;
				case 'pages': $('#page-filters').slideDown(); break;			
				case 'advanced': $('#advanced-filters').slideDown(); break;
			}
 		});
		
		$("#query").on("change", function() {
			$('#advanced-filters > div').empty();
			$('#advanced-filters .spinner').css("visibility","visible");
			$.post( ajaxurl, { action: 'exportform', nonce: $(this).attr("data-nonce"), query : $(this).val() }, function(data){
				$('#advanced-filters .spinner').css("visibility","hidden");
				$('#advanced-filters > div').append( data );
			});
		});
	});
//]]>
</script>
<?php
}
?>