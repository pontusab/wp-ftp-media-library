<?php
/*
Plugin Name: Wp-ftp-media-library
Plugin URI: http://wordpress.stackexchange.com/questions/74180/upload-images-to-remote-server
Description: Let's you upload images to ftp-server and remove the upload on the local machine.
Version: 1.0
Author: Pontus Abrahamsson
Author URI: http://pontusab.se
Text Domain: wp-ftp-media-library
Domain Path: /languages
*/

/**
 * @version 1.0
 */

function wfm_load_text_domain() {
  load_plugin_textdomain( 'wp-ftp-media-library', false , basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action('plugins_loaded', 'wfm_load_text_domain');


include('fields.php');

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
		'host'	  =>	cmb2_get_option('wfm_options','wfm_hostname'),  			// * the ftp-server hostname
		'port'    =>  cmb2_get_option('wfm_options','wfm_port'),         // * the ftp-server port (of type int)
		'user'	  =>	cmb2_get_option('wfm_options','wfm_username'), 				// * ftp-user
		'pass'	  =>	cmb2_get_option('wfm_options','wfm_password'),	 				// * ftp-password
		'cdn'     =>  cmb2_get_option('wfm_options','wfm_cdn'),			// * This have to be a pointed domain or subdomain to the root of the uploads
		'path'	  =>	cmb2_get_option('wfm_options','wfm_path'),	 					// - ftp-path, default is root (/). Change here and add the dir on the ftp-server,
		'base'	  =>    $upload_dir['basedir']  	// Basedir on local 
	);


	/**
	 * Change the upload url to the ftp-server
	 */

	if( empty( $upload_url ) ) {
		update_option( 'upload_url_path', esc_url( $settings['cdn'] ) );
	}


	/**
	 * Host-connection
	 * Read about it here: http://php.net/manual/en/function.ftp-connect.php
	 */
	
	$connection = ftp_connect( $settings['host'], $settings['port'] );


	/**
	 * Login to ftp
	 * Read about it here: http://php.net/manual/en/function.ftp-login.php
	 */

	$login = ftp_login( $connection, $settings['user'], $settings['pass'] );

	// turn passive mode on
	ftp_pasv($connection, true);

	
	/**
	 * Check ftp-connection
	 */

	if ( !$connection || !$login ) {
	    die('Connection attempt failed, Check your settings');
	}


	function ftp_putAll($conn_id, $src_dir, $dst_dir, $created) {
            $d = dir($src_dir);
	    while($file = $d->read()) { // do this for each file in the directory
	        if ($file != "." && $file != "..") { // to prevent an infinite loop
	            if (is_dir($src_dir."/".$file)) { // do the following if it is a directory
	                if (!@ftp_chdir($conn_id, $dst_dir."/".$file)) {
	                    ftp_mkdir($conn_id, $dst_dir."/".$file); // create directories that do not yet exist
	                }
	                $created  = ftp_putAll($conn_id, $src_dir."/".$file, $dst_dir."/".$file, $created); // recursive part
	            } else {
	                $upload = ftp_put($conn_id, $dst_dir."/".$file, $src_dir."/".$file, FTP_BINARY); // put the files
	                if($upload)
	                	$created[] = $src_dir."/".$file;
	            }
	        }
	    }
	    $d->close();
	    return $created;
	}

	/**
	 * If we ftp-upload successfully, mark it for deletion
	 * http://php.net/manual/en/function.ftp-put.php
	 */
	$delete = ftp_putAll($connection, $settings['base'], $settings['path'], array());
	


	// Delete all successfully-copied files
	foreach ( $delete as $file ) {
		unlink( $file );
	}
	
	return $args;
}
add_filter( 'wp_generate_attachment_metadata', 'wpse_74180_upload_to_ftp' );


function wfm_delete_ftp_file( $args ) {

	$upload_dir = wp_upload_dir();
	$upload_url = get_option('upload_url_path');
	$upload_yrm = get_option('uploads_use_yearmonth_folders');

	$settings = array(
		'host'	  =>	cmb2_get_option('wfm_options','wfm_hostname'),  			// * the ftp-server hostname
		'port'    =>  cmb2_get_option('wfm_options','wfm_port'),         // * the ftp-server port (of type int)
		'user'	  =>	cmb2_get_option('wfm_options','wfm_username'), 				// * ftp-user
		'pass'	  =>	cmb2_get_option('wfm_options','wfm_password'),	 				// * ftp-password
		'cdn'     =>  cmb2_get_option('wfm_options','wfm_cdn'),			// * This have to be a pointed domain or subdomain to the root of the uploads
		'path'	  =>	cmb2_get_option('wfm_options','wfm_path'),	 					// - ftp-path, default is root (/). Change here and add the dir on the ftp-server,
		'base'	  =>    $upload_dir['basedir']  	// Basedir on local 
	);

	if( empty( $upload_url ) ) {
		update_option( 'upload_url_path', esc_url( $settings['cdn'] ) );
	}
	$connection = ftp_connect( $settings['host'], $settings['port'] );
	$login = ftp_login( $connection, $settings['user'], $settings['pass'] );
	ftp_pasv($connection, true);
	if ( !$connection || !$login ) {
	  die('Connection attempt failed, Check your settings');
	}
	$file_year = substr(wp_get_attachment_metadata($args)['file'],0,8);
	$file_original = str_replace($settings['cdn'].'/',"",wp_get_attachment_url($args));
	$file_thumb = $file_year.wp_get_attachment_metadata($args)['sizes']['thumbnail']['file'];
	$file_medium = $file_year.wp_get_attachment_metadata($args)['sizes']['medium']['file'];
	$file_medium_large = $file_year.wp_get_attachment_metadata($args)['sizes']['medium_large']['file'];
	$file_large = $file_year.wp_get_attachment_metadata($args)['sizes']['large']['file'];
	$file_post = $file_year.wp_get_attachment_metadata($args)['sizes']['post-thumbnail']['file'];
	error_log($file_year, 0);
	ftp_delete($connection,$file_original);
	ftp_delete($connection,$file_thumb);
	ftp_delete($connection,$file_medium);
	ftp_delete($connection,$file_medium_large);
	ftp_delete($connection,$file_large);
	ftp_delete($connection,$file_post);
	ftp_close($connection);
	
}
add_action( 'delete_attachment', 'wfm_delete_ftp_file', 10, 1 );
