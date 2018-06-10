<?php

/**
 * Created by PhpStorm.
 * User: игорь
 * Date: 08.06.2018
 * Time: 16:29
 *
 * необходимые настройки
 * кому
 * тема
 */
class emailsender implements sender
{

    function __construct($option_name)
    {
        $this->options = get_option($option_name);
    }

    function send($message)
    {
        if (isset($this->options['emailsender_1']) && $this->options['emailsender_1'] === 'emailsender_1') {
            return wp_mail(explode(',',$this->options['emailsender_2']) , $this->options['emailsender_3'] , $message, 'From:<emailsender@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n");
        } else {
            return false;
        }

    }

    function showSettings($page, $section, $option_name)
    {
        $this->option_name = $option_name;
        // todo: возможно здесь надо их получать чтобы данные были обновленные
        $this->options = get_option($option_name);;
        // Добавляем секции в настройках
        add_settings_field(
            'emailsender_1', // id
            'отправка на почту', // title
            array($this, '_1_callback'), // callback
            $page, // page
            $section // section
        );

        add_settings_field(
            'emailsender_2', // id
            'Адрес(а)(через запятую)', // title
            array($this, '_2_callback'), // callback
            $page, // page
            $section // section
        );

        add_settings_field(
            'emailsender_3', // id
            'Тема сообщения', // title
            array($this, '_3_callback'), // callback
            $page, // page
            $section // section
        );
    }

    public function _1_callback()
    {
        printf(
            '<input type="checkbox" name="%s[emailsender_1]" id="emailsender_1" value="emailsender_1" %s> <label for="emailsender_1">Включено</label>',
            $this->option_name,
            (isset($this->options['emailsender_1']) && $this->options['emailsender_1'] === 'emailsender_1') ? 'checked' : ''
        );
    }

    public function _2_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[emailsender_2]" id="emailsender_2" value="%s">',
            $this->option_name,
            isset($this->options['emailsender_2']) ? esc_attr($this->options['emailsender_2']) : ''
        );
    }

    public function _3_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[emailsender_3]" id="emailsender_3" value="%s">',
            $this->option_name,
            isset($this->options['emailsender_3']) ? esc_attr($this->options['emailsender_3']) : ''
        );
    }

    function settings_sanitize($input, $sanitary_values)
    {
        if (isset($input['emailsender_1'])) {
            $sanitary_values['emailsender_1'] = $input['emailsender_1'];
        }
        if (isset($input['emailsender_2'])) {
            $sanitary_values['emailsender_2'] = sanitize_text_field($input['emailsender_2']);
        }
        if (isset($input['emailsender_3'])) {
            $sanitary_values['emailsender_3'] = sanitize_text_field($input['emailsender_3']);
        }

        return $sanitary_values;
    }


}