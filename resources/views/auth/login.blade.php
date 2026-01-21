<x-guest-layout>
    <div class="text-center mb-4">
        <div class="mx-auto mb-3" style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#06b6d4);display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 6v6l4 2"></path>
                <circle cx="12" cy="12" r="9"></circle>
            </svg>
        </div>
        <h1 class="h4 fw-bold">CONSULTPRO</h1>
        <p class="text-muted small">Accede a tu panel de consultas</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="invalid-feedback d-block" />
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="invalid-feedback d-block" />
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
      {{--       <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label small text-muted" for="remember_me">{{ __('Remember me') }}</label>
            </div> --}}
{{-- 
            @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif --}}
        </div>

        <div class="d-grid mb-2">
            <button type="submit" class="btn btn-primary btn-lg">{{ __('Ingresar') }}</button>
        </div>
    </form>

    <div class="text-center mt-3 small text-muted">¿No tienes cuenta? Contacta al administrador para acceso.</div>
</x-guest-layout>
