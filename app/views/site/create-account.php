<div class="row">
    <div class="col-md-12">
        <h1>
            Create a new account
        </h1>
        <hr />
    </div>
</div>
<div class="row">
    <div class="col-md-7">
        <p>
            Enter your profile details:
        </p>
        <form role="form" method="post" action="<?=url('create-account')?>">
            <div class="form-group w400">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" value="<?=$email?>">
            </div>
            <div class="form-group w400">
                <label for="first_name">First name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Your first name" value="<?=$first_name?>">
            </div>
            <div class="form-group w400">
                <label for="last_name">Last name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Your last name" value="<?=$last_name?>">
            </div>
            <div class="form-group w400">
                <label for="password1">Password</label>
                <input type="password" class="form-control" id="password1" name="password1" placeholder="Your password">
                <p class="help-block">5 characters minimum</p>
            </div>
            <div class="form-group w400">
                <label for="password2">Verify password</label>
                <input type="password" class="form-control" id="password2" name="password2" placeholder="Your password again">
            </div>
            <div class="form-group">
                I'm an
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="type[]" value="candidate"<?=($is_candidate === true ? ' checked="checked"' : '')?>> Job seeker
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="type[]" value="agent"<?=($is_agency === true ? ' checked="checked"' : '')?>> Agency
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="type[]" value="employer"<?=($is_employer === true ? ' checked="checked"' : '')?>> Employer
                </label>
            </div>
            
            <div class="form-group w400">
                <hr />
                <button type="submit" class="btn btn-primary">
                    Create my account
                </button>
            </div>
        </form>
        
    </div>
    <div class="col-md-5">
        <p>
            Sign up using your social accounts:
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