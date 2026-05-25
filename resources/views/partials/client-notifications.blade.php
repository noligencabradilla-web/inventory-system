@php
    // Only compute and render notifications for client users
    $isClient = auth()->check() && auth()->user()->role === 'client';
    if($isClient) {
        // Get notification counts (simplified for now)
        $pendingRequests = 0;
        $approvedRequests = 0;
        $readyToReceive = 0;
        $rejectedRequests = 0;
        $lowInventory = 0;
        $memberActivity = 0;
        
        $pendingRequests = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('client_id', auth()->id())->where('status', 'pending')->count() : 0;
        $approvedRequests = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('client_id', auth()->id())->where('status', 'approved')->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(7))->count() : 0;
        $readyToReceive = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('client_id', auth()->id())->where('status', 'ready_to_receive')->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(7))->count() : 0;
        $rejectedRequests = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('client_id', auth()->id())->where('status', 'cancelled')->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(7))->count() : 0;
        
        // Check for low inventory
        $inventoryItems = class_exists(\App\Models\StockRequestItem::class) ? \App\Models\StockRequestItem::with(['stock'])
            ->whereHas('request', function($query) {
                $query->where('client_id', auth()->id())
                      ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('approved_qty', '>', 0)
            ->get() : collect();
        
        foreach ($inventoryItems as $item) {
            $availableQty = max(0, ($item->approved_qty ?? 0) - ($item->distributed_qty ?? 0));
            if ($availableQty > 0 && $availableQty <= 5) {
                $lowInventory++;
            }
        }
        
        // Recent member activity (only count unviewed)
        $memberActivity = 0;
        if (class_exists(\App\Models\ClientMember::class)) {
            $allMembers = \App\Models\ClientMember::where('client_id', auth()->id())
                ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(3))
                ->get();
            
            $viewedKey = 'client_viewed_member_activity_' . auth()->id();
            $currentViewed = session($viewedKey, []);
            
            foreach ($allMembers as $member) {
                $notificationId = 'new_member_' . $member->id;
                if (!in_array($notificationId, $currentViewed)) {
                    $memberActivity++;
                }
            }
        }
        
        $totalPending = $pendingRequests + $approvedRequests + $readyToReceive + $rejectedRequests + $lowInventory;
    }
@endphp

@if($isClient && request()->is('client*') && $totalPending > 0)
<style>
.client-notif{ display:flex; align-items:center; gap:8px; position:relative; }
.client-notif button{ background:transparent;border:none;cursor:pointer;font-size:20px;padding:8px 10px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); position:relative; }
.client-notif button:hover{ background:rgba(59,130,246,0.1); transform: scale(1.05); }
.client-notif button:active{ transform: scale(0.95); }
.client-notif .icon{ width:20px; height:20px; display:inline-block; color:#65676b; transition: color 0.3s ease; }
.client-notif button:hover .icon{ color:#1877f2; }
.client-notif .badge{ position:absolute; top:-4px; right:-4px; background:#e41e3f;color:#fff;padding:3px 6px;border-radius:999px;font-weight:600;font-size:10px; min-width:18px; text-align:center; box-shadow:0 2px 4px rgba(228,30,63,0.3); animation: pulse 2s infinite; }
@keyframes pulse{ 0%{ transform: scale(1); } 50%{ transform: scale(1.1); } 100%{ transform: scale(1); } }

.client-notif .dropdown{ display:none; position:absolute; right:0; top:calc(100% + 12px); width:360px; background:#ffffff; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08); z-index:9999; max-height:480px; overflow:hidden; border:1px solid rgba(0,0,0,0.08); }
.client-notif .dropdown.open{ display:block; animation: slideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes slideDown{ from{ opacity:0; transform: translateY(-10px); } to{ opacity:1; transform: translateY(0); } }

.client-notif .dropdown-header{ padding:16px 16px 12px 16px; border-bottom:1px solid #e4e6eb; background:#f8f9fa; border-radius:12px 12px 0 0; }
.client-notif .dropdown-header h3{ margin:0; font-size:18px; font-weight:700; color:#1c1e21; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.client-notif .dropdown-header .subtitle{ font-size:13px; color:#65676b; margin-top:2px; }

.client-notif .notifications-list{ max-height:320px; overflow-y:auto; }
.client-notif .notifications-list::-webkit-scrollbar{ width:6px; }
.client-notif .notifications-list::-webkit-scrollbar-track{ background:#f1f3f4; }
.client-notif .notifications-list::-webkit-scrollbar-thumb{ background:#dadde1; border-radius:3px; }
.client-notif .notifications-list::-webkit-scrollbar-thumb:hover{ background:#b0b3b8; }

.client-notif .notification-item{ display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-bottom:1px solid #f0f2f5; text-decoration:none; color:#1c1e21; transition: all 0.2s ease; cursor:pointer; position:relative; }
.client-notif .notification-item:hover{ background:#f8f9fa; }
.client-notif .notification-item:active{ background:#e4e6eb; }
.client-notif .notification-item.unread::before{ content:''; position:absolute; left:4px; top:20px; width:8px; height:8px; background:#1877f2; border-radius:50%; }

.client-notif .notification-avatar{ width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #1877f2, #42b883); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.client-notif .notification-avatar svg{ width:20px; height:20px; color:#fff; }
.client-notif .notification-avatar.pending{ background:linear-gradient(135deg, #ff9800, #f57c00); }
.client-notif .notification-avatar.approved{ background:linear-gradient(135deg, #4caf50, #388e3c); }
.client-notif .notification-avatar.ready{ background:linear-gradient(135deg, #2196f3, #1976d2); }
.client-notif .notification-avatar.rejected{ background:linear-gradient(135deg, #f44336, #d32f2f); }
.client-notif .notification-avatar.inventory{ background:linear-gradient(135deg, #ff9800, #f57c00); }
.client-notif .notification-avatar.member{ background:linear-gradient(135deg, #2196f3, #1976d2); }

.client-notif .notification-content{ flex:1; min-width:0; }
.client-notif .notification-title{ font-size:14px; font-weight:600; color:#1c1e21; margin-bottom:2px; line-height:1.3; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.client-notif .notification-message{ font-size:13px; color:#65676b; line-height:1.4; margin-bottom:4px; }
.client-notif .notification-time{ font-size:12px; color:#8a8d91; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

.client-notif .notification-count{ display:flex; align-items:center; justify-content:center; min-width:24px; height:24px; background:#e4e6eb; border-radius:12px; font-size:12px; font-weight:600; color:#1c1e21; flex-shrink:0; }

.client-notif .dropdown-footer{ padding:12px 16px; border-top:1px solid #e4e6eb; background:#f8f9fa; }
.client-notif .dropdown-footer a{ display:block; text-align:center; padding:8px; background:#e4e6eb; border-radius:8px; color:#1c1e21; text-decoration:none; font-size:14px; font-weight:600; transition: all 0.2s ease; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.client-notif .dropdown-footer a:hover{ background:#d4d6db; transform: translateY(-1px); }

.client-notif .empty-state{ padding:40px 20px; text-align:center; color:#65676b; }
.client-notif .empty-state svg{ width:48px; height:48px; margin-bottom:12px; opacity:0.3; }
.client-notif .empty-state p{ font-size:14px; margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
</style>

<div class="client-notif" aria-live="polite">
    <button id="clientNotifBtn" title="Notifications" aria-haspopup="true" aria-expanded="false">
        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 17H9a3 3 0 006 0z" fill="currentColor" opacity="0.9"/>
            <path d="M18 8a6 6 0 10-12 0v4l-2 2v1h16v-1l-2-2V8z" stroke="currentColor" stroke-width="0" fill="currentColor" />
        </svg>
        <span id="client-notification-badge" class="badge" style="display:{{ $totalPending ? 'inline-block' : 'none' }}">{{ $totalPending }}</span>
    </button>

    <div id="clientNotifDropdown" class="dropdown" role="menu" aria-hidden="true">
        <div class="dropdown-header">
            <h3>Notifications</h3>
            <div class="subtitle">{{ $totalPending > 0 ? $totalPending . ' new notifications' : 'No new notifications' }}</div>
        </div>

        <div class="notifications-list">
            @if($totalPending > 0)
                @if($pendingRequests > 0)
                    <a href="{{ route('client.requests') }}" class="notification-item {{ $pendingRequests > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar pending">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Pending Requests</div>
                            <div class="notification-message">{{ $pendingRequests }} request{{ $pendingRequests !== 1 ? 's' : '' }} pending review</div>
                            <div class="notification-time">View your requests</div>
                        </div>
                        <div class="notification-count">{{ $pendingRequests }}</div>
                    </a>
                @endif

                @if($approvedRequests > 0)
                    <a href="{{ route('client.requests') }}" class="notification-item {{ $approvedRequests > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar approved">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Approved Requests</div>
                            <div class="notification-message">{{ $approvedRequests }} request{{ $approvedRequests !== 1 ? 's' : '' }} approved recently</div>
                            <div class="notification-time">View approved items</div>
                        </div>
                        <div class="notification-count">{{ $approvedRequests }}</div>
                    </a>
                @endif

                @if($readyToReceive > 0)
                    <a href="{{ route('client.requests') }}" class="notification-item {{ $readyToReceive > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar ready">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 8H4v6h16V8zm-2 4H6v-2h12v2z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Items Ready to Receive</div>
                            <div class="notification-message">{{ $readyToReceive }} request{{ $readyToReceive !== 1 ? 's' : '' }} ready for pickup</div>
                            <div class="notification-time">Collect your items</div>
                        </div>
                        <div class="notification-count">{{ $readyToReceive }}</div>
                    </a>
                @endif

                @if($rejectedRequests > 0)
                    <a href="{{ route('client.requests') }}" class="notification-item {{ $rejectedRequests > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar rejected">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Rejected Requests</div>
                            <div class="notification-message">{{ $rejectedRequests }} request{{ $rejectedRequests !== 1 ? 's' : '' }} not approved</div>
                            <div class="notification-time">Review request details</div>
                        </div>
                        <div class="notification-count">{{ $rejectedRequests }}</div>
                    </a>
                @endif

                @if($lowInventory > 0)
                    <a href="{{ route('client.inventory') }}" class="notification-item {{ $lowInventory > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar inventory">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 18H7V6h10v12zm-5-14c-1.1 0-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4h-2c0-1.1-.9-2-2-2zm-2 8c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Low Inventory Alert</div>
                            <div class="notification-message">{{ $lowInventory }} item{{ $lowInventory !== 1 ? 's' : '' }} running low</div>
                            <div class="notification-time">Check inventory levels</div>
                        </div>
                        <div class="notification-count">{{ $lowInventory }}</div>
                    </a>
                @endif

                {{-- New Member Activity hidden temporarily --}}
                {{-- @if($memberActivity > 0)
                    <a href="{{ route('client.account', ['tab' => 'members']) }}" class="notification-item {{ $memberActivity > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar member">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">New Member Activity</div>
                            <div class="notification-message">{{ $memberActivity }} member{{ $memberActivity !== 1 ? 's' : '' }} added recently</div>
                            <div class="notification-time">Manage team members</div>
                        </div>
                        <div class="notification-count">{{ $memberActivity }}</div>
                    </a>
                @endif --}}
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    <p>No new notifications</p>
                </div>
            @endif
        </div>

        <div class="dropdown-footer">
            <a href="{{ route('client.notifications') }}">View All Notifications</a>
        </div>
    </div>
</div>

<script>
(function(){
    const btn = document.getElementById('clientNotifBtn');
    const dd = document.getElementById('clientNotifDropdown');
    if(!btn || !dd) return;
    function close(){ dd.classList.remove('open'); btn.setAttribute('aria-expanded','false'); dd.setAttribute('aria-hidden','true'); }
    function open(){ dd.classList.add('open'); btn.setAttribute('aria-expanded','true'); dd.setAttribute('aria-hidden','false'); }
    btn.addEventListener('click', function(e){ e.stopPropagation(); dd.classList.contains('open') ? close() : open(); });
    document.addEventListener('click', function(){ close(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
})();

(function(){
    const badge = document.getElementById('client-notification-badge');
    const url = '/client/notifications/counts';
    async function fetchCounts(){
        try{
            const res = await fetch(url, { credentials: 'same-origin' });
            if(!res.ok) return;
            const data = await res.json();
            // update badge
            if(badge){
                if(data.total && data.total > 0){ badge.style.display = 'inline-block'; badge.textContent = data.total; }
                else{ badge.style.display = 'none'; }
            }
        }catch(e){ console.error('Client notif counts error', e); }
    }

    // initial fetch and interval
    fetchCounts();
    setInterval(fetchCounts, 30000); // every 30s
})();
</script>
@endif
