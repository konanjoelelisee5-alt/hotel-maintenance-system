<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->action . '%'))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->latest()
            ->paginate(30);

        $users = \App\Models\User::orderBy('name')->get();

        return view('activity-logs.index', compact('logs', 'users'));
    }
}