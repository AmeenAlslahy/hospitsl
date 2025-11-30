<?php
// common_functions.php

/**
 * التحقق من صلاحيات المستخدم
 * @param array $requiredRoles الأدوار المطلوبة
 * @param string $redirect الصفحة التي سيتم التوجيه إليها في حالة الرفض
 */
function checkAccess($requiredRoles = [], $redirect = 'login.php') {
    global $auth;
    
    if (!$auth->isLoggedIn()) {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_PATH . "/$redirect");
        exit();
    }
    
    if (!empty($requiredRoles) && !in_array($auth->getUserRole(), $requiredRoles)) {
        $_SESSION['error'] = "غير مصرح لك بالوصول إلى هذه الصفحة";
        header("Location: " . BASE_PATH . "/" . getDashboardUrl($auth->getUserRole()));
        exit();
    }
}

/**
 * إنشاء عنصر واجهة مستخدم للتنبيهات
 * @param string $message نص الرسالة
 * @param string $type نوع التنبيه (success, error, warning, info)
 * @return string كود HTML للتنبيه
 */
function uiAlert($message, $type = 'success') {
    $icons = [
        'success' => 'check-circle',
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    
    return <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show">
        <i class="fas fa-{$icon} me-2"></i>
        {$message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
HTML;
}

/**
 * إنشاء بطاقة واجهة مستخدم متسقة
 * @param string $title عنوان البطاقة
 * @param string $content محتوى البطاقة
 * @param array $options خيارات إضافية
 * @return string كود HTML للبطاقة
 */
function uiCard($title, $content, $options = []) {
    $headerClass = $options['headerClass'] ?? 'bg-primary text-white';
    $footer = $options['footer'] ?? '';
    $footerClass = $options['footerClass'] ?? 'bg-white';
    
    $card = <<<HTML
    <div class="card shadow-sm mb-4">
        <div class="card-header {$headerClass}">
            <h3 class="h5 mb-0">{$title}</h3>
        </div>
        <div class="card-body">
            {$content}
        </div>
HTML;

    if (!empty($footer)) {
        $card .= <<<HTML
        <div class="card-footer {$footerClass}">
            {$footer}
        </div>
HTML;
    }

    $card .= '</div>';
    return $card;
}

/**
 * تنسيق التاريخ والوقت
 * @param string $dateTime التاريخ والوقت
 * @param string $format التنسيق المطلوب
 * @return string التاريخ المنسق
 */
function formatDate($dateTime, $format = 'd/m/Y h:i A') {
    if (empty($dateTime)) return 'غير محدد';
    
    try {
        $date = new DateTime($dateTime);
        return $date->format($format);
    } catch (Exception $e) {
        return 'تاريخ غير صالح';
    }
}

/**
 * إنشاء عنصر نموذج إدخال
 * @param array $config إعدادات الحقل
 * @return string كود HTML لعنصر الإدخال
 */
function formInput($config) {
    $type = $config['type'] ?? 'text';
    $id = $config['id'] ?? $config['name'];
    $label = $config['label'] ?? '';
    $value = $config['value'] ?? '';
    $required = isset($config['required']) && $config['required'] ? 'required' : '';
    $classes = $config['class'] ?? 'form-control';
    $attributes = $config['attributes'] ?? '';
    
    $input = <<<HTML
    <div class="mb-3">
        <label for="{$id}" class="form-label">{$label}</label>
        <input type="{$type}" class="{$classes}" id="{$id}" name="{$config['name']}" 
               value="{$value}" {$required} {$attributes}>
HTML;

    if (!empty($config['helpText'])) {
        $input .= <<<HTML
        <div class="form-text">{$config['helpText']}</div>
HTML;
    }

    $input .= '</div>';
    return $input;
}