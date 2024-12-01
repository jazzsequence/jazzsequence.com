<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitb6c6bcb382d89ca5dfbeda383e325ee5
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitb6c6bcb382d89ca5dfbeda383e325ee5', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitb6c6bcb382d89ca5dfbeda383e325ee5', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitb6c6bcb382d89ca5dfbeda383e325ee5::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
