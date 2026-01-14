<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Show the notifications center.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all'); // all, unread, read
        
        $query = $user->notifications();
        
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }
        
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($notification) => $this->formatNotification($notification));
        
        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->readNotifications()->count(),
        ];
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'stats' => $stats,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);
        
        $notification->markAsRead();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Notification marked as read.');
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'All notifications marked as read.');
    }
    
    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);
        
        $notification->delete();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Notification deleted.');
    }
    
    /**
     * Delete all read notifications.
     */
    public function destroyRead(Request $request)
    {
        $request->user()
            ->readNotifications()
            ->delete();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'All read notifications deleted.');
    }
    
    /**
     * Get recent notifications (for header dropdown).
     */
    public function recent(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->take(10)
            ->get()
            ->map(fn($n) => $this->formatNotification($n));
        
        $unreadCount = $request->user()->unreadNotifications()->count();
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
    
    /**
     * Format a notification for frontend.
     */
    private function formatNotification(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        
        return [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'title' => $data['title'] ?? $this->getTitleFromType($notification->type),
            'message' => $data['message'] ?? $data['body'] ?? '',
            'icon' => $data['icon'] ?? $this->getIconFromType($notification->type),
            'color' => $data['color'] ?? $this->getColorFromType($notification->type),
            'action_url' => $data['action_url'] ?? $data['url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toIso8601String(),
            'created_at_human' => $notification->created_at->diffForHumans(),
        ];
    }
    
    /**
     * Get default title based on notification type.
     */
    private function getTitleFromType(string $type): string
    {
        $titles = [
            'AdminAlert' => 'Admin Alert',
            'DepositReceived' => 'Deposit Received',
            'GeneralAnnouncement' => 'Announcement',
            'KycStatusUpdated' => 'KYC Status Update',
            'TransferCompleted' => 'Transfer',
            'WithdrawalProcessed' => 'Withdrawal Update',
            'VoucherRedeemed' => 'Voucher Redeemed',
        ];
        
        $className = class_basename($type);
        return $titles[$className] ?? 'Notification';
    }
    
    /**
     * Get default icon based on notification type.
     */
    private function getIconFromType(string $type): string
    {
        $icons = [
            'AdminAlert' => 'pi pi-shield',
            'DepositReceived' => 'pi pi-download',
            'GeneralAnnouncement' => 'pi pi-megaphone',
            'KycStatusUpdated' => 'pi pi-id-card',
            'TransferCompleted' => 'pi pi-arrows-h',
            'WithdrawalProcessed' => 'pi pi-upload',
            'VoucherRedeemed' => 'pi pi-ticket',
        ];
        
        $className = class_basename($type);
        return $icons[$className] ?? 'pi pi-bell';
    }
    
    /**
     * Get default color based on notification type.
     */
    private function getColorFromType(string $type): string
    {
        $colors = [
            'AdminAlert' => 'red',
            'DepositReceived' => 'green',
            'GeneralAnnouncement' => 'blue',
            'KycStatusUpdated' => 'yellow',
            'TransferCompleted' => 'blue',
            'WithdrawalProcessed' => 'orange',
            'VoucherRedeemed' => 'green',
        ];
        
        $className = class_basename($type);
        return $colors[$className] ?? 'gray';
    }
}
