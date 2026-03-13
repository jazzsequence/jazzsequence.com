<?php
/**
 * MCP Adapter MU Plugin Loader
 *
 * Loads the WordPress MCP Adapter from the Composer vendor directory.
 * This allows the MCP Adapter to function as a must-use plugin.
 *
 * @package jazzsequence-mcp
 */

// Load MCP Adapter from vendor.
$mcp_adapter_path = dirname( __DIR__, 2 ) . '/vendor/wordpress/mcp-adapter/mcp-adapter.php';

if ( file_exists( $mcp_adapter_path ) ) {
	require_once $mcp_adapter_path;
}
