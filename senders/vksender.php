<?php

/**
 * Created by PhpStorm.
 * User: игорь
 * Date: 08.06.2018
 * Time: 16:29
 *
 */
class vksender implements sender
{

    function __construct($option_name)
    {
        $this->options = get_option($option_name);
    }

    function send($message)
    {
        if (isset($this->options['vksender_1']) && $this->options['vksender_1'] === 'vksender_1') {
            return $this->sendmessage($message);
        } else {
            return false;
        }
    }

    function showSettings($page, $section, $option_name)
    {
        $this->option_name = $option_name;
        // todo: возможно здесь надо их получать чтобы данные были обновленные
        $this->options = get_option($option_name);
        // Добавляем секции в настройках
        add_settings_field(
            'vksender_1', // id
            'отправка в вк', // title
            array($this, '_1_callback'), // callback
            $page, // page
            $section // section
        );

        add_settings_field(
            'vksender_2', // id
            'Api access token', // title
            array($this, '_2_callback'), // callback
            $page, // page
            $section // section
        );

        add_settings_field(
            'vksender_3', // id
            'Api secret', // title
            array($this, '_3_callback'), // callback
            $page, // page
            $section // section
        );

        add_settings_field(
            'vksender_4', // id
            'id беседы для отправки', // title
            array($this, '_4_callback'), // callback
            $page, // page
            $section // section
        );
    }

    public function _1_callback()
    {
        printf(
            '<input type="checkbox" name="%s[vksender_1]" id="vksender_1" value="vksender_1" %s> <label for="vksender_1">Включено</label>',
            $this->option_name,
            (isset($this->options['vksender_1']) && $this->options['vksender_1'] === 'vksender_1') ? 'checked' : ''
        );
    }

    public function _2_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[vksender_2]" id="vksender_2" value="%s">',
            $this->option_name,
            isset($this->options['vksender_2']) ? esc_attr($this->options['vksender_2']) : ''
        );
    }

    public function _3_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[vksender_3]" id="vksender_3" value="%s">',
            $this->option_name,
            isset($this->options['vksender_3']) ? esc_attr($this->options['vksender_3']) : ''
        );
    }

    public function _4_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[vksender_4]" id="vksender_4" value="%s">',
            $this->option_name,
            isset($this->options['vksender_4']) ? esc_attr($this->options['vksender_4']) : ''
        );
    }

    function settings_sanitize($input, $sanitary_values)
    {
        if (isset($input['vksender_1'])) {
            $sanitary_values['vksender_1'] = $input['vksender_1'];
        }

        if (isset($input['vksender_2'])) {
            $sanitary_values['vksender_2'] = sanitize_text_field($input['vksender_2']);
        }

        if (isset($input['vksender_3'])) {
            $sanitary_values['vksender_3'] = sanitize_text_field($input['vksender_3']);
        }

        if (isset($input['vksender_4'])) {
            $sanitary_values['vksender_4'] = sanitize_text_field($input['vksender_4']);
        }
        return $sanitary_values;
    }

    function sendmessage($message)
    {
        $access_tocken = esc_attr($this->options['vksender_2']);
        $secret = esc_attr($this->options['vksender_3']);
        $id = esc_attr($this->options['vksender_4']);
        $url = 'https://api.vk.com/method/messages.send';
        $params = array(
            'peer_id' => $id,    // Кому отправляем
            'message' => $message,   // Что отправляем
            'access_token' => $access_tocken,  // access_token
            'v' => '5.38',
        );
        $sig = md5("/method/messages.send?" . http_build_query($params) . $secret);
        $params['sig'] = $sig;
        // В $result вернется id отправленного сообщения
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            )
        )));
        return $result;
    }

}