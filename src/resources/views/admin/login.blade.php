<!doctype html>
<!-- 
  Created by: ⁱᵏᵈˣʰᶻ
  Version: 1.0.1
-->
<html lang="zh-CN">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="ikd_xhz">
    <link rel="icon" href="/favicon.ico">
    <title>后台登录 - {{ config('app.name') }}</title>
    <meta name="keywords" content="{{ config('sys.web.keywords') }}"/>
    <meta name="description" content="{{ config('sys.web.description') }}"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        /* ｉｋｄｘｈｚ */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4ecfb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 15px;
            margin: auto;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .login-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .login-card .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-align: center;
            font-size: 1.2rem;
            border: none;
        }
        .login-card .card-body {
            padding: 2rem;
        }
        .captcha-img {
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            height: 38px;
            width: auto;
            margin-top: 5px;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            padding: 0.5rem 2rem;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        /* ikdxHz */
        .form-control {
            height: auto;
            padding: 0.75rem 1rem;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #8e8e93;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="login-logo">
            <i class="fa fa-cloud mr-2"></i>{{ config('app.name') }}
        </div>
        <p class="text-muted">管理员后台登录</p>
    </div>
    <div id="content">
        <div class="card login-card">
            <div class="card-header">
                <i class="fa fa-user-shield mr-2"></i>管理员登录
            </div>
            <div class="card-body">
                <form id="form-login">
                    <div class="form-group">
                        <label for="username">
                            <i class="fa fa-user fa-fw"></i> 用户名
                        </label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="输入管理员账号">
                    </div>
                    <div class="form-group">
                        <label for="password">
                            <i class="fa fa-lock fa-fw"></i> 密码
                        </label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="输入管理员密码">
                    </div>
                    <div class="form-group">
                        <label for="code">
                            <i class="fa fa-shield-alt fa-fw"></i> 验证码
                        </label>
                        <div class="row">
                            <div class="col-7">
                                <input type="text" name="code" id="code-input" class="form-control" placeholder="输入验证码">
                            </div>
                            <div class="col-5">
                                <img title="点击刷新" src="/captcha" class="captcha-img" id="code" 
                                     onclick="this.src='/captcha?_='+Math.random();">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="remember" id="remember">
                            <label class="custom-control-label" for="remember">记住登录</label>
                        </div>
                    </div>
                    <div class="form-group mt-4 mb-0">
                        <button type="button" class="btn btn-primary btn-login btn-block" @click="login">
                            <i class="fa fa-sign-in-alt mr-2"></i>登 录
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="login-footer">
            <!-- 1kd+xhz -->
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} - 由<span class="font-weight-bold">𝓲𝓴𝓭𝔁𝓱𝔃</span>团队开发</p>
        </div>
    </div>
</div>

<!-- 先加载依赖库 -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- 加载layer.js -->
<script src="https://cdn.jsdelivr.net/npm/layer@3.5.1/dist/layer.min.js"></script>
<!-- 加载Vue -->
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js"></script>
<!-- 最后加载我们自己的脚本 -->
<script src="/js/main.js"></script>

<script>
    // ⓘⓚⓓⓧⓗⓩ
    new Vue({
        el: '#content',
        data: {},
        methods: {
            login: function () {
                var vm = this;
                this.$post('/admin/login', $("#form-login").serialize())
                    .then(function (data) {
                        $("#code").click();
                        if (data.status === 0) {
                            location.href = data.go ? data.go : "{{ request()->get('go','/') }}";
                        } else {
                            vm.$message(data.message, 'error');
                        }
                    });
            },
        },
        mounted: function () {
            var vm = this;
            // 自动聚焦用户名输入框
            document.getElementById('username').focus();
            // 回车键提交表单
            document.onkeyup = function (e) {
                var code = parseInt(e.charCode || e.keyCode);
                if (code === 13) {
                    vm.login();
                }
            }
        }
    });
</script>
</body>
</html>