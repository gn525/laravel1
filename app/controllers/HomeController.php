<?php

class HomeController extends SiteController {

    public function __construct() {
        parent::__construct();
    }

    public function homePage() {
        parent::setPageTitle('Homepage');

        return parent::getTemplate('homepage');
    }

    public function signIn() {
        parent::setPageTitle('Sign in');

        $data = array(
            'email' => Input::old('email', ''),
        );

        return parent::getTemplate('sign-in', $data);
    }

    public function doSignIn() {
        $user_id = 0;

        $email = Input::get('email', '');
        $password = Input::get('password', '');
        $remember_me = (bool) Input::get('remember_me', 0);

        if (strlen($email) == 0) {
            parent::alertDanger('Empty email address');
            return Redirect::to('sign-in')->withInput();
        }
        
        if (strlen($password) == 0) {
            parent::alertDanger('Empty password');
            return Redirect::to('sign-in')->withInput();
        }
        
        try {
            $user = User::where('email', '=', $email)->firstOrFail();
            
            $user_id = intval($user->id);
        } catch (Exception $e) {
            parent::alertDanger('There\'s no account having this email address. Please try again...');
            return Redirect::to('sign-in')->withInput();
        }
       
       if ($user_id > 0) {
            if ($user->active == 'no') {
                parent::alertDanger('Your account is deactivated');
                return Redirect::to('sign-in')->withInput();
            }
       
            if (Hash::check($password, $user->password)) {
                
                Auth::attempt(array('email' => $email, 'password' => $password, 'active' => 'yes'), $remember_me);

                if ($remember_me == false) {
                    $user->remember_token = '';
                    $user->save();
                }

                parent::alertSuccess('Hello '.$user->first_name.' '.$user->last_name.'! You are successfully signed in.');

                return Redirect::to('/');

            } else {
                parent::alertDanger('Wrong password');
                return Redirect::to('sign-in')->withInput();
            }
       } else {
           return Redirect::to('sign-in')->withInput();
       }
       
    }

    public function signOut() {
        Auth::logout();
        
        return Redirect::to('/');
    }
    
    public function createAccount() {
        parent::setPageTitle('Create account');

        $type = (array) Input::old('type');

        $data = array(
            'email' => Input::old('email', ''),
            'first_name' => Input::old('first_name', ''),
            'last_name' => Input::old('last_name', ''),
            'is_agency' => (is_array($type) && in_array('agent', $type) ? true : false),
            'is_candidate' => (is_array($type) && in_array('candidate', $type) ? true : false),
            'is_employer' => (is_array($type) && in_array('employer', $type) ? true : false)
        );

        return parent::getTemplate('create-account', $data);
    }
    
    public function doCreateAccount() {
        $email = trim(Input::get('email'));
        $first_name = trim(Input::get('first_name'));
        $last_name = trim(Input::get('last_name'));
        $password1 = Input::get('password1');
        $password2 = Input::get('password2');
        $type = (array) Input::get('type');
        
        $errors = array();
        
        if (strlen($email) == 0) {
            $errors[] = 'Email address is required';
        }
        
        if (strlen($first_name) == 0) {
            $errors[] = 'First name is required';
        }
        
        if (strlen($last_name) == 0) {
            $errors[] = 'Last name is required';
        }
        
        if (strlen($password1) < 5) {
            $errors[] = 'Password is too short';
        } else {
            if ($password1 !== $password2) {
                $errors[] = 'Passwords don\'t match';
            }
        }
        
        if (count($type) == 0) {
            $errors[] = 'Account type is required';
        }
        
        if (count($errors) === 0) {
             // create the actual account
            try {

                $user = new User();
                $user->email = $email;
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->password = Hash::make($password1);
                $user->is_agent = in_array('agent', $type) ? 'yes' : 'no';
                $user->is_candidate = in_array('candidate', $type) ? 'yes' : 'no';
                $user->is_employer = in_array('employer', $type) ? 'yes' : 'no';
                $user->save();
                
                $user_id = $user->id;
                
                Auth::login($user);
            
                //echo $user_id; die();
                
                return Redirect::to('my-account');

            } catch (Exception $e) {
            
                if (intval($e->getCode()) === 23000) {
                    $errors[] = 'An account with your email address already exists';
                } else {
                    $errors[] = 'Could not create your account. Please try again...';
                }
            }
        }
        
        if (count($errors) === 0) {

            return Redirect::to('create-account');

        } else {
        
            parent::alertDanger(implode('<br>', $errors));
            return Redirect::to('create-account')->withInput();

        }
    }
    
    public function myAccount() {
        if (!Auth::check()) {
            return Redirect::to('/sign-in');
        }

        parent::setPageTitle('My account');

        $data = array();

        return parent::getTemplate('my-account', $data);
    }
    
    public function socialLinkedIn() {
        $provider = new Linkedin(Config::get('social.linkedin'));
        
        if (!Input::has('code')) {
            $provider->authorize();
        } else {
        
            try {
                $t = $provider->getAccessToken('authorization_code', array('code' => Input::get('code')));

                try {
                    $userDetails = $provider->getUserDetails($t);
                    $resource = '/v1/people/~:(id,firstName,lastName,emailAddress,pictureUrl,positions,educations,threeCurrentPositions,threePastPositions,dateOfBirth,location)';
                    $params = array(
                        'oauth2_access_token' => $t->accessToken,
                        'format' => 'json'
                    );

                    $url = 'https://api.linkedin.com'.$resource.'?'.http_build_query($params);

                    $context = stream_context_create(array('http' => array('method' => 'GET')));
                    $response = file_get_contents($url, false, $context);
                    $data = json_decode($response);
                    
                    $email = trim($data->emailAddress);
                    $firstName = trim($data->firstName);
                    $lastName = trim($data->lastName);
                    $linkedInID = $data->id;

                    $user = User::where('email', '=', $email)->first();
                    
                    if ($user == null) {
                        $user = new User();
                        $user->email = $email;
                        $user->first_name = $firstName;
                        $user->last_name = $lastName;
                        $user->is_candidate = 'yes';
                        $user->is_agent = 'no';
                        $user->linkedin_id = $linkedInID;
                        $user->active = 'yes';
                        
                        $user->save();
                        
                        Auth::login($user);
                        
                        parent::alertSuccess('Welcome to propel.me.uk...');
                        
                        return Redirect::to('/');
                        
                    } else {
                        if ($user->linkedin_id != $linkedInID) {
                            $user->linkedin_id = $linkedInID;
                            $user->save();
                            
                            parent::alertSuccess('Your propel.me.uk account is now connected with LinkedIn');
                        } else {
                            parent::alertSuccess('Hello '.$user->first_name.' '.$user->last_name.'! You are successfully signed in.');
                        }
                        
                        Auth::login($user);

                        return Redirect::to('/');
                    }
                    
                } catch (Exception $e) {
                    // Unable to get user details
                    //echo $e->getMessage();
                    //return 'Unable to get user details';
                    
                    parent::alertDanger('Unable to get profile details');
                    return Redirect::to('/sign-in');
                }
            } catch (Exception $e) {
                // Unable to get access token
                //return 'Unable to get access token';
                parent::alertDanger('Could not get access token');
                return Redirect::to('/sign-in');
            }
            
        }
    }
    
    public function socialFacebook() {
        $facebook = new Facebook(Config::get('social.facebook'));
        
        $code = Input::get('code');
        
        if (strlen($code) == 0) {
            $params = array(
                'redirect_uri' => url('/connect/facebook'),
                'scope' => 'email',
            );
            
            return Redirect::to($facebook->getLoginUrl($params));
        }
        
        $me = $facebook->api('/me');
        
        $email = $me['email'];
        $firstName = $me['first_name'];
        $lastName = $me['last_name'];
        $fcbID = $me['id'];
        
        //print_r($me);

        $user = User::where('email', '=', $email)->first();
                    
        if ($user == null) {
            $user = new User();
            $user->email = $email;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->is_candidate = 'yes';
            $user->is_agent = 'no';
            $user->facebook_id = $fcbID;
            $user->active = 'yes';
            
            $user->save();
            
            Auth::login($user);
            
            parent::alertSuccess('Welcome to propel.me.uk...');
            
            return Redirect::to('/');
            
        } else {
            if ($user->facebook_id != $fcbID) {
                $user->facebook_id = $fcbID;
                $user->save();
                
                parent::alertSuccess('Your propel.me.uk account is now connected with Facebook');
            } else {
                parent::alertSuccess('Hello '.$user->first_name.' '.$user->last_name.'! You are successfully signed in.');
            }
            
            Auth::login($user);

            return Redirect::to('/');
        }
    }
}
