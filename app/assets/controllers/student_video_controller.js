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

            // Initialize HLS.js with high-quality audio settings
            this.hls = new Hls({
                maxBufferLength: 30,
                maxMaxBufferLength: 60,
                maxBufferSize: 60 * 1000 * 1000, // 60MB
                maxBufferHole: 0.5,
                debug: false,
                // Audio quality settings
                enableWorker: true,
                lowLatencyMode: false,
                backBufferLength: 90,
                // Ensure proper audio codec handling
                audioCodec: undefined, // Let browser choose best
                preferManagedMediaSource: false
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

            this.hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
                console.log('[HLS] Manifest parsed, initializing Plyr');
                console.log('[HLS] Audio tracks available:', data.audioTracks?.length || 0);
                console.log('[HLS] Video levels:', data.levels.length);

                // Store levels for quality selector
                this.hlsLevels = data.levels;

                // Force start with highest quality to ensure best audio
                // HLS.js will adapt down if needed, but starts with best quality
                if (data.levels && data.levels.length > 0) {
                    const highestLevel = data.levels.length - 1;
                    console.log('[HLS] Starting with highest quality level:', highestLevel);
                    this.hls.currentLevel = highestLevel;

                    // Log all available quality levels and their audio bitrates
                    data.levels.forEach((level, index) => {
                        console.log(`[HLS] Level ${index}: ${level.width}x${level.height}, bitrate: ${level.bitrate}`);
                    });
                }

                this.initializePlyr();
            });

            // Monitor audio track changes
            this.hls.on(Hls.Events.AUDIO_TRACK_SWITCHED, (event, data) => {
                console.log('[HLS] Audio track switched to:', data.id);
            });

            // Log audio track loading
            this.hls.on(Hls.Events.AUDIO_TRACK_LOADING, (event, data) => {
                console.log('[HLS] Loading audio track:', data.id);
            });

            // Log audio track loaded
            this.hls.on(Hls.Events.AUDIO_TRACK_LOADED, (event, data) => {
                console.log('[HLS] Audio track loaded:', {
                    id: data.id,
                    fragments: data.details.fragments.length
                });
            });

            // Monitor quality level switching (critical for debugging audio issues)
            this.hls.on(Hls.Events.LEVEL_SWITCHING, (event, data) => {
                console.log('[HLS] Switching to quality level:', data.level);
            });

            this.hls.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
                const level = this.hls.levels[data.level];
                console.log('[HLS] Quality level switched:', {
                    level: data.level,
                    resolution: `${level.width}x${level.height}`,
                    bitrate: level.bitrate
                });
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

            // Enable Safari-specific features
            videoElement.setAttribute('playsinline', '');
            videoElement.setAttribute('webkit-playsinline', '');

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

        // Ensure proper audio handling for Chrome
        videoElement.volume = 1.0; // Full volume
        videoElement.muted = false; // Ensure not muted

        // Build quality options from HLS levels
        const qualityOptions = this.buildQualityOptions();

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
            quality: qualityOptions,
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
            keyboard: { focused: true, global: false },
            tooltips: { controls: true, seek: true },
            hideControls: true,
            resetOnEnd: false,
            blankVideo: '', // Disable external blank video to avoid CORS
            volume: 1, // Ensure Plyr volume is at 100%
            muted: false, // Ensure Plyr is not muted
            fullscreen: {
                enabled: true,
                fallback: true,
                iosNative: true // Use native fullscreen on iOS/Safari
            },
            i18n: {
                qualityLabel: {
                    '-1': 'Auto',
                    ...this.buildQualityLabels()
                }
            }
        });

        console.log('[Plyr] Player initialized successfully');

        // Safari-specific PiP fix
        if (this.isSafari()) {
            this.setupSafariPiP();
        }

        // Setup quality change listener
        this.setupQualityChangeListener();

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

    buildQualityOptions() {
        // If HLS is available, build quality options from levels
        if (this.hls && this.hlsLevels && this.hlsLevels.length > 0) {
            console.log('[Quality] Building quality options from HLS levels');

            return {
                default: -1,
                options: [-1, ...this.hlsLevels.map((_, index) => index)],
                forced: true,
                onChange: (quality) => {
                    console.log('[Quality] User selected quality:', quality);
                    this.changeQuality(quality);
                }
            };
        }

        // Fallback for native HLS (Safari)
        return {
            default: 0,
            options: [0],
            forced: true
        };
    }

    setupQualityChangeListener() {
        if (!this.hls) return;

        // Listen to Plyr quality change events
        this.player.on('qualitychange', (event) => {
            const quality = event.detail.quality;
            console.log('[Quality] Quality change event:', quality);
        });

        // Update quality labels dynamically after player is ready
        this.player.on('ready', () => {
            this.updateQualityLabels();
        });
    }

    buildQualityLabels() {
        if (!this.hlsLevels) return {};

        const labels = {};
        this.hlsLevels.forEach((level, index) => {
            labels[index] = `${level.height}p`;
        });

        console.log('[Quality] Built quality labels:', labels);
        return labels;
    }

    updateQualityLabels() {
        if (!this.hlsLevels) return;

        // Update quality menu labels to show resolution
        setTimeout(() => {
            const qualityMenu = this.element.querySelector('[data-plyr="menu"][id*="quality"]');
            if (!qualityMenu) return;

            const qualityOptions = qualityMenu.querySelectorAll('[role="menuitemradio"]');

            qualityOptions.forEach((option) => {
                const value = parseInt(option.getAttribute('value'));
                const labelSpan = option.querySelector('span:last-child');

                if (!labelSpan) return;

                if (value === -1) {
                    labelSpan.textContent = 'Auto';
                } else if (this.hlsLevels[value]) {
                    const level = this.hlsLevels[value];
                    labelSpan.textContent = `${level.height}p`;
                }
            });

            console.log('[Quality] Labels updated');
        }, 200);
    }

    changeQuality(newQuality) {
        if (!this.hls) return;

        const quality = parseInt(newQuality);

        if (quality === -1) {
            // Auto quality
            console.log('[Quality] Switching to Auto quality');
            this.hls.currentLevel = -1;
        } else {
            // Manual quality
            console.log('[Quality] Switching to quality level:', quality);
            this.hls.currentLevel = quality;
        }
    }

    isSafari() {
        return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    }

    setupSafariPiP() {
        const videoElement = this.playerTarget;

        // Check if PiP is supported
        if (!videoElement.webkitSupportsPresentationMode) {
            console.log('[Safari] PiP not supported');
            return;
        }

        console.log('[Safari] Setting up PiP support');

        // Listen for PiP button clicks from Plyr
        this.player.on('enterpip', () => {
            if (videoElement.webkitSetPresentationMode) {
                videoElement.webkitSetPresentationMode('picture-in-picture');
            }
        });

        this.player.on('leavepip', () => {
            if (videoElement.webkitSetPresentationMode) {
                videoElement.webkitSetPresentationMode('inline');
            }
        });

        // Sync Safari's native PiP events with Plyr
        videoElement.addEventListener('webkitpresentationmodechanged', () => {
            if (videoElement.webkitPresentationMode === 'picture-in-picture') {
                console.log('[Safari] Entered PiP mode');
            } else {
                console.log('[Safari] Exited PiP mode');
            }
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
