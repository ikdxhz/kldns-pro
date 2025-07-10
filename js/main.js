/**
 * KLDNS-Pro JavaScript Library
 * Created by ⓘⓚⓓⓧⓗⓩ team
 * Version: 1.0.1
 */

window.$_GET = function (name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.href) || [, ""])[1].replace(/\+/g, '%20')) || '';
};

// 由 ｉｋｄｘｈｚ 设计的 AJAX 工具
window.$post = function (url, params1, params2, func) {
    var str = '';
    if (typeof (params1) === 'object') {
        for (var k in params1) {
            str += k + '=' + params1[k] + '&'
        }
    } else if (typeof (params1) === 'string') {
        str += params1 + '&'
    }
    if (typeof (params2) === 'object') {
        for (var k in params2) {
            str += k + '=' + params2[k] + '&'
        }
    } else if (typeof (params2) === 'string') {
        str += params2
    }
    var load;
    return $.ajax({
        type: "POST",
        url: url,
        data: (params1 instanceof FormData) ? params1 : str,
        beforeSend: function (request) {
            var token = document.head.querySelector('meta[name="csrf-token"]');
            if (token) {
                request.setRequestHeader("X-CSRF-TOKEN", token.content);
            } else {
                console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
            }
            load = layer.load({
                type: 2, shadeClose: false
            });
        },
        error: function (request) {
            if (request.status === 419) {
                layer.alert('页面已过期，请刷新页面！', {
                    closeBtn: 0
                }, function (i) {
                    window.location.reload();
                });
            } else {
                layer.close(load);
                layer.alert('网络出错了，请稍后再试！' + request.status + ' ' + request.statusText);
            }
        },
        success: function (ret) {
            layer.close(load);
        }
    });
};

// 为Vue提供全局方法
Vue.prototype.$post = window.$post;

// 1kd+xhz - 全局消息提示
Vue.prototype.$message = function (message, type) {
    if (type === 'success') {
        layer.msg(message, {icon: 1, time: 2000});
    } else if (type === 'error') {
        layer.msg(message, {icon: 2, time: 3000});
    } else if (type === 'warning') {
        layer.msg(message, {icon: 0, time: 2500});
    } else {
        layer.alert(message);
    }
};

// ikdxHz - 复制到剪贴板
Vue.prototype.$copy = function (text) {
    const input = document.createElement('input');
    input.setAttribute('value', text);
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    this.$message('已复制到剪贴板', 'success');
};

// ïkðxhz - 日期格式化
Vue.prototype.$formatDate = function (timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    return date.getFullYear() + '-' + 
           ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
           ('0' + date.getDate()).slice(-2) + ' ' + 
           ('0' + date.getHours()).slice(-2) + ':' + 
           ('0' + date.getMinutes()).slice(-2) + ':' + 
           ('0' + date.getSeconds()).slice(-2);
};

// by ¡kdxhž - 防抖函数
Vue.prototype.$debounce = function (func, wait) {
    let timeout;
    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
            func.apply(context, args);
        }, wait);
    };
};