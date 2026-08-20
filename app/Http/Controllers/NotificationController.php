<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Liste complète des notifications de l'utilisateur connecté.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Marque une notification comme lue et redirige vers l'OT concerné.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->route('work-orders.show', $notification->data['work_order_id']);
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}