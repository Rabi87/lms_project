<?php
// دعم اللغات
$supported_langs = ['ar' => 'العربية', 'en' => 'English'];
$default_lang = 'ar';

// تحديد اللغة الحالية
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $default_lang;
}

$current_lang = $_SESSION['lang'];

// ملفات الترجمة
$translations = [
    'ar' => [
        'latest_additions' => 'أحدث الإضافات',
        'discounts' => 'عروض تخفيضات',
        'bestsellers' => 'الأكثر مبيعًا',
        'library' => 'المكتبة',
        'view_more' => 'عرض المزيد',
        'static_content' => 'إعلان أو محتوى ثابت',
        // ... أضف بقية الترجمات هنا
    ],
    'en' => [
        'latest_additions' => 'Latest Additions',
        'discounts' => 'Discount Offers',
        'bestsellers' => 'Bestsellers',
        'library' => 'Library',
        'view_more' => 'View More',
        'static_content' => 'Advertisement or Static Content',
        // ... أضف بقية الترجمات هنا
    ]
];

// دالة الترجمة
function __($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $key;
}