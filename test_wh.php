<?php

use Illuminate\Support\Facades\Request;

// 1. Test Telegram
$slug = 'admin-user-69b6292cccaff';
$tgPayload = '{
    "update_id": 12345,
    "message": {
        "message_id": 1,
        "from": { "id": 987, "first_name": "Test", "username": "testbot" },
        "chat": { "id": 987, "type": "private" },
        "date": 1709420000,
        "text": "Hello Telegram"
    }
}';
$tgReq = Request::create("/telegram/webhook/{$slug}", 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json'
], $tgPayload);
echo "Telegram Response: " . app()->handle($tgReq)->getContent() . "\n";

// 2. Test WhatsApp
$waPayload = '{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "id": "111111111111111",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "metadata": {
              "display_phone_number": "15551234567",
              "phone_number_id": "937748686098218"
            },
            "contacts": [
              {
                "profile": {
                  "name": "Test Mock WhatsApp User"
                },
                "wa_id": "11234567890"
              }
            ],
            "messages": [
              {
                "from": "11234567890",
                "id": "wamid.HBgLMTIxMzI0MzU0NjYVAgASGBQzQTJBMTBFRTQ5QkA5NDk3A1",
                "timestamp": "1709420000",
                "text": {
                  "body": "Hello WhatsApp"
                },
                "type": "text"
              }
            ]
          },
          "field": "messages"
        }
      ]
    }
  ]
}';
$secret = config('services.whatsapp.app_secret', 'campaignflow_app_secret');
$signature = 'sha256=' . hash_hmac('sha256', $waPayload, $secret);

$waReq = Request::create("/whatsapp/webhook", 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_X_Hub_Signature_256' => $signature
], $waPayload);
echo "WhatsApp Response: " . app()->handle($waReq)->getContent() . "\n";

