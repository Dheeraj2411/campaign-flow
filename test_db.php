<?php
echo "\n--- Contacts ---\n";
echo \App\Models\Contact::latest()->take(2)->get()->toJson(JSON_PRETTY_PRINT);
echo "\n--- Conversations ---\n";
echo \App\Models\Conversation::latest()->take(2)->get()->toJson(JSON_PRETTY_PRINT);
echo "\n--- Messages ---\n";
echo \App\Models\Message::latest()->take(2)->get()->toJson(JSON_PRETTY_PRINT);
echo "\n";
