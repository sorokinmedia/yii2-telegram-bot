#telegram bot for yii2

## установка компонента
В основной конфиг `main.php` в секцию компонентов добавить:

```
'telegram' => [
    'class' => \sorokinmedia\telegram\Telegram::class,
    'bot_name' => 'CabinetAnalitica_bot',
    'bot_url' => 'https://api.telegram.org/bot296189883:AAEQVbfs1uKabirZ0p315Q7uplUpXahhkD8',
    'admin_chat_ids' => [286248959, 204349098, 261513190], // Ma3oBblu, raskrutka, varenik
    'ticket_chat_ids' => [286248959, 204349098, 261513190],
    'service_name' => 'Sorokin.space',
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

## использование в проекте

+ `\Yii::$app->telegram->send(int $chat_id, string $message)` - отправка сообщения в указанный чат

+ `\Yii::$app->telegram->sendAdminMessages(string $message)` - отправка сообщения по списку чатов, указанных в `admin_chat_ids` параметре конфига

+ `\Yii::$app->telegram->sendTicketMessages(string $message)` - отправка сообщения по списку чатов, указанных в `ticket_chat_ids` параметре конфига

+ `\Yii::$app->telegram->getUpdates()` - апдейт БД (ID последнего сообщения). необходимо использовать в консольном экшене, который вызывается по крону раз в минуту