<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5c2e7eccd6f0a442b7462d097abb3a8f
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5c2e7eccd6f0a442b7462d097abb3a8f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5c2e7eccd6f0a442b7462d097abb3a8f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5c2e7eccd6f0a442b7462d097abb3a8f::$classMap;

        }, null, ClassLoader::class);
    }
}
