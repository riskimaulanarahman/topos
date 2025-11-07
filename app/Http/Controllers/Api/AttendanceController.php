<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceClockInRequest;
use App\Http\Requests\AttendanceClockOutRequest;
use App\Http\Requests\AttendanceReportRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service)
    {
    }

    // Employee self profile
    public function me(Request $request)
    {
        $emp = $request->user('employee');
        return response()->json(['data' => [
            'id' => $emp->id,
            'name' => $emp->name,
            'email' => $emp->email,
            'phone' => $emp->phone,
            'role' => $emp->role,
            'is_active' => $emp->is_active,
        ]]);
    }

    // Employee self attendances
    public function myAttendances(Request $request)
    {
        $emp = $request->user('employee');
        $q = Attendance::where('employee_id', $emp->id)->orderBy('clock_in_at', 'desc');
        if ($request->filled('date_from')) {
            $q->whereDate('clock_in_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('clock_in_at', '<=', $request->input('date_to'));
        }
        return response()->json(['data' => $q->paginate($request->integer('page_size', 20))]);
    }

    public function clockIn(AttendanceClockInRequest $request)
    {
        $emp = $request->user('employee');
        $att = $this->service->clockIn($emp, $request->float('lat'), $request->float('lng'), $request->input('photo_base64'), $request->input('notes'));
        return response()->json(['message' => 'Clock-in berhasil', 'data' => $att]);
    }

    public function clockOut(AttendanceClockOutRequest $request)
    {
        $emp = $request->user('employee');
        $att = $this->service->clockOut($emp, $request->float('lat'), $request->float('lng'), $request->input('photo_base64'), $request->input('notes'));
        return response()->json(['message' => 'Clock-out berhasil', 'data' => $att]);
    }

    // Admin report
    public function report(AttendanceReportRequest $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('employees.manage');

        $q = Attendance::with('employee')
            ->when($request->filled('employee_id'), fn($qr) => $qr->where('employee_id', $request->integer('employee_id')))
            ->whereBetween('clock_in_at', [
                $request->input('date_from').' 00:00:00',
                $request->input('date_to').' 23:59:59',
            ])
            ->orderBy('employee_id')
            ->orderBy('clock_in_at');

        $format = $request->input('format', 'json');
        if ($format === 'csv') {
            $filename = 'attendance_report_'.now()->format('Ymd_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];
            $callback = function () use ($q) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Employee','Date','Clock In','Clock Out','Minutes']);
                $q->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->employee->name,
                            $row->clock_in_at->toDateString(),
                            optional($row->clock_in_at)->toDateTimeString(),
                            optional($row->clock_out_at)->toDateTimeString(),
                            $row->work_minutes,
                        ]);
                    }
                });
                fclose($handle);
            };
            return new StreamedResponse($callback, 200, $headers);
        }

        $rows = $q->paginate($request->integer('page_size', 50));
        return response()->json(['data' => $rows]);
    }
}

