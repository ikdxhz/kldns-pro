<!doctype html>
<!-- 
  Modified by: ⁱᵏᵈˣʰᶻ
  Version: 1.0.2
-->
<html lang="zh-CN">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('sys.web.name') }} - 快速管理您的DNS记录">
    <meta name="author" content="ikd_xhz">
    <link rel="icon" href="/favicon.ico">
    <title>@yield('title') - {{ config('sys.web.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <!-- 𝓲𝓴𝓭𝔁𝓱𝔃 modified -->
    @yield('head')
</head>
<body>
<header class="navbar navbar-expand flex-md-row bd-navbar">
    <div class="navbar-nav-scroll">
        <ul class="navbar-nav bd-navbar-nav flex-row">
            <li class="nav-item d-sm-none" id="menu">
                <a class="nav-link nav-menu" href="#">
                    <i class="fa fa-bars fa-lg"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-logo-name" href="/">
                    <i class="fa fa-cloud mr-2"></i>{{ config('sys.web.name') }}
                </a>
            </li>
        </ul>
    </div>

    <ul class="navbar-nav flex-row ml-auto d-md-flex">
        <li class="nav-item dropdown">
            <a class="nav-item nav-link dropdown-toggle" href="#" id="user_btns" data-toggle="dropdown">
                <i class="fa fa-user-circle mr-1"></i>{{ auth()->user()->username }}
                <span class="d-none d-sm-inline badge badge-primary ml-1">
                    {{ auth()->user()->group ? auth()->user()->group->name : '' }}
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="user_btns">
                <!-- !кdxんz -->
                <div class="dropdown-header small text-muted">用户操作</div>
                <a class="dropdown-item" href="/home/profile">
                    <i class="fa fa-key fa-fw mr-1"></i>修改密码
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="/logout" onclick="return confirm('确认退出登录？');">
                    <i class="fa fa-sign-out-alt fa-fw mr-1"></i>退出登录
                </a>
            </div>
        </li>
    </ul>
</header>
<div class="container-fluid">
    <div class="row flex-xl-nowrap">
        <div class="col-12 col-md-3 col-xl-2 bd-sidebar">
            <!-- ｉｋｄｘｈｚ -->
            <div class="menu-item">
                <a href="/home" class="menu-link">
                    <i class="fa fa-globe"></i> 解析记录
                </a>
            </div>
            <div class="menu-item">
                <a href="/home/point" class="menu-link">
                    <i class="fa fa-cube"></i> 积分明细
                </a>
            </div>
            <div class="p-3 d-none d-md-block">
                <div class="text-center small text-muted mt-4">
                    <p>© {{ date('Y') }} {{ config('sys.web.name') }}</p>
                    <p class="mb-0">由<span class="font-weight-bold">��𝕜𝕕𝕩𝕙𝕫</span>团队修改</p>
                </div>
            </div>
        </div>
        <main class="col-12 col-md-9 col-xl-10 py-md-3 pl-md-5 bd-content">
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/layer@3.5.1/dist/layer.js"></script>
<script src="/js/main.js"></script>
<script>
    // by ¡kdxhž
    var showMenu = false;
    $(document).ready(function() {
        $("#menu").click(function () {
            if (showMenu) {
                $(".bd-sidebar").removeClass('openMenu');
                $(".bd-content").removeClass('moveRight');
                $(".bd-content").addClass('moveAnimation');
                showMenu = false;
            } else {
                $(".bd-content").removeClass('moveAnimation');
                $(".bd-sidebar").addClass('openMenu');
                $(".bd-content").addClass('moveRight');
                showMenu = true;
            }
        });
        
        // 高亮当前活动菜单
        $(".bd-sidebar a").each(function () {
            var pathname = window.location.pathname;
            var href = $(this).attr('href');
            if (href == pathname) {
                $(this).parent().addClass('active');
            }
        });
    });
</script>
@yield('foot')
</html>