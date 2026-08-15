# Paper-check: does the `TenantResolver` contract fit real tenancy packages?

The contract is the riskiest thing v1 publishes. An adapter can be rewritten in
a patch release; the interface an application implements cannot be reshaped
without a major version. This is the check, done on paper against the two
ecosystems that matter, before the shape was frozen.

The shape under review:

```php
interface TenantResolver
{
    public function makeCurrent(Profile $profile): void;
}
```

Called by the login flow before the user resolver runs, once per click, only
for profiles that name a `tenant`.

## spatie/laravel-multitenancy

That package's model is a tenant object with a `makeCurrent()` method, and a
set of configured tasks that run on every switch: swapping the database
connection, prefixing the cache, and whatever the application added.

An adapter is the obvious two lines:

```php
final class SpatieTenantResolver implements TenantResolver
{
    public function makeCurrent(Profile $profile): void
    {
        Tenant::query()->where('name', $profile->tenant)->firstOrFail()->makeCurrent();
    }
}
```

**Fits.** The vocabulary is the same word, the timing is the same moment, and
the tasks run because `makeCurrent()` on the tenant is what runs them. The
profile's `tenant` value is whatever column the application looks tenants up
by, which is why the package hands it over untouched instead of typing it.

What the contract deliberately does not do is return the tenant. Returning one
would mean naming a tenant type, and this package has no business having an
opinion about what a tenant is.

## stancl/tenancy

That package initialises tenancy through middleware, keyed off the request:
`InitializeTenancyByDomain`, `...BySubdomain`, `...ByPath`, and
`...ByRequestData` among others. Tenancy is normally a property of how the
request arrived, not of what the controller decided.

There is still a programmatic entry point - `tenancy()->initialize($tenant)` -
which is what an adapter would call:

```php
final class StanclTenantResolver implements TenantResolver
{
    public function makeCurrent(Profile $profile): void
    {
        tenancy()->initialize(Tenant::findOrFail($profile->tenant));
    }
}
```

**Fits, with a caveat worth writing down.** Initialising tenancy inside the
request works, but the *next* request carries no tenant unless it arrives on a
domain, subdomain, or path that stancl's middleware recognises. In practice
that means a stancl application's tenant profiles want a `redirect` pointing at
the tenant's own domain or path, so the redirect after login lands somewhere
the middleware can initialise from.

That is an adapter and documentation concern rather than a contract one: the
seam is still "make this profile's tenant current, now", and nothing about the
shape would change to accommodate it.

## What the check ruled out

- **Returning the tenant** (`makeCurrent(Profile): object`). Forces the package
  to name a tenant type, and gives callers something to depend on that no two
  tenancy packages agree about.
- **Passing the tenant value rather than the profile**
  (`makeCurrent(string|int $tenant)`). Cheaper to implement, but an adapter
  that wants the profile key for an error message, or the guard, cannot have
  it. The profile is the package's one noun; passing it costs nothing.
- **A second `endTenancy()` method.** Nothing in the login flow would call it.
  A login that fails after the tenant is current leaves it current for the rest
  of a request that is about to render an error page, and a method that exists
  only for that is a method that gets implemented wrongly.
- **Making the resolver responsible for the redirect.** Tempting for stancl,
  but it would fold two decisions into one interface and make the redirect
  precedence chain untestable without a tenancy package.

## Conclusion

The shape holds for both ecosystems. The Spatie adapter is the first
integration and lands after v1; stancl support is later scope, and its caveat
above is the reason it is not first.
