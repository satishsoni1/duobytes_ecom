@extends('auth.layouts.authentication')

@section('css')
    <style>
        .tz-auth { --tz-ink: #14161f; --tz-ink-2: #4a4f5e; --tz-line: #dfe2ea; display: grid; min-height: 100vh; min-height: 100dvh; background: #fff; color: var(--tz-ink); }
        .tz-auth-visual { display: none; }
        .tz-auth-main { display: flex; flex-direction: column; justify-content: center; padding: 20px 20px 32px; }
        .tz-auth-inner { width: 100%; max-width: 420px; margin: 0 auto; }
        .tz-auth-back { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; margin-bottom: 20px; font-size: 14px; font-weight: 600; color: var(--tz-ink-2) !important; }
        .tz-auth-back:hover { color: var(--primary) !important; text-decoration: none; }
        .tz-auth-back i { font-size: 18px; }
        .tz-auth-logo { display: inline-block; margin-bottom: 28px; }
        .tz-auth-logo img { display: block; height: 44px; width: auto; }
        .tz-auth h1 { margin: 0 0 6px; font-size: 28px; font-weight: 800; letter-spacing: -.02em; color: var(--tz-ink); }
        .tz-auth-sub { margin: 0 0 28px; font-size: 15px; color: var(--tz-ink-2); }
        .tz-auth .form-group { margin-bottom: 18px; }
        .tz-auth .form-group > label { margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--tz-ink); }
        .tz-auth .form-control { height: 50px; padding: 0 16px; border: 1px solid var(--tz-line); border-radius: 12px; font-size: 16px; color: var(--tz-ink); }
        .tz-auth .form-control:focus { border-width: 1px !important; border-color: var(--primary); box-shadow: 0 0 0 3px var(--soft-primary); }
        .tz-auth .form-control.is-invalid { border-color: #d43533; background-image: none; }
        .tz-auth .iti { width: 100%; }
        .tz-auth #password { padding-right: 50px; }
        .tz-auth .password-toggle { top: 25px; right: 0; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--tz-ink-2); }
        .tz-auth-options { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 16px; margin-bottom: 24px; font-size: 14px; }
        .tz-auth-options .aiz-checkbox { margin-bottom: 0; color: var(--tz-ink-2); }
        .tz-auth-options .aiz-checkbox > span:first-of-type { font-size: 14px; }
        .tz-auth-options a { font-weight: 600; color: var(--primary) !important; }
        .tz-auth-btn { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 50px; padding: 0 22px; border-radius: 999px; font-size: 15px; font-weight: 700; }
        .tz-auth-submit { border: 0; background: var(--primary); color: #fff; transition: background .15s; }
        .tz-auth-submit:hover, .tz-auth-submit:focus { background: var(--hov-primary); color: #fff; }
        .tz-auth-social-label { margin: 24px 0 12px; font-size: 13px; text-align: center; color: var(--tz-ink-2); }
        .tz-auth-register { margin-top: 28px; padding-top: 24px; border-top: 1px solid #eceef3; text-align: center; }
        .tz-auth-register p { margin: 0 0 12px; font-size: 14px; color: var(--tz-ink-2); }
        .tz-auth-register-btn { border: 1px solid var(--tz-line); color: var(--tz-ink) !important; transition: border-color .15s, color .15s; }
        .tz-auth-register-btn:hover { border-color: var(--primary); color: var(--primary) !important; text-decoration: none; }
        @media (min-width: 992px) {
            .tz-auth { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); }
            .tz-auth-visual { display: block; position: sticky; top: 16px; height: calc(100vh - 32px); margin: 16px 0 16px 16px; overflow: hidden; border-radius: 24px; background: var(--primary); }
            .tz-auth-visual img { width: 100%; height: 100%; object-fit: cover; }
            .tz-auth-visual::after { content: ""; position: absolute; inset: 50% 0 0; background: linear-gradient(to top, rgba(10, 20, 50, .72), rgba(10, 20, 50, 0)); }
            .tz-auth-motto { position: absolute; left: 40px; right: 40px; bottom: 36px; z-index: 1; margin: 0; font-size: 34px; font-weight: 800; line-height: 1.15; letter-spacing: -.02em; color: #fff; }
            .tz-auth-main { padding: 32px 48px; }
            .tz-auth h1 { font-size: 32px; }
        }
    </style>
@endsection

@section('content')
    <main class="tz-auth">
        <!-- Brand image -->
        <div class="tz-auth-visual">
            <img src="{{ uploaded_asset(get_setting('customer_login_page_image')) }}" alt="" loading="lazy">
            <p class="tz-auth-motto">{{ get_setting('site_motto') }}</p>
        </div>

        <div class="tz-auth-main">
            <div class="tz-auth-inner">
                <!-- Go Back -->
                <a href="{{ url()->previous() }}" class="tz-auth-back">
                    <i class="las la-arrow-left"></i>
                    {{ translate('Back to Previous Page')}}
                </a>
                <!-- Logo -->
                <div>
                    <a href="{{ route('home') }}" class="tz-auth-logo">
                        <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="{{ get_setting('website_name') }}">
                    </a>
                </div>
                <!-- Titles -->
                <h1>{{ rtrim(translate('Welcome Back !'), ' !') }}</h1>
                <p class="tz-auth-sub">{{ translate('Login to your account')}}</p>
                <!-- Login form -->
                                    <form class="form-default loginForm" id="user-login-form"  role="form" action="{{ route('login') }}" method="POST">
                                        @csrf
                                        
                                        <!-- Email or Phone -->
                                        @if (addon_is_activated('otp_system'))
                                            <div class="form-group phone-form-group mb-1">
                                                <label for="phone">{{  translate('Phone') }}</label>
                                                <input type="tel" phone-number id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ old('phone') }}" placeholder="" name="phone" autocomplete="off">
                                            </div>

                                            <input type="hidden" name="country_code" value="">
                                            
                                            <div class="form-group email-form-group mb-1 d-none">
                                                <label for="email">{{  translate('Email') }}</label>
                                                <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="username">
                                                @if ($errors->has('email'))
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $errors->first('email') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="form-group text-right">
                                                <button class="btn btn-link p-0 text-primary fs-12 fw-400" type="button" onclick="toggleEmailPhone(this)"><i>*{{ translate('Use Email Instead') }}</i></button>
                                            </div>
                                        @else
                                            <div class="form-group">
                                                <label for="email">{{  translate('Email') }}</label>
                                                <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="username">
                                                @if ($errors->has('email'))
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $errors->first('email') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                            
                                        <div class="password-login-block">
                                            <!-- password -->
                                            <div class="form-group">
                                                <label for="password">{{  translate('Password') }}</label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Password')}}" name="password" id="password" autocomplete="current-password">
                                                    <i class="password-toggle las la-eye"></i>
                                                </div>
                                            </div>
                                            <!-- Recaptcha -->
                                            @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)
                                                
                                                @if ($errors->has('g-recaptcha-response'))
                                                    <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
                                                        <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                                    </span>
                                                @endif
                                            @endif
                                            <div class="tz-auth-options">
                                                <!-- Remember Me -->
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                    <span>{{  translate('Remember Me') }}</span>
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                                <!-- Forgot password -->
                                                <div>
                                                    @if(get_setting('login_with_otp'))
                                                        <a href="javascript:void(0);" class="toggle-login-with-otp" onclick="toggleLoginPassOTP(this)">{{ translate('Login With OTP') }} / </a>
                                                    @endif
                                                    <a href="{{ route('password.request') }}">{{ translate('Forgot password?')}}</a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <button type="submit" class="btn tz-auth-btn tz-auth-submit submit-button">{{  translate('Login') }}</button>
                                    </form>

                                    <!-- DEMO MODE -->
                                    @if (env("DEMO_MODE") == "On")
                                        <div class="mt-4">
                                            <table class="table table-bordered mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td>{{ translate('Customer Account')}}</td>
                                                        <td>
                                                            <button class="btn btn-info btn-sm" onclick="autoFillCustomer()">{{ translate('Copy credentials') }}</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    <!-- Social Login -->
                                    @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1 || get_setting('apple_login') == 1)
                                        <p class="tz-auth-social-label">{{ translate('Or Login With')}}</p>
                                        <ul class="list-inline social colored text-center mb-0">
                                            @if (get_setting('facebook_login') == 1)
                                                <li class="list-inline-item">
                                                    <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook">
                                                        <i class="lab la-facebook-f"></i>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (get_setting('twitter_login') == 1)
                                                <li class="list-inline-item">
                                                    <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="x-twitter">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#ffffff" viewBox="0 0 16 16" class="mb-2 pb-1">
                                                            <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 
                                                            .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                                                        </svg>
                                                    </a>
                                                </li>
                                            @endif
                                            @if(get_setting('google_login') == 1)
                                                <li class="list-inline-item">
                                                    <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google">
                                                        <i class="lab la-google"></i>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (get_setting('apple_login') == 1)
                                                <li class="list-inline-item">
                                                    <a href="{{ route('social.login', ['provider' => 'apple']) }}"
                                                        class="apple">
                                                        <i class="lab la-apple"></i>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    @endif

                <!-- Register Now -->
                <div class="tz-auth-register">
                    <p>{{ translate('Dont have an account?')}}</p>
                    <a href="{{ route('user.registration') }}" class="tz-auth-btn tz-auth-register-btn">{{ translate('Register Now')}}</a>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('script')
    <script type="text/javascript">
        function autoFillCustomer(){
            $('#email').val('customer@example.com');
            $('#password').val('123456');
        }
    </script>
  @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>
        
        <script type="text/javascript">
                document.getElementById('user-login-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    grecaptcha.ready(function() {
                        grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {action: 'register'}).then(function(token) {
                            var input = document.createElement('input');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', 'g-recaptcha-response');
                            input.setAttribute('value', token);
                            e.target.appendChild(input);

                            var actionInput = document.createElement('input');
                            actionInput.setAttribute('type', 'hidden');
                            actionInput.setAttribute('name', 'recaptcha_action');
                            actionInput.setAttribute('value', 'recaptcha_customer_login');
                            e.target.appendChild(actionInput);
                            
                            e.target.submit();
                        });
                    });
                });
        </script>
    @endif

@endsection