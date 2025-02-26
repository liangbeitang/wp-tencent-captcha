=== Tencent Cloud Captcha ===
Contributors: your_name
Donate link: 
Tags: captcha, security, login, tencent cloud
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

为 WordPress 登录系统集成腾讯云验证码，有效防范暴力破解攻击。

== 描述 ==

本插件为 WordPress 登录系统提供以下安全增强功能：

- 在登录表单集成腾讯云验证码
- 自动验证用户身份
- 支持滑动验证和智能验证模式
- 与腾讯云安全体系深度整合
- 不修改默认登录流程样式
- 支持所有标准 WordPress 登录方式（包括 XML-RPC）

== 安装 ==

1. 通过 WordPress 后台插件目录搜索 "Tencent Cloud Captcha"
2. 点击 "立即安装"
3. 激活插件
4. 前往 ​**设置 → 腾讯云验证码**​ 配置应用信息

或手动安装：

1. 下载插件 ZIP 包
2. 通过 WordPress 后台 ​**插件 → 安装插件 → 上传插件**​ 安装
3. 激活插件
4. 配置应用信息

== 配置说明 ==

**必要步骤**：在使用前需完成以下配置：

1. 前往 [腾讯云验证码控制台](https://console.cloud.tencent.com/captcha)
2. 创建验证应用（选择「WEB 应用」类型）
3. 获取 `CaptchaAppId` 和 `AppSecretKey`
4. 在 WordPress 后台 ​**设置 → 腾讯云验证码**​ 填入配置信息

== 使用截图 ==

1. [登录表单验证码弹窗截图]
2. [插件设置界面截图]
3. [验证失败提示示例]

== 常见问题 ==

= 验证码不显示怎么办？ =
1. 检查浏览器控制台是否有 JavaScript 错误
2. 确认 `CaptchaAppId` 配置正确
3. 确保网站没有禁用外部 JavaScript 加载

= 如何测试验证功能？ =
1. 使用隐身窗口访问登录页面
2. 触发登录操作时验证码会自动显示
3. 可多次尝试失败验证测试容错机制

= 支持多语言吗？ =
验证码界面语言自动跟随浏览器语言设置，支持：
- 简体中文
- 繁体中文
- 英语
- 日语
- 韩语

= 如何自定义样式？ =
本插件使用腾讯云默认验证码样式，如需定制：
1. 登录腾讯云验证码控制台
2. 进入「验证管理」→「样式配置」
3. 根据指引进行可视化配置

== 更新日志 ==

= 1.0.0 =
* 初始版本发布
* 实现核心验证功能
* 支持 WordPress 5.0+
* 完成基础管理界面

== 高级功能 ==

通过钩子实现扩展功能：

`tencent_captcha_before_verify`
```php
// 在验证前执行自定义逻辑
add_action('tencent_captcha_before_verify', function($ticket, $randstr) {
    // 记录验证请求
    error_log('Captcha verification requested: ' . $ticket);
});
```

`tencent_captcha_after_failed`
```php
// 验证失败时的自定义处理
add_action('tencent_captcha_after_failed', function($error_code) {
    // 发送管理员通知
    wp_mail(get_option('admin_email'), '登录验证失败', '错误代码: ' . $error_code);
});
```

== 注意事项 ==

1. 需保持与腾讯云 API 的稳定连接
2. 建议在 HTTPS 环境下使用
3. 每日验证次数受腾讯云套餐限制
4. 高安全需求场景建议启用两步验证
5. 本插件不会收集任何用户数据

== 技术支持 ==

如需技术支持，请通过以下方式联系我们：

- 腾讯云官方支持：https://cloud.tencent.com/online-service
- WordPress 支持论坛：[插件讨论区](https://wordpress.org/support/plugin/your-plugin-slug)
- 紧急联系：security@yourdomain.com
