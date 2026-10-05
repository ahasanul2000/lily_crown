<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Storefront customer accounts, authenticated with Sanctum bearer tokens. */
class AccountController extends Controller
{
    private const PHONE_RULE = 'regex:/^(\+?88)?01[3-9]\d{8}$/';

    private const MESSAGES = [
        'phone.regex' => 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678).',
        'phone.unique' => 'An account with this mobile number already exists. Please log in.',
        'email.unique' => 'An account with this email already exists. Please log in.',
    ];

    public function register(Request $request)
    {
        $request->merge(['phone' => self::normalisePhone($request->input('phone'))]);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'string', self::PHONE_RULE, 'unique:users,phone'],
            'email' => 'nullable|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], self::MESSAGES);

        $user = User::create($data);

        return response()->json($this->withToken($user), 201);
    }

    /** Log in with either the mobile number or the email address. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:150',
            'password' => 'required|string',
        ]);

        $login = trim($data['login']);
        $user = str_contains($login, '@')
            ? User::where('email', $login)->first()
            : User::where('phone', self::normalisePhone($login))->first();

        // Staff accounts sign in through the admin panel, not the storefront.
        if (! $user || $user->is_admin || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Incorrect mobile number/email or password.']);
        }

        return response()->json($this->withToken($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->present($request->user())]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->merge(['phone' => self::normalisePhone($request->input('phone', $user->phone))]);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'string', self::PHONE_RULE, Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'shipping_area' => ['nullable', Rule::in(array_keys(CheckoutService::SHIPPING_AREAS))],
        ], self::MESSAGES);

        $user->update($data);

        return response()->json(['message' => 'Profile updated', 'data' => $this->present($user)]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $user->update(['password' => $data['password']]);
        // Sign out other devices; keep this one.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();

        return response()->json(['message' => 'Password changed']);
    }

    public function orders(Request $request)
    {
        $page = $request->user()->orders()->with('orderItems')->latest('id')->paginate(10);

        return response()->json([
            'data' => collect($page->items())->map->toCustomerArray(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    private function withToken(User $user): array
    {
        return [
            'token' => $user->createToken('storefront')->plainTextToken,
            'data' => $this->present($user),
        ];
    }

    private function present(User $user): array
    {
        return $user->only(['id', 'name', 'phone', 'email', 'address', 'city', 'shipping_area'])
            + ['orders_count' => $user->orders()->count()];
    }

    /** Store numbers as 01XXXXXXXXX so "+880 1712-345678" and "01712345678" are the same account. */
    private static function normalisePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) >= 11 ? substr($digits, -11) : $phone;
    }
}
