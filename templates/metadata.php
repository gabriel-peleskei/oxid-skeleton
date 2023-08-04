<?php declare(strict_types=1);

use __MODULENAMESPACE__;

$sMetadataVersion = '2.1';

$sVersion = Module::VERSION;

$aModule = [
    'id'                      => Module::ID,
    'title'                   => [
        'de'                  => "__TITLE__",
        'en'                  => "__TITLE__"
    ],
    'description'             => [
        'de'                  => '__DESCRIPTION__',
        'en'                  => '__DESCRIPTION__'
    ],
    'thumbnail'               => 'out/logo.png',
    'version'                 => $sVersion,
    'author'                  => '',
    'url'                     => '',
    'email'                   => '',
    'controllers'             => [],
    'templates'               => [],
    'extend'                  => [],
    'blocks'                  => [],
    'events'                  => [
        'onActivate'   => Module::class . '::onActivate',
        'onDeactivate' => Module::class . '::onDeactivate',
    ],
    'settings'                => [],
    'smartyPluginDirectories' => []
];