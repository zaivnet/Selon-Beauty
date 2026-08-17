import mediapipeModelUrl from '../models/blaze_face_short_range.tflite?url';

const DETECTION_WIDTH = 320;
const MEDIAPIPE_WASM_PATH = '/vendor/mediapipe/tasks-vision-1.0.0/wasm';

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

export function hasAcceptableFace(result, minWidthRatio = 0.2) {
    if (!result || result.frameWidth <= 0 || result.frameHeight <= 0) return false;

    return result.faces.some((face) => {
        const centerX = (face.x + face.width / 2) / result.frameWidth;
        const centerY = (face.y + face.height / 2) / result.frameHeight;
        const widthRatio = face.width / result.frameWidth;

        return widthRatio >= minWidthRatio
            && centerX >= 0.25 && centerX <= 0.75
            && centerY >= 0.2 && centerY <= 0.8;
    });
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
    }

    async init() {
        this.destroy();

        if ('FaceDetector' in window) {
            try {
                const nativeDetector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 2 });
                const warmup = document.createElement('canvas');
                warmup.width = 8;
                warmup.height = 8;
                await nativeDetector.detect(warmup);
                this.detector = nativeDetector;
                this.backend = 'native';
                return this.backend;
            } catch (_error) {
                this.detector = null;
            }
        }

        return this.initFallback();
    }

    async initFallback() {
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
            logDetectorFailure(failure);
            throw failure;
        }

        const wasmRoot = mediapipeWasmRoot();
        const nonSimdFileset = {
            wasmLoaderPath: `${wasmRoot}/vision_wasm_nosimd_internal.js`,
            wasmBinaryPath: `${wasmRoot}/vision_wasm_nosimd_internal.wasm`,
        };
        let standardFileset = null;

        try {
            this.stage = 'LOAD_WASM';
            standardFileset = await FilesetResolver.forVisionTasks(wasmRoot);
        } catch (error) {
            const failure = detectorStageError(this.stage, error);
            logDetectorFailure(failure);
        }

        const filesets = standardFileset ? [standardFileset, nonSimdFileset] : [nonSimdFileset];
        let createError = null;

        for (const fileset of filesets) {
            try {
                this.stage = 'CREATE_FACE_DETECTOR';
                this.detector = await MediaPipeFaceDetector.createFromOptions(fileset, {
                    baseOptions: {
                        modelAssetPath: mediapipeModelUrl,
                        delegate: 'CPU',
                    },
                    runningMode: 'VIDEO',
                    minDetectionConfidence: 0.6,
                    minSuppressionThreshold: 0.3,
                });
                this.backend = 'mediapipe';
                this.stage = 'READY';
                this.lastError = null;
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
        logDetectorFailure(failure);
        throw failure;
    }

    isReady() {
        return this.detector !== null;
    }

    async detect(source, mode = 'video') {
        if (!this.isReady() || !source || !this.context) {
            throw new Error('Face presence detector is not ready.');
        }

        const original = sourceSize(source);
        if (original.width <= 0 || original.height <= 0) {
            return { faces: [], frameWidth: 0, frameHeight: 0 };
        }

        this.canvas.width = DETECTION_WIDTH;
        this.canvas.height = Math.max(1, Math.round(DETECTION_WIDTH * original.height / original.width));
        this.context.drawImage(source, 0, 0, this.canvas.width, this.canvas.height);

        if (this.backend === 'native') {
            try {
                const faces = await this.detector.detect(this.canvas);
                return {
                    faces: faces.map((face) => normalizeBox(face.boundingBox)),
                    frameWidth: this.canvas.width,
                    frameHeight: this.canvas.height,
                };
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
            return {
                faces: (result.detections || []).map((face) => normalizeBox(face.boundingBox)),
                frameWidth: this.canvas.width,
                frameHeight: this.canvas.height,
            };
        } catch (error) {
            const failure = detectorStageError('VIDEO_INFERENCE', error);
            this.lastError = failure;
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
