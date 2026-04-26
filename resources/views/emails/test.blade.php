<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - {{ $siteName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f4f8; color: #334155; line-height: 1.6; }
        .wrapper { max-width: 520px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); text-align: center; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 24px; }
        .header .icon { font-size: 48px; margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; }
        .header p { color: #d1fae5; font-size: 14px; margin-top: 8px; }
        .body { padding: 32px 24px; }
        .body h2 { font-size: 18px; color: #059669; margin-bottom: 12px; }
        .body p { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin: 20px 0; text-align: left; }
        .info-box .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .info-box .label { color: #64748b; }
        .info-box .value { color: #1e293b; font-weight: 600; }
        .footer { background: #f8fafc; padding: 16px 24px; border-top: 1px solid #e2e8f0; }
        .footer p { font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="icon">✅</div>
                <h1>{{ $siteName }}</h1>
                <p>Cấu hình SMTP hoạt động tốt!</p>
            </div>
            <div class="body">
                <h2>Email test thành công!</h2>
                <p>Nếu bạn nhận được email này, hệ thống email SMTP đã được cấu hình đúng.</p>
                <div class="info-box">
                    <div class="row">
                        <span class="label">Thời gian gửi</span>
                        <span class="value">{{ now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Hệ thống</span>
                        <span class="value">{{ $siteName }}</span>
                    </div>
                </div>
                <p>Bạn có thể quay lại trang quản trị và tiếp tục cấu hình.</p>
            </div>
            <div class="footer">
                <p>© {{ date('Y') }} {{ $siteName }}. Email test tự động.</p>
            </div>
        </div>
    </div>
</body>
</html>
