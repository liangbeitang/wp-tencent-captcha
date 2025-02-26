<?php
/*
Plugin Name: WP·腾讯云验证码插件
Plugin URI: https://www.liangbeitang.com/open-source-coding/wp-plugin/wp-tencent-captcha/
Description: 该插件用于实现 WordPress 登录功能集成腾讯云验证码功能。
Version: 1.0
Author: 梁北棠 <contact@liangbeitang.com>
Author URI: https://www.liangbeitang.com
License: GPL2
*/

// 添加设置页面到 WordPress 后台菜单
function wp_login_captcha_add_settings_page() {
    // 在 admin 菜单栏添加主菜单
    add_menu_page(
        '腾讯验证码设置',
        '腾讯验证码',
        'manage_options',
        'wp-login-captcha-settings',
        'wp_login_captcha_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'wp_login_captcha_add_settings_page');

// 渲染设置页面
function wp_login_captcha_settings_page() {
    // 保存设置
    if (isset($_POST['wp_login_captcha_save_settings'])) {
        $captcha_app_id = sanitize_text_field($_POST['wp_login_captcha_app_id']);
        $app_secret_key = sanitize_text_field($_POST['wp_login_captcha_app_secret_key']);

        update_option('wp_login_captcha_app_id', $captcha_app_id);
        update_option('wp_login_captcha_app_secret_key', $app_secret_key);

        echo '<div class="updated"><p>设置已保存。</p></div>';
    }

    $captcha_app_id = get_option('wp_login_captcha_app_id');
    $app_secret_key = get_option('wp_login_captcha_app_secret_key');

    ?>
    <div class="wrap">
        <h1>腾讯验证码设置</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wp_login_captcha_app_id">验证码应用 ID</label></th>
                    <td><input type="text" id="wp_login_captcha_app_id" name="wp_login_captcha_app_id" value="<?php echo esc_attr($captcha_app_id); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wp_login_captcha_app_secret_key">AppSecretKey</label></th>
                    <td><input type="text" id="wp_login_captcha_app_secret_key" name="wp_login_captcha_app_secret_key" value="<?php echo esc_attr($app_secret_key); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="wp_login_captcha_save_settings" id="submit" class="button button-primary" value="保存设置">
            </p>
        </form>
    </div>
    <?php
}

// 添加验证码脚本到登录页面
function wp_login_captcha_enqueue_scripts() {
    // 修正为正确的SDK地址
    wp_enqueue_script(
        'tencent-captcha',
        'https://turing.captcha.qcloud.com/TCaptcha.js',
        array(),
        null,
        true
    );

    // 自定义脚本
    wp_enqueue_script(
        'wp-login-captcha',
        plugins_url('wp-login-captcha.js', __FILE__),
        array('tencent-captcha', 'jquery'),
        '1.0',
        true
    );

    // 传递参数到前端
    wp_localize_script(
        'wp-login-captcha',
        'wp_login_captcha_params',
        array(
            'captcha_app_id' => get_option('wp_login_captcha_app_id'),
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}
add_action('login_enqueue_scripts', 'wp_login_captcha_enqueue_scripts');

// 优化后的验证函数
function wp_login_captcha_verify_login($user, $username, $password) {
    if (!isset($_POST['tencent_captcha_ticket']) || empty($_POST['tencent_captcha_ticket'])) {
        return new WP_Error('captcha_missing', __('请先完成验证码验证', 'wp-login-captcha'));
    }

    $result = wp_login_captcha_verify_ticket(
        $_POST['tencent_captcha_ticket'],
        $_POST['tencent_captcha_randstr'],
        get_option('wp_login_captcha_app_id'),
        get_option('wp_login_captcha_app_secret_key')
    );

    if (is_wp_error($result)) {
        return $result;
    }

    return $user;
}

// 使用官方推荐的签名方法
function wp_login_captcha_verify_ticket($ticket, $randstr, $appid, $appsecret) {
    $params = [
        'CaptchaType' => 9,
        'Ticket' => $ticket,
        'UserIp' => $_SERVER['REMOTE_ADDR'],
        'Randstr' => $randstr,
        'CaptchaAppId' => (int)$appid,
        'AppSecretKey' => $appsecret
    ];

    $url = 'https://ssl.captcha.qq.com/ticket/verify?' . http_build_query($params);
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return new WP_Error('captcha_error', __('验证服务暂时不可用，请稍后重试', 'wp-login-captcha'));
    }

    $result = json_decode($response['body'], true);
    
    if ($result['response'] != 1) {
        return new WP_Error('captcha_failed', __('验证码验证失败，错误代码：' . $result['error'], 'wp-login-captcha'));
    }

    return true;
}