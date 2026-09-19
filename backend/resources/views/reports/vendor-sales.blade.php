<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #212529; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 0; }
        .muted { color: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f8f8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }
        .value { font-size: 13px; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 10px; color: #6c757d; }
    </style>
</head>
<body>
    <h1>{{ $vendor->display_name }}</h1>
    <p class="muted">
        Sales report — completed bookings.
        Generated {{ $generatedAt->format('d M Y, H:i') }}.
        @if ($vendor->district) · {{ $vendor->district->name }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th>Orders</th>
                <th>Recorded value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['periods'] as $period)
                <tr>
                    <td>{{ $period['title'] }} <span class="muted">({{ $period['caption'] }})</span></td>
                    <td>{{ $period['count'] }}</td>
                    <td class="value">{{ config('app.currency_symbol', 'Rs. ') }}{{ number_format($period['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (count($report['recent']) > 0)
        <h2>Recent completed orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Completed</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['recent'] as $row)
                    <tr>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['completed_at']?->format('d M Y') ?? '—' }}</td>
                        <td>{{ config('app.currency_symbol', 'Rs. ') }}{{ number_format($row['value'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer">
        Settlement happens directly between you and the buyer. This platform
        takes no commission and holds no money; these figures are recorded for
        your own bookkeeping only.
    </p>
</body>
</html>
