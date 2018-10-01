<?php

namespace App\Http\Controllers\Auth;

use ThrottlesLogins;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LdapController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    private $ldapController;
    private $withLdap = false;
    
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance .
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        if (env('USE_LDAP')) {
          $this->withLdap = true;
          $this->ldapController = new LdapController(env('LDAP_URL'), env('LDAP_PARAMS'));
        }
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $id = $request->input('userID');
        $password = $request->input('password');
        if ($this->withLdap && $this->ldapController->authenticate($id, $password)) {
            $user = User::where('username', '=', $id)->first();
            if (!$user) {
                Auth::login($this->createUser($id));
            } else {
                Auth::login($user);
            }
            return $this->sendLoginResponse($request);
        } else if (!$this->withLdap) {
          $user = User::where('username', '=', $id)->first();
          Auth::login($user);
          return $this->sendLoginResponse($request);
        } else {
            // If the login attempt was unsuccessful we will increment the number of attempts
            // to login and redirect the user back to the login form. Of course, when this
            // user surpasses their maximum number of attempts they will get locked out.
            $this->incrementLoginAttempts($request);

            return $this->sendFailedLoginResponse($request);
        }

    }

    private function userExists($id)
    {
        return User::where('username', $id)->exists();
    }

    private function createUser($id)
    {
        $userData = $this->ldapController->getUserData($id);
        $userData['username'] = $id;
        
        // Remove the password such that it won't be saved into the mahout database
        $userData['password'] = '';
        $user = User::create($userData);
        return $user;
    }

    private function validateLogin(Request $request)
    {
        $this->validate($request, [
            'userID'        => 'required|string',
            'password'  => 'required|string'
        ]);
    }
}
