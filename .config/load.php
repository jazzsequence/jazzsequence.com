<?php
if ( ! Altis\get_environment_type() === 'local' ) {
	define( 'DB_NAME', 'wordpress' );
	define( 'DB_USER', 'root' );
	define( 'DB_PASSWORD', 'd0d469444e274531cba5a67ffb8d70df432cad20b26f52ec' );
	define( 'DB_HOST', 'localhost' );
	define( 'DB_CHARSET', 'utf8' );
	define( 'DB_COLLATE', '' );
}