<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Http\Request;

class TestBillingRoute extends Command
{
    protected $signature = 'test:billing';
    protected $description = 'Test the billing route';

    public function handle()
    {
        $user = User::where('email', 'admin@campaignflow.com')->first();
        auth()->login($user);

        $request = Request::create('/billing', 'GET');
        $response = app()->handle($request);

        $this->info("Status: " . $response->status());
        $this->info("Content: " . substr($response->getContent(), 0, 500));
    }
}
