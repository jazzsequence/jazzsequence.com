<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb6c6bcb382d89ca5dfbeda383e325ee5
{
    public static $classMap = array (
        'Autoblue\\Admin' => __DIR__ . '/../..' . '/includes/Admin.php',
        'Autoblue\\Assets' => __DIR__ . '/../..' . '/includes/Assets.php',
        'Autoblue\\Blocks' => __DIR__ . '/../..' . '/includes/Blocks.php',
        'Autoblue\\Bluesky' => __DIR__ . '/../..' . '/includes/Bluesky.php',
        'Autoblue\\Bluesky\\API' => __DIR__ . '/../..' . '/includes/Bluesky/API.php',
        'Autoblue\\Bluesky\\TextParser' => __DIR__ . '/../..' . '/includes/Bluesky/TextParser.php',
        'Autoblue\\Comments' => __DIR__ . '/../..' . '/includes/Comments.php',
        'Autoblue\\ConnectionsManager' => __DIR__ . '/../..' . '/includes/ConnectionsManager.php',
        'Autoblue\\Endpoints\\AccountController' => __DIR__ . '/../..' . '/includes/Endpoints/AccountController.php',
        'Autoblue\\Endpoints\\ConnectionsController' => __DIR__ . '/../..' . '/includes/Endpoints/ConnectionsController.php',
        'Autoblue\\Endpoints\\SearchController' => __DIR__ . '/../..' . '/includes/Endpoints/SearchController.php',
        'Autoblue\\ImageCompressor' => __DIR__ . '/../..' . '/includes/ImageCompressor.php',
        'Autoblue\\Meta' => __DIR__ . '/../..' . '/includes/Meta.php',
        'Autoblue\\PostHandler' => __DIR__ . '/../..' . '/includes/PostHandler.php',
        'Autoblue\\Setup' => __DIR__ . '/../..' . '/includes/Setup.php',
        'Autoblue\\Utils' => __DIR__ . '/../..' . '/includes/Utils.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitb6c6bcb382d89ca5dfbeda383e325ee5::$classMap;

        }, null, ClassLoader::class);
    }
}
