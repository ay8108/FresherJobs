<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = JobPost::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('ai_role', 'like', "%{$request->role}%");
            })
            ->when($request->filled('city'), function ($query) use ($request) {
                $query->where('ai_city', $request->city);
            })
            ->when($request->filled('experience'), function ($query) use ($request) {
                $query->where('experience_level', $request->experience);
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function show(int $id)
    {
        $job = JobPost::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }
}
