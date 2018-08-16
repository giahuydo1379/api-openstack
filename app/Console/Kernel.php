<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        '\App\Console\Commands\ScheduleCheckStatusVM',
        '\App\Console\Commands\ScheduleCheckStatusMail',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('ScheduleCheck:StatusVM')
            ->everyMinute();
        $schedule->command('ScheduleCheck:StatusMail')
            ->everyMinute();
//        DB::table('cloud_mail_queue')->delete(1);
        // $schedule->command('inspire')
        //          ->hourly();
//        $schedule->call('app\Http\Controllers\MailController@ScheduleCheckMail')->everyMinute();
//        $schedule->call('app\Http\Controllers\ServerController@ScheduleCheckStatus')->everyMinute();
//        $schedule->call(function () {
//            DB::table('recent_users')->delete();
//        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
