<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1fa7c3fe3acbb539df60ed60bc0c9ffd
{
    public static $files = array (
        'e60490e52a06d47f29d323e997c0a140' => __DIR__ . '/..' . '/symnext/symnext/src/Boot/utilities.php',
        '91a19c89bb033d89eb07b2284b1f782a' => __DIR__ . '/..' . '/symnext/symnext/src/Boot/defines.php',
        'b0fe325ef2b70f2a8d982576da434159' => __DIR__ . '/..' . '/symnext/symnext/src/init.php',
        '22564cc0e3ccb5ac49fda2513a76362a' => __DIR__ . '/..' . '/symnext/symnext_admin_ui/src/init.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symnext\\AdminUI\\' => 16,
            'Symnext\\' => 8,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'G' => 
        array (
            'Garden\\Cli\\' => 11,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symnext\\AdminUI\\' => 
        array (
            0 => __DIR__ . '/..' . '/symnext/symnext_admin_ui/src',
        ),
        'Symnext\\' => 
        array (
            0 => __DIR__ . '/..' . '/symnext/symnext/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Garden\\Cli\\' => 
        array (
            0 => __DIR__ . '/..' . '/vanilla/garden-cli/src',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1fa7c3fe3acbb539df60ed60bc0c9ffd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1fa7c3fe3acbb539df60ed60bc0c9ffd::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1fa7c3fe3acbb539df60ed60bc0c9ffd::$classMap;

        }, null, ClassLoader::class);
    }
}
