@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Client & Member Inventory Monitoring';
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')

    <style>
        .monitoring-container {
            margin: 20px 0;
        }

        .client-section {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .client-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15,23,42,.12);
            border-color: #3b82f6;
        }

        .client-list-item {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .client-basic-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            color: white;
        }

        .client-details {
            display: flex;
            flex-direction: column;
        }

        .client-name {
            font-weight: 600;
            font-size: 16px;
            color: #1e293b;
        }

        .client-office {
            font-size: 14px;
            color: #64748b;
        }

        .client-stats {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #64748b;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-value {
            font-weight: 600;
            color: #1e293b;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .inventory-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            color: #374151;
        }

        .inventory-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .inventory-table tr:hover {
            background: #f8fafc;
        }

        .settings-card{
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            margin: 16px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-content {
            padding: 16px;
            display: none;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-low {
            background: #fef2f2;
            color: #dc2626;
        }

        .stock-medium {
            background: #fef3c7;
            color: #d97706;
        }

        .stock-good {
            background: #ecfdf5;
            color: #059669;
        }

        .member-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .member-active {
            background: #ecfdf5;
            color: #059669;
        }

        .member-inactive {
            background: #fef2f2;
            color: #dc2626;
        }

        .search-container {
            margin-bottom: 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .no-data {
            text-align: center;
            padding: 48px;
            color: #64748b;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #3b82f6;
        }

        .urgent-badge {
            background: #dc2626;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-left: 8px;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            transform: rotate(-90deg);
        }

        .collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .expanded .toggle-icon {
            transform: rotate(0deg);
        }

        .distribution-info {
            display: flex;
            gap: 16px;
            font-size: 12px;
        }

        .distribution-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .distribution-label {
            color: #64748b;
        }

        .distribution-value {
            font-weight: 600;
            color: #374151;
        }

        /* Modal Styles */
        .client-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 5000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .client-modal.show {
            display: flex;
        }

        .modal-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 1200px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            animation: slideUp 0.4s ease;
            display: flex;
        }

        .modal-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 24px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            z-index: 10;
            border-radius: 18px 18px 0 0;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.2s ease;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-left {
            flex: 1;
            padding: 80px 24px 24px;
            overflow-y: auto;
            max-height: 85vh;
        }

        .modal-right {
            width: 300px;
            background: #f8fafc;
            border-left: 1px solid #e2e8f0;
            padding: 80px 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .nav-button {
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-button:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .nav-button.active {
            border-color: #3b82f6;
            background: #3b82f6;
            color: white;
        }

        .nav-button-icon {
            font-size: 20px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>

    <div class="monitoring-container">
        <h2 style="margin-bottom: 20px; color: #1e293b; font-size: 24px; font-weight: 700;">
            📦 Client & Member Inventory Monitoring
        </h2>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $totalClients ?? 0 }}</div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $totalInventoryItems ?? 0 }}</div>
                <div class="stat-label">Total Inventory Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $totalMembers ?? 0 }}</div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $lowStockClients ?? 0 }}</div>
                <div class="stat-label">Low Stock Alerts</div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" id="monitoringSearch" class="search-input" placeholder="Search clients, offices, or members..." oninput="filterMonitoring()">
        </div>

        @if(isset($clientsWithFullData) && $clientsWithFullData->count() > 0)
            @foreach($clientsWithFullData as $client)
                <div class="client-section" 
                     data-client-name="{{ strtolower($client->name ?? '') }}" 
                     data-client-office="{{ strtolower($client->office ?? '') }}"
                     data-member-names="{{ strtolower($client->members->pluck('name')->implode(' ')) }}"
                     data-member-emails="{{ strtolower($client->members->pluck('email')->implode(' ')) }}"
                     onclick="openClientModal({{ $client->id }}, '{{ $client->name ?? 'Unknown Client' }}', '{{ $client->office ?? 'Not specified' }}', '{{ $client->email ?? 'No email' }}', {{ $client->inventory_items_count ?? 0 }}, {{ $client->members_count ?? 0 }}, {{ $client->total_available_inventory ?? 0 }})">
                    
                    <div class="client-list-item">
                        <div class="client-basic-info">
                            <div class="client-avatar">
                                {{ strtoupper(substr($client->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="client-details">
                                <div class="client-name">
                                    {{ $client->name ?? 'Unknown Client' }}
                                </div>
                                <div class="client-office">
                                    Office: {{ $client->office ?? 'Not specified' }}
                                </div>
                            </div>
                        </div>
                        <div class="client-stats">
                            <div class="stat-item">
                                <span>📦</span>
                                <span class="stat-value">{{ $client->total_available_inventory ?? 0 }}</span>
                                <span>items</span>
                            </div>
                            <div class="stat-item">
                                <span>👥</span>
                                <span class="stat-value">{{ $client->members_count ?? 0 }}</span>
                                <span>members</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-data">
                <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                <div style="font-size: 18px; font-weight: 600; color: #1e40af;">No client data available.</div>
                <div style="font-size: 14px; margin-top: 8px;">Client inventory and member data will appear here once available.</div>
            </div>
        @endif
    </div>

    <!-- Client Details Modal -->
    <div id="clientModal" class="client-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalOfficeName" style="margin: 0; font-size: 20px; font-weight: 700;">Client Details</h3>
                <button class="modal-close" onclick="closeClientModal()">&times;</button>
            </div>
            
            <div class="modal-left">
                <!-- Overview Section (Default) -->
                <div id="overview-section" class="content-section">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        <!-- Client Info Card -->
                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>�</span>
                                    Client Information
                                </h3>
                            </div>
                            <div class="card-content" style="display: block;">
                                <div class="info-section">
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Client Name</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalClientNameValue" style="margin: 0;">-</div>
                                    </div>
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Office</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalClientOffice" style="margin: 0;">-</div>
                                    </div>
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Email</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalClientEmail" style="margin: 0;">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Stats Card -->
                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>📦</span>
                                    Inventory Statistics
                                </h3>
                            </div>
                            <div class="card-content" style="display: block;">
                                <div class="info-section">
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Total Inventory Items</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalInventoryCount" style="margin: 0;">-</div>
                                    </div>
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Available Inventory</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalAvailableInventory" style="margin: 0;">-</div>
                                    </div>
                                    <div class="info-item" style="display: flex; align-items: center; gap: 8px;">
                                        <div class="info-label" style="margin: 0; min-width: 120px;">Total Members</div>
                                        <div style="color: var(--muted); font-weight: 600;">:</div>
                                        <div class="info-value" id="modalMembersCount" style="margin: 0;">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Inventory Section -->
                <div id="inventory-section" class="content-section active">
                    <div id="inventoryContent">
                        <!-- Content will be populated dynamically -->
                    </div>
                </div>

                <!-- Members Section -->
                <div id="members-section" class="content-section">
                    <div id="membersContent">
                        <!-- Content will be populated dynamically -->
                    </div>
                </div>
            </div>
            
            <div class="modal-right">
                <button class="nav-button active" onclick="showSection('inventory', event)">
                    <span class="nav-button-icon">📦</span>
                    <span>Client Inventory</span>
                </button>
                <button class="nav-button" onclick="showSection('members', event)">
                    <span class="nav-button-icon">👥</span>
                    <span>Members & Their Held Items</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Member Items Popups for Admin -->
    @foreach($clientsWithFullData as $client)
        @foreach($client->members as $member)
            @php
                // Get the actual member model to access distributions
                $currentMember = \App\Models\ClientMember::find($member->id);
            @endphp
            
            <!-- Backdrop -->
            <div id="admin-member-items-backdrop-{{ $client->id }}-{{ $member->id }}" 
                 style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998; cursor:pointer;"
                 onclick="toggleAdminMemberItemsDropdown('admin-member-items-{{ $client->id }}-{{ $member->id }}')">
            </div>
            
            <!-- Popup Window -->
            <div id="admin-member-items-{{ $client->id }}-{{ $member->id }}" 
                 style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:9999; min-width:600px; max-width:800px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,0.25); margin:0; padding:0; pointer-events:auto;"
                 onclick="event.stopPropagation();">
                
                <!-- Header -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:white; border-radius:12px 12px 0 0;">
                    <div>
                        <div style="font-weight:700; color:#fff; font-size:16px;">{{ $member->name ?? 'Unknown Member' }}</div>
                        <div style="color:rgba(255,255,255,0.8); font-size:13px; margin-top:2px;">{{ $member->email ?? 'No email' }}</div>
                        <div style="color:rgba(255,255,255,0.7); font-size:11px; margin-top:2px;">Client: {{ $client->name ?? 'Unknown Client' }}</div>
                    </div>
                    <button type="button" onclick="toggleAdminMemberItemsDropdown('admin-member-items-{{ $client->id }}-{{ $member->id }}')" style="background:none; border:none; color:rgba(255,255,255,0.8); cursor:pointer; padding:6px 8px; border-radius:6px; font-size:20px; line-height:1;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">×</button>
                </div>
                
                <div style="max-height:300px; overflow-y:auto; pointer-events:auto;" onclick="event.stopPropagation();">
                    @php
                        // Collect only distributed items (no deductions)
                        $distributedItems = collect();
                        
                        // Add distributed items only
                        if($currentMember && $currentMember->distributions->count() > 0) {
                            foreach($currentMember->distributions as $distribution) {
                                $availableQty = $distribution->distributed_qty - ($distribution->used_qty ?? 0);
                                $itemName = $distribution->stockRequestItem->stock->description ?? 'Item';
                                
                                if($distributedItems->has($itemName)) {
                                    // Update existing item
                                    $existing = $distributedItems->get($itemName);
                                    $existing->distributed_qty += $distribution->distributed_qty;
                                    $existing->used_qty += $distribution->used_qty ?? 0;
                                    $existing->available_qty += $availableQty;
                                } else {
                                    // Add new item
                                    $distributedItems->put($itemName, (object)[
                                        'name' => $itemName,
                                        'distributed_qty' => $distribution->distributed_qty,
                                        'used_qty' => $distribution->used_qty ?? 0,
                                        'available_qty' => $availableQty
                                    ]);
                                }
                            }
                        }
                    @endphp
                    
                    @if($distributedItems->count() > 0)
                        <div style="padding:8px 12px;">
                            <div style="font-weight:600; color:#374151; font-size:12px; margin-bottom:8px;">DISTRIBUTED ITEMS</div>
                            @foreach($distributedItems as $item)
                                <div style="padding:8px; margin-bottom:6px; border:1px solid #e5e7eb; border-radius:6px; background:#f9fafb;">
                                    <div style="font-weight:600; color:#1f2937; font-size:12px; margin-bottom:4px;">{{ $item->name }}</div>
                                    <div style="display:flex; gap:12px; font-size:11px; color:#6b7280;">
                                        <span><strong>Quantity:</strong> {{ $item->distributed_qty }}</span>
                                        <span><strong>Available:</strong> <span style="color:#059669; font-weight:600;">{{ $item->available_qty }}</span></span>
                                        <span><strong>Used:</strong> <span style="color:#d97706; font-weight:600;">{{ $item->used_qty }}</span></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="padding:20px; text-align:center; color:#9ca3af; font-size:12px;">
                            No items distributed to this member
                        </div>
                    @endif
                </div>
                
                <div style="padding:16px; background:#f8fafc; border-radius:0 0 12px 12px; border-top:1px solid #e2e8f0;">
                    <div style="font-size:12px; color:#64748b; text-align:center;">
                        <div style="margin-bottom:6px; font-weight:600; color:#1e40af; font-size:14px;">SUMMARY</div>
                        <div style="font-size:13px;">
                            Total Received: {{ $member->distributed_items }} | 
                            Total Used: {{ $member->used_items }} | 
                            <span style="color:#059669; font-weight:700;">Available: {{ $member->available_items }}</span>
                        </div>
                                            </div>
                </div>
            </div>
        @endforeach
    @endforeach

    <script>
        // Store client data globally
        let clientsData = {};
        let currentClientId = null;

        @foreach($clientsWithFullData as $client)
            clientsData[{{ $client->id }}] = {
                id: {{ $client->id }},
                name: '{{ $client->name ?? "Unknown Client" }}',
                office: '{{ $client->office ?? "Not specified" }}',
                email: '{{ $client->email ?? "No email" }}',
                inventory_items: @json($client->inventory_items),
                members: @json($client->members),
                inventory_items_count: {{ $client->inventory_items_count ?? 0 }},
                members_count: {{ $client->members_count ?? 0 }},
                total_available_inventory: {{ $client->total_available_inventory ?? 0 }}
            };
        @endforeach

        function openClientModal(clientId, name, office, email, inventoryCount, membersCount, availableInventory) {
            currentClientId = clientId;
            
            // Set overview data
            document.getElementById('modalOfficeName').textContent = office + ' - Details';
            document.getElementById('modalClientNameValue').textContent = name;
            document.getElementById('modalClientOffice').textContent = office;
            document.getElementById('modalClientEmail').textContent = email;
            document.getElementById('modalInventoryCount').textContent = inventoryCount;
            document.getElementById('modalMembersCount').textContent = membersCount;
            document.getElementById('modalAvailableInventory').textContent = availableInventory;
            
            // Load inventory content
            loadInventoryContent(clientId);
            
            // Load members content
            loadMembersContent(clientId);
            
            // Show modal
            document.getElementById('clientModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Show inventory section by default
            showSection('inventory');
        }

        function closeClientModal() {
            document.getElementById('clientModal').classList.remove('show');
            document.body.style.overflow = '';
            currentClientId = null;
        }

        function showSection(sectionName, event = null) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').classList.add('active');
            
            // Add active class to the clicked button, or infer from section name when triggered programmatically
            let activeButton = null;
            if (event && event.target) {
                activeButton = event.target.closest('.nav-button');
            }
            if (!activeButton) {
                activeButton = document.querySelector(`.nav-button[onclick*="showSection('${sectionName}'`);
            }
            if (activeButton) {
                activeButton.classList.add('active');
            }
        }

        function loadInventoryContent(clientId) {
            const client = clientsData[clientId];
            if (!client) return;
            
            const inventoryContent = document.getElementById('inventoryContent');
            
            if (client.inventory_items && client.inventory_items.length > 0) {
                let html = `
                    <div class="settings-card">
                        <div class="card-header">
                            <h3>
                                <span>📦</span>
                                Client Inventory
                            </h3>
                        </div>
                        <div class="card-content" style="display: block;">
                            <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--line); border-radius: 8px;">
                                <table class="inventory-table" style="margin: 0;">
                                    <thead style="position: sticky; top: 0; background: var(--panel2); z-index: 10;">
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Description</th>
                                            <th>Approved</th>
                                            <th>Available</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                client.inventory_items.slice(0, 10).forEach(item => {
                    const availableQty = item.my_inventory || Math.max(0, (item.approved_qty || 0) - (item.distributed_qty || 0));
                    const stockStatus = availableQty <= 5 ? 'low' : (availableQty <= 20 ? 'medium' : 'good');
                    
                    html += `
                        <tr>
                            <td style="font-weight: 600; color: #1e40af;">
                                ${item.stock?.id_no || 'N/A'}
                                ${item.type === 'urgent' ? '<span class="urgent-badge">URGENT</span>' : ''}
                            </td>
                            <td>${item.stock?.description || item.stock?.name || 'Unknown Item'}</td>
                            <td style="text-align: center; font-weight: 600;">${item.approved_qty || 0}</td>
                            <td style="text-align: center; font-weight: 600; color: #059669;">${availableQty}</td>
                            <td style="text-align: center;">
                                <span class="stock-badge stock-${stockStatus}">
                                    ${stockStatus === 'low' ? 'Low Stock' : (stockStatus === 'medium' ? 'Medium' : 'Good')}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                            ${client.inventory_items.length > 10 ? `
                                <div style="margin-top: 8px; padding: 8px; background: var(--panel2); border-radius: 6px; text-align: center; font-size: 12px; color: var(--muted);">
                                    Showing 10 of ${client.inventory_items.length} items. Scroll to see more.
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                inventoryContent.innerHTML = html;
            } else {
                inventoryContent.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 12px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                        <div>No inventory items found for this client.</div>
                    </div>
                `;
            }
        }

        function loadMembersContent(clientId) {
            const client = clientsData[clientId];
            if (!client) return;
            
            const membersContent = document.getElementById('membersContent');
            
            if (client.members && client.members.length > 0) {
                let html = `
                    <div class="settings-card">
                        <div class="card-header">
                            <h3>
                                <span>👥</span>
                                Members & Their Held Items
                            </h3>
                        </div>
                        <div class="card-content" style="display: block;">
                            <table class="inventory-table">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Email</th>
                                        <th>Distributed Items</th>
                                        <th>Available Items</th>
                                        <th>Used Items</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                client.members.forEach(member => {
                    html += `
                        <tr>
                            <td style="font-weight: 600; color: #1e40af;">
                                ${member.name || 'Unknown Member'}
                            </td>
                            <td>${member.email || 'No email'}</td>
                            <td style="text-align: center; font-weight: 600;">
                                ${member.distributed_items || 0}
                            </td>
                            <td style="text-align: center; font-weight: 600; color: #059669;">
                                ${member.available_items || 0}
                            </td>
                            <td style="text-align: center; font-weight: 600; color: #d97706;">
                                ${member.used_items || 0}
                            </td>
                            <td style="text-align: center;">
                                <span class="member-badge ${(member.available_items || 0) > 0 ? 'member-active' : 'member-inactive'}">
                                    ${(member.available_items || 0) > 0 ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                membersContent.innerHTML = html;
            } else {
                membersContent.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 12px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                        <div>No members found for this client.</div>
                    </div>
                `;
            }
        }

        // Close modal when clicking outside
        document.getElementById('clientModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeClientModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('clientModal').classList.contains('show')) {
                closeClientModal();
            }
        });

        function toggleCard(cardId) {
            const card = document.getElementById(cardId);
            const header = card.previousElementSibling;
            
            if (card.style.display === 'none' || card.style.display === '') {
                card.style.display = 'block';
                header.classList.remove('collapsed');
                header.classList.add('expanded');
            } else {
                card.style.display = 'none';
                header.classList.remove('expanded');
                header.classList.add('collapsed');
            }
        }

        function toggleAdminMemberItemsDropdown(dropdownId) {
            // Prevent event bubbling
            if (event) {
                event.stopPropagation();
            }
            
            // Close all other dropdowns and backdrops first
            const allDropdowns = document.querySelectorAll('[id^="admin-member-items-"]:not([id^="admin-member-items-backdrop-"])');
            const allBackdrops = document.querySelectorAll('[id^="admin-member-items-backdrop-"]');
            
            allDropdowns.forEach(dropdown => {
                if (dropdown.id !== dropdownId) {
                    dropdown.style.display = 'none';
                }
            });
            
            allBackdrops.forEach(backdrop => {
                if (backdrop.id !== 'admin-member-items-backdrop-' + dropdownId.replace('admin-member-items-', '')) {
                    backdrop.style.display = 'none';
                }
            });
            
            // Toggle the current dropdown and backdrop
            const dropdown = document.getElementById(dropdownId);
            const backdropId = 'admin-member-items-backdrop-' + dropdownId.replace('admin-member-items-', '');
            const backdrop = document.getElementById(backdropId);
            
            if (dropdown && backdrop) {
                const isVisible = dropdown.style.display === 'block';
                dropdown.style.display = isVisible ? 'none' : 'block';
                backdrop.style.display = isVisible ? 'none' : 'block';
                
                // Prevent background scrolling when popup is open
                if (!isVisible) {
                    document.body.style.overflow = 'hidden';
                    document.body.style.position = 'fixed';
                    document.body.style.width = '100%';
                    document.body.style.top = `-${window.scrollY}px`;
                } else {
                    // Restore scrolling
                    const scrollY = document.body.style.top;
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                    document.body.style.top = '';
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[id^="admin-member-items-"]') && !event.target.closest('button[onclick*="toggleAdminMemberItemsDropdown"]')) {
                const allDropdowns = document.querySelectorAll('[id^="admin-member-items-"]');
                const hasOpenDropdowns = Array.from(allDropdowns).some(dropdown => dropdown.style.display === 'block');
                
                allDropdowns.forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
                
                const allBackdrops = document.querySelectorAll('[id^="admin-member-items-backdrop-"]');
                allBackdrops.forEach(backdrop => {
                    backdrop.style.display = 'none';
                });
                
                // Restore scrolling if any dropdowns were open
                if (hasOpenDropdowns) {
                    const scrollY = document.body.style.top;
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                    document.body.style.top = '';
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
            }
        });

        function filterMonitoring() {
            const searchTerm = document.getElementById('monitoringSearch').value.toLowerCase();
            const clientSections = document.querySelectorAll('.client-section');

            clientSections.forEach(section => {
                const clientName = section.dataset.clientName || '';
                const clientOffice = section.dataset.clientOffice || '';
                const memberNames = section.dataset.memberNames || '';
                const memberEmails = section.dataset.memberEmails || '';

                const matches = clientName.includes(searchTerm) ||
                               clientOffice.includes(searchTerm) ||
                               memberNames.includes(searchTerm) ||
                               memberEmails.includes(searchTerm);

                if (matches || searchTerm === '') {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }

        // Auto-refresh every 5 minutes to get latest data
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
@endsection
