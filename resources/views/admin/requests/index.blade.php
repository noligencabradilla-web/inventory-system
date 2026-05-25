@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Requests';

  $activeTab = request('tab', 'pending');

  $pending   = $requests->where('status', 'pending');
  $approved  = $requests->where('status', 'approved');
  $ready     = $requests->where('status', 'ready_to_receive');
  $rejected  = $requests->where('status', 'rejected');

  $shown = match ($activeTab) {
      'approved' => $approved,
      'ready_to_receive' => $ready,
      'rejected' => $rejected,
      default => $pending,
  };
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')
<style>
    .tabs{ 
        display:flex; 
        gap:12px; 
        flex-wrap:wrap; 
        margin-bottom:20px; 
        padding:16px; 
        border:1px solid #cbd5e1;
        border-radius:18px;
        background:#ffffff;
        box-shadow:0 10px 30px rgba(15,23,42,0.08);
    }
    .tab{
        display:flex; 
        align-items:center; 
        gap:10px;
        padding:14px 22px; 
        border-radius:14px; 
        text-decoration:none;
        background:#f8fafc; 
        border:1px solid transparent;
        color:#334155;
        font-weight:700; 
        box-shadow:0 4px 12px rgba(15,23,42,.08);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        position:relative;
        overflow:hidden;
    }
    .tab::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        bottom:0;
        background:linear-gradient(90deg, transparent, rgba(255,255,255,0.8));
        opacity:0;
        transition:opacity 0.3s ease;
    }
    .tab:hover::before{ opacity:1; }
    .tab:hover{ 
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(37,99,235,.15);
        border-color:rgba(37,99,235,0.2);
    }

    /* Active tab variants to match Blue + Orange palette */
    .tab.active{ 
        border-color:var(--blue); 
        background:linear-gradient(135deg, #3b82f6, #1d4ed8); 
        color:#ffffff; 
        box-shadow:0 6px 20px rgba(59,130,246,0.3);
        transform: translateY(-2px);
    }
    .tab.pending.active{ 
        border-color:var(--orange); 
        background:linear-gradient(135deg, #f97316, #ea580c); 
        color:#ffffff; 
        box-shadow:0 6px 20px rgba(249,115,22,0.3);
    }
    .tab.approved.active{ 
        border-color:var(--blue); 
        background:linear-gradient(135deg, #3b82f6, #1d4ed8); 
        color:#ffffff; 
        box-shadow:0 6px 20px rgba(59,130,246,0.3);
    }
    .tab.ready.active{ 
        border-color:var(--blue); 
        background:linear-gradient(135deg, #3b82f6, #1d4ed8); 
        color:#ffffff; 
        box-shadow:0 6px 20px rgba(59,130,246,0.3);
    }
    .tab.rejected.active{ 
        border-color: #fca5a5; 
        background:linear-gradient(135deg, #ef4444, #dc2626); 
        color:#ffffff; 
        box-shadow:0 6px 20px rgba(239,68,68,0.3);
    }

    .badge{
        padding:4px 10px; 
        border-radius:999px; 
        font-size:11px; 
        font-weight:700;
        border:1px solid rgba(37,99,235,0.15);
        background:rgba(37,99,235,0.12);
        color:#1d4ed8;
        min-width:20px;
        text-align:center;
        box-shadow:0 2px 4px rgba(15,23,42,0.08);
    }

    /* Request card / header / expanded state */
    .req-card{
        border:1px solid #e2e8f0;
        border-radius:14px;
        background:linear-gradient(180deg,#ffffff,#fffdfa);
        overflow:visible;
        margin-bottom:14px;
        box-shadow:0 6px 18px rgba(2,6,23,0.04);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        position:relative;
    }

    /* Hover & focus: lift card slightly and intensify accent */
    .req-card:hover, .req-card:focus-within{
        transform: translateY(-6px);
        box-shadow:0 30px 60px rgba(2,6,23,0.12);
        border-color: rgba(37,99,235,0.12);
    }

    /* status-specific subtle left accent when expanded */
    .req-card.status-pending.expanded{ box-shadow:0 18px 40px rgba(249,115,22,0.06); border-left:4px solid rgba(249,115,22,0.9); }
    .req-card.status-approved.expanded{ box-shadow:0 18px 40px rgba(37,99,235,0.06); border-left:4px solid rgba(37,99,235,0.9); }
    .req-card.status-ready_to_receive.expanded{ box-shadow:0 18px 40px rgba(37,99,235,0.06); border-left:4px solid rgba(37,99,235,0.9); }
    .req-card.status-rejected.expanded{ box-shadow:0 18px 40px rgba(220,38,38,0.12); border-left:4px solid rgba(220,38,38,0.95); }

    /* stronger rejected details styling */
    .req-card.status-rejected .req-header{ background: linear-gradient(90deg, rgba(254,226,226,0.45), rgba(254,202,202,0.15)); }
    .req-card.status-rejected .req-title{ color:#991b1b; }
    .req-card.status-rejected .req-sub{ color:#7f1d1d; }
    .req-card.status-rejected .req-body{
        background: rgba(254,202,202,0.12);
        border-top:1px solid rgba(220,38,38,0.25);
        border-bottom:2px solid rgba(220,38,38,0.30);
        border-radius: 0 0 14px 14px;
    }
    .req-card.status-rejected .req-body table th, .req-card.status-rejected .req-body table td{
        border-color: rgba(220,38,38,0.2);
    }
    .req-card.status-rejected .status-pill{ background:linear-gradient(180deg,#fee2e2,#fecaca); color:#991b1b; border-color:#fda4af; }

    /* Hover accent per status */
    .req-card.status-pending:hover{ border-color: rgba(249,115,22,0.25); box-shadow:0 30px 60px rgba(249,115,22,0.06); }
    .req-card.status-approved:hover, .req-card.status-ready_to_receive:hover{ border-color: rgba(37,99,235,0.18); box-shadow:0 30px 60px rgba(37,99,235,0.06); }
    .req-card.status-rejected:hover{ border-color: rgba(220,38,38,0.18); box-shadow:0 30px 60px rgba(220,38,38,0.06); }

    .req-header{
        padding:18px 24px; /* increased padding for better spacing */
        display:flex;
        align-items:center;
        justify-content:flex-start; /* keep left content natural; right content will be absolute */
        gap:16px; /* increased gap for better spacing */
        cursor:pointer;
        /* enhanced gradient background */
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        border-bottom:2px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        position:relative; /* needed for absolute-positioned .req-right and chevron */
        border-radius:14px 14px 0 0; /* rounded top corners */
    }

    /* enhanced header hover effects */
    .req-card:hover .req-header{ 
        background: linear-gradient(135deg, #f8fafc, #f1f5f9); 
        border-bottom-color: rgba(37,99,235,0.15);
        transform: translateY(-1px);
    }
    .req-card.status-pending:hover .req-header{ 
        background: linear-gradient(135deg, #fff7ed, #fed7aa); 
        border-bottom-color: rgba(249,115,22,0.15);
    }
    .req-card.status-approved:hover .req-header{ 
        background: linear-gradient(135deg, #eff6ff, #dbeafe); 
        border-bottom-color: rgba(37,99,235,0.15);
    }
    /* rotate chevron when card is expanded */
    .req-card.expanded .req-header::after{ transform:translateY(-50%) rotate(180deg); color:rgba(2,6,23,0.6); }

    .req-title{
        font-weight:800;
        font-size:18px;
        color:#0f172a;
    }
    .req-sub{
        margin-top:4px;
        color:#475569;
        font-size:13px;
    }
    .req-right{
        position:absolute; /* move to the far right */
        right:56px; /* leave space for the chevron */
        top:50%;
        transform:translateY(-50%);
        text-align:right;
        color:#334155;
        font-weight:700;
        white-space:nowrap;
    }

    /* responsive fallback: keep .req-right in normal flow on small screens */
    @media (max-width:640px){
        .req-header{ padding-right:16px; }
        .req-header::after{ position:static; transform:none; margin-left:12px; top:auto; right:auto; }
        .req-right{ position:static; transform:none; margin-left:auto; }
    }

    /* animated collapse/expand using max-height + opacity */
    .req-body{
        max-height:0;
        overflow:hidden;
        padding:0 16px;
        background:linear-gradient(180deg, #fafbfc 0%, rgba(99,102,241,.02) 100%);
        transition: max-height 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease, padding 0.3s ease, border 0.3s ease;
        opacity:0;
        border-top: 2px solid rgba(37,99,235,.08);
    }
    .req-body.open{
        max-height:1200px; /* large enough to show content */
        padding:14px 16px 16px;
        opacity:1;
        border-top: 2px solid rgba(37,99,235,.15);
    }

    table{ width:100%; border-collapse:collapse; }
    th, td{ 
        border:1px solid #e2e8f0; 
        padding:10px; 
        text-align:center;
        transition: background-color 0.2s ease;
    }
    th{ 
        background:linear-gradient(135deg, #f8fafc, #f1f5f9);
        color:#0f172a;
        font-weight:700;
    }
    tbody tr:hover td{
        background-color: rgba(37,99,235,.03);
    }

    .muted{ color:#64748b; }
    .status-pill{
        display:inline-block;
        padding:4px 10px;
        border-radius:999px;
        border:1px solid rgba(2,6,23,0.06);
        background:transparent;
        font-size:12px;
        font-weight:700;
        color:#334155;
    }
    .status-pill.status-pending{ background:linear-gradient(180deg,var(--orange-soft),#fff7ed); color:var(--orange); border-color:rgba(249,115,22,0.16); }
    .status-pill.status-approved{ background:linear-gradient(180deg,#eff6ff,#eef6ff); color:var(--blue); border-color:rgba(37,99,235,0.12); }
    .status-pill.status-ready_to_receive{ background:linear-gradient(180deg,#eef2ff,#f8fbff); color:var(--blue); border-color:rgba(37,99,235,0.10); }
    .status-pill.status-rejected{ background:linear-gradient(180deg,#fff1f2,#fff5f5); color:#b91c1c; border-color:rgba(244,63,94,0.12); }
    .btn{
        padding:12px 20px;
        border-radius:10px;
        border:2px solid #3b82f6;
        background:linear-gradient(135deg, #3b82f6, #1d4ed8);
        color:#ffffff;
        cursor:pointer;
        font-weight:700;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow:0 4px 12px rgba(59,130,246,0.2);
        position:relative;
        overflow:hidden;
    }
    .btn::before{
        content:'';
        position:absolute;
        top:0;
        left:-100%;
        width:100%;
        height:100%;
        background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2));
        transition:left 0.3s ease;
    }
    .btn:hover::before{ left:100%; }
    .btn:hover{ 
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(59,130,246,0.3);
        border-color:rgba(59,130,246,0.5);
    }
    .btn:active{
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(59,130,246,0.2);
    }
    .btn-ghost{
        padding:12px 20px;
        border-radius:10px;
        border:2px solid #e2e8f0;
        background:#ffffff;
        color:#64748b;
        cursor:pointer;
        font-weight:600;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow:0 2px 8px rgba(15,23,42,0.08);
    }
    .btn-ghost:hover{ 
        background:linear-gradient(135deg, #f8fafc, #f1f5f9); 
        border-color:rgba(59,130,246,0.3);
        color:#374151;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59,130,246,0.15);
    }
    .btn-ghost:active{
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(59,130,246,0.1);
    }
    .btn-max{
        padding:8px 12px;
        border-radius:8px;
        border:1px solid #3b82f6;
        background:#3b82f6;
        color:#fff;
        cursor:pointer;
        font-weight:700;
        white-space:nowrap;
        flex-shrink:0;
        transition:all 0.3s ease;
    }
    .btn-max:hover{
        background:#2563eb;
        border-color:rgba(37,99,235,0.5);
        transform: translateY(-1px);
        box-shadow:0 4px 8px rgba(37,99,235,.2);
    }

    input[type="number"], input[type="text"], select{
        padding:10px 12px;
        border:2px solid #e2e8f0;
        border-radius:10px;
        font-size:14px;
        background:#fff;
        color:#0f172a;
        transition: all 0.3s ease;
    }
    input[type="number"]:focus, input[type="text"]:focus, select:focus{
        outline:none;
        border-color:#3b82f6;
        box-shadow:0 0 0 3px rgba(59,130,246,0.1);
    }
    input[type="number"]:hover, input[type="text"]:hover, select:hover{
        border-color:#cbd5e1;
    }
    /* Spinner and no-results */
    #search-spinner{ border:3px solid rgba(0,0,0,0.08); border-top-color:rgba(37,99,235,0.9); border-radius:50%; width:20px; height:20px; display:inline-block; animation:spin 1s linear infinite; }
    @keyframes spin{ to{ transform: rotate(360deg); } }
    .no-results{ padding:18px; text-align:center; color:#64748b; background:transparent; border-radius:8px; margin-top:8px; }
    /* Confirmation modal */
    #confirmModal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center; }
    #confirmModal.show{ display:flex; }
    .modal-box{ background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:420px; width:90%; padding:24px; }
    .modal-box h3{ margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800; }
    .modal-box p{ margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5; }
    .modal-box .modal-buttons{ display:flex; gap:10px; justify-content:flex-end; }
    .modal-box .modal-btn{ 
        padding:10px 16px; 
        border-radius:10px; 
        border:none; 
        font-weight:700; 
        cursor:pointer; 
        font-size:14px;
        transition: all 0.3s ease;
    }
    .modal-btn-confirm{ 
        background:#2563eb; 
        color:#fff;
    }
    .modal-btn-confirm:hover{ 
        opacity:.92;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37,99,235,.2);
    }
    .modal-btn-confirm:active{
        transform: translateY(0);
    }
    .modal-btn-cancel{ 
        background:#e2e8f0; 
        color:#0f172a;
    }
    .modal-btn-cancel:hover{ 
        background:#cbd5e1;
        transform: translateY(-2px);
    }
    .modal-btn-cancel:active{
        transform: translateY(0);
    }
</style>

<div class="tabs">
    <a class="tab pending {{ $activeTab==='pending'?'active':'' }}" href="/admin/requests?tab=pending">
        Pending <span class="badge">{{ $pending->count() }}</span>
    </a>
    <a class="tab approved {{ $activeTab==='approved'?'active':'' }}" href="/admin/requests?tab=approved">
        Approved <span class="badge">{{ $approved->count() }}</span>
    </a>
    <a class="tab ready {{ $activeTab==='ready_to_receive'?'active':'' }}" href="/admin/requests?tab=ready_to_receive">
        Ready to Receive <span class="badge">{{ $ready->count() }}</span>
    </a>
    <a class="tab rejected {{ $activeTab==='rejected'?'active':'' }}" href="/admin/requests?tab=rejected">
        Rejected <span class="badge">{{ $rejected->count() }}</span>
    </a>
</div>

<div id="no-results" class="no-results" style="display:none;">No results found.</div>

{{-- Search bar: search by Ref No. (#123) or client name --}}
<div style="background:linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius:12px; padding:20px; margin-bottom:20px; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(15,23,42,.06);">
    <form method="GET" action="{{ route('requests.index') }}" style="display:flex; gap:12px; align-items:center; width:100%;">
        <div style="position:relative; flex:1;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); pointer-events:none;">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-6.98-6.98a2 2 0 0 1-1.72 1.72h-1.72a2 2 0 0 1 1.72z"></path>
            </svg>
            <input
                type="text"
                name="q"
                placeholder="Search by Ref No. or client name"
                value="{{ request('q') }}"
                style="width:100%; padding:12px 12px 12px 40px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);"
            >
            <input type="hidden" name="tab" value="{{ $activeTab }}">
        </div>
        <button type="submit" class="btn" style="padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(59,130,246,0.2);">Search</button>
        <a href="{{ route('requests.index', ['tab' => $activeTab]) }}" class="btn-ghost" style="padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">Clear</a>
        <span id="search-spinner" style="display:none; margin-left:12px;"></span>
    </form>
</div>

<div id="requests-list">
@forelse($shown as $req)
    @php $rid = 'req-'.$req->id; @endphp

    <div class="req-card status-{{ $req->status }}">
        <div class="req-header" onclick="toggleReq('{{ $rid }}')">
            <div>
                <div class="req-title">
                    Request from <span style="color:#2563eb;">{{ $req->office }}</span>
                    <span class="muted">•</span>
                    <span class="muted">{{ $req->client?->name ?? 'Client' }}</span>
                    @if($req->member)
                        <span class="muted">•</span>
                        <span style="color:#059669;">Member: {{ $req->member->name }}</span>
                    @endif
                    <span class="muted">•</span>
                    <span class="muted">{{ $req->created_at?->format('M d, Y') }}</span>
                </div>

                <div class="req-sub">
                    <span class="muted">Status:</span>
                    <span class="status-pill status-{{ $req->status }}">{{ strtoupper(str_replace('_',' ', $req->status)) }}</span>
                    <span class="muted" style="margin-left:10px;">Request ID:</span>
                    <b>#{{ $req->id }}</b>
                </div>

                @if($req->reason)
                    <div class="req-sub" style="margin-top:8px;">
                        <span class="muted">Reason:</span>
                        <div style="margin-top:4px; padding:8px 12px; background:linear-gradient(135deg, #f8fafc, #f1f5f9); border:1px solid #e2e8f0; border-radius:8px; color:#475569; font-size:13px; line-height:1.4;">
                            {{ $req->reason }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="req-right">
                Ref. No:
                <span style="color:#0f172a;">
                    #{{ $req->id }}
                </span>
                <div class="muted" style="font-size:12px; font-weight:600; margin-top:4px;">
                    Click to view details
                </div>
            </div>
        </div>

        <div id="{{ $rid }}" class="req-body">
            <div class="muted" style="margin-bottom:10px;">
                <!-- Approve partially by setting Approved Qty per item (0 = rejected item). -->
            </div>

            {{-- ✅ ONE FORM FOR ALL BUTTONS --}}
            <form method="POST" action="{{ route('admin.requests.decision', $req->id) }}">
                @csrf
                @method('PUT')

                <div style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                    <table style="width:100%; border-collapse:collapse;">
                                <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                            <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px; min-width:180px;">Item</th>
                            <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px; min-width:120px;">Requested</th>
                            <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px; min-width:120px;">Available</th>
                            <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px; min-width:160px;">Approved Qty</th>
                        </tr>

                        @forelse($req->items as $item)
                            @php
                                $lineQty = $item->approved_qty ?? $item->requested_qty;
                            @endphp
                            <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; text-align:left;">
                                    <div style="font-weight:700; color:#1e40af; font-size:14px;">{{ $item->stock?->id_no ?? '' }}</div>
                                    <div style="color:#64748b; font-size:14px; margin-top:3px;">{{ $item->stock?->description ?? 'N/A' }}</div>
                                    <div style="color:#64748b; font-size:11px; margin-top:3px;">Unit: {{ $item->stock?->unit ?? '—' }}</div>
                                </td>

                                <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $item->requested_qty }}</td>
                                <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $item->stock?->stock ?? 0 }}</td>
                                <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">{{ $lineQty }}</td>

                                <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; min-width:160px;">
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <input
                                            type="number"
                                            class="approved-qty"
                                            name="approved_qty[{{ $item->id }}]"
                                            min="0"
                                            max="{{ $item->stock?->stock ?? 0 }}"
                                            value="{{ $item->approved_qty ?? 0 }}"
                                            {{ $activeTab !== 'pending' ? 'readonly' : '' }}
                                            style="flex:1; text-align:center; padding:8px 10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; transition:all 0.3s ease; background:#ffffff;"
                                        >
                                        @if($activeTab === 'pending')
                                            <button
                                                type="button"
                                                class="btn-max"
                                                onclick="setMax(this, {{ $item->requested_qty }})"
                                                style="padding:8px 10px; border-radius:8px; border:1px solid #3b82f6; background:#3b82f6; color:#fff; cursor:pointer; font-weight:700; white-space:nowrap; flex-shrink:0; transition:all 0.3s ease;"
                                            >
                                                Max
                                            </button>
                                        @endif
                                    </div>
                                    @if($item->rejection_reason && $item->status === 'rejected')
                                        <div style="margin-top:8px; padding:6px 8px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; color:#991b1b; font-size:11px; line-height:1.3;">
                                            <strong>Reason:</strong> {{ $item->rejection_reason }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr style="background:linear-gradient(135deg, #f8fafc, #f1f5f9);">
                                <td colspan="6" style="padding:20px 10px; text-align:center; color:#64748b; font-size:14px;">No request items found for this request.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>

                @if($req->status !== 'ready_to_receive')
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                        {{-- Save Decision - only show if NOT approved --}}
                        @if($req->status !== 'approved')
                            <button class="btn-ghost" type="button" onclick="handleSaveDecision(this.closest('form'), '{{ $req->id }}')">
                                Save Decision
                            </button>
                        @endif

                        {{-- Only show other buttons if NOT pending --}}
                        @if($req->status !== 'pending')
                            {{-- Reject Whole --}}
                            <button class="btn-ghost" type="button" onclick="rejectEntireRequest('{{ $req->id }}')">
                                Reject Whole Request
                            </button>

                            {{-- Ready to Receive --}}
                            <button class="btn-ghost" type="button" onclick="confirmAction(event, 'ready_to_receive', 'Generate Code', 'Proceed to generate a verification code for the client to claim these items.', '{{ $req->id }}')">
                                Ready to Receive
                            </button>
                        @endif
                    </div>
                @endif
            </form>

            {{-- ✅ ONLY SHOW RELEASE FORM IF READY TO RECEIVE --}}
            @if($req->status === 'ready_to_receive')
                <hr style="border:none; border-top:1px solid #e2e8f0; margin:16px 0;">

                <form method="POST" action="{{ route('admin.requests.release', $req->id) }}">
                    @csrf
                    @method('PUT')

                    <div style="background:#eff6ff; border:1px solid #2563eb; border-radius:10px; padding:12px; display:flex; gap:10px; align-items:flex-end;">
                        <div style="flex:1; min-width:200px;">
                            <label style="font-size:12px; font-weight:700; color:#0f172a; display:block; margin-bottom:6px;">🔐 Client Code</label>
                            <input type="text" name="verification_code" placeholder="Enter code" required style="padding:10px;">
                        </div>

                        <div style="min-width:200px;">
                            <label style="font-size:12px; font-weight:700; color:#0f172a; display:block; margin-bottom:6px;">👤 Received By:</label>
                            <input type="text" name="received_by" placeholder="Enter receiver name" required style="padding:10px; width:100%;">
                        </div>

                        <button class="btn" type="submit" style="padding:10px 16px;">Release</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

@empty
    <div class="muted">No requests found.</div>
@endforelse
</div>

<!-- Confirmation Modal -->
<div id="confirmModal">
    <div class="modal-box">
        <h3 id="modal-title">Confirm</h3>
        <p id="modal-message">Are you sure?</p>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
            <button class="modal-btn modal-btn-confirm" onclick="submitConfirmAction()">Confirm</button>
        </div>
    </div>
</div>

<script>
let pendingAction = null;

function toggleReq(id){
    const el = document.getElementById(id);
    if(!el) return;

    // toggle the body open/closed
    el.classList.toggle('open');

    // also toggle expanded state on the parent card so CSS can style header and card
    const card = el.closest('.req-card');
    if(card) card.classList.toggle('expanded');
}

function setMax(btn, maxValue){
    // Find the input field (previous sibling)
    const input = btn.previousElementSibling;
    if(input && input.tagName === 'INPUT'){
        input.value = maxValue;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function confirmAction(e, status, title, message, requestId){
    e.preventDefault();
    const modal = document.getElementById('confirmModal');
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    pendingAction = { status, form: e.target.closest('form') };
    if(modal) modal.classList.add('show');
}

function closeConfirmModal(){
    const modal = document.getElementById('confirmModal');
    if(modal) modal.classList.remove('show');
    pendingAction = null;
}

function submitConfirmAction(){
    if(pendingAction && pendingAction.form){
        // Only add status field if status is not null (Save Decision has null status)
        if(pendingAction.status !== null){
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'status';
            input.value = pendingAction.status;
            pendingAction.form.appendChild(input);
        }
        pendingAction.form.submit();
    }
    closeConfirmModal();
}

// Close modal on Escape key or background click
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeConfirmModal();
});
document.getElementById('confirmModal')?.addEventListener('click', function(e){
    if(e.target === this) closeConfirmModal();
});

// Live search (AJAX)
(function(){
    const searchForm = document.querySelector('form[action="{{ route('requests.index') }}"]');
    if(!searchForm) return;
    const searchInput = searchForm.querySelector('input[name="q"]');
    const requestsList = document.getElementById('requests-list');
    const url = '{{ route('requests.index') }}';
    let timer = null;

    function fetchResults(q){
        const params = new URLSearchParams();
        params.append('q', q || '');
        params.append('tab', '{{ $activeTab }}');

        const spinner = document.getElementById('search-spinner');
        const noResults = document.getElementById('no-results');
        if(spinner) spinner.style.display = 'inline-block';
        if(noResults) noResults.style.display = 'none';

        fetch(url + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if(spinner) spinner.style.display = 'none';
                if(data.html !== undefined){
                    requestsList.innerHTML = data.html;
                }
                if(typeof data.count !== 'undefined'){
                    if(data.count === 0){
                        if(noResults) noResults.style.display = 'block';
                    } else {
                        if(noResults) noResults.style.display = 'none';
                    }
                }
            }).catch(()=>{ if(spinner) spinner.style.display = 'none'; });
    }

    searchInput.addEventListener('input', function(e){
        clearTimeout(timer);
        timer = setTimeout(()=> fetchResults(this.value.trim()), 350);
    });

    searchForm.addEventListener('submit', function(e){
        e.preventDefault();
        clearTimeout(timer);
        fetchResults(searchInput.value.trim());
    });
})();

</script>

<!-- Rejection Reason Modal -->
<div id="rejectionReasonModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:500px; width:90%; padding:24px;">
        <h3 style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Rejection Reason</h3>
        <p style="margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5;">Please provide a reason for rejecting this item. This will be visible to the client.</p>
        
        <form id="rejectionReasonForm">
            @csrf
            <input type="hidden" id="rejectionItemId" name="item_id">
            <input type="hidden" id="rejectionApprovedQty" name="approved_qty">
            
            <div style="margin-bottom:16px;">
                <label for="rejectionReason" style="display:block; margin-bottom:4px; color:#0f172a; font-weight:600; font-size:13px;">Reason for Rejection *</label>
                <textarea id="rejectionReason" name="rejection_reason" required placeholder="Please explain why this item is being rejected..." style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#fff; min-height:80px; resize:vertical;"></textarea>
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="modal-btn-cancel" onclick="closeRejectionModal()">Cancel</button>
                <button type="submit" class="modal-btn-confirm">Reject Item</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-btn-cancel, .modal-btn-confirm {
    padding:10px 16px; 
    border-radius:10px; 
    border:none; 
    font-weight:700; 
    cursor:pointer; 
    font-size:14px;
    transition: all 0.3s ease;
}
.modal-btn-cancel {
    background:#e2e8f0; 
    color:#0f172a;
}
.modal-btn-cancel:hover {
    background:#cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,.1);
}
.modal-btn-cancel:active {
    transform: translateY(0);
}
.modal-btn-confirm {
    background:#3ec63f; 
    color:#fff;
}
.modal-btn-confirm:hover {
    background:#b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(220,38,38,.2);
}
.modal-btn-confirm:active {
    transform: translateY(0);
}
</style>

<script>
let currentDecisionForm = null;

function handleSaveDecision(formElement, requestId) {
    currentDecisionForm = formElement;
    
    // Check all approved quantities
    const approvedQtyInputs = formElement.querySelectorAll('input[name^="approved_qty"]');
    let hasZeroQty = false;
    let hasPositiveQty = false;
    let rejectedItems = [];
    
    approvedQtyInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const itemId = input.name.match(/\[(\d+)\]/)[1];
        
        if (qty === 0) {
            hasZeroQty = true;
            rejectedItems.push(itemId);
        } else if (qty > 0) {
            hasPositiveQty = true;
        }
    });
    
    if (hasZeroQty && !hasPositiveQty) {
        // All items are rejected - show rejection reason modal for each item
        showRejectionReasonsModal(rejectedItems);
    } else if (hasZeroQty && hasPositiveQty) {
        // Mixed approval/rejection - show rejection reason modal for rejected items
        showRejectionReasonsModal(rejectedItems);
    } else {
        // All items are approved - proceed with normal confirmation
        confirmAction(event, null, 'Save Decision', 'Save the approval quantities for this request?', requestId);
    }
}

function showRejectionReasonsModal(rejectedItemIds) {
    // Clear previous rejection reasons
    const form = currentDecisionForm;
    rejectedItemIds.forEach(itemId => {
        const existingInput = form.querySelector(`input[name="rejection_reason[${itemId}]"]`);
        if (existingInput) {
            existingInput.remove();
        }
    });
    
    // Show modal
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectionReasonModal').style.display = 'flex';
}

function closeRejectionModal() {
    document.getElementById('rejectionReasonModal').style.display = 'none';
    currentDecisionForm = null;
}

function rejectEntireRequest(requestId) {
    currentDecisionForm = document.querySelector(`form[action*="${requestId}"]`);
    if (!currentDecisionForm) {
        console.error('Form not found for request ID:', requestId);
        return;
    }
    
    // Set up for entire request rejection
    document.getElementById('rejectionItemId').value = 'entire_request';
    document.getElementById('rejectionApprovedQty').value = '0';
    
    // Update modal title and description for entire request
    const modalTitle = document.querySelector('#rejectionReasonModal h3');
    const modalDesc = document.querySelector('#rejectionReasonModal p');
    modalTitle.textContent = 'Reject Entire Request';
    modalDesc.textContent = 'Please provide a reason for rejecting this entire request. This will be visible to the client.';
    
    // Update submit button text
    const submitBtn = document.querySelector('#rejectionReasonForm button[type="submit"]');
    submitBtn.textContent = 'Reject Entire Request';
    
    // Show modal
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectionReasonModal').style.display = 'flex';
}

document.getElementById('rejectionReasonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentDecisionForm) return;
    
    const rejectionReason = document.getElementById('rejectionReason').value;
    const itemId = document.getElementById('rejectionItemId').value;
    const approvedQty = document.getElementById('rejectionApprovedQty').value;
    
    // Check if this is an entire request rejection
    if (itemId === 'entire_request') {
        // Add status=rejected parameter for entire request rejection
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'rejected';
        currentDecisionForm.appendChild(statusInput);
        
        // Add rejection reason parameter
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = rejectionReason;
        currentDecisionForm.appendChild(reasonInput);
    } else {
        // Individual item rejection logic (existing)
        const approvedQtyInputs = currentDecisionForm.querySelectorAll('input[name^="approved_qty"]');
        
        // Add rejection reason to all items with 0 approved qty
        approvedQtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            const itemId = input.name.match(/\[(\d+)\]/)[1];
            
            if (qty === 0) {
                // Add rejection reason input
                const rejectionInput = document.createElement('input');
                rejectionInput.type = 'hidden';
                rejectionInput.name = `rejection_reason[${itemId}]`;
                rejectionInput.value = rejectionReason;
                currentDecisionForm.appendChild(rejectionInput);
            }
        });
    }
    
    // Submit the form
    currentDecisionForm.submit();
});

// Close modal on Escape key
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeRejectionModal();
});
</script>
@endsection
