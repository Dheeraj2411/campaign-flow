<?php
use Illuminate\Http\Request;
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::where('is_admin', true)->first();
auth()->login($user);

$request = Request::create('/billing', 'GET');
$response = $kernel->handle($request);
echo "Status: " . $response->status() . "\n";
echo "Content: " . substr($response->getContent(), 0, 500) . "\n";
