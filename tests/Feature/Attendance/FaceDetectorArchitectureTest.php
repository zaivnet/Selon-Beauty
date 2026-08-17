<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;

class FaceDetectorArchitectureTest extends TestCase
{
    public function test_cross_browser_detector_uses_native_then_local_mediapipe_fallback(): void
    {
        $module = file_get_contents(resource_path('js/attendance-face-detector.js'));
        $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('1.0.0', $package['dependencies']['@mediapipe/tasks-vision']);
        $this->assertStringContainsString("'FaceDetector' in window", $module);
        $this->assertStringContainsString("import('@mediapipe/tasks-vision')", $module);
        $this->assertStringContainsString('FilesetResolver.forVisionTasks(wasmRoot)', $module);
        $this->assertStringContainsString("const MEDIAPIPE_WASM_PATH = '/vendor/mediapipe/tasks-vision-1.0.0/wasm';", $module);
        $this->assertStringContainsString("new URL(MEDIAPIPE_WASM_PATH, window.location.origin)", $module);
        $this->assertStringNotContainsString('import.meta.env.BASE_URL', $module);
        $this->assertStringContainsString('blaze_face_short_range.tflite?url', $module);
        $this->assertStringContainsString("delegate: 'CPU'", $module);
        $this->assertStringContainsString("this.backend = 'native'", $module);
        $this->assertStringContainsString("this.backend = 'mediapipe'", $module);
        $this->assertStringContainsString('vision_wasm_nosimd_internal.js', $module);
        $this->assertStringContainsString('vision_wasm_nosimd_internal.wasm', $module);
        $this->assertStringContainsString('detectForVideo(this.canvas, this.lastVideoTimestamp)', $module);
        $this->assertStringContainsString("setOptions({ runningMode: 'IMAGE' })", $module);
        foreach (['FD-IMPORT', 'FD-WASM', 'FD-CREATE', 'FD-INFERENCE'] as $code) {
            $this->assertStringContainsString($code, $module);
        }
        $this->assertStringContainsString("this.stage = 'READY'", $module);
        $this->assertStringContainsString('throw failure;', $module);
    }

    public function test_official_resolver_wasm_directory_contains_simd_and_non_simd_assets(): void
    {
        $directory = public_path('vendor/mediapipe/tasks-vision-1.0.0/wasm');

        foreach ([
            'vision_wasm_internal.js',
            'vision_wasm_internal.wasm',
            'vision_wasm_nosimd_internal.js',
            'vision_wasm_nosimd_internal.wasm',
        ] as $asset) {
            $this->assertFileExists($directory.DIRECTORY_SEPARATOR.$asset);
        }
    }

    public function test_model_is_local_versioned_and_has_expected_integrity(): void
    {
        $model = resource_path('models/blaze_face_short_range.tflite');

        $this->assertFileExists($model);
        $this->assertSame(229746, filesize($model));
        $this->assertSame(
            'b4578f35940bf5a1a655214a1cce5cab13eba73c1297cd78e1a04c2380b0152f',
            hash_file('sha256', $model),
        );
    }

    public function test_dashboard_blocks_capture_until_valid_face_and_revalidates_still_and_file(): void
    {
        $dashboard = file_get_contents(resource_path('views/employee/dashboard.blade.php'));

        foreach ([
            'MEMUAT DETEKSI WAJAH...',
            'WAJAH BELUM TERDETEKSI',
            '✓ WAJAH TERDETEKSI',
            'DETEKSI WAJAH BERMASALAH',
            '(!faceDetectorReady || !faceValid)',
            "detectValidFace(document.getElementById('camera-canvas'), true, 'image')",
            'validateSelectedSelfieFile',
            'Wajah tidak terdeteksi pada foto. Ambil selfie ulang.',
        ] as $expected) {
            $this->assertStringContainsString($expected, $dashboard);
        }

        $openCamera = substr($dashboard, strpos($dashboard, 'async function openCamera()'));
        $this->assertLessThan(
            strpos($openCamera, 'await initFacePresenceDetector()'),
            strpos($openCamera, 'navigator.mediaDevices.getUserMedia'),
        );
        $this->assertStringContainsString('cameraError = true', $openCamera);
        $this->assertStringContainsString("detectorError = error?.code || 'FD-UNKNOWN'", $dashboard);
        $this->assertStringContainsString('[${detectorError}]', $dashboard);
    }

    public function test_no_skin_colour_heuristic_exists(): void
    {
        $sources = strtolower(
            file_get_contents(resource_path('js/attendance-face-detector.js'))
            .file_get_contents(resource_path('views/employee/dashboard.blade.php')),
        );

        foreach (['skintone', 'skin-tone', 'skincount', 'rgb threshold', 'warm tone'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function test_pwa_privacy_policy_remains_network_only_for_private_routes(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const CACHE_NAME = 'selon-beauty-static-v3';", $worker);
        $this->assertStringContainsString("url.pathname.startsWith('/app')", $worker);
        $this->assertStringContainsString("url.pathname.startsWith('/attendance')", $worker);
        $this->assertStringContainsString("url.pathname.startsWith('/build/')", $worker);
    }
}
