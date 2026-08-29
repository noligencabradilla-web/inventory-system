@php
    $pendingRequestsCount = class_exists(\App\Models\StockRequest::class)
        ? \App\Models\StockRequest::where('status', 'pending')->count()
        : 0;
    $pendingPR = class_exists(\App\Models\PasswordResetRequest::class)
        ? \App\Models\PasswordResetRequest::where('status', 'pending')->count()
        : 0;
@endphp

<style>
    /* Enhanced admin sidebar styles */
    .nav-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .nav-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 3px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 0 3px 3px 0;
    }

    .nav-item:hover::before {
        transform: translateX(0);
    }

    .nav-item:hover .nav-icon {
        transform: scale(1.1);
    }

    .nav-item.active::before {
        transform: translateX(0);
        background: linear-gradient(135deg, #2563eb, #1e40af);
    }

    .nav-item.active .nav-icon {
        color: #2563eb;
        transform: scale(1.05);
    }

    .nav-text {
        position: relative;
        z-index: 1;
        flex: 1;
    }

    .nav-tooltip {
        position: absolute;
        left: calc(100% + 8px);
        top: 50%;
        transform: translateY(-50%);
        background: #1e293b;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 1000;
    }

    .nav-tooltip::before {
        content: '';
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #1e293b;
    }

    .nav-item:hover .nav-tooltip {
        opacity: 1;
    }

    /* Enhanced notification badge */
    .sidebar-count{
        display:inline-flex;
        align-items: center;
        justify-content: center;
        min-width:24px;
        height:24px;
        padding:0 6px;
        border-radius:999px;
        background:linear-gradient(135deg, #ef4444, #dc2626);
        color:#fff;
        font-weight:700;
        font-size:11px;
        text-align:center;
        box-shadow:0 2px 8px rgba(239,68,68,0.3);
        transition: all 0.2s ease;
        position: relative;
        z-index: 2;
    }

    .sidebar-count:hover {
        transform: scale(1.1);
        box-shadow:0 4px 12px rgba(239,68,68,0.4);
    }

    /* Pulse animation for items with notifications */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .nav-item.has-notification .nav-icon {
        animation: pulse 2s infinite;
    }

    /* Badge animation */
    @keyframes badge-bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .sidebar-count {
        animation: badge-bounce 3s infinite;
    }

    /* Mobile improvements */
    @media (max-width: 640px) {
        .nav-tooltip {
            display: none;
        }
    }
</style>

<a href="{{ route('admin.dashboard') }}" class="{{ request()->is('admin') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
    </svg>
    <span class="nav-text">Dashboard</span>
    <span class="nav-tooltip">Admin dashboard overview</span>
</a>

<a href="{{ route('admin.summary') }}" class="{{ request()->is('admin/summary*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 20V10"></path>
        <path d="M12 20V4"></path>
        <path d="M6 20v-6"></path>
    </svg>
    <span class="nav-text">Summary</span>
    <span class="nav-tooltip">View system statistics</span>
</a>

<a href="/admin/categories" class="{{ request()->is('admin/categories*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
    </svg>
    <span class="nav-text">Categories</span>
    <span class="nav-tooltip">Manage item categories</span>
</a>

<a href="/admin/stocks" class="{{ request()->is('admin/stocks*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
    </svg>
    <span class="nav-text">Stocks</span>
    <span class="nav-tooltip">Manage inventory stocks</span>
</a>

<a href="/admin/inbound" class="{{ request()->is('admin/inbound*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
    </svg>
    <span class="nav-text">Inbound</span>
    <span class="nav-tooltip">Track incoming inventory</span>
</a>

<a href="{{ route('admin.allocations.index') }}"
   class="{{ request()->routeIs('admin.allocations.*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <path d="M9 12l2 2 4-4"></path>
    </svg>
    <span class="nav-text">Allocations</span>
    <span class="nav-tooltip">Monitor office stock allocations</span>
</a>

<a href="/admin/outbound" class="{{ request()->is('admin/outbound*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
    </svg>
    <span class="nav-text">Outbound</span>
    <span class="nav-tooltip">Track outgoing inventory</span>
</a>

<a href="/admin/requests" class="{{ request()->is('admin/requests*') ? 'active' : '' }} nav-item {{ $pendingRequestsCount ? 'has-notification' : '' }}">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span class="nav-text">Requests</span>
    @if($pendingRequestsCount)
        <span class="sidebar-count">{{ $pendingRequestsCount }}</span>
    @endif
    <span class="nav-tooltip">Manage stock requests</span>
</a>

<a href="/admin/password-reset" class="{{ request()->is('admin/password-reset*') ? 'active' : '' }} nav-item {{ $pendingPR ? 'has-notification' : '' }}">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="11" width="18" height="10" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
    </svg>
    <span class="nav-text">Password Reset</span>
    @if($pendingPR)
        <span class="sidebar-count">{{ $pendingPR }}</span>
    @endif
    <span class="nav-tooltip">Handle password reset requests</span>
</a>

<a href="{{ route('admin.notifications') }}" class="{{ request()->is('admin/notifications*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
    </svg>
    <span class="nav-text">Notifications</span>
    <span class="nav-tooltip">View all notifications</span>
</a>

{{-- Notification Settings tab hidden --}}
{{-- 
<a href="{{ route('admin.notification-preferences.index') }}" class="{{ request()->is('admin/notification-preferences*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 1.54l4.24 4.24M1 12h6m6 0h6"></path>
    </svg>
    <span class="nav-text">Notification Settings</span>
    <span class="nav-tooltip">Configure notification preferences</span>
</a>
--}}

<a href="{{ route('admin.users.index') }}" class="{{ request()->is('admin/users*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
    <span class="nav-text">Accounts</span>
    <span class="nav-tooltip">Manage user accounts</span>
</a>

<a href="{{ route('admin.client.monitoring') }}" class="{{ request()->is('admin/client-monitoring*') ? 'active' : '' }} nav-item">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12" y1="22.08" x2="12" y2="12"></line>
    </svg>
    <span class="nav-text">Client Monitoring</span>
    <span class="nav-tooltip">Monitor client inventory & members</span>
</a>
