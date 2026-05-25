<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Summary Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 24px 32px;
            background: #fff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-bottom: 2px solid #1976d2;
        }
        .header-table td { vertical-align: middle; padding-bottom: 10px; }
        .header-logo { width: 36px; text-align: center; padding: 0 4px; }
        .header-logo img { width: 60px; height: auto; }
        .header-center { text-align: center; padding: 0 6px; }
        .gov-title { font-size: 14px; font-weight: 700; color: #111; letter-spacing: 0.5px; }
        .sub-title  { font-size: 10.5px; color: #555; margin-top: 2px; }
        .doc-title  { font-size: 13px; font-weight: 600; color: #222; margin-top: 4px; }

        .meta-table {
            width: 100%; border-collapse: collapse; margin-bottom: 16px;
            background: #f0f4fb; border-radius: 4px;
        }
        .meta-table td { padding: 7px 12px; font-size: 10.5px; color: #444; width: 50%; }
        .label { font-weight: 600; color: #1976d2; }

        h2 {
            font-size: 11.5px; font-weight: 700; color: #1976d2;
            margin: 16px 0 8px; padding-bottom: 4px;
            border-bottom: 1px solid #d0d9e8;
            text-transform: uppercase; letter-spacing: 0.4px;
        }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10.5px; }
        .data-table th {
            background: #1976d2; color: #fff;
            padding: 7px 10px; text-align: left;
            font-weight: 600; font-size: 10px; letter-spacing: 0.3px;
        }
        .data-table th.tc, .data-table td.tc { text-align: center; }
        .data-table th.tr, .data-table td.tr { text-align: right; }
        .data-table td { padding: 6px 10px; border-bottom: 1px solid #e8edf4; color: #333; }
        .data-table tbody tr:nth-child(even) { background: #f7f9fd; }

        .footer {
            margin-top: 28px; padding-top: 12px;
            border-top: 1px solid #e0e0e0;
            text-align: center; font-size: 9.5px; color: #999; line-height: 1.6;
        }
    </style>
</head>
<body>

@php
    $bagongPilipinasPath = base_path('public/images/Bagong-Pilipinas.png');
    $spSealPath = base_path('public/images/SP_Seal.png.png');
    $bagongPilipinasData = '';
    $spSealData = '';

    try {
        if (file_exists($bagongPilipinasPath)) {
            $bagongPilipinasData = 'data:image/png;base64,' . base64_encode(file_get_contents($bagongPilipinasPath));
        }
    } catch (Exception $e) {
        $bagongPilipinasData = '';
    }

    try {
        if (file_exists($spSealPath)) {
            $spSealData = 'data:image/png;base64,' . base64_encode(file_get_contents($spSealPath));
        }
    } catch (Exception $e) {
        $spSealData = '';
    }
@endphp

    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($bagongPilipinasData))
                    <img src="{{ $bagongPilipinasData }}" alt="Bagong Pilipinas" style="width:60px;height:auto;">
                @else
                    <div style="width:60px;height:60px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;">LOGO</div>
                @endif
            </td>
            <td class="header-center">
                <div class="gov-title">Province of La Union</div>
                <div class="sub-title">Office of Sangguniang Panlalawigan</div>
                <div class="doc-title">Admin Inventory Summary</div>
            </td>
            <td class="header-logo">
                @if(!empty($spSealData))
                    <img src="{{ $spSealData }}" alt="SP Seal" style="width:60px;height:auto;">
                @else
                    <div style="width:60px;height:60px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;">SEAL</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td><span class="label">Generated:</span> {{ now()->format('F d, Y h:i A') }}</td>
        </tr>
        @if(request('date_from') || request('date_to'))
        <tr>
            <td><span class="label">Date Range:</span>
                @if(request('date_from')){{ \Carbon\Carbon::parse(request('date_from'))->format('M d, Y') }}@endif
                {{ request('date_from') && request('date_to') ? ' to ' : '' }}
                @if(request('date_to')){{ \Carbon\Carbon::parse(request('date_to'))->format('M d, Y') }}@endif
            </td>
        </tr>
        @endif
        @if(!empty($office))
        <tr>
            <td><span class="label">Office:</span> {{ $office }}</td>
        </tr>
        @endif
    </table>

    <h2>Summary Inventory</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="tr">Starting Balance</th>
                <th class="tr">Inbound</th>
                <th class="tr">Sum</th>
                <th class="tr">Outbound</th>
                <th class="tr">Ending Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockSummaries as $row)
                <tr>
                    <td>{{ $row['item'] }} @if($row['id_no']) ({{ $row['id_no'] }})@endif</td>
                    <td class="tr">{{ number_format($row['starting_balance']) }}</td>
                    <td class="tr">{{ number_format($row['inbound']) }}</td>
                    <td class="tr">{{ number_format($row['sum']) }}</td>
                    <td class="tr">{{ number_format($row['outbound']) }}</td>
                    <td class="tr">{{ number_format($row['ending_balance']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="tc" style="padding:16px; font-style:italic; color:#999;">No stock records found for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This report was generated from the admin summary section of the inventory system.
    </div>
</body>
</html>
