@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Client Portal';
@endphp

@section('sidebar')
    @include('client.sidebar')
@endsection

@section('content')
    {{-- Quick Actions - Moved to top --}}
    <div style="margin-bottom:20px;">
        <h2 style="margin:0 0 8px 0;">Quick Actions</h2>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:12px; margin-bottom:24px;">
        <div style="padding:14px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.02); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,.25)'; this.style.borderColor='rgba(59,130,246,.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0,0,0,.06)'; this.style.borderColor='rgba(255,255,255,.08)';">
            <div style="color:#9ca3af; font-size:12px;">Quick Action</div>
            <div style="font-weight:700; margin-top:6px;">View Available Stocks</div>
            <p style="color:#9ca3af; font-size:12px; margin:10px 0 0;">
                Browse items and request.
            </p>
            <a href="{{ route('client.stocks') }}" style="display:inline-block; margin-top:10px; color:#3b82f6; text-decoration:none; transition: all 0.3s ease;" onmouseover="this.style.opacity='0.7'; this.style.transform='translateX(4px)';" onmouseout="this.style.opacity='1'; this.style.transform='translateX(0)';">
                Open →
            </a>
        </div>

        <div style="padding:14px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.02); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,.25)'; this.style.borderColor='rgba(34,197,94,.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0,0,0,.06)'; this.style.borderColor='rgba(255,255,255,.08)';">
            <div style="color:#9ca3af; font-size:12px;">Track</div>
            <div style="font-weight:700; margin-top:6px;">My Requests</div>
            <p style="color:#9ca3af; font-size:12px; margin:10px 0 0;">
                Check request status.
            </p>
            <a href="{{ route('client.requests') }}" style="display:inline-block; margin-top:10px; color:#22c55e; text-decoration:none; transition: all 0.3s ease;" onmouseover="this.style.opacity='0.7'; this.style.transform='translateX(4px)';" onmouseout="this.style.opacity='1'; this.style.transform='translateX(0)';">
                Track →
            </a>
        </div>

        <div style="padding:14px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.02); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,.25)'; this.style.borderColor='rgba(234,179,8,.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0,0,0,.06)'; this.style.borderColor='rgba(255,255,255,.08)';">
            <div style="color:#9ca3af; font-size:12px;">Reminder</div>
            <div style="font-weight:700; margin-top:6px;">Confirm Received</div>
            <p style="color:#9ca3af; font-size:12px; margin:10px 0 0;">
                If your status is <b>Ready To Receive</b>, you can confirm once you receive it.
            </p>
            <a href="{{ route('client.requests', ['tab' => 'on_delivery']) }}" style="display:inline-block; margin-top:10px; color:#eab308; text-decoration:none; transition: all 0.3s ease;" onmouseover="this.style.opacity='0.7'; this.style.transform='translateX(4px)';" onmouseout="this.style.opacity='1'; this.style.transform='translateX(0)';">
                Go to Ready To Receive →
            </a>
        </div>
    </div>

    @if($isSubaccount ?? false)
        {{-- Subaccount Dashboard --}}
        <div style="margin-bottom:20px;">
            <h2 style="margin:0 0 8px 0;">My Distributed Inventory</h2>
            <div style="color:#6b7280; font-size:14px;">Items allocated to your subaccount</div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
            <div style="padding:16px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                <div style="color:#6b7280; font-size:12px; font-weight:600;">Total Items</div>
                <div style="font-size:24px; font-weight:700; color:#111827; margin-top:4px;">{{ $totalItems }}</div>
            </div>
            <div style="padding:16px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                <div style="color:#6b7280; font-size:12px; font-weight:600;">Used Items</div>
                <div style="font-size:24px; font-weight:700; color:#111827; margin-top:4px;">{{ $usedItems }}</div>
            </div>
            <div style="padding:16px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                <div style="color:#6b7280; font-size:12px; font-weight:600;">Available Items</div>
                <div style="font-size:24px; font-weight:700; color:#111827; margin-top:4px;">{{ $availableItems }}</div>
            </div>
                    </div>

        @if($distributedItems->count() > 0)
            <div style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                <div style="padding:16px; background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <h3 style="margin:0; font-size:16px;">Distributed Items</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f9fafb;">
                                <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Item</th>
                                <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Allocated Qty</th>
                                <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Used Qty</th>
                                <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Available Qty</th>
                                <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($distributedItems as $allocation)
                                <tr>
                                    <td style="padding:12px; border-bottom:1px solid #e5e7eb;">
                                        <div style="font-weight:600;">{{ $allocation->stockRequestItem->stock->id_no }}</div>
                                        <div style="color:#6b7280; font-size:12px;">{{ $allocation->stockRequestItem->stock->description }}</div>
                                    </td>
                                    <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ $allocation->allocated_qty }}</td>
                                    <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ $allocation->used_qty }}</td>
                                    <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ $allocation->allocated_qty - $allocation->used_qty }}</td>
                                    <td style="padding:12px; border-bottom:1px solid #e5e7eb;">
                                        @if($allocation->members->count() > 0)
                                            {{ $allocation->members->pluck('name')->join(', ') }}
                                        @else
                                            No members assigned
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div style="text-align:center; padding:40px; color:#6b7280;">
                No items have been distributed to your subaccount yet.
            </div>
        @endif
    @else

        {{-- Member Performance Analytics Section --}}
        <div style="margin-top:32px;">
            <!-- <h2 style="margin:0 0 8px 0;">Member Performance Analytics</h2> -->
            <!-- <div style="color:#6b7280; font-size:14px;">Monitor member activity and usage patterns</div> -->
        </div>

        @if(isset($memberPerformance))
            <div style="background:white; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.1); overflow:hidden; margin-bottom:32px;">
                <div style="padding:20px; background:linear-gradient(135deg, #f8fafc, #e2e8f0); border-bottom:1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                        <h3 style="margin:0; font-size:18px; color:#1e293b; display:flex; align-items:center; gap:8px;">
                            <span></span> Member Performance Analytics
                        </h3>
                        <div style="display:flex; gap:8px;">
                            <button onclick="showPerformanceChart('frequent')" id="frequentBtn" class="performance-btn active" style="padding:8px 16px; border:1px solid #3b82f6; background:#3b82f6; color:white; border-radius:8px; font-size:14px; cursor:pointer; transition:all 0.2s ease;">
                                Request
                            </button>
                            <button onclick="showPerformanceChart('usage')" id="usageBtn" class="performance-btn" style="padding:8px 16px; border:1px solid #e2e8f0; background:white; color:#64748b; border-radius:8px; font-size:14px; cursor:pointer; transition:all 0.2s ease;">
                                Usage
                            </button>
                        </div>
                    </div>
                </div>
                
                <div style="padding:24px;">
                    {{-- Most Frequent Chart --}}
                    <div id="frequentChart" class="performance-chart">
                        <h4 style="margin:0 0 20px; color:#374151; font-size:16px;">Most Frequent Requestors</h4>
                        @if($memberPerformance['most_frequent_requestors']->count() > 0)
                            <div style="display:grid; gap:12px;">
                                @foreach($memberPerformance['most_frequent_requestors'] as $index => $memberData)
                                    @php
                                        $maxRequests = $memberPerformance['most_frequent_requestors']->first()['request_count'];
                                        $barWidth = $maxRequests > 0 ? ($memberData['request_count'] / $maxRequests) * 100 : 0;
                                    @endphp
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="min-width:120px; font-size:13px; color:#374151; font-weight:500; text-align:right;">
                                            {{ $memberData['member']->name }}
                                        </div>
                                        <div style="flex:1; background:#f1f5f9; border-radius:6px; height:32px; position:relative; overflow:hidden;">
                                            <div style="height:100%; background:linear-gradient(90deg, #3b82f6, #1d4ed8); width:{{ $barWidth }}%; transition:width 0.5s ease; display:flex; align-items:center; padding:0 8px;">
                                                <span style="color:white; font-size:12px; font-weight:600;">{{ $memberData['request_count'] }}</span>
                                            </div>
                                        </div>
                                        <div style="min-width:60px; font-size:12px; color:#64748b; text-align:left;">
                                            {{ $memberData['total_approved_qty'] }} items
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align:center; padding:40px; color:#64748b;">
                                <div style="font-size:48px; margin-bottom:16px;">📋</div>
                                <div style="font-size:16px;">No request data available</div>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Usage Chart --}}
                    <div id="usageChart" class="performance-chart" style="display:none;">
                        <h4 style="margin:0 0 20px; color:#374151; font-size:16px;">Heaviest Users by Quantity</h4>
                        @if($memberPerformance['heaviest_users']->count() > 0)
                            <div style="display:grid; gap:12px;">
                                @foreach($memberPerformance['heaviest_users'] as $index => $memberData)
                                    @php
                                        $maxUsage = $memberPerformance['heaviest_users']->first()['total_used_qty'];
                                        $barWidth = $maxUsage > 0 ? ($memberData['total_used_qty'] / $maxUsage) * 100 : 0;
                                    @endphp
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="min-width:120px; font-size:13px; color:#374151; font-weight:500; text-align:right;">
                                            {{ $memberData['member']->name }}
                                        </div>
                                        <div style="flex:1; background:#fef3c7; border-radius:6px; height:32px; position:relative; overflow:hidden;">
                                            <div style="height:100%; background:linear-gradient(90deg, #f59e0b, #d97706); width:{{ $barWidth }}%; transition:width 0.5s ease; display:flex; align-items:center; padding:0 8px;">
                                                <span style="color:white; font-size:12px; font-weight:600;">{{ $memberData['total_used_qty'] }}</span>
                                            </div>
                                        </div>
                                                                            </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align:center; padding:40px; color:#78350f;">
                                <div style="font-size:48px; margin-bottom:16px;">⚖️</div>
                                <div style="font-size:16px;">No usage data available</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if(false)
        {{-- Direct Client Members Section --}}
        <div style="margin-top:32px;">
            <h2 style="margin:0 0 8px 0;">Member inventory overview</h2>
            <div style="color:#6b7280; font-size:14px;">Items distributed to members</div>
        </div>

        @if(isset($clientMembers) && $clientMembers->count() > 0)
            <div style="border-radius:16px; background:linear-gradient(135deg, #ffffff, #f0fdf4); box-shadow:0 4px 12px rgba(34,197,94,0.08); padding:20px; margin-bottom:20px;">
                <div style="display:grid; gap:16px;">
                    @foreach($clientMembers as $member)
                        @php
                            // Regular distributions
                            $distributionCount = $member->distributions->count();
                            $distributedQty = $member->distributions->sum('distributed_qty');
                            $usedQty = Schema::hasColumn('client_member_distributions', 'used_qty') ? $member->distributions->sum('used_qty') : 0;
                            
                            // Direct request items (original ones only, not usage records)
                            $directDeductions = $member->directDeductions->filter(function ($deduction) {
                                return $deduction->stock_request_item_id === null && !str_contains($deduction->reason ?? '', 'Used from direct request');
                            });
                            $directDistributedQty = $directDeductions->sum('deducted_qty');
                            $directUsedQty = $member->directDeductions->filter(function ($deduction) {
                                return str_contains($deduction->reason ?? '', 'Used from direct request');
                            })->sum('deducted_qty');
                            
                            // Combined totals
                            $totalDistributedQty = $distributedQty + $directDistributedQty;
                            $totalUsedQty = $usedQty + $directUsedQty;
                            $availableQty = $totalDistributedQty - $totalUsedQty;
                            $totalUsedValue = 0;
                            $totalAvailableValue = 0;
                            
                            foreach($member->distributions as $distribution) {
                                $usedQtyItem = $distribution->used_qty ?? 0;
                                $availableQtyItem = $distribution->distributed_qty - $usedQtyItem;
                            }
                            
                            $memberId = 'direct_member_' . $member->id;
                        @endphp
                        
                        <div style="background:#f8fafc; border-radius:8px; margin-bottom:12px; overflow:hidden; border-left:3px solid #16a34a;">
                            <!-- Member Header with Summary -->
                            <div style="padding:12px 16px; background:#ffffff; cursor:pointer; transition:background-color 0.2s ease;" onclick="toggleMemberCard('{{ $memberId }}')" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='#ffffff'">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span id="toggle_{{ $memberId }}" style="font-size:12px; color:#16a34a; transition: transform 0.3s ease;">&#9656;</span>
                                        <div>
                                            <h3 style="margin:0; font-size:14px; color:#334155; font-weight:600;">{{ $member->name }}</h3>
                                            <div style="color:#64748b; font-size:12px; margin-top:2px;">{{ $member->email ?? 'No email provided' }}</div>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:12px; text-align:right;">
                                        <div style="background:#fef3c7; padding:6px 10px; border-radius:6px;">
                                            <div style="font-size:9px; color:#92400e; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Used</div>
                                            <div style="font-size:13px; font-weight:700; color:#92400e; margin-top:2px;">{{ $totalUsedQty }}</div>
                                        </div>
                                        <div style="background:#d1fae5; padding:6px 10px; border-radius:6px;">
                                            <div style="font-size:9px; color:#065f46; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Available</div>
                                            <div style="font-size:13px; font-weight:700; color:#065f46; margin-top:2px;">{{ $availableQty }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible Items Table -->
                            <div id="content_{{ $memberId }}" style="padding:16px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                                @if($member->distributions->count() > 0 || $directDeductions->count() > 0)
                                    <div style="overflow-x:auto; border-radius:12px; box-shadow:0 4px 12px rgba(34,197,94,0.1);">
                                        <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                            <thead>
                                                <tr>
                                                    <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #16a34a; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Item</th>
                                                    <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #16a34a; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Unit</th>
                                                    <th style="padding:12px 10px; text-align:center; border-bottom:2px solid #16a34a; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Distributed</th>
                                                    <th style="padding:12px 10px; text-align:center; border-bottom:2px solid #16a34a; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Available</th>
                                                    <th style="padding:12px 10px; text-align:center; border-bottom:2px solid #16a34a; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Used</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($member->distributions as $distribution)
                                                    @php
                                                        $usedQtyItem = $distribution->used_qty ?? 0;
                                                        $availableQtyItem = $distribution->distributed_qty - $usedQtyItem;
                                                        $totalValueItem = $distribution->distributed_qty * ($distribution->stockRequestItem->stock->price ?? 0);
                                                    @endphp
                                                    <tr style="border-bottom:1px solid #dcfce7; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                                        <td style="padding:14px 10px; border-bottom:1px solid #dcfce7;">
                                                            <div style="font-weight:700; color:#16a34a; font-size:14px;">{{ $distribution->stockRequestItem->stock->id_no }}</div>
                                                            <div style="color:#64748b; font-size:11px; margin-top:3px;">{{ $distribution->stockRequestItem->stock->description }}</div>
                                                        </td>
                                                        <td style="padding:14px 10px; border-bottom:1px solid #dcfce7; color:#475569; font-weight:600;">{{ $distribution->stockRequestItem->stock->unit }}</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#16a34a; font-weight:700;">{{ $distribution->distributed_qty }}</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#059669; font-weight:700; background:linear-gradient(135deg, #ecfdf5, #d1fae5);">{{ $availableQtyItem }}</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#ea580c; font-weight:700; background:linear-gradient(135deg, #fff7ed, #fed7aa);">{{ $usedQtyItem }}</td>
                                                    </tr>
                                                @endforeach
                                                
                                                @foreach($directDeductions as $deduction)
                                                    <tr style="border-bottom:1px solid #dcfce7; background:linear-gradient(135deg, #f0fdf4, #dcfce7);">
                                                        <td style="padding:14px 10px; border-bottom:1px solid #dcfce7;">
                                                            <div style="font-weight:700; color:#16a34a; font-size:14px;">Direct</div>
                                                            <div style="color:#64748b; font-size:11px; margin-top:3px;">{{ $deduction->reason ?? 'Direct Request Item' }}</div>
                                                        </td>
                                                        <td style="padding:14px 10px; border-bottom:1px solid #dcfce7; color:#475569; font-weight:600;">N/A</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#16a34a; font-weight:700;">{{ $deduction->deducted_qty }}</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#059669; font-weight:700; background:linear-gradient(135deg, #ecfdf5, #d1fae5);">{{ $deduction->deducted_qty }}</td>
                                                        <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #dcfce7; color:#ea580c; font-weight:700; background:linear-gradient(135deg, #fff7ed, #fed7aa);">0</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div style="text-align:center; padding:32px; color:#64748b; background:linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius:12px; border:2px dashed #16a34a;">
                                        <div style="font-size:32px; margin-bottom:12px; color:#16a34a;">&#128196;</div>
                                        <div style="font-size:16px; font-weight:600; color:#16a34a;">No items assigned to this member</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div style="text-align:center; padding:48px; color:#6b7280; background:#f9fafb; border-radius:16px; border:1px solid #e5e7eb;">
                <div style="font-size:48px; margin-bottom:16px;">&#128100;</div>
                <div style="font-size:18px; font-weight:600; margin-bottom:8px;">No Direct Members Found</div>
                <div style="font-size:14px;">No direct client members have been created yet.</div>
            </div>
        @endif
        @endif
    @endif
@endsection

<script>
// Initialize all cards as collapsed
document.addEventListener('DOMContentLoaded', function() {
    // Initialize direct member cards
    const directMemberCards = document.querySelectorAll('[id^="content_direct_member_"]');
    directMemberCards.forEach(card => {
        card.style.display = 'none';
    });
    
    // Set all direct member toggles to right arrow
    const directToggles = document.querySelectorAll('[id^="toggle_direct_member_"]');
    directToggles.forEach(toggle => {
        toggle.textContent = '>';
        toggle.style.transform = 'rotate(-90deg)';
    });
});

function toggleMemberCard(memberId) {
    const content = document.getElementById('content_' + memberId);
    const toggle = document.getElementById('toggle_' + memberId);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.textContent = '▼';
        toggle.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        toggle.textContent = '▶';
        toggle.style.transform = 'rotate(-90deg)';
    }
}

function showPerformanceChart(type) {
    // Hide all charts
    document.querySelectorAll('.performance-chart').forEach(chart => {
        chart.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.performance-btn').forEach(btn => {
        btn.style.background = 'white';
        btn.style.color = '#64748b';
        btn.style.borderColor = '#e2e8f0';
    });
    
    // Show selected chart and activate button
    if (type === 'frequent') {
        document.getElementById('frequentChart').style.display = 'block';
        document.getElementById('frequentBtn').style.background = '#3b82f6';
        document.getElementById('frequentBtn').style.color = 'white';
        document.getElementById('frequentBtn').style.borderColor = '#3b82f6';
    } else if (type === 'usage') {
        document.getElementById('usageChart').style.display = 'block';
        document.getElementById('usageBtn').style.background = '#f59e0b';
        document.getElementById('usageBtn').style.color = 'white';
        document.getElementById('usageBtn').style.borderColor = '#f59e0b';
    }
}
</script>

