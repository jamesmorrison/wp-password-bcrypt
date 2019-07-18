<?php
/**
 * Plugin Name: WP Password bcrypt
 * Plugin URI:  https://github.com/jamesmorrison/wp-password-bcrypt/
 * Description: Replaces wp_hash_password and wp_check_password with PHP 5.5's password_hash and password_verify. Updated to WordPress Extra code standards.
 * Author:      James Morrison, Roots
 * Author URI:  https://www.jamesmorrison.me
 * Version:     1.3.0
 * Licence:     MIT
 */

const WP_OLD_HASH_PREFIX = '$P$';

/**
 * Check if user has entered correct password, supports bcrypt and pHash.
 *
 * @param string $password Plaintext password
 * @param string $hash Hash of password
 * @param int|string $user_id ID of user to whom password belongs
 *
 * @return mixed|void
 *
 */
function wp_check_password( $password, $hash, $user_id = '' ) {

	if ( strpos( $hash, WP_OLD_HASH_PREFIX ) === 0 ) {

		global $wp_hasher;

		if ( empty( $wp_hasher ) ) {

			require_once ABSPATH . WPINC . '/class-phpass.php';

			$wp_hasher = new PasswordHash( 8, true );

		}

		$check = $wp_hasher->CheckPassword( $password, $hash );

		if ( $check && $user_id ) {
			$hash = wp_set_password( $password, $user_id );
		}
	}

	$check = password_verify( $password, $hash );

	return apply_filters( 'check_password', $check, $password, $hash, $user_id );
}

/**
 * Hash password using bcrypt
 *
 * @param string $password Plaintext password
 * @return bool|string
 */
function wp_hash_password( $password ) {

	$options = apply_filters( 'wp_hash_password_options', [] );

	return password_hash( $password, PASSWORD_DEFAULT, $options );

}

/**
 * Set password using bcrypt
 *
 * @param string $password Plaintext password
 * @param int $user_id ID of user to whom password belongs
 * @return bool|string
 */
function wp_set_password( $password, $user_id ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$hash = wp_hash_password( $password );

	$wpdb->update(
		$wpdb->users,
		[
			'user_pass'           => $hash,
			'user_activation_key' => '',
		],
		[ 'ID' => $user_id ]
	);

	wp_cache_delete( $user_id, 'users' );

	return $hash;

}
