<?php
namespace sorokinmedia\user\tests;

use sorokinmedia\user\tests\entities\User\User;
use yii\console\Application;
use yii\db\Connection;
use yii\db\Schema;

/**
 * Class TestCase
 * @package sorokinmedia\user\tests
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function mockApplication()
    {
        new Application([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'runtimePath' => __DIR__ . '/runtime',
            'aliases' => [
                '@tests' => __DIR__,
            ],
        ]);
    }

    /**
     *
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
    }

    /**
     * инициализация нужных таблиц
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function initDb()
    {
        @unlink(__DIR__ . '/runtime/sqlite.db');
        $db = new Connection([
            'dsn' => 'sqlite:' . \Yii::$app->getRuntimePath() . '/sqlite.db',
            'charset' => 'utf8',
        ]);
        \Yii::$app->set('db', $db);
        if ($db->getTableSchema('user')){
            $db->createCommand()->dropTable('user')->execute();
        }
        $db->createCommand()->createTable('user', [
            'id' => Schema::TYPE_PK,
            'email' => Schema::TYPE_STRING . '(255) NOT NULL',
            'password_hash' => Schema::TYPE_STRING . '(60) NOT NULL',
            'password_reset_token' =>Schema::TYPE_STRING . '(255)',
            'auth_key' => Schema::TYPE_STRING . '(45)',
            'username' => Schema::TYPE_STRING . '(255) NOT NULL',
            'status_id' => Schema::TYPE_TINYINT,
            'created_at' => Schema::TYPE_INTEGER . '(11)',
            'last_entering_date' => Schema::TYPE_INTEGER . '(11)',
            'email_confirm_token' => Schema::TYPE_STRING . '(255)'
        ])->execute();
        if ($db->getTableSchema('company')){
            $db->createCommand()->dropTable('company')->execute();
        }
        $db->createCommand()->createTable('user_meta', [
            'user_id' => Schema::TYPE_INTEGER,
            'notification_email' => Schema::TYPE_STRING . '(255)',
            'notification_phone' => Schema::TYPE_JSON,
            'notification_telegram' => Schema::TYPE_INTEGER,
            'full_name' => Schema::TYPE_JSON,
            'display_name' => Schema::TYPE_STRING . '(500)',
            'tz' => Schema::TYPE_STRING . '(100)',
            'location' => Schema::TYPE_STRING . '(200)',
            'about' => Schema::TYPE_TEXT,
            'custom_fields' => Schema::TYPE_JSON,
            'PRIMARY KEY(user_id)',
        ])->execute();
        if ($db->getTableSchema('user_access_token')){
            $db->createCommand()->dropTable('user_access_token')->execute();
        }
        $db->createCommand()->createTable('user_access_token', [
            'user_id' => Schema::TYPE_INTEGER,
            'access_token' => Schema::TYPE_STRING . '(32) NOT NULL',
            'created_at' => Schema::TYPE_INTEGER . '(11)',
            'updated_at' => Schema::TYPE_INTEGER . '(11)',
            'expired_at' => Schema::TYPE_INTEGER . '(11)',
            'is_active' => Schema::TYPE_TINYINT,
            'PRIMARY KEY(user_id, access_token)',
        ])->execute();
        if ($db->getTableSchema('sms_code')){
            $db->createCommand()->dropTable('sms_code')->execute();
        }
        $db->createCommand()->createTable('sms_code', [
            'id' => Schema::TYPE_PK,
            'user_id' => Schema::TYPE_INTEGER,
            'phone' => Schema::TYPE_STRING . '(12) NOT NULL',
            'created_at' => Schema::TYPE_INTEGER . '(11)',
            'code' => Schema::TYPE_INTEGER . '(4)',
            'type_id' => Schema::TYPE_INTEGER . '(1)',
            'ip' => Schema::TYPE_STRING . '(15)',
            'is_used' => Schema::TYPE_INTEGER . '(2)',
            'is_validated' => Schema::TYPE_INTEGER . '(1)',
            'is_deleted' => Schema::TYPE_INTEGER . '(1)',
        ])->execute();
        if ($db->getTableSchema('company')){
            $db->createCommand()->dropTable('company')->execute();
        }
        $db->createCommand()->createTable('company', [
            'id' => Schema::TYPE_INTEGER,
            'owner_id' => Schema::TYPE_INTEGER,
            'name' => Schema::TYPE_STRING . '(500)',
            'description' => Schema::TYPE_TEXT,
            'PRIMARY KEY(id)',
        ])->execute();
        if ($db->getTableSchema('company_user')){
            $db->createCommand()->dropTable('company_user')->execute();
        }
        $db->createCommand()->createTable('company_user', [
            'company_id' => Schema::TYPE_INTEGER,
            'user_id' => Schema::TYPE_INTEGER,
            'role' => Schema::TYPE_STRING . '(255)',
            'PRIMARY KEY(company_id, user_id, role)',
        ])->execute();

        $this->initDefaultData();
    }

    /**
     * дефолтный набор данных для тестов
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function initDefaultData()
    {
        $db = new Connection([
            'dsn' => 'sqlite:' . \Yii::$app->getRuntimePath() . '/sqlite.db',
            'charset' => 'utf8',
        ]);
        \Yii::$app->set('db', $db);
        $db->createCommand()->insert('user', [
            'id' => 1,
            'email' => 'test@yandex.ru',
            'password_hash' => '$2y$13$965KGf0VPtTcQqflsIEDtu4kmvM4mstARSbtRoZRiwYZkUqCQWmcy',
            'password_reset_token' => null,
            'auth_key' => 'NdLufkTZDHMPH8Sw3p5f7ukUXSXllYwM',
            'username' => 'IvanSidorov',
            'status_id' => 1,
            'created_at' => 1460902430,
            'last_entering_date' => 1532370359,
            'email_confirm_token' => null,
        ])->execute();
        $db->createCommand()->insert('user_access_token', [
            'user_id' => 1,
            'access_token' => 'a188dd6d0a16071691c0a6247ed76ed4',
            'created_at' => 1528365638,
            'updated_at' => null,
            'expired_at' => null,
            'is_active' => 1,
        ])->execute();
        $db->createCommand()->insert('user_meta', [
            'user_id' => 1,
            'notification_email' => 'test1@yandex.ru',
            'notification_phone' => '{"number": 9198078281, "country": 7, "is_verified": true}',
            'notification_telegram' => 12345678,
            'full_name' => '{"name": "Руслан", "surname": "Гилязетдинов", "patronymic": "Рашидович"}',
            'tz' => 'Europe/Samara',
            'location' => 'Russia/Samara',
            'about' => 'О себе: текст',
            'custom_fields' => '[{"name": "Афвф", "value": "аывфыы 34"}]',
        ])->execute();
        $db->createCommand()->insert('sms_code', [
            'id' => 1,
            'user_id' => 1,
            'phone' => '79198078281',
            'created_at' => 1536009859,
            'code' => 3244,
            'type_id' => 1,
            'ip' => '109.124.226.156',
            'is_used' => 0,
            'is_validated' => 0,
            'is_deleted' => 0,
        ])->execute();
        $db->createCommand()->insert('company', [
            'id' => 1,
            'owner_id' => 1,
            'name' => 'Моя компания',
            'description' => null
        ])->execute();
        $db->createCommand()->insert('company_user', [
            'company_id' => 1,
            'user_id' => 1,
            'role' => User::ROLE_OWNER
        ])->execute();
    }

    /**
     * доп данные для таблицы user
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function initDbAdditional()
    {
        $db = new Connection([
            'dsn' => 'sqlite:' . \Yii::$app->getRuntimePath() . '/sqlite.db',
            'charset' => 'utf8',
        ]);
        \Yii::$app->set('db', $db);
        $db->createCommand()->insert('user', [
            'id' => 2,
            'email' => 'test@yandex.ru',
            'password_hash' => '$2y$13$965KGf0VPtTcQqflsIEDtu4kmvM4mstARSbtRoZRiwYZkUqCQWmcy',
            'password_reset_token' => null,
            'auth_key' => 'NdLufkTZDHMPH8Sw3p5f7ukUXSXllYwM',
            'username' => 'IvanSidorov',
            'status_id' => 1,
            'created_at' => 1460902430,
            'last_entering_date' => 1532370359,
            'email_confirm_token' => null,
        ])->execute();

        $db->createCommand()->insert('sms_code', [
            'id' => 2,
            'user_id' => 1,
            'phone' => '79198078281',
            'created_at' => time() - 3600,
            'code' => 4432,
            'type_id' => 1,
            'ip' => '109.124.226.156',
            'is_used' => 0,
            'is_validated' => 0,
            'is_deleted' => 0,
        ])->execute();
    }
}