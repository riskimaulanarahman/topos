<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceWebController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::orderBy('name')->get();
        $q = Attendance::with('employee')->orderByDesc('clock_in_at');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('clock_in_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('clock_in_at', '<=', $request->input('date_to'));
        }
        $rows = $q->paginate(20);
        return view('pages.attendances.index', compact('rows','employees'));
    }

    public function exportCsv(Request $request)
    {
        $q = Attendance::with('employee')->orderBy('employee_id')->orderBy('clock_in_at');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('clock_in_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('clock_in_at', '<=', $request->input('date_to'));
        }
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
                        $row->employee?->name,
                        optional($row->clock_in_at)->toDateString(),
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
}

