@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Transaction History';
@endphp

@section('sidebar')
    @include('client.sidebar')
@endsection

@section('content')
    <style>
        .cards-grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:22px; }
        .card{ 
            border:1px solid var(--line); 
            border-radius:14px; 
            background:#ffffff; 
            box-shadow:0 10px 28px rgba(15,23,42,.06); 
            overflow:hidden;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .card:hover{
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(15,23,42,.15);
            border-color: rgba(37,99,235,.2);
        }
        .card-head{ 
            padding:14px 16px; 
            background:linear-gradient(135deg, rgba(37,99,235,.05), rgba(99,102,241,.02));
            border-bottom:1px solid var(--line); 
            display:flex; 
            justify-content:space-between; 
            gap:12px; 
            cursor:pointer;
            transition: all 0.3s ease;
        }
        .card:hover .card-head{ 
            background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.04));
            border-bottom-color: rgba(37,99,235,.15);
        }
        .card-body{ 
            padding:16px;
            background:linear-gradient(135deg, #fafbfc 0%, rgba(99,102,241,.02) 100%);
            transition: all 0.3s ease;
        }
        .card-body.hidden{ display:none; }
        .card-title{ font-weight:800; font-size:16px; }
        .card-sub{ color:var(--muted); font-size:13px; margin-top:4px; }
        
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        
        
        .muted{ color:var(--muted); }
        .list{ list-style: disc inside; color:var(--muted); }
        .list li{ margin-bottom:6px; }
    </style>

    <div style="margin-top:16px;">
        <h2 style="margin-bottom:14px;">Transaction History</h2>

        <!-- Filters Section -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="position:relative;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div>
                        <div class="card-title">Transaction Filters</div>
                        <div class="card-sub">Filter by type and member</div>
                    </div>

                    <form id="filterForm" method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:4px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <label style="font-size:13px; font-weight:600; color:#374151;">Type:</label>
                            <div style="display:flex; gap:12px;">
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="all" 
                                        {{ ($type ?? 'all') === 'all' ? 'checked' : '' }}
                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>All</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="request" 
                                        {{ ($type ?? 'all') === 'request' ? 'checked' : '' }}
                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Request</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="deduction" 
                                        {{ ($type ?? 'all') === 'deduction' ? 'checked' : '' }}
                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Deduction</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="distribution" 
                                        {{ ($type ?? 'all') === 'distribution' ? 'checked' : '' }}
                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Distribution</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="direct" 
                                        {{ ($type ?? 'all') === 'direct' ? 'checked' : '' }}
                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Direct</span>
                                </label>
                            </div>
                        </div>

                        <select
                            id="filterMember"
                            name="member"
                            style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff; min-width:180px;"
                            onchange="document.getElementById('filterForm').submit();"
                        >
                            <option value="">All Members</option>
                            @foreach($clientMembers as $member)
                                <option value="{{ $member->id }}" {{ $member->id == ($memberId ?? '') ? 'selected' : '' }}>{{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Direct Deductions Section -->
        @if(isset($directDeductions) && $directDeductions->count() > 0)
            <h3 style="margin-bottom:12px; color:#dc2626; font-size:16px;">Direct Inventory Deductions</h3>
            @forelse($directDeductions as $deduction)
                <div class="card" style="margin-bottom:14px; border-left:4px solid #dc2626;">
                    <div class="card-head" onclick="toggleDeduction('deduction-{{ $deduction->id }}')">
                        <div>
                            <div class="card-title" style="color:#dc2626;">Deduction #{{ $deduction->id }}</div>
                            <div class="card-sub">Deducted on {{ $deduction->created_at?->format('F j, Y, g:i A') }}</div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px; border:1px solid #fca5a5; display: none;">
                                <div style="font-size:12px; color:#991b1b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Item Deducted</div>
                                <div style="font-size:14px; font-weight:800; color:#7f1d1d; margin-top:2px;">
                                    @if($deduction->stockRequestItem)
                                        {{ $deduction->stockRequestItem->stock->description ?? 'Unknown Item' }}
                                    @else
                                        {{ $deduction->reason ?? 'Direct Request Item' }}
                                    @endif
                                    ({{ $deduction->deducted_qty }} units)
                                </div>
                                @if($deduction->member)
                                    <div style="font-size:12px; color:#991b1b; margin-top:4px;">Assigned to: {{ $deduction->member->name }}</div>
                                @endif
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:12px; font-weight:700;">DEDUCTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="deduction-{{ $deduction->id }}" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Deduction Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px;">
                                <div style="font-weight:600; color:#7f1d1d; margin-bottom:4px;">
                                    @if($deduction->stockRequestItem)
                                        {{ $deduction->stockRequestItem->stock->description ?? 'Unknown Item' }}
                                    @else
                                        {{ $deduction->reason ?? 'Direct Request Item' }}
                                    @endif
                                </div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#991b1b;">
                                    @if($deduction->stockRequestItem)
                                        <span><strong>ID:</strong> {{ $deduction->stockRequestItem->stock->id_no ?? 'N/A' }}</span>
                                        <span><strong>Unit:</strong> {{ $deduction->stockRequestItem->stock->unit ?? 'N/A' }}</span>
                                    @else
                                        <span><strong>Type:</strong> Direct Request Item</span>
                                    @endif
                                    <span><strong>Quantity:</strong> {{ $deduction->deducted_qty }}</span>
                                </div>
                                @if($deduction->member)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                        <strong>Assigned Member:</strong> {{ $deduction->member->name }} ({{ $deduction->member->email }})
                                    </div>
                                @endif
                                {{-- Reason field hidden --}}
                                @if($deduction->reason)
                                    {{-- <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                        <strong>Reason:</strong> {{ $deduction->reason }}
                                    </div> --}}
                                @endif
                                @if($deduction->received_by)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #10b981;">
                                        <strong>Received By:</strong> {{ $deduction->received_by }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        @endif

        <!-- Member Distribution Section -->
        @if((isset($memberDistributions) && $memberDistributions->count() > 0) || (isset($distributionDirectRequests) && $distributionDirectRequests->count() > 0))
            <h3 style="margin-bottom:12px; color:#2563eb; font-size:16px;">Member Distributions</h3>

            @foreach($memberDistributions ?? collect() as $distribution)
                <div class="card" style="margin-bottom:14px; border-left:4px solid #2563eb;">
                    <div class="card-head" onclick="toggleDistribution('distribution-{{ $distribution->id }}')">
                        <div>
                            <div class="card-title" style="color:#2563eb;">Distribution #{{ $distribution->id }}</div>
                            <div class="card-sub">Distributed on {{ $distribution->created_at?->format('F j, Y, g:i A') }}</div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px; border:1px solid #93c5fd; display: none;">
                                <div style="font-size:12px; color:#1d4ed8; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Item Distributed</div>
                                <div style="font-size:14px; font-weight:800; color:#1e40af; margin-top:2px;">
                                    {{ $distribution->stockRequestItem?->stock?->description ?? 'Unknown Item' }}
                                    ({{ $distribution->distributed_qty }} units)
                                </div>
                                @if($distribution->member)
                                    <div style="font-size:12px; color:#1d4ed8; margin-top:4px;">Member: {{ $distribution->member->name }}</div>
                                @endif
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#2563eb; color:#fff; font-size:12px; font-weight:700;">DISTRIBUTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="distribution-{{ $distribution->id }}" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Distribution Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px;">
                                <div style="font-weight:600; color:#1e40af; margin-bottom:4px;">{{ $distribution->stockRequestItem?->stock?->description ?? 'Unknown Item' }}</div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#1d4ed8;">
                                    <span><strong>ID:</strong> {{ $distribution->stockRequestItem?->stock?->id_no ?? 'N/A' }}</span>
                                    <span><strong>Unit:</strong> {{ $distribution->stockRequestItem?->stock?->unit ?? 'N/A' }}</span>
                                    <span><strong>Quantity:</strong> {{ $distribution->distributed_qty }}</span>
                                </div>
                                @if($distribution->member)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Member:</strong> {{ $distribution->member->name }} ({{ $distribution->member->email }})
                                    </div>
                                @endif
                                @if($distribution->used_qty)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Used Qty:</strong> {{ $distribution->used_qty }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @foreach($distributionDirectRequests ?? collect() as $distribution)
                <div class="card" style="margin-bottom:14px; border-left:4px solid #2563eb;">
                    <div class="card-head" onclick="toggleDistribution('distribution-direct-{{ $distribution->id }}')">
                        <div>
                            <div class="card-title" style="color:#2563eb;">Distribution #{{ $distribution->id }}</div>
                            <div class="card-sub">Distributed on {{ $distribution->created_at?->format('F j, Y, g:i A') }}</div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px; border:1px solid #93c5fd; display: none;">
                                <div style="font-size:12px; color:#1d4ed8; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Direct Request Item</div>
                                <div style="font-size:14px; font-weight:800; color:#1e40af; margin-top:2px;">
                                    {{ $distribution->reason ?? 'Direct Request Item' }}
                                    ({{ $distribution->deducted_qty }} units)
                                </div>
                                @if($distribution->member)
                                    <div style="font-size:12px; color:#1d4ed8; margin-top:4px;">Member: {{ $distribution->member->name }}</div>
                                @endif
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#2563eb; color:#fff; font-size:12px; font-weight:700;">DISTRIBUTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="distribution-direct-{{ $distribution->id }}" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Distribution Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px;">
                                <div style="font-weight:600; color:#1e40af; margin-bottom:4px;">{{ $distribution->reason ?? 'Direct Request Item' }}</div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#1d4ed8;">
                                    <span><strong>Quantity:</strong> {{ $distribution->deducted_qty }}</span>
                                </div>
                                @if($distribution->member)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Member:</strong> {{ $distribution->member->name }} ({{ $distribution->member->email }})
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        @endif

        <!-- Urgent Outbound Section -->
        @if(isset($urgentOutbounds) && $urgentOutbounds->count() > 0)
            <h3 style="margin-bottom:12px; color:#dc2626; font-size:16px;">Urgent Outbound Requests</h3>
            @forelse($urgentOutbounds as $outbound)
                <div class="card" style="margin-bottom:14px; border-left:4px solid #dc2626;">
                    <div class="card-head" onclick="toggleUrgentOutbound('urgent-{{ $outbound->id }}')">
                        <div>
                            <div class="card-title" style="color:#dc2626;">Urgent Outbound #{{ $outbound->id }}</div>
                            <div class="card-sub">Created on {{ $outbound->created_at?->format('F j, Y, g:i A') }}</div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px; border:1px solid #fca5a5;">
                                <div style="font-size:12px; color:#991b1b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Recipient</div>
                                <div style="font-size:14px; font-weight:800; color:#7f1d1d; margin-top:2px;">{{ $outbound->urgent_recipient_name ?? 'Unknown' }}</div>
                                @if($outbound->urgent_recipient_office)
                                    <div style="font-size:12px; color:#991b1b; margin-top:4px;">Office: {{ $outbound->urgent_recipient_office }}</div>
                                @endif
                            </div>
                            {{-- Reason field hidden --}}
                            @if($outbound->reason)
                                {{-- <div style="margin-top:8px; padding:6px 10px; background:#fef9c3; border-radius:6px; border:1px solid #fde047;">
                                    <div style="font-size:12px; color:#854d0e; font-weight:600;">Reason:</div>
                                    <div style="font-size:13px; color:#713f12; margin-top:2px;">{{ $outbound->reason }}</div>
                                </div> --}}
                            @endif
                            <div style="margin-top:8px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:11px; font-weight:700;">URGENT</span>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:12px; font-weight:700;">URGENT</span>
                            </div>
                        </div>
                    </div>

                    <div id="urgent-{{ $outbound->id }}" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Urgent Outbound Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px;">
                                <div style="font-weight:600; color:#7f1d1d; margin-bottom:4px;">{{ $outbound->stock?->description ?? 'Unknown Item' }}</div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#991b1b;">
                                    <span><strong>ID:</strong> {{ $outbound->stock?->id_no ?? 'N/A' }}</span>
                                    <span><strong>Unit:</strong> {{ $outbound->stock?->unit ?? 'N/A' }}</span>
                                    <span><strong>Quantity:</strong> {{ $outbound->total }}</span>
                                </div>
                                @if($outbound->received_by)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                        <strong>Received By:</strong> {{ $outbound->received_by }}
                                    </div>
                                @endif
                                <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                    <strong>Recipient:</strong> {{ $outbound->urgent_recipient_name }} @if($outbound->urgent_recipient_office)({{ $outbound->urgent_recipient_office }})@endif
                                </div>
                                {{-- Reason field hidden --}}
                                @if($outbound->reason)
                                    {{-- <div style="margin-top:8px; padding:6px 8px; background:#fef9c3; border-radius:6px; border:1px solid #fde047;">
                                        <strong style="color:#854d0e;">Reason:</strong> {{ $outbound->reason }}
                                    </div> --}}
                                @endif
                                {{-- Note field hidden --}}
                                {{-- <div style="margin-top:8px; padding:6px 8px; background:#fef2f2; border-radius:6px; border:1px solid #fca5a5;">
                                    <strong style="color:#991b1b;">Note:</strong> This was an urgent outbound request processed immediately.
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        @endif

        <!-- Direct Requests Section -->
        @if(isset($directRequests) && $directRequests->count() > 0)
            <h3 style="margin-bottom:12px; color:#10b981; font-size:16px;">Direct Requests</h3>
            @forelse($directRequests as $request)
                <div class="card" style="margin-bottom:14px; border-left:4px solid #10b981;">
                    <div class="card-head" onclick="toggleDirectRequest('direct-{{ $request->id }}')">
                        <div>
                            <div class="card-title" style="color:#10b981;">Direct Request #{{ $request->id }}</div>
                            <div class="card-sub">Created on {{ $request->created_at?->format('F j, Y, g:i A') }}</div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius:8px; border:1px solid #6ee7b7;">
                                <div style="font-size:12px; color:#047857; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">@if($request->member) Member @else Client @endif</div>
                                <div style="font-size:14px; font-weight:800; color:#065f46; margin-top:2px;">{{ $request->member?->name ?? $request->client?->name ?? 'Unknown' }}</div>
                                @if($request->member)
                                    <div style="font-size:12px; color:#047857; margin-top:4px;">{{ $request->member?->email }}</div>
                                @else
                                    <div style="font-size:12px; color:#047857; margin-top:4px;">Office: {{ $request->office ?? 'Not specified' }}</div>
                                @endif
                            </div>
                            <div style="margin-top:8px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#10b981; color:#fff; font-size:11px; font-weight:700;">Direct request</span>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#10b981; color:#fff; font-size:12px; font-weight:700;">DIRECT</span>
                            </div>
                        </div>
                    </div>

                    <div id="direct-{{ $request->id }}" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Direct Request Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius:8px;">
                                <div style="font-weight:600; color:#065f46; margin-bottom:4px;">{{ $request->stock?->description ?? 'Unknown Item' }}</div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#047857;">
                                    <span><strong>ID:</strong> {{ $request->stock?->id_no ?? 'N/A' }}</span>
                                    <span><strong>Unit:</strong> {{ $request->stock?->unit ?? 'N/A' }}</span>
                                    <span><strong>Quantity:</strong> {{ $request->total }}</span>
                                </div>
                                @if($request->received_by)
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #6ee7b7;">
                                        <strong>Received By:</strong> {{ $request->received_by }}
                                    </div>
                                @endif
                                <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #6ee7b7;">
                                    <strong>Requested by:</strong> {{ $request->member?->name ?? $request->client?->name }} @if($request->member?->email)({{ $request->member->email }})@elseif($request->office)({{ $request->office }})@endif
                                </div>
                                {{-- Note field hidden --}}
                                {{-- <div style="margin-top:8px; padding:6px 8px; background:#ecfdf5; border-radius:6px; border:1px solid #6ee7b7;">
                                    <strong style="color:#047857;">Note:</strong> This was a direct request from the @if($request->member) member @else client/office @endif, not through the regular request process.
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        @endif

        <!-- Stock Requests Section -->
        @if(($type ?? 'all') === 'all' || ($type ?? 'all') === 'request')
            <h3 style="margin-bottom:12px; color:#3b82f6; font-size:16px;">Stock Requests</h3>
            @forelse($requests as $req)
                <div class="card status-{{ $req->status }}" style="margin-bottom:14px;">
                    <div class="card-head" onclick="toggleReq('req-{{ $req->id }}')">
                    <div>
                        <div class="card-title">Request #{{ $req->id }} from {{ $req->office ?? 'Unknown Office' }}</div>
                        <div class="card-sub">Submitted on {{ $req->created_at?->format('F j, Y, g:i A') }}</div>
                        @php
                            $totalQuantity = 0;
                            foreach($req->items as $item) {
                                $totalQuantity += $item->approved_qty;
                            }
                        @endphp
                        <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius:8px; border:1px solid #bae6fd;">
                            <div style="font-size:12px; color:#0369a1; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Quantity</div>
                            <div style="font-size:16px; font-weight:800; color:#0c4a6e; margin-top:2px;">{{ $totalQuantity }}</div>
                        </div>
                    </div>

                    <div style="text-align:right; min-width:160px;">
                        <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Current Status</div>
                        <div style="margin-top:4px;">
                            <span class="pill {{ $req->status }}">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                        </div>
                        @if($req->verification_code)
                            <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Verification: <span style="font-weight:700; color:var(--text);">{{ $req->verification_code }}</span></div>
                        @endif
                        @if($req->member)
                            <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Requested By: <span style="font-weight:700; color:var(--text);">{{ $req->member->name }} ({{ $req->member->email }})</span></div>
                        @endif
                        @if($req->status === 'released')
                            {{-- Debug: Show received_by value --}}
                            @if($req->received_by)
                                <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Received By: <span style="font-weight:700; color:var(--text);">{{ $req->received_by }}</span></div>
                            @else
                                <div style="font-size:11px; color:red; margin-top:6px; font-weight:500;">DEBUG: received_by is NULL for Request #{{ $req->id }}</div>
                            @endif
                        @endif
                    </div>
                </div>

                <div id="req-{{ $req->id }}" class="card-body hidden">
                    <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Requested Items</div>
                    <div style="display:grid; gap:8px;">
                        @foreach($req->items as $item)
                            <div style="padding:12px; background:linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius:8px; border-left:3px solid var(--blue);">
                                <div style="font-weight:600; color:var(--text); margin-bottom:4px;">{{ $item->stock?->description ?? 'Unknown Item' }}</div>
                                <div style="display:flex; gap:16px; font-size:12px; color:var(--muted);">
                                    <span><strong>Unit:</strong> {{ $item->stock?->unit ?? 'N/A' }}</span>
                                                                        <span><strong>Requested:</strong> {{ $item->requested_qty }}</span>
                                    <span><strong>Approved:</strong> {{ $item->approved_qty }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body">
                    <div style="text-align:center; padding:20px;">
                        <div style="font-size:24px; margin-bottom:12px; color:var(--muted);">ð</div>
                        <div class="muted" style="font-size:14px;">No transaction history available. Submit a request to see your transactions here.</div>
                    </div>
                </div>
            </div>
            @endforelse
        @endif
    </div>

    <script>
        function toggleReq(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleDeduction(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleUrgentOutbound(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }
        
        function toggleDirectRequest(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleDistribution(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        // Auto-update filters without needing a button.
        (function(){
            const form = document.getElementById('filterForm');

            if (!form) return;

            const submit = () => {
                const params = new URLSearchParams();
                
                // Get selected radio button
                const typeRadio = document.querySelector('input[name="type"]:checked');
                const type = typeRadio ? typeRadio.value : 'all';

                // Get selected member
                const memberSelect = document.getElementById('filterMember');
                const member = memberSelect ? memberSelect.value : '';

                if (type && type !== 'all') params.set('type', type);
                if (member) params.set('member', member);

                const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.location.href = url;
            };
        })();
    </script>
@endsection
