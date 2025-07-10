@extends('home.layout.index')
@section('title', '记录列表')
@section('content')
    @if(config('sys.html_home'))
        <div class="alert alert-primary">
            {!! config('sys.html_home') !!}
        </div>
    @endif
    <div id="vue" class="pt-3 pt-sm-0">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fa fa-info-circle fa-2x mr-3"></i>
            <div>
                欢迎使用域名解析服务！您可以在这里管理您的所有DNS记录。如需帮助，请将鼠标悬停在 <i class="fa fa-question-circle"></i> 图标上查看提示。
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-list-ul mr-2"></i>记录列表</span>
                <a href="#modal-store" data-toggle="modal"
                   @click="storeInfo={did:domainList.length>0?domainList[0].did:0,line_id:0,type:'A'}"
                   class="btn btn-sm btn-primary"><i class="fa fa-plus mr-1"></i>添加记录</a>
            </div>
            <div class="card-header">
                <div class="form-inline">
                    <input type="text" disabled="disabled" class="d-none">
                    <div class="form-group">
                        <label class="mr-2">域名</label>
                        <select class="form-control" v-model="search.did">
                            <option value="0">所有域名</option>
                            <option v-for="(domain,i) in domainList" :value="domain.did">@{{ domain.domain }}</option>
                        </select>
                    </div>
                    <div class="form-group ml-1">
                        <label class="mr-2">类型</label>
                        <select class="form-control" v-model="search.type">
                            <option value="0">所有类型</option>
                            <option value="A">A</option>
                            <option value="CNAME">CNAME</option>
                        </select>
                    </div>
                    <div class="form-group ml-1">
                        <input type="text" placeholder="主机记录" class="form-control" v-model="search.name" title="例如: www">
                    </div>
                    <div class="form-group ml-1">
                        <input type="text" placeholder="记录值" class="form-control" v-model="search.value" title="例如: 1.2.3.4 或 example.com">
                    </div>
                    <a class="btn btn-info ml-1" @click="getList(1)"><i class="fa fa-search"></i> 搜索</a></div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>完整域名</th>
                            <th>类型</th>
                            <th>线路</th>
                            <th>记录值
                                <i class="fa fa-question-circle text-muted" title="点击记录值可以快速复制"></i>
                            </th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody v-cloak="">
                        <tr v-if="data.data && data.data.length === 0">
                            <td colspan="7" class="text-center text-muted">
                                <p class="my-3"><i class="fa fa-info-circle fa-3x"></i></p>
                                暂无记录，请点击右上角“添加记录”
                            </td>
                        </tr>
                        <tr v-for="(row,i) in data.data" :key="i">
                            <td>@{{ row.id }}</td>
                            <td>
                                <a :href="'http://'+row.name+'.'+(row.domain?row.domain.domain:'')" target="_blank" title="点击测试访问">
                                    @{{ row.name }}.@{{ row.domain?row.domain.domain:'' }}
                                </a>
                            </td>
                            <td><span class="badge badge-secondary">@{{ row.type }}</span></td>
                            <td>@{{ row.line }}</td>
                            <td @click="$copy(row.value)" style="cursor: pointer;" title="点击复制">@{{ row.value }}</td>
                            <td>@{{ $formatDate(row.created_at) }}</td>
                            <td>
                                <a href="#modal-store" class="btn btn-sm btn-info" data-toggle="modal"
                                   @click="storeInfo=Object.assign({},row)">
                                   <i class="fa fa-edit"></i> 修改
                                </a>
                                <a class="btn btn-sm btn-danger" @click="del(row.id)">
                                   <i class="fa fa-trash"></i> 删除
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer pb-0 text-center">
                @include('admin.layout.pagination')
            </div>
        </div>
        <div class="modal fade" id="modal-store">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <i class="fa fa-plus-circle mr-2"></i>记录添加/修改
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form-store">
                            <input type="hidden" name="action" value="recordStore">
                            <input type="hidden" name="id" :value="storeInfo.id" v-if="storeInfo.id">
                            <input type="hidden" name="did" :value="storeInfo.did" v-if="storeInfo.id">
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">
                                    主机记录
                                    <i class="fa fa-question-circle text-muted" title="填写域名前缀, 如www, mail, @ (表示根域名)"></i>
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <input type="text" name="name" class="form-control" v-model="storeInfo.name" placeholder="例如: www, mail, @">
                                        <div class="input-group-append">
                                            <select class="form-control" name="did" style="flex: none;width: 150px;"
                                                v-model="storeInfo.did" :disabled="storeInfo.id">
                                            <option v-for="(domain,i) in domainList" :value="domain.did">
                                                    .@{{ domain.domain }}
                                            </option>
                                        </select>
                                        </div>
                                    </div>
                                    <div class="input_tips" v-if="desc" v-html="desc"></div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">
                                    记录类型
                                     <i class="fa fa-question-circle text-muted" title="A记录指向IPv4地址，CNAME指向另一个域名"></i>
                                </label>
                                <div class="col-sm-10">
                                    <select class="form-control" name="type" v-model="storeInfo.type">
                                        <option value="A">A (将域名指向一个IPv4地址)</option>
                                        <option value="CNAME">CNAME (将域名指向另一个域名)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">
                                    记录值
                                    <i class="fa fa-question-circle text-muted" title="A记录请填写IP地址, CNAME请填写域名"></i>
                                </label>
                                <div class="col-sm-10">
                                    <input type="text" name="value" class="form-control" placeholder="A记录: 1.2.3.4 | CNAME: example.com"
                                           v-model="storeInfo.value">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">
                                    解析线路
                                    <i class="fa fa-question-circle text-muted" title="选择不同的线路，可以让不同网络的用户访问到不同的服务器"></i>
                                </label>
                                <div class="col-sm-10">
                                    <select class="form-control" name="line_id" v-model="storeInfo.line_id">
                                        <option v-for="(line,i) in getLineList()" :value="line.Id">
                                            @{{ line.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">
                                    所需积分
                                    <i class="fa fa-question-circle text-muted" title="添加此记录需要消耗的积分，由管理员设置"></i>
                                </label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" :value="getDomainPoint()+' 积分/条'" disabled>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>关闭</button>
                        <button type="button" class="btn btn-primary" @click="form('store')"><i class="fa fa-check mr-1"></i>确认</button>
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
                search: {
                    page: 1, did: 0, name: '', type: 0, value: ''
                },
                domainList: [],
                data: {},
                storeInfo: {
                    did: 0,
                    line_id: 0
                },
                selectDid: 0,
                desc: ''
            },
            methods: {
                getDomainPoint: function () {
                    var vm = this;
                    for (var i = 0; i < this.domainList.length; i++) {
                        if (this.domainList[i].did === this.storeInfo.did) {
                            vm.desc = this.domainList[i].desc;
                            return this.domainList[i].point;
                        }
                    }
                    return 0;
                },
                getLineList: function () {
                    for (var i = 0; i < this.domainList.length; i++) {
                        if (this.domainList[i].did === this.storeInfo.did) {
                            if (this.selectDid != this.storeInfo.did) {
                                this.storeInfo.line_id = this.domainList[i].line[0].Id;
                                this.selectDid = this.storeInfo.did
                            }
                            return this.domainList[i].line;
                        }
                    }
                    return [{Name: '默认', Id: 0}];
                },
                getList: function (page) {
                    var vm = this;
                    vm.search.page = typeof page === 'undefined' ? vm.search.page : page;
                    this.$post("/home", vm.search, {action: 'recordList'})
                        .then(function (data) {
                            if (data.status === 0) {
                                vm.data = data.data
                            } else {
                                vm.$message(data.message, 'error');
                            }
                        })
                },
                getDomainList: function (page) {
                    var vm = this;
                    this.$post("/home", vm.search, {action: 'domainList'})
                        .then(function (data) {
                            if (data.status === 0) {
                                vm.domainList = data.data
                            } else {
                                vm.$message(data.message, 'error');
                            }
                        })
                },
                form: function (id) {
                    var vm = this;
                    this.$post("/home", $("#form-" + id).serialize())
                        .then(function (data) {
                            if (data.status === 0) {
                                vm.getList();
                                $("#modal-" + id).modal('hide');
                                vm.$message(data.message, 'success');
                            } else {
                                vm.$message(data.message, 'error');
                            }
                        });
                },
                del: function (id) {
                    var vm = this;
                    layer.confirm('确认删除这条记录吗？此操作不可恢复。', {
                        btn: ['确认','取消'],
                        title: '请确认'
                    }, function(index){
                        layer.close(index);
                        vm.$post("/home", {action: 'recordDelete', id: id})
                        .then(function (data) {
                            if (data.status === 0) {
                                vm.getList();
                                vm.$message(data.message, 'success');
                            } else {
                                vm.$message(data.message, 'error');
                            }
                            });
                        });
                },
            },
            mounted: function () {
                this.getDomainList();
                this.getList();
            }
        });
    </script>
@endsection