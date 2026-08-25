<?php
    // Add enhanced styles for client sidebar
    $enhancedSidebar = true;
?>

<style>
/* Enhanced client sidebar styles */
.nav-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.nav-item {
    display: flex;
    align-items: center;
    justify-content: flex-start;
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
    text-align: left;
    flex-grow: 1;
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

/* Pulse animation for important items */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.nav-item.pulse .nav-icon {
    animation: pulse 2s infinite;
}

/* Mobile improvements */
@media (max-width: 640px) {
    .nav-tooltip {
        display: none;
    }
}
</style>

<?php if(auth()->user()->role === 'subaccount' && auth()->user()->subaccount): ?>
    <a href="<?php echo e(route('client.account.subaccounts.show', ['subaccount' => auth()->user()->subaccount, 'tab' => 'details'])); ?>" class="<?php echo e(request()->routeIs('client.account.subaccounts.show') && request('tab', 'details') === 'details' ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span class="nav-text">Subaccount Details</span>
        <span class="nav-tooltip">View your subaccount information</span>
    </a>

    <a href="<?php echo e(route('client.account.subaccounts.show', ['subaccount' => auth()->user()->subaccount, 'tab' => 'members'])); ?>" class="<?php echo e(request()->routeIs('client.account.subaccounts.show') && request('tab') === 'members' ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span class="nav-text">Monitor Members</span>
        <span class="nav-tooltip">Manage subaccount members</span>
    </a>

    <a href="<?php echo e(route('client.account.subaccounts.show', ['subaccount' => auth()->user()->subaccount, 'tab' => 'inventory'])); ?>" class="<?php echo e(request()->routeIs('client.account.subaccounts.show') && request('tab') === 'inventory' ? 'active' : ''); ?> nav-item pulse">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
        </svg>
        <span class="nav-text">Available Items</span>
        <span class="nav-tooltip">Browse available inventory</span>
    </a>

    <a href="<?php echo e(route('client.account')); ?>" class="<?php echo e(request()->routeIs('client.account') && request('tab', 'settings') !== 'inventory' && request('tab') !== 'subaccounts' ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.96l4.24 4.24M1.54 14.04l4.24-4.24M18.46 14.04l4.24-4.24"></path>
        </svg>
        <span class="nav-text">Account Settings</span>
        <span class="nav-tooltip">Configure account preferences</span>
    </a>
<?php else: ?>
    <a href="<?php echo e(route('client.dashboard')); ?>" class="<?php echo e(request()->is('client') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span class="nav-text">Dashboard</span>
        <span class="nav-tooltip">Main dashboard overview</span>
    </a>

    <a href="<?php echo e(route('client.summary')); ?>" class="<?php echo e(request()->is('client/summary*') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span class="nav-text">Transaction History</span>
        <span class="nav-tooltip">View transaction records</span>
    </a>

    <a href="<?php echo e(route('client.inventory')); ?>" class="<?php echo e(request()->is('client/inventory*') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        </svg>
        <span class="nav-text">My Inventory</span>
        <span class="nav-tooltip">Manage your inventory</span>
    </a>

    <a href="<?php echo e(route('client.account', ['tab' => 'inventory'])); ?>" class="<?php echo e(request()->routeIs('client.account') && request('tab') === 'inventory' ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 20V10"></path>
            <path d="M12 20V4"></path>
            <path d="M6 20v-6"></path>
        </svg>
        <span class="nav-text">Report</span>
        <span class="nav-tooltip">View analytics and reports</span>
    </a>

    <a href="<?php echo e(route('client.account', ['tab' => 'members'])); ?>" class="<?php echo e(request()->routeIs('client.account') && request('tab') === 'members' ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span class="nav-text">Members</span>
        <span class="nav-tooltip">Manage team members</span>
    </a>

    <a href="<?php echo e(route('client.stocks')); ?>" class="<?php echo e(request()->is('client/stocks*') ? 'active' : ''); ?> nav-item pulse">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="nav-text">Available Stocks</span>
        <span class="nav-tooltip">Browse stock catalog</span>
    </a>
    <a href="<?php echo e(route('client.allocations.index')); ?>"
    class="<?php echo e(request()->routeIs('client.allocations.index') ? 'active' : ''); ?> nav-item pulse">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <path d="M9 12l2 2 4-4"></path>
        </svg>
        <span class="nav-text">Allocated Items</span>
        <span class="nav-tooltip">View items allocated to your office</span>
    </a>

    <a href="<?php echo e(route('client.requests')); ?>" class="<?php echo e(request()->is('client/requests*') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span class="nav-text">My Requests</span>
        <span class="nav-tooltip">Track your requests</span>
    </a>

    
    <a href="<?php echo e(route('client.notifications')); ?>" class="<?php echo e(request()->is('client/notifications*') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span class="nav-text">Notifications</span>
        <span class="nav-tooltip">View your notifications</span>
    </a>

    <a href="<?php echo e(route('client.account')); ?>" class="<?php echo e(request()->routeIs('client.account') && (!request('tab') || request('tab') === 'settings') ? 'active' : ''); ?> nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.96l4.24 4.24M1.54 14.04l4.24-4.24M18.46 14.04l4.24-4.24"></path>
        </svg>
        <span class="nav-text">Account Settings</span>
        <span class="nav-tooltip">Configure account preferences</span>
    </a>
<?php endif; ?>
<?php /**PATH C:\wamp64\www\inventory-system\resources\views/client/sidebar.blade.php ENDPATH**/ ?>