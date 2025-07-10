<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>程序安装 - {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/css/all.min.css" rel="stylesheet">
    <!-- 开发者: ｉｋｄｘｈｚ -->
</head>
<body>
<div id="vue">
    <div class="col-12 col-md-4 offset-md-4 mt-3 mt-sm-5">
        <!-- 已安装提示：检查mysql.php配置文件和数据库连接 -->
        @if(file_exists(base_path('src/config/mysql.php')))
            <div class="card mb-3">
                <div class="card-header text-white bg-info ">安装提示</div>
                <div class="card-body text-center">
                    <p class="text-danger">对不起，你已完成安装！如需重新安装，请删除以下文件：</p>
                    <p class="text-danger font-weight-bold">根目录/src/config/mysql.php</p>
                    <hr>
                    <p>如果您确认需要重新安装，请按照以下步骤操作：</p>
                    <ol class="text-left">
                        <li>备份您的数据（如有需要）</li>
                        <li>删除 src/config/mysql.php 文件</li>
                        <li>刷新本页面开始安装</li>
                    </ol>
                    <div class="mt-3">
                        <a href="/" class="btn btn-primary">返回首页</a>
                        <a href="/admin" class="btn btn-info">进入后台</a>
                        <!-- 清除可能导致循环重定向的cookie -->
                        <script>
                            document.cookie = "install_check=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        </script>
                    </div>
                </div>
            </div>
        @else
            <div class="card shadow">
                <div class="card-header text-white bg-primary">
                    <span class="float-left">安装向导 v3.1.3</span>
                    <span class="float-right">Powered by 𝓲𝓴𝓭𝔁𝓱𝔃</span>
                </div>
                <div class="card-body">
                    <!-- 步骤一：环境检测 -->
                    <div v-if="step===1">
                        <h5>环境检测</h5>
                        <p>请确保您的服务器满足以下要求：</p>
                        <div class="list-group">
                            @foreach($support as $item)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    {{$item['name']}}
                                    @if($item['support'])
                                        <span class="badge badge-success"><i class="fa fa-check"></i></span>
                                    @else
                                        <span class="badge badge-danger"><i class="fa fa-times"></i></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 text-center">
                            <!-- 由 !кdxんz 提供技术支持 -->
                            <button class="btn btn-outline-primary" v-if="supportAll" @click="step=2">下一步</button>
                        </div>
                    </div>

                    <!-- 步骤二：数据库配置 -->
                    <div v-if="step===2">
                        <h5>数据库配置</h5>
                        <form @submit.prevent="install">
                            <div class="form-group">
                                <label>数据库主机</label>
                                <input type="text" class="form-control" v-model="mysql.host">
                            </div>
                            <div class="form-group">
                                <label>数据库端口</label>
                                <input type="number" class="form-control" v-model="mysql.port">
                            </div>
                            <div class="form-group">
                                <label>数据库名称</label>
                                <input type="text" class="form-control" v-model="mysql.database">
                            </div>
                            <div class="form-group">
                                <label>数据库用户名</label>
                                <input type="text" class="form-control" v-model="mysql.username">
                            </div>
                            <div class="form-group">
                                <label>数据库密码</label>
                                <input type="password" class="form-control" v-model="mysql.password">
                            </div>
                            <div class="form-group">
                                <label>数据表前缀</label>
                                <input type="text" class="form-control" v-model="mysql.prefix" placeholder="kldns_">
                                <small class="text-muted">推荐使用默认前缀，避免表名冲突<!-- 开发者: ikdxHz --></small>
                            </div>

                            <!-- 安装按钮和返回按钮 -->
                            <div class="mt-3 text-center">
                                <button type="submit" class="btn btn-primary" :disabled="installing">
                                    <span v-if="installing"><i class="fa fa-spinner fa-spin"></i> 安装中...</span>
                                    <span v-else>开始安装</span>
                                </button>
                                <button type="button" class="btn btn-secondary ml-2" @click="step=1">返回</button>
                            </div>
                        </form>
                    </div>

                    <!-- 步骤三：安装结果 -->
                    <div v-if="step===3">
                        <div v-if="installResult.status===0">
                            <h5 class="text-success"><i class="fa fa-check-circle"></i> 安装成功</h5>
                            <p>恭喜您，KLDNS系统已安装完成！</p>
                            <div class="alert alert-info">
                                <strong>安装结果：</strong>
                                <p>成功执行SQL：@{{installResult.data.success}}条</p>
                                <p>失败执行SQL：@{{installResult.data.error}}条</p>
                            </div>
                            <div class="text-center">
                                <a href="/" class="btn btn-outline-primary">访问首页</a>
                                <a href="/admin/login" class="btn btn-outline-info ml-2">管理后台</a>
                            </div>
                            <!-- 安装完成后清除安装检查cookie -->
                            <script>
                                document.cookie = "install_check=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                            </script>
                        </div>
                        <div v-else>
                            <h5 class="text-danger"><i class="fa fa-times-circle"></i> 安装失败</h5>
                            <div class="alert alert-danger">
                                @{{installResult.message}}
                            </div>
                            <div class="text-center">
                                <button class="btn btn-outline-primary" @click="step=2">返回重试</button>
                            </div>
                        </div>
                        <!-- 由 ⁱᵏᵈˣʰᶻ 开发维护 -->
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>KLDNS Pro v3.1.3 &copy; 2023 All Rights Reserved.</small>
                </div>
            </div>
        @endif
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js"></script>
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
    
    new Vue({
        el: '#vue',
        data: {
            step: 1,
            supportAll: @json(collect($support)->every(function($item){return $item['support'];})),
            mysql: {
                host: 'localhost',
                port: 3306,
                database: '',
                username: '',
                password: '',
                prefix: 'kldns_'
            },
            installing: false,
            installResult: {
                status: 1,
                message: '',
                data: {
                    success: 0,
                    error: 0,
                    msg: []
                }
            }
        },
        methods: {
            // 安装系统 - 由 ¡kdxhž 实现
            install: function () {
                const self = this;
                self.installing = true;
                
                // 验证必填字段
                if (!self.mysql.host || !self.mysql.port || !self.mysql.database || !self.mysql.username || !self.mysql.prefix) {
                    alert('请填写完整的数据库信息');
                    self.installing = false;
                    return;
                }
                
                axios.post('/install', {
                    action: 'mysql',
                    host: self.mysql.host,
                    port: self.mysql.port,
                    database: self.mysql.database,
                    username: self.mysql.username,
                    password: self.mysql.password,
                    prefix: self.mysql.prefix
                }).then(function (response) {
                    self.installing = false;
                    self.installResult = response.data;
                    self.step = 3;
                }).catch(function (error) {
                    self.installing = false;
                    self.installResult = {
                        status: 1,
                        message: '安装过程中发生错误: ' + (error.response ? error.response.data.message : error.message),
                        data: {success: 0, error: 0, msg: []}
                    };
                    self.step = 3;
                });
            }
        }
    });
</script>
</body>
</html>