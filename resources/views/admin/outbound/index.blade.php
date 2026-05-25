@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Outbound';
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')
<style>
    .toolbar{ display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
    .btn-link{
        display:inline-block;
        padding:10px 12px;
        border-radius:10px;
        border:1px solid var(--blue);
        background: var(--blue-soft);
        color: var(--blue);
        text-decoration:none;
        font-weight:700;
    }
    .btn-link:hover{ background: rgba(37,99,235,.18); }
    .btn-add-outbound:hover{ 
        background: linear-gradient(135deg, #2563eb, #1e40af) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(59,130,246,0.3) !important;
        border-color: rgba(59,130,246,0.5) !important;
    }
    .btn-add-outbound:hover::after{ left:100% !important; }
    .btn-add-outbound:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(59,130,246,0.2) !important;
    }

    .ob-card{
        margin-bottom:12px;
        border:1px solid var(--line);
        border-radius:14px;
        background:#ffffff;
        box-shadow:0 10px 28px rgba(15,23,42,.06);
        overflow:hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .ob-card:hover{
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(15,23,42,.15);
        border-color: rgba(37,99,235,.2);
    }

    .ob-header{
        padding:14px 16px;
        background:linear-gradient(135deg, rgba(37,99,235,.05), rgba(99,102,241,.02));
        border-bottom:1px solid var(--line);
        display:flex;
        justify-content:center;
        align-items:center;
        cursor:pointer;
        transition: all 0.3s ease;
        position:relative;
    }
    .ob-card:hover .ob-header{
        background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.04));
        border-bottom-color: rgba(37,99,235,.15);
    }

    .ob-title{
        font-weight:800;
        font-size:16px;
        color:var(--text);
        margin:0;
        position:relative;
        left:auto;
        top:auto;
        transform:none;
        white-space:nowrap;
    }
    .ob-sub{
        color:var(--muted);
        font-size:13px;
        margin-top:4px;
    }

    .ob-header-right{
        position:absolute;
        right:16px;
        top:50%;
        transform:translateY(-50%);
        text-align:right;
        display:flex;
        flex-direction:column;
        align-items:flex-end;
        gap:8px;
        white-space:nowrap;
    }

    .ob-date{
        font-weight:700;
        font-size:14px;
        color:var(--text);
    }

    .ob-count{
        font-weight:700;
        font-size:14px;
        color:var(--text);
    }

    .ob-toggle{ 
        color:var(--muted); 
        font-size:12px; 
        font-weight:600;
        display:flex;
        align-items:center;
        gap:6px;
    }

    .ob-toggle::after{
        content:"â";
        display:inline-block;
        transition:transform 0.2s ease;
        font-size:10px;
    }

    .ob-body{
        padding:16px;
        background:linear-gradient(135deg, #fafbfc 0%, rgba(99,102,241,.02) 100%);
        transition: all 0.3s ease;
        display:none;
    }
    .ob-body.open{ display:block; }
    .ob-body.open ~ .ob-header .ob-toggle::after{
        transform:rotate(-180deg);
    }

    .pill{
        display:inline-block; 
        padding:5px 12px; 
        border-radius:999px;
        border:1px solid rgba(37,99,235,.45);
        background: rgba(37,99,235,.14);
        color: var(--blue);
        font-size:12px; 
        font-weight:800;
    }
    .pill.orange{
        border-color: rgba(249,115,22,.35);
        background: rgba(249,115,22,.12);
        color: var(--orange);
    }
    .pill.green{
        border-color: rgba(22,163,74,.35);
        background: rgba(22,163,74,.12);
        color: #16a34a;
    }

    .table-wrap{ overflow:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe); }
    table{ width:100%; border-collapse:collapse; }
    th,td{ border:1px solid #e0e7ff; padding:10px; text-align:left; }
    th{ background:linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; font-weight:700; font-size:12px; border-bottom:2px solid #1e40af; }
    td{ color: #475569; font-size:13px; border-bottom:1px solid #e0e7ff; }
    td b{ color:#1e40af; font-weight:700; }

    .ob-summary{
        margin-top:16px;
        padding:16px;
        background:linear-gradient(135deg, rgba(37,99,235,.06) 0%, rgba(37,99,235,.03) 100%);
        border:1px solid rgba(37,99,235,.1);
        border-radius:12px;
    }

    .ob-summary-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:16px;
    }

    .ob-summary-item{
        display:flex;
        flex-direction:column;
    }

    .ob-summary-label{
        color:var(--muted);
        font-size:12px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.5px;
        margin-bottom:6px;
    }

    .ob-summary-value{
        color:var(--text);
        font-weight:700;
        font-size:16px;
    }

    .search-filter-wrap{
        margin-bottom:20px;
    }

    .report-filters{
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        align-items:flex-end;
        margin-bottom:20px;
    }

    .report-filters label{
        display:block;
        margin-bottom:6px;
        font-size:13px;
        color:var(--text);
        font-weight:600;
    }

    .report-filters input[type="date"]{
        width:100%;
        min-width:180px;
        padding:10px 14px;
        border:1px solid var(--line);
        border-radius:8px;
        background:#fff;
        font-size:14px;
        color:var(--text);
    }

    .report-filters .report-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
    }

    .search-input{
        width:100%;
        max-width:400px;
        padding:10px 14px;
        border:1px solid var(--line);
        border-radius:8px;
        font-size:14px;
        color:var(--text);
    }

    .search-input:focus{
        outline:none;
        border-color:var(--blue);
        box-shadow:0 0 0 3px rgba(37,99,235,.1);
    }

    .no-results{
        text-align:center;
        padding:60px 20px;
        color:var(--muted);
    }

    .no-results-icon{
        font-size:48px;
        margin-bottom:12px;
        opacity:0.5;
    }

    .no-results-text{
        margin:0;
        font-size:16px;
    }
    
    /* Modal button hover effects */
    .modal-btn-primary:hover{
        background: linear-gradient(135deg, #2563eb, #1e40af) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(59,130,246,0.3) !important;
        border-color: rgba(59,130,246,0.5) !important;
    }
    .modal-btn-primary:hover::after{ left:100% !important; }
    .modal-btn-primary:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(59,130,246,0.2) !important;
    }
    
    .modal-btn-secondary:hover{
        background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(59,130,246,0.15) !important;
        border-color: rgba(59,130,246,0.3) !important;
        color: #374151 !important;
    }
    .modal-btn-secondary:hover::after{ left:100% !important; }
    .modal-btn-secondary:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(59,130,246,0.1) !important;
    }
</style>

<div class="toolbar">
    <h2 style="margin:0;">Outbound Items</h2>
    <button type="button" onclick="openOutboundModal()" class="btn-add-outbound" style="display:flex; align-items:center; gap:8px; padding:12px 20px; border-radius:12px; border:2px solid transparent; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; text-decoration:none; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        Add Outbound
        <span style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
    </button>
</div>
@php
    $stocks = \App\Models\Stock::all();
    $clients = \App\Models\User::where('role', 'client')->get();
    $pendingRequests = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('status','pending')->count() : 0;
    $pendingPR = class_exists(\App\Models\PasswordResetRequest::class) ? \App\Models\PasswordResetRequest::where('status','pending')->count() : 0;
    $totalPending = $pendingRequests + $pendingPR;
@endphp

{{-- Outbound-specific notification button removed to avoid duplicate notifications. Main notifications partial is used instead. --}}

<form method="get" action="{{ route('outbound.index') }}">
    <div class="search-filter-wrap">
        <input type="text" id="searchInput" name="search" class="search-input" value="{{ old('search', request('search')) }}" placeholder="Search by client, office, or item...">
    </div>

    <div class="report-filters">
        <div>
            <label for="date_from">Date From</label>
            <input type="date" id="date_from" name="date_from" value="{{ old('date_from', $dateFrom ?? request('date_from')) }}">
        </div>
        <div>
            <label for="date_to">Date To</label>
            <input type="date" id="date_to" name="date_to" value="{{ old('date_to', $dateTo ?? request('date_to')) }}">
        </div>
        <div>
            <label for="office">Office</label>
            <select id="office" name="office" style="width:100%; min-width:180px; padding:10px 14px; border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--text); font-size:14px;">
                <option value="all" {{ empty($office ?? request('office')) || ($office ?? request('office')) === 'all' ? 'selected' : '' }}>All Offices</option>
                @foreach($offices as $officeOption)
                    <option value="{{ $officeOption }}" {{ ($office ?? request('office')) === $officeOption ? 'selected' : '' }}>{{ $officeOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-actions">
            <button type="submit" class="btn-link">Apply</button>
            <a href="{{ route('admin.outbound.report.pdf', array_filter(request()->only(['search','date_from','date_to','office']))) }}" class="btn-link" style="background:var(--blue); color:#fff; border:none;">Download PDF</a>
        </div>
    </div>
</form>

@php
    // Group outbounds by date first, then by client_id and office
    $groupedByDate = $outbounds->groupBy(function($item) {
        return $item->deducted_at?->format('Y-m-d') ?? $item->created_at?->format('Y-m-d') ?? 'No Date';
    });
@endphp

@forelse($groupedByDate as $dateKey => $dateGroup)
    @php
        // Group by client and office within each date
        $grouped = $dateGroup->groupBy(function($item) {
            return $item->client_id . '-' . $item->office;
        });
    @endphp

    @forelse($grouped as $groupKey => $group)
        @php
            $firstRow = $group->first();
            $obid = 'ob-' . $firstRow->client_id . '-' . str_replace(' ', '-', strtolower($firstRow->office)) . '-' . $dateKey;
            $clientName = $firstRow->client?->name ?? 'Unknown Client';
            $clientOffice = $firstRow->office ?? '—';
            $groupDate = $firstRow->deducted_at?->format('M d, Y') ?? $firstRow->created_at?->format('M d, Y') ?? '—';
        @endphp

        <div class="ob-card" data-client="{{ strtolower($clientName) }}" data-office="{{ strtolower($clientOffice) }}">
            <div class="ob-header" onclick="toggleOb('{{ $obid }}')">
                <div style="flex:1;">
                    <div class="ob-title">{{ $clientOffice }}</div>
                </div>

                <div class="ob-header-right">
                    <div class="ob-count" style="text-align:right;">
                        <div style="font-size:13px;color:var(--muted);">{{ $group->count() }} item{{ $group->count() !== 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>

            <div id="{{ $obid }}" class="ob-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:120px;">Stock ID</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px;">Description</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:100px;">Unit</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:100px;">Quantity</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:120px;">Requestor</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:120px;">Approval</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:120px;">Status</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:140px;">Received By</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:140px;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group as $row)
                                @php
                                    $status = $row->status ?? 'released';
                                    $approval = $row->approval ?? 'pending';
                                    $stockId = $row->stock?->id_no ?? $row->stock_id;
                                    $description = $row->stock?->description ?? $row->description ?? '—';
                                    $memberName = $row->member?->name ?? '—';
                                    $memberEmail = $row->member?->email ?? '—';
                                    $isDirectRequest = $row->is_direct_request ?? false;
                                    $isUrgentOutbound = $row->is_urgent_outbound ?? false;
                                @endphp
                                <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                        <div style="font-weight:700; color:#1e40af; font-size:14px;">{{ $stockId }}</div>
                                        @if($isDirectRequest && !$isUrgentOutbound)
                                            <div style="margin-top:4px;">
                                                <span style="padding:2px 6px; border-radius:4px; background:#10b981; color:#fff; font-size:10px; font-weight:700;">DIRECT</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                        <div style="color:#64748b; font-size:14px;">{{ $description }}</div>
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $row->stock?->unit ?? '—' }}</td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $row->total ?? '—' }}</td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $memberName }}</td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                        @if($approval === 'approved')
                                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bbf7d0; background:#ecfdf5; color:#059669;">{{ ucfirst($approval) }}</span>
                                        @else
                                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fed7aa; background:#fff7ed; color:#ea580c;">{{ ucfirst($approval) }}</span>
                                        @endif
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                        @if($isUrgentOutbound)
                                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fecaca; background:#fef2f2; color:#dc2626;">URGENT</span>
                                        @elseif($isDirectRequest)
                                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bbf7d0; background:#ecfdf5; color:#059669;">Direct</span>
                                        @else
                                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bbf7d0; background:#ecfdf5; color:#059669;">Released</span>
                                        @endif
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">
                                        @if($row->received_by)
                                            <div style="font-size:13px; font-weight:600; color:#059669;">{{ $row->received_by }}</div>
                                        @else
                                            <span style="color:#9ca3af;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $row->deducted_at?->format('M d, Y') ?? $row->created_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <p style="color:var(--muted); margin-bottom:20px;">No records for this date.</p>
    @endforelse
@empty
    <div class="no-results">
        <div class="no-results-icon">📦</div>
        <p class="no-results-text">No outbound records found.</p>
    </div>
@endforelse

<!-- Add Outbound Modal -->
<div id="outboundModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#ffffff; border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 18px 40px rgba(2,6,23,.2);">
        <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#1e293b;">Add Outbound</h3>
        
        <form id="outboundForm" method="POST" action="{{ route('outbound.store') }}">
            @csrf
            
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Select Stock</label>
                <div style="position:relative;">
                    <!-- Hidden input to store the selected stock ID -->
                    <input type="hidden" name="stock_id" id="stockIdInput" required>
                    
                    <!-- Custom dropdown trigger -->
                    <div id="stockDropdown" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); cursor:pointer; position:relative;">
                        <span id="selectedStockText">-- Choose a stock --</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); pointer-events:none;">
                            <path d="M6 9l6 6 6-6"></path>
                        </svg>
                    </div>
                    
                    <!-- Custom dropdown with integrated search -->
                    <div id="stockDropdownMenu" style="position:absolute; top:100%; left:0; right:0; background:#ffffff; border:2px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 25px rgba(15,23,42,.15); margin-top:4px; max-height:300px; overflow-y:auto; z-index:1000; display:none;">
                        <!-- Search bar inside dropdown -->
                        <div style="position:relative; border-bottom:1px solid #e2e8f0;">
                            <input type="text" id="stockSearchInput" placeholder="Search stocks..." style="width:100%; padding:12px 14px; border:none; border-radius:0; font-size:14px; color:#374151; background:#ffffff; outline:none;">
                        </div>
                        
                        <!-- Stock options -->
                        <div id="stockOptions">
                            @foreach($stocks as $stock)
                                <div class="stock-option-item" data-stock-id="{{ $stock->id }}" data-stock-text="{{ $stock->description }} ({{ $stock->id_no }})" style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                    <div style="font-weight:600; color:#1e40af;">{{ $stock->description }}</div>
                                    <div style="font-size:12px; color:#64748b; margin-top:2px;">ID: {{ $stock->id_no }} | Available: {{ $stock->stock }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Client</label>
                <div style="position:relative;">
                    <!-- Hidden input to store the selected client ID -->
                    <input type="hidden" name="client_id" id="clientIdInput" required>
                    <input type="hidden" name="office" id="officeInput">
                    
                    <!-- Input field for client/member/non-member entry -->
                    <input 
                        type="text" 
                        id="clientDropdown" 
                        name="recipient_name" 
                        placeholder="Type client name, member name, or non-member name..."
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); outline:none;"
                    >
                    
                    <!-- Custom dropdown -->
                    <div id="clientDropdownMenu" style="position:absolute; top:100%; left:0; right:0; background:#ffffff; border:2px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 25px rgba(15,23,42,.15); margin-top:4px; max-height:300px; overflow-y:auto; z-index:1000; display:none;">
                        <!-- Client and Member options -->
                        <div id="clientOptions">
                            @foreach($clients as $client)
                                <div class="client-option-item" data-type="client" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}" data-client-office="{{ $client->office ?? 'Not specified' }}" style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                    <div style="font-weight:600; color:#1e40af;">{{ $client->name }}</div>
                                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Client • Office: {{ $client->office ?? 'Not specified' }}</div>
                                </div>
                            @endforeach
                            @foreach($members as $member)
                                <div class="client-option-item" data-type="member" data-client-id="{{ $member->client_id }}" data-member-id="{{ $member->id }}" data-client-name="{{ $member->name }}" data-client-office="{{ $member->client->office ?? 'non office member' }}" style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                    <div style="font-weight:600; color:#059669;">{{ $member->name }}</div>
                                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Member of {{ $member->client->name }} • Office: {{ $member->client->office ?? 'non office member' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Office/Department</label>
                <div id="officeDisplay" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#64748b; background:#f8fafc; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                    Select a client to view their office
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Quantity</label>
                <input type="number" name="total" id="modalTotal" min="1" value="1" required style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Reason for Request</label>
                <textarea name="reason" placeholder="Enter reason for this outbound request..." style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); resize:vertical; min-height:80px; outline:none;"></textarea>
            </div>
            
            <div style="display:flex; gap:12px;">
                <button type="submit" class="modal-btn-primary" style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                    <span style="position:relative; z-index:1;">Add Outbound</span>
                    <span style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
                </button>
                <button type="button" onclick="closeOutboundModal()" class="modal-btn-secondary" style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                    <span style="position:relative; z-index:1;">Cancel</span>
                    <span style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(59,130,246,0.1)); transition:left 0.3s ease;"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleOb(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.classList.toggle('open');
}

const searchInput = document.getElementById('searchInput');

function filterCards(){
    const search = searchInput.value.toLowerCase();

    document.querySelectorAll('.ob-card').forEach(card => {
        const client = card.dataset.client;
        const office = card.dataset.office;

        const matchesSearch = !search || client.includes(search) || office.includes(search);

        card.style.display = matchesSearch ? '' : 'none';
    });
}

searchInput.addEventListener('input', filterCards);

function openOutboundModal() {
    document.getElementById('outboundModal').style.display = 'flex';
}

function closeOutboundModal() {
    document.getElementById('outboundModal').style.display = 'none';
}

// Custom dropdown with integrated search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Stock dropdown functionality
    const stockDropdown = document.getElementById('stockDropdown');
    const stockDropdownMenu = document.getElementById('stockDropdownMenu');
    const stockSearchInput = document.getElementById('stockSearchInput');
    const stockIdInput = document.getElementById('stockIdInput');
    const selectedStockText = document.getElementById('selectedStockText');
    const stockOptions = document.querySelectorAll('.stock-option-item');
    
    if (stockDropdown && stockDropdownMenu && stockSearchInput) {
        // Toggle dropdown when clicking on the trigger
        stockDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = stockDropdownMenu.style.display === 'block';
            stockDropdownMenu.style.display = isOpen ? 'none' : 'block';
            if (!isOpen) {
                stockSearchInput.value = '';
                stockSearchInput.focus();
                resetStockOptions();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!stockDropdown.contains(e.target) && !stockDropdownMenu.contains(e.target)) {
                stockDropdownMenu.style.display = 'none';
            }
        });
        
        // Search functionality
        stockSearchInput.addEventListener('input', function() {
            const searchTerm = stockSearchInput.value.toLowerCase();
            
            stockOptions.forEach(option => {
                const stockText = option.dataset.stockText.toLowerCase();
                if (stockText.includes(searchTerm)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });
        
        // Handle option selection
        stockOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const stockId = option.dataset.stockId;
                const stockText = option.dataset.stockText;
                
                stockIdInput.value = stockId;
                selectedStockText.textContent = stockText;
                stockDropdownMenu.style.display = 'none';
                
                // Add hover effect
                option.style.background = '#f8fafc';
                setTimeout(() => {
                    option.style.background = '';
                }, 200);
            });
            
            // Add hover effect
            option.addEventListener('mouseenter', function() {
                option.style.background = '#f8fafc';
            });
            
            option.addEventListener('mouseleave', function() {
                option.style.background = '';
            });
        });
        
        function resetStockOptions() {
            stockOptions.forEach(option => {
                option.style.display = 'block';
            });
        }
    }
    
    // Client dropdown functionality
    const clientDropdown = document.getElementById('clientDropdown');
    const clientDropdownMenu = document.getElementById('clientDropdownMenu');
    const clientIdInput = document.getElementById('clientIdInput');
    const officeInput = document.getElementById('officeInput');
    const officeDisplay = document.getElementById('officeDisplay');
    const clientOptions = document.querySelectorAll('.client-option-item');
    
    if (clientDropdown && clientDropdownMenu) {
        // Show dropdown when input receives focus
        clientDropdown.addEventListener('focus', function(e) {
            clientDropdownMenu.style.display = 'block';
            resetClientOptions();
        });
        
        // Handle input typing to show dropdown and filter results
        clientDropdown.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            if (searchTerm.length >= 1) {
                clientDropdownMenu.style.display = 'block';
                
                let hasMatches = false;
                // Filter options based on input (both clients and members)
                clientOptions.forEach(option => {
                    const clientName = option.dataset.clientName.toLowerCase();
                    const clientOffice = option.dataset.clientOffice.toLowerCase();
                    const type = option.dataset.type || 'client';
                    const searchText = clientName + ' ' + clientOffice + ' ' + type;
                    
                    if (searchText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasMatches = true;
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Handle non-member option
                if (!hasMatches && searchTerm.length >= 2) {
                    addNonMemberOption();
                } else {
                    removeNonMemberOption();
                }
            } else {
                resetClientOptions();
                removeNonMemberOption();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!clientDropdown.contains(e.target) && !clientDropdownMenu.contains(e.target)) {
                clientDropdownMenu.style.display = 'none';
            }
        });
        
        // Helper functions for non-member option handling
        function addNonMemberOption() {
            let nonMemberOption = document.getElementById('nonMemberOption');
            if (!nonMemberOption) {
                nonMemberOption = document.createElement('div');
                nonMemberOption.id = 'nonMemberOption';
                nonMemberOption.className = 'client-option-item';
                nonMemberOption.style.cssText = 'padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#dc2626; font-weight:600;';
                nonMemberOption.innerHTML = `
                    <div style="font-weight:600; color:#dc2626;">${clientDropdown.value}</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">Non-member - Will create urgent recipient</div>
                `;
                
                nonMemberOption.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const recipientName = clientDropdown.value;
                    
                    // Clear client data and set as urgent recipient
                    clientIdInput.value = '';
                    officeInput.value = 'Not specified';
                    clientDropdown.value = recipientName;
                    
                    // Add hidden fields for urgent recipient
                    let urgentNameInput = document.getElementById('urgent_recipient_name');
                    let urgentOfficeInput = document.getElementById('urgent_recipient_office');
                    let isUrgentInput = document.getElementById('is_urgent_outbound');
                    
                    if (!urgentNameInput) {
                        urgentNameInput = document.createElement('input');
                        urgentNameInput.type = 'hidden';
                        urgentNameInput.id = 'urgent_recipient_name';
                        urgentNameInput.name = 'urgent_recipient_name';
                        clientDropdown.parentNode.appendChild(urgentNameInput);
                    }
                    
                    if (!urgentOfficeInput) {
                        urgentOfficeInput = document.createElement('input');
                        urgentOfficeInput.type = 'hidden';
                        urgentOfficeInput.id = 'urgent_recipient_office';
                        urgentOfficeInput.name = 'urgent_recipient_office';
                        clientDropdown.parentNode.appendChild(urgentOfficeInput);
                    }
                    
                    if (!isUrgentInput) {
                        isUrgentInput = document.createElement('input');
                        isUrgentInput.type = 'hidden';
                        isUrgentInput.id = 'is_urgent_outbound';
                        isUrgentInput.name = 'is_urgent_outbound';
                        isUrgentInput.value = 'true';
                        clientDropdown.parentNode.appendChild(isUrgentInput);
                    }
                    
                    urgentNameInput.value = recipientName;
                    urgentOfficeInput.value = officeInput.value;
                    
                    if (officeDisplay) {
                        officeDisplay.textContent = 'Non-member';
                        officeDisplay.style.color = '#dc2626';
                        officeDisplay.style.background = '#fef2f2';
                    }
                    
                    clientDropdownMenu.style.display = 'none';
                });
                
                nonMemberOption.addEventListener('mouseenter', function() {
                    this.style.background = '#fef2f2';
                });
                
                nonMemberOption.addEventListener('mouseleave', function() {
                    this.style.background = '';
                });
                
                document.getElementById('clientOptions').appendChild(nonMemberOption);
            } else {
                nonMemberOption.querySelector('div').textContent = clientDropdown.value;
            }
            nonMemberOption.style.display = 'block';
        }

        function removeNonMemberOption() {
            const nonMemberOption = document.getElementById('nonMemberOption');
            if (nonMemberOption) {
                nonMemberOption.style.display = 'none';
            }
        }
        
        // Handle option selection
        clientOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const type = option.dataset.type || 'client';
                const clientId = option.dataset.clientId;
                const clientName = option.dataset.clientName;
                const clientOffice = option.dataset.clientOffice;
                const memberId = option.dataset.memberId;
                
                clientIdInput.value = clientId;
                officeInput.value = clientOffice;
                clientDropdown.value = clientName;
                
                // Handle member-specific logic
                if (type === 'member' && memberId) {
                    // Add member_id hidden field
                    let memberInput = document.getElementById('member_id');
                    if (!memberInput) {
                        memberInput = document.createElement('input');
                        memberInput.type = 'hidden';
                        memberInput.id = 'member_id';
                        memberInput.name = 'member_id';
                        clientDropdown.parentNode.appendChild(memberInput);
                    }
                    memberInput.value = memberId;
                    
                    // Add is_direct_request hidden field
                    let directRequestInput = document.getElementById('is_direct_request');
                    if (!directRequestInput) {
                        directRequestInput = document.createElement('input');
                        directRequestInput.type = 'hidden';
                        directRequestInput.id = 'is_direct_request';
                        directRequestInput.name = 'is_direct_request';
                        directRequestInput.value = 'true';
                        clientDropdown.parentNode.appendChild(directRequestInput);
                    }
                    
                    // Disable office field for non-office members
                    if (clientOffice === 'non office member') {
                        officeInput.value = 'non office member';
                        officeInput.disabled = true;
                    }
                } else {
                    // Remove member-specific fields for client selection
                    const memberInput = document.getElementById('member_id');
                    const directRequestInput = document.getElementById('is_direct_request');
                    if (memberInput) memberInput.remove();
                    if (directRequestInput) directRequestInput.remove();
                    officeInput.disabled = false;
                }
                officeDisplay.textContent = clientOffice;
                officeDisplay.style.color = '#374151';
                officeDisplay.style.background = '#ffffff';
                clientDropdownMenu.style.display = 'none';
                
                // Add hover effect
                option.style.background = '#f8fafc';
                setTimeout(() => {
                    option.style.background = '';
                }, 200);
            });
            
            // Add hover effect
            option.addEventListener('mouseenter', function() {
                option.style.background = '#f8fafc';
            });
            
            option.addEventListener('mouseleave', function() {
                option.style.background = '';
            });
        });
        
        function resetClientOptions() {
            clientOptions.forEach(option => {
                option.style.display = 'block';
            });
        }
    }
});

// Close modal when clicking outside
document.getElementById('outboundModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOutboundModal();
    }
});
</script>
@endsection
