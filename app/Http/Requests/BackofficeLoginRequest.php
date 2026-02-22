<?php

namespace App\Http\Requests;

use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use App\Support\Security\GuardIpBlockService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BackofficeLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $ipBlockService = app(GuardIpBlockService::class);
        if ($ipBlockService->isIpBlocked($this->ip())) {
            throw ValidationException::withMessages([
                'email' => 'Too many failed login attempts from this IP. Access is permanently blocked.',
            ]);
        }

        $this->ensureIsNotRateLimited();

        $email = (string) $this->string('email');
        $password = (string) $this->input('password');
        $remember = $this->boolean('remember');

        if ($this->isInactiveBackofficeAccount($email, $password) || $this->isInactiveDealerAccount($email, $password)) {
            $ipBlockService->recordFailedAttempt($this->ip(), $this->resolveGuardHint($email));
            RateLimiter::hit($this->throttleKey(), 300);

            throw ValidationException::withMessages([
                'email' => 'This account is inactive and cannot log in.',
            ]);
        }

        $backofficeAuthenticated = Auth::guard('backoffice')->attempt(
            [
                'email' => $email,
                'password' => $password,
                'is_active' => true,
            ],
            $remember
        );

        $dealerAuthenticated = false;
        if (! $backofficeAuthenticated) {
            $dealerAuthenticated = Auth::guard('dealer')->attempt(
                [
                    'email' => $email,
                    'password' => $password,
                    'is_active' => true,
                ],
                $remember
            );
        }

        if ($dealerAuthenticated) {
            $dealerUser = Auth::guard('dealer')->user();
            $dealerIsActive = $dealerUser instanceof DealerUser
                && $dealerUser->dealer()->where('is_active', true)->exists();

            if (! $dealerIsActive) {
                Auth::guard('dealer')->logout();
                $ipBlockService->recordFailedAttempt($this->ip(), 'dealer');
                RateLimiter::hit($this->throttleKey(), 300);

                throw ValidationException::withMessages([
                    'email' => 'This account is inactive and cannot log in.',
                ]);
            }
        }

        if (! $backofficeAuthenticated && ! $dealerAuthenticated) {
            $ipBlockService->recordFailedAttempt($this->ip(), $this->resolveGuardHint($email));
            RateLimiter::hit($this->throttleKey(), 300);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function isInactiveBackofficeAccount(string $email, string $password): bool
    {
        $user = User::query()->where('email', $email)->first();

        return $user instanceof User
            && Hash::check($password, (string) $user->password)
            && ! (bool) $user->is_active;
    }

    private function isInactiveDealerAccount(string $email, string $password): bool
    {
        $dealerUser = DealerUser::query()
            ->with('dealer:id,is_active')
            ->where('email', $email)
            ->first();

        if (! $dealerUser instanceof DealerUser) {
            return false;
        }

        if (! Hash::check($password, (string) $dealerUser->password)) {
            return false;
        }

        $dealerIsActive = (bool) optional($dealerUser->dealer)->is_active;

        return ! (bool) $dealerUser->is_active || ! $dealerIsActive;
    }

    private function resolveGuardHint(string $email): string
    {
        if (User::query()->where('email', $email)->exists()) {
            return 'backoffice';
        }

        if (DealerUser::query()->where('email', $email)->exists()) {
            return 'dealer';
        }

        return 'backoffice';
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $ipBlockService = app(GuardIpBlockService::class);
        $ipBlockService->recordFailedAttempt($this->ip(), 'backoffice');
        if ($ipBlockService->isIpBlocked($this->ip())) {
            throw ValidationException::withMessages([
                'email' => 'Too many failed login attempts from this IP. Access is permanently blocked.',
            ]);
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::lower((string) $this->input('email')).'|'.$this->ip();
    }

    public function wantsJson()
    {
        return false;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = redirect()
            ->back()
            ->withErrors($validator)
            ->withInput();

        throw new ValidationException($validator, $response);
    }

}
