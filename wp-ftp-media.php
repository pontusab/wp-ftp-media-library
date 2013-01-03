<?php
/*
Plugin Name: Wp-ftp-media-library
Plugin URI: http://wordpress.stackexchange.com/questions/74180/upload-images-to-remote-server
Description: Let's you upload images to ftp-server and remove the upload on the local machine.
Version: 0.1
Author: Pontus Abrahamsson
Author URI: http://pontusab.se
*/

/**
 * @version 0.1
 */

function wpse_74180_upload_to_ftp( $args ) {

	$upload_dir = wp_upload_dir();
	$upload_url = get_option('upload_url_path');
	$upload_yrm = get_option('uploads_use_yearmonth_folders');


	/**
	 * Change this to match your server
	 * You only need to change the those with (*)
	 * If marked with (-) its optional 
	 */

	$settings = array(
		'host'	  =>	'ip or hostname',  			// * the ftp-server hostname
		'user'	  =>	'username', 				// * ftp-user
		'pass'	  =>	'password',	 				// * ftp-password
		'cdn'     =>    'cdn.example.com',			// * This have to be a pointed domain or subdomain to the root of the uploads
		'path'	  =>	'/',	 					// - ftp-path, default is root (/). Change here and add the dir on the ftp-server,
		'base'	  =>    $upload_dir['basedir']  	// Basedir on local 
	);


	/**
	 * Change the upload url to the ftp-server
	 */

	if( empty( $upload_url ) ) {
		update_option( 'upload_url_path', esc_url( $settings['cdn'] ) );
	}


	/**
	 * If uploads is stored like /uploads/year/month
	 * Remove and use only /uploads/
	 */

	if( $upload_yrm ) {
		update_option( 'uploads_use_yearmonth_folders', '' );
	}


	/**
	 * Host-connection
	 * Read about it here: http://php.net/manual/en/function.ftp-connect.php
	 */
	
	$connection = ftp_connect( $settings['host'] );


	/**
	 * Login to ftp
	 * Read about it here: http://php.net/manual/en/function.ftp-login.php
	 */

	$login = ftp_login( $connection, $settings['user'], $settings['pass'] );

	
	/**
	 * Check ftp-connection
	 */

	if ( !$connection || !$login ) {
	    die('Connection attempt failed, Check your settings');
	}


	/**
	 * Get all files in uploads - local
	 * Remove hidden-files... mabye better solution 
	 * http://php.net/manual/en/function.scandir.php
	 */
	
	$files = preg_grep('/^([^.])/', scandir( $settings['base'] ) );


	// Cycle through all source files
	foreach ( $files as $file ) {

		/**
		 * If we ftp-upload successfully, mark it for deletion
		 * http://php.net/manual/en/function.ftp-put.php
		 */

		if( ftp_put( $connection, $settings['path'] . "/" . $file, $settings['base'] . "/" . $file, FTP_BINARY ) ) {
			$delete[] = $file;
		} 
	}


	// Delete all successfully-copied files
	foreach ( $delete as $file ) {
		unlink( $settings['base'] . '/' . $file );
	}
}
add_filter( 'wp_generate_attachment_metadata', 'wpse_74180_upload_to_ftp' );