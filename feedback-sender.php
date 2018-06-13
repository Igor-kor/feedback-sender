<?php
/**
 * Plugin Name: Feedback-sender
 * Plugin URI: http://github.com/
 * Description: Отправка обратной связи
 * Version: 0.2
 * Author: Шарангия Игорь
 * Author URI: http://vk.com/id117766113
 */

// Для того, чтобы этот файл не могли подключить вне WordPress
if (!defined("WPINC")) {
    die;
}

// Подключаем класс плагина
require_once(plugin_dir_path(__FILE__) . "feedback.php");

// Получаем сущность плагина, паттерн Singleton
new feedback(__FILE__);