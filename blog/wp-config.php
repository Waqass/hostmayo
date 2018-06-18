<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '1O-7i%3wx[N5rinXIf(y4A_R=^bf{f3&31H= YSP?dG0wO%7j+8q/pS!b E!fwF_');
define('SECURE_AUTH_KEY',  'U*Ngi$7,Bp$U>Zj(3WswrC:HpmUc-:u bc=$zpf;5{:q86~kmz<;0vDFb=v0n3iY');
define('LOGGED_IN_KEY',    '@|tCt9+:OhaD=M>5Q^e^3s8[.4$BO:X}4P&/WyV2#G1HWd!.Yc!)w;~Crgf(@]0i');
define('NONCE_KEY',        'K!,y]ZH~3b$[$H4k+h/?M;foxG??@fNA(O G}e[[WYy4w~`A 6hXUc2=fDf,Olk^');
define('AUTH_SALT',        'pyFq;6wdt#!s8+My!s28w<I5CHIW({F# Q140Kam<?@Q]`M<FiR:r!{I!y02~Xv`');
define('SECURE_AUTH_SALT', ')lvYQTz-i|Oz+QIMgH64%mJrDK&yqgAH`SAU[2jwq;LEiNcV]v8l+S-hUA{c-SNf');
define('LOGGED_IN_SALT',   ':azTpxn&Y::  #OEG96~=M1GHM}|ilMnbP*X T8kWQ-aa?|3DPQ$myGxj3RlcT0T');
define('NONCE_SALT',       'mZ`)0vaNo]qUm>(,3~_3xT9<?])Qj0TYS%Yy`IT.D@M4~`ELfBj8kf]vfV@^e/f$');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
