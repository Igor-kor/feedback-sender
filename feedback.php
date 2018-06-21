<?php

/**
 * Created by PhpStorm.
 * User: игорь
 * Date: 08.06.2018
 * Time: 16:11
 *
 * WordPress Option Page generator http://jeremyhixon.com/wp-tools/option-page/
 */
class feedback
{
    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $version = "0.1";

    /**
     * Unique identifier for your plugin.
     *
     * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
     * match the Text Domain file header in the main plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $plugin_slug = "feedback-sender";

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $plugin_screen_hook_suffix = null;

    protected $option_name = "feedback_option_name";


    public function __construct($file)
    {
        $this->file = $file;
        $this->load_dependencies();
        $this->init();
        //todo что-то не доделал
        if (is_admin()) ;
        $this->settings();
    }

    // подключение зависимостей
    function load_dependencies()
    {
        require_once plugin_dir_path($this->file) . "senders/sender.php";
        require_once plugin_dir_path($this->file) . "senders/emailsender.php";
        require_once plugin_dir_path($this->file) . "senders/vksender.php";
        require_once plugin_dir_path($this->file) . "recaptchalib.php";
    }

    // инициализация всего что нужно сделать
    function init()
    {
        $this->add_hooks();
        $this->senders[] = new vksender($this->option_name);
        $this->senders[] = new emailsender($this->option_name);
    }

    //добавление хуков
    function add_hooks()
    {
        $this->add_action('wp_ajax_feedback', 'feedback_callback');
        $this->add_action('wp_ajax_nopriv_feedback', 'feedback_callback');
        $this->add_action('wp_head', 'js_variables');
        $this->add_action('wp_footer', 'recaptcha');

    }

    // добавление для рекапчи
    function recaptcha()
    {
        ?>
        <script src='https://www.google.com/recaptcha/api.js'></script>
        <?php
    }


    //чтоб постоянно не писать в массиве
    private function add_action($action, $function)
    {
        add_action($action, array($this, $function));
    }


    // передает нужные переменные на фронт
    function js_variables()
    {
        $variables = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'is_mobile' => wp_is_mobile()
        );
        echo "<script type=\"text/javascript\">window.wp_data =" . json_encode($variables) . ";</script>";
    }

    // обработка запросв
    function feedback_callback()
    {
        $this->options = get_option($this->option_name);
        // если есть проверка на рекапчу
        if (isset($this->options['_4']) && $this->options['_4'] === '_4') {
            //todo: проверяем капчу
            if ($this->valid_recaptcha($_REQUEST)) {
                // отправляем сообщение
                $response = $this->send($_REQUEST);
            }
        } else {
            // отправляем сообщение
            $response = $this->send($_REQUEST);
        }
        // отправляем статус ок
        echo(json_encode(array('status' => 'ok', 'request_vars' => $_REQUEST, 'response' => $response)));
        // обязательно при работе с аяксом
        wp_die();
    }

    //проверка рекапчи
    function valid_recaptcha($request)
    {
        // ваш секретный ключ
        $this->options = get_option($this->option_name);
        $secret = $this->options["_5"];
        // пустой ответ
        $response = null;
        // проверка секретного ключа
        $reCaptcha = new ReCaptcha($secret);
        // if submitted check response
        if ($request["g-recaptcha-response"]) {
            $response = $reCaptcha->verifyResponse(
                $this->getIpClient(),
                $request["g-recaptcha-response"]
            );
        }
        if ($response != null && $response->success) {
            return true;
        }
        return false;
    }

    // отправление сообщения
    function send($text)
    {
        //проверяем все доступные поля
        $text = $this->checkfields($text);
        // todo: желательно генерировать уникальный номер обращения
        foreach ($this->senders as $sender) {
            $response[] = $sender->send($text);
        }
        return $response;
    }

    // отображение настроек
    function settings()
    {
        $this->add_action('admin_menu', 'settings_add_plugin_page');
        $this->add_action('admin_init', 'settings_page_init');
    }

    // доюавление пункта в настройках
    function settings_add_plugin_page()
    {
        add_options_page(
            'Feedback plugin', // page_title
            'Feedback plugin', // menu_title
            'manage_options', // capability
            'feedback', // menu_slug
            array($this, 'settings_create_admin_page') // function
        );
    }

    //создание страницы с настройками
    function settings_create_admin_page()
    {
        $this->options = get_option('feedback_option_name'); ?>

        <div class="wrap">
            <h2>Feedback plugin settings</h2>
            <p>Настройки плагина обратной связи</p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('feedback_option_group');
                do_settings_sections('feedback-admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // добаление полей в настройки
    public function settings_page_init()
    {
        register_setting(
            'feedback_option_group', // option_group
            'feedback_option_name', // option_name
            array($this, 'feedback_sanitize') // sanitize_callback
        );

        add_settings_section(
            'feedback_setting_section', // id
            'Settings', // title
            array($this, 'feedback_section_info'), // callback
            'feedback-admin' // page
        );

        foreach ($this->senders as $sender) {
            $sender->showSettings('feedback-admin', 'feedback_setting_section', 'feedback_option_name', $this->options);
        }

        add_settings_field(
            '', // id
            'Тестовая отправка', // title
            array($this, '_1_callback'), // callback
            'feedback-admin', // page
            'feedback_setting_section' // section
        );

        add_settings_field(
            '_2', // id
            'Поля которые будут отправлятся', // title
            array($this, '_2_callback'), // callback
            'feedback-admin', // page
            'feedback_setting_section' // section
        );

        add_settings_field(
            '_3', // id
            'отправка ip адреса клиента', // title
            array($this, '_3_callback'), // callback
            'feedback-admin', // page
            'feedback_setting_section' // section
        );

        add_settings_field(
            '_4', // id
            'Рекапча', // title
            array($this, '_4_callback'), // callback
            'feedback-admin', // page
            'feedback_setting_section' // section
        );

        add_settings_field(
            '_5', // id
            'secret recaptcha', // title
            array($this, '_5_callback'), // callback
            'feedback-admin', // page
            'feedback_setting_section' // section
        );

    }

    // вывод формы с заданными полями для проверки
    public function _1_callback()
    {
        $fields = explode(',', get_option($this->option_name)["_2"]);
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        ?>
        <?php
        foreach ($fields as $key) {
            if (isset($key)) {
                $text = explode(':', $key);
                echo '<label>' . $text[1] . ':</label> <input class="testform" type="text" name="' . $text[0] . '"><br><br>';
            }
        }
        ?>
        <input value="Тестовая отправка" type="button" onclick="jQuery(function ($) {
             var msg = {};
              Array.from($('.testform')).forEach(function ( el){
                  console.log(el);
                 msg[el.name] = el.value;
             });
              msg['action']='feedback';
              console.log(msg);
              var msgtext = $.param(msg);
             console.log(msgtext);
            $.ajax({
                type: 'GET',
                // в админке уже есть переменная ajaxurl
                // url: window.wp_data.ajax_url,
                url: ajaxurl,
                data: msg,
            success: function (response) {
                alert('Отправленно!');
                $('#responseajax').html(response);
                }
            });
        });return false;">
        <br><br>
        <textarea id="responseajax" readonly style="width: 400px;height: 50px"></textarea>

        <?php
    }

    // поля для отправки
    public function _2_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[_2]" id="_2" value="%s">',
            $this->option_name,
            isset($this->options['_2']) ? esc_attr($this->options['_2']) : ''
        );
    }

    // включить отправку айпи
    public function _3_callback()
    {
        printf(
            '<input type="checkbox" name="%s[_3]" id="_3" value="_3" %s> <label for="_3">Включено</label>',
            $this->option_name,
            (isset($this->options['_3']) && $this->options['_3'] === '_3') ? 'checked' : ''
        );
    }

    //включить рекапчу
    public function _4_callback()
    {
        printf(
            '<input type="checkbox" name="%s[_4]" id="_4" value="_4" %s> <label for="_4">Включено</label>',
            $this->option_name,
            (isset($this->options['_4']) && $this->options['_4'] === '_4') ? 'checked' : ''
        );
    }

    // рекапча секрет
    public function _5_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="%s[_5]" id="_5" value="%s">',
            $this->option_name,
            isset($this->options['_5']) ? esc_attr($this->options['_5']) : ''
        );
    }

    // проверка сохранения полей настроек
    public function feedback_sanitize($input)
    {
        $sanitary_values = array();
        foreach ($this->senders as $sender) {
            $sanitary_values = $sender->settings_sanitize($input, $sanitary_values);
        }

        if (isset($input['_2'])) {
            $sanitary_values['_2'] = sanitize_text_field($input['_2']);
        }

        if (isset($input['_3'])) {
            $sanitary_values['_3'] = $input['_3'];
        }

        if (isset($input['_4'])) {
            $sanitary_values['_4'] = $input['_4'];
        }

        if (isset($input['_5'])) {
            $sanitary_values['_5'] = sanitize_text_field($input['_5']);
        }

        return $sanitary_values;
    }


    public function feedback_section_info()
    {

    }

    // проверка всех полей чтобы не отправлять лишнее
    function checkfields($args)
    {
        $message = "";
        $this->options = get_option($this->option_name);
        $fields = explode(',', $this->options["_2"]);
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        foreach ($fields as $key) {
            if (isset($key)) {
                $text = explode(':', $key);
                $message .= $text[1] . ": " . $args[trim($text[0])] . "\r\n";
            }
        }

        if (isset($this->options['_3']) && $this->options['_3'] === '_3') {
            $ip = $this->getIpClient();
            $message .= "ip:" . json_encode($ip) . "\r\n";
        }

        return $message;
    }

    // озвращает айпи текущего клиента
    function getIpClient()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}