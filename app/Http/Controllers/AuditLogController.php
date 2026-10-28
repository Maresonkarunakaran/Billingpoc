<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::where('business_id', Auth::user()->business_id)
            ->with(['user', 'branch'])
            ->latest('id')
            ->paginate(30);

        return view('audit.index', compact('logs'));
    }
}
