<?php

namespace App\Services;

use App\Models\Contact;

class MessagePersonalizationService
{
    public function personalize(string $message, Contact $contact): string
    {
        $data = [
            'name' => $contact->name,
            'phone' => $contact->phone,
            'telegram_username' => $contact->telegram_username,
        ];

        $customAttributes = $contact->custom_attributes ?? [];
        if (is_array($customAttributes)) {
            $data = array_merge($data, $customAttributes);
        }

        return preg_replace_callback('/\{\{?(\w+)\}?\}/', function ($match) use ($data) {
            return $data[$match[1]] ?? $match[0];
        }, $message);
    }
}
