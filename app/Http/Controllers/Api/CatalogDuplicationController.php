<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogDuplicationRequest;
use App\Jobs\ProcessDuplicationJob;
use App\Models\DuplicationJob;
use App\Models\Outlet;
use App\Services\CatalogDuplicationService;
use Illuminate\Http\Request;

class CatalogDuplicationController extends Controller
{
    public function __construct(private readonly CatalogDuplicationService $service)
    {
    }

    public function store(CatalogDuplicationRequest $request)
    {
        $user = $request->user();
        $sourceOutlet = Outlet::findOrFail($request->input('source_outlet_id'));
        $targetOutlet = Outlet::findOrFail($request->input('target_outlet_id'));

        $job = $this->service->createJob(
            $user,
            $sourceOutlet,
            $targetOutlet,
            $request->validated()['resources'],
            $request->validated()['options'] ?? []
        );

        ProcessDuplicationJob::dispatch($job);

        return response()->json([
            'message' => __('Proses duplikasi dimulai.'),
            'job_id' => $job->id,
        ], 202);
    }

    public function show(DuplicationJob $job)
    {
        $this->authorize('view', $job);

        $job->load('items');

        return response()->json([
            'data' => $job,
        ]);
    }

}
