<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceService
{
    public function clockIn(Employee $employee, ?float $lat = null, ?float $lng = null, ?string $photoBase64 = null, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($employee, $lat, $lng, $photoBase64, $notes) {
            // Prevent double clock-in (open session exists today)
            $existing = Attendance::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->whereDate('clock_in_at', now()->toDateString())
                ->first();
            if ($existing) {
                abort(422, 'Sudah ada sesi clock-in yang belum ditutup.');
            }

            $this->ensureInOfficeRadiusOrAbort($lat, $lng);

            $photoPath = $this->storePhotoIfProvided($photoBase64, 'clockin');

            $att = Attendance::create([
                'employee_id' => $employee->id,
                'clock_in_at' => now()->toDateTimeString(),
                'clock_in_lat' => $lat,
                'clock_in_lng' => $lng,
                'clock_in_photo_path' => $photoPath,
                'notes' => $notes,
            ]);

            Log::info('Employee clock-in', ['employee_id' => $employee->id, 'attendance_id' => $att->id]);

            return $att;
        });
    }

    public function clockOut(Employee $employee, ?float $lat = null, ?float $lng = null, ?string $photoBase64 = null, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($employee, $lat, $lng, $photoBase64, $notes) {
            $att = Attendance::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->latest('clock_in_at')
                ->first();

            if (!$att) {
                abort(422, 'Tidak ada sesi clock-in yang aktif.');
            }

            $this->ensureInOfficeRadiusOrAbort($lat, $lng);

            $photoPath = $this->storePhotoIfProvided($photoBase64, 'clockout');

            $att->clock_out_at = now()->toDateTimeString();
            $att->clock_out_lat = $lat;
            $att->clock_out_lng = $lng;
            $att->clock_out_photo_path = $photoPath;
            $att->notes = $notes;

            $att->work_minutes = $att->clock_in_at->diffInMinutes(now());
            $att->save();

            Log::info('Employee clock-out', ['employee_id' => $employee->id, 'attendance_id' => $att->id, 'minutes' => $att->work_minutes]);

            return $att;
        });
    }

    private function ensureInOfficeRadiusOrAbort(?float $lat, ?float $lng): void
    {
        $officeLat = config('app.office_lat', env('OFFICE_LAT'));
        $officeLng = config('app.office_lng', env('OFFICE_LNG'));
        $radiusM = (float) (config('app.office_radius_m', env('OFFICE_RADIUS_M', 0)));
        if (!$radiusM) {
            return; // no geofence
        }
        if ($lat === null || $lng === null) {
            abort(422, 'Lokasi wajib disertakan untuk absensi.');
        }
        $distance = $this->haversineDistanceMeters((float)$officeLat, (float)$officeLng, (float)$lat, (float)$lng);
        if ($distance > $radiusM) {
            abort(422, 'Di luar radius kantor.');
        }
    }

    private function storePhotoIfProvided(?string $photoBase64, string $kind): ?string
    {
        $required = filter_var(env('ATTENDANCE_PHOTO_REQUIRED', false), FILTER_VALIDATE_BOOLEAN);
        if ($required && empty($photoBase64)) {
            abort(422, 'Foto absensi wajib.');
        }
        if (empty($photoBase64)) {
            return null;
        }
        $data = base64_decode($photoBase64, true);
        if ($data === false) {
            abort(422, 'Format foto tidak valid.');
        }
        $dir = 'public/attendances/'.now()->format('Y/m/d');
        $filename = $kind.'-'.Str::uuid().'.jpg';
        Storage::put($dir.'/'.$filename, $data);
        return 'storage/attendances/'.now()->format('Y/m/d').'/'.$filename;
    }

    private function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}

