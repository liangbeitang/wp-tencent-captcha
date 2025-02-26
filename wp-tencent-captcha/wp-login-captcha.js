// 定义回调函数
function callback(res) {
    console.log('callback:', res);

    if (res.ret === 0) {
        // 复制结果至剪切板
        var str = '【randstr】->【' + res.randstr + '】      【ticket】->【' + res.ticket + '】';
        var ipt = document.createElement('input');
        ipt.value = str;
        document.body.appendChild(ipt);
        ipt.select();
        document.execCommand("Copy");
        document.body.removeChild(ipt);
        alert('1. 返回结果（randstr、ticket）已复制到剪切板，ctrl+v 查看。 2. 打开浏览器控制台，查看完整返回结果。');

        // 将验证码结果添加到登录表单中
        var form = document.getElementById('loginform');
        if (form) {
            var ticketInput = document.createElement('input');
            ticketInput.type = 'hidden';
            ticketInput.name = 'tencent_captcha_ticket';
            ticketInput.value = res.ticket;
            form.appendChild(ticketInput);

            var randstrInput = document.createElement('input');
            randstrInput.type = 'hidden';
            randstrInput.name = 'tencent_captcha_randstr';
            randstrInput.value = res.randstr;
            form.appendChild(randstrInput);

            // 提交登录表单
            form.submit();
        }
    }
}

// 定义验证码js加载错误处理函数
function loadErrorCallback() {
    var appid = wp_login_captcha_params.captcha_app_id;
    // 生成容灾票据或自行做其它处理
    var ticket = 'terror_1001_' + appid + '_' + Math.floor(new Date().getTime() / 1000);
    callback({
        ret: 0,
        randstr: '@' + Math.random().toString(36).substr(2),
        ticket: ticket,
        errorCode: 1001,
        errorMessage: 'jsload_error'
    });
}

// 定义验证码触发事件
window.onload = function () {
    var loginButton = document.getElementById('wp-submit');
    if (loginButton) {
        loginButton.onclick = function () {
            try {
                // 生成一个验证码对象
                var captcha = new TencentCaptcha(wp_login_captcha_params.captcha_app_id, callback, {});
                // 调用方法，显示验证码
                captcha.show();
            } catch (error) {
                // 加载异常，调用验证码js加载错误处理函数
                loadErrorCallback();
            }
            return false; // 阻止表单默认提交
        };
    }
};