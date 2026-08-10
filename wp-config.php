<?php

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'andrew_db' );

/** Database username */
define( 'DB_USER', 'andrew' );

/** Database password */
define( 'DB_PASSWORD', '8d1)V&ySeKF9' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         ' |x3QcA]yP7jfoa^Ir5],8dx[*sdA4pwF>9rP57KS-K?gqK;rxJ,HgectjxL>_CA' );
define( 'SECURE_AUTH_KEY',  '{s[~t|>+Mn`t~5.eF.4n0r(Ry8PH*Ie*!R{F=hO@4xJoN}ML/pS@2qa-b6xf=2[p' );
define( 'LOGGED_IN_KEY',    '#yVL~2(Y--4!*#L,+Mf-ij/Q.|X$Yn6:i&7Ck@zF:^wnB^[N xW(cB,ne]~@r$<A' );
define( 'NONCE_KEY',        'xbMhQt:&03nGLw Y8{tMN.}NxI9PY9mwCR&9KT).rt/U]ZgOv4$qk_$*=@:k^$]5' );
define( 'AUTH_SALT',        '>8b,2bD!${YeI<u|0;iA_3sI_7t_1>M#2vDtj-$_)<lqgs#yo<a-,82[:Ap,+&c9' );
define( 'SECURE_AUTH_SALT', '>ifS2[p!9YXMYbwae#33%|Z2GUN(j5z@S7v>-HjxiAgkvBMCiONKsd-T|S^|0pm_' );
define( 'LOGGED_IN_SALT',   'c=S_7,=]E9FF<-ylU0[31=gy2S)6}W&5P= r> }j%YLL>nxs)0^U=XA5Zaom5@OQ' );
define( 'NONCE_SALT',       ',sv)F8+r1D22t}s8V2n/A>y4_Nlk|0oU=PZSmR*=b@PtgJNa2gQF>9[}}^#5xyPY' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define('FS_METHOD', 'direct');
define('WP_HOME','https://demo.kbizsoft.com');
define('WP_SITEURL','https://demo.kbizsoft.com');
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
