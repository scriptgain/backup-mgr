<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function edit()
    {
        return view('settings.notifications');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_email' => ['nullable', 'email', 'max:191'],
            'smtp_host' => ['nullable', 'string', 'max:191'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:191'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'mail_from' => ['nullable', 'email', 'max:191'],
        ]);

        Setting::put('notifications_enabled', $request->boolean('notifications_enabled') ? '1' : '0');
        foreach (['notify_email', 'smtp_host', 'smtp_port', 'smtp_username', 'mail_from'] as $k) {
            Setting::put($k, $data[$k] ?? '');
        }
        // Keep the stored SMTP password when left blank.
        if (! empty($data['smtp_password'])) {
            Setting::put('smtp_password', $data['smtp_password']);
        }

        return redirect()->route('settings.notifications.edit')->with('status', 'Notification settings saved.');
    }

    public function test(Request $request)
    {
        $to = Setting::get('notify_email');
        if (! $to) {
            return back()->with('status', 'Set a notification email first.');
        }
        try {
            Mail::raw('This is a test notification from ' . config('brand.name') . '. Email alerts are working.', function ($m) use ($to) {
                $m->to($to)->subject('[' . config('brand.name') . '] Test Notification');
            });

            return back()->with('status', "Test email sent to {$to}.");
        } catch (\Throwable $e) {
            return back()->with('status', 'Could not send: ' . $e->getMessage());
        }
    }
}
