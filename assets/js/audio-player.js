(function() {
    var CONFIG_CACHE_KEY = 'echoads_player_config';
    function fetchPlayerConfig(configEndpoint, apiKey) {
        if (!configEndpoint || typeof jQuery === 'undefined') {
            return Promise.resolve({ language: { code: 'en' }, defaultPlaybackSetting: 'CLICK_TO_PLAY' });
        }
        if (typeof window[CONFIG_CACHE_KEY] === 'undefined') {
            window[CONFIG_CACHE_KEY] = jQuery.ajax({
                url: configEndpoint,
                type: 'GET',
                headers: apiKey ? { 'x-api-key': apiKey } : {},
                timeout: 8000,
                dataType: 'json'
            }).then(function(response) {
                var data = response && response.data ? response.data : response;
                return {
                    language: (data && data.language) ? data.language : { code: 'en' },
                    defaultPlaybackSetting: (data && data.defaultPlaybackSetting) ? String(data.defaultPlaybackSetting).toUpperCase() : 'CLICK_TO_PLAY'
                };
            }).catch(function() {
                return { language: { code: 'en' }, defaultPlaybackSetting: 'CLICK_TO_PLAY' };
            });
        }
        return window[CONFIG_CACHE_KEY];
    }

    var TRANSLATIONS = {
        en: {
            listenToArticle: 'Listen to this Article',
            listenToArticleAria: 'Listen to this article',
            audioPlayerAria: 'Audio Player',
            play: 'Play',
            pause: 'Pause',
            playPauseTitle: 'Play/Pause',
            audioProgressAria: 'Audio progress',
            volumeTitle: 'Volume',
            volumeAria: 'Volume',
            volumeLevelAria: 'Volume level',
            statusReady: 'Ready',
            statusLoading: 'Loading...',
            statusPlaying: 'Playing',
            statusPaused: 'Paused',
            statusError: 'Error',
            statusBuffering: 'Buffering...',
            statusFinished: 'Finished',
            statusChecking: 'Checking status...',
            statusGenerating: 'Audio is being generated...',
            statusNotReady: 'Audio not ready',
            statusCheckFailed: 'Status check failed',
            statusFailed: 'Audio generation failed',
            statusSkipped: 'Audio generation skipped'
        },
        ar: {
            listenToArticle: 'استمع للخبر الآن',
            listenToArticleAria: 'استمع للخبر الآن',
            audioPlayerAria: 'مشغل صوتي',
            play: 'تشغيل',
            pause: 'إيقاف',
            playPauseTitle: 'تشغيل/إيقاف',
            audioProgressAria: 'تقدم التشغيل',
            volumeTitle: 'مستوى الصوت',
            volumeAria: 'مستوى الصوت',
            volumeLevelAria: 'مستوى الصوت',
            statusReady: 'جاهز',
            statusLoading: 'جاري التحميل...',
            statusPlaying: 'جاري التشغيل',
            statusPaused: 'متوقف',
            statusError: 'خطأ',
            statusBuffering: 'جاري التخزين المؤقت...',
            statusFinished: 'انتهى',
            statusChecking: 'جاري التحقق...',
            statusGenerating: 'جاري إنشاء الصوت...',
            statusNotReady: 'الصوت غير جاهز',
            statusCheckFailed: 'فشل التحقق من الحالة',
            statusFailed: 'فشل إنشاء الصوت',
            statusSkipped: 'تم تخطي إنشاء الصوت'
        }
    };

    var STATUS_TO_TRANSLATION_KEY = {
        'Ready': 'statusReady',
        'Loading...': 'statusLoading',
        'Playing': 'statusPlaying',
        'Paused': 'statusPaused',
        'Error': 'statusError',
        'Buffering...': 'statusBuffering',
        'Finished': 'statusFinished',
        'Checking status...': 'statusChecking',
        'Audio is being generated...': 'statusGenerating',
        'Audio not ready': 'statusNotReady',
        'Status check failed': 'statusCheckFailed',
        'Audio generation failed': 'statusFailed',
        'Audio generation skipped': 'statusSkipped'
    };

window.EchoAdsAudioController = {
    init: function(playerId) {
        var audioData = window.EchoAdsAudioPlayers[playerId];
        if (!audioData) return;

        var wrapper = document.getElementById(playerId + "-wrapper");
        var SESSION_STORAGE_KEY = 'echoads_play_session_id';
        var playSessionId = null;
        try {
            playSessionId = sessionStorage.getItem(SESSION_STORAGE_KEY);
        } catch (e) {}
        if (!playSessionId) {
            playSessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : 'echoads-' + Date.now() + '-' + Math.random().toString(36).substring(2, 15);
            try {
                sessionStorage.setItem(SESSION_STORAGE_KEY, playSessionId);
            } catch (e) {}
        }

        if (typeof window.echoadsVisitorIdPromise === 'undefined') {
            window.echoadsVisitorIdPromise = (function() {
                var FP = window.FingerprintJS;
                if (typeof FP === 'undefined' || typeof FP.load !== 'function') {
                    return Promise.resolve(null);
                }
                return FP.load()
                    .then(function(agent) {
                        return agent && typeof agent.get === 'function'
                            ? agent.get().then(function(result) { return result.visitorId || null; })
                            : Promise.resolve(null);
                    })
                    .catch(function() { return null; });
            })();
        }
        var visitorIdPromise = window.echoadsVisitorIdPromise;
        
        // Get wrapper and listen button container elements
        var listenBtnContainer = document.getElementById(playerId + "-listen-btn-container");
        var playerContainer = document.getElementById(playerId);
        
        // Get all player elements
        var audio = document.getElementById(playerId + "-audio");
        var playPauseBtn = document.getElementById(playerId + "-play-pause");
        var waveform = document.getElementById(playerId + "-progress");
        var currentTimeSpan = document.getElementById(playerId + "-current-time");
        var durationSpan = document.getElementById(playerId + "-duration");
        var trackDisplay = document.getElementById(playerId + "-track");
        var statusDisplay = document.getElementById(playerId + "-status");
        var volumeControl = document.getElementById(playerId + "-volume-control");
        var volumeBtn = document.getElementById(playerId + "-volume-btn");
        var volumeInput = document.getElementById(playerId + "-volume-input");
        var volumeFill = document.getElementById(playerId + "-volume-fill");
        var volumeSliderWrapper = volumeControl ? volumeControl.querySelector(".echoads-volume-slider-wrapper") : null;
        var playIcon = playPauseBtn.querySelector(".play-icon");
        var pauseIcon = playPauseBtn.querySelector(".pause-icon");
        var volumeIcon = volumeBtn.querySelector(".volume-icon");
        var volumeMutedIcon = volumeBtn.querySelector(".volume-muted-icon");
        var waveformBars = waveform.querySelectorAll(".echoads-bar");
        
        if (!audio || !playPauseBtn || !waveform) {
            console.error("Audio player elements not found for", playerId);
            return;
        }
        
        var currentTrack = 0;
        var isPlaying = false;
        var isDragging = false;
        var audioStatusChecked = false;
        var audioStatus = null;
        var isMuted = false;
        var lastVolume = 80;
        var isPlayerVisible = false;
        var isVolumePopupOpen = false;
        var fiveSecondTrackingSent = false;
        var trackingSentThisPage = {};
        var translationsMap = null;
        
        var tracks = [
            { url: audioData.preRoll, name: "Pre-Roll Ad", trackingUrl: audioData.prerollTrackingUrl, campaignAudioId: audioData.preRollAudioId, allowSeeking: false },
            { url: audioData.article, name: "Article Audio", trackingUrl: null, campaignAudioId: audioData.articleAudioId, allowSeeking: true },
            { url: audioData.postRoll, name: "Post-Roll Ad", trackingUrl: audioData.postrollTrackingUrl, campaignAudioId: audioData.postRollAudioId, allowSeeking: false }
        ].filter(track => track.url);
        
        // Initialize volume
        if (volumeInput) {
            audio.volume = volumeInput.value / 100;
            lastVolume = volumeInput.value;
            updateVolumeFill(volumeInput.value);
        }
        
        // Show player and hide listen button container
        function showPlayer() {
            if (listenBtnContainer) {
                listenBtnContainer.classList.add('echoads-hidden');
            }
            if (playerContainer) {
                playerContainer.classList.remove('echoads-hidden');
            }
            isPlayerVisible = true;
        }
        
        function playWhenAudioReady() {
            var playNow = function() {
                audio.play().catch(function(error) {
                    console.error("Play failed:", error);
                    updatePlayerState("Error");
                });
            };
            if (audio.readyState >= 2) {
                playNow();
            } else {
                var onCanPlay = function() {
                    audio.removeEventListener("canplay", onCanPlay);
                    playNow();
                };
                audio.addEventListener("canplay", onCanPlay);
            }
        }

        // Listen button container click handler
        if (listenBtnContainer) {
            listenBtnContainer.addEventListener("click", function() {
                showPlayer();

                // Check status and start playing
                checkAudioStatus(function(canPlay) {
                    if (canPlay) {
                        if (tracks.length > 0 && !audio.src) {
                            loadTrack(0);
                            playWhenAudioReady();
                        } else if (audio.src) {
                            playWhenAudioReady();
                        }
                    } else {
                        updatePlayPauseButton(false);
                    }
                });
            });
            
            // Keyboard support for listen button container
            listenBtnContainer.addEventListener("keydown", function(e) {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    listenBtnContainer.click();
                }
            });
        }
        
        function updatePlayerState(state) {
            if (statusDisplay) {
                var displayText = state;
                if (translationsMap) {
                    var key = STATUS_TO_TRANSLATION_KEY[state];
                    if (key && translationsMap[key] !== undefined) displayText = translationsMap[key];
                }
                statusDisplay.textContent = displayText;
            }
            playerContainer.className = playerContainer.className.replace(/\s*(loading|playing|paused)/g, '');
            // Preserve hidden class if player is not visible
            if (!isPlayerVisible) {
                playerContainer.classList.add('echoads-hidden');
            }
            if (state === "Loading...") {
                playerContainer.classList.add('loading');
            } else if (state === "Playing") {
                playerContainer.classList.add('playing');
            } else if (state === "Paused") {
                playerContainer.classList.add('paused');
            }
        }

        function checkAudioStatus(callback) {
            if (!audioData.statusEndpoint || !audioData.apiKey) {
                if (callback) callback(true);
                return;
            }

            if (audioStatusChecked && audioStatus === 'COMPLETED') {
                if (callback) callback(true);
                return;
            }

            updatePlayerState("Checking status...");

            if (typeof jQuery === "undefined") {
                console.error("jQuery is required for status check");
                updatePlayerState("Error");
                if (callback) callback(false);
                return;
            }

            jQuery.ajax({
                url: audioData.statusEndpoint,
                type: 'GET',
                headers: {
                    'x-api-key': audioData.apiKey
                },
                timeout: 10000,
                success: function(response) {
                    audioStatusChecked = true;
                    
                    var status = null;
                    if (response.success && response.data && response.data.audioStatus) {
                        status = response.data.audioStatus;
                    } else if (response.audioStatus) {
                        status = response.audioStatus;
                    }

                    audioStatus = status;

                    if (status === 'COMPLETED') {
                        updatePlayerState("Ready");
                        if (callback) callback(true);
                    } else {
                        var statusMessage = status || 'Unknown';
                        updatePlayerState("Status: " + statusMessage);
                        if (status === 'PENDING' || status === 'PROCESSING') {
                            updatePlayerState("Audio is being generated...");
                        } else if (status === 'FAILED' || status === 'SKIPPED') {
                            updatePlayerState("Audio generation " + status.toLowerCase());
                        } else {
                            updatePlayerState("Audio not ready");
                        }
                        if (callback) callback(false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error checking audio status:", error);
                    updatePlayerState("Status check failed");
                    if (callback) callback(true);
                }
            });
        }
        
        function loadTrack(index) {
            if (index >= tracks.length || index < 0) return;
            currentTrack = index;
            fiveSecondTrackingSent = false;
            updatePlayerState("Loading...");
            updateWaveformState();
            audio.src = tracks[index].url;
            if (trackDisplay) {
                trackDisplay.textContent = tracks[index].name;
            }
            audio.load();
        }
        
        function callTrackingEndpoint(options) {
            var url = options.url;
            var apiKey = options.apiKey;
            var campaignAudioId = options.campaignAudioId;
            var playPositionSeconds = options.playPositionSeconds;
            var sessionId = options.playSessionId;
            var visitorId = options.visitorId;
            if (!url || typeof jQuery === "undefined") return;
            var requestBody = {
                campaignAudioId: campaignAudioId ?? null,
                playSessionId: sessionId,
                visitorId: visitorId,
                playPositionSeconds: typeof playPositionSeconds === 'number' ? playPositionSeconds : 0
            };
            var ajaxOptions = {
                url: url,
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify(requestBody),
                success: function(response) {
                    console.log("Tracking call successful:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Tracking call failed:", error);
                }
            };
            if (apiKey) {
                ajaxOptions.headers = {
                    'x-api-key': apiKey
                };
            }
            jQuery.ajax(ajaxOptions);
        }

        function sendTrackingOnce(track, playPositionSeconds) {
            if (!track || !track.trackingUrl) return;
            var baseKey = (track.campaignAudioId != null && track.campaignAudioId !== '') ? String(track.campaignAudioId) : ('track-' + currentTrack);
            var key = baseKey + '-' + (typeof playPositionSeconds === 'number' ? playPositionSeconds : 0);
            if (trackingSentThisPage[key]) return;
            trackingSentThisPage[key] = true;
            visitorIdPromise.then(function(visitorId) {
                callTrackingEndpoint({
                    url: track.trackingUrl,
                    apiKey: audioData.apiKey,
                    campaignAudioId: track.campaignAudioId,
                    playPositionSeconds: playPositionSeconds,
                    playSessionId: playSessionId,
                    visitorId: visitorId
                });
            });
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds)) return "0:00";
            var minutes = Math.floor(seconds / 60);
            var secs = Math.floor(seconds % 60);
            return minutes + ":" + (secs < 10 ? "0" : "") + secs;
        }
        
        function updatePlayPauseButton(playing) {
            if (playIcon && pauseIcon) {
                var label = playing
                    ? (translationsMap ? translationsMap.pause : "Pause")
                    : (translationsMap ? translationsMap.play : "Play");
                if (playing) {
                    playIcon.style.display = "none";
                    pauseIcon.style.display = "block";
                } else {
                    playIcon.style.display = "block";
                    pauseIcon.style.display = "none";
                }
                playPauseBtn.setAttribute("aria-label", label);
            }
        }
        
        function updateVolumeIcon() {
            if (volumeIcon && volumeMutedIcon) {
                if (isMuted || audio.volume === 0) {
                    volumeIcon.style.display = "none";
                    volumeMutedIcon.style.display = "block";
                } else {
                    volumeIcon.style.display = "block";
                    volumeMutedIcon.style.display = "none";
                }
            }
        }
        
        function updateVolumeFill(value) {
            if (volumeFill) {
                volumeFill.style.height = value + '%';
            }
            // Update the thumb position via CSS custom property
            if (volumeSliderWrapper) {
                volumeSliderWrapper.style.setProperty('--volume-percent', value + '%');
            }
        }
        
        function toggleVolumePopup() {
            if (volumeControl) {
                isVolumePopupOpen = !isVolumePopupOpen;
                volumeControl.classList.toggle('active', isVolumePopupOpen);
                volumeBtn.setAttribute('aria-expanded', isVolumePopupOpen ? 'true' : 'false');
            }
        }
        
        function closeVolumePopup() {
            if (volumeControl && isVolumePopupOpen) {
                isVolumePopupOpen = false;
                volumeControl.classList.remove('active');
                volumeBtn.setAttribute('aria-expanded', 'false');
            }
        }
        
        function isSeekingAllowed() {
            return tracks[currentTrack] && tracks[currentTrack].allowSeeking;
        }
        
        function updateWaveformState() {
            var seekingAllowed = isSeekingAllowed();
            if (waveform) {
                waveform.style.cursor = seekingAllowed ? 'pointer' : 'default';
                waveform.setAttribute('data-seeking-disabled', seekingAllowed ? 'false' : 'true');
            }
        }
        
        function updateWaveformProgress() {
            if (isDragging) return;
            var progress = (audio.currentTime / audio.duration) * 100;
            if (isNaN(progress)) progress = 0;
            var totalBars = waveformBars.length;
            var activeBars = Math.floor((progress / 100) * totalBars);
            waveformBars.forEach(function(bar, index) {
                if (index < activeBars) {
                    bar.classList.add('active');
                } else {
                    bar.classList.remove('active');
                }
            });
            waveform.setAttribute('aria-valuenow', Math.round(progress));
            currentTimeSpan.textContent = formatTime(audio.currentTime);
            var track = tracks[currentTrack];
            if (track && track.trackingUrl && !fiveSecondTrackingSent && Math.floor(audio.currentTime) >= 5) {
                fiveSecondTrackingSent = true;
                sendTrackingOnce(track, 5);
            }
        }
        
        // Audio event listeners
        audio.addEventListener("loadedmetadata", function() {
            if (durationSpan) {
                durationSpan.textContent = formatTime(audio.duration);
            }
            updatePlayerState("Ready");
        });
        
        audio.addEventListener("timeupdate", updateWaveformProgress);
        
        audio.addEventListener("ended", function() {
            if (currentTrack < tracks.length - 1) {
                loadTrack(currentTrack + 1);
                setTimeout(function() {
                    audio.play().catch(function(error) {
                        console.error("Auto-play failed:", error);
                        updatePlayerState("Ready");
                        updatePlayPauseButton(false);
                    });
                }, 100);
            } else {
                updatePlayPauseButton(false);
                updatePlayerState("Finished");
                waveformBars.forEach(function(bar) {
                    bar.classList.remove('active');
                });
                currentTimeSpan.textContent = "0:00";
                isPlaying = false;
            }
        });
        
        audio.addEventListener("play", function() {
            isPlaying = true;
            updatePlayPauseButton(true);
            updatePlayerState("Playing");
            var track = tracks[currentTrack];
            if (track.trackingUrl) {
                sendTrackingOnce(track, 0);
            }
        });
        
        audio.addEventListener("pause", function() {
            isPlaying = false;
            updatePlayPauseButton(false);
            updatePlayerState("Paused");
        });
        
        audio.addEventListener("waiting", function() {
            updatePlayerState("Buffering...");
        });
        
        audio.addEventListener("canplay", function() {
            if (!isPlaying) {
                updatePlayerState("Ready");
            }
        });
        
        audio.addEventListener("error", function() {
            updatePlayerState("Error");
            console.error("Audio error:", audio.error);
        });
        
        // Play/Pause button
        playPauseBtn.addEventListener("click", function() {
            if (audio.paused) {
                checkAudioStatus(function(canPlay) {
                    if (canPlay) {
                        audio.play().catch(function(error) {
                            console.error("Play failed:", error);
                            updatePlayerState("Error");
                        });
                    } else {
                        updatePlayPauseButton(false);
                    }
                });
            } else {
                audio.pause();
            }
        });
        
        // Waveform click for seeking
        function handleWaveformClick(e) {
            if (!isSeekingAllowed()) return;
            
            var rect = waveform.getBoundingClientRect();
            var clickX = e.clientX - rect.left;
            var width = rect.width;
            var clickPercent = Math.max(0, Math.min(1, clickX / width));
            
            if (audio.duration) {
                audio.currentTime = clickPercent * audio.duration;
            }
        }
        
        waveform.addEventListener("click", handleWaveformClick);
        
        // Waveform dragging
        waveform.addEventListener("mousedown", function(e) {
            if (!isSeekingAllowed()) return;
            isDragging = true;
            handleWaveformClick(e);
        });
        
        document.addEventListener("mousemove", function(e) {
            if (isDragging && isSeekingAllowed()) {
                var rect = waveform.getBoundingClientRect();
                var clickX = e.clientX - rect.left;
                var width = rect.width;
                var clickPercent = Math.max(0, Math.min(1, clickX / width));
                
                var totalBars = waveformBars.length;
                var activeBars = Math.floor(clickPercent * totalBars);
                
                waveformBars.forEach(function(bar, index) {
                    if (index < activeBars) {
                        bar.classList.add('active');
                    } else {
                        bar.classList.remove('active');
                    }
                });
                
                if (audio.duration) {
                    currentTimeSpan.textContent = formatTime(clickPercent * audio.duration);
                }
            }
        });
        
        document.addEventListener("mouseup", function(e) {
            if (isDragging) {
                isDragging = false;
                if (isSeekingAllowed()) {
                    var rect = waveform.getBoundingClientRect();
                    var clickX = e.clientX - rect.left;
                    var width = rect.width;
                    var clickPercent = Math.max(0, Math.min(1, clickX / width));
                    
                    if (audio.duration) {
                        audio.currentTime = clickPercent * audio.duration;
                    }
                }
            }
        });
        
        // Volume button - toggle popup
        if (volumeBtn) {
            volumeBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                toggleVolumePopup();
            });
        }
        
        // Close volume popup when clicking outside
        document.addEventListener("click", function(e) {
            if (volumeControl && !volumeControl.contains(e.target)) {
                closeVolumePopup();
            }
        });
        
        // Volume slider
        if (volumeInput) {
            volumeInput.addEventListener("input", function() {
                var value = volumeInput.value;
                audio.volume = value / 100;
                isMuted = value == 0;
                if (!isMuted) {
                    lastVolume = value;
                }
                updateVolumeIcon();
                updateVolumeFill(value);
            });
            
            // Prevent popup from closing when interacting with slider
            volumeInput.addEventListener("click", function(e) {
                e.stopPropagation();
            });
        }
        
        // Keyboard support for player
        playerContainer.addEventListener("keydown", function(e) {
            switch(e.key) {
                case " ":
                case "Enter":
                    e.preventDefault();
                    playPauseBtn.click();
                    break;
                case "ArrowLeft":
                    e.preventDefault();
                    if (isSeekingAllowed() && audio.duration) {
                        audio.currentTime = Math.max(0, audio.currentTime - 10);
                    }
                    break;
                case "ArrowRight":
                    e.preventDefault();
                    if (isSeekingAllowed() && audio.duration) {
                        audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
                    }
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    if (volumeInput) {
                        var newVal = Math.min(100, parseInt(volumeInput.value) + 10);
                        volumeInput.value = newVal;
                        audio.volume = newVal / 100;
                        isMuted = false;
                        lastVolume = newVal;
                        updateVolumeIcon();
                        updateVolumeFill(newVal);
                    }
                    break;
                case "ArrowDown":
                    e.preventDefault();
                    if (volumeInput) {
                        var newVal = Math.max(0, parseInt(volumeInput.value) - 10);
                        volumeInput.value = newVal;
                        audio.volume = newVal / 100;
                        isMuted = newVal == 0;
                        updateVolumeIcon();
                        updateVolumeFill(newVal);
                    }
                    break;
                case "m":
                case "M":
                    e.preventDefault();
                    // Toggle mute
                    if (isMuted) {
                        isMuted = false;
                        audio.volume = lastVolume / 100;
                        if (volumeInput) {
                            volumeInput.value = lastVolume;
                            updateVolumeFill(lastVolume);
                        }
                    } else {
                        isMuted = true;
                        lastVolume = volumeInput ? volumeInput.value : audio.volume * 100;
                        audio.volume = 0;
                        if (volumeInput) {
                            volumeInput.value = 0;
                            updateVolumeFill(0);
                        }
                    }
                    updateVolumeIcon();
                    break;
                case "Escape":
                    closeVolumePopup();
                    break;
            }
        });
        
        // Touch support for mobile
        var touchStartX = 0;
        waveform.addEventListener("touchstart", function(e) {
            if (!isSeekingAllowed()) return;
            e.preventDefault();
            isDragging = true;
            touchStartX = e.touches[0].clientX;
            var rect = waveform.getBoundingClientRect();
            var clickX = touchStartX - rect.left;
            var width = rect.width;
            var clickPercent = Math.max(0, Math.min(1, clickX / width));
            
            if (audio.duration) {
                audio.currentTime = clickPercent * audio.duration;
            }
        });
        
        waveform.addEventListener("touchmove", function(e) {
            if (isDragging && isSeekingAllowed()) {
                e.preventDefault();
                var rect = waveform.getBoundingClientRect();
                var clickX = e.touches[0].clientX - rect.left;
                var width = rect.width;
                var clickPercent = Math.max(0, Math.min(1, clickX / width));
                
                var totalBars = waveformBars.length;
                var activeBars = Math.floor(clickPercent * totalBars);
                
                waveformBars.forEach(function(bar, index) {
                    if (index < activeBars) {
                        bar.classList.add('active');
                    } else {
                        bar.classList.remove('active');
                    }
                });
                
                if (audio.duration) {
                    currentTimeSpan.textContent = formatTime(clickPercent * audio.duration);
                }
            }
        });
        
        waveform.addEventListener("touchend", function(e) {
            if (isDragging) {
                e.preventDefault();
                isDragging = false;
                if (isSeekingAllowed()) {
                    var rect = waveform.getBoundingClientRect();
                    var clickX = touchStartX - rect.left;
                    var width = rect.width;
                    var clickPercent = Math.max(0, Math.min(1, clickX / width));
                    
                    if (audio.duration) {
                        audio.currentTime = clickPercent * audio.duration;
                    }
                }
            }
        });
        
        // Initialize volume icon and fill
        updateVolumeIcon();
        updateVolumeFill(lastVolume);
        
        // Don't auto-load tracks - wait for listen button click
        // Only disable if no tracks available
        if (tracks.length === 0) {
            if (listenBtnContainer) {
                listenBtnContainer.style.opacity = '0.5';
                listenBtnContainer.style.cursor = 'not-allowed';
                listenBtnContainer.style.pointerEvents = 'none';
            }
        }

        function applyTranslationsToDom(t) {
            if (!t) return;
            var listenText = listenBtnContainer ? listenBtnContainer.querySelector(".echoads-listen-text") : null;
            if (listenText) listenText.textContent = t.listenToArticle;
            if (listenBtnContainer) listenBtnContainer.setAttribute("aria-label", t.listenToArticleAria);
            if (playerContainer) playerContainer.setAttribute("aria-label", t.audioPlayerAria);
            if (playPauseBtn) {
                playPauseBtn.setAttribute("title", t.playPauseTitle);
                playPauseBtn.setAttribute("aria-label", isPlaying ? t.pause : t.play);
            }
            if (waveform) waveform.setAttribute("aria-label", t.audioProgressAria);
            if (volumeBtn) {
                volumeBtn.setAttribute("title", t.volumeTitle);
                volumeBtn.setAttribute("aria-label", t.volumeAria);
            }
            if (volumeInput) volumeInput.setAttribute("aria-label", t.volumeLevelAria);
        }

        fetchPlayerConfig(audioData.configEndpoint, audioData.apiKey).then(function(config) {
            var langCode = (config.language && config.language.code) ? String(config.language.code).toLowerCase() : "en";
            var isRtl = langCode === "ar";
            var playbackSetting = (config.defaultPlaybackSetting || "CLICK_TO_PLAY").toUpperCase();
            var isAutoplay = playbackSetting === "AUTOPLAY";

            translationsMap = TRANSLATIONS[langCode] || TRANSLATIONS.en;
            if (wrapper) {
                if (isRtl) {
                    wrapper.setAttribute("dir", "rtl");
                    wrapper.classList.add("echoads-rtl");
                } else {
                    wrapper.setAttribute("dir", "ltr");
                    wrapper.classList.remove("echoads-rtl");
                }
                wrapper.classList.remove("echoads-hidden");
                wrapper.removeAttribute("data-echoads-waiting-config");
            }
            applyTranslationsToDom(translationsMap);
            updatePlayPauseButton(isPlaying);

            if (isAutoplay && tracks.length > 0) {
                showPlayer();
                checkAudioStatus(function(canPlay) {
                    if (canPlay) {
                        if (!audio.src) loadTrack(0);
                        playWhenAudioReady();
                    }
                });
            }
        });
    }
};

})();

// Process any players that were registered before this script loaded (avoids race condition)
(function() {
    var pending = window.EchoAdsAudioPlayersPendingInit;
    if (pending && pending.length) {
        window.EchoAdsAudioPlayersPendingInit = [];
        pending.forEach(function(playerId) {
            if (window.EchoAdsAudioController && window.EchoAdsAudioPlayers[playerId]) {
                window.EchoAdsAudioController.init(playerId);
            }
        });
    }
})();
