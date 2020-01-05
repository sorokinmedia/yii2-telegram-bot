<?php

namespace sorokinmedia\telegram;

use Exception;
use sorokinmedia\telegram\entities\TelegramLog\TelegramLog;
use sorokinmedia\user\entities\User\AbstractUser;
use Yii;
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
 * @property array $special_chat_ids ID чатов для спец отправки
 * @property string $user_class класс для модели User
 * @property string $user_meta_class класс для можели UserMeta
 */
class Telegram extends Component
{
    public $service_name;
    public $bot_name;
    public $bot_url;
    public $admin_chat_ids;
    public $ticket_chat_ids;
    public $special_chat_ids;
    public $user_class;
    public $user_meta_class;

    /**
     * рассылка тем, кто с тикетами работает
     * @param $message
     * @return bool
     * @throws Exception
     */
    public function sendTicketMessages(string $message): bool
    {
        foreach ($this->ticket_chat_ids as $account) {
            $this->sendMessage($account, $message);
        }
        return true;
    }

    /**
     * отправка сообщения
     * @param int $chat_id
     * @param string $text
     * @param bool $save
     * @return array|bool
     * @throws Exception
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
            curl_errno($ch);
            curl_close($ch);
            if (isset($content['ok']) && $content['ok']) {
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
     * получение урла для отправки сообшений
     * @return mixed
     */
    public function getBotUrl(): string
    {
        return $this->bot_url;
    }

    /**
     * рассылка тем, кто в спец рассылке
     * @param $message
     * @return bool
     * @throws Exception
     */
    public function sendSpecialMessages(string $message): bool
    {
        foreach ($this->special_chat_ids as $account) {
            $this->sendMessage($account, $message);
        }
        return true;
    }

    /**
     * @param Exception $e
     * @return bool
     * @throws Exception
     */
    public function sendAdminError(Exception $e): bool
    {
        $this->sendAdminMessages('File: ' . $e->getFile() . "\n\nLine: " . $e->getLine() . "\n\nMessage: " . $e->getMessage());
        return true;
    }

    /**
     * рассылка админам в телеграм
     * @param $message
     * @return bool
     * @throws Exception
     */
    public function sendAdminMessages(string $message): bool
    {
        foreach ($this->admin_chat_ids as $account) {
            $this->sendMessage($account, $message);
        }
        return true;
    }

    /**
     * @param AbstractUser $user
     * @return string
     */
    public function getBotLink(AbstractUser $user): string
    {
        return 'http://t-do.ru/' . $this->bot_name . '?start=' . $user->auth_key;
    }

    /**
     * Получает новые сообщения с сервера telegram
     * @return array|bool
     * @throws Exception
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
     * @throws Exception
     * Обработка сообщения
     */
    public function processMessage(array $message): bool
    {
        $text = $message['message']['text'] ?? null;
        if ($text !== null) {
            TelegramLog::updateLastMessageId($message);
            $chat_id = $message['message']['from']['id'];
            $user_chat_id = $this->user_meta_class::checkTelegram($chat_id);
            $command = strtok($text, ' ');
            $api_key = strtok(' ');
            if ($user_chat_id !== null) {
                $this->sendMessage($user_chat_id, Yii::t('app-sm-telegram-bot', 'Не знаю такой команды :('));
                return true;
            }
            if ($command === '/start' && $api_key !== '') {
                $user = $this->user_class::setTelegramId($chat_id, $api_key);
                if ($user !== null) {
                    $this->sendMessage($chat_id, Yii::t('app-sm-telegram-bot', 'С этого момента я буду присылать тебе уведомления c {service_name} :-)', ['service_name' => $this->service_name]));
                    $this->sendAdminMessages(Yii::t('app-sm-telegram-bot', '#telegram Зарегистрировался новый пользователь {username}', ['username' => $user->displayName]));
                    return true;
                }
            }
            $this->sendMessage($chat_id, Yii::t('app-sm-telegram-bot', 'Неизвестный пользователь, необходима регистрация на сайте {service_name}', ['service_name' => $this->service_name]));
            return true;
        }
        return false;
    }
}
