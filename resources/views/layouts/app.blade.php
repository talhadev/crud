<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- font awesome -->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

<style type="text/css">
    .color-twitter{color:#00aced;}
    .color-facebook{color:#00539f;}
    .color-linkedin{color:#0176b5;}
</style>

</head>
<body>
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/home') }}">
                        <img src="/logo.png" style="width: 150px;">
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
                        @if (Auth::check())
                            <li>{!! HTML::link(url('/home'), Lang::get('titles.home')) !!}</li>

                            @if( Auth::user()->isadmin == 1 )
                                <li>{!! HTML::link(url('/store'), Lang::get('titles.store')) !!}</li>
                                <li>{!! HTML::link(url('/actions'), Lang::get('titles.actions')) !!}</li>
                            @endif

                            <li class="dropdown">
                                <a href="/order/success" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Lang::get('titles.order') }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="/order/success">
                                            {{ Lang::get('titles.ordersuccess') }}
                                        </a>
                                        <a href="/order/cancelled">
                                            {{ Lang::get('titles.ordercancelled') }}
                                        </a>
                                        <a href="/order/failure">
                                            {{ Lang::get('titles.orderfailure') }}
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li><a href="https://www.twitter.com/"><i class="fa fa-twitter color-twitter"></i></a></li>
                            <li><a href="https://www.facebook.com/"><i class="fa fa-facebook color-facebook"></i></a></li>
                            <li><a href="https://www.linkedin.com/"><i class="fa fa-linkedin color-linkedin"></i></a></li>
                        @endif
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
