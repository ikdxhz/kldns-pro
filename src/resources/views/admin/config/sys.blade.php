@extends('admin.layout.index')
@section('title', '系统配置')
@section('content')
    <div id="vue" class="pt-3 pt-sm-0 row">
        <div class="col-12 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fa fa-desktop mr-2"></i>站点设置
                </div>
                <div class="card-body">
                    <form id="form-web">
                        <input type="hidden" name="action" value="config">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">站点名称</label>
                            <div class="col-sm-9">
                                <input type="text" name="web[name]" class="form-control" placeholder="输入站点名称"
                                       value="{{ config('sys.web.name') }}" title="显示在浏览器标签和页面标题中">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">首页标题</label>
                            <div class="col-sm-9">
                                <input type="text" name="web[title]" class="form-control" placeholder="输入首页标题"
                                       value="{{ config('sys.web.title') }}" title="用于SEO优化，显示在搜索引擎结果中">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">网站关键词</label>
                            <div class="col-sm-9">
                                <input type="text" name="web[keywords]" class="form-control" placeholder="输入网站关键词"
                                       value="{{ config('sys.web.keywords') }}" title="用于SEO，多个关键词用逗号隔开">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">网站描述</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="web[description]" placeholder="输入网站描述" title="用于SEO，对网站的简短介绍"
                                >{{ config('sys.web.description') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">页头代码</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="html_header" placeholder="可在此处添加统计代码等" rows="5"
                                >{!! config('sys.html_header') !!}</textarea>
                                 <small class="form-text text-muted">此内容将插入到每个页面的 &lt;head&gt; 标签内，可用于添加统计代码、验证文件等。</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">用户公告</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="html_home" placeholder="将显示在用户中心首页的公告（支持HTML）" rows="5"
                                >{!! config('sys.html_home') !!}</textarea>
                                 <small class="form-text text-muted">此公告会显示在所有用户中心首页的顶部。</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">首页链接</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="index_urls" placeholder="输入首页顶部链接" rows="3"
                                >{!! config('sys.index_urls') !!}</textarea>
                                <div class="input_tips">
                                    格式：<code>链接名称|链接地址</code>，每行一条。例如：<br>
                                    <code>博客|https://example.com</code>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-right">
                    <a class="btn btn-primary" @click="form('web')"><i class="fa fa-save mr-1"></i>保存设置</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fa fa-user-cog mr-2"></i>用户配置
                </div>
                <div class="card-body">
                    <form id="form-user">
                        <input type="hidden" name="action" value="config">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">开启注册</label>
                            <div class="col-sm-9">
                                <select name="user[reg]" :value="{{ config('sys.user.reg',0) }}" class="form-control">
                                    <option value="0">关闭注册</option>
                                    <option value="1">开启注册</option>
                                </select>
                                <small class="form-text text-muted">关闭后，新用户将无法注册。</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">邮箱认证</label>
                            <div class="col-sm-9">
                                <select name="user[email]" :value="{{ config('sys.user.email',0) }}"
                                        class="form-control">
                                    <option value="0">不需要认证</option>
                                    <option value="1">需要认证</option>
                                </select>
                                <div class="input_tips">开启认证后，用户注册后需要通过邮件链接激活账户才能使用全部功能。请务必配置好下方的“邮箱配置”。</div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">注册赠送积分</label>
                            <div class="col-sm-9">
                                <input type="number" name="user[point]" class="form-control" placeholder="输入注册赠送积分"
                                       value="{{ config('sys.user.point',0) }}">
                                <small class="form-text text-muted">新用户注册后自动获得的积分数量，用于添加解析记录。</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-right">
                    <a class="btn btn-primary" @click="form('user')"><i class="fa fa-save mr-1"></i>保存设置</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-header">
                   <i class="fa fa-envelope mr-2"></i>邮箱配置
                </div>
                <div class="card-body">
                    <form id="form-mail">
                        <input type="hidden" name="action" value="config">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">SMTP服务器</label>
                            <div class="col-sm-9">
                                <input type="text" name="mail[host]" class="form-control" placeholder="例如: smtp.qq.com"
                                       value="{{ config('sys.mail.host','smtp.qq.com') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">SMTP端口</label>
                            <div class="col-sm-9">
                                <input type="text" name="mail[port]" class="form-control" placeholder="例如: 465"
                                       value="{{ config('sys.mail.port','465') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">加密类型</label>
                            <div class="col-sm-9">
                                <select name="mail[encryption]" :value="'{{ config('sys.mail.encryption','ssl') }}'"
                                        class="form-control">
                                    <option value="ssl">SSL</option>
                                    <option value="tls">TLS</option>
                                    <option value="">不加密</option>
                                </select>
                                <small class="form-text text-muted">请根据您的邮箱服务商要求选择。</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">邮箱账号</label>
                            <div class="col-sm-9">
                                <input type="text" name="mail[username]" class="form-control" placeholder="用于发送邮件的邮箱地址"
                                       value="{{ config('sys.mail.username') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">邮箱密码/授权码</label>
                            <div class="col-sm-9">
                                <input type="text" name="mail[password]" class="form-control" placeholder="输入邮箱密码或专用授权码"
                                       value="{{ config('sys.mail.password') }}">
                                <div class="input_tips"><b>注意:</b> 这里通常不是您的邮箱登录密码，而是需要在邮箱服务商后台单独申请的“SMTP服务授权码”。</div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">发送测试</label>
                            <div class="col-sm-9">
                                <input type="text" name="mail[test]" class="form-control" placeholder="输入一个用于接收测试邮件的地址"
                                       value="{{ config('sys.mail.test','123456@qq.com') }}">
                                <div class="input_tips">点击保存后，系统会自动向此地址发送一封测试邮件以验证配置是否正确。</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-right">
                    <a class="btn btn-primary" @click="form('mail')"><i class="fa fa-save mr-1"></i>保存并测试</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fa fa-globe-asia mr-2"></i>域名配置
                </div>
                <div class="card-body">
                    <form id="form-domain">
                        <input type="hidden" name="action" value="config">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">保留前缀</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="reserve_domain_name" placeholder="输入你想保留的域名前缀"
                                          rows="5"
                                >{{ config('sys.reserve_domain_name') }}</textarea>
                                <div class="input_tips">多个用英文逗号<code>,</code>隔开。例如：<code>www,m,mail,admin</code>。被保留的前缀将无法被用户注册。</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-right">
                    <a class="btn btn-primary" @click="form('domain')"><i class="fa fa-save mr-1"></i>保存设置</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('foot')
    <script>
        new Vue({
            el: '#vue',
            data: {},
            methods: {
                form: function (id) {
                    var vm = this;
                    this.$post("/admin/config", $("#form-" + id).serialize())
                        .then(function (data) {
                            if (data.status === 0) {
                                vm.$message(data.message, 'success');
                            } else {
                                vm.$message(data.message, 'error');
                            }
                        });
                },
            },
            mounted: function () {
            }
        });
    </script>
@endsection