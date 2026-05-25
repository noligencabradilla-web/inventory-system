@php
    // Only compute and render notifications for admin users
    $isAdmin = auth()->check() && (auth()->user()->role ?? '') === 'admin';
    if($isAdmin) {
        $pendingRequests = class_exists(\App\Models\StockRequest::class) ? \App\Models\StockRequest::where('status','pending')->count() : 0;
        $pendingPR = class_exists(\App\Models\PasswordResetRequest::class) ? \App\Models\PasswordResetRequest::where('status','pending')->count() : 0;
        $lowThreshold = 49;
        $lowStock = class_exists(\App\Models\Stock::class) ? \App\Models\Stock::where('stock','>',0)->where('stock','<=',$lowThreshold)->count() : 0;
        $outStock = class_exists(\App\Models\Stock::class) ? \App\Models\Stock::where('stock','<=',0)->count() : 0;
        
        // New notification types
        $urgentOutbounds = class_exists(\App\Models\Outbound::class) ? \App\Models\Outbound::where('is_urgent_outbound', true)->where('approval', 'pending')->count() : 0;
        
        $expiringItems = 0;
        if (class_exists(\App\Models\Stock::class) && \Illuminate\Support\Facades\Schema::hasColumn('stocks', 'expiry_date')) {
            $sevenDaysFromNow = \Carbon\Carbon::now()->addDays(7);
            $expiringItems = \App\Models\Stock::where('expiry_date', '<=', $sevenDaysFromNow)
                ->where('expiry_date', '>', \Carbon\Carbon::now())
                ->where('stock', '>', 0)
                ->count();
        }
        
        $recentClients = class_exists(\App\Models\User::class) ? \App\Models\User::where('role', 'client')
            ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(24))
            ->count() : 0;
        
        $failedJobs = 0;
        try {
            $failedJobs = \DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // Table might not exist, ignore
        }
        
        $totalPending = $pendingRequests + $pendingPR + $lowStock + $outStock + $urgentOutbounds + $expiringItems + $failedJobs;
    }
@endphp

@if($isAdmin && request()->is('admin*'))
<style>
.top-notif{ display:flex; align-items:center; gap:8px; position:relative; }
.top-notif button{ background:transparent;border:none;cursor:pointer;font-size:20px;padding:8px 10px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); position:relative; }
.top-notif button:hover{ background:rgba(59,130,246,0.1); transform: scale(1.05); }
.top-notif button:active{ transform: scale(0.95); }
.top-notif .icon{ width:20px; height:20px; display:inline-block; color:#65676b; transition: color 0.3s ease; }
.top-notif button:hover .icon{ color:#1877f2; }
.top-notif .badge{ position:absolute; top:-4px; right:-4px; background:#e41e3f;color:#fff;padding:3px 6px;border-radius:999px;font-weight:600;font-size:10px; min-width:18px; text-align:center; box-shadow:0 2px 4px rgba(228,30,63,0.3); animation: pulse 2s infinite; }
@keyframes pulse{ 0%{ transform: scale(1); } 50%{ transform: scale(1.1); } 100%{ transform: scale(1); } }

.top-notif .dropdown{ display:none; position:absolute; right:0; top:calc(100% + 12px); width:360px; background:#ffffff; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08); z-index:9999; max-height:480px; overflow:hidden; border:1px solid rgba(0,0,0,0.08); }
.top-notif .dropdown.open{ display:block; animation: slideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes slideDown{ from{ opacity:0; transform: translateY(-10px); } to{ opacity:1; transform: translateY(0); } }

.top-notif .dropdown-header{ padding:16px 16px 12px 16px; border-bottom:1px solid #e4e6eb; background:#f8f9fa; border-radius:12px 12px 0 0; }
.top-notif .dropdown-header h3{ margin:0; font-size:18px; font-weight:700; color:#1c1e21; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.top-notif .dropdown-header .subtitle{ font-size:13px; color:#65676b; margin-top:2px; }

.top-notif .notifications-list{ max-height:320px; overflow-y:auto; }
.top-notif .notifications-list::-webkit-scrollbar{ width:6px; }
.top-notif .notifications-list::-webkit-scrollbar-track{ background:#f1f3f4; }
.top-notif .notifications-list::-webkit-scrollbar-thumb{ background:#dadde1; border-radius:3px; }
.top-notif .notifications-list::-webkit-scrollbar-thumb:hover{ background:#b0b3b8; }

.top-notif .notification-item{ display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-bottom:1px solid #f0f2f5; text-decoration:none; color:#1c1e21; transition: all 0.2s ease; cursor:pointer; position:relative; }
.top-notif .notification-item:hover{ background:#f8f9fa; }
.top-notif .notification-item:active{ background:#e4e6eb; }
.top-notif .notification-item.unread::before{ content:''; position:absolute; left:4px; top:20px; width:8px; height:8px; background:#1877f2; border-radius:50%; }

.top-notif .notification-avatar{ width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #1877f2, #42b883); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.top-notif .notification-avatar svg{ width:20px; height:20px; color:#fff; }
.top-notif .notification-avatar.request{ background:linear-gradient(135deg, #ff6b6b, #ee5a24); }
.top-notif .notification-avatar.password{ background:linear-gradient(135deg, #4834d4, #686de0); }
.top-notif .notification-avatar.stock{ background:linear-gradient(135deg, #f9ca24, #f0932b); }
.top-notif .notification-avatar.urgent{ background:linear-gradient(135deg, #e74c3c, #c0392b); }
.top-notif .notification-avatar.expiring{ background:linear-gradient(135deg, #f39c12, #e67e22); }
.top-notif .notification-avatar.client{ background:linear-gradient(135deg, #27ae60, #2ecc71); }
.top-notif .notification-avatar.system{ background:linear-gradient(135deg, #8e44ad, #9b59b6); }

.top-notif .notification-content{ flex:1; min-width:0; }
.top-notif .notification-title{ font-size:14px; font-weight:600; color:#1c1e21; margin-bottom:2px; line-height:1.3; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.top-notif .notification-message{ font-size:13px; color:#65676b; line-height:1.4; margin-bottom:4px; }
.top-notif .notification-time{ font-size:12px; color:#8a8d91; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

.top-notif .notification-count{ display:flex; align-items:center; justify-content:center; min-width:24px; height:24px; background:#e4e6eb; border-radius:12px; font-size:12px; font-weight:600; color:#1c1e21; flex-shrink:0; }

.top-notif .dropdown-footer{ padding:12px 16px; border-top:1px solid #e4e6eb; background:#f8f9fa; }
.top-notif .dropdown-footer a{ display:block; text-align:center; padding:8px; background:#e4e6eb; border-radius:8px; color:#1c1e21; text-decoration:none; font-size:14px; font-weight:600; transition: all 0.2s ease; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.top-notif .dropdown-footer a:hover{ background:#d4d6db; transform: translateY(-1px); }

.top-notif .empty-state{ padding:40px 20px; text-align:center; color:#65676b; }
.top-notif .empty-state svg{ width:48px; height:48px; margin-bottom:12px; opacity:0.3; }
.top-notif .empty-state p{ font-size:14px; margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
</style>

<div class="top-notif" aria-live="polite">
    <button id="topNotifBtn" title="Notifications" aria-haspopup="true" aria-expanded="false">
        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 17H9a3 3 0 006 0z" fill="currentColor" opacity="0.9"/>
            <path d="M18 8a6 6 0 10-12 0v4l-2 2v1h16v-1l-2-2V8z" stroke="currentColor" stroke-width="0" fill="currentColor" />
        </svg>
        <span id="notif_badge" class="badge" style="display:{{ $totalPending ? 'inline-block' : 'none' }}">{{ $totalPending }}</span>
    </button>

    <div id="topNotifDropdown" class="dropdown" role="menu" aria-hidden="true">
        <div class="dropdown-header">
            <h3>Notifications</h3>
            <div class="subtitle">{{ $totalPending > 0 ? $totalPending . ' new notifications' : 'No new notifications' }}</div>
        </div>

        <div class="notifications-list">
            @if($totalPending > 0)
                @if($pendingRequests > 0)
                    <a href="/admin/requests" class="notification-item {{ $pendingRequests > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar request">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Pending Stock Requests</div>
                            <div class="notification-message">You have {{ $pendingRequests }} pending stock request{{ $pendingRequests !== 1 ? 's' : '' }} that need your attention</div>
                            <div class="notification-time">View all requests</div>
                        </div>
                        <div class="notification-count">{{ $pendingRequests }}</div>
                    </a>
                @endif

                @if($pendingPR > 0)
                    <a href="/admin/password-reset" class="notification-item {{ $pendingPR > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar password">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Password Reset Requests</div>
                            <div class="notification-message">{{ $pendingPR }} user{{ $pendingPR !== 1 ? 's' : '' }} requesting password reset</div>
                            <div class="notification-time">Review password requests</div>
                        </div>
                        <div class="notification-count">{{ $pendingPR }}</div>
                    </a>
                @endif

                @if($lowStock > 0)
                    <a href="/admin/stocks" class="notification-item {{ $lowStock > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar stock">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 18H7V6h10v12zm-5-14c-1.1 0-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4h-2c0-1.1-.9-2-2-2zm-2 8c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Low Stock Alert</div>
                            <div class="notification-message">{{ $lowStock }} item{{ $lowStock !== 1 ? 's' : '' }} running low on stock (≤5 units)</div>
                            <div class="notification-time">Check inventory levels</div>
                        </div>
                        <div class="notification-count">{{ $lowStock }}</div>
                    </a>
                @endif

                @if($outStock > 0)
                    <a href="/admin/stocks" class="notification-item {{ $outStock > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar stock">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Out of Stock Alert</div>
                            <div class="notification-message">{{ $outStock }} item{{ $outStock !== 1 ? 's' : '' }} completely out of stock</div>
                            <div class="notification-time">Urgent: Restock needed</div>
                        </div>
                        <div class="notification-count">{{ $outStock }}</div>
                    </a>
                @endif

                @if($urgentOutbounds > 0)
                    <a href="/admin/summary?type=urgent" class="notification-item {{ $urgentOutbounds > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar urgent">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Urgent Outbound Requests</div>
                            <div class="notification-message">{{ $urgentOutbounds }} urgent request{{ $urgentOutbounds !== 1 ? 's' : '' }} awaiting approval</div>
                            <div class="notification-time">Review urgent requests</div>
                        </div>
                        <div class="notification-count">{{ $urgentOutbounds }}</div>
                    </a>
                @endif

                @if($expiringItems > 0)
                    <a href="/admin/stocks" class="notification-item {{ $expiringItems > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar expiring">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Expiring Items Alert</div>
                            <div class="notification-message">{{ $expiringItems }} item{{ $expiringItems !== 1 ? 's' : '' }} expiring within 7 days</div>
                            <div class="notification-time">Check expiry dates</div>
                        </div>
                        <div class="notification-count">{{ $expiringItems }}</div>
                    </a>
                @endif

                {{-- New Client Registrations hidden temporarily --}}
                {{-- @if($recentClients > 0)
                    <a href="/admin/clients" class="notification-item {{ $recentClients > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar client">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">New Client Registrations</div>
                            <div class="notification-message">{{ $recentClients }} new client{{ $recentClients !== 1 ? 's' : '' }} registered in last 24 hours</div>
                            <div class="notification-time">Welcome new clients</div>
                        </div>
                        <div class="notification-count">{{ $recentClients }}</div>
                    </a>
                @endif --}}

                @if($failedJobs > 0)
                    <a href="/admin/system-health" class="notification-item {{ $failedJobs > 0 ? 'unread' : '' }}">
                        <div class="notification-avatar system">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">System Health Alert</div>
                            <div class="notification-message">{{ $failedJobs }} failed job{{ $failedJobs !== 1 ? 's' : '' }} detected</div>
                            <div class="notification-time">Check system status</div>
                        </div>
                        <div class="notification-count">{{ $failedJobs }}</div>
                    </a>
                @endif
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
            <a href="{{ route('admin.notifications') }}">View All Notifications</a>
        </div>
    </div>
</div>

<script>
(function(){
    const btn = document.getElementById('topNotifBtn');
    const dd = document.getElementById('topNotifDropdown');
    if(!btn || !dd) return;
    function close(){ dd.classList.remove('open'); btn.setAttribute('aria-expanded','false'); dd.setAttribute('aria-hidden','true'); }
    function open(){ dd.classList.add('open'); btn.setAttribute('aria-expanded','true'); dd.setAttribute('aria-hidden','false'); }
    btn.addEventListener('click', function(e){ e.stopPropagation(); dd.classList.contains('open') ? close() : open(); });
    document.addEventListener('click', function(){ close(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
})();
</script>

<script>
(function(){
    const badge = document.getElementById('notif_badge');
    const url = '/admin/notifications/counts';
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
            // update items
            function setText(id, val){ const el = document.getElementById(id); if(el) el.textContent = val; }
            function pluralSuffix(id, val){ const el = document.getElementById(id); if(!el) return; el.textContent = (val !== 1) ? 's' : ''; }

            setText('notif_pendingRequests', data.pendingRequests ?? 0);
            pluralSuffix('notif_pendingRequests_s', data.pendingRequests ?? 0);
            setText('notif_pendingPR', data.pendingPasswordResets ?? 0);
            pluralSuffix('notif_pendingPR_s', data.pendingPasswordResets ?? 0);
            setText('notif_lowStock', data.lowStock ?? 0);
            document.getElementById('notif_lowStock_s') && (document.getElementById('notif_lowStock_s').textContent = (data.lowStock !== 1 ? ' items' : ' item'));
            setText('notif_outStock', data.outStock ?? 0);
            document.getElementById('notif_outStock_s') && (document.getElementById('notif_outStock_s').textContent = (data.outStock !== 1 ? ' items' : ' item'));
            setText('notif_urgentOutbounds', data.urgentOutbounds ?? 0);
            setText('notif_expiringItems', data.expiringItems ?? 0);
            setText('notif_recentClients', data.recentClients ?? 0);
            setText('notif_failedJobs', data.failedJobs ?? 0);

        }catch(e){ console.error('Notif counts error', e); }
    }

    // initial fetch and interval
    fetchCounts();
    setInterval(fetchCounts, 30000); // every 30s
})();
</script>
@endif