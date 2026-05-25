@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $activeTab = request('tab', 'settings');
  $pageTitle = $activeTab === 'inventory' ? 'Report' : ($activeTab === 'members' ? 'Members' : ($activeTab === 'subaccounts' ? 'Offices' : 'Account Settings'));
  
  // Define clientMembers for use throughout the view
  if (auth()->user()->role === 'client') {
      $clientMembers = \App\Models\ClientMember::where('client_id', auth()->id())
          ->with(['distributions.stockRequestItem.stock', 'directDeductions.stockRequestItem.stock'])
          ->get();
      
      // Create memberReports using the same logic as Report tab
      $allClientMembers = \App\Models\ClientMember::where('client_id', auth()->id())->get();
      
      // Get all ClientMemberDistribution records
      $allDistributions = \App\Models\ClientMemberDistribution::with(['member', 'stockRequestItem.stock'])
          ->whereHas('member', function ($query) {
              $query->where('client_id', auth()->id());
          })->get();
      
      // Get all ClientDirectDeduction records (for direct request items)
      $allDirectDeductions = \App\Models\ClientDirectDeduction::with(['member'])
          ->whereHas('member', function ($query) {
              $query->where('client_id', auth()->id());
          })
          ->where('stock_request_item_id', null) // Only direct request items
          ->get();
      
      // Group distributions by member
      $distributionsByMember = $allDistributions->groupBy('member_id');
      $directDeductionsByMember = $allDirectDeductions->groupBy('member_id');
      
      $memberReports = $allClientMembers->map(function ($member) use ($distributionsByMember, $directDeductionsByMember) {
          $memberDistributions = $distributionsByMember->get($member->id, collect());
          $memberDirectDeductions = $directDeductionsByMember->get($member->id, collect());
          
          // Regular distributions
          $distributedQty = $memberDistributions->sum('distributed_qty');
          $usedQty = \Illuminate\Support\Facades\Schema::hasColumn('client_member_distributions', 'used_qty') ? $memberDistributions->sum('used_qty') : 0;
          
          // Separate original direct request items from usage records
          $originalDirectItems = $memberDirectDeductions->filter(function ($deduction) {
              return !str_contains($deduction->reason ?? '', 'Used from direct request');
          });
          $usageRecords = $memberDirectDeductions->filter(function ($deduction) {
              return str_contains($deduction->reason ?? '', 'Used from direct request');
          });
          
          // Original direct request items (count as distributed and available)
          $directDistributedQty = $originalDirectItems->sum('deducted_qty');
          $directAvailableQty = $originalDirectItems->sum('deducted_qty');
          
          // Combine both types
          $totalDistributed = $distributedQty + $directDistributedQty;
          $totalUsed = $usedQty + $usageRecords->sum('deducted_qty'); // Add usage records to used
          $availableQty = ($distributedQty - $usedQty) + $directAvailableQty; // Original direct items add to available
          
          return [
              'name' => $member->name,
              'email' => $member->email,
              'distributed_items' => $totalDistributed,
              'available_items' => max(0, $availableQty),
              'used_items' => $totalUsed,
          ];
      });
  } else {
      $clientMembers = collect();
      $memberReports = collect();
  }
@endphp

@section('sidebar')
    @include('client.sidebar')
@endsection

@section('content')
<style>
    .account-container{
        width: 100%;
        max-width: none;
        margin: 24px 0;
        padding: 0 16px;
        box-sizing: border-box;
    }
    
    .settings-card{
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 18px;
        margin-bottom: 18px;
        box-shadow: 0 10px 25px rgba(15,23,42,.08);
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }
    .settings-card:hover{
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(15,23,42,.12);
    }
    
    .card-header{
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        background: rgba(37,99,235,.08);
        border-bottom: 1px solid var(--line);
    }
    
    .card-header:hover{
        background: rgba(37,99,235,.16);
    }
    
    .card-header h3{
        margin: 0;
        color: var(--text);
        font-size: 18px;
        font-weight: 700;
    }
    
    .card-toggle{
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .2px;
    }
    .card-toggle::after{
        content: '▾';
        display: inline-block;
        transition: transform .2s ease;
    }
    .card-header.open .card-toggle::after{
        transform: rotate(-180deg);
    }
    
    .card-body{
        display: none;
        padding: 18px;
        background: rgba(255,255,255,.8);
        border-radius: 0 0 18px 18px;
        animation: fadeIn 180ms ease-out;
    }
    
    .card-body.open{ display: block; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    

    .form-group{
        margin-bottom: 16px;
    }
    .form-group label{
        display: block;
        margin-bottom: 8px;
        color: var(--text);
        font-weight: 700;
        font-size: 14px;
    }
    .form-group input{
        width: 100%;
        padding: 10px;
        border: 1px solid var(--line);
        border-radius: 8px;
        font-size: 14px;
        background: white;
        color: var(--text);
        transition: border-color .2s;
        box-sizing: border-box;
    }
    .form-group input:focus{
        outline: none;
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    
    .form-group input:disabled{
        background: var(--line);
        color: var(--muted);
        cursor: not-allowed;
    }

    .btn-submit{
        display: inline-block;
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(59,130,246,0.1);
    }
    .btn-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    .btn-submit:hover{
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(30,64,175,0.3);
    }
    .btn-submit:hover::before {
        left: 100%;
    }
    .btn-submit:active{
        transform: translateY(0) scale(0.98);
        box-shadow: 0 2px 8px rgba(30,64,175,0.2);
    }
    .btn-submit:disabled {
        background: linear-gradient(135deg, #6b7280, #475569);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    .btn-submit:disabled::before {
        display: none;
    }

    .alert{
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        border: 1px solid;
    }
    .alert-success{
        background: rgba(22,163,74,.1);
        border-color: rgba(22,163,74,.3);
        color: #16a34a;
    }
    .alert-error{
        background: rgba(220,38,38,.1);
        border-color: rgba(220,38,38,.3);
        color: #dc2626;
    }

    .error-list{
        margin: 0;
        padding-left: 20px;
    }
    .error-list li{
        margin: 4px 0;
    }
    
    .info-section{
        display: grid;
        gap: 12px;
    }
    
    .info-item{
        border-bottom: 1px solid var(--line);
        padding-bottom: 12px;
    }
    
    .info-item:last-child{
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .info-label{
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }
    
    .info-value{
        margin: 4px 0 0;
        color: var(--text);
        font-weight: 700;
    }
    
    /* Confirmation modal */
    #confirmModal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center; }
    #confirmModal.show{ display:flex; }
    .confirm-box{ background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:420px; width:90%; padding:24px; }
    .confirm-box h3{ margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800; }
    .confirm-box p{ margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5; }
    .confirm-buttons{ display:flex; gap:10px; justify-content:flex-end; }
    .confirm-btn-cancel{ padding:10px 16px; border-radius:10px; border:none; background:#e2e8f0; color:#0f172a; font-weight:700; cursor:pointer; font-size:14px; transition: all 0.3s ease; }
    .confirm-btn-cancel:hover{ background:#cbd5e1; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,.1); }
    .confirm-btn-cancel:active{ transform: translateY(0); }
    .confirm-btn-confirm{ padding:10px 16px; border-radius:10px; border:none; background:#2563eb; color:#fff; font-weight:700; cursor:pointer; font-size:14px; transition: all 0.3s ease; }
    .confirm-btn-confirm:hover{ background:rgba(37,99,235,.9); transform: translateY(-2px); box-shadow: 0 6px 12px rgba(37,99,235,.2); }
    .confirm-btn-confirm:active{ transform: translateY(0); }

    /* Distribution modal */
    #distributionModal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease; }
    #distributionModal.show{ display:flex; }
    .distribution-box{ background:#fff; border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,.3); max-width:650px; width:100%; max-height:85vh; overflow:hidden; animation:slideUp .4s ease; }
    .distribution-header{ position:relative; padding:24px 28px; border-bottom:1px solid var(--line); background:linear-gradient(135deg, #f8fafc, #fff); }
    .distribution-header h3{ margin:0; font-size:20px; color:var(--text); font-weight:800; }
    .distribution-body{ padding:28px; overflow-y:auto; max-height:calc(85vh - 80px); }
    .distribution-close{ position:absolute; top:16px; right:20px; background:none; border:none; font-size:28px; cursor:pointer; color:var(--muted); transition:color .2s ease; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; }
    .distribution-close:hover{ color:var(--text); background:rgba(0,0,0,.05); }

    /* Form enhancements */
    .form-group select:focus, .form-group input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
    .btn-submit:hover { background:#1d4ed8; transform:translateY(-1px); box-shadow:0 4px 12px rgba(37,99,235,.3); }
    .btn-submit:active { transform:translateY(0); }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Report Generator Styles */
    .report-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.08);
        transition: transform .2s, box-shadow .2s;
    }
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15,23,42,.12);
    }
    .report-card-header {
        padding: 16px 18px;
        background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(37,99,235,.04));
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .report-card-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
    }
    .report-card-body {
        padding: 18px;
        background: rgba(255,255,255,.9);
    }
    .report-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-size: 14px;
    }
    .report-stat:last-child {
        margin-bottom: 0;
    }
    .stat-label {
        color: var(--muted);
        font-weight: 600;
    }
    .stat-value {
        font-weight: 700;
        color: var(--text);
    }
    .text-success { color: #16a34a !important; }
    .text-danger { color: #dc2626 !important; }

    .report-section {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(15,23,42,.08);
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .report-table th {
        padding: 12px 10px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #374151;
        font-weight: 700;
        text-align: left;
        border-bottom: 2px solid #1e40af;
        font-size: 12px;
    }
    .report-table td {
        padding: 14px 10px;
        border-bottom: 1px solid #e0e7ff;
        color: #475569;
        font-weight: 600;
    }
    .report-table tr:hover {
        background: linear-gradient(135deg, #ffffff, #f8fafc);
    }
    .subaccount-row {
        background: rgba(37,99,235,.06) !important;
        font-weight: 700;
    }
    .subaccount-row td {
        border-bottom: 2px solid var(--line);
    }

</style>

<!-- Confirmation Modal -->
<div id="confirmModal">
    <div class="confirm-box">
        <h3 id="modal-title">Confirm</h3>
        <p id="modal-text">Are you sure?</p>
        <div class="confirm-buttons">
            <button type="button" class="confirm-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="confirm-btn-confirm" onclick="submitConfirmedForm()">Confirm</button>
        </div>
    </div>
</div>

<!-- Distribution Modal -->
<div id="distributionModal">
    <div class="distribution-box">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeDistributionModal()">&times;</button>
            <h3>Distribute Items to Subaccounts</h3>
        </div>
        <div class="distribution-body">
            <div style="margin-bottom:20px; padding:16px; background:rgba(37,99,235,.05); border-radius:12px; border-left:4px solid #2563eb;">
                <!-- <p style="color: var(--muted); margin: 0; font-size:14px; line-height:1.5;">📦 Distribute items directly from your inventory to subaccounts. This will deduct from your "My Inventory".</p> -->
            </div>

            @if($approvedInventory->isEmpty())
                <div style="text-align:center; padding:40px; color:var(--muted);">
                    <div style="font-size:48px; margin-bottom:16px;">📭</div>
                    <p>No items are available in your inventory for distribution.</p>
                </div>
            @elseif($subaccounts->isEmpty())
                <div style="text-align:center; padding:40px; color:var(--muted);">
                    <div style="font-size:48px; margin-bottom:16px;">👥</div>
                    <p>Create a subaccount first before distributing items.</p>
                </div>
            @else
                <form method="POST" action="{{ route('client.account.distributeToSubaccounts') }}" style="display:grid; gap:20px;">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label for="subaccount_id_dist" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                                <span>🏢</span> Subaccount
                            </label>
                            <select id="subaccount_id_dist" name="subaccount_id" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                                <option value="">-- select subaccount --</option>
                                @foreach($subaccounts as $subaccount)
                                    <option value="{{ $subaccount->id }}">{{ $subaccount->name }}</option>
                                @endforeach
                            </select>
                            @error('subaccount_id')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="stock_request_item_id_dist" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                                <span>📦</span> Item from My Inventory
                            </label>
                            <select id="stock_request_item_id_dist" name="stock_request_item_id" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                                @foreach($approvedInventory as $item)
                                    @php
                                        $distributed = $item->distributed_qty ?? 0;
                                        $myInventory = max(0, $item->approved_qty - $distributed);
                                    @endphp
                                    @if($myInventory > 0)
                                    <option value="{{ $item->id }}">{{ $item->stock->description ?? $item->stock->name ?? 'Item' }} @if(isset($item->stock->price)) (₱{{ number_format($item->stock->price, 2) }}) @endif — available: {{ $myInventory }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('stock_request_item_id')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="allocated_qty_dist" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                            <span>🔢</span> Quantity to Distribute
                        </label>
                        <input type="number" id="allocated_qty_dist" name="allocated_qty" min="1" required value="{{ old('allocated_qty', 1) }}" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                        @error('allocated_qty')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                    </div>
                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                        <button type="button" onclick="closeDistributionModal()" style="padding:12px 20px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                        <button type="submit" class="btn-submit" style="padding:12px 20px; border:none; background:#2563eb; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">📤 Distribute Item</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease;">
    <div class="distribution-box">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeAddMemberModal()">&times;</button>
            <h3>Add New Member</h3>
        </div>
        <div class="distribution-body">
            <div style="margin-bottom:20px; padding:16px; background:rgba(37,99,235,.05); border-radius:12px; border-left:4px solid #2563eb;">
                <p style="color: var(--muted); margin: 0; font-size:14px; line-height:1.5;">👤 Add members to your client account to track distributed supplies.</p>
            </div>

            <form method="POST" action="{{ route('client.account.members.store') }}" style="display:grid; gap:20px;">
                @csrf
                <div class="form-group">
                    <label for="member_name_modal" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>👤</span> Member Name
                    </label>
                    <input type="text" id="member_name_modal" name="name" placeholder="e.g. John Doe" required value="{{ old('name') }}" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                    @error('name')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="member_email_modal" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>📧</span> Member Email
                    </label>
                    <input type="email" id="member_email_modal" name="email" placeholder="Enter member email" required value="{{ old('email') }}" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                    @error('email')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                    <button type="button" onclick="closeAddMemberModal()" style="padding:12px 20px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                    <button type="submit" class="btn-submit" style="padding:12px 20px; border:none; background:#2563eb; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">➕ Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Distribute Items Modal -->
<div id="distributeMemberModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease;">
    <div class="distribution-box">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeDistributeModal()">&times;</button>
            <h3>Distribute Items to Members</h3>
        </div>
        <div class="distribution-body">
            <div style="margin-bottom:20px; padding:16px; background:rgba(22,163,74,.05); border-radius:12px; border-left:4px solid #16a34a;">
                <p style="color: var(--muted); margin: 0; font-size:14px; line-height:1.5;">📤 Distribute items directly from your inventory to members.</p>
            </div>

            @if($approvedInventory->isEmpty())
                <div style="text-align:center; padding:40px; color:var(--muted);">
                    <div style="font-size:48px; margin-bottom:16px;">📭</div>
                    <p>No items are available in your inventory for distribution.</p>
                </div>
            @else
                <form id="distributeToMemberForm" style="display:grid; gap:20px;">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label for="member_id_dist_modal" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                                <span>👤</span> Member
                            </label>
                            <select id="member_id_dist_modal" name="member_id" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                                <option value="">-- select member --</option>
                                @foreach($clientMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                                @endforeach
                            </select>
                            <div id="member_id_error" style="color: var(--danger); font-size: 12px; display:none;"></div>
                        </div>
                        <div class="form-group">
                            <label for="stock_request_item_id_member_dist_modal" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                                <span>📦</span> Item from My Inventory
                            </label>
                            <select id="stock_request_item_id_member_dist_modal" name="stock_request_item_id" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                                @foreach($approvedInventory as $item)
                                    @php
                                        $distributed = $item->distributed_qty ?? 0;
                                        $myInventory = max(0, $item->approved_qty - $distributed);
                                    @endphp
                                    @if($myInventory > 0)
                                    <option value="{{ $item->id }}">{{ $item->stock->description ?? $item->stock->name ?? 'Item' }} @if(isset($item->stock->price)) ({{ number_format($item->stock->price, 2) }}) @endif — available: {{ $myInventory }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div id="stock_request_item_id_error" style="color: var(--danger); font-size: 12px; display:none;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="distributed_qty_member_dist_modal" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                            <span>🔢</span> Quantity to Distribute
                        </label>
                        <input type="number" id="distributed_qty_member_dist_modal" name="distributed_qty" min="1" required value="{{ old('distributed_qty', 1) }}" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                        <div id="distributed_qty_error" style="color: var(--danger); font-size: 12px; display:none;"></div>
                    </div>
                    <div id="distribution-success-message" style="display:none; margin-bottom:16px; padding:12px; background:rgba(22,163,74,.1); border:1px solid rgba(22,163,74,.3); border-radius:8px; color:#16a34a; font-weight:600;">
                        Item distributed to member successfully!
                    </div>
                    <div id="distribution-error-message" style="display:none; margin-bottom:16px; padding:12px; background:rgba(220,38,38,.1); border:1px solid rgba(220,38,38,.3); border-radius:8px; color:#dc2626; font-weight:600;">
                    </div>
                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                        <button type="button" onclick="closeDistributeModal()" style="padding:12px 20px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                        <button type="button" onclick="submitDistributeToMember()" class="btn-submit" style="padding:12px 20px; border:none; background:#16a34a; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">📤 Distribute to Member</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Deduct Items Modal -->
<div id="deductModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease;">
    <div class="distribution-box">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeDeductModal()">&times;</button>
            <h3>Deduct Items from Member</h3>
        </div>
        <div class="distribution-body">
            <div style="margin-bottom:20px; padding:16px; background:rgba(220,38,38,.05); border-radius:12px; border-left:4px solid #dc2626;">
                <p style="color: var(--muted); margin: 0; font-size:14px; line-height:1.5;">➖ Deduct used items from member's inventory.</p>
            </div>

            <form method="POST" action="{{ route('client.account.deductItems') }}" style="display:grid; gap:20px;">
                @csrf
                <input type="hidden" id="deduct_member_id" name="member_id">
                <input type="hidden" id="deduct_distribution_id" name="distribution_id">
                
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>👤</span> Member
                    </label>
                    <input type="text" id="deduct_member_name" readonly style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#f8fafc; color:var(--text);">
                </div>
                
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>📦</span> Item
                    </label>
                    <select id="deduct_distribution_select" name="distribution_id" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                        <option value="">-- select item distribution --</option>
                        <!-- Options will be populated by JavaScript based on selected member -->
                    </select>
                    @error('distribution_id')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                </div>
                
                <div class="form-group">
                    <label for="deduct_qty" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>🔢</span> Quantity to Deduct
                    </label>
                    <input type="number" id="deduct_qty" name="deducted_qty" min="1" required value="{{ old('deducted_qty', 1) }}" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                    @error('deducted_qty')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                </div>
                
                <div class="form-group">
                    <label for="received_by" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span>✍️</span> Received By
                    </label>
                    <input type="text" id="received_by" name="received_by" required value="{{ old('received_by', Auth::user()->name) }}" placeholder="Enter your name" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                    @error('received_by')<span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>@enderror
                </div>
                
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                    <button type="button" onclick="closeDeductModal()" style="padding:12px 20px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                    <button type="submit" class="btn-submit" style="padding:12px 20px; border:none; background:#dc2626; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">➖ Deduct Items</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="account-container">
    @if($activeTab === 'settings')
    <!-- Account Information Section -->
    <div style="max-width: 1000px; margin: 0 auto 40px; margin-bottom: 40px;">
        <h2 style="margin: 0 0 16px; font-size: 24px; color: var(--text); font-weight: 700;">Account Information</h2>
        
        <div style="display: grid; gap: 16px;">
            <div style="background: rgba(255,255,255); border: 1px solid var(--line); border-radius: 14px; padding: 18px;">
                <p style="color: var(--muted); font-size: 12px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase;">Full Name</p>
                <p style="color: var(--text); font-size: 16px; font-weight: 700; margin: 0;">{{ auth()->user()->name }}</p>
            </div>

            <div style="background: rgba(255,255,255); border: 1px solid var(--line); border-radius: 14px; padding: 18px;">
                <p style="color: var(--muted); font-size: 12px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase;">Account Type</p>
                <p style="color: var(--text); font-size: 16px; font-weight: 700; margin: 0;">{{ ucfirst(auth()->user()->role) }}</p>
            </div>

            <div style="background: rgba(255,255,255); border: 1px solid var(--line); border-radius: 14px; padding: 18px;">
                <p style="color: var(--muted); font-size: 12px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase;">Member Since</p>
                <p style="color: var(--text); font-size: 16px; font-weight: 700; margin: 0;">{{ auth()->user()->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Account Settings Section -->
    <div style="max-width: 1000px; margin: 0 auto;">
        <h2 style="margin: 0 0 16px; font-size: 24px; color: var(--text); font-weight: 700;">Account Settings</h2>
        
        <div style="background: rgba(255,255,255); border: 1px solid var(--line); border-radius: 14px; padding: 24px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: var(--text); font-weight: 700;">Current Email Address</h3>
            <p style="color: var(--muted); margin: 0 0 16px; font-size: 14px;">{{ auth()->user()->email }}</p>
            <button type="button" class="btn-submit" onclick="openEmailModal()">Update Email Address</button>
        </div>

        @if(auth()->user()->role === 'client')
        <div style="background: rgba(255,255,255); border: 1px solid var(--line); border-radius: 14px; padding: 24px;">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: var(--text); font-weight: 700;">Password</h3>
            <p style="color: var(--muted); margin: 0 0 16px; font-size: 14px;">Update your account password to keep your account secure.</p>
            <button type="button" class="btn-submit" onclick="openPasswordModal()">Change Password</button>
        </div>
        @endif
    </div>

    <!-- Email Update Modal -->
    <div id="emailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:450px; width:90%; padding:24px; animation: slideIn 0.3s ease;">
            <button type="button" onclick="closeEmailModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
            
            <h3 style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Update Email Address</h3>
            <p style="color:#475569; font-size:14px; margin:0 0 20px;">Change the email address associated with your account.</p>
            
            <form method="POST" action="{{ route('client.account.updateEmail') }}" id="emailForm">
                @csrf
                
                <div class="form-group">
                    <label for="current_email_display">Current Email</label>
                    <input type="email" id="current_email_display" value="{{ auth()->user()->email }}" disabled>
                </div>

                <div class="form-group">
                    <label for="new_email">New Email Address</label>
                    <input type="email" id="new_email" name="email" placeholder="Enter new email" required>
                    @error('email')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:24px;">
                    <button type="button" onclick="closeEmailModal()" style="padding:10px 16px; border-radius:8px; border:none; background:#e2e8f0; color:#0f172a; font-weight:700; cursor:pointer;">Cancel</button>
                    <button type="button" onclick="showConfirmModal(document.getElementById('emailForm'), 'Update Email Address', 'Update your email address? You may need to verify the new email.')" style="padding:10px 16px; border-radius:8px; border:none; background:#2563eb; color:#fff; font-weight:700; cursor:pointer;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Update Modal -->
    @if(auth()->user()->role === 'client')
    <div id="passwordModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:450px; width:90%; padding:24px; animation: slideIn 0.3s ease;">
            <button type="button" onclick="closePasswordModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
            
            <h3 style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Change Password</h3>
            <p style="color:#475569; font-size:14px; margin:0 0 20px;">Update your account password to keep your account secure.</p>
            
            <form method="POST" action="{{ route('client.account.updatePassword') }}" id="passwordForm">
                @csrf
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                    @error('current_password')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 6 characters)" required>
                    @error('new_password')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation">Confirm New Password</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" placeholder="Confirm new password" required>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:24px;">
                    <button type="button" onclick="closePasswordModal()" style="padding:10px 16px; border-radius:8px; border:none; background:#e2e8f0; color:#0f172a; font-weight:700; cursor:pointer;">Cancel</button>
                    <button type="button" onclick="showConfirmModal(document.getElementById('passwordForm'), 'Change Password', 'Change your password? Make sure to remember the new password.')" style="padding:10px 16px; border-radius:8px; border:none; background:#2563eb; color:#fff; font-weight:700; cursor:pointer;">Change Password</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endif

    @if($activeTab === 'inventory' && auth()->user()->role === 'client')
    <div class="account-container">
                
        <!-- Report Generator content moved into Distribution details main content -->
        @if(isset($mainInventoryTotals) && isset($memberInventoryTotals))
            <div style="margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:16px; gap:16px;">
                    <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="date_from" style="display:block; margin-bottom:4px; color:var(--text); font-weight:600; font-size:13px;">From Date</label>
                            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" style="padding:8px 12px; border:1px solid var(--line); border-radius:8px; font-size:13px; background:white; color:var(--text); min-width:150px;">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="date_to" style="display:block; margin-bottom:4px; color:var(--text); font-weight:600; font-size:13px;">To Date</label>
                            <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" style="padding:8px 12px; border:1px solid var(--line); border-radius:8px; font-size:13px; background:white; color:var(--text); min-width:150px;">
                        </div>
                        <button type="button" onclick="applyDateFilter()" class="btn-submit" style="padding:8px 16px; font-size:13px; background:#3b82f6;">
                            <span></span> Filter
                        </button>
                        <button type="button" onclick="clearDateFilter()" class="btn-submit" style="padding:8px 16px; font-size:13px; background:#6b7280;">
                            <span></span> Clear
                        </button>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <a href="{{ route('client.account.report.pdf') }}?{{ http_build_query(request()->only(['date_from', 'date_to'])) }}" class="btn-submit" style="padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:6px; text-decoration:none;">
                            <span>­</span> Download PDF
                        </a>
                    </div>
                </div>
                <div id="inventoryReport" style="display:block; padding:24px;">
                    <div style="display:grid; gap:20px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                            <div class="report-card">
                                <div class="report-card-header" style="padding:16px;">
                                    <span style="font-size:20px;">🏢</span>
                                    <h4 style="margin:0; font-weight:700;">Inventory</h4>
                                </div>
                                <div class="report-card-body" style="padding:20px;">
                                    <div class="report-stat">
                                        <span class="stat-label" style="color:#374151; font-weight:600;">Received:</span>
                                        <span class="stat-value" style="color:#1e40af; font-weight:700; font-size:18px;">{{ $mainInventoryTotals['total_received'] }}</span>
                                    </div>
                                    <div class="report-stat">
                                        <span class="stat-label" style="color:#059669; font-weight:600;">Available:</span>
                                        <span class="stat-value text-success" style="background:linear-gradient(135deg, #ecfdf5, #d1fae5); color:#059669; font-weight:700; font-size:18px;">{{ $mainInventoryTotals['total_available'] }}</span>
                                    </div>
                                </div>
                            </div>
                            {{-- Members Inventory card hidden --}}
                            {{-- <div class="report-card" style="display:none;">
                                <div class="report-card-header" style="padding:16px;">
                                    <span style="font-size:20px;">👥</span>
                                    <h4 style="margin:0; font-weight:700;">Members Inventory</h4>
                                </div>
                                <div class="report-card-body" style="padding:20px;">
                                    <div class="report-stat">
                                        <span class="stat-label" style="color:#374151; font-weight:600;">Distributed to Members:</span>
                                        <span class="stat-value" style="color:#1e40af; font-weight:700; font-size:18px;">{{ $memberInventoryTotals['total_distributed'] }}</span>
                                    </div>
                                                                        <div class="report-stat">
                                        <span class="stat-label" style="color:#059669; font-weight:600;">Available Items:</span>
                                        <span class="stat-value text-success" style="background:linear-gradient(135deg, #ecfdf5, #d1fae5); color:#059669; font-weight:700; font-size:18px;">{{ $memberInventoryTotals['total_available'] }}</span>
                                    </div>
                                </div>
                            </div> --}}
                        </div>
                        <div class="report-section">
                            <h4 style="margin:0 0 16px; font-size:18px; color:var(--text); display:flex; align-items:center; gap:8px;">
                                <span>📋</span> Member Usage Details
                            </h4>
                            @if(isset($memberReports) && $memberReports->isNotEmpty())
                                <div style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                                    <table class="report-table" style="width:100%; border-collapse:collapse;">
                                        <thead>
                                            <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Member Name</th>
                                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Email</th>
                                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Distributed Items</th>
                                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Items Left</th>
                                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Used Items</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($memberReports as $memberReport)
                                                <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; font-weight:700; color:#1e40af; font-size:14px;">{{ $memberReport['name'] }}</td>
                                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-size:13px;">{{ $memberReport['email'] }}</td>
                                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:700;">{{ $memberReport['distributed_items'] }}</td>
                                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#059669; font-weight:700; background:linear-gradient(135deg, #ecfdf5, #d1fae5);">{{ $memberReport['available_items'] }}</td>
                                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#ea580c; font-weight:700; background:linear-gradient(135deg, #fff7ed, #fed7aa);">{{ $memberReport['used_items'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div style="text-align:center; padding:48px; color:#64748b; background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius:16px; border:2px dashed #3b82f6;">
                                    <div style="font-size:48px; margin-bottom:16px; color:#3b82f6;">👥</div>
                                    <p>No member data available for report.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Available Items Lists -->
                        <div class="report-section">
                            <h4 style="margin:0 0 16px; font-size:18px; color:var(--text); display:flex; align-items:center; gap:8px;">
                                <span>📦</span> Available Items Details
                            </h4>
                            
                            <!-- Client Available Items -->
                            <div style="margin-bottom:24px;">
                                <h5 style="margin:0 0 12px; font-size:16px; color:#1e40af; font-weight:600;">Client Available Items</h5>
                                @if(isset($approvedInventory) && $approvedInventory->isNotEmpty())
                                    <div style="overflow-x:auto; border-radius:12px; box-shadow:0 4px 12px rgba(59,130,246,0.1); background:linear-gradient(135deg, #f8fafc, #e2e8f0);">
                                        <table class="report-table" style="width:100%; border-collapse:collapse;">
                                            <thead>
                                                <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                                    <th style="padding:10px 8px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:11px; background:#f8fafc;">Item Description</th>
                                                    <th style="padding:10px 8px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:11px; background:#f8fafc;">Total Received</th>
                                                    <th style="padding:10px 8px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:11px; background:#f8fafc;">Distributed</th>
                                                    <th style="padding:10px 8px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:11px; background:#f8fafc;">Available</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($approvedInventory as $item)
                                                    <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                                        <td style="padding:10px 8px; border-bottom:1px solid #e0e7ff; color:#374151; font-size:12px; font-weight:500;">{{ $item->stock->description ?? 'Unknown Item' }}</td>
                                                        <td style="padding:10px 8px; text-align:right; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:600; font-size:12px;">{{ $item->approved_qty ?? 0 }}</td>
                                                        <td style="padding:10px 8px; text-align:right; border-bottom:1px solid #e0e7ff; color:#ea580c; font-weight:600; font-size:12px;">{{ $item->distributed_qty ?? 0 }}</td>
                                                        <td style="padding:10px 8px; text-align:right; border-bottom:1px solid #e0e7ff; color:#059669; font-weight:700; font-size:12px; background:linear-gradient(135deg, #ecfdf5, #d1fae5);">{{ $item->my_inventory ?? 0 }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div style="text-align:center; padding:24px; color:#64748b; background:linear-gradient(135deg, #f8fafc, #e2e8f0); border-radius:12px; border:1px dashed #3b82f6;">
                                        <div style="font-size:32px; margin-bottom:12px; color:#3b82f6;">📦</div>
                                        <p style="font-size:14px;">No client inventory items available</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Members Available Items -->
                            <div>
                                <h5 style="margin:0 0 12px; font-size:16px; color:#059669; font-weight:600;">Members Available Items</h5>
                                @if(isset($clientMembers) && $clientMembers->isNotEmpty())
                                    <div style="display:grid; gap:16px;">
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
                                                <div style="border-radius:12px; box-shadow:0 4px 12px rgba(16,185,129,0.1); background:linear-gradient(135deg, #f0fdf4, #dcfce7); overflow:hidden;">
                                                    <div style="padding:12px; background:linear-gradient(135deg, #059669, #047857); color:white;">
                                                        <h6 style="margin:0; font-size:14px; font-weight:600;">{{ $member->name }}</h6>
                                                        <p style="margin:4px 0 0 0; font-size:11px; opacity:0.9;">{{ $member->email }}</p>
                                                    </div>
                                                    <div style="padding:12px;">
                                                        <table style="width:100%; border-collapse:collapse; font-size:11px;">
                                                            <thead>
                                                                <tr style="background:linear-gradient(135deg, #ecfdf5, #d1fae5);">
                                                                    <th style="padding:6px 4px; text-align:left; font-weight:600; color:#064e3b;">Item</th>
                                                                    <th style="padding:6px 4px; text-align:right; font-weight:600; color:#064e3b;">Distributed</th>
                                                                    <th style="padding:6px 4px; text-align:right; font-weight:600; color:#064e3b;">Used</th>
                                                                    <th style="padding:6px 4px; text-align:right; font-weight:600; color:#064e3b;">Available</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($memberItems as $item)
                                                                    <tr style="border-bottom:1px solid #bbf7d0;">
                                                                        <td style="padding:4px; color:#064e3b; font-weight:500;">{{ $item->description }}</td>
                                                                        <td style="padding:4px; text-align:right; color:#1e40af;">{{ $item->distributed_qty }}</td>
                                                                        <td style="padding:4px; text-align:right; color:#ea580c;">{{ $item->used_qty }}</td>
                                                                        <td style="padding:4px; text-align:right; color:#059669; font-weight:600;">{{ $item->available_qty }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div style="text-align:center; padding:24px; color:#64748b; background:linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius:12px; border:1px dashed #059669;">
                                        <div style="font-size:32px; margin-bottom:12px; color:#059669;">👥</div>
                                        <p style="font-size:14px;">No member inventory items available</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
            @if($approvedInventory->isEmpty())
                <!-- <p style="margin-top:20px; color: var(--muted);">No approved items are available yet.</p> -->
            @else
                <!-- Distribute Items to Subaccounts button hidden for now -->
                <!-- <button type="button" class="btn-submit" onclick="openDistributionModal()" style="margin-bottom:16px;">Distribute Items to Subaccounts</button> -->
            @endif
    </div>
    @endif

    @if($activeTab === 'members' && auth()->user()->role === 'client')
    
    <h2 style="margin: 0 0 16px; font-size: 24px; color: var(--text); font-weight: 700;">Members</h2>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px;">
        <button type="button" class="btn-submit" onclick="openAddMemberModal()">
            <span style="display: flex; align-items: center; gap: 8px;">
                <span>➕</span> Add Member
            </span>
        </button>
        @if(!$clientMembers->isEmpty() && !$approvedInventory->isEmpty())
        <button type="button" class="btn-submit" style="background: #16a34a;" onclick="openDistributeModal()">
            <span style="display: flex; align-items: center; gap: 8px;">
                <span>📤</span> Distribute Items
            </span>
        </button>
        @endif
    </div>

    <!-- Members List -->
    <div class="settings-card">
        <div class="card-header" style="cursor: default; background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(37,99,235,.04));">
            <h3 style="display: flex; align-items: center; gap: 8px;">
                <span>👥</span> Members List
            </h3>
            <span style="color: var(--muted); font-size: 14px; font-weight: 600;">
                {{ $clientMembers->count() }} member{{ $clientMembers->count() !== 1 ? 's' : '' }}
            </span>
        </div>
        <div class="card-body open" style="display: block;">
            @if($clientMembers->isEmpty())
                <div style="text-align:center; padding:40px; color:var(--muted); background:#f8fafc; border-radius:12px; border:1px solid var(--line);">
                    <div style="font-size:48px; margin-bottom:16px;">👥</div>
                    <p>No members added yet. Click "Add Member" to get started.</p>
                </div>
            @else
                <div style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                    <table class="report-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Member Name</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Email</th>
                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Item list</th>
                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Items Left</th>
                                <th style="padding:12px 10px; text-align:right; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Used Items</th>
                                <th style="padding:12px 10px; text-align:center; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($memberReports as $memberReport)
                                @php
                                    $currentMember = $clientMembers->firstWhere('name', $memberReport['name']);
                                @endphp
                                <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; font-weight:700; color:#1e40af; font-size:14px;">{{ $memberReport['name'] }}</td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-size:13px;">{{ $memberReport['email'] }}</td>
                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:700;">
                                    {{-- Number hidden --}}
                                    {{-- {{ $memberReport['distributed_items'] }} --}}
                                    @if($memberReport['distributed_items'] > 0)
                                        <div style="position:relative; display:inline-block; margin-left:8px;">
                                            <button 
                                                type="button" 
                                                onclick="toggleMemberItemsDropdown('member-items-{{ $memberReport['name'] }}')"
                                                style="background:none; border:none; color:#64748b; cursor:pointer; padding:4px; border-radius:4px; font-size:16px; line-height:1;"
                                                onmouseover="this.style.background='#f1f5f9'"
                                                onmouseout="this.style.background='none'"
                                            >
                                                show
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#059669; font-weight:700; background:linear-gradient(135deg, #ecfdf5, #d1fae5);">{{ $memberReport['available_items'] }}</td>
                                    <td style="padding:14px 10px; text-align:right; border-bottom:1px solid #e0e7ff; color:#ea580c; font-weight:700; background:linear-gradient(135deg, #fff7ed, #fed7aa);">
                                    {{ $memberReport['used_items'] }}
                                </td>
                                    <td style="padding:12px; border:1px solid var(--line);">
                                        <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:center;">
                                            <button type="button" class="btn-action" style="padding:6px 12px; font-size:12px; background:#3b82f6; color:white; border:none; border-radius:6px; cursor:pointer;" onclick="openEditMemberModal({{ $currentMember->id }}, '{{ $currentMember->name }}', '{{ $currentMember->email }}')">
                                                Edit
                                            </button>
                                            @if($memberReport['available_items'] > 0)
                                            <button type="button" class="btn-action" style="padding:6px 12px; font-size:12px; background:#dc2626; color:white; border:none; border-radius:6px; cursor:pointer;" onclick="openDeductModal({{ $currentMember->id }}, '{{ $memberReport['name'] }}', {{ $memberReport['available_items'] }})">
                                                Deduct
                                            </button>
                                            @endif
                                            <button type="button" class="btn-action" style="padding:6px 12px; font-size:12px; background:#ef4444; color:white; border:none; border-radius:6px; cursor:pointer;" onclick="openDeleteMemberModal({{ $currentMember->id }}, '{{ $currentMember->name }}')">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

    @if($activeTab === 'subaccounts' && auth()->user()->role === 'client')
    <div class="settings-card">
        <div class="card-header" onclick="toggleCard('subaccounts-card')">
            <h3>Offices</h3>
            <span class="card-toggle">Click to expand</span>
        </div>

        <div id="subaccounts-card" class="card-body open">
            <p style="color: var(--muted); margin: 0 0 16px;">Create and manage your office subaccounts. Each subaccount can have its own members and inventory allocations.</p>

            <form method="POST" action="{{ route('client.account.subaccounts.store') }}">
                @csrf
                <div class="form-group">
                    <label for="subaccount_name">Office Name</label>
                    <input type="text" id="subaccount_name" name="name" placeholder="e.g. Main Office, Branch Office" required value="{{ old('name') }}">
                    @error('name')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="subaccount_email">Login Email</label>
                    <input type="email" id="subaccount_email" name="email" placeholder="Enter email for subaccount login" required value="{{ old('email') }}">
                    @error('email')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="subaccount_password">Password</label>
                    <input type="password" id="subaccount_password" name="password" placeholder="Enter password" required>
                    @error('password')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="subaccount_password_confirmation">Confirm Password</label>
                    <input type="password" id="subaccount_password_confirmation" name="password_confirmation" placeholder="Confirm password" required>
                </div>
                <div class="form-group">
                    <label for="subaccount_description">Description (Optional)</label>
                    <textarea id="subaccount_description" name="description" placeholder="Brief description of this office" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span style="color: var(--danger); font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn-submit">Create Office</button>
            </form>

            @if($subaccounts->isNotEmpty())
                <div style="margin-top:32px; overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                    <h4 style="margin:0 0 16px; color:#1e40af; font-weight:700; text-shadow:0 2px 4px rgba(0,0,0,0.1);">Existing Offices</h4>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Office Name</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Login Email</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Members</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Allocated Items</th>
                                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subaccounts as $subaccount)
                                @php
                                    $allocationCount = $subaccount->allocations->count();
                                @endphp
                                <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; font-weight:700; color:#1e40af; font-size:14px;">
                                        {{ $subaccount->name }}
                                        @if($subaccount->description)
                                            <div style="color:#64748b; font-size:11px; margin-top:4px;">{{ $subaccount->description }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-size:13px;">{{ $subaccount->user->email ?? 'No login email' }}</td>
                                    <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:700;">{{ $subaccount->members_count }}</td>
                                    <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:700;">{{ $allocationCount }}</td>
                                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                        <a href="{{ route('client.account.subaccounts.show', $subaccount) }}" style="color:#3b82f6; text-decoration: none; font-weight: 600; background:linear-gradient(135deg, #eff6ff, #dbeafe); padding:6px 12px; border-radius:6px; display:inline-block;">Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="margin-top:32px; text-align:center; padding:48px; color:#64748b; background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius:16px; border:2px dashed #3b82f6;">
                    <div style="font-size:48px; margin-bottom:16px; color:#3b82f6;">🏢</div>
                    <p style="font-size:18px; font-weight:600; color:#1e40af;">No offices created yet. Create your first office above to get started.</p>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Static Member Items Popups (Outside Table Structure) -->
@foreach($memberReports as $memberReport)
    @php
        $currentMember = $clientMembers->firstWhere('name', $memberReport['name']);
    @endphp
    
    <!-- Backdrop -->
    <div id="member-items-backdrop-{{ $memberReport['name'] }}" 
         style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998; cursor:pointer;"
         onclick="toggleMemberItemsDropdown('member-items-{{ $memberReport['name'] }}')">
    </div>
    
    <!-- Popup Window -->
    <div id="member-items-{{ $memberReport['name'] }}" 
         style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:9999; min-width:600px; max-width:800px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,0.25); margin:0; padding:0; pointer-events:auto;"
         onclick="event.stopPropagation();">
        
        <div style="padding:16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-weight:700; color:#1e40af; font-size:16px;">{{ $memberReport['name'] }}</div>
                <div style="color:#64748b; font-size:13px; margin-top:2px;">{{ $memberReport['email'] }}</div>
            </div>
            <button type="button" onclick="toggleMemberItemsDropdown('member-items-{{ $memberReport['name'] }}')" style="background:none; border:none; color:#64748b; cursor:pointer; padding:6px 8px; border-radius:6px; font-size:20px; line-height:1;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">×</button>
        </div>
        
        <div style="max-height:300px; overflow-y:auto; pointer-events:auto;" onclick="event.stopPropagation();">
            @php
                // Collect all items and deduplicate by stock description
                $allItems = collect();
                
                // Add distributed items
                if($currentMember && $currentMember->distributions->count() > 0) {
                    foreach($currentMember->distributions as $distribution) {
                        $availableQty = $distribution->distributed_qty - ($distribution->used_qty ?? 0);
                        $itemName = $distribution->stockRequestItem->stock->description ?? 'Item';
                        
                        if($allItems->has($itemName)) {
                            // Update existing item
                            $existing = $allItems->get($itemName);
                            $existing->distributed_qty += $distribution->distributed_qty;
                            $existing->used_qty += $distribution->used_qty ?? 0;
                            $existing->available_qty += $availableQty;
                        } else {
                            // Add new item
                            $allItems->put($itemName, (object)[
                                'name' => $itemName,
                                'distributed_qty' => $distribution->distributed_qty,
                                'used_qty' => $distribution->used_qty ?? 0,
                                'available_qty' => $availableQty,
                                'deducted_qty' => 0,
                                'has_distributed' => true,
                                'has_deducted' => false
                            ]);
                        }
                    }
                }
                
                // Add direct request items as available items
                if($currentMember && $currentMember->directDeductions->count() > 0) {
                    foreach($currentMember->directDeductions as $deduction) {
                        if($deduction->stock_request_item_id === null) { // Only direct request items
                            $itemName = $deduction->reason ?? 'Direct Request Item';
                            
                            // Skip the specific item that needs to be removed
                            if($itemName === 'Used from direct request - Member distribution - Binder Arch Folder / Document File - Cover, Blue') {
                                continue;
                            }
                            
                            // Clean up item name by removing prefixes
                            if(str_contains($itemName, '"Member distribution" - ')) {
                                $itemName = str_replace('"Member distribution" - ', '', $itemName);
                            }
                            if(str_contains($itemName, 'Member distribution - ')) {
                                $itemName = str_replace('Member distribution - ', '', $itemName);
                            }
                            
                            if($allItems->has($itemName)) {
                                // Update existing item
                                $existing = $allItems->get($itemName);
                                $existing->distributed_qty += $deduction->deducted_qty;
                                $existing->available_qty += $deduction->deducted_qty;
                            } else {
                                // Add new item as available
                                $allItems->put($itemName, (object)[
                                    'name' => $itemName,
                                    'distributed_qty' => $deduction->deducted_qty,
                                    'used_qty' => 0,
                                    'available_qty' => $deduction->deducted_qty,
                                    'deducted_qty' => 0,
                                    'has_distributed' => true,
                                    'has_deducted' => false
                                ]);
                            }
                        }
                    }
                }
            @endphp
            
            @if($allItems->count() > 0)
                <div style="padding:8px 12px;">
                    <div style="font-weight:600; color:#374151; font-size:12px; margin-bottom:8px;">ITEMS LIST</div>
                    @foreach($allItems as $item)
                        <div style="padding:8px; margin-bottom:6px; border:1px solid #e5e7eb; border-radius:6px; background:#f9fafb;">
                            <div style="font-weight:600; color:#1f2937; font-size:12px; margin-bottom:4px;">{{ $item->name }}</div>
                            
                            @if($item->has_distributed)
                                <div style="display:flex; gap:12px; font-size:11px; color:#6b7280; margin-bottom:2px;">
                                    <span><strong>Quantity:</strong> {{ $item->distributed_qty }}</span>
                                    <span><strong>Available:</strong> <span style="color:#059669; font-weight:600;">{{ $item->available_qty }}</span></span>
                                </div>
                            @endif
                            
                            @if($item->has_deducted)
                                <div style="font-size:11px; color:#6b7280;">
                                    <span><strong>Deducted:</strong> {{ $item->deducted_qty }} units</span>
                                </div>
                            @endif
                            
                            @if($item->has_distributed && $item->has_deducted)
                                <div style="font-size:10px; color:#9ca3af; margin-top:2px; font-style:italic;">
                                    Both distributed and deducted
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding:20px; text-align:center; color:#9ca3af; font-size:12px;">
                    No items assigned to this member
                </div>
            @endif
        </div>
        
        <div style="padding:16px; background:#f8fafc; border-radius:0 0 12px 12px; border-top:1px solid #e2e8f0;">
            <div style="font-size:12px; color:#64748b; text-align:center;">
                <div style="margin-bottom:6px; font-weight:600; color:#1e40af; font-size:14px;">SUMMARY</div>
                <div style="font-size:13px;">
                    Total Received: {{ $memberReport['distributed_items'] }} | 
                    Total Used: {{ $memberReport['used_items'] }} | 
                    <span style="color:#059669; font-weight:700;">Available: {{ $memberReport['available_items'] }}</span>
                </div>
                @if($memberReport['distributed_items'] > 0)
                    <div style="margin-top:6px; font-size:11px; color:#94a3b8;">
                        ({{ $memberReport['available_items'] }} of {{ $memberReport['distributed_items'] }} items remaining)
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach

@php
    $subaccountMembers = $subaccounts->mapWithKeys(function($subaccount) {
        return [
            $subaccount->id => $subaccount->members->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ];
            })->toArray(),
        ];
    })->toArray();
@endphp

<script>
let pendingForm = null;

function toggleCard(id){
    const body = document.getElementById(id);
    if(!body) return;

    const header = body.previousElementSibling;
    const isOpen = body.style.display !== 'block';

    body.style.display = isOpen ? 'block' : 'none';
    body.classList.toggle('open', isOpen);

    if(header && header.classList) {
        header.classList.toggle('open', isOpen);
    }
}

function openEmailModal(){
    document.getElementById('emailModal').style.display = 'flex';
}

function closeEmailModal(){
    document.getElementById('emailModal').style.display = 'none';
    document.getElementById('emailForm').reset();
}

function openPasswordModal(){
    document.getElementById('passwordModal').style.display = 'flex';
}

function closePasswordModal(){
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordForm').reset();
}

function showConfirmModal(form, title, message){
    pendingForm = form;
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-text').textContent = message;
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal(){
    document.getElementById('confirmModal').classList.remove('show');
    pendingForm = null;
}

function submitConfirmedForm(){
    if(pendingForm){
        pendingForm.submit();
    }
    closeConfirmModal();
}

// Close modals on Escape key
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') {
        closeConfirmModal();
        closeEmailModal();
        closePasswordModal();
        closeDistributionModal();
    }
});

function openDistributionModal(){
    document.getElementById('distributionModal').classList.add('show');
}

function closeDistributionModal(){
    document.getElementById('distributionModal').classList.remove('show');
}

function updateMembers() {
    const subaccountSelect = document.getElementById('subaccount_id');
    const memberSelect = document.getElementById('member_id');
    if (!subaccountSelect || !memberSelect) return;

    const selectedSubaccountId = subaccountSelect.value;
    memberSelect.innerHTML = '<option value="">-- select member --</option>';
    if (!selectedSubaccountId) return;

    const subaccountMembers = @json($subaccountMembers);

    const members = subaccountMembers[selectedSubaccountId] || [];
    members.forEach(member => {
        const option = document.createElement('option');
        option.value = member.id;
        option.textContent = member.email ? `${member.name} (${member.email})` : member.name;
        memberSelect.appendChild(option);
    });
}

function toggleInventoryReport() {
    const report = document.getElementById('inventoryReport');
    const toggle = document.querySelector('.card-toggle[onclick="toggleInventoryReport()"]');
    if (!report || !toggle) return;

    const isOpen = report.style.display === 'block';
    report.style.display = isOpen ? 'none' : 'block';
    toggle.textContent = isOpen ? 'Show Report' : 'Hide Report';
}

function openAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'flex';
}

function closeAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'none';
    document.querySelector('#addMemberModal form').reset();
}

function openDistributeModal() {
    document.getElementById('distributeMemberModal').style.display = 'flex';
}

function closeDistributeModal() {
    document.getElementById('distributeMemberModal').style.display = 'none';
    document.querySelector('#distributeMemberModal form').reset();
    // Hide success/error messages
    document.getElementById('distribution-success-message').style.display = 'none';
    document.getElementById('distribution-error-message').style.display = 'none';
    // Hide error messages for individual fields
    document.getElementById('member_id_error').style.display = 'none';
    document.getElementById('stock_request_item_id_error').style.display = 'none';
    document.getElementById('distributed_qty_error').style.display = 'none';
}

function submitDistributeToMember() {
    const form = document.getElementById('distributeToMemberForm');
    const formData = new FormData(form);
    
    // Clear previous messages
    document.getElementById('distribution-success-message').style.display = 'none';
    document.getElementById('distribution-error-message').style.display = 'none';
    document.getElementById('member_id_error').style.display = 'none';
    document.getElementById('stock_request_item_id_error').style.display = 'none';
    document.getElementById('distributed_qty_error').style.display = 'none';
    
    // Disable submit button to prevent double submission
    const submitBtn = form.querySelector('button[onclick="submitDistributeToMember()"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Distributing...';
    
    fetch('{{ route("client.account.distributeToMember") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            document.getElementById('distribution-success-message').style.display = 'block';
            // Reset form
            form.reset();
            // Close modal after a delay
            setTimeout(() => {
                closeDistributeModal();
                // Refresh the page to show updated data
                window.location.reload();
            }, 2000);
        } else {
            // Show error message
            const errorDiv = document.getElementById('distribution-error-message');
            errorDiv.textContent = data.message || 'An error occurred while distributing the item.';
            errorDiv.style.display = 'block';
            
            // Show field-specific errors if any
            if (data.errors) {
                if (data.errors.member_id) {
                    document.getElementById('member_id_error').textContent = data.errors.member_id[0];
                    document.getElementById('member_id_error').style.display = 'block';
                }
                if (data.errors.stock_request_item_id) {
                    document.getElementById('stock_request_item_id_error').textContent = data.errors.stock_request_item_id[0];
                    document.getElementById('stock_request_item_id_error').style.display = 'block';
                }
                if (data.errors.distributed_qty) {
                    document.getElementById('distributed_qty_error').textContent = data.errors.distributed_qty[0];
                    document.getElementById('distributed_qty_error').style.display = 'block';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorDiv = document.getElementById('distribution-error-message');
        errorDiv.textContent = 'An unexpected error occurred. Please try again.';
        errorDiv.style.display = 'block';
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function openDeductModal(memberId, memberName, availableQty) {
    document.getElementById('deduct_member_id').value = memberId;
    document.getElementById('deduct_member_name').value = memberName;
    document.getElementById('deductModal').style.display = 'flex';
    
    // Populate the distribution select with member's items
    const distributionSelect = document.getElementById('deduct_distribution_select');
    distributionSelect.innerHTML = '<option value="">-- select item distribution --</option>';
    
    // Find the member and populate their distributions
    const members = @json($clientMembers);
    const member = members.find(m => m.id == memberId);
    
    if (member) {
        // Use the same calculation as Member List table for consistency
        const aggregatedItems = new Map();
        
        // Get the member report data that matches the Member List table
        const memberReports = @json($memberReports);
        const currentMemberReport = memberReports.find(report => report.name === member.name);
        
        if (currentMemberReport && currentMemberReport.available_items > 0) {
            // Aggregate items from the member's actual distributions using the same logic as member reports
            if (member.distributions) {
                member.distributions.forEach(distribution => {
                    const availableQty = distribution.distributed_qty - (distribution.used_qty || 0);
                    if (availableQty > 0) {
                        const itemName = distribution.stock_request_item?.stock?.description || distribution.stock_request_item?.stock?.name || 'Item';
                        
                        if (aggregatedItems.has(itemName)) {
                            const existing = aggregatedItems.get(itemName);
                            existing.availableQty += availableQty;
                            existing.distributionIds.push(distribution.id);
                        } else {
                            aggregatedItems.set(itemName, {
                                name: itemName,
                                availableQty: availableQty,
                                distributionIds: [distribution.id],
                                directIds: [],
                                type: 'regular'
                            });
                        }
                    }
                });
            }
            
            // Add direct request items (original ones only, not usage records)
            if (member.direct_deductions) {
                member.direct_deductions.forEach(deduction => {
                    if (deduction.stock_request_item_id === null && !deduction.reason?.includes('Used from direct request')) {
                        let itemName = deduction.reason || 'Direct Request Item';
                        
                        // Clean up the item name
                        if (itemName.includes('Member distribution - ')) {
                            itemName = itemName.replace('Member distribution - ', '');
                        }
                        if (itemName.includes('Member inventory deduction - ')) {
                            itemName = itemName.replace('Member inventory deduction - ', '');
                        }
                        
                        if (aggregatedItems.has(itemName)) {
                            const existing = aggregatedItems.get(itemName);
                            existing.availableQty += deduction.deducted_qty;
                            existing.directIds = existing.directIds || [];
                            existing.directIds.push(deduction.id);
                        } else {
                            aggregatedItems.set(itemName, {
                                name: itemName,
                                availableQty: deduction.deducted_qty,
                                distributionIds: [],
                                directIds: [deduction.id],
                                type: 'direct'
                            });
                        }
                    }
                });
            }
        }
        
        // Create options from aggregated items
        aggregatedItems.forEach((item, itemName) => {
            // Show all available items from member regardless of quantity
            if (item.availableQty > 0) {
                const option = document.createElement('option');
                
                // If we have both regular and direct items, prioritize regular distribution for deduction
                if (item.distributionIds && item.distributionIds.length > 0) {
                    option.value = item.distributionIds[0];
                } else if (item.directIds && item.directIds.length > 0) {
                    option.value = `direct_${item.directIds[0]}`;
                }
                
                // Store the item data for reference during form submission
                option.dataset.itemName = itemName;
                option.dataset.availableQty = item.availableQty;
                option.dataset.hasRegular = item.distributionIds && item.distributionIds.length > 0;
                option.dataset.hasDirect = item.directIds && item.directIds.length > 0;
                
                option.textContent = `${itemName} — Available: ${item.availableQty}`;
                distributionSelect.appendChild(option);
            }
        });
    }
}

function closeDeductModal() {
    document.getElementById('deductModal').style.display = 'none';
    document.querySelector('#deductModal form').reset();
}

function applyDateFilter() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    // Build URL with date parameters
    const url = new URL(window.location);
    if (dateFrom) {
        url.searchParams.set('date_from', dateFrom);
    } else {
        url.searchParams.delete('date_from');
    }
    if (dateTo) {
        url.searchParams.set('date_to', dateTo);
    } else {
        url.searchParams.delete('date_to');
    }
    
    // Reload page with new filters
    window.location.href = url.toString();
}

function clearDateFilter() {
    // Clear date inputs
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    
    // Remove date parameters from URL and reload
    const url = new URL(window.location);
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    
    window.location.href = url.toString();
}

function toggleMemberItemsDropdown(dropdownId) {
    // Prevent event bubbling
    if (event) {
        event.stopPropagation();
    }
    
    // Close all other dropdowns and backdrops first
    const allDropdowns = document.querySelectorAll('[id^="member-items-"]:not([id^="member-items-backdrop-"])');
    const allBackdrops = document.querySelectorAll('[id^="member-items-backdrop-"]');
    
    allDropdowns.forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
            dropdown.style.display = 'none';
        }
    });
    
    allBackdrops.forEach(backdrop => {
        if (backdrop.id !== 'member-items-backdrop-' + dropdownId.replace('member-items-', '')) {
            backdrop.style.display = 'none';
        }
    });
    
    // Toggle the current dropdown and backdrop
    const dropdown = document.getElementById(dropdownId);
    const backdropId = 'member-items-backdrop-' + dropdownId.replace('member-items-', '');
    const backdrop = document.getElementById(backdropId);
    
    if (dropdown && backdrop) {
        if (dropdown.style.display === 'none' || dropdown.style.display === '') {
            // Show both backdrop and dropdown
            backdrop.style.display = 'block';
            dropdown.style.display = 'block';
            
            // Ensure popup stays centered and blocks events
            dropdown.style.top = '50%';
            dropdown.style.left = '50%';
            dropdown.style.transform = 'translate(-50%, -50%)';
            dropdown.style.pointerEvents = 'auto';
        } else {
            // Hide both backdrop and dropdown
            backdrop.style.display = 'none';
            dropdown.style.display = 'none';
        }
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[id^="member-items-"]') && !event.target.closest('button[onclick*="toggleMemberItemsDropdown"]')) {
        const allDropdowns = document.querySelectorAll('[id^="member-items-"]');
        allDropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});
</script>

<!-- Edit Member Modal -->
<div id="editMemberModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease;">
    <div class="distribution-box">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeEditMemberModal()">&times;</button>
            <h3>Edit Member Information</h3>
        </div>
        <div class="distribution-body">
            <form id="editMemberForm" method="POST" style="display:grid; gap:20px;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="edit_member_name" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span></span> Member Name
                    </label>
                    <input type="text" id="edit_member_name" name="name" placeholder="e.g. John Doe" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                </div>
                <div class="form-group">
                    <label for="edit_member_email" style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text);">
                        <span></span> Member Email
                    </label>
                    <input type="email" id="edit_member_email" name="email" placeholder="Enter member email" required style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:#fff; transition:border-color .2s ease;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                    <button type="button" onclick="closeEditMemberModal()" style="padding:12px 20px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                    <button type="submit" class="btn-submit" style="padding:12px 20px; border:none; background:#2563eb; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">ð Update Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Member Modal -->
<div id="deleteMemberModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:5000; align-items:center; justify-content:center; padding:20px; animation:fadeIn .3s ease;">
    <div class="distribution-box" style="max-width:450px;">
        <div class="distribution-header">
            <button class="distribution-close" onclick="closeDeleteMemberModal()">&times;</button>
            <h3>Delete Member</h3>
        </div>
        <div class="distribution-body">
            <div style="text-align:center; padding:20px 0;">
                <div style="font-size:48px; margin-bottom:16px;"></div>
                <p style="color:#374151; font-size:16px; margin:0 0 8px;">Are you sure you want to delete this member?</p>
                <p style="color:#ef4444; font-weight:600; margin:0 0 20px;" id="deleteMemberName"></p>
                <p style="color:#64748b; font-size:14px; margin:0 0 24px;">This action cannot be undone. All associated distribution records will also be deleted.</p>
            </div>
            <form id="deleteMemberForm" method="POST" style="display:flex; gap:12px; justify-content:center;">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeDeleteMemberModal()" style="padding:12px 24px; border:1px solid var(--line); background:#f8fafc; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">Cancel</button>
                <button type="submit" class="btn-submit" style="padding:12px 24px; border:none; background:#ef4444; color:#fff; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s ease;">ð Delete Member</button>
            </form>
        </div>
    </div>
</div>

<script>
// Edit Member Modal Functions
function openEditMemberModal(memberId, memberName, memberEmail) {
    const modal = document.getElementById('editMemberModal');
    const form = document.getElementById('editMemberForm');
    
    // Set form action
    form.action = '/client/account/members/' + memberId;
    
    // Populate form fields
    document.getElementById('edit_member_name').value = memberName;
    document.getElementById('edit_member_email').value = memberEmail;
    
    // Show modal
    modal.style.display = 'flex';
}

function closeEditMemberModal() {
    document.getElementById('editMemberModal').style.display = 'none';
}

// Delete Member Modal Functions
function openDeleteMemberModal(memberId, memberName) {
    const modal = document.getElementById('deleteMemberModal');
    const form = document.getElementById('deleteMemberForm');
    
    // Set form action
    form.action = '/client/account/members/' + memberId;
    
    // Set member name in confirmation message
    document.getElementById('deleteMemberName').textContent = memberName;
    
    // Show modal
    modal.style.display = 'flex';
}

function closeDeleteMemberModal() {
    document.getElementById('deleteMemberModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('editMemberModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditMemberModal();
    }
});

document.getElementById('deleteMemberModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteMemberModal();
    }
});
</script>

@endsection
