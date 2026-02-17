<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class TestRedisConnection extends Command
{
    protected $signature = 'redis:test {--connection=default : The Redis connection to test}';

    protected $description = 'Test the Redis connection';

    public function handle()
    {
        $connection = $this->option('connection');

        $this->info("Testing Redis connection: [{$connection}]...");

        try {
            $ping = Redis::connection($connection)->ping();
            $this->info('✅ PING successful: ' . $ping);

            Redis::connection($connection)->set('laravel:redis:test', 'it works!');
            $this->info('✅ SET successful');

            $value = Redis::connection($connection)->get('laravel:redis:test');
            $this->info('✅ GET successful: ' . $value);

            Redis::connection($connection)->del('laravel:redis:test');
            $this->info('✅ DEL successful (cleaned up test key)');

            $this->newLine();
            $this->table(
                ['Property', 'Value'],
                [
                    ['Host',     config("database.redis.{$connection}.host")],
                    ['Port',     config("database.redis.{$connection}.port")],
                    ['Database', config("database.redis.{$connection}.database")],
                    ['Client',   config('database.redis.client')],
                ]
            );

            $this->newLine();
            $this->info('🎉 Redis connection is working perfectly!');

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Redis connection failed!');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}