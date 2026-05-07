<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\SmtpConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('id')->get()->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($request->input('settings') as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            if ($setting) {
                $value = $item['value'];

                // Don't overwrite password fields with empty values
                if ($setting->type === 'password' && (is_null($value) || $value === '')) {
                    continue;
                }

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $setting->update(['value' => $value]);
            }
        }

        Setting::clearCache();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Cập nhật cài đặt thành công');
    }

    /**
     * Send a test email to verify SMTP settings.
     */
    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            // Apply SMTP settings from database
            SmtpConfigService::apply();

            Mail::to($request->input('email'))->send(new TestMail());

            return back()->with('success', 'Email test đã được gửi thành công đến ' . $request->input('email'));
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $hint = '';

            // Provide Gmail-specific hints
            $smtpHost = Setting::get('smtp_host', '');
            if (str_contains($smtpHost, 'gmail')) {
                if (str_contains($message, 'Authentication') || str_contains($message, '535') || str_contains($message, 'Username and Password not accepted')) {
                    $hint = ' → Gmail yêu cầu dùng "App Password" (Mật khẩu ứng dụng), không phải mật khẩu tài khoản Google thường. Vào myaccount.google.com → Bảo mật → Mật khẩu ứng dụng để tạo.';
                } elseif (str_contains($message, 'Connection refused') || str_contains($message, 'Connection timed out')) {
                    $hint = ' → Kiểm tra SMTP Host: smtp.gmail.com, Port: 587, Encryption: tls. Đảm bảo server cho phép kết nối outbound port 587.';
                } elseif (str_contains($message, 'certificate') || str_contains($message, 'SSL')) {
                    $hint = ' → Thử đổi Encryption sang "tls" với Port 587, hoặc "ssl" với Port 465.';
                }
            }

            return back()->with('error', 'Gửi email thất bại: ' . $message . $hint);
        }
    }
}
