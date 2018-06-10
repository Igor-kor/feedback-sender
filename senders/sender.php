<?php

/**
 * Created by PhpStorm.
 * User: игорь
 * Date: 08.06.2018
 * Time: 16:30
 */
interface sender
{
    // Отправка
    function send($message);

    // Настроки
    function showSettings($page, $section, $option_name);

    // проверка сохранения полей настроек
    //  должен вернуть массив с настройками $sanitary_values
    function settings_sanitize($input, $sanitary_values);
}