<?php
/*
Plugin Name: unFocus Prune Users
Plugin URI: http://www.unfocus.com/projects/wp-prune-users/
Description: Adds functionality to prune WordPress users by date and inactivity.
Author: unFocus Projects
Author URI: http://www.unfocus.com/
Version: 0.1
License: GPL2
Network: true
*/
/* Copyright 2011 Kevin Newman www.unfocus.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

class unFocus_PruneUsers
{
	private static $instance;
	
	public static function admin_menu()
	{
		add_submenu_page(
			'users.php',
			'Prune Users (unFocus)',
			'Prune (unFocus)',
			'manage_options',
			'unfocus-prune-users',
			'unFocus_PruneUsers::page_prune'
		);	
	}
	
	public static function page_prune()
	{
		global $wpdb;
		
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$nonce_action = 'unfocus-prune';
		
		$sql = "SELECT ID FROM $wpdb->users";
		
		if ( isset($_GET['doit'] ) && $_GET['doit'] == 1 &&
				wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) )
		{
			foreach ($wpdb->get_results($sql) as $user)
			{
				if (
					self::get_user_comments_approved( $user->ID ) > 0 ||
					count_user_posts( $user->ID ) > 0
				) {
					continue;
				}
				else if ( user_can( $user->ID, 'subscriber') )
				{
					wp_delete_user( $user->ID );
				}
			}
		}
		
		$count = 0;
		foreach ($wpdb->get_results($sql) as $user)
		{
			if (
				self::get_user_comments_approved( $user->ID ) > 0 ||
				count_user_posts( $user->ID ) > 0
			) {
				continue;
			}
			else if ( user_can( $user->ID, 'subscriber') )
				++$count;
		}
		
		$url = wp_nonce_url( './users.php?page=unfocus-prune-users&doit=1', $nonce_action );
		
		echo "<p>There are $count users who have never posted, or commented. Delete them?</p>
		<p><a href='$url' onclick=\"return confirm('This can NOT be undone. Are you sure?')\">
		Do it!</a></p>";
		
	}
	
	/**
	 * Borrowed from akimset_get_user_comments_approved
	 */
	private static function get_user_comments_approved( $user_id )
	{
		global $wpdb;
		
		if ( !empty($user_id) ) {
			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $wpdb->comments
					 WHERE user_id = %d AND comment_approved = 1",
					$user_id
				)
			);
		}
	}
}

if ( is_admin() ) {
	add_action('admin_menu', array( 'unFocus_PruneUsers', 'admin_menu' ) );
}
?>