{{--
    The profile buttons. Labels only: an email address on a button is an
    account identifier in every screenshot and every screen-share.

    The styles are here rather than in the page so that the component looks
    like itself wherever it is dropped. They are scoped to one class name, they
    depend on nothing, and they take their text colour from the page around
    them so that a dark host page stays readable.
--}}
<div class="dev-login-profiles">
    @once
        <style>
            .dev-login-profiles { display: grid; gap: 1.5rem; }
            .dev-login-profiles h2 {
                margin: 0 0 .5rem;
                font-size: .75rem;
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: inherit;
                opacity: .7;
            }
            .dev-login-profiles form { margin: 0 0 .5rem; }
            .dev-login-profiles button {
                width: 100%;
                padding: .75rem 1rem;
                border: 1px solid #94a3b8;
                border-radius: .5rem;
                background: #ffffff;
                color: #0f172a;
                font: inherit;
                font-weight: 500;
                text-align: left;
                cursor: pointer;
            }
            .dev-login-profiles button:hover { border-color: #475569; background: #f1f5f9; }
            .dev-login-profiles button:focus-visible { outline: 2px solid #0f172a; outline-offset: 2px; }
            .dev-login-profiles p { margin: 0; opacity: .7; }
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
                    <button type="submit">{{ $profile->label }}</button>
                </form>
            @endforeach
        </section>
    @empty
        <p>No profiles are configured. Add one to the <code>profiles</code> key in <code>config/dev-login.php</code>.</p>
    @endforelse
</div>
