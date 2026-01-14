<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportCategory;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the user's support tickets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Build query with filters
        $query = $user->supportTickets()->latest();
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        $tickets = $query->paginate(15)->through(fn($ticket) => [
            'id' => $ticket->id,
            'ticket_id' => $ticket->ticket_id,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'message' => $ticket->message,
            'created_at' => $ticket->created_at->toIso8601String(),
            'updated_at' => $ticket->updated_at->toIso8601String(),
        ]);
        
        // Get categories
        $categories = SupportCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
        
        // Default categories if none exist
        if (empty($categories)) {
            $categories = ['General', 'Technical', 'Billing', 'Account', 'Other'];
        }
        
        // Calculate stats
        $stats = [
            'total' => $user->supportTickets()->count(),
            'open' => $user->supportTickets()->whereIn('status', ['open', 'pending'])->count(),
            'in_progress' => $user->supportTickets()->where('status', 'in_progress')->count(),
            'resolved' => $user->supportTickets()->whereIn('status', ['resolved', 'closed'])->count(),
        ];
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('SupportTickets/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
            'stats' => $stats,
            'settings' => $settings,
            'filters' => [
                'status' => $request->status,
                'priority' => $request->priority,
            ],
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(Request $request)
    {
        $categories = SupportCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
        
        if (empty($categories)) {
            $categories = ['General', 'Technical', 'Billing', 'Account', 'Other'];
        }
        
        return Inertia::render('SupportTickets/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
            'message' => 'required|string|max:10000',
        ]);
        
        $ticket = $request->user()->supportTickets()->create([
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);
        
        // Notify admins
        $this->notifyAdmins(
            'New Support Ticket',
            "User {$request->user()->name} created ticket #{$ticket->ticket_id}: {$validated['subject']}",
            "/admin/support-tickets/{$ticket->id}"
        );
        
        return redirect()->route('support-tickets.show', $ticket->id)
            ->with('success', 'Support ticket created successfully. Our team will respond shortly.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, SupportTicket $supportTicket)
    {
        // Ensure user owns this ticket
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket.');
        }
        
        $ticket = [
            'id' => $supportTicket->id,
            'ticket_id' => $supportTicket->ticket_id,
            'subject' => $supportTicket->subject,
            'category' => $supportTicket->category,
            'priority' => $supportTicket->priority,
            'status' => $supportTicket->status,
            'message' => $supportTicket->message,
            'created_at' => $supportTicket->created_at->toIso8601String(),
            'updated_at' => $supportTicket->updated_at->toIso8601String(),
            'user' => [
                'id' => $supportTicket->user->id,
                'name' => $supportTicket->user->name,
            ],
        ];
        
        // Get ticket replies if the relationship exists
        $replies = [];
        if (method_exists($supportTicket, 'replies')) {
            $replies = $supportTicket->replies()
                ->with('user:id,name')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($reply) => [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'is_admin' => $reply->is_admin ?? false,
                    'created_at' => $reply->created_at->toIso8601String(),
                    'user' => $reply->user ? [
                        'id' => $reply->user->id,
                        'name' => $reply->user->name,
                    ] : null,
                ]);
        }
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('SupportTickets/Show', [
            'ticket' => $ticket,
            'replies' => $replies,
            'settings' => $settings,
        ]);
    }

    /**
     * Add a reply to the ticket.
     */
    public function reply(Request $request, SupportTicket $supportTicket)
    {
        // Ensure user owns this ticket
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket.');
        }
        
        // Ensure ticket is not closed
        if ($supportTicket->status === 'closed') {
            return back()->withErrors(['message' => 'Cannot reply to a closed ticket.']);
        }
        
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
        ]);
        
        // Create reply if the relationship exists
        if (method_exists($supportTicket, 'replies')) {
            $supportTicket->replies()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
                'is_admin' => false,
            ]);
        }
        
        // Update ticket status to pending (waiting for support response)
        $supportTicket->update([
            'status' => 'pending',
        ]);
        
        // Notify admins
        $this->notifyAdmins(
            'New Ticket Reply',
            "User {$request->user()->name} replied to ticket #{$supportTicket->ticket_id}",
            "/admin/support-tickets/{$supportTicket->id}"
        );
        
        return back()->with('success', 'Reply sent successfully.');
    }

    /**
     * Close the ticket.
     */
    public function close(Request $request, SupportTicket $supportTicket)
    {
        // Ensure user owns this ticket
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket.');
        }
        
        $supportTicket->update([
            'status' => 'closed',
        ]);
        
        return back()->with('success', 'Ticket closed successfully.');
    }

    /**
     * Reopen a closed ticket.
     */
    public function reopen(Request $request, SupportTicket $supportTicket)
    {
        // Ensure user owns this ticket
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket.');
        }
        
        $supportTicket->update([
            'status' => 'open',
        ]);
        
        // Notify admins
        $this->notifyAdmins(
            'Ticket Reopened',
            "User {$request->user()->name} reopened ticket #{$supportTicket->ticket_id}",
            "/admin/support-tickets/{$supportTicket->id}"
        );
        
        return back()->with('success', 'Ticket reopened successfully.');
    }

    /**
     * Delete the ticket.
     */
    public function destroy(Request $request, SupportTicket $supportTicket)
    {
        // Ensure user owns this ticket
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket.');
        }
        
        $supportTicket->delete();
        
        return redirect()->route('support-tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }

    /**
     * Notify admin users.
     */
    private function notifyAdmins(string $title, string $message, string $url): void
    {
        try {
            $admins = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new AdminAlert($title, $message, $url));
            }
        } catch (\Exception $e) {
            // Silently fail if notification fails
            \Log::warning('Failed to notify admins: ' . $e->getMessage());
        }
    }
}
