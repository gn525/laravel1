<div class="row">
    <div class="col-md-12">
        <h1>
            Sign in
        </h1>
        <hr />
    </div>
</div>
<div class="row">
    <div class="col-md-7">
        <p>
            Enter your account data:
        </p>
        <form role="form" method="post" action="<?=url('sign-in')?>">
            <div class="form-group w400">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Your email address" value="<?=$email?>">
            </div>
            <div class="form-group w400">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Your password">
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="remember_me" value="1"> Keep me signed in
                </label>
            </div>
            
            <div class="form-group w400">
                <hr />
                <button type="submit" class="btn btn-primary">
                    Sign in
                </button>
                <div class="pull-right">
                    <a href="<?=url('recover-password')?>" class="btn btn-link">
                        I forgot my password
                    </a>
                </div>
            </div>
        </form>
        
    </div>
    <div class="col-md-5">
        <p>
            Connect using your social accounts:
        </p>
        <p>
            <a href="<?=url('connect/linkedin')?>" class="btn btn-default w150">
                <i class="fa fa-linkedin-square"></i>
                LinkedIn
            </a>
        </p>
        <p>
            <a href="<?=url('connect/facebook')?>" class="btn btn-default w150">
                <i class="fa fa-facebook-square"></i>
                Facebook
            </a>
        </p>
    </div>
</div>