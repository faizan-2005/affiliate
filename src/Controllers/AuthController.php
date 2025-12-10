<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Affiliate;
use App\Models\Advertiser;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        return $this->view('auth/login');
    }

    public function login()
    {
        if ($this->request->isGet()) {
            return $this->showLogin();
        }

        $errors = $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($this->hasErrors($errors)) {
            return $this->view('auth/login', ['errors' => $errors]);
        }

        $email = $this->input('email');
        $password = $this->input('password');

        $user = User::where('email', '=', $email)->first();

        if (!$user || !$user->verifyPassword($password)) {
            return $this->view('auth/login', [
                'error' => 'Invalid email or password'
            ]);
        }

        if (!$user->isActive()) {
            return $this->view('auth/login', [
                'error' => 'Your account is not active'
            ]);
        }

        // Set session
        Session::put('user_id', $user->id);
        Session::put('user_role', $user->role);

        // Update last login
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        // Redirect based on role
        $redirects = [
            'admin' => '/admin/dashboard',
            'affiliate' => '/affiliate/dashboard',
            'advertiser' => '/advertiser/dashboard',
        ];

        return $this->redirect($redirects[$user->role] ?? '/');
    }

    public function showRegister()
    {
        return $this->view('auth/register');
    }

    public function register()
    {
        if ($this->request->isGet()) {
            return $this->showRegister();
        }

        $errors = $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'role' => 'required',
            'company_name' => 'required'
        ]);

        if ($this->hasErrors($errors)) {
            return $this->view('auth/register', ['errors' => $errors]);
        }

        $email = $this->input('email');

        if (User::where('email', '=', $email)->first()) {
            return $this->view('auth/register', [
                'error' => 'Email already registered'
            ]);
        }

        // Create user
        $user = User::create([
            'name' => $this->input('name'),
            'email' => $email,
            'password' => password_hash($this->input('password'), PASSWORD_BCRYPT),
            'role' => $this->input('role'),
            'status' => 'active'
        ]);

        // Create role-specific record
        if ($user->role === 'affiliate') {
            Affiliate::create([
                'user_id' => $user->id,
                'company_name' => $this->input('company_name'),
                'approval_status' => 'pending'
            ]);
        } elseif ($user->role === 'advertiser') {
            Advertiser::create([
                'user_id' => $user->id,
                'company_name' => $this->input('company_name')
            ]);
        }

        return $this->redirect('/login?registered=1');
    }

    public function logout()
    {
        Session::flush();
        return $this->redirect('/login');
    }

    public function showForgotPassword()
    {
        return $this->view('auth/forgot-password');
    }

    public function sendReset()
    {
        $email = $this->input('email');
        $user = User::where('email', '=', $email)->first();

        if (!$user) {
            return $this->view('auth/forgot-password', [
                'message' => 'If this email exists, you will receive a password reset link'
            ]);
        }

        // Create reset token (would send email in production)
        $token = bin2hex(random_bytes(32));
        
        // Store token in database
        \App\Core\Database::getInstance()->insert('password_reset_tokens', [
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600)
        ]);

        // In production, send email with reset link
        log_info("Password reset token created for user {$user->id}");

        return $this->view('auth/forgot-password', [
            'message' => 'Password reset link sent to your email'
        ]);
    }
}

function auth()
{
    return new class {
        public function check()
        {
            return Session::has('user_id');
        }

        public function user()
        {
            if ($this->check()) {
                return User::find(Session::get('user_id'));
            }
            return null;
        }

        public function id()
        {
            return Session::get('user_id');
        }

        public function role()
        {
            return Session::get('user_role');
        }

        public function hasRole($role)
        {
            return $this->role() === $role;
        }

        public function isAdmin()
        {
            return $this->role() === 'admin';
        }

        public function isAffiliate()
        {
            return $this->role() === 'affiliate';
        }

        public function isAdvertiser()
        {
            return $this->role() === 'advertiser';
        }

        public function logout()
        {
            Session::flush();
        }
    };
}
