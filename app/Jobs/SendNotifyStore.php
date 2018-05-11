<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Mail;

class SendNotifyStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email_data, $to;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $to)
    {   
        $this->email_data = $data;
        $this->to         = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        $email = $this->to;
        Mail::send('emails.notifystore', $this->email_data, function($message) use($email) {      
            $message->to($email)->subject('Store Registered!');
        });
    }
}
