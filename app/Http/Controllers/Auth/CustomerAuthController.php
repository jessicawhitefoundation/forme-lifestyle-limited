<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class CustomerAuthController extends Controller
{
    public function showRegister(): Response
    {
        return Inertia::render('auth/Register', [
            'redirect' => $this->rememberRedirect(),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $sessionId = session()->getId();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        Auth::login($user);

        // Merge any items in guest cart into user cart
        Cart::mergeSessionCartToUser($sessionId, $user->id);

        // Send welcome email
        OrderMailService::sendWelcomeNotification($user);

        return redirect()->intended(route('home'));
    }

    public function showLogin(): Response
    {
        return Inertia::render('auth/Login', [
            'redirect' => $this->rememberRedirect(),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $sessionId = session()->getId();
        $remember = $request->boolean('remember');
        $credentials = $request->only('email', 'password');

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->first();

        // Customers who switched on two-step login must complete it first.
        if (Features::enabled(Features::twoFactorAuthentication()) && $user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->id, 'login.remember' => $remember]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, $remember);
        Cart::mergeSessionCartToUser($sessionId, $user->id);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function account(): Response
    {
        $user = auth()->user();

        return Inertia::render('auth/Account', [
            'user' => $user,
            'savedCard' => $user->paystack_auth_code ? [
                'last_four' => $user->card_last_four,
                'brand' => $user->card_brand,
            ] : null,
        ]);
    }

    public function orders(): Response
    {
        $orders = Order::where('user_id', auth()->id())
            ->where('payment_status', 'paid')
            ->with('items')
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total_amount' => $o->total_amount,
                'items_count' => $o->items->count(),
                'routely_tracking_number' => $o->routely_tracking_number,
                'routely_status' => $o->routely_status,
                'created_at' => $o->created_at->format('M d, Y'),
            ]);

        return Inertia::render('auth/Orders', ['orders' => $orders]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city' => 'nullable|string|max:120',
            'shipping_state' => 'nullable|string|max:60',
        ]);

        auth()->user()->update($request->only([
            'name', 'phone', 'shipping_address', 'shipping_city', 'shipping_state',
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function removeSavedCard()
    {
        auth()->user()->forceFill([
            'paystack_auth_code' => null,
            'card_last_four' => null,
            'card_brand' => null,
        ])->save();

        return back()->with('success', 'Your saved card has been removed.');
    }

    /**
     * Send the customer back where they came from after signing in. Only paths
     * on this site are accepted, so the link can't redirect anyone elsewhere.
     */
    private function rememberRedirect(): ?string
    {
        $redirect = request('redirect');

        if (! is_string($redirect) || ! preg_match('#^/(?![/\\\\])#', $redirect)) {
            return null;
        }

        redirect()->setIntendedUrl($redirect);

        return $redirect;
    }
}
