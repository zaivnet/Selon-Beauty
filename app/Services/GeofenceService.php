<?php

namespace App\Services;

use App\Models\AttendanceLocation;
use App\Models\Outlet;

class GeofenceService
{
    /**
     * Calculate distance between two GPS points in meters using the Haversine formula.
     */
    public function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Determine if an employee's coordinates fall within the location's allowed radius.
     */
    public function isWithinRadius(float $employeeLat, float $employeeLon, object $location): bool
    {
        if (! $location->is_active) {
            return false;
        }

        $distance = $this->calculateDistanceMeters(
            $employeeLat,
            $employeeLon,
            (float) $location->latitude,
            (float) $location->longitude
        );

        return $distance <= (float) $location->radius_meters;
    }

    /**
     * Determine if GPS accuracy is within the allowed maximum limit for the location.
     */
    public function isAccuracyAcceptable(float $gpsAccuracyMeters, object $location): bool
    {
        return $gpsAccuracyMeters <= (float) $location->max_accuracy_meters;
    }

    /**
     * Perform full server-side geofence evaluation for an employee's check-in position.
     *
     * @return array{
     *     distance_meters: float,
     *     is_within_radius: bool,
     *     is_accuracy_valid: bool,
     *     is_valid: bool,
     *     error_message: string|null
     * }
     */
    public function evaluateGeofence(float $employeeLat, float $employeeLon, float $gpsAccuracyMeters, object $location): array
    {
        if (! $location->is_active) {
            return [
                'distance_meters' => 0.0,
                'is_within_radius' => false,
                'is_accuracy_valid' => false,
                'is_valid' => false,
                'error_message' => 'Lokasi absensi saat ini tidak aktif.',
            ];
        }

        $distance = $this->calculateDistanceMeters(
            $employeeLat,
            $employeeLon,
            (float) $location->latitude,
            (float) $location->longitude
        );

        $isWithinRadius = $distance <= (float) $location->radius_meters;
        $isAccuracyValid = $gpsAccuracyMeters <= (float) $location->max_accuracy_meters;

        $errorMessage = null;
        if (! $isAccuracyValid) {
            $errorMessage = "Akurasi GPS Anda ({$gpsAccuracyMeters}m) terlalu rendah. Maksimal yang diizinkan adalah {$location->max_accuracy_meters}m.";
        } elseif (! $isWithinRadius) {
            $errorMessage = "Posisi Anda ({$distance}m) di luar radius absensi toko ({$location->radius_meters}m).";
        }

        return [
            'distance_meters' => $distance,
            'is_within_radius' => $isWithinRadius,
            'is_accuracy_valid' => $isAccuracyValid,
            'is_valid' => $isWithinRadius && $isAccuracyValid,
            'error_message' => $errorMessage,
        ];
    }
}
