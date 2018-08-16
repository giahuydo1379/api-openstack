<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use App\Mail\ServerMail;
use App\Http\Models\MailQueue;
use App\Jobs\MailQueue as MailQueueJob;


class MailController extends ApiController
{

    public function __construct()
    {
        //$this->middleware('check.authorize');
    }

    //code example
//    public function SendMail($mailaddress,$infoMail=[],$type=1)
//    {
//        switch ($type) {
//            case 1:
//                $viewmail='detailserver';
//                break;
//            case 2:
//                $viewmail='detailserver';
//                break;
//            default:
//                $viewmail='detailserver';
//        }
//        $mailcontent=new ServerMail();
//        $mailcontent->setView($viewmail);
//        $mailcontent->subject('Your Subject');
//
//        foreach($infoMail as $key => $value){
//            $mailcontent->with($key,$value);
//        }
//
//        Mail::to($mailaddress)->send($mailcontent);
//    }

    public function SendMail($email)
    {
        $MailQueue = MailQueue::where('status', 0)->first();
        if(!isset($MailQueue['client_email'])){
            return $this->resError('The Email list is empty.');
        }


        $mailcontent = new ServerMail();
        $mailcontent->setView($MailQueue['layout']);
        $mailcontent->subject($MailQueue['subject']);
        $infoMail=json_decode($MailQueue['param']);
        foreach ($infoMail as $key => $value) {
            $mailcontent->with($key, $value);
        }
        try{
            Mail::to($email)
                    ->send($mailcontent);
            $MailQueue->status=1;
            $MailQueue->sent_date= date('Y-m-d H:i:s');
            $MailQueue->save();
            return $this->resSuccess('Success. You have sent the email.');
        }
        catch (Exception $ex){
            $MailQueue->status=2;
            $MailQueue->save();
            return $this->resError('The send mail process have some problem, please retry later.');
        }
    }

    public function ScheduleCheckMail()
    {
        $ListMail = MailQueue::where('status', 0)
            ->get();
        if ($ListMail->isEmpty()) {
            echo 'No email need to send';
            return;
        }
        try {
            foreach ($ListMail as $value) {
                MailQueueJob::dispatch($value['id'],'ltdungrs@gmail.com')->onQueue('sendmail');
                $MailModel=MailQueue::find($value['id']);
                $MailModel->status=3;
                $MailModel->save();
            }
            echo 'Ok ';
        } catch (Exception $ex) {
            echo 'Error exception';
            return;
        }
        echo 'Finished';
    }

//    public function Test()
//    {
//        MailQueueJob::dispatch('ltdungrs@gmail.com')->onQueue('sendmail');
//        return 'ok';
//    }




}
