<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ScheduleCheckStatusMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ScheduleCheck:StatusMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status Mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        DB::table('cloud_mail_queue')->delete(45);
        $request = Request::create('api/mail/schedulecheckmail', 'GET');
        $this->info(app()['Illuminate\Contracts\Http\Kernel']->handle($request));

        //
    }
}