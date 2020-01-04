<?php

namespace sorokinmedia\telegram\entities\TelegramLog;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * Class TelegramLog
 * @package sorokinmedia\telegram\entities\TelegramLog
 *
 * @property integer $id
 * @property integer $last_message_id
 */
class TelegramLog extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'telegram_log';
    }

    /**
     * Получает из базы id последнего сохраненного сообщения
     * @return int
     */
    public static function getMaxLastMessageId(): int
    {
        return (static::findOne(1))->last_message_id;
    }

    /**
     * Сохраняет сообщение в базу
     * @param $message
     * @return boolean
     * @throws \Exception
     */
    public static function updateLastMessageId(array $message): bool
    {
        if ($message['update_id'] != 0) {
            $telegramLog = static::findOne(1); //Всегда берем первый id, он будет единственным
            if ($telegramLog instanceof self){
                $telegramLog->last_message_id = $message['update_id'];
                if (!$telegramLog->save()) {
                    throw new Exception(Yii::t('app', 'Ошибка при сохранении лога телеграма: updateLastMessageId'));
                }
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['last_message_id'], 'integer'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'last_message_id' => Yii::t('app', 'ID последнего сообщения'),
        ];
    }
}
