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
        this.positionRestored = false; // Flag to prevent multiple restorations

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
            resetOnEnd: false,
            blankVideo: '' // Disable external blank video to avoid CORS
        });

        console.log('[Plyr] Player initialized successfully');

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

            // Restore saved position when video is ready to play
            if (!this.positionRestored && this.hasCurrentPositionValue && this.currentPositionValue > 0) {
                console.log('[Plyr] Restoring position to:', this.currentPositionValue, 'seconds');
                this.player.currentTime = this.currentPositionValue;
                this.positionRestored = true; // Prevent multiple restorations
            }
        });

        this.player.on('play', () => {
            console.log('[Plyr] Play event fired');
            this.startProgressTracking();
        });

        this.player.on('pause', () => {
            console.log('[Plyr] Pause event fired');
            this.saveProgressAndUpdateUI(true); // Force save on pause
            this.stopProgressTracking();
        });

        this.player.on('ended', () => {
            console.log('[Plyr] Ended event fired');
            this.handleVideoEnd();
        });

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

        // Send progress every 5 seconds during playback
        this.progressInterval = setInterval(() => {
            this.saveProgressAndUpdateUI();
        }, 5000);
    }

    stopProgressTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    async saveProgressAndUpdateUI(force = false) {
        if (!this.player || !this.hasProgressUrlValue) return;

        const currentTime = Math.floor(this.player.currentTime);
        const duration = Math.floor(this.player.duration);

        // Don't save if position hasn't changed significantly (unless forced)
        if (!force && Math.abs(currentTime - this.lastSavedPosition) < 2) return;

        this.lastSavedPosition = currentTime;

        console.log('[Progress] Sending position:', currentTime, 'seconds');

        try {
            const response = await fetch(this.progressUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    position: currentTime,
                    duration: duration
                })
            });

            if (!response.ok) {
                console.error('[Progress] Failed to save:', response.statusText);
                return;
            }

            const data = await response.json();
            console.log('[Progress] Response:', data);

            // Update UI based on backend response
            this.updateProgressUI(data.completion, data.completed);

        } catch (error) {
            console.error('[Progress] Error saving:', error);
        }
    }

    updateProgressUI(completionPercentage, isCompleted) {
        const roundedPercentage = Math.round(completionPercentage);

        // Update all progress percentage displays (multiple elements may exist)
        const progressTexts = document.querySelectorAll('.lecture-progress-text');
        progressTexts.forEach(el => {
            el.textContent = `${roundedPercentage}%`;
        });

        // Update progress bar (if element exists in template)
        const progressBar = document.querySelector('.lecture-progress-bar');
        if (progressBar) {
            progressBar.style.width = `${completionPercentage}%`;
            progressBar.setAttribute('aria-valuenow', roundedPercentage);

            // Show progress bar container if progress > 0
            const progressContainer = progressBar.closest('.mt-4');
            if (progressContainer && completionPercentage > 0) {
                progressContainer.style.display = 'block';
            }
        }

        // Update completion badge (if element exists in template)
        const completionBadge = document.querySelector('.lecture-completion-badge');
        if (completionBadge) {
            if (isCompleted) {
                completionBadge.textContent = 'Completed';
                completionBadge.classList.remove('bg-secondary', 'bg-primary');
                completionBadge.classList.add('bg-success');
            } else if (completionPercentage > 0) {
                completionBadge.textContent = 'In Progress';
                completionBadge.classList.remove('bg-secondary', 'bg-success');
                completionBadge.classList.add('bg-primary');
            }
        }

        console.log('[Progress] UI updated:', roundedPercentage + '%', isCompleted ? '(Completed)' : '');
    }

    async handleVideoEnd() {
        // Send final progress update (forced)
        await this.saveProgressAndUpdateUI(true);

        // Stop progress tracking
        this.stopProgressTracking();
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
        // Clear progress tracking interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }

        // Destroy Plyr player first
        if (this.player) {
            try {
                this.player.destroy();
            } catch (error) {
                console.warn('[Plyr] Error during player cleanup:', error);
            }
            this.player = null;
        }

        // Then destroy HLS instance
        if (this.hls) {
            try {
                this.hls.destroy();
            } catch (error) {
                console.warn('[HLS] Error during HLS cleanup:', error);
            }
            this.hls = null;
        }

        // Reset flags
        this.positionRestored = false;
    }
}
