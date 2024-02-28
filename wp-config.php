<?php
define( 'WP_CACHE', false ); // Added by WP Rocket

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'techvertu' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '80)[#40FK!JXl-YN#:yUxf:~4;m4A34|g9B/z2%fjIMw4#6Hlqr[o8*0rO8!8I&O');
define('SECURE_AUTH_KEY', 'k(&1JnrQKIT73Bk3%X0/T4%]JO+91+Vvk01E1I0aT8Uz(ba7~7::kms:0VVb#+i-');
define('LOGGED_IN_KEY', 'DMX5pS6/AI%%t8-aFNZSE!0lD~q*!2DYDdGwv57U(Am8u3;mA7)Etwg3*2flM-U5');
define('NONCE_KEY', 'ePjB@+c&K0el]H~/M5jQS-4)_CFA]v21TsU*Ez9l5oo!3/iI6r;r2dav#y&SUu68');
define('AUTH_SALT', 'I|91w1497a6oj5r*We)8SV#0H06+E1ekQQET8ost:x4O|st1JQ4d*NaaK#62lcJO');
define('SECURE_AUTH_SALT', 'eh2c9_-r7q;%23I5-3Eqpe9X9R3xV*+%F1D%H:|;y@4et51y407V4R(9h74sI~d0');
define('LOGGED_IN_SALT', 'V4@RL~j[I(p20@K1C8u~[H!tu%81|Phm|V]&FWc@9%05%#@Dy]@m0Bmxc!MDPv9q');
define('NONCE_SALT', 'd-P/_%qRg]KR]7v;[6ssx8vn-d-B4@QB_]X6hM~lswo6Q27345_Lr86ECpQ3-+75');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '2XqVhkmN_';


/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
define('WP_DEBUG_DISPLAY', false);
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define( 'WP_MEMORY_LIMIT', '512M' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
