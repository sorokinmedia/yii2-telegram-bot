<?php
namespace sorokinmedia\telegram;

use sorokinmedia\telegram\entities\TelegramLog\AbstractTelegramLog;
use sorokinmedia\user\entities\User\AbstractUser;
use yii\base\Component;

/**
 * Class Telegram
 * @package sorokinmedia\telegram
 *
 * @property string $bot_name название бота
 * @property string $bot_url URL для доступа к боту через API
 * @property array $admin_chat_ids ID чатов админов
 * @property array $ticket_chat_ids ID чатов отвечающих на тикеты
 */
class Telegram extends Component
{
    public $bot_name;
    public $bot_url;
    public $admin_chat_ids;
    public $ticket_chat_ids;

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
    public function getUrl() : string
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
        $send_message_url = $this->getUrl() . '/sendMessage';
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
                    AbstractTelegramLog::updateLastMessageId($msg_for_db);
                }
                return $msg_for_db;
            }
            return false;
        }
        return false;
    }

    /**
     * Получает новые сообщения с сервера telegram
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function getUpdates()
    {
        $offset = AbstractTelegramLog::getMaxLastMessageId() + 1;
        $updates_url = self::getUrl() . '/getUpdates?offset=' . $offset;
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
        if (!$err and $content['ok']) {
            foreach ($content['result'] as $msg_for_db) {
                $this->processMessage($msg_for_db);
            }
            return $content['result'];
        }
        return false;
    }

    /**
     * Обработка сообщения
     * @param $message
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function processMessage(array $message)
    {
        if (isset($message['message']['text'])) {
            $text = $message['message']['text'];
        } else {
            $text = null;
        }
        if ($text) {
            AbstractTelegramLog::updateLastMessageId($message);
            $id_from = $message['message']['from']['id'];
            $user_id = UserMeta::getTelegramId($id_from);
            $first_character = substr($text, 0, 1);
            $command = strtok($text, ' ');
            $arg1 = strtok(' ');
            if ($user_id) {
                switch ($first_character) {
                    case '/': { //если это команда
                        self::sendMessage($id_from, 'Не знаю такой команды :(');
                        break;
                    }
                    default: {
                        self::sendMessage($id_from, 'Не знаю такой команды :(');
                        break;
                    }
                }
            } else {
                if ($command == '/start' and $arg1) {
                    /** @var User $user */
                    $user = User::findOne(['auth_key' => $arg1]);
                    if ($user) {
                        $user->userMeta->setTelegramId($id_from); //Присвоение айдишника телеграмма
                        $user->telegramOn();
                        self::sendMessage($id_from, "С этого момента я буду присылать тебе сообщения c Workhard :-)");
                        self::sendAdminMessages("Зарегистрировался новый пользователь " . $user->displayName);
                    }
                } else {
                    self::sendMessage($id_from, 'Неизвестный пользователь, необходима регистрация на сайте workhard.online');
                }
            }
        }
    }
}