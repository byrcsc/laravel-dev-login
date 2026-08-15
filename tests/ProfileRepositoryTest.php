<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileNotFound;
use ByRcsc\LaravelDevLogin\Profile;
use ByRcsc\LaravelDevLogin\ProfileRepository;
use ByRcsc\LaravelDevLogin\Tests\Support\CountingResolver;

function profiles(array $profiles): ProfileRepository
{
    config()->set('dev-login.profiles', $profiles);

    return app(ProfileRepository::class);
}

it('hydrates the two-line profile the quick start shows', function (): void {
    $profile = profiles([
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com'],
    ])->find('admin');

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->key)->toBe('admin')
        ->and($profile->label)->toBe('Admin')
        ->and($profile->email)->toBe('admin@example.com')
        ->and($profile->guard)->toBeNull()
        ->and($profile->remember)->toBeFalse()
        ->and($profile->tenant)->toBeNull()
        ->and($profile->redirect)->toBeNull()
        ->and($profile->resolver)->toBeNull()
        ->and($profile->fireLoginEvent)->toBeTrue()
        ->and($profile->hasTenant())->toBeFalse();
});

it('hydrates a profile that names everything', function (): void {
    $profile = profiles([
        'owner' => [
            'label' => 'Acme owner',
            'email' => 'owner@acme.test',
            'guard' => 'web',
            'remember' => true,
            'tenant' => 'acme',
            'redirect' => '/dashboard',
            'resolver' => CountingResolver::class,
            'fire_login_event' => false,
        ],
    ])->find('owner');

    expect($profile->guard)->toBe('web')
        ->and($profile->remember)->toBeTrue()
        ->and($profile->tenant)->toBe('acme')
        ->and($profile->redirect)->toBe('/dashboard')
        ->and($profile->resolver)->toBe(CountingResolver::class)
        ->and($profile->fireLoginEvent)->toBeFalse()
        ->and($profile->hasTenant())->toBeTrue();
});

it('keeps an integer tenant an integer', function (): void {
    $profile = profiles([
        'one' => ['label' => 'One', 'email' => 'one@example.com', 'tenant' => 7],
    ])->find('one');

    expect($profile->tenant)->toBe(7);
});

it('hands out every configured profile, keyed by the route parameter', function (): void {
    $all = profiles([
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com'],
        'member' => ['label' => 'Member', 'email' => 'member@example.com'],
    ])->all();

    expect(array_keys($all))->toBe(['admin', 'member'])
        ->and($all['member']->label)->toBe('Member');
});

it('names the profile it could not find, and the ones it has', function (): void {
    $repository = profiles([
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com'],
    ]);

    expect(fn () => $repository->find('ghost'))
        ->toThrow(ProfileNotFound::class, 'There is no dev login profile [ghost]. Configured profiles: admin.');
});

it('says so when nothing is configured at all', function (): void {
    expect(fn () => profiles([])->find('admin'))
        ->toThrow(ProfileNotFound::class, 'No dev login profiles are configured.');
});

it('refuses a profile it cannot read, and always names the key', function (array $settings, string $expected): void {
    expect(fn () => profiles(['broken' => $settings])->find('broken'))
        ->toThrow(InvalidProfile::class, $expected);
})->with([
    'no label' => [
        ['email' => 'admin@example.com'],
        'Dev login profile [broken] is missing a [label], which every profile needs.',
    ],
    'no email' => [
        ['label' => 'Admin'],
        'Dev login profile [broken] is missing a [email], which every profile needs.',
    ],
    'empty label' => [
        ['label' => '', 'email' => 'admin@example.com'],
        'Dev login profile [broken] is missing a [label], which every profile needs.',
    ],
    'label of the wrong type' => [
        ['label' => ['Admin'], 'email' => 'admin@example.com'],
        'Dev login profile [broken] has a [label] that is not a string.',
    ],
    'a tenant that is not a scalar' => [
        ['label' => 'Admin', 'email' => 'admin@example.com', 'tenant' => ['acme']],
        'Dev login profile [broken] has a [tenant] that is not a string, an integer, or null.',
    ],
    'remember that is not a boolean' => [
        ['label' => 'Admin', 'email' => 'admin@example.com', 'remember' => 'yes'],
        'Dev login profile [broken] has a [remember] that is not true or false.',
    ],
    'an unknown guard' => [
        ['label' => 'Admin', 'email' => 'admin@example.com', 'guard' => 'ghost'],
        'Dev login profile [broken] names the guard [ghost], which is not in config/auth.php.',
    ],
    'a resolver that is not one' => [
        ['label' => 'Admin', 'email' => 'admin@example.com', 'resolver' => Profile::class],
        'does not implement ByRcsc\LaravelDevLogin\Contracts\UserResolver',
    ],
    'a setting nobody has heard of' => [
        ['label' => 'Admin', 'email' => 'admin@example.com', 'guardd' => 'web'],
        'Dev login profile [broken] has settings this package does not know: guardd.',
    ],
]);

it('refuses a profile that is not a list of settings', function (): void {
    expect(fn () => profiles(['broken' => 'admin@example.com'])->find('broken'))
        ->toThrow(InvalidProfile::class, 'Dev login profile [broken] must be an array of settings.');
});

/*
 * Token and API guards are out of scope, and the place to find that out is the
 * config file rather than a session that silently does not persist.
 */
it('refuses a guard it cannot drive', function (): void {
    config()->set('auth.guards.api', ['driver' => 'token', 'provider' => 'users']);

    expect(fn () => profiles([
        'api' => ['label' => 'API', 'email' => 'admin@example.com', 'guard' => 'api'],
    ])->find('api'))->toThrow(InvalidProfile::class, 'This package drives session guards only.');
});

/*
 * `config:cache` is `var_export()` of the whole live config and a `require` of
 * the file it wrote, so this freezes the real config - the shipped file, with
 * profiles added the way an application adds them - and hydrates from the
 * thawed copy.
 */
it('reads a profile the same way after the config has been cached', function (): void {
    $repository = profiles([
        'admin' => [
            'label' => 'Admin',
            'email' => 'admin@example.com',
            'guard' => 'web',
            'remember' => true,
            'tenant' => 'acme',
        ],
    ]);

    $uncached = $repository->find('admin');

    $path = tempnam(sys_get_temp_dir(), 'dev-login-config').'.php';
    file_put_contents($path, '<?php return '.var_export(config('dev-login'), true).';');

    /** @var array<string, mixed> $frozen */
    $frozen = require $path;
    unlink($path);

    config()->set('dev-login', $frozen);

    expect(app(ProfileRepository::class)->find('admin'))->toEqual($uncached);
});
