<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MessagePersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagePersonalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_interpolates_contact_variables_and_custom_attributes()
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_id' => $user->id, 'name' => 'Personalization workspace']);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => 'Alice',
            'phone' => '+1234567890',
            'tags' => ['lead'],
            'custom_attributes' => ['company' => 'Acme Corp'],
        ]);

        $service = new MessagePersonalizationService();

        $template = 'Hi {{name}}, your company is {{company}} and phone is {{phone}}.';
        $message = $service->personalize($template, $contact);

        $this->assertEquals('Hi Alice, your company is Acme Corp and phone is +1234567890.', $message);
    }
}
