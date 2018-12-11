<?php
namespace sorokinmedia\telegram;

use sorokinmedia\telegram\entities\TelegramLog\TelegramLog;
use sorokinmedia\user\entities\User\AbstractUser;
use yii\base\Component;

/**
 * Class Telegram
 * @package sorokinmedia\telegram
 *
 * @property string $service_name название проекта
 * @property string $bot_name название бота
 * @property string $bot_url URL для доступа к боту через API
 * @property array $admin_chat_ids ID чатов админов
 * @property array $ticket_chat_ids ID чатов отвечающих на тикеты
 * @property string $user_class класс для модели User
 */
class Telegram extends Component
{
    public $service_name;
    public $bot_name;
    public $bot_url;
    public $admin_chat_ids;
    public $ticket_chat_ids;
    public $user_class;

    /**
     * рассылка админам в телеграм
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function sendAdminMessages(string $message) : bool
    {
        foreach ($this->admin_chat_ids as $account){
            self::sendMessage($account, $message);
        }
        return true;
    }

    /**
     * рассылка тем, кто с тикетами работает
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function sendTicketMessages(string $message) : bool
    {
        foreach ($this->ticket_chat_ids as $account){
            self::sendMessage($account, $message);
        }
        return true;
    }

    /**
     * @param \Exception $e
     * @return bool
     * @throws \Exception
     */
    public function sendAdminError(\Exception $e) : bool
    {
        self::sendAdminMessages("File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\nMessage: " . $e->getMessage());
        return true;
    }

    /**
     * @param AbstractUser $user
     * @return string
     */
    public function getBotLink(AbstractUser $user) : string
    {
        return 'http://t-do.ru/' . $this->bot_name . '?start=' . $user->auth_key;
    }

    /**
     * получение урла для отправки сообшений
     * @return mixed
     */
    public function getBotUrl() : string
    {
        return $this->bot_url;
    }

    /**
     * отправка сообщения
     * @param int $chat_id
     * @param string $text
     * @param bool $save
     * @return array|bool
     * @throws \Exception
     */
    public function sendMessage(int $chat_id, string $text, bool $save = true)
    {
        $send_message_url = $this->getBotUrl() . '/sendMessage';
        if ($chat_id and $text) {
            $ch = curl_init($send_message_url);
            $postdata = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            $content = json_decode(curl_exec($ch), true);
            $err = curl_errno($ch);
            curl_close($ch);
            if (isset($content['ok']) and $content['ok']) {
                $msg_for_db = [
                    'update_id' => 0,
                    'message' => $content['result']
                ]; //update_id = 0 - значит сообщение от бота
                if ($save) {
                    TelegramLog::updateLastMessageId($msg_for_db);
                }
                return $msg_for_db;
            }
            return false;
        }
        return false;
    }

    /**
     * Получает новые сообщения с сервера telegram
     * @return array|bool
     * @throws \Exception
     */
    public function getUpdates()
    {
        $offset = TelegramLog::getMaxLastMessageId() + 1;
        $updates_url = $this->getBotUrl() . '/getUpdates?offset=' . $offset;
        $ch = curl_init($updates_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = json_decode(curl_exec($ch), true);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        if (!$err && $content['ok']) {
            foreach ($content['result'] as $message) {
                $this->processMessage($message);
            }
            return $content['result'];
        }
        return false;
    }

    /**
     * @param array $message
     * @return bool
     * @throws \Exception
     * Обработка сообщения
     */
    public function processMessage(array $message) : bool
    {
        $text = null;
        if (isset($message['message']['text'])) {
            $text = $message['message']['text'];
        }
        if (!is_null($text)) {
            TelegramLog::updateLastMessageId($message);
            $chat_id = $message['message']['from']['id'];
            $user_chat_id = $this->user_class::getTelegramId($chat_id);
            $command = strtok($text, ' ');
            $arg1 = strtok(' ');
            if (!is_null($user_chat_id)) {
                $this->sendMessage($user_chat_id, \Yii::t('app', 'Не знаю такой команды :('));
                return true;
            }
            if ($command === '/start' and $arg1) {
                /** @var AbstractUser $user */
                $user = $this->user_class::setTelegramId($chat_id, $arg1);
                if (!is_null($user)) {
                    $this->sendMessage($chat_id, \Yii::t('app', 'С этого момента я буду присылать тебе уведомления c {service_name} :-)', ['service_name' => $this->service_name]));
                    $this->sendAdminMessages(\Yii::t('app', '#telegram Зарегистрировался новый пользователь {username}', ['username' => $user->displayName]));
                    return true;
                }
            }
            $this->sendMessage($chat_id, \Yii::t('app', 'Неизвестный пользователь, необходима регистрация на сайте {service_name}', ['service_name' => $this->service_name]));
            return true;
        }
        return false;
    }
}