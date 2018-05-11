<style type="text/css">
      .color-twitter{color:#00aced;}
      .color-facebook{color:#00539f;}
      .color-linkedin{color:#0176b5;}
</style>

<nav class="navbar navbar-inverse navbar-static-top">
    <div class="container">
        <div class="navbar-header"> 
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">{{ Lang::get('toggleNav') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
              </button>
              <a href="/home" class="navbar-brand"><img src="{{URL::asset('technify_logo.png')}}"></a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav" class="pull-right">
                <li>
                    <a href="https://www.twitter.com/"><i class="fa fa-twitter color-twitter"></i></a>
                </li>
                <li>
                    <a href="https://www.facebook.com/"><i class="fa fa-facebook color-facebook"></i></a>
                </li>
                <li>
                    <a href="https://www.linkedin.com/"><i class="fa fa-linkedin color-linkedin"></i></a>
                </li>
            </ul>

        </div>
    </div>
</nav>