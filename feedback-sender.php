<?php
/**
 * Plugin Name: Feedback-sender
 * Plugin URI: https://github.com/Igor-kor/feedback-sender
 * Description: Отправка обратной связи
 * Version: 0.2.1
 * Author: Шарангия Игорь
 * Author URI: https://vk.com/id117766113
 * GitHub Plugin URI: https://github.com/Igor-kor/feedback-sender
 */

// Для того, чтобы этот файл не могли подключить вне WordPress
if (!defined("WPINC")) {
    die;
}

// Подключаем класс плагина
require_once(plugin_dir_path(__FILE__) . "feedback.php");

// Получаем сущность плагина, паттерн Singleton
new feedback(__FILE__);