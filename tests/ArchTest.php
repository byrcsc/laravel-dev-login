<?php

declare(strict_types=1);

arch('no debugging leftovers ship')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

arch('everything declares strict types')
    ->expect('ByRcsc\LaravelDevLogin')
    ->toUseStrictTypes();

/*
 * The published config has to survive `config:cache`, and `var_export()` is
 * what caching it comes down to. A closure anywhere in the file turns a cached
 * config into a fatal error in an application that runs the cache command,
 * which is why every seam in this package is a class-string instead.
 */
it('ships a config file that can be cached', function (): void {
    $config = require __DIR__.'/../config/dev-login.php';

    expect($config)->toBeArray();

    $closures = static function (array $values) use (&$closures): bool {
        foreach ($values as $value) {
            if ($value instanceof Closure) {
                return true;
            }

            if (is_array($value) && $closures($value)) {
                return true;
            }
        }

        return false;
    };

    expect($closures($config))->toBeFalse('config/dev-login.php contains a closure.');
});
