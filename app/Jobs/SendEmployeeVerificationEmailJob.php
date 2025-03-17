<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\employeeRegistrationVerificationMail;
use Illuminate\Support\Facades\Mail;

class SendEmployeeVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $employee;
    public $verificationUrl;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($employee, $verificationUrl)
    {
        $this->employee = $employee;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Send the email
        Mail::to($this->employee['email'])->send(
            new employeeRegistrationVerificationMail($this->verificationUrl, $this->employee)
        );
    }
}
