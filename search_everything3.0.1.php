<?php
/*
Plugin Name: Search Everything
Plugin URI: http://dancameron.org/wordpress/
Description: Adds search functionality with little setup. Including options to search pages, attachments, drafts, comments and custom fields (metadata).
Heavy props to <a href="http://kinrowan.net">Cori Schlegel</a> for making the admin options panel and the additional search capabilities. Thanks to <a href="http://alexking.org">Alex King</a> amongst <a href="http://blog.saddey.net">ot</a>h<a href="http://www.reaper-x.com">ers</a> for the WP 2.1 compatibility
Version: 3.02
Author: Dan Cameron
Author URI: http://dancameron.org
*/

/*
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, version 2.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
*/




//add filters based upon option settings

//logging
$logging = 0;

function SE3_log($msg) {
	global $logging;
	if ($logging) {
		$fp = fopen("logfile.log","a+");
		$date = date("Y-m-d H:i:s ");
		$source = "search_everything_2 plugin: ";
		fwrite($fp, "\n\n".$date."\n".$source."\n".$msg);
		fclose($fp);
	}
	return true;
	}



//add filters based upon option settings
if ("true" == get_option('SE3_use_page_search')) {
	add_filter('posts_where', 'SE3_search_pages');
	SE3_log("searching pages");
	}

if ("true" == get_option('SE3_use_comment_search')) {
	add_filter('posts_where', 'SE3_search_comments');
	add_filter('posts_join', 'SE3_comments_join');
	SE3_log("searching comments");
	}

if ("true" == get_option('SE3_use_draft_search')) {
	add_filter('posts_where', 'SE3_search_draft_posts');
	SE3_log("searching drafts");
	}

if ("true" == get_option('SE3_use_attachment_search')) {
	add_filter('posts_where', 'SE3_search_attachments');
	SE3_log("searching attachments");
	}

if ("true" == get_option('SE3_use_metadata_search')) {
	add_filter('posts_where', 'SE3_search_metadata');
	add_filter('posts_join', 'SE3_search_metadata_join');
	SE3_log("searching metadata");
	}

//Duplicate fix provided by Tiago.Pocinho
	add_filter('posts_request', 'SE3_distinct');
function SE3_distinct($query){
	  global $wp_query;
	  if (!empty($wp_query->query_vars['s'])) {
	    if (strstr($where, 'DISTINCT')) {}
	    else {
	      $query = str_replace('SELECT', 'SELECT DISTINCT', $query);
	    }
	  }
	  return $query;
	}
	
	
//search pages
function SE3_search_pages($where) {
	global $wp_query;
	if (!empty($wp_query->query_vars['s'])) {
		$where = str_replace('"', '\'', $where);
		if (strstr($where, 'post_type')) { // >= v 2.1
			$where = str_replace('post_type = \'post\' AND ', '', $where);
		}
		else { // < v 2.1
			$where = str_replace(' AND (post_status = \'publish\'', ' AND (post_status = \'publish\' or post_status = \'static\'', $where);
		}
	}

	SE3_log("pages where: ".$where);
	return $where;
}

//search drafts
function SE3_search_draft_posts($where) {
	global $wp_query;
	if (!empty($wp_query->query_vars['s'])) {
		$where = str_replace('"', '\'', $where);
		$where = str_replace(' AND (post_status = \'publish\'', ' AND (post_status = \'publish\' or post_status = \'draft\'', $where);
	}

	SE3_log("drafts where: ".$where);
	return $where;
}

//search attachments
function SE3_search_attachments($where) {
	global $wp_query;
	if (!empty($wp_query->query_vars['s'])) {
		$where = str_replace('"', '\'', $where);
		$where = str_replace(' AND (post_status = \'publish\'', ' AND (post_status = \'publish\' or post_status = \'attachment\'', $where);
		$where = str_replace('AND post_status != \'attachment\'','',$where);
	}

	SE3_log("attachments where: ".$where);
	return $where;
}

//search comments
function SE3_search_comments($where) {
global $wp_query;
	if (!empty($wp_query->query_vars['s'])) {
		$where .= " OR (comment_content LIKE '%" . $wp_query->query_vars['s'] . "%') ";
	}

	SE3_log("comments where: ".$where);

	return $where;
}

//join for searching comments
function SE3_comments_join($join) {
	global $wp_query, $wpdb;

	if (!empty($wp_query->query_vars['s'])) {

		if ('true' == get_option('SE3_approved_comments_only')) {
			$comment_approved = " AND comment_approved =  '1'";
  		} else {
			$comment_approved = '';
    	}

		$join .= "LEFT JOIN $wpdb->comments ON ( comment_post_ID = ID " . $comment_approved . ") ";
	}
	SE3_log("comments join: ".$join);
	return $join;
}

//search metadata
function SE3_search_metadata($where) {
	global $wp_query;
	if (!empty($wp_query->query_vars['s'])) {
		$where .= " OR meta_value LIKE '%" . $wp_query->query_vars['s'] . "%' ";
	}

	SE3_log("metadata where: ".$where);

	return $where;
}

//join for searching metadata
function SE3_search_metadata_join($join) {
	global $wp_query, $wpdb;

	if (!empty($wp_query->query_vars['s'])) {

		$join .= "LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";
	}
	SE3_log("metadata join: ".$join);
	return $join;
}


//build admin interface
function SE3_option_page() {

global $wpdb, $table_prefix;

	if ( isset($_POST['SE3_update_options']) ) {

		$errs = array();

		if ( !empty($_POST['search_pages']) ) {
			update_option('SE3_use_page_search', "true");
		} else {
			update_option('SE3_use_page_search', "false");
		}

		if ( !empty($_POST['search_comments']) ) {
			update_option('SE3_use_comment_search', "true");
		} else {
			update_option('SE3_use_comment_search', "false");
		}

		if ( !empty($_POST['appvd_comments']) ) {
			update_option('SE3_approved_comments_only', "true");
		} else {
			update_option('SE3_approved_comments_only', "false");
		}

		if ( !empty($_POST['search_drafts']) ) {
			update_option('SE3_use_draft_search', "true");
		} else {
			update_option('SE3_use_draft_search', "false");
		}

		if ( !empty($_POST['search_attachments']) ) {
			update_option('SE3_use_attachment_search', "true");
		} else {
			update_option('SE3_use_attachment_search', "false");
		}

		if ( !empty($_POST['search_metadata']) ) {
			update_option('SE3_use_metadata_search', "true");
		} else {
			update_option('SE3_use_metadata_search', "false");
		}

		if ( empty($errs) ) {
			echo '<div id="message" class="updated fade"><p>Options updated!</p></div>';
		} else {
			echo '<div id="message" class="error fade"><ul>';
			foreach ( $errs as $name => $msg ) {
				echo '<li>'.wptexturize($msg).'</li>';
			}
			echo '</ul></div>';
	 }
	} // End if update

	//set up option checkbox values
	if ('true' == get_option('SE3_use_page_search')) {
		$page_search = 'checked="true"';
	} else {
		$page_search = '';
	}

	if ('true' == get_option('SE3_use_comment_search')) {
		$comment_search = 'checked="true"';
	} else {
		$comment_search = '';
	}

	if ('true' == get_option('SE3_approved_comments_only')) {
		$appvd_comment = 'checked="true"';
	} else {
		$appvd_comment = '';
	}

	if ('true' == get_option('SE3_use_draft_search')) {
		$draft_search = 'checked="true"';
	} else {
		$draft_search = '';
	}

	if ('true' == get_option('SE3_use_attachment_search')) {
		$attachment_search = 'checked="true"';
	} else {
		$attachment_search = '';
	}

	if ('true' == get_option('SE3_use_metadata_search')) {
		$metadata_search = 'checked="true"';
	} else {
		$metadata_search = '';
	}

	?>

	<div style="width:75%;" class="wrap" id="SE3_options_panel">
	<h2>Search Everything</h2>
	<p>The options selected below will be used in every search query on this site; in addition to the built-in post search.</p>
	<div id="searchform">
		<form method="get" id="searchform" action="<?php bloginfo('home'); ?>">
			<div><input type="text" value="<?php echo wp_specialchars($s, 1); ?>" name="s" id="s" />
				<input type="submit" id="searchsubmit" value="Test Search" />
			</div>
		</form>
	</div>



	<form method="post">

	<table id="search_options" cell-spacing="2" cell-padding="2">
		<tr>
			<td class="col1"><input type="checkbox" name="search_pages" value="<?php echo get_option('SE3_use_page_search'); ?>" <?php echo $page_search; ?> /></td>
			<td class="col2">Search Every Page</td>
		</tr>
		<tr>
			<td class="col1"><input type="checkbox" name="search_comments" value="<?php echo get_option('SE3_use_comment_search'); ?>" <?php echo $comment_search; ?> /></td>
			<td class="col2">Search Every Comment</td>
		</tr>
		<tr class="child_option">
			<td>&nbsp;</td>
			<td>
				<table>
					<tr>
						<td class="col1"><input type="checkbox" name="appvd_comments" value="<?php echo get_option('SE3_approved_comments_only'); ?>" <?php echo $appvd_comment; ?> /></td>
						<td class="col2">Search only Approved comments only?</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="col1"><input type="checkbox" name="search_drafts" value="<?php echo get_option('SE3_use_draft_search'); ?>" <?php echo $draft_search; ?> /></td>
			<td class="col2">Search Every Draft</td>
		</tr>
		<tr>
			<td class="col1"><input type="checkbox" name="search_attachments" value="<?php echo get_option('SE3_use_attachment_search'); ?>" <?php echo $attachment_search; ?> /></td>
			<td class="col2">Search Every Attachment Title and Description</td>
		</tr>
		<tr>
			<td class="col1"><input type="checkbox" name="search_metadata" value="<?php echo get_option('SE3_use_metadata_search'); ?>" <?php echo $metadata_search; ?> /></td>
			<td class="col2">Search Custom Fields (Metadata)</td>
		</tr>
	</table>

	<p class="submit">
	<input type="submit" name="SE3_update_options" value="Save"/>
	</p>You may have to update your options twice before it sticks.
	</form>

	</div>

	<?php
}	//end SE3_option_page

function SE3_add_options_panel() {
	add_options_page('Search Everything', 'Search Everything', 'edit_plugins', 'SE3_options_page', 'SE3_option_page');
}
add_action('admin_menu', 'SE3_add_options_panel');

//styling options page
function SE3_options_style() {
	?>
	<style type="text/css">

	table#search_options {
		table-layout: auto;
 	}


 	#search_options td.col1, #search_options th.col1 {
		width: 30px;
		text-align: left;
  	}

 	#search_options td.col2, #search_options th.col2 {
		width: 220px;
		margin-left: -15px;
		text-align: left;
  	}

  	#search_options tr.child_option {
		margin-left: 15px;
		margin-top: -3px;
   }

   #SE3_options_panel p.submit {
		text-align: left;
   }

	div#searchform div {
		margin-left: auto;
		margin-right: auto;
		margin-top: 5px;
		margin-bottom: 5px;
 	}

 	</style>

<?php
}


add_action('admin_head', 'SE3_options_style');

?>
