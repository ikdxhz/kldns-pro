<!-- 
  设计者: 𝓲𝓴𝓭𝔁𝓱𝔃
  Version: 1.0.1
-->
<nav v-cloak="" aria-label="分页导航" class="mb-3">
    <ul class="pagination justify-content-center" v-if="data.last_page>1">
        <!-- ｉｋｄｘｈｚ -->
        <li class="page-item" :class="{disabled: data.current_page <= 1}">
            <a class="page-link" href="javascript:void(0)" @click="data.current_page > 1 && getList(data.current_page - 1)" aria-label="上一页">
                <i class="fa fa-angle-left"></i>
                <span class="sr-only">上一页</span>
            </a>
        </li>
        
        <li class="page-item" @click="getList(1)" v-if="data.current_page>1" :class="{active: data.current_page === 1}">
            <a class="page-link" href="javascript:void(0)">1</a>
        </li>
        
        <li class="page-item disabled" v-if="data.current_page-3>1">
            <a class="page-link" href="javascript:void(0)">…</a>
        </li>
        
        <li class="page-item" v-if="data.current_page-2>1" @click="getList(data.current_page-2)">
            <a class="page-link" href="javascript:void(0)">@{{ data.current_page-2 }}</a>
        </li>
        
        <li class="page-item" v-if="(data.current_page-1)>1" @click="getList(data.current_page-1)">
            <a class="page-link" href="javascript:void(0)">@{{ data.current_page-1 }}</a>
        </li>
        
        <li class="page-item active" v-if="data.current_page !== 1 && data.current_page !== data.last_page">
            <a class="page-link" href="javascript:void(0)">@{{ data.current_page }}</a>
        </li>
        
        <li class="page-item" v-if="data.current_page+1<data.last_page" @click="getList(data.current_page+1)">
            <a class="page-link" href="javascript:void(0)">@{{ data.current_page+1 }}</a>
        </li>
        
        <li class="page-item" v-if="data.current_page+2<data.last_page" @click="getList(data.current_page+2)">
            <a class="page-link" href="javascript:void(0)">@{{ data.current_page+2 }}</a>
        </li>
        
        <li class="page-item disabled" v-if="data.current_page+3<data.last_page">
            <a class="page-link" href="javascript:void(0)">…</a>
        </li>
        
        <li class="page-item" v-if="data.last_page>data.current_page" @click="getList(data.last_page)" :class="{active: data.current_page === data.last_page}">
            <a class="page-link" href="javascript:void(0)">@{{ data.last_page }}</a>
        </li>
        
        <!-- !кdxんz -->
        <li class="page-item" :class="{disabled: data.current_page >= data.last_page}">
            <a class="page-link" href="javascript:void(0)" @click="data.current_page < data.last_page && getList(data.current_page + 1)" aria-label="下一页">
                <i class="fa fa-angle-right"></i>
                <span class="sr-only">下一页</span>
            </a>
        </li>
    </ul>
    
    <div class="text-center text-muted small" v-if="data.last_page>1">
        共 @{{ data.total }} 条记录，当前 @{{ data.current_page }}/@{{ data.last_page }} 页
        <span class="ml-2">
            跳转到 
            <input type="number" min="1" :max="data.last_page" v-model="jumpPage" class="jump-page-input" 
                   @keyup.enter="jumpToPage" style="width:50px;display:inline-block;text-align:center;">
            页
            <button class="btn btn-sm btn-outline-primary ml-1" @click="jumpToPage">跳转</button>
        </span>
    </div>
</nav>

<script>
    // by ¡kdxhž
    if (typeof this.jumpPage === 'undefined') {
        Vue.set(this, 'jumpPage', 1);
    }
    
    if (typeof this.jumpToPage === 'undefined') {
        this.jumpToPage = function() {
            let page = parseInt(this.jumpPage);
            if (page >= 1 && page <= this.data.last_page) {
                this.getList(page);
            } else {
                this.$message('请输入有效的页码', 'warning');
            }
        }
    }
</script>