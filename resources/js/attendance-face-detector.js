import mediapipeWasmLoaderUrl from '@mediapipe/tasks-vision/vision_wasm_module_internal.js?url';
import mediapipeWasmBinaryUrl from '@mediapipe/tasks-vision/vision_wasm_module_internal.wasm?url';
import mediapipeModelUrl from '../models/blaze_face_short_range.tflite?url';

const DETECTION_WIDTH = 320;

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
        try {
            const { FaceDetector: MediaPipeFaceDetector } = await import('@mediapipe/tasks-vision');
            this.detector = await MediaPipeFaceDetector.createFromOptions({
                wasmLoaderPath: mediapipeWasmLoaderUrl,
                wasmBinaryPath: mediapipeWasmBinaryUrl,
            }, {
                baseOptions: {
                    modelAssetPath: mediapipeModelUrl,
                    delegate: 'CPU',
                },
                runningMode: 'IMAGE',
                minDetectionConfidence: 0.6,
                minSuppressionThreshold: 0.3,
            });
            this.backend = 'mediapipe';
            return this.backend;
        } catch (_error) {
            this.detector = null;
            this.backend = null;
            throw new Error('Face presence detector failed to initialize.');
        }
    }

    isReady() {
        return this.detector !== null;
    }

    async detect(source) {
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
                return this.detect(source);
            }
        }

        const result = this.detector.detect(this.canvas);
        return {
            faces: (result.detections || []).map((face) => normalizeBox(face.boundingBox)),
            frameWidth: this.canvas.width,
            frameHeight: this.canvas.height,
        };
    }

    destroy() {
        if (this.backend === 'mediapipe' && this.detector?.close) {
            this.detector.close();
        }
        this.detector = null;
        this.backend = null;
    }
}
