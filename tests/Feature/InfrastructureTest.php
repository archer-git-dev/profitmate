<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class InfrastructureTest extends TestCase
{
    /**
     * 1. Проверка базы данных PostgreSQL.
     */
    public function test_postgresql_connection_is_working(): void
    {
        try {
            $pdo = DB::connection('pgsql')->getPdo();
            $this->assertNotNull($pdo);
            $this->assertEquals('pgsql', DB::connection('pgsql')->getConfig('driver'));
        } catch (\Exception $e) {
            $this->fail('❌ PostgreSQL Error: ' . $e->getMessage());
        }
    }

    /**
     * 2. Проверка Redis (запись и чтение).
     */
    public function test_redis_connection_is_working(): void
    {
        try {
            Redis::set('infra_test_key', 'working');
            $value = Redis::get('infra_test_key');

            $this->assertEquals('working', $value);
            Redis::del('infra_test_key'); // Удаляем мусор
        } catch (\Exception $e) {
            $this->fail('❌ Redis Error: ' . $e->getMessage());
        }
    }

    /**
     * 3. Проверка MinIO (S3) - реальное создание файла.
     */
    public function test_minio_s3_connection_is_working(): void
    {
        try {
            $filename = 'test_infra_' . time() . '.txt';
            $content = 'Hello from Laravel Test';

            // 1. Записываем
            Storage::disk('s3')->put($filename, $content);

            // 2. Проверяем существование
            $exists = Storage::disk('s3')->exists($filename);
            $this->assertTrue($exists, 'File was not created in MinIO');

            // 3. Проверяем доступность URL (необязательно, но полезно)
            $url = Storage::disk('s3')->url($filename);
            $this->assertNotEmpty($url);

            // 4. Удаляем
            Storage::disk('s3')->delete($filename);

        } catch (\Exception $e) {
            $this->fail('❌ MinIO (S3) Error: ' . $e->getMessage() . ' Check if bucket exists!');
        }
    }

    /**
     * 4. Проверка RabbitMQ (отправка сообщения).
     */
    public function test_rabbitmq_connection_is_working(): void
    {
        try {
            // Пытаемся отправить "сырое" сообщение, чтобы не триггерить Job-классы
            // 'default' - имя очереди
            Queue::connection('rabbitmq')->pushRaw('test payload', 'health-check-queue');

            // Если мы дошли сюда и не упали в catch - значит коннект есть
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('❌ RabbitMQ Error: ' . $e->getMessage());
        }
    }

    /**
     * 5. Проверка Mailpit (SMTP).
     */
    public function test_mailpit_smtp_connection_is_working(): void
    {
        try {
            // Отключаем реальную отправку в лог, форсируем SMTP
            config(['mail.default' => 'smtp']);

            Mail::raw('Infrastructure Test Email', function ($message) {
                $message->to('test@fitvibe.local')
                    ->subject('Infrastructure Check');
            });

            // Если ошибок нет - письмо ушло в Mailpit
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('❌ Mailpit/SMTP Error: ' . $e->getMessage());
        }
    }

    /**
     * 6. Проверка ClickHouse.
     */
    public function test_clickhouse_connection_is_working(): void
    {
        try {
            // Создаем конфигурацию клиента вручную, используя данные из ENV
            $config = [
                'host' => env('CLICKHOUSE_HOST', 'clickhouse'),
                'port' => env('CLICKHOUSE_PORT', '8123'),
                'username' => env('CLICKHOUSE_USERNAME', 'default'),
                'password' => env('CLICKHOUSE_PASSWORD', ''),
            ];

            // Инициализируем клиент (библиотека smi2/phpclickhouse)
            $db = new \ClickHouseDB\Client($config);

            // Указываем базу данных
            $db->database(env('CLICKHOUSE_DATABASE', 'default'));

            // Устанавливаем таймаут (на всякий случай)
            $db->setTimeout(1.5);
            $db->setConnectTimeOut(5);

            // Выполняем Ping запрос
            $stat = $db->ping(); // Вернет true, если соединение есть

            // Если пинг прошел, попробуем простой SELECT
            $statement = $db->select('SELECT 1 as ping');
            $rows = $statement->rows();

            $this->assertTrue($stat, 'ClickHouse ping failed');
            $this->assertEquals(1, $rows[0]['ping']);

        } catch (\Exception $e) {
            $this->fail('❌ ClickHouse Error: ' . $e->getMessage());
        }
    }
}
