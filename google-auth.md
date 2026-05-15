# Implementing Google OAuth in Laravel

A complete guide using **Laravel Socialite** — the standard package for OAuth in Laravel.

---

## 1. Install Laravel Socialite

```bash
composer require laravel/socialite
```

---

## 2. Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Navigate to **APIs & Services → Credentials**
4. Click **Create Credentials → OAuth Client ID**
5. Set **Authorized redirect URIs** to: `http://your-app.com/auth/google/callback`
6. Copy your **Client ID** and **Client Secret**

---

## 3. Add Credentials to `.env`

```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://your-app.com/auth/google/callback
```

---

## 4. Configure `config/services.php`

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

---

## 5. Add Routes (`routes/web.php`)

```php
use App\Http\Controllers\Auth\GoogleController;

Route::get('/auth/google',          [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
```

---

## 6. Create the Controller

```bash
php artisan make:controller Auth/GoogleController
```

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // Redirect user to Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Handle Google callback
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors('Google login failed. Please try again.');
        }

        // Find or create user
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'              => $googleUser->getName(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now(), // Google emails are pre-verified
                'password'          => null,  // No password for OAuth users
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }
}
```

---

## 7. Update the `users` Table

```bash
php artisan make:migration add_google_fields_to_users_table
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('google_id')->nullable()->unique()->after('id');
        $table->string('avatar')->nullable()->after('google_id');
        $table->string('password')->nullable()->change(); // Allow null for OAuth users
    });
}
```

```bash
php artisan migrate
```

---

## 8. Update the `User` Model

Add the new fields to `$fillable`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'google_id',  // add this
    'avatar',     // add this
];
```

---

## 9. Add Login Button to Your View

```html
<a href="{{ route('auth.google') }}" class="btn btn-google">
    <img src="/google-icon.svg" alt="Google">
    Sign in with Google
</a>
```

---

## Key Considerations

| Topic | Recommendation |
|---|---|
| **Existing users** | `updateOrCreate` by email handles users who previously registered with a password |
| **Password column** | Make it nullable since OAuth users won't have one |
| **Token storage** | Store `google_token` / `google_refresh_token` if you need to call Google APIs on behalf of the user |
| **Scopes** | Add `.scopes(['email', 'profile'])` before `->redirect()` if you need extra permissions |
| **Stateless** | Use `->stateless()->user()` for APIs instead of web sessions |

---

> **Flow summary:** User clicks button → redirected to Google → Google sends them back to your callback URL → you log them in or create their account.

---

## Using with Laravel Herd or Local by Flywheel

The setup is identical — the only thing you need to update is your **redirect URI** to match your local domain.

### Laravel Herd

Your app typically runs on a `.test` domain, so update your `.env`:

```env
GOOGLE_REDIRECT_URI=http://your-app.test/auth/google/callback
```

### Local by Flywheel

Same idea, just use your local domain (e.g., `your-app.local`):

```env
GOOGLE_REDIRECT_URI=http://your-app.local/auth/google/callback
```

Then in **Google Cloud Console → OAuth Credentials**, add that same URI to the **Authorized redirect URIs** list.

### One Gotcha — Google Requires HTTPS for Some Flows

Google OAuth **does accept `http://` for `localhost` and `.test`/`.local` domains** during development, so you won't be blocked. But if you run into issues:

- Herd supports **HTTPS with a trusted local cert** — you can enable it per-site and use `https://your-app.test/...` instead
- Make sure the redirect URI in your `.env` **exactly matches** what's registered in Google Cloud Console — even a trailing slash difference will cause an error

### Quick Checklist

- [ ] Update `GOOGLE_REDIRECT_URI` in `.env` to your local domain
- [ ] Add that URI to **Authorized redirect URIs** in Google Cloud Console
- [ ] Make sure `APP_URL` in `.env` also matches (e.g., `http://your-app.test`)
