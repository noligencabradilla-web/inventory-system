@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Available Stocks';

  // ✅ FIX: prepare data BEFORE @json to avoid Blade parse error
  $stocksJson = $stocks->map(function ($s) {
      return [
          'id' => $s->id,
          'id_no' => $s->id_no,
          'description' => $s->description,
          'unit' => $s->unit,
          'stock' => $s->stock,
          'category' => $s->category?->name ?? 'Unknown',
      ];
  })->values();
@endphp

@section('sidebar')
    @include('client.sidebar')
@endsection

@section('content')
<style>
    .btn{
        padding:10px 12px;
        border-radius:12px;
        border:1px solid #2563eb;
        background:#2563eb;
        color:#fff;
        cursor:pointer;
        font-weight:700;
        transition: all 0.3s ease;
    }
    .btn:hover{ 
        opacity:.92;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37,99,235,.2);
    }
    .btn:active{
        transform: translateY(0);
    }
    .btn-ghost{
        padding:10px 12px;
        border-radius:12px;
        border:1px solid #e2e8f0;
        background:#fff;
        color:#0f172a;
        cursor:pointer;
        font-weight:700;
        transition: all 0.3s ease;
    }
    .btn-ghost:hover{ 
        background:#f8fafc;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,.08);
    }
    .btn-ghost:active{
        transform: translateY(0);
    }

    .btn-cancel{
        padding:10px 12px;
        border-radius:12px;
        border:1px solid #dc2626; /* red border */
        background:#fff;
        color:#dc2626; /* red text */
        cursor:pointer;
        font-weight:700;
        transition: all 0.3s ease;
    }
    .btn-cancel:hover{ 
        background:#b91c1c; 
        color:#fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(220,38,38,.2);
    }
    .btn-cancel:active{
        transform: translateY(0);
    }

    table{ width:100%; border-collapse: collapse; margin-bottom:16px; border-radius:12px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe); }
    th, td{ border:1px solid #e0e7ff; padding:10px; text-align:left; }
    th{ background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; font-size:12px; text-shadow:0 2px 4px rgba(0,0,0,0.3); border-bottom:2px solid #1e40af; }
    tr:hover{ background:linear-gradient(135deg, #ffffff, #f8fafc); }

    .pill{
        display:inline-block;
        padding:4px 10px;
        border-radius:999px;
        font-size:12px;
        border:1px solid #e2e8f0;
        background:#f8fafc;
        color:#475569;
        font-weight:700;
    }
    .pill.ok{ border-color:#bbf7d0; background:#ecfdf5; color:#065f46; }
    .pill.bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b; }
    .pill.low{ border-color: rgba(249,115,22,.35); background: rgba(249,115,22,.10); color: var(--orange); }

    /* Modal */
    .modal{
        display:none;
        position:fixed;
        inset:0;
        background: rgba(15,23,42,.65);
        z-index:9999;
        padding:24px;
        backdrop-filter: blur(4px);
    }
    .modal.active{
        display:flex;
        align-items:center;
        justify-content:center;
        overflow-y:auto;
        animation: modalFadeIn 0.3s ease;
    }
    .modal-card{
        max-width: 1100px; /* Increased from 950px to 1100px */
        width:100%;
        max-height: calc(100vh - 48px);
        background: linear-gradient(135deg, #ffffff, #fafbfc);
        border:1px solid #e2e8f0;
        border-radius:20px;
        overflow:hidden;
        box-shadow:0 25px 60px rgba(15,23,42,.25);
        flex-shrink:0;
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        width: 115%; /* Expanded from 100% to 115% */
    }
    .modal-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
        padding:20px 24px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-bottom:1px solid rgba(99, 102, 241, 0.2);
        position: relative;
    }
    .modal-head::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
        animation: shimmer 3s infinite;
    }
    .modal-title{
        font-size:20px;
        font-weight:800;
        color:#ffffff;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .modal-title::before {
        content: 'create';
        font-size: 16px;
        background: rgba(255,255,255,0.2);
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
    }
    .modal-body{
        padding:24px;
        display:grid;
        grid-template-columns: 1.3fr .7fr;
        gap:20px;
        background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
        min-height: 500px; /* Ensure enough height for all content */
        text-align: center; /* Center the table in cart container */
    }
    
    /* show cart toggle button on mobile */
    @media (max-width: 640px){
        .cart-toggle{ display:flex !important; }
    }

    /* Mobile: hide cart by default, show search items */
    @media (max-width: 640px){
        .modal-body{
            grid-template-columns: 1fr;
        }
        .modal-body > .cart{
            display:none;
        }
        /* When toggled, hide search and show cart only */
        .modal-body.show-cart > div:first-child{
            display:none !important;
        }
        .modal-body.show-cart > .cart{
            display:block !important;
        }
    }
    .field{
        display:flex;
        flex-direction:column;
        gap:8px;
        margin-bottom:16px;
        align-items: flex-start;
    }
    .field label{
        font-weight: 700;
        color: #1e293b;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }
    .field label b{
        color: #6366f1;
    }
    input, select{
        padding:12px 16px;
        border-radius:12px;
        border:2px solid #e2e8f0;
        outline:none;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    input:focus, select:focus{ 
        border-color:#6366f1; 
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        transform: translateY(-1px);
    }
    .muted{ color:#64748b; font-size:13px; font-weight: 500; }

    .cart{ 
        border:2px solid #e2e8f0;
        border-radius:16px;
        overflow:hidden;
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        width: 100%; /* Increased from 90% to 100% to make it wider */
        margin-right: 40px; /* Increased space on the right side */
        padding-right: 20px; /* Added padding to ensure quantity field is visible */
    }
    .cart:hover{
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .cart-head{
        padding:12px 16px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-bottom:1px solid rgba(99, 102, 241, 0.2);
        font-weight:500;
        color:#ffffff; /* Fixed color - was cutted */
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        position: relative;
        line-height: 1.2;
    }
    .cart-head::before {
        content: 'cart';
        font-size: 9px;
        background: rgba(255,255,255,0.15);
        padding: 2px 5px;
        border-radius: 3px;
        font-weight: 500;
        font-family: monospace;
        margin-right: 2px;
    }
    .cart-head::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    }
    .cart-body{ 
        padding:18px;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        background: linear-gradient(135deg, #ffffff, #fafbfc);
        border-top: 1px solid rgba(99, 102, 241, 0.1);
    }
    .cart-row{
        display:flex;
        gap:12px;
        align-items:center;
        justify-content:space-between;
        padding:14px 0;
        border-bottom:1px solid #f1f5f9;
        transition: all 0.2s ease;
        border-radius: 8px;
        margin-bottom: 4px;
    }
    .cart-row:hover{
        background: rgba(99, 102, 241, 0.05);
        padding-left: 8px;
        padding-right: 8px;
        transform: translateX(4px);
    }
    .cart-row:last-child{ border-bottom:none; }
    .qty{
        width:100px;
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .qty:focus{
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .modal-btn-cancel, .modal-btn-confirm {
        padding:12px 20px; 
        border-radius:12px; 
        border:none; 
        font-weight:700; 
        cursor:pointer; 
        font-size:14px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }
    .modal-btn-cancel {
        background: linear-gradient(135deg, #94a3b8, #6b7280); 
        color:#ffffff;
        box-shadow: 0 4px 12px rgba(107, 114, 128, 0.2);
    }
    .modal-btn-cancel:hover {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
    }
    .modal-btn-cancel:active {
        transform: translateY(0) scale(0.98);
    }
    .modal-btn-confirm {
        background: linear-gradient(135deg, #6366f1, #4f46e5); 
        color:#ffffff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }
    .modal-btn-confirm:hover {
        background: linear-gradient(135deg, #4f46e5, #374151);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
    }
    .modal-btn-confirm:active {
        transform: translateY(0) scale(0.98);
    }
    
    /* Animations */
    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes modalSlideIn {
        from { 
            opacity: 0; 
            transform: translateY(30px) scale(0.95); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
    <div>
        <h2 style="margin:0;">Available Stocks</h2>
        <div class="muted">Create one request with multiple items.</div>
    </div>
    <button class="btn" onclick="openReqModal()">+ Create Request</button>
</div>

<div style="overflow:auto; border-radius:14px; border:1px solid #e2e8f0;">
    <table>
        <tr>
            <th style="min-width:120px;">ID No</th>
            <th style="min-width:220px;">Description</th>
            <th style="min-width:110px;">Category</th>
            <th style="min-width:80px;">Unit</th>
            <th style="min-width:120px;">Stock</th>
        </tr>
        @foreach($stocks as $s)
            @if($s->stock > 0)
            <tr>
                <td><b>{{ $s->id_no }}</b></td>
                <td>{{ $s->description }}</td>
                <td>{{ $s->category?->name ?? 'Unknown' }}</td>
                <td>{{ $s->unit }}</td>
                <td>
                    @if($s->stock >= 50)
                        <span class="pill ok">Available</span>
                    @else
                        <span class="pill low">Available</span>
                    @endif
                </td>
            </tr>
            @endif
        @endforeach
    </table>
</div>

{{-- CONFIRMATION MODAL --}}
<div id="confirmModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:420px; width:90%; padding:24px;">
        <h3 id="confirmTitle" style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Confirm</h3>
        <p id="confirmMessage" style="margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5;">Are you sure?</p>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="modal-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="modal-btn-confirm" onclick="confirmAction()">Confirm</button>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal" id="reqModal">
    <div class="modal-card">
        <div class="modal-head">
            <div style="flex:1;">
                <div class="modal-title">Create Request (Multiple Items)</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500; margin-top: 4px;">Search items, add to list, set quantities, then submit. <button class="btn-ghost" type="button" onclick="closeReqModal()" style="position:absolute; top:14px; right:16px; background:none; border:none; color:#ffffff; font-size:18px; padding:0; width:24px; height:24px; display:flex; align-items:center; justify-content:center;">×</button></div>
                 
            </div>
           
        </div>

        <form method="POST" action="{{ route('client.requests.store') }}">
            @csrf

            <!-- cart toggle button for mobile -->
            <button type="button" id="cartToggleBtn" class="cart-toggle btn-ghost" onclick="toggleCartView()" title="Toggle View" style="display:none; width:100%; padding:12px; margin:0 0 12px 0; justify-content:center; gap:8px;">
                See Cart
                <!-- cart icon SVG -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.1 5H19M7 13v8a2 2 0 002 2h10a2 2 0 002-2v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                    <circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                </svg>
            </button>

            <div class="modal-body">
                {{-- LEFT: search & add --}}
                <div>
                    <div class="field" style="align-items: flex-start;">
                        <label style="margin-bottom: 4px;"><b>Office</b></label>
                        <div class="muted" style="margin-top: 0;">{{ Auth::user()->office ?? '-' }}</div>
                        <input type="hidden" name="office" value="{{ Auth::user()->office ?? '' }}">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div class="field">
                        <label><b>Requesting Member (Optional)</b></label>
                        <select name="member_id" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:10px; font-size:13px; background:#ffffff;">
                            <option value="">-- Select Member (Optional) --</option>
                            @if(auth()->user()->role === 'client')
                                @php
                                    $clientMembers = \App\Models\ClientMember::where('client_id', auth()->id())->get();
                                @endphp
                                @foreach($clientMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                                @endforeach
                            @endif
                        </select>
                        <!-- <div class="muted" style="font-size:11px; margin-top:4px;">Select a member if this request is on behalf of someone else.</div> -->
                    </div>

                    <div class="field">
                        <label><b>Reason for Request (Optional)</b></label>
                        <textarea name="reason" placeholder="Please provide a reason for this request..." style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:10px; font-size:13px; background:#ffffff; min-height:60px; resize:vertical;">{{ old('reason') }}</textarea>
                        <!-- <div class="muted" style="font-size:11px; margin-top:4px;">Explain why you need these items (optional but helpful for admin).</div> -->
                    </div>

                                    </div>

                    <div class="field">
                        <label><b>Search Item</b></label>
                        <input type="text" id="searchBox" placeholder="Type to search..." oninput="renderStockList()">
                        <div class="muted">Tip: search by ID No or Description.</div>
                    </div>

                    <div style="border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; background: linear-gradient(135deg, #fafbfc, #ffffff);">
                        <div style="max-height: 400px; overflow-y: auto; overflow-x: auto;">
                            <table style="margin:0; position: sticky; top: 0; background: linear-gradient(135deg, #fafbfc, #ffffff);">
                                <thead>
                                    <tr>
                                        <th>ID No</th>
                                        <th>Description</th>
                                        <th>Stock</th>
                                        <th style="width:140px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="stockList"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: cart --}}
                <div class="cart">
                    <div class="cart-head">Selected Items</div>
                    <div class="cart-body">
                        <div id="cartEmpty" class="muted">No items selected yet.</div>
                        <div id="cartRows"></div>

                        <div style="display:flex; gap:10px; margin-top:12px;">
                            <button type="button" class="btn" style="flex:1;" onclick="confirmSubmitRequest()">Submit Request</button>
                            <button type="button" class="btn-ghost" onclick="clearCart()">Clear</button>
                        </div>

                        <div class="muted" style="margin-top:10px;">
                            Admin may approve partially depending on availability.
                        </div>
                    </div>
                </div>
            </div>

            {{-- hidden inputs will be injected here --}}
            <div id="hiddenInputs"></div>
        </form>
    </div>
</div>

<script>

const STOCKS = @json($stocksJson);

let cart = {}; // { stockId: qty }

function toggleCartView(){
    const body = document.querySelector('#reqModal .modal-body');
    body.classList.toggle('show-cart');
    const btn = document.getElementById('cartToggleBtn');
    if (body.classList.contains('show-cart')) {
        btn.innerHTML = `Stocks <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.1 5H19M7 13v8a2 2 0 002 2h10a2 2 0 002-2v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                    <circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                </svg>`;
    } else {
        btn.innerHTML = `See Cart <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.1 5H19M7 13v8a2 2 0 002 2h10a2 2 0 002-2v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                    <circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="2"/>
                </svg>`;
    }
}

function openReqModal(){
    const modal = document.getElementById('reqModal');
    modal.classList.add('active');
    renderStockList();
    renderCart();
}
function closeReqModal(){
    const modal = document.getElementById('reqModal');
    modal.classList.remove('active');
}
document.addEventListener('click', (e) => {
    const modal = document.getElementById('reqModal');
    if (e.target === modal) closeReqModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReqModal();
});

function renderStockList(){
    const q = (document.getElementById('searchBox').value || '').toLowerCase().trim();
    const tbody = document.getElementById('stockList');
    tbody.innerHTML = '';

    const filtered = STOCKS.filter(s => {
        // exclude out-of-stock items from the modal list
        if (s.stock <= 0) return false;
        if (!q) return true;
        return (s.id_no || '').toLowerCase().includes(q) || (s.description || '').toLowerCase().includes(q);
    });

    filtered.forEach(s => {
        const btnText = cart[s.id] ? 'Added' : 'Add';
        const btnDisabled = cart[s.id] ? 'disabled' : '';
        const tr = document.createElement('tr');
        // show availability level only (no numeric count)
        const stockBadge = s.stock >= 50 ? `<span class="pill ok">Available</span>` : `<span class="pill low">Available</span>`;
        tr.innerHTML = `
            <td><b>${escapeHtml(s.id_no)}</b></td>
            <td>${escapeHtml(s.description)}</td>
            <td>${stockBadge}</td>
            <td>
                <button type="button" class="btn-ghost" ${btnDisabled} onclick="addToCart(${s.id})">${btnText}</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function addToCart(stockId){
    const s = STOCKS.find(x => x.id === stockId);
    if (!s || s.stock <= 0) return;
    cart[stockId] = 1;
    renderStockList();
    renderCart();
}

function removeFromCart(stockId){
    delete cart[stockId];
    renderStockList();
    renderCart();
}

function updateQty(stockId, max){
    let v = parseInt(document.getElementById('qty_'+stockId).value || '1', 10);
    if (isNaN(v) || v < 1) v = 1;
    if (v > max) v = max;
    cart[stockId] = v;
    document.getElementById('qty_'+stockId).value = v;
    renderCart();
}

function clearCart(){
    cart = {};
    renderStockList();
    renderCart();
}

function renderCart(){
    const rows = document.getElementById('cartRows');
    const empty = document.getElementById('cartEmpty');
    rows.innerHTML = '';

    const keys = Object.keys(cart);
    if (keys.length === 0){
        empty.style.display = 'block';
        renderHiddenInputs();
        return;
    }
    empty.style.display = 'none';

    // Create table for cart items
    const table = document.createElement('table');
    table.style.cssText = 'width: 100%; border-collapse: collapse; margin-bottom:16px; margin-right: 20px;';
    
    // Create table header
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th style="padding: 8px 12px; border: 1px solid #e2e8f0; background: #f8fafc; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">ID</th>
            <th style="padding: 8px 12px; border: 1px solid #e2e8f0; background: #f8fafc; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Description</th>
            <th style="padding: 8px 12px; border: 1px solid #e2e8f0; background: #f8fafc; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Unit</th>
            <th style="padding: 8px 12px; border: 1px solid #e2e8f0; background: #f8fafc; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Qty</th>
                    </tr>
    `;
    
    const tbody = document.createElement('tbody');
    let grandTotal = 0;

    keys.forEach(k => {
        const stockId = parseInt(k, 10);
        const s = STOCKS.find(x => x.id === stockId);
        if (!s) return;

        const qty = cart[stockId];
        const total = qty;
        grandTotal += total;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding: 10px 12px; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 600; font-size: 12px;">${escapeHtml(s.id_no)}</td>
            <td style="padding: 10px 12px; border: 1px solid #e2e8f0; color: #475569; font-size: 12px;">${escapeHtml(s.description)}</td>
            <td style="padding: 10px 12px; border: 1px solid #e2e8f0; color: #64748b; font-size: 12px;">${escapeHtml(s.unit)}</td>
            <td style="padding: 10px 12px; border: 1px solid #e2e8f0; text-align: center;">
                <input class="qty" id="qty_${stockId}" type="number" min="1" max="${s.stock}"
                       value="${qty}" onchange="updateQty(${stockId}, ${s.stock})" 
                       style="width: 60px; padding: 4px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center;">
            </td>
                                `;
        tbody.appendChild(tr);
    });

    table.appendChild(thead);
    table.appendChild(tbody);
    rows.appendChild(table);

    // Add grand total
    const totalDiv = document.createElement('div');
    totalDiv.style.cssText = 'margin-top:12px; padding:12px; background:linear-gradient(135deg, #f8fafc, #f1f5f9); border:1px solid #e2e8f0; border-radius:8px; text-align:right; font-weight:700; color:#1e293b; font-size: 14px;';
    totalDiv.innerHTML = `Grand Total: <span style="color: #6366f1; font-weight: 800;">${grandTotal}</span>`;
    rows.appendChild(totalDiv);

    renderHiddenInputs();
}

function renderHiddenInputs(){
    const holder = document.getElementById('hiddenInputs');
    holder.innerHTML = '';

    Object.keys(cart).forEach(k => {
        const stockId = parseInt(k, 10);
        const qty = cart[stockId];

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `items[${stockId}]`;
        input.value = qty;

        holder.appendChild(input);
    });
}

function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"']/g, m => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
    }[m]));
}

// --- single, reliable confirm/submit implementation ---
let __confirmCb = null;

function confirmSubmitRequest(){
    const keys = Object.keys(cart);
    if (keys.length === 0) {
        alert('Please add at least one item.');
        return;
    }

    // ensure hidden inputs exist before showing confirmation
    renderHiddenInputs();

    // show modal and set callback name
    showConfirmModal('Submit Request', 'Submit this request for approval? You can view and manage it in My Requests.', 'submitRequestConfirmed');
}

function submitRequestConfirmed(){
    // ensure hidden inputs are up-to-date then submit
    renderHiddenInputs();
    showLoading('Submitting request...');
    const form = document.querySelector('#reqModal form');
    if (form) {
        // disable submit button to avoid double-submits
        const btn = form.querySelector('button[type="button"].btn');
        if (btn) btn.disabled = true;
        form.submit();
    }
}

function showConfirmModal(title, message, callbackName){
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    window.__confirmCb = callbackName || null;
    document.getElementById('confirmModalOverlay').style.display = 'flex';
}

function closeConfirmModal(){
    document.getElementById('confirmModalOverlay').style.display = 'none';
    window.__confirmCb = null;
}

function confirmAction(){
    if (window.__confirmCb && typeof window[window.__confirmCb] === 'function') {
        window[window.__confirmCb]();
    }
    closeConfirmModal();
}
</script>
@endsection
