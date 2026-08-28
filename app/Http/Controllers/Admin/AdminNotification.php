<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Models\PasswordResetRequest;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminNotificationController extends Controller
{
    public function notifications()
    {
        $user = auth()->user();

        $notifications = $this->getAdminNotifications($user);

        return view('admin.notifications', compact('notifications'));
    }

    public function counts()
    {
        $pendingRequests = StockRequest::where('status', 'pending')->count();
        $pendingPasswordResets = PasswordResetRequest::where('status', 'pending')->count();

        $lowThreshold = 49;
        $lowStock = Stock::where('stock', '>', 0)
            ->where('stock', '<=', $lowThreshold)
            ->count();

        $outStock = Stock::where('stock', '<=', 0)->count();

        $urgentOutbounds = Outbound::where('is_urgent_outbound', true)
            ->where('approval', 'pending')
            ->count();

        $expiringItems = 0;

        if (Schema::hasColumn('stocks', 'expiry_date')) {
            $sevenDaysFromNow = Carbon::now()->addDays(7);

            $expiringItems = Stock::where('expiry_date', '<=', $sevenDaysFromNow)
                ->where('expiry_date', '>', Carbon::now())
                ->where('stock', '>', 0)
                ->count();
        }

        $recentClients = User::where('role', 'client')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();

        $failedJobs = 0;

        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            $failedJobs = 0;
        }

        $total = $pendingRequests
            + $pendingPasswordResets
            + $lowStock
            + $outStock
            + $urgentOutbounds
            + $expiringItems
            + $recentClients
            + $failedJobs;

        return response()->json([
            'pendingRequests' => $pendingRequests,
            'pendingPasswordResets' => $pendingPasswordResets,
            'lowStock' => $lowStock,
            'outStock' => $outStock,
            'urgentOutbounds' => $urgentOutbounds,
            'expiringItems' => $expiringItems,
            'recentClients' => $recentClients,
            'failedJobs' => $failedJobs,
            'total' => $total,
        ]);
    }

    private function getAdminNotifications($user)
    {
        $notifications = collect();

        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);

        $notifications = $notifications->merge($this->getPendingRequestNotifications($currentRead));
        $notifications = $notifications->merge($this->getPasswordResetNotifications($currentRead));
        $notifications = $notifications->merge($this->getStockAlertNotifications($currentRead));
        $notifications = $notifications->merge($this->getUrgentOutboundNotifications($currentRead));
        $notifications = $notifications->merge($this->getExpiringItemNotifications($currentRead));
        $notifications = $notifications->merge($this->getNewClientNotifications($currentRead));
        $notifications = $notifications->merge($this->getSystemHealthNotifications($currentRead));

        return $notifications->sortByDesc('created_at');
    }

    private function getPendingRequestNotifications($currentRead = [])
    {
        $notifications = collect();

        $pendingRequests = StockRequest::where('status', 'pending')
            ->with('client')
            ->get();

        foreach ($pendingRequests as $request) {
            $notificationId = 'pending_' . $request->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'pending_requests',
                'title' => 'Pending Stock Request',
                'message' => "Request #{$request->id} from " . ($request->client->name ?? 'Unknown') . " needs your review",
                'created_at' => $request->created_at,
                'read' => $isRead,
                'action_url' => '/admin/requests#request-' . $request->id,
                'icon' => 'clock',
                'color' => 'orange',
            ]);
        }

        return $notifications;
    }

    private function getPasswordResetNotifications($currentRead = [])
    {
        $notifications = collect();

        $pendingPasswordResets = PasswordResetRequest::where('status', 'pending')->get();

        foreach ($pendingPasswordResets as $reset) {
            $notificationId = 'password_' . $reset->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'password_resets',
                'title' => 'Password Reset Request',
                'message' => "User {$reset->email} is requesting password reset",
                'created_at' => $reset->created_at,
                'read' => $isRead,
                'action_url' => '/admin/password-reset',
                'icon' => 'lock',
                'color' => 'purple',
            ]);
        }

        return $notifications;
    }

    private function getStockAlertNotifications($currentRead = [])
    {
        $notifications = collect();

        $lowThreshold = 49;

        $lowStock = Stock::where('stock', '>', 0)
            ->where('stock', '<=', $lowThreshold)
            ->get();

        foreach ($lowStock as $stock) {
            $notificationId = 'low_' . $stock->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'low_stock',
                'title' => 'Low Stock Alert',
                'message' => "{$stock->description} is running low ({$stock->stock} units remaining)",
                'created_at' => $stock->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'alert-triangle',
                'color' => 'yellow',
            ]);
        }

        $outStock = Stock::where('stock', '<=', 0)->get();

        foreach ($outStock as $stock) {
            $notificationId = 'out_' . $stock->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'out_of_stock',
                'title' => 'Out of Stock Alert',
                'message' => "{$stock->description} is completely out of stock",
                'created_at' => $stock->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'x-circle',
                'color' => 'red',
            ]);
        }

        return $notifications;
    }

    private function getUrgentOutboundNotifications($currentRead = [])
    {
        $notifications = collect();

        $urgentOutbounds = Outbound::where('is_urgent_outbound', true)
            ->where('approval', 'pending')
            ->with(['stock', 'urgentRecipient'])
            ->get();

        foreach ($urgentOutbounds as $urgent) {
            $notificationId = 'urgent_' . $urgent->id;
            $isRead = in_array($notificationId, $currentRead);

            $stockDescription = $urgent->stock->description ?? 'Stock item';
            $recipientName = $urgent->recipient_name ?? 'recipient';

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'urgent_outbounds',
                'title' => 'Urgent Outbound Request',
                'message' => "{$stockDescription} for {$recipientName} needs immediate approval",
                'created_at' => $urgent->created_at,
                'read' => $isRead,
                'action_url' => '/admin/summary?type=urgent',
                'icon' => 'alert-triangle',
                'color' => 'red',
            ]);
        }

        return $notifications;
    }

    private function getExpiringItemNotifications($currentRead = [])
    {
        $notifications = collect();

        $expiringItems = collect();

        if (Schema::hasColumn('stocks', 'expiry_date')) {
            $sevenDaysFromNow = Carbon::now()->addDays(7);

            $expiringItems = Stock::where('expiry_date', '<=', $sevenDaysFromNow)
                ->where('expiry_date', '>', Carbon::now())
                ->where('stock', '>', 0)
                ->get();
        }

        foreach ($expiringItems as $item) {
            $daysLeft = Carbon::parse($item->expiry_date)->diffInDays(now());
            $notificationId = 'expiring_' . $item->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'expiring_items',
                'title' => 'Expiring Item Alert',
                'message' => "{$item->description} expires in {$daysLeft} days",
                'created_at' => $item->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'clock',
                'color' => $daysLeft <= 3 ? 'red' : 'orange',
            ]);
        }

        return $notifications;
    }

    private function getNewClientNotifications($currentRead = [])
    {
        $notifications = collect();

        $recentClients = User::where('role', 'client')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->get();

        foreach ($recentClients as $client) {
            $notificationId = 'client_' . $client->id;
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'new_clients',
                'title' => 'New Client Registration',
                'message' => "{$client->name} has registered as a new client",
                'created_at' => $client->created_at,
                'read' => $isRead,
                'action_url' => '/admin/clients',
                'icon' => 'user-plus',
                'color' => 'green',
            ]);
        }

        return $notifications;
    }

    private function getSystemHealthNotifications($currentRead = [])
    {
        $notifications = collect();

        $failedJobs = 0;

        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            $failedJobs = 0;
        }

        if ($failedJobs > 0) {
            $notificationId = 'system_health';
            $isRead = in_array($notificationId, $currentRead);

            $notifications->push((object) [
                'id' => $notificationId,
                'type' => 'system_health',
                'title' => 'System Health Alert',
                'message' => "{$failedJobs} failed job" . ($failedJobs !== 1 ? 's' : '') . " detected",
                'created_at' => now(),
                'read' => $isRead,
                'action_url' => '/admin/system-health',
                'icon' => 'alert-triangle',
                'color' => 'red',
            ]);
        }

        return $notifications;
    }

    public function markNotificationAsRead($id)
    {
        $user = auth()->user();

        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);

        if (!in_array($id, $currentRead)) {
            $currentRead[] = $id;
            session([$readKey => $currentRead]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsAsRead()
    {
        $user = auth()->user();

        $notifications = $this->getAdminNotifications($user);
        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);

        foreach ($notifications as $notification) {
            if (!in_array($notification->id, $currentRead)) {
                $currentRead[] = $notification->id;
            }
        }

        session([$readKey => $currentRead]);

        return response()->json(['success' => true]);
    }
}