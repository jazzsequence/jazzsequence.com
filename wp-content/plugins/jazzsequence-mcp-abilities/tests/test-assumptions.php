<?php
/**
 * Direct assumption tests that can run against production WordPress.
 *
 * Usage: wp eval-file tests/test-assumptions.php
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

echo "=== JAZZSEQUENCE MCP ABILITIES ASSUMPTION TESTS ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

function test_assert( $condition, $message, &$passed, &$failed ) {
	if ( $condition ) {
		echo "✓ PASS: {$message}\n";
		$passed++;
	} else {
		echo "✗ FAIL: {$message}\n";
		$failed++;
	}
}

// Test 1: wp_register_ability exists
test_assert(
	function_exists( 'wp_register_ability' ),
	'wp_register_ability() function exists',
	$tests_passed,
	$tests_failed
);

// Test 2: wp_get_abilities exists
test_assert(
	function_exists( 'wp_get_abilities' ),
	'wp_get_abilities() function exists',
	$tests_passed,
	$tests_failed
);

// Test 3: Plugin constant defined
test_assert(
	defined( 'JSMCP_ABILITY_CATEGORY' ),
	'JSMCP_ABILITY_CATEGORY constant defined',
	$tests_passed,
	$tests_failed
);

// Test 4: Bootstrap function exists
test_assert(
	function_exists( 'JazzSequence\MCP_Abilities\bootstrap' ),
	'JazzSequence\MCP_Abilities\bootstrap() function exists',
	$tests_passed,
	$tests_failed
);

// Test 5: MCP integration function exists
test_assert(
	function_exists( 'JazzSequence\MCP_Abilities\register_mcp_integration' ),
	'JazzSequence\MCP_Abilities\register_mcp_integration() function exists',
	$tests_passed,
	$tests_failed
);

// Test 6: Get all abilities
if ( function_exists( 'wp_get_abilities' ) ) {
	$all_abilities = wp_get_abilities();
	$total_count = count( $all_abilities );

	echo "\nTotal abilities registered: {$total_count}\n";

	// Test 7: Count jazzsequence-mcp abilities
	$jazzsequence_abilities = array_filter(
		array_keys( $all_abilities ),
		function( $name ) {
			return strpos( $name, 'jazzsequence-mcp/' ) === 0;
		}
	);

	$jazzsequence_count = count( $jazzsequence_abilities );
	echo "JazzSequence MCP abilities: {$jazzsequence_count}\n";

	test_assert(
		$jazzsequence_count === 34,
		'Exactly 34 jazzsequence-mcp abilities registered',
		$tests_passed,
		$tests_failed
	);

	// Test 8: Check MCP metadata on jazzsequence abilities
	if ( $jazzsequence_count > 0 ) {
		echo "\nChecking MCP metadata on abilities:\n";
		$abilities_with_mcp = 0;
		$abilities_without_mcp = 0;

		foreach ( $jazzsequence_abilities as $ability_name ) {
			$ability = wp_get_ability( $ability_name );
			if ( $ability ) {
				$meta = $ability->get_meta();
				$has_mcp_public = isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true;

				if ( $has_mcp_public ) {
					$abilities_with_mcp++;
				} else {
					$abilities_without_mcp++;
					echo "  - {$ability_name}: MISSING mcp.public metadata\n";
				}
			}
		}

		echo "  Abilities with mcp.public=true: {$abilities_with_mcp}\n";
		echo "  Abilities WITHOUT mcp.public=true: {$abilities_without_mcp}\n";

		test_assert(
			$abilities_without_mcp === 0,
			'All jazzsequence-mcp abilities have mcp.public=true',
			$tests_passed,
			$tests_failed
		);
	}

	// Test 9: Count core abilities
	$core_abilities = array_filter(
		array_keys( $all_abilities ),
		function( $name ) {
			return strpos( $name, 'core/' ) === 0;
		}
	);
	echo "\nCore abilities: " . count( $core_abilities ) . "\n";

	// Test 10: Count ninja forms abilities
	$ninja_abilities = array_filter(
		array_keys( $all_abilities ),
		function( $name ) {
			return strpos( $name, 'ninjaforms/' ) === 0;
		}
	);
	echo "Ninja Forms abilities: " . count( $ninja_abilities ) . "\n";
}

// Test 11: Check MCP integration filter
test_assert(
	has_filter( 'mcp_adapter_default_server_config' ) > 0,
	'mcp_adapter_default_server_config filter is registered',
	$tests_passed,
	$tests_failed
);

// Test 12: Simulate filter application
if ( function_exists( 'wp_get_abilities' ) ) {
	echo "\nTesting filter behavior:\n";

	$config = [ 'tools' => [ 'mcp-adapter/discover-abilities' ] ];
	$original_count = count( $config['tools'] );

	$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );
	$filtered_count = count( $filtered_config['tools'] );

	echo "  Original tools count: {$original_count}\n";
	echo "  Filtered tools count: {$filtered_count}\n";
	echo "  Tools added by filter: " . ($filtered_count - $original_count) . "\n";

	test_assert(
		$filtered_count > $original_count,
		'Filter adds abilities to tools array',
		$tests_passed,
		$tests_failed
	);

	// Count how many jazzsequence tools were added
	$jazzsequence_tools = array_filter(
		$filtered_config['tools'],
		function( $tool ) {
			return strpos( $tool, 'jazzsequence-mcp/' ) === 0;
		}
	);

	echo "  JazzSequence tools in filtered config: " . count( $jazzsequence_tools ) . "\n";

	test_assert(
		count( $jazzsequence_tools ) === 34,
		'Filter adds all 34 jazzsequence-mcp abilities to tools',
		$tests_passed,
		$tests_failed
	);
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ( $tests_failed > 0 ) {
	echo "\n✗ TESTS FAILED\n";
	exit( 1 );
} else {
	echo "\n✓ ALL TESTS PASSED\n";
	exit( 0 );
}
