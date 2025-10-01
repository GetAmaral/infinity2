import { Controller } from '@hotwired/stimulus';
import Plyr from 'plyr';
import Hls from 'hls.js';
import 'plyr/dist/plyr.css';

export default class extends Controller {
    static values = {
        videoUrl: String,
        lectureId: String,
        progressUrl: String,
        currentPosition: Number,
        completed: Boolean
    }

    static targets = ['player']

    connect() {
        // Guard: Prevent re-initialization if already connected
        if (this.player || this.hls) {
            console.log('[Player] Already initialized, skipping connect()');
            return;
        }

        this.player = null;
        this.hls = null;
        this.progressInterval = null;
        this.lastSavedPosition = 0;
        this.milestones = {
            25: false,
            50: false,
            75: false,
            100: false
        };

        this.initializePlayer();
    }

    disconnect() {
        this.cleanup();
    }

    initializePlayer() {
        const videoElement = this.playerTarget;

        console.log('[HLS] Initializing player with video URL:', this.videoUrlValue);
        console.log('[HLS] Video element ready:', videoElement.readyState);

        // Check if HLS is supported
        if (Hls.isSupported() && this.videoUrlValue.endsWith('.m3u8')) {
            console.log('[HLS] HLS.js is supported, initializing...');

            // Initialize HLS.js
            this.hls = new Hls({
                maxBufferLength: 30,
                maxMaxBufferLength: 60,
                maxBufferSize: 60 * 1000 * 1000, // 60MB
                maxBufferHole: 0.5,
                debug: false
            });

            // Track HLS.js lifecycle events
            this.hls.on(Hls.Events.MEDIA_ATTACHING, () => {
                console.log('[HLS] Media attaching to video element');
            });

            this.hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                console.log('[HLS] Media successfully attached to video element');
            });

            this.hls.on(Hls.Events.MANIFEST_LOADING, () => {
                console.log('[HLS] Loading manifest:', this.videoUrlValue);
            });

            this.hls.on(Hls.Events.MANIFEST_LOADED, (event, data) => {
                console.log('[HLS] Manifest loaded successfully:', {
                    levels: data.levels.length,
                    qualities: data.levels.map(l => `${l.width}x${l.height}`)
                });
            });

            this.hls.on(Hls.Events.LEVEL_LOADED, (event, data) => {
                console.log('[HLS] Level loaded:', {
                    level: data.level,
                    fragments: data.details.fragments.length
                });
            });

            this.hls.on(Hls.Events.FRAG_LOADING, (event, data) => {
                console.log('[HLS] Fragment loading:', data.frag.relurl);
            });

            this.hls.on(Hls.Events.FRAG_LOADED, (event, data) => {
                console.log('[HLS] Fragment loaded:', {
                    url: data.frag.relurl,
                    duration: data.frag.duration,
                    size: data.payload.byteLength
                });
            });

            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('[HLS] Manifest parsed, initializing Plyr');
                this.initializePlyr();
            });

            this.hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('[HLS] Error occurred:', {
                    type: data.type,
                    details: data.details,
                    fatal: data.fatal,
                    error: data.error,
                    response: data.response,
                    url: data.url,
                    frag: data.frag
                });

                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            console.error('[HLS] Fatal network error, attempting recovery...');
                            this.hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            console.error('[HLS] Fatal media error, attempting recovery...');
                            this.hls.recoverMediaError();
                            break;
                        default:
                            console.error('[HLS] Unrecoverable error, destroying HLS instance');
                            this.showError(`Failed to load video: ${data.details}`);
                            break;
                    }
                }
            });

            console.log('[HLS] Loading source:', this.videoUrlValue);
            this.hls.loadSource(this.videoUrlValue);

            console.log('[HLS] Attaching media to video element');
            this.hls.attachMedia(videoElement);

        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            // Native HLS support (Safari)
            console.log('[HLS] Using native HLS support (Safari)');
            videoElement.src = this.videoUrlValue;
            this.initializePlyr();
        } else {
            console.error('[HLS] No HLS support detected');
            this.showError('Your browser does not support HLS video playback');
        }
    }

    initializePlyr() {
        const videoElement = this.playerTarget;

        console.log('[Plyr] Initializing Plyr player');
        console.log('[Plyr] Video element state:', {
            readyState: videoElement.readyState,
            networkState: videoElement.networkState,
            src: videoElement.src || videoElement.currentSrc,
            error: videoElement.error
        });

        // Initialize Plyr player
        this.player = new Plyr(videoElement, {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'settings',
                'pip',
                'fullscreen'
            ],
            settings: ['quality', 'speed'],
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
            keyboard: { focused: true, global: false },
            tooltips: { controls: true, seek: true },
            hideControls: true,
            resetOnEnd: false
        });

        console.log('[Plyr] Player initialized successfully');

        // Restore saved position if not completed
        if (this.hasCurrentPositionValue && this.currentPositionValue > 0 && !this.completedValue) {
            console.log('[Plyr] Will restore position to:', this.currentPositionValue);
            this.player.on('loadeddata', () => {
                console.log('[Plyr] Video data loaded, restoring position');
                this.player.currentTime = this.currentPositionValue;
            });
        }

        // Setup event listeners
        this.player.on('ready', () => {
            console.log('[Plyr] Player ready event fired');
        });

        this.player.on('loadstart', () => {
            console.log('[Plyr] Load start event fired');
        });

        this.player.on('loadeddata', () => {
            console.log('[Plyr] Data loaded event fired');
        });

        this.player.on('canplay', () => {
            console.log('[Plyr] Can play event fired');
        });

        this.player.on('play', () => {
            console.log('[Plyr] Play event fired');
            this.startProgressTracking();
        });

        this.player.on('pause', () => {
            console.log('[Plyr] Pause event fired');
            this.saveProgress();
        });

        this.player.on('ended', () => {
            console.log('[Plyr] Ended event fired');
            this.handleVideoEnd();
        });

        this.player.on('timeupdate', () => this.checkMilestones());

        this.player.on('error', (event) => {
            const videoError = videoElement.error;
            console.error('[Plyr] Error event fired:', {
                event: event,
                videoError: videoError ? {
                    code: videoError.code,
                    message: videoError.message,
                    MEDIA_ERR_ABORTED: videoError.code === 1,
                    MEDIA_ERR_NETWORK: videoError.code === 2,
                    MEDIA_ERR_DECODE: videoError.code === 3,
                    MEDIA_ERR_SRC_NOT_SUPPORTED: videoError.code === 4
                } : null,
                videoSrc: videoElement.src || videoElement.currentSrc,
                readyState: videoElement.readyState,
                networkState: videoElement.networkState
            });
            this.showError('Video playback error: ' + (videoError?.message || 'Unknown error'));
        });

        this.player.on('statechange', (event) => {
            console.log('[Plyr] State change:', event.detail);
        });
    }

    startProgressTracking() {
        // Clear existing interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Save progress every 5 seconds during playback
        this.progressInterval = setInterval(() => {
            this.saveProgress();
        }, 5000);
    }

    async saveProgress() {
        if (!this.player || !this.hasProgressUrlValue) return;

        const currentTime = Math.floor(this.player.currentTime);
        const duration = Math.floor(this.player.duration);

        // Don't save if position hasn't changed significantly
        if (Math.abs(currentTime - this.lastSavedPosition) < 2) return;

        this.lastSavedPosition = currentTime;

        const completionPercentage = duration > 0 ? (currentTime / duration) * 100 : 0;

        try {
            const response = await fetch(this.progressUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    position: currentTime,
                    duration: duration,
                    completion: completionPercentage
                })
            });

            if (!response.ok) {
                console.error('Failed to save progress:', response.statusText);
            }
        } catch (error) {
            console.error('Error saving progress:', error);
        }
    }

    checkMilestones() {
        if (!this.player) return;

        const currentTime = this.player.currentTime;
        const duration = this.player.duration;
        const percentage = (currentTime / duration) * 100;

        // Check and report milestones
        for (const [milestone, reached] of Object.entries(this.milestones)) {
            if (!reached && percentage >= parseInt(milestone)) {
                this.milestones[milestone] = true;
                this.reportMilestone(parseInt(milestone));
            }
        }
    }

    async reportMilestone(percentage) {
        if (!this.hasProgressUrlValue) return;

        console.log(`Milestone reached: ${percentage}%`);

        try {
            await fetch(`${this.progressUrlValue}/milestone`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    milestone: percentage
                })
            });
        } catch (error) {
            console.error('Error reporting milestone:', error);
        }
    }

    async handleVideoEnd() {
        // Mark as 100% complete
        this.milestones[100] = true;
        await this.saveProgress();
        await this.reportMilestone(100);

        // Stop progress tracking
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
    }

    showError(message) {
        const container = this.element.querySelector('.video-error-message');
        if (container) {
            container.textContent = message;
            container.style.display = 'block';
        } else {
            alert(message);
        }
    }

    cleanup() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        if (this.player) {
            this.player.destroy();
        }

        if (this.hls) {
            this.hls.destroy();
        }
    }
}
