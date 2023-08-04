<?php declare(strict_types=1);

namespace __ROOTNAMESPACE__\Application\Core;

class Module
{
    public const ID = '__ID__';
    public const VERSION = '__VERSION__';

    protected static $__instance;

    public static function getInstance()
    {
        return self::$__instance ?? (self::$__instance = oxNew(self::class));
    }

    public static function onActivate(): void
    {
        static::getInstance()->activate();
    }

    public static function onDeactivate(): void
    {
        static::getInstance()->deactivate();
    }

    public function activate(): void {}

    public function deactivate(): void {}
}