<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Tests;

use ByRcsc\LaravelDevLogin\DevLoginServiceProvider;
use ByRcsc\LaravelDevLogin\Tests\Support\User;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [DevLoginServiceProvider::class];
    }

    /**
     * Config applied before the app boots. Set this and call
     * `refreshApplication()` to test anything decided at boot time - a plain
     * `config()->set()` lands too late, and the rebuild discards it.
     *
     * Nearly everything this package decides is decided at boot: whether the
     * gates pass, and therefore whether the routes exist at all. Expect to
     * use this more than in an ordinary package.
     *
     * @var array<string, mixed>
     */
    protected array $bootConfig = [];

    /**
     * Rebuild the application with extra config.
     *
     * @param  array<string, mixed>  $config
     */
    public function rebootWith(array $config): void
    {
        $this->bootConfig = array_merge($this->bootConfig, $config);

        $this->refreshApplication();
    }

    /**
     * The framework's own `users` table, and nothing of the package's: it
     * ships no schema because it writes nothing.
     *
     * Called by the tests that need users rather than by every test, because
     * most of this suite boots applications the gates refuse to open, and a
     * migration run is an expensive way to prove a route does not exist.
     */
    public function withUsersTable(): void
    {
        $this->loadLaravelMigrations();

        // MySQL and PostgreSQL keep their rows between tests, where SQLite in
        // memory hands each test an empty database. The table starts empty on
        // every engine rather than where the last test left it.
        DB::table('users')->delete();
    }

    protected function defineEnvironment($app): void
    {
        // The package drives a session guard, and a session needs cookies an
        // application can encrypt. Fixed rather than generated so a failure is
        // reproducible; it protects nothing.
        $app['config']->set('app.key', 'base64:GLXTBOOtsAv0YyRb2Vy5J5PS/w4Cc6vfCzZLwLVeb0Q=');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->databaseConnection());
        $app['config']->set('auth.providers.users.model', User::class);

        foreach ($this->bootConfig as $key => $value) {
            $app['config']->set($key, $value);
        }

        // `app.env` is read through the application rather than the config
        // repository once the framework has booted, so the two are kept in
        // step here. Booting into a chosen environment is most of what the
        // gate tests do.
        if (array_key_exists('app.env', $this->bootConfig)) {
            $environment = (string) $this->bootConfig['app.env'];

            $app->detectEnvironment(static fn (): string => $environment);
        }
    }

    /**
     * Which engine the suite runs against. SQLite in memory is the default;
     * CI's database matrix sets `DB_DRIVER` to prove the package against
     * MySQL and PostgreSQL as well.
     *
     * The package writes no schema and never touches a table of its own, but
     * it does read the guard's user provider, and a user provider reads a
     * database. That is the seam this matrix exists to cover.
     *
     * @return array<string, mixed>
     */
    protected function databaseConnection(): array
    {
        return match (env('DB_DRIVER', 'sqlite')) {
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => (int) env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'dev_login_test'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => (int) env('DB_PORT', 5432),
                'database' => env('DB_DATABASE', 'dev_login_test'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', 'postgres'),
                'charset' => 'utf8',
                'prefix' => '',
            ],
            default => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        };
    }
}
