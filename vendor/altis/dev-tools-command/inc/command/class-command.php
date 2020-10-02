<?php

namespace Altis\Dev_Tools\Command;

use Composer\Command\BaseCommand;
use DOMDocument;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Chassis command for Composer.
 */
class Command extends BaseCommand {
	/**
	 * Configure the command.
	 */
	protected function configure() {
		$this->setName( 'dev-tools' );
		$this->setDescription( 'Developer tools' );
		$this->setDefinition( [
			new InputArgument( 'subcommand', InputArgument::REQUIRED, 'phpunit' ),
			new InputOption( 'chassis', null, null, 'Run commands in the Local Chassis environment' ),
			new InputArgument( 'options', InputArgument::IS_ARRAY ),
		] );
		$this->setHelp(
			<<<EOT
Run a dev tools feature.

To run PHPUnit integration tests:
    phpunit [--chassis] [--] [options]
                                use `--` to separate arguments you want to
                                pass to phpunit. Use the --chassis option
                                if you are running Local Chassis.
EOT
		);
	}

	/**
	 * Wrapper command to dispatch subcommands
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int Status code to return
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$subcommand = $input->getArgument( 'subcommand' );
		switch ( $subcommand ) {
			case 'phpunit':
				return $this->phpunit( $input, $output );

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $subcommand ) );
		}
	}

	/**
	 * Runs PHPUnit with zero config by default.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function phpunit( InputInterface $input, OutputInterface $output ) {
		$options = [];

		// Get dev-tools config.
		$config = $this->get_config()['phpunit'] ?? [];

		// Set default directories.
		$directories = [ 'tests' ];

		// Get directories from config.
		if ( isset( $config['directories'] ) ) {
			$directories = array_merge( (array) $config['directories'], $directories );
		}

		$directories = array_map( function ( $path ) {
			return trim( $path, DIRECTORY_SEPARATOR );
		}, $directories );
		$directories = array_filter( $directories, function ( $path ) {
			$full_path = $this->get_root_dir() . DIRECTORY_SEPARATOR . $path;
			if ( strpos( $path, '*' ) !== false ) {
				return ! empty( glob( $full_path ) );
			}
			return is_dir( $full_path );
		} );
		$directories = array_unique( $directories );

		// Write XML config.
		$doc = new DOMDocument( '1.0', 'utf-8' );

		// Create PHPUnit Element.
		$phpunit = $doc->createElement( 'phpunit' );
		$phpunit->setAttribute( 'bootstrap', 'altis/dev-tools/inc/phpunit/bootstrap.php' );
		$phpunit->setAttribute( 'backupGlobals', 'false' );
		$phpunit->setAttribute( 'colors', 'true' );
		$phpunit->setAttribute( 'convertErrorsToExceptions', 'true' );
		$phpunit->setAttribute( 'convertNoticesToExceptions', 'true' );
		$phpunit->setAttribute( 'convertWarningsToExceptions', 'true' );

		// Allow overrides and additional attributes.
		if ( isset( $config['attributes'] ) ) {
			foreach ( $config['attributes'] as $name => $value ) {
				$phpunit->setAttribute( $name, $value );
			}
		}

		// Create testsuites.
		$testsuites = $doc->createElement( 'testsuites' );

		// Create testsuite.
		$testsuite = $doc->createElement( 'testsuite' );
		$testsuite->setAttribute( 'name', 'project' );

		foreach ( $directories as $directory ) {
			$tag = $doc->createElement( 'directory', "../{$directory}/" );
			// class-test-*.php
			$variant = $tag->cloneNode( true );
			$variant->setAttribute( 'prefix', 'class-test-' );
			$variant->setAttribute( 'suffix', '.php' );
			$testsuite->appendChild( $variant );
			// test-*.php
			$variant = $tag->cloneNode( true );
			$variant->setAttribute( 'prefix', 'test-' );
			$variant->setAttribute( 'suffix', '.php' );
			$testsuite->appendChild( $variant );
			// *-test.php
			$variant = $tag->cloneNode( true );
			$variant->setAttribute( 'suffix', '-test.php' );
			$testsuite->appendChild( $variant );
		}

		// Build the doc.
		$doc->appendChild( $phpunit );
		$phpunit->appendChild( $testsuites );
		$testsuites->appendChild( $testsuite );

		// Add extensions if set.
		if ( isset( $config['extensions'] ) ) {
			$extensions = $doc->createElement( 'extensions' );
			foreach ( (array) $config['extensions'] as $class ) {
				$extension = $doc->createElement( 'extension' );
				$extension->setAttribute( 'class', $class );
				$extensions->appendChild( $extension );
			}
			$phpunit->appendChild( $extensions );
		}

		// Write the file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents(
			$this->get_root_dir() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'phpunit.xml',
			$doc->saveXML()
		);

		// Check for passed config option.
		$input_options = implode( ' ', $input->getArgument( 'options' ) );
		if ( ! preg_match( '/(-c|--configuration)\s+/', $input_options ) ) {
			$options[] = '-c';
			$options[] = 'vendor/phpunit.xml';
		}

		return $this->run_command( $input, $output, 'vendor/bin/phpunit', $options );
	}

	/**
	 * Run the passed command on either the local-server or local-chassis environment.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $command The command to run.
	 * @param array $options Any required options to pass to the command.
	 * @return void
	 */
	protected function run_command( InputInterface $input, OutputInterface $output, string $command, array $options = [] ) {
		$use_chassis = $input->getOption( 'chassis' );
		$cli = $this->getApplication()->find( $use_chassis ? 'chassis' : 'local-server' );
		$input_options = $input->getArgument( 'options' );

		// Add the command, default options and input options together.
		$options = array_merge(
			[ $command ],
			$options,
			$input_options
		);

		$return_val = $cli->run( new ArrayInput( [
			'subcommand' => 'exec',
			'options' => $options,
		] ), $output );

		return $return_val;
	}

	/**
	 * Get the root directory path for the project.
	 *
	 * @return string
	 */
	protected function get_root_dir() : string {
		return dirname( $this->getComposer()->getConfig()->getConfigSource()->getName() );
	}

	/**
	 * Get a module config from composer.json.
	 *
	 * @param string $module The module to get the config for.
	 * @return array
	 */
	protected function get_config( $module = 'dev-tools' ) : array {
		// @codingStandardsIgnoreLine
		$json = file_get_contents( $this->get_root_dir() . DIRECTORY_SEPARATOR . 'composer.json' );
		$composer_json = json_decode( $json, true );

		return (array) ( $composer_json['extra']['altis']['modules'][ $module ] ?? [] );
	}

}
