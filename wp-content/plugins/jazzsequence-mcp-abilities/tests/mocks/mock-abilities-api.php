<?php
/**
 * Mock WordPress Abilities API for testing.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

// Global registry for testing.
global $_wp_abilities_registry;
$_wp_abilities_registry = [];

global $_wp_ability_categories_registry;
$_wp_ability_categories_registry = [];

/**
 * Mock wp_register_ability_category function.
 *
 * @param string $slug Category slug.
 * @param array  $args Category arguments.
 * @return bool
 */
function wp_register_ability_category( string $slug, array $args ): bool {
	global $_wp_ability_categories_registry;
	$_wp_ability_categories_registry[ $slug ] = $args;
	return true;
}

/**
 * Mock wp_register_ability function.
 *
 * @param string $name Ability name.
 * @param array  $args Ability arguments.
 * @return \WP_Ability|false
 */
function wp_register_ability( string $name, array $args ) {
	global $_wp_abilities_registry;

	$ability = new stdClass();
	$ability->name = $name;
	$ability->args = $args;

	$_wp_abilities_registry[ $name ] = $ability;

	return (object) [
		'get_name' => function () use ( $name ) {
			return $name; },
		'get_meta' => function () use ( $args ) {
			return $args['meta'] ?? []; },
	];
}

/**
 * Mock wp_get_abilities function.
 *
 * @return array
 */
function wp_get_abilities(): array {
	global $_wp_abilities_registry;

	$abilities = [];
	foreach ( $_wp_abilities_registry as $name => $data ) {
		$abilities[ $name ] = (object) [
			'name' => $name,
			'get_name' => function () use ( $name ) {
				return $name; },
			'get_meta' => function () use ( $data ) {
				return $data->args['meta'] ?? []; },
		];
	}

	return $abilities;
}

/**
 * Mock wp_get_ability function.
 *
 * @param string $name Ability name.
 * @return object|null
 */
function wp_get_ability( string $name ) {
	global $_wp_abilities_registry;

	if ( ! isset( $_wp_abilities_registry[ $name ] ) ) {
		return null;
	}

	$data = $_wp_abilities_registry[ $name ];

	return (object) [
		'name' => $name,
		'get_name' => function () use ( $name ) {
			return $name; },
		'get_meta' => function () use ( $data ) {
			return $data->args['meta'] ?? []; },
	];
}

/**
 * Reset the mock registry for testing.
 */
function _reset_abilities_registry() {
	global $_wp_abilities_registry, $_wp_ability_categories_registry;
	$_wp_abilities_registry = [];
	$_wp_ability_categories_registry = [];
}
