<?php
/*
Plugin Name: 腾讯云验证码插件
Plugin URI: https://www.liangbeitang.com/open-source-coding/wp-plugin/wp-tencent-captcha/
Description: 该插件用于实现 WordPress 登录功能集成腾讯云验证码功能。
Version: 1.0
Author: 梁北棠 <contact@liangbeitang.com>
Author URI: https://www.liangbeitang.com
License: GPL2
*/

// 添加设置页面到 WordPress 后台菜单
function wp_tencent_captcha_add_settings_page() {
    add_menu_page(
        '腾讯验证码设置',
        '腾讯验证码',
        'manage_options',
        'wp-tencent-captcha-settings',
        'wp_tencent_captcha_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'wp_tencent_captcha_add_settings_page');

// 渲染设置页面
function wp_tencent_captcha_settings_page() {
    // 保存设置（添加nonce验证和输入处理）
    if (isset($_POST['wp_tencent_captcha_save_settings']) && check_admin_referer('wp_tencent_captcha_settings')) {
        $captcha_app_id = isset($_POST['wp_tencent_captcha_app_id']) 
            ? sanitize_text_field(wp_unslash($_POST['wp_tencent_captcha_app_id'])) 
            : '';
        
        $app_secret_key = isset($_POST['wp_tencent_captcha_app_secret_key']) 
            ? sanitize_text_field(wp_unslash($_POST['wp_tencent_captcha_app_secret_key'])) 
            : '';

        update_option('wp_tencent_captcha_app_id', $captcha_app_id);
        update_option('wp_tencent_captcha_app_secret_key', $app_secret_key);

        echo '<div class="updated"><p>' . esc_html__('设置已保存。', 'wp-tencent-captcha') . '</p></div>';
    }

    $captcha_app_id = get_option('wp_tencent_captcha_app_id');
    $app_secret_key = get_option('wp_tencent_captcha_app_secret_key');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('腾讯验证码设置', 'wp-tencent-captcha') ?></h1>
        <form method="post">
            <?php wp_nonce_field('wp_tencent_captcha_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wp_tencent_captcha_app_id"><?php esc_html_e('验证码应用 ID', 'wp-tencent-captcha') ?></label></th>
                    <td><input type="text" id="wp_tencent_captcha_app_id" name="wp_tencent_captcha_app_id" value="<?php echo esc_attr($captcha_app_id); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wp_tencent_captcha_app_secret_key"><?php esc_html_e('AppSecretKey', 'wp-tencent-captcha') ?></label></th>
                    <td><input type="text" id="wp_tencent_captcha_app_secret_key" name="wp_tencent_captcha_app_secret_key" value="<?php echo esc_attr($app_secret_key); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="wp_tencent_captcha_save_settings" id="submit" class="button button-primary" value="<?php esc_attr_e('保存设置', 'wp-tencent-captcha') ?>">
            </p>
        </form>
    </div>
    <?php
}

// 添加验证码脚本到登录页面
function wp_tencent_captcha_enqueue_scripts() {
    wp_enqueue_script(
        'tencent-captcha',
        'https://turing.captcha.qcloud.com/TCaptcha.js',
        array(),
        null,
        true
    );

    wp_enqueue_script(
        'wp-tencent-captcha',
        plugins_url('wp-tencent-captcha.js', __FILE__),
        array('tencent-captcha', 'jquery'),
        '1.0',
        true
    );

    wp_localize_script(
        'wp-tencent-captcha',
        'wp_tencent_captcha_params',
        array(
            'captcha_app_id' => esc_js(get_option('wp_tencent_captcha_app_id')),
            'ajaxurl' => esc_url(admin_url('admin-ajax.php'))
        )
    );
}
add_action('login_enqueue_scripts', 'wp_tencent_captcha_enqueue_scripts');

// 优化后的验证函数
function wp_tencent_captcha_verify_login($user, $username, $password) {
    // 验证输入数据
    $ticket = isset($_POST['tencent_captcha_ticket']) 
        ? sanitize_text_field(wp_unslash($_POST['tencent_captcha_ticket'])) 
        : '';
    
    $randstr = isset($_POST['tencent_captcha_randstr']) 
        ? sanitize_text_field(wp_unslash($_POST['tencent_captcha_randstr'])) 
        : '';

    if (empty($ticket)) {
        return new WP_Error('captcha_missing', esc_html__('请先完成验证码验证', 'wp-tencent-captcha'));
    }

    $result = wp_tencent_captcha_verify_ticket(
        $ticket,
        $randstr,
        get_option('wp_tencent_captcha_app_id'),
        get_option('wp_tencent_captcha_app_secret_key')
    );

    if (is_wp_error($result)) {
        return $result;
    }

    return $user;
}

// 使用官方推荐的签名方法
function wp_tencent_captcha_verify_ticket($ticket, $randstr, $appid, $appsecret) {
    // 验证IP地址
    $user_ip = isset($_SERVER['REMOTE_ADDR']) 
        ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) 
        : '';
    
    if (!filter_var($user_ip, FILTER_VALIDATE_IP)) {
        return new WP_Error('invalid_ip', esc_html__('无效的客户端IP地址', 'wp-tencent-captcha'));
    }

    $params = [
        'CaptchaType' => 9,
        'Ticket' => $ticket,
        'UserIp' => $user_ip,
        'Randstr' => $randstr,
        'CaptchaAppId' => (int)$appid,
        'AppSecretKey' => $appsecret
    ];

    $url = 'https://ssl.captcha.qq.com/ticket/verify?' . http_build_query($params);
    $response = wp_remote_get(esc_url_raw($url));

    if (is_wp_error($response)) {
        return new WP_Error('captcha_error', esc_html__('验证服务暂时不可用，请稍后重试', 'wp-tencent-captcha'));
    }

    $result = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!isset($result['response']) || $result['response'] != 1) {
        $error_code = isset($result['error']) ? $result['error'] : 'unknown';
        return new WP_Error(
            'captcha_failed', 
            sprintf(
                esc_html__('验证码验证失败，错误代码：%s', 'wp-tencent-captcha'),
                $error_code
            )
        );
    }

    return true;
}