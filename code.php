<?php
// تابع پاکسازی دیتابیس
function wikiplus_clean_wp_database()
{
    global $wpdb;

    // حذف پیش‌نویس‌های خودکار
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");

    // حذف پست‌های زباله‌دان
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");

    // حذف نظرات زباله‌دان
    $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");

    // بهینه‌سازی جدول‌ها
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE {$table[0]}");
    }
}

// ایجاد کرون جاب برای هر 48 ساعت
function wikiplus_schedule_cleanup_task()
{
    if (!wp_next_scheduled('wikiplus_database_cleanup_cron')) {
        wp_schedule_event(time(), 'wikiplus_every_48_hours', 'wikiplus_database_cleanup_cron');
    }
}
add_action('wp', 'wikiplus_schedule_cleanup_task');

function wikiplus_add_48_hour_interval($schedules)
{
    $schedules['wikiplus_every_48_hours'] = array(
        'interval' => 48 * 60 * 60,
        'display'  => __('Every 48 Hours'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'wikiplus_add_48_hour_interval');

add_action('wikiplus_database_cleanup_cron', 'wikiplus_clean_wp_database');

function wikiplus_unschedule_cleanup_task()
{
    $timestamp = wp_next_scheduled('wikiplus_database_cleanup_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wikiplus_database_cleanup_cron');
    }
}
register_deactivation_hook(__FILE__, 'wikiplus_unschedule_cleanup_task');
