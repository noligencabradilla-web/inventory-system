<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reports PDF</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 24px 32px;
            background: #fff;
        }

        /* ── HEADER ── */
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

        /* ── META ── */
        .meta-table {
            width: 100%; border-collapse: collapse; margin-bottom: 16px;
            background: #f0f4fb; border-radius: 4px;
        }
        .meta-table td { padding: 7px 12px; font-size: 10.5px; color: #444; width: 50%; }
        .label { font-weight: 600; color: #1976d2; }

        /* ── SECTION TITLES ── */
        h2 {
            font-size: 11.5px; font-weight: 700; color: #1976d2;
            margin: 16px 0 8px; padding-bottom: 4px;
            border-bottom: 1px solid #d0d9e8;
            text-transform: uppercase; letter-spacing: 0.4px;
        }

        /* ── SUMMARY STATS ── */
        .summary-table {
            width: 100%; border-collapse: collapse; margin-bottom: 20px;
        }
        .summary-table td {
            padding: 6px 0;
            font-size: 11px;
            color: #333;
        }
        .summary-table td.summary-label {
            font-weight: 600;
            width: 200px;
            color: #444;
        }
        .summary-table td.summary-value {
            color: #111;
        }

        /* ── DATA TABLES ── */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10.5px; }
        .data-table th {
            background: #1976d2; color: #fff;
            padding: 7px 10px; text-align: left;
            font-weight: 600; font-size: 10px; letter-spacing: 0.3px;
        }
        .data-table th.tr, .data-table td.tr { text-align: right; }
        .data-table th.tc, .data-table td.tc { text-align: center; }
        .data-table td { padding: 6px 10px; border-bottom: 1px solid #e8edf4; color: #333; }
        .data-table tbody tr:nth-child(even) { background: #f7f9fd; }

        .text-green  { color: #2e7d32; font-weight: 600; }
        .text-red    { color: #c62828; font-weight: 600; }

        /* ── FOOTER ── */
        .footer {
            margin-top: 28px; padding-top: 12px;
            border-top: 1px solid #e0e0e0;
            text-align: center; font-size: 9.5px; color: #999; line-height: 1.6;
        }
    </style>
</head>
<body>

    {{-- Base64 encoded images for reliable PDF display --}}
@php
    // Get image paths
    $bagongPilipinasPath = base_path('public/images/Bagong-Pilipinas.png');
    $spSealPath = base_path('public/images/SP_Seal.png.png');
    
    // Debug: Check if files exist
    $bagongExists = file_exists($bagongPilipinasPath);
    $spSealExists = file_exists($spSealPath);
    
    // Encode images to base64
    $bagongPilipinasData = '';
    $spSealData = '';
    
    try {
        if ($bagongExists) {
            $imageData = file_get_contents($bagongPilipinasPath);
            if ($imageData !== false) {
                $bagongPilipinasData = 'data:image/png;base64,' . base64_encode($imageData);
            }
        }
    } catch (Exception $e) {
        // Handle error silently
    }
    
    try {
        if ($spSealExists) {
            $imageData = file_get_contents($spSealPath);
            if ($imageData !== false) {
                $spSealData = 'data:image/png;base64,' . base64_encode($imageData);
            }
        }
    } catch (Exception $e) {
        // Handle error silently
    }
@endphp

    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($bagongPilipinasData))
                    <img src="{{ $bagongPilipinasData }}" alt="Bagong Pilipinas" style="width:60px;height:auto;">
                @else
                    <div style="width:60px;height:60px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;">
                        LOGO
                    </div>
                @endif
            </td>
            <td class="header-center">
                <div class="gov-title">Province of La Union</div>
                <div class="sub-title">Office of Sangguniang Panlalawigan</div>
                <div class="doc-title">Inventory Report</div>
            </td>
            <td class="header-logo">
                @if(!empty($spSealData))
                    <img src="{{ $spSealData }}" alt="SP Seal" style="width:60px;height:auto;">
                @else
                    <div style="width:60px;height:60px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;">
                        SEAL
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- META --}}
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
    </table>

    {{-- ── SUMMARY STATS ── --}}
    <h2>Summary</h2>
    <table class="summary-table">
        <tr>
            <td class="summary-label">Received:</td>
            <td class="summary-value">{{ $mainInventoryTotals['total_received'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="summary-label">Distributed:</td>
            <td class="summary-value">{{ $mainInventoryTotals['total_distributed'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="summary-label">Available:</td>
            <td class="summary-value">{{ $mainInventoryTotals['total_available'] ?? 0 }}</td>
        </tr>
    </table>
    <br><br><br>
    {{-- ── MEMBER USAGE DETAILS ── --}}
    <h2>Member Usage Details</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Member Name</th>
                <th>Email</th>
                <th class="tc">Distributed Items</th>
                <th class="tc">Items Left</th>
                <th class="tc">Used Items</th>
            </tr>
        </thead>
        <tbody>
            @forelse($memberReports as $member)
                <tr>
                    <td>{{ $member['name'] }}</td>
                    <td>{{ $member['email'] }}</td>
                    <td class="tc">{{ $member['distributed_items'] }}</td>
                    <td class="tc {{ $member['available_items'] > 0 ? 'text-green' : '' }}">
                        {{ $member['available_items'] }}
                    </td>
                    <td class="tc {{ $member['used_items'] > 0 ? 'text-red' : '' }}">
                        {{ $member['used_items'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="tc" style="padding:16px; font-style:italic; color:#999;">
                        No member usage data available for selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── AVAILABLE ITEMS DETAILS ── --}}
    <h2>Available Items Details</h2>
    
    {{-- Client Available Items --}}
    <h3 style="font-size: 11px; font-weight: 600; color: #1976d2; margin: 12px 0 6px;">{{ $user->office ?? 'Client' }} Available Items</h3>
    @if(isset($approvedInventory) && $approvedInventory->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="tc">Total Received</th>
                    <th class="tc">Distributed</th>
                    <th class="tc">Available</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvedInventory as $item)
                    <tr>
                        <td>{{ $item->stock->description ?? 'Unknown Item' }}</td>
                        <td class="tc">{{ $item->approved_qty ?? 0 }}</td>
                        <td class="tc">{{ $item->distributed_qty ?? 0 }}</td>
                        <td class="tc {{ ($item->my_inventory ?? 0) > 0 ? 'text-green' : '' }}">
                            {{ $item->my_inventory ?? 0 }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tr>
                <td colspan="4" class="tc" style="padding:16px; font-style:italic; color:#999;">
                    No client inventory items available
                </td>
            </tr>
        </table>
    @endif
    
    <br>
    
    {{-- Members Available Items --}}
    <h3 style="font-size: 11px; font-weight: 600; color: #059669; margin: 12px 0 6px;">Members Available Items</h3>
    @if(isset($clientMembers) && $clientMembers->isNotEmpty())
        @foreach($clientMembers as $member)
            @php
                $memberItems = collect();
                
                // Aggregate items by name to avoid duplicates
                $aggregatedItems = [];
                
                // Add regular distribution items
                if($member->distributions->isNotEmpty()) {
                    foreach($member->distributions as $distribution) {
                        $availableQty = $distribution->distributed_qty - ($distribution->used_qty ?? 0);
                        if($availableQty > 0) {
                            $itemName = $distribution->stockRequestItem->stock->description ?? 'Unknown Item';
                            
                            if(isset($aggregatedItems[$itemName])) {
                                $aggregatedItems[$itemName]['distributed_qty'] += $distribution->distributed_qty;
                                $aggregatedItems[$itemName]['used_qty'] += $distribution->used_qty ?? 0;
                                $aggregatedItems[$itemName]['available_qty'] += $availableQty;
                            } else {
                                $aggregatedItems[$itemName] = [
                                    'description' => $itemName,
                                    'distributed_qty' => $distribution->distributed_qty,
                                    'used_qty' => $distribution->used_qty ?? 0,
                                    'available_qty' => $availableQty
                                ];
                            }
                        }
                    }
                }
                
                // Add direct deduction items
                if($member->directDeductions->isNotEmpty()) {
                    foreach($member->directDeductions as $deduction) {
                        if($deduction->stock_request_item_id === null) { // Only direct request items
                            // Extract clean item name from reason
                            $itemName = $deduction->reason ?? 'Direct Request Item';
                            
                            // Remove prefixes to get clean item name
                            if(str_contains($itemName, 'Member distribution - ')) {
                                $itemName = str_replace('Member distribution - ', '', $itemName);
                            }
                            if(str_contains($itemName, 'Member inventory deduction - ')) {
                                $itemName = str_replace('Member inventory deduction - ', '', $itemName);
                            }
                            if(str_contains($itemName, 'Used from direct request - ')) {
                                $itemName = str_replace('Used from direct request - ', '', $itemName);
                            }
                            
                            if(isset($aggregatedItems[$itemName])) {
                                $aggregatedItems[$itemName]['distributed_qty'] += $deduction->deducted_qty;
                                $aggregatedItems[$itemName]['used_qty'] += 0; // Direct items are not used yet
                                $aggregatedItems[$itemName]['available_qty'] += $deduction->deducted_qty;
                            } else {
                                $aggregatedItems[$itemName] = [
                                    'description' => $itemName,
                                    'distributed_qty' => $deduction->deducted_qty,
                                    'used_qty' => 0, // Direct items are not used yet
                                    'available_qty' => $deduction->deducted_qty
                                ];
                            }
                        }
                    }
                }
                
                // Convert aggregated items back to collection
                foreach($aggregatedItems as $item) {
                    $memberItems->push((object)$item);
                }
            @endphp
            
            @if($memberItems->isNotEmpty())
                <div style="margin-bottom: 16px;">
                    <h4 style="font-size: 10px; font-weight: 600; color: #059669; margin: 8px 0 4px; padding: 4px 8px; background: #f0fdf4; border-radius: 4px;">
                        {{ $member->name }} ({{ $member->email }})
                    </h4>
                    <table class="data-table" style="font-size: 9px;">
                        <thead>
                            <tr style="background: #ecfdf5;">
                                <th style="padding: 4px; font-size: 9px;">Item</th>
                                <th class="tc" style="padding: 4px; font-size: 9px;">Distributed</th>
                                <th class="tc" style="padding: 4px; font-size: 9px;">Used</th>
                                <th class="tc" style="padding: 4px; font-size: 9px;">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($memberItems as $item)
                                <tr>
                                    <td style="padding: 3px 4px;">{{ $item->description }}</td>
                                    <td class="tc" style="padding: 3px 4px;">{{ $item->distributed_qty }}</td>
                                    <td class="tc" style="padding: 3px 4px;">{{ $item->used_qty }}</td>
                                    <td class="tc {{ $item->available_qty > 0 ? 'text-green' : '' }}" style="padding: 3px 4px;">{{ $item->available_qty }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    @else
        <table class="data-table">
            <tr>
                <td colspan="4" class="tc" style="padding:16px; font-style:italic; color:#999;">
                    No member inventory items available
                </td>
            </tr>
        </table>
    @endif

    {{-- ── FOOTER ── --}}
    <div class="footer">
        
    </div>

</body>
</html>
