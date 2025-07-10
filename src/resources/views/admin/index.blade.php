@extends('admin.layout.index')
@section('title', '后台首页')
@section('content')
    <div id="vue" class="pt-3 pt-sm-0">
        <div class="alert alert-info" role="alert">
            <i class="fa fa-info-circle mr-2"></i>欢迎回来，<b>{{ auth('admin')->user()->username }}</b>！在这里您可以概览系统状态。
        </div>

        <!-- 统计信息 -->
        <div class="row">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fa fa-users mr-2"></i>总用户数</h5>
                        <p class="card-text display-4">@{{ stats.users }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fa fa-globe-asia mr-2"></i>域名总数</h5>
                        <p class="card-text display-4">@{{ stats.domains }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fa fa-list-ul mr-2"></i>记录总数</h5>
                        <p class="card-text display-4">@{{ stats.records }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fa fa-user-plus mr-2"></i>今日新增用户</h5>
                        <p class="card-text display-4">@{{ stats.today_users }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fa fa-book-reader mr-2"></i>添加域名快速入门
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <span class="badge badge-primary mr-2">1</span>
                        点击左侧菜单栏的<a href="/admin/config/dns" class="font-weight-bold">接口配置</a>，先对您使用的域名解析平台（如Cloudflare, 阿里云等）的接口进行配置。
                        <small class="form-text text-muted mt-1">
                            <i class="fa fa-exclamation-circle"></i> 这是最重要的一步，系统需要通过这些接口来管理您的域名。
                        </small>
                    </div>
                    <div class="list-group-item">
                        <span class="badge badge-primary mr-2">2</span>
                        点击左侧菜单栏的<a href="/admin/domain/list" class="font-weight-bold">域名列表</a>，然后点击“添加”按钮。
                    </div>
                    <div class="list-group-item">
                        <span class="badge badge-primary mr-2">3</span>
                        在添加页面，选择您刚配置好的解析平台，点击“获取”按钮，系统会自动拉取您在该平台下的所有域名。
                    </div>
                     <div class="list-group-item">
                        <span class="badge badge-primary mr-2">4</span>
                        选择您想提供给用户使用的域名，设置好用户添加记录所需的积分等信息，然后保存即可。
                        <small class="form-text text-muted mt-1">
                            <i class="fa fa-exclamation-circle"></i> 您还可以设置用户组，并为不同用户组的域名设置不同的解析线路和价格。
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('foot')
<script>
    new Vue({
        el: '#vue',
        data: {
            stats: {
                users: 0,
                domains: 0,
                records: 0,
                today_users: 0
            }
        },
        methods: {
            getStats: function() {
                var vm = this;
                this.$post('/admin', {action: 'stats'}) // 假设后台有这个接口
                    .then(function(data) {
                        if(data.status === 0) {
                            vm.stats = data.data;
                        }
                    }).catch(function() {
                        // 即使接口不存在或失败，也不报错
                        console.warn('获取统计信息失败，请检查后台接口。');
                    });
            }
        },
        mounted: function() {
            this.getStats();
        }
    });
</script>
@endsection