<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?=$pageTitle?> - propel.me.uk</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="<?=asset('css/bootstrap.min.css')?>">
        <link rel="stylesheet" href="<?=asset('css/bootstrap-theme.min.css')?>">
        <link rel="stylesheet" href="<?=asset('css/font-awesome.min.css')?>">
        <link rel="stylesheet" href="<?=asset('css/site.css')?>">
<? if (count($extraCss) > 0): ?>
<? foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?=$css?>">
<? endforeach; ?>
<? endif; ?>
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <script src="<?=asset('js/jquery-1.11.1.min.js')?>"></script>
        <script src="<?=asset('js/bootstrap.min.js')?>"></script>
    </head>
    <body>
        <div class="container">
            <!-- header -->
            <div class="row">
                <div class="col-md-6 col-sm-6" id="top-logo">
                    <a href="<?=url('/')?>">
                        <img src="<?=asset('img/site_logo.png')?>" alt="Propel.me.uk">
                    </a>
                </div>
                <div class="col-md-6 col-sm-6">
<? if ($signedIn === true): ?>
                    <div id="top-logout">
                        <i class="fa fa-user"></i> <?=$userName?>
                        <a href="<?=url('/sign-out')?>" class="btn btn-default btn-sm">
                            <i class="fa fa-sign-out"></i>
                            Sign out
                        </a>
                    </div>
<? endif; ?>
                </div>
            </div>
            <!-- navigation -->
            <div class="row">
                <div class="col-md-12">
                    <nav class="navbar navbar-default" role="navigation">
                        <div class="container-fluid">
                            <ul class="nav navbar-nav">
                                <li<?=$section == '' ? ' class="active"' : ''?>>
                                    <a href="<?=url()?>">Home</a>
                                </li>
                                <li<?=$section == 'jobs' ? ' class="active"' : ''?>>
                                    <a href="<?=url('jobs')?>">Jobs</a>
                                </li>
                                <li<?=$section == 'profile' ? ' class="active"' : ''?>>
                                    <a href="<?=url('profile')?>">Profile</a>
                                </li>
                                <li<?=$section == 'explore' ? ' class="active"' : ''?>>
                                    <a href="<?=url('explore')?>">Explore</a>
                                </li>
                                <li<?=$section == 'search' ? ' class="active"' : ''?>>
                                    <a href="<?=url('search')?>">Search</a>
                                </li>
                                <li<?=$section == 'recruit' ? ' class="active"' : ''?>>
                                    <a href="<?=url('recruit')?>">Recruit</a>
                                </li>
                            </ul>
                            <ul class="nav navbar-nav navbar-right">
<? if ((bool) $signedIn === false): ?>
                                <li<?=$section == 'sign-in' ? ' class="active"' : ''?>>
                                    <a href="<?=url('sign-in')?>">Sign in</a>
                                </li>
                                <li<?=$section == 'create-account' ? ' class="active"' : ''?>>
                                    <a href="<?=url('create-account')?>">Create account</a>
                                </li>
<? else: ?>
                                <li<?=$section == 'my-account' ? ' class="active"' : ''?>>
                                    <a href="<?=url('my-account')?>">My account</a>
                                </li>
<? endif; ?>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
            <!-- content -->
            <div class="row">
                <div class="col-md-12">
                    <div class="container-fluid" id="site-content">
<? if (strlen($alertSuccess) > 0): ?>
                        <div class="row"><div class="col-md-12"><div class="alert alert-success main_alert" role="alert"><?=$alertSuccess?></div></div></div>
<? elseif (strlen($alertInfo) > 0): ?>
                        <div class="row"><div class="col-md-12"><div class="alert alert-info main_alert" role="alert"><?=$alertInfo?></div></div></div>
<? elseif (strlen($alertWarning) > 0): ?>
                        <div class="row"><div class="col-md-12"><div class="alert alert-warning main_alert" role="alert"><?=$alertWarning?></div></div></div>
<? elseif (strlen($alertDanger) > 0): ?>
                        <div class="row"><div class="col-md-12"><div class="alert alert-danger main_alert" role="alert"><?=$alertDanger?></div></div></div>
<? endif; ?>
                        <?=$content?>
                    </div>
                </div>
            </div>
            <!-- footer -->
            <div class="row" id="site-footer">
                <div class="col-md-8 col-sm-8">
                    <p class="text-muted">
                        Copyright &copy; <?=date('Y')?> propel.me.uk. All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-4 col-sm-4" id="footer-links">
                    <ul>
                        <li>
                            <a href="<?=url('contact')?>">Contact</a>
                        </li>
                        <li>
                            <a href="<?=url('about')?>">About us</a>
                        </li>
                        <li>
                            <a href="<?=url('terms-and-conditions')?>">Terms and conditions</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
<? if (count($extraJs) > 0): ?>
<? foreach ($extraJs as $js): ?>
        <script src="<?=$js?>"></script>
<? endforeach; ?>
<? endif; ?>
    </body>
</html>