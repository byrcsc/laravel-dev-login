{{--
    The profile buttons show the configured label, email, and guard so a
    developer can distinguish similar accounts before authenticating.

    The styles are here rather than in the page so that the component looks
    like itself wherever it is dropped. They are scoped to one class name, they
    depend on nothing, and they take their text colour from the page around
    them so that a dark host page stays readable.
--}}
<div class="dev-login-profiles">
    @once
        <style>
            .dev-login-profiles { display: grid; gap: 1rem; }
            .dev-login-profiles h2 {
                margin: 0 0 .375rem;
                font-size: .625rem;
                font-weight: 700;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: inherit;
                opacity: .58;
            }
            .dev-login-profiles form { margin: 0 0 .375rem; }
            .dev-login-profiles form:last-child { margin-bottom: 0; }
            .dev-login-profiles button {
                width: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: .75rem;
                padding: .625rem .875rem;
                border: 1px solid #cbd5e1;
                border-radius: .75rem;
                background: #ffffff;
                color: #0f172a;
                font: inherit;
                text-align: left;
                cursor: pointer;
                transition: border-color 140ms ease, box-shadow 140ms ease, transform 140ms ease;
            }
            .dev-login-profiles button:hover {
                border-color: #64748b;
                box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .08);
                transform: translateY(-1px);
            }
            .dev-login-profiles button:focus-visible { outline: 2px solid #0f172a; outline-offset: 2px; }
            .dev-login-profiles .identity { min-width: 0; }
            .dev-login-profiles .name {
                display: block;
                overflow: hidden;
                font-size: .8125rem;
                font-weight: 700;
                line-height: 1.35;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .dev-login-profiles .email {
                display: block;
                overflow: hidden;
                margin-top: .0625rem;
                color: #64748b;
                font-size: .6875rem;
                line-height: 1.35;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .dev-login-profiles .role {
                display: inline-flex;
                align-items: center;
                gap: .375rem;
                padding: .25rem .4375rem;
                border-radius: 9999px;
                background: #f1f5f9;
                color: #475569;
                font-size: .5625rem;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
                white-space: nowrap;
            }
            .dev-login-profiles .role::after {
                content: '\2192';
                color: #94a3b8;
                font-size: .75rem;
            }
            .dev-login-profiles p { margin: 0; opacity: .7; }
            @media (max-width: 32rem) {
                .dev-login-profiles button { align-items: start; }
                .dev-login-profiles .role::after { display: none; }
            }
        </style>
    @endonce

    @forelse ($groups as $group)
        <section @if ($group['tenant'] === null) aria-label="Profiles with no tenant" @endif>
            @if ($group['tenant'] !== null)
                <h2>{{ $group['tenant'] }}</h2>
            @endif

            @foreach ($group['profiles'] as $profile)
                <form method="POST" action="{{ route('dev-login.attempt', $profile->key) }}">
                    @csrf
                    <button type="submit">
                        <span class="identity">
                            <span class="name">{{ $profile->label }}</span>
                            <span class="email">{{ $profile->email }}</span>
                        </span>
                        <span class="role">{{ $profile->guard ?? config('auth.defaults.guard', 'default') }} guard</span>
                    </button>
                </form>
            @endforeach
        </section>
    @empty
        <p>No profiles are configured. Add one to the <code>profiles</code> key in <code>config/dev-login.php</code>.</p>
    @endforelse
</div>
