<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f4f8; color: #334155; line-height: 1.6; }
        .wrapper { max-width: 640px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); padding: 32px 24px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .header p { color: #cffafe; font-size: 14px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-top: 12px; }
        .body { padding: 28px 24px; }
        .greeting { font-size: 16px; margin-bottom: 16px; }
        .greeting strong { color: #0891b2; }
        .info-grid { display: table; width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 8px 12px; background: #f8fafc; font-size: 13px; color: #64748b; font-weight: 600; width: 40%; border: 1px solid #e2e8f0; }
        .info-value { display: table-cell; padding: 8px 12px; font-size: 13px; color: #1e293b; border: 1px solid #e2e8f0; }
        .section-title { font-size: 15px; font-weight: 700; color: #0f172a; margin: 24px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #0891b2; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table th { background: #f1f5f9; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        .items-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .items-table tr:last-child td { border-bottom: none; }
        .item-name { font-weight: 600; color: #1e293b; }
        .text-right { text-align: right; }
        .totals { background: #f8fafc; border-radius: 12px; padding: 16px; margin-top: 16px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .total-row.grand { border-top: 2px solid #0891b2; padding-top: 12px; margin-top: 8px; font-size: 18px; font-weight: 800; color: #0891b2; }
        .payment-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .payment-cod { background: #fef3c7; color: #92400e; }
        .payment-sepay { background: #dbeafe; color: #1e40af; }
        .footer { background: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
        .footer a { color: #0891b2; text-decoration: none; }
        @media (max-width: 480px) {
            .wrapper { padding: 12px 8px; }
            .header { padding: 24px 16px; }
            .body { padding: 20px 16px; }
            .info-label, .info-value { display: block; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            {{-- Header --}}
            <div class="header">
                <h1>{{ $siteName }}</h1>
                <p>Xác nhận đơn hàng thành công</p>
                <div class="badge">📦 #{{ $order->order_number }}</div>
            </div>

            {{-- Body --}}
            <div class="body">
                <p class="greeting">
                    Xin chào <strong>{{ $order->shipping_name }}</strong>,<br>
                    Cảm ơn bạn đã đặt hàng tại <strong>{{ $siteName }}</strong>! Đơn hàng của bạn đã được tiếp nhận.
                </p>

                {{-- Order Info --}}
                <div class="section-title">📋 Thông tin đơn hàng</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Mã đơn hàng</div>
                        <div class="info-value"><strong>#{{ $order->order_number }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Ngày đặt</div>
                        <div class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Thanh toán</div>
                        <div class="info-value">
                            @if($order->payment_method === 'cod')
                                <span class="payment-badge payment-cod">💵 Thanh toán khi nhận hàng</span>
                            @else
                                <span class="payment-badge payment-sepay">📱 Chuyển khoản QR (SePay)</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Trạng thái</div>
                        <div class="info-value">⏳ Đang xử lý</div>
                    </div>
                </div>

                {{-- Shipping Info --}}
                <div class="section-title">🚚 Thông tin giao hàng</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Người nhận</div>
                        <div class="info-value">{{ $order->shipping_name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Điện thoại</div>
                        <div class="info-value">{{ $order->shipping_phone }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Địa chỉ</div>
                        <div class="info-value">
                            {{ $order->shipping_address }}
                            @if($order->shipping_ward), {{ $order->shipping_ward }}@endif
                            @if($order->shipping_district), {{ $order->shipping_district }}@endif
                            @if($order->shipping_city), {{ $order->shipping_city }}@endif
                        </div>
                    </div>
                    @if($order->notes)
                    <div class="info-row">
                        <div class="info-label">Ghi chú</div>
                        <div class="info-value">{{ $order->notes }}</div>
                    </div>
                    @endif
                </div>

                {{-- Items --}}
                <div class="section-title">🛒 Chi tiết sản phẩm</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-right">SL</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td class="item-name">{{ $item->product->name ?? 'Sản phẩm' }}</td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                            <td class="text-right"><strong>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}₫</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="totals">
                    <div class="total-row">
                        <span>Tạm tính</span>
                        <span>{{ number_format($order->subtotal, 0, ',', '.') }}₫</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="total-row">
                        <span>Giảm giá</span>
                        <span style="color: #dc2626;">-{{ number_format($order->discount, 0, ',', '.') }}₫</span>
                    </div>
                    @endif
                    <div class="total-row">
                        <span>Phí vận chuyển</span>
                        <span>{{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . '₫' : 'Miễn phí' }}</span>
                    </div>
                    <div class="total-row grand">
                        <span>Tổng thanh toán</span>
                        <span>{{ number_format($order->total, 0, ',', '.') }}₫</span>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ:</p>
                @if($contactPhone)
                    <p>📞 <a href="tel:{{ $contactPhone }}">{{ $contactPhone }}</a></p>
                @endif
                @if($contactEmail)
                    <p>📧 <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
                @endif
                <p style="margin-top: 12px; font-size: 11px; color: #cbd5e1;">
                    © {{ date('Y') }} {{ $siteName }}. Email này được gửi tự động, vui lòng không trả lời.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
