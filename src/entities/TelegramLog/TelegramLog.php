<?php
namespace sorokinmedia\telegram\entities\TelegramLog;

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
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'telegram_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['last_message_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('app', 'ID'),
            'last_message_id' => \Yii::t('app', 'ID последнего сообщения'),
        ];
    }

    /**
     * Получает из базы id последнего сохраненного сообщения
     * @return int
     */
    public static function getMaxLastMessageId() : int
    {
        return (static::findOne(1))->last_message_id;
    }

    /**
     * Сохраняет сообщение в базу
     * @param $message
     * @throws \Exception
     * @return boolean
     */
    public static function updateLastMessageId(array $message)
    {
        if ($message['update_id'] != 0) {
            $telegramLog = static::findOne(1); //Всегда берем первый id, он будет единственным
            $telegramLog->last_message_id = $message['update_id'];
            if(!$telegramLog->save()) {
                throw new Exception(\Yii::t('app','Ошибка при сохранении лога телеграма: updateLastMessageId'));
            }
        }
        return true;
    }
}