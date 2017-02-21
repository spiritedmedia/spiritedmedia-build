<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInited8e0a8e89c4f2aaccb5c5745a10fa82
{
    public static $files = array (
        '026a968263ec72e63c0a08beed134bb1' => __DIR__ . '/..' . '/scribu/scb-framework/load-composer.php',
        'a5d94d5918910fbb77c98dd8aa0cc5d9' => __DIR__ . '/..' . '/scribu/scb-framework/Util.php',
    );

    public static $classMap = array (
        'scbAdminPage' => __DIR__ . '/..' . '/scribu/scb-framework/AdminPage.php',
        'scbBoxesPage' => __DIR__ . '/..' . '/scribu/scb-framework/BoxesPage.php',
        'scbCron' => __DIR__ . '/..' . '/scribu/scb-framework/Cron.php',
        'scbCustomField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbForm' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbFormField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbFormField_I' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbForms' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbHooks' => __DIR__ . '/..' . '/scribu/scb-framework/Hooks.php',
        'scbLoad4' => __DIR__ . '/..' . '/scribu/scb-framework/load.php',
        'scbMultipleChoiceField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbOptions' => __DIR__ . '/..' . '/scribu/scb-framework/Options.php',
        'scbPostMetabox' => __DIR__ . '/..' . '/scribu/scb-framework/PostMetabox.php',
        'scbRadiosField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbSelectField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbSingleCheckboxField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbSingleChoiceField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbTable' => __DIR__ . '/..' . '/scribu/scb-framework/Table.php',
        'scbTextField' => __DIR__ . '/..' . '/scribu/scb-framework/Forms.php',
        'scbUtil' => __DIR__ . '/..' . '/scribu/scb-framework/Util.php',
        'scbWidget' => __DIR__ . '/..' . '/scribu/scb-framework/Widget.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInited8e0a8e89c4f2aaccb5c5745a10fa82::$classMap;

        }, null, ClassLoader::class);
    }
}
