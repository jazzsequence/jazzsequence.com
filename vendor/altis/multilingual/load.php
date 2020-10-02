<?php
/**
 * Altis Multilingual Module.
 *
 * @package altis/multilingual
 */

namespace Altis\Multilingual; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	Altis\register_module( 'multilingual', __DIR__, 'Multilingual', [] );
} );
