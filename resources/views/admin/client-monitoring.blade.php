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
        box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
        border-color: #3b82f6;
    }

    .client-list-item {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
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
        flex-shrink: 0;
    }

    .client-details {
        display: flex;
        flex-direction: column;
    }

    .client-name {
        font-weight: 700;
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
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-value {
        font-weight: 700;
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
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
        color: #374151;
        white-space: nowrap;
    }

    .inventory-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
    }

    .inventory-table tr:hover {
        background: #f8fafc;
    }

    .settings-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 18px;
        margin-bottom: 18px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .card-header {
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(37, 99, 235, .08);
        border-bottom: 1px solid var(--line);
    }

    .card-header h3 {
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
    }

    .stock-badge,
    .member-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
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

    .stat-card .stat-value {
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
        position: relative;
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
        padding: 90px 24px 24px;
        overflow-y: auto;
        max-height: 85vh;
    }

    .modal-right {
        width: 300px;
        background: #f8fafc;
        border-left: 1px solid #e2e8f0;
        padding: 90px 20px 24px;
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
        font-weight: 700;
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

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: #334155;
    }

    .info-label {
        min-width: 130px;
        font-weight: 700;
        color: #475569;
    }

    .held-items-details {
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #eff6ff;
        padding: 8px 10px;
    }

    .held-items-details summary {
        cursor: pointer;
        font-weight: 700;
        color: #1d4ed8;
    }

    .held-item {
        margin-top: 8px;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @media (max-width: 900px) {
        .modal-container {
            flex-direction: column;
            overflow-y: auto;
        }

        .modal-left {
            padding: 90px 16px 16px;
            max-height: none;
        }

        .modal-right {
            width: 100%;
            padding: 16px;
            border-left: none;
            border-top: 1px solid #e2e8f0;
        }

        .client-list-item {
            align-items: flex-start;
            flex-direction: column;
        }

        .client-stats {
            justify-content: flex-start;
        }
    }
</style>

<div class="monitoring-container">
    <h2 style="margin-bottom: 20px; color: #1e293b; font-size: 24px; font-weight: 700;">
        📦 Client & Member Inventory Monitoring
    </h2>

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

    <div class="search-container">
        <span class="search-icon">🔍</span>
        <input
            type="text"
            id="monitoringSearch"
            class="search-input"
            placeholder="Search clients, offices, or members..."
            oninput="filterMonitoring()"
        >
    </div>

    @if(isset($clientsWithFullData) && $clientsWithFullData->count() > 0)
        @foreach($clientsWithFullData as $client)
            @php
                $officeName = is_object($client->office ?? null)
                    ? ($client->office->office ?? 'Not specified')
                    : ($client->office ?? 'Not specified');

                $members = collect($client->members ?? []);
            @endphp

            <div
                class="client-section"
                data-client-name="{{ strtolower($client->name ?? '') }}"
                data-client-office="{{ strtolower($officeName) }}"
                data-member-names="{{ strtolower($members->pluck('name')->implode(' ')) }}"
                data-member-emails="{{ strtolower($members->pluck('email')->implode(' ')) }}"
                data-modal-id="client-modal-{{ $client->id }}"
                onclick="openClientModalFromElement(this)"
            >
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
                                Office: {{ $officeName }}
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
            <div style="font-size: 18px; font-weight: 700; color: #1e40af;">No client data available.</div>
            <div style="font-size: 14px; margin-top: 8px;">Client inventory and member data will appear here once available.</div>
        </div>
    @endif
</div>

@if(isset($clientsWithFullData) && $clientsWithFullData->count() > 0)
    @foreach($clientsWithFullData as $client)
        @php
            $officeName = is_object($client->office ?? null)
                ? ($client->office->office ?? 'Not specified')
                : ($client->office ?? 'Not specified');

            $inventoryItems = collect($client->inventory_items ?? []);
            $members = collect($client->members ?? []);
        @endphp

        <div id="client-modal-{{ $client->id }}" class="client-modal">
            <div class="modal-container" onclick="event.stopPropagation();">
                <div class="modal-header">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;">
                        {{ $officeName }} - Details
                    </h3>

                    <button
                        type="button"
                        class="modal-close"
                        onclick="closeClientModal('client-modal-{{ $client->id }}')"
                    >
                        &times;
                    </button>
                </div>

                <div class="modal-left">
                    <div class="info-grid">
                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>👤</span>
                                    Client Information
                                </h3>
                            </div>

                            <div class="card-content">
                                <div class="info-item">
                                    <div class="info-label">Client Name</div>
                                    <div>:</div>
                                    <div>{{ $client->name ?? 'Unknown Client' }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Office</div>
                                    <div>:</div>
                                    <div>{{ $officeName }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div>:</div>
                                    <div>{{ $client->email ?? 'No email' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>📦</span>
                                    Inventory Statistics
                                </h3>
                            </div>

                            <div class="card-content">
                                <div class="info-item">
                                    <div class="info-label">Inventory Items</div>
                                    <div>:</div>
                                    <div>{{ $client->inventory_items_count ?? 0 }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Available Inventory</div>
                                    <div>:</div>
                                    <div>{{ $client->total_available_inventory ?? 0 }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Total Members</div>
                                    <div>:</div>
                                    <div>{{ $client->members_count ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="inventory-section-{{ $client->id }}" class="content-section active">
                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>📦</span>
                                    Client Inventory
                                </h3>
                            </div>

                            <div class="card-content">
                                @if($inventoryItems->count() > 0)
                                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--line); border-radius: 8px;">
                                        <table class="inventory-table" style="margin: 0;">
                                            <thead>
                                                <tr>
                                                    <th>Item ID</th>
                                                    <th>Description</th>
                                                    <th>Approved</th>
                                                    <th>Available</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach($inventoryItems as $item)
                                                    @php
                                                        $approvedQty = (int) data_get($item, 'approved_qty', 0);
                                                        $distributedQty = (int) data_get($item, 'distributed_qty', 0);
                                                        $availableQty = data_get($item, 'my_inventory');

                                                        if ($availableQty === null) {
                                                            $availableQty = max(0, $approvedQty - $distributedQty);
                                                        }

                                                        $availableQty = (int) $availableQty;

                                                        if ($availableQty <= 5) {
                                                            $stockStatus = 'low';
                                                            $stockLabel = 'Low Stock';
                                                        } elseif ($availableQty <= 20) {
                                                            $stockStatus = 'medium';
                                                            $stockLabel = 'Medium';
                                                        } else {
                                                            $stockStatus = 'good';
                                                            $stockLabel = 'Good';
                                                        }
                                                    @endphp

                                                    <tr>
                                                        <td style="font-weight: 700; color: #1e40af;">
                                                            {{ data_get($item, 'stock.id_no', 'N/A') }}

                                                            @if(data_get($item, 'type') === 'urgent')
                                                                <span class="urgent-badge">URGENT</span>
                                                            @endif
                                                        </td>

                                                        <td>
                                                            {{ data_get($item, 'stock.description', data_get($item, 'stock.name', 'Unknown Item')) }}
                                                        </td>

                                                        <td style="text-align: center; font-weight: 700;">
                                                            {{ $approvedQty }}
                                                        </td>

                                                        <td style="text-align: center; font-weight: 700; color: #059669;">
                                                            {{ $availableQty }}
                                                        </td>

                                                        <td style="text-align: center;">
                                                            <span class="stock-badge stock-{{ $stockStatus }}">
                                                                {{ $stockLabel }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 12px;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                                        <div>No inventory items found for this client.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="members-section-{{ $client->id }}" class="content-section">
                        <div class="settings-card">
                            <div class="card-header">
                                <h3>
                                    <span>👥</span>
                                    Members & Their Held Items
                                </h3>
                            </div>

                            <div class="card-content">
                                @if($members->count() > 0)
                                    <div style="overflow-x: auto;">
                                        <table class="inventory-table">
                                            <thead>
                                                <tr>
                                                    <th>Member Name</th>
                                                    <th>Email</th>
                                                    <th>Distributed</th>
                                                    <th>Available</th>
                                                    <th>Used</th>
                                                    <th>Status</th>
                                                    <th>Held Items</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach($members as $member)
                                                    @php
                                                        $availableItems = (int) data_get($member, 'available_items', 0);
                                                        $distributedItemsCount = (int) data_get($member, 'distributed_items', 0);
                                                        $usedItemsCount = (int) data_get($member, 'used_items', 0);

                                                        $memberDistributions = collect(data_get($member, 'distributions', []));
                                                        $heldItems = collect();

                                                        foreach ($memberDistributions as $distribution) {
                                                            $distributedQty = (int) data_get($distribution, 'distributed_qty', 0);
                                                            $usedQty = (int) data_get($distribution, 'used_qty', 0);
                                                            $availableQty = max(0, $distributedQty - $usedQty);
                                                            $itemName = data_get($distribution, 'stockRequestItem.stock.description', 'Item');

                                                            if ($heldItems->has($itemName)) {
                                                                $existing = $heldItems->get($itemName);
                                                                $existing->distributed_qty += $distributedQty;
                                                                $existing->used_qty += $usedQty;
                                                                $existing->available_qty += $availableQty;
                                                            } else {
                                                                $heldItems->put($itemName, (object) [
                                                                    'name' => $itemName,
                                                                    'distributed_qty' => $distributedQty,
                                                                    'used_qty' => $usedQty,
                                                                    'available_qty' => $availableQty,
                                                                ]);
                                                            }
                                                        }
                                                    @endphp

                                                    <tr>
                                                        <td style="font-weight: 700; color: #1e40af;">
                                                            {{ data_get($member, 'name', 'Unknown Member') }}
                                                        </td>

                                                        <td>
                                                            {{ data_get($member, 'email', 'No email') }}
                                                        </td>

                                                        <td style="text-align: center; font-weight: 700;">
                                                            {{ $distributedItemsCount }}
                                                        </td>

                                                        <td style="text-align: center; font-weight: 700; color: #059669;">
                                                            {{ $availableItems }}
                                                        </td>

                                                        <td style="text-align: center; font-weight: 700; color: #d97706;">
                                                            {{ $usedItemsCount }}
                                                        </td>

                                                        <td style="text-align: center;">
                                                            <span class="member-badge {{ $availableItems > 0 ? 'member-active' : 'member-inactive' }}">
                                                                {{ $availableItems > 0 ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </td>

                                                        <td>
                                                            @if($heldItems->count() > 0)
                                                                <details class="held-items-details">
                                                                    <summary>View Items</summary>

                                                                    @foreach($heldItems as $heldItem)
                                                                        <div class="held-item">
                                                                            <div style="font-weight: 700; color: #1f2937; font-size: 12px; margin-bottom: 4px;">
                                                                                {{ $heldItem->name }}
                                                                            </div>

                                                                            <div style="display: flex; gap: 12px; font-size: 11px; color: #6b7280; flex-wrap: wrap;">
                                                                                <span>
                                                                                    <strong>Quantity:</strong> {{ $heldItem->distributed_qty }}
                                                                                </span>

                                                                                <span>
                                                                                    <strong>Available:</strong>
                                                                                    <span style="color: #059669; font-weight: 700;">
                                                                                        {{ $heldItem->available_qty }}
                                                                                    </span>
                                                                                </span>

                                                                                <span>
                                                                                    <strong>Used:</strong>
                                                                                    <span style="color: #d97706; font-weight: 700;">
                                                                                        {{ $heldItem->used_qty }}
                                                                                    </span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </details>
                                                            @else
                                                                <span style="color: #9ca3af; font-size: 12px;">
                                                                    No items
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 12px;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                                        <div>No members found for this client.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-right">
                    <button
                        type="button"
                        class="nav-button active"
                        data-client-id="{{ $client->id }}"
                        data-section="inventory"
                        onclick="showClientSectionFromElement(this, event)"
                    >
                        <span class="nav-button-icon">📦</span>
                        <span>Client Inventory</span>
                    </button>

                    <button
                        type="button"
                        class="nav-button"
                        data-client-id="{{ $client->id }}"
                        data-section="members"
                        onclick="showClientSectionFromElement(this, event)"
                    >
                        <span class="nav-button-icon">👥</span>
                        <span>Members & Their Held Items</span>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
@endif

<script>

    function openClientModalFromElement(element) {
        const modalId = element.dataset.modalId;
        openClientModal(modalId);
    }

    function openClientModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeClientModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function showClientSectionFromElement(element, event) {
        const clientId = element.dataset.clientId;
        const sectionName = element.dataset.section;

        showClientSection(clientId, sectionName, event);
    }

    function showClientSection(clientId, sectionName, event = null) {
        const modal = document.getElementById(`client-modal-${clientId}`);

        if (!modal) {
            return;
        }

        modal.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        modal.querySelectorAll('.nav-button').forEach(button => {
            button.classList.remove('active');
        });

        const selectedSection = document.getElementById(`${sectionName}-section-${clientId}`);

        if (selectedSection) {
            selectedSection.classList.add('active');
        }

        if (event && event.target) {
            const button = event.target.closest('.nav-button');

            if (button) {
                button.classList.add('active');
            }
        }
    }

    function filterMonitoring() {
        const searchInput = document.getElementById('monitoringSearch');
        const searchTerm = (searchInput?.value || '').toLowerCase();
        const clientSections = document.querySelectorAll('.client-section');

        clientSections.forEach(section => {
            const clientName = section.dataset.clientName || '';
            const clientOffice = section.dataset.clientOffice || '';
            const memberNames = section.dataset.memberNames || '';
            const memberEmails = section.dataset.memberEmails || '';

            const matches =
                clientName.includes(searchTerm) ||
                clientOffice.includes(searchTerm) ||
                memberNames.includes(searchTerm) ||
                memberEmails.includes(searchTerm);

            section.style.display = matches || searchTerm === '' ? 'block' : 'none';
        });
    }

    document.addEventListener('click', function(event) {
        const openModal = event.target.closest('.client-modal.show');

        if (openModal && event.target === openModal) {
            openModal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.client-modal.show').forEach(modal => {
                modal.classList.remove('show');
            });

            document.body.style.overflow = '';
        }
    });
</script>
@endsection