<style type="text/css">
	.color-twitter{color:#00aced;}
	.color-facebook{color:#00539f;}
	.color-linkedin{color:#0176b5;}

    .set-padding{
        padding-top: 70px;
    }
</style>
    
<div class="set-padding">

    <nav class="navbar navbar-default navbar-fixed-top">
    	<div class="container">
    		<div class="navbar-header">
    			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
    				<span class="sr-only">{{ Lang::get('toggleNav') }}</span>
    				<span class="icon-bar"></span>
    				<span class="icon-bar"></span>
    				<span class="icon-bar"></span>
    			</button>					
    			<a href="/home" class="navbar-brand"><img src="/logo.png" style="width: 150px;"></a> 
    		</div>

    		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
    			<ul class="nav navbar-nav"> 
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
    			</ul>
    			<ul class="nav navbar-nav navbar-right">

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

</div>