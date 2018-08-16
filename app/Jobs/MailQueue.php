<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use App\Mail\ServerMail;
use App\Http\Models\MailQueue as ModelMail;

class MailQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $email_id;
    protected $to_email;

    public function __construct($email_id,$to_email)
    {
        //
        $this->email_id = $email_id;
        $this->to_email = $to_email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->SendMailTo($this->to_email,$this->email_id);
        }catch (\Exception $ex) {
            echo new \Exception($ex->getMessage());
        }
    }

    public function SendMailTo($to_email,$email_id)
    {
        try {
            $MailQueue = ModelMail::where('id', '=', $email_id)->first();
            if (!isset($MailQueue['client_email'])) {
                echo 'The email cannot found';
                return;
            }


            $mailcontent = new ServerMail();
            $mailcontent->setView($MailQueue['layout']);
            $mailcontent->subject($MailQueue['subject']);
            $infoMail = json_decode($MailQueue['param']);
            foreach ($infoMail as $key => $value) {
                $mailcontent->with($key, $value);
            }
            Mail::to($to_email)
                ->send($mailcontent);
            $MailQueue->status = 1;
            $MailQueue->sent_date = date('Y-m-d H:i:s');
            $MailQueue->save();
            echo 'Success. You have sent the email.';
        } catch (\Exception $ex) {
            $MailQueue->status = 2;
            $MailQueue->save();
            echo $ex->getMessage();
        }
    }

}
