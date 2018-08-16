<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ScheduleCheckStatusVM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ScheduleCheck:StatusVM';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status Server';

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
        $request = Request::create('api/server/schedulecheckstatus', 'GET');
        $this->info(app()['Illuminate\Contracts\Http\Kernel']->handle($request));

        //
    }
}
