<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Reset the demo database and seed fresh data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Resetting demo environment...');

        $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->components->info('Demo data restored.');

        return self::SUCCESS;
    }
}

