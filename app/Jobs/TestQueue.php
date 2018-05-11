<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Mail;

class TestQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $to;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $to)
    {
        $this->data = $data;
        $this->to   = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        $email = $this->to;
        Mail::send('emails.test_queue', $this->data, function($message) use($email) {      
            $message->to($email)->subject('Test Queue!');
        });
    }
}
