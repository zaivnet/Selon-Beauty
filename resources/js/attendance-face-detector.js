import mediapipeModelUrl from '../models/blaze_face_short_range.tflite?url';

const DETECTION_WIDTH = 320;
const MEDIAPIPE_WASM_PATH = '/vendor/mediapipe/tasks-vision-1.0.0/wasm';

export const DEFAULT_MIN_DETECTION_CONFIDENCE = 0.50;
export const DEFAULT_MIN_WIDTH_RATIO = 0.15;
export const DEFAULT_CENTER_X_MIN = 0.25;
export const DEFAULT_CENTER_X_MAX = 0.75;
export const DEFAULT_CENTER_Y_MIN = 0.20;
export const DEFAULT_CENTER_Y_MAX = 0.80;

const DETECTOR_ERRORS = {
    IMPORT_TASKS_VISION: 'FD-IMPORT',
    LOAD_WASM: 'FD-WASM',
    CREATE_FACE_DETECTOR: 'FD-CREATE',
    VIDEO_INFERENCE: 'FD-INFERENCE',
};

function mediapipeWasmRoot() {
    return new URL(MEDIAPIPE_WASM_PATH, window.location.origin).href.replace(/\/$/, '');
}

function detectorStageError(stage, error) {
    const failure = new Error('Face presence detector failed.');
    failure.name = 'FaceDetectorStageError';
    failure.stage = stage;
    failure.code = DETECTOR_ERRORS[stage] || 'FD-UNKNOWN';
    failure.originalErrorName = error?.name || 'Error';
    failure.originalErrorMessage = error?.message || 'Unknown error';
    return failure;
}

function logDetectorFailure(error) {
    console.warn(
        `[FaceDetector] ${error.code} ${error.stage}:`,
        `${error.originalErrorName}: ${error.originalErrorMessage}`,
    );
}

function sourceSize(source) {
    return {
        width: source.videoWidth || source.naturalWidth || source.width || 0,
        height: source.videoHeight || source.naturalHeight || source.height || 0,
    };
}

function normalizeBox(box) {
    return {
        x: Number(box.x ?? box.originX ?? 0),
        y: Number(box.y ?? box.originY ?? 0),
        width: Number(box.width ?? 0),
        height: Number(box.height ?? 0),
    };
}

export function evaluateFaceAcceptance(result, minWidthRatio = DEFAULT_MIN_WIDTH_RATIO) {
    if (!result || result.frameWidth <= 0 || result.frameHeight <= 0) {
        return {
            acceptable: false,
            reason: 'invalid_frame',
            bestMetrics: null,
        };
    }

    if (!result.faces || result.faces.length === 0) {
        return {
            acceptable: false,
            reason: 'no_detection',
            bestMetrics: null,
        };
    }

    let passFace = null;
    let bestFace = null;
    let maxArea = -1;

    for (const face of result.faces) {
        const centerX = (face.x + face.width / 2) / result.frameWidth;
        const centerY = (face.y + face.height / 2) / result.frameHeight;
        const widthRatio = face.width / result.frameWidth;
        const area = face.width * face.height;

        const metrics = {
            widthRatio: Number(widthRatio.toFixed(4)),
            centerXRatio: Number(centerX.toFixed(4)),
            centerYRatio: Number(centerY.toFixed(4)),
            confidence: face.confidence !== undefined ? face.confidence : null,
            boundingBox: { x: face.x, y: face.y, width: face.width, height: face.height },
        };

        const isSizeValid = widthRatio >= minWidthRatio;
        const isXValid = centerX >= DEFAULT_CENTER_X_MIN && centerX <= DEFAULT_CENTER_X_MAX;
        const isYValid = centerY >= DEFAULT_CENTER_Y_MIN && centerY <= DEFAULT_CENTER_Y_MAX;

        if (isSizeValid && isXValid && isYValid) {
            passFace = { face, metrics };
            break;
        }

        if (area > maxArea) {
            maxArea = area;
            let rejectReason = 'geometry_rejected';
            if (!isSizeValid) {
                rejectReason = 'face_too_small';
            } else if (centerX < DEFAULT_CENTER_X_MIN) {
                rejectReason = 'face_too_far_left';
            } else if (centerX > DEFAULT_CENTER_X_MAX) {
                rejectReason = 'face_too_far_right';
            } else if (centerY < DEFAULT_CENTER_Y_MIN) {
                rejectReason = 'face_too_high';
            } else if (centerY > DEFAULT_CENTER_Y_MAX) {
                rejectReason = 'face_too_low';
            }

            bestFace = { face, metrics, rejectReason };
        }
    }

    if (passFace) {
        return {
            acceptable: true,
            reason: 'none',
            bestMetrics: passFace.metrics,
        };
    }

    return {
        acceptable: false,
        reason: bestFace ? bestFace.rejectReason : 'no_detection',
        bestMetrics: bestFace ? bestFace.metrics : null,
    };
}

export function hasAcceptableFace(result, minWidthRatio = DEFAULT_MIN_WIDTH_RATIO) {
    return evaluateFaceAcceptance(result, minWidthRatio).acceptable;
}

export class AttendanceFaceDetector {
    constructor() {
        this.backend = null;
        this.detector = null;
        this.canvas = document.createElement('canvas');
        this.context = this.canvas.getContext('2d', { willReadFrequently: false });
        this.lastVideoTimestamp = 0;
        this.stage = null;
        this.lastError = null;

        this.diagnostics = {
            backend: 'unavailable',
            initStatus: 'uninitialized',
            initDurationMs: 0,
            nativeApiAvailable: typeof window !== 'undefined' && 'FaceDetector' in window,
            nativeWarmupCount: null,
            nativeWarmupDurationMs: null,
            nativeWarmupError: null,
            filesetDurationMs: null,
            mediapipeInitDurationMs: null,
            wasmType: null,
            lastDetection: {
                timestamp: 0,
                durationMs: 0,
                mode: null,
                sourceDimensions: { width: 0, height: 0 },
                canvasDimensions: { width: 0, height: 0 },
                rawDetectionCount: 0,
                rawConfidence: null,
                bestFaceMetrics: null,
                validationResult: 'IDLE',
                rejectReason: 'none',
                lastError: null,
            },
            history: [],
        };
    }

    getDiagnostics() {
        return {
            ...this.diagnostics,
            lastDetection: { ...this.diagnostics.lastDetection },
            history: [...this.diagnostics.history],
        };
    }

    recordDetectionDiagnostic(entry) {
        this.diagnostics.lastDetection = entry;
        this.diagnostics.history.push(entry);
        if (this.diagnostics.history.length > 5) {
            this.diagnostics.history.shift();
        }
    }

    async init() {
        this.destroy();
        const initStart = performance.now();
        this.diagnostics.initStatus = 'loading';
        this.diagnostics.nativeApiAvailable = typeof window !== 'undefined' && 'FaceDetector' in window;

        if (this.diagnostics.nativeApiAvailable) {
            const warmupStart = performance.now();
            try {
                const nativeDetector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 2 });
                const warmup = document.createElement('canvas');
                warmup.width = 8;
                warmup.height = 8;
                const warmupDetections = await nativeDetector.detect(warmup);
                this.diagnostics.nativeWarmupDurationMs = Math.round(performance.now() - warmupStart);
                this.diagnostics.nativeWarmupCount = Array.isArray(warmupDetections) ? warmupDetections.length : 0;
                this.diagnostics.nativeWarmupError = null;

                this.detector = nativeDetector;
                this.backend = 'native';
                this.diagnostics.backend = 'native';
                this.diagnostics.initStatus = 'ready';
                this.diagnostics.initDurationMs = Math.round(performance.now() - initStart);
                return this.backend;
            } catch (error) {
                this.detector = null;
                this.diagnostics.nativeWarmupDurationMs = Math.round(performance.now() - warmupStart);
                this.diagnostics.nativeWarmupError = error?.message || 'Native warmup failed';
            }
        }

        return this.initFallback(initStart);
    }

    async initFallback(initStart = performance.now()) {
        let MediaPipeFaceDetector;
        let FilesetResolver;

        try {
            this.stage = 'IMPORT_TASKS_VISION';
            const tasksVision = await import('@mediapipe/tasks-vision');
            MediaPipeFaceDetector = tasksVision.FaceDetector;
            FilesetResolver = tasksVision.FilesetResolver;
        } catch (error) {
            const failure = detectorStageError(this.stage, error);
            this.lastError = failure;
            this.diagnostics.initStatus = 'failed';
            this.diagnostics.backend = 'unavailable';
            logDetectorFailure(failure);
            throw failure;
        }

        const wasmRoot = mediapipeWasmRoot();
        const nonSimdFileset = {
            wasmLoaderPath: `${wasmRoot}/vision_wasm_nosimd_internal.js`,
            wasmBinaryPath: `${wasmRoot}/vision_wasm_nosimd_internal.wasm`,
            type: 'nosimd',
        };
        let standardFileset = null;

        const filesetStart = performance.now();
        try {
            this.stage = 'LOAD_WASM';
            standardFileset = await FilesetResolver.forVisionTasks(wasmRoot);
            standardFileset.type = 'simd';
            this.diagnostics.filesetDurationMs = Math.round(performance.now() - filesetStart);
        } catch (error) {
            this.diagnostics.filesetDurationMs = Math.round(performance.now() - filesetStart);
            const failure = detectorStageError(this.stage, error);
            logDetectorFailure(failure);
        }

        const filesets = standardFileset ? [standardFileset, nonSimdFileset] : [nonSimdFileset];
        let createError = null;

        for (const fileset of filesets) {
            const mpStart = performance.now();
            try {
                this.stage = 'CREATE_FACE_DETECTOR';
                this.detector = await MediaPipeFaceDetector.createFromOptions(fileset, {
                    baseOptions: {
                        modelAssetPath: mediapipeModelUrl,
                        delegate: 'CPU',
                    },
                    runningMode: 'VIDEO',
                    minDetectionConfidence: DEFAULT_MIN_DETECTION_CONFIDENCE,
                    minSuppressionThreshold: 0.3,
                });
                this.backend = 'mediapipe';
                this.stage = 'READY';
                this.lastError = null;
                this.diagnostics.backend = 'mediapipe';
                this.diagnostics.wasmType = fileset.type || (fileset === standardFileset ? 'simd' : 'nosimd');
                this.diagnostics.mediapipeInitDurationMs = Math.round(performance.now() - mpStart);
                this.diagnostics.initStatus = 'ready';
                this.diagnostics.initDurationMs = Math.round(performance.now() - initStart);
                return this.backend;
            } catch (error) {
                this.detector?.close?.();
                this.detector = null;
                createError = error;
            }
        }

        const failure = detectorStageError('CREATE_FACE_DETECTOR', createError);
        this.lastError = failure;
        this.backend = null;
        this.diagnostics.backend = 'unavailable';
        this.diagnostics.initStatus = 'failed';
        logDetectorFailure(failure);
        throw failure;
    }

    isReady() {
        return this.detector !== null;
    }

    async detect(source, mode = 'video') {
        const detectStart = performance.now();

        if (!this.isReady() || !source || !this.context) {
            this.recordDetectionDiagnostic({
                timestamp: Date.now(),
                durationMs: 0,
                mode,
                sourceDimensions: { width: 0, height: 0 },
                canvasDimensions: { width: 0, height: 0 },
                rawDetectionCount: 0,
                rawConfidence: null,
                bestFaceMetrics: null,
                validationResult: 'FAIL',
                rejectReason: 'detector_not_ready',
                lastError: 'Detector not ready',
            });
            throw new Error('Face presence detector is not ready.');
        }

        const original = sourceSize(source);
        if (original.width <= 0 || original.height <= 0) {
            const emptyResult = { faces: [], frameWidth: 0, frameHeight: 0 };
            this.recordDetectionDiagnostic({
                timestamp: Date.now(),
                durationMs: Math.round(performance.now() - detectStart),
                mode,
                sourceDimensions: original,
                canvasDimensions: { width: 0, height: 0 },
                rawDetectionCount: 0,
                rawConfidence: null,
                bestFaceMetrics: null,
                validationResult: 'FAIL',
                rejectReason: 'invalid_frame',
                lastError: null,
            });
            return emptyResult;
        }

        this.canvas.width = DETECTION_WIDTH;
        this.canvas.height = Math.max(1, Math.round(DETECTION_WIDTH * original.height / original.width));
        this.context.drawImage(source, 0, 0, this.canvas.width, this.canvas.height);

        if (this.backend === 'native') {
            try {
                const faces = await this.detector.detect(this.canvas);
                const mappedFaces = faces.map((face) => normalizeBox(face.boundingBox));
                const detectionResult = {
                    faces: mappedFaces,
                    frameWidth: this.canvas.width,
                    frameHeight: this.canvas.height,
                };
                const evaluation = evaluateFaceAcceptance(detectionResult);

                this.recordDetectionDiagnostic({
                    timestamp: Date.now(),
                    durationMs: Math.round(performance.now() - detectStart),
                    mode,
                    sourceDimensions: original,
                    canvasDimensions: { width: this.canvas.width, height: this.canvas.height },
                    rawDetectionCount: mappedFaces.length,
                    rawConfidence: null,
                    bestFaceMetrics: evaluation.bestMetrics,
                    validationResult: evaluation.acceptable ? 'PASS' : 'FAIL',
                    rejectReason: evaluation.reason,
                    lastError: null,
                });

                return detectionResult;
            } catch (_error) {
                await this.initFallback();
                return this.detect(source, mode);
            }
        }

        try {
            this.stage = 'VIDEO_INFERENCE';
            let result;
            if (mode === 'image') {
                await this.detector.setOptions({ runningMode: 'IMAGE' });
                try {
                    result = this.detector.detect(this.canvas);
                } finally {
                    await this.detector.setOptions({ runningMode: 'VIDEO' });
                }
            } else {
                const now = performance.now();
                this.lastVideoTimestamp = Math.max(now, this.lastVideoTimestamp + 1);
                result = this.detector.detectForVideo(this.canvas, this.lastVideoTimestamp);
            }

            const rawDetections = result.detections || [];
            const mappedFaces = rawDetections.map((detection) => ({
                ...normalizeBox(detection.boundingBox),
                confidence: detection.categories?.[0]?.score !== undefined
                    ? Number(detection.categories[0].score.toFixed(4))
                    : null,
            }));

            const detectionResult = {
                faces: mappedFaces,
                frameWidth: this.canvas.width,
                frameHeight: this.canvas.height,
            };

            const evaluation = evaluateFaceAcceptance(detectionResult);
            const bestConfidence = mappedFaces.length > 0 ? mappedFaces[0].confidence : null;

            this.recordDetectionDiagnostic({
                timestamp: Date.now(),
                durationMs: Math.round(performance.now() - detectStart),
                mode,
                sourceDimensions: original,
                canvasDimensions: { width: this.canvas.width, height: this.canvas.height },
                rawDetectionCount: mappedFaces.length,
                rawConfidence: bestConfidence,
                bestFaceMetrics: evaluation.bestMetrics,
                validationResult: evaluation.acceptable ? 'PASS' : 'FAIL',
                rejectReason: evaluation.reason,
                lastError: null,
            });

            return detectionResult;
        } catch (error) {
            const failure = detectorStageError('VIDEO_INFERENCE', error);
            this.lastError = failure;
            this.recordDetectionDiagnostic({
                timestamp: Date.now(),
                durationMs: Math.round(performance.now() - detectStart),
                mode,
                sourceDimensions: original,
                canvasDimensions: { width: this.canvas.width, height: this.canvas.height },
                rawDetectionCount: 0,
                rawConfidence: null,
                bestFaceMetrics: null,
                validationResult: 'FAIL',
                rejectReason: 'detector_error',
                lastError: `${failure.code}: ${failure.originalErrorMessage}`,
            });
            logDetectorFailure(failure);
            throw failure;
        }
    }

    destroy() {
        if (this.backend === 'mediapipe' && this.detector?.close) {
            this.detector.close();
        }
        this.detector = null;
        this.backend = null;
    }
}
