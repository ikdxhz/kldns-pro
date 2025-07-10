/**
 * KLDNS-Pro JavaScript Library
 * Modified by ⓘⓚⓓⓧⓗⓩ team
 * Version: 1.0.2
 */

window.$_GET = function (name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.href) || [, ""])[1].replace(/\+/g, '%20')) || '';
};

// 由 ｉｋｄｘｈｚ 修改的 AJAX 工具
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
            // 确保layer已定义
            if (typeof layer !== 'undefined') {
                load = layer.load({
                    type: 2, shadeClose: false
                });
            } else {
                console.warn('Layer.js not loaded yet');
            }
        },
        error: function (request) {
            // 确保layer已定义
            if (typeof layer !== 'undefined') {
                if (load) layer.close(load);
                
                if (request.status === 419) {
                    layer.alert('页面已过期，请刷新页面！', {
                        closeBtn: 0
                    }, function (i) {
                        window.location.reload();
                    });
                } else {
                    layer.alert('网络出错了，请稍后再试！' + request.status + ' ' + request.statusText);
                }
            } else {
                console.error('请求错误:', request.status, request.statusText);
                alert('网络出错了，请稍后再试！' + request.status + ' ' + request.statusText);
            }
        },
        success: function (ret) {
            // 确保layer已定义
            if (typeof layer !== 'undefined' && load) {
                layer.close(load);
            }
        }
    });
};

// 为Vue提供全局方法
Vue.prototype.$post = window.$post;

// 1kd+xhz - 全局消息提示
Vue.prototype.$message = function (message, type) {
    if (typeof layer !== 'undefined') {
        if (type === 'success') {
            layer.msg(message, {icon: 1, time: 2000});
        } else if (type === 'error') {
            layer.msg(message, {icon: 2, time: 3000});
        } else if (type === 'warning') {
            layer.msg(message, {icon: 0, time: 2500});
        } else {
            layer.alert(message);
        }
    } else {
        // 回退到原生alert
        alert(message);
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