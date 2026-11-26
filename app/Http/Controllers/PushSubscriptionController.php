<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    private const PUSH_SERVICES = '#^https://([a-z0-9-]+\.)*(fcm\.googleapis\.com|android\.googleapis\.com|push\.services\.mozilla\.com|push\.apple\.com|notify\.windows\.com)(:443)?/#i';

    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH, function ($attr, $value, $fail) {
                // Server mengirim request ke alamat ini, jadi hanya layanan push resmi.
                if (! preg_match(self::PUSH_SERVICES, (string) $value)) {
                    $fail(__('Alamat notifikasi tidak dikenal.'));
                }
            }],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], $data['contentEncoding'] ?? null,
        );

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $request->user()->deletePushSubscription($request->validate(['endpoint' => ['required', 'string']])['endpoint']);

        return response()->noContent();
    }
}
