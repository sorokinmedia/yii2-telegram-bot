#Telegram bot for yii2

[![Total Downloads](https://img.shields.io/packagist/dt/sorokinmedia/yii2-telegram-bot.svg)](https://packagist.org/packages/sorokinmedia/yii2-telegram-bot)

## Установка компонента
В основной конфиг `main.php` в секцию компонентов добавить:

```
'telegram' => [
    'class' => \sorokinmedia\telegram\Telegram::class,
    'bot_name' => 'BotName',
    'bot_url' => 'https://api.telegram.org/bot_api_url',
    'admin_chat_ids' => [],
    'ticket_chat_ids' => [],
    'service_name' => 'ServiceName',
    'user_class' => \common\components\user\entities\User\User::class,
    'user_meta_class' => \common\components\user\entities\UserMeta\UserMeta::class,
],
```

где: 

+ `class` - основной класс компонента

+ `bot_name` - название бота

+ `bot_url` - URL бота для API

+ `admin_chat_ids` - ID чатов для отправки сообщений по ним

+ `ticket_chat_ids` - ID часто для отправки сообщений по ним

+ `service_name` - название проекта

+ `user_class` - класс модели User

+ `user_meta_class` - класс модели UserMeta

## Использование в проекте

+ `\Yii::$app->telegram->sendMessage(int $chat_id, string $message)` - отправка сообщения в указанный чат

+ `\Yii::$app->telegram->deleteMessage(int $chat_id, int $message_id)` - удалить сообщение из чата пользователя

+ `\Yii::$app->telegram->sendAdminMessages(string $message)` - отправка сообщения по списку чатов, указанных в `admin_chat_ids` параметре конфига

+ `\Yii::$app->telegram->sendTicketMessages(string $message)` - отправка сообщения по списку чатов, указанных в `ticket_chat_ids` параметре конфига

+ `\Yii::$app->telegram->getUpdates()` - апдейт БД (ID последнего сообщения). необходимо использовать в консольном экшене, который вызывается по крону раз в минуту

## Translation

Use `Yii::t('app-sm-telegram-bot', '')` for messages which need to be translated.
