window.EchoAdsAudioController = {
    init: function(playerId) {
        var audioData = window.EchoAdsAudioPlayers[playerId];
        if (!audioData) return;
        var i18n = window.echoads_i18n || {};
        var isRtl = !!(i18n.isRtl);
        var fallbacks = {
            loading: "Loading...", playing: "Playing", paused: "Paused", error: "Error", ready: "Ready",
            checking_status: "Checking status...", audio_being_generated: "Audio is being generated...",
            status_check_failed: "Status check failed", buffering: "Buffering...", finished: "Finished",
            play: "Play", pause: "Pause", pre_roll_ad: "Pre-Roll Ad", article_audio: "Article Audio",
            post_roll_ad: "Post-Roll Ad", status_label: "Status:", audio_not_ready: "Audio not ready",
            audio_generation: "Audio generation"
        };
        function t(key) { return i18n[key] !== undefined && i18n[key] !== "" ? i18n[key] : (fallbacks[key] || key); }
        function getClickPercent(clientX, rect) {
            var clickX = clientX - rect.left;
            var width = rect.width;
            var pct = Math.max(0, Math.min(1, clickX / width));
            return isRtl ? 1 - pct : pct;
        }
        function getClickPercentFromTouch(touch, rect) {
            var clickX = touch.clientX - rect.left;
            var width = rect.width;
            var pct = Math.max(0, Math.min(1, clickX / width));
            return isRtl ? 1 - pct : pct;
        }
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
        
        var tracks = [
            { url: audioData.preRoll, name: t("pre_roll_ad"), trackingUrl: audioData.prerollTrackingUrl, campaignAudioId: audioData.preRollAudioId, allowSeeking: false },
            { url: audioData.article, name: t("article_audio"), trackingUrl: null, campaignAudioId: audioData.articleAudioId, allowSeeking: true },
            { url: audioData.postRoll, name: t("post_roll_ad"), trackingUrl: audioData.postrollTrackingUrl, campaignAudioId: audioData.postRollAudioId, allowSeeking: false }
        ].filter(function(track) { return track.url; });
        
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
        
        // Listen button container click handler
        if (listenBtnContainer) {
            listenBtnContainer.addEventListener("click", function() {
                showPlayer();
                
                // Check status and start playing
                checkAudioStatus(function(canPlay) {
                    if (canPlay) {
                        if (tracks.length > 0 && !audio.src) {
                            loadTrack(0);
                        }
                        // Small delay to ensure track is loaded
                        setTimeout(function() {
                            audio.play().catch(function(error) {
                                console.error("Play failed:", error);
                                updatePlayerState("error");
                            });
                        }, 100);
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
        
        function updatePlayerState(stateKey) {
            if (statusDisplay) {
                statusDisplay.textContent = t(stateKey);
            }
            playerContainer.className = playerContainer.className.replace(/\s*(loading|playing|paused)/g, '');
            if (!isPlayerVisible) {
                playerContainer.classList.add('echoads-hidden');
            }
            if (stateKey === "loading") {
                playerContainer.classList.add('loading');
            } else if (stateKey === "playing") {
                playerContainer.classList.add('playing');
            } else if (stateKey === "paused") {
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

            updatePlayerState("checking_status");

            if (typeof jQuery === "undefined") {
                console.error("jQuery is required for status check");
                updatePlayerState("error");
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
                        updatePlayerState("ready");
                        if (callback) callback(true);
                    } else {
                        if (status === 'PENDING' || status === 'PROCESSING') {
                            updatePlayerState("audio_being_generated");
                        } else if (status === 'FAILED' || status === 'SKIPPED') {
                            updatePlayerState("audio_generation");
                            if (statusDisplay) statusDisplay.textContent = t("audio_generation") + " " + (status ? status.toLowerCase() : "");
                        } else {
                            updatePlayerState("audio_not_ready");
                        }
                        if (callback) callback(false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error checking audio status:", error);
                    updatePlayerState("status_check_failed");
                    if (callback) callback(true);
                }
            });
        }
        
        function loadTrack(index) {
            if (index >= tracks.length || index < 0) return;
            currentTrack = index;
            updatePlayerState("loading");
            updateWaveformState();
            audio.src = tracks[index].url;
            if (trackDisplay) {
                trackDisplay.textContent = tracks[index].name;
            }
            audio.load();
        }
        
        function callTrackingEndpoint(url, apiKey, campaignAudioId) {
            if (!url || typeof jQuery === "undefined") return;
            
            var ajaxOptions = {
                url: url,
                type: "POST",
                contentType: "application/json",
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
            
            var requestBody = {};
            if (campaignAudioId !== null && campaignAudioId !== undefined) {
                requestBody.campaignAudioId = campaignAudioId;
            }
            
            if (Object.keys(requestBody).length > 0) {
                ajaxOptions.data = JSON.stringify(requestBody);
            }
            
            jQuery.ajax(ajaxOptions);
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds)) return "0:00";
            var minutes = Math.floor(seconds / 60);
            var secs = Math.floor(seconds % 60);
            return minutes + ":" + (secs < 10 ? "0" : "") + secs;
        }
        
        function updatePlayPauseButton(playing) {
            if (playIcon && pauseIcon) {
                if (playing) {
                    playIcon.style.display = "none";
                    pauseIcon.style.display = "block";
                    playPauseBtn.setAttribute("aria-label", t("pause"));
                } else {
                    playIcon.style.display = "block";
                    pauseIcon.style.display = "none";
                    playPauseBtn.setAttribute("aria-label", t("play"));
                }
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
            
            // Update waveform bars based on progress
            var totalBars = waveformBars.length;
            var activeBars = Math.floor((progress / 100) * totalBars);
            
            waveformBars.forEach(function(bar, index) {
                if (index < activeBars) {
                    bar.classList.add('active');
                } else {
                    bar.classList.remove('active');
                }
            });
            
            // Update ARIA value
            waveform.setAttribute('aria-valuenow', Math.round(progress));
            
            currentTimeSpan.textContent = formatTime(audio.currentTime);
        }
        
        // Audio event listeners
        audio.addEventListener("loadedmetadata", function() {
            if (durationSpan) {
                durationSpan.textContent = formatTime(audio.duration);
            }
            updatePlayerState("ready");
        });
        audio.addEventListener("timeupdate", updateWaveformProgress);
        audio.addEventListener("ended", function() {
            if (currentTrack < tracks.length - 1) {
                loadTrack(currentTrack + 1);
                setTimeout(function() {
                    audio.play().catch(function(error) {
                        console.error("Auto-play failed:", error);
                        updatePlayerState("ready");
                        updatePlayPauseButton(false);
                    });
                }, 100);
            } else {
                updatePlayPauseButton(false);
                updatePlayerState("finished");
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
            updatePlayerState("playing");
            var track = tracks[currentTrack];
            if (track && track.trackingUrl) {
                callTrackingEndpoint(track.trackingUrl, audioData.apiKey, track.campaignAudioId);
            }
        });
        audio.addEventListener("pause", function() {
            isPlaying = false;
            updatePlayPauseButton(false);
            updatePlayerState("paused");
        });
        audio.addEventListener("waiting", function() {
            updatePlayerState("buffering");
        });
        audio.addEventListener("canplay", function() {
            if (!isPlaying) {
                updatePlayerState("ready");
            }
        });
        audio.addEventListener("error", function() {
            updatePlayerState("error");
            console.error("Audio error:", audio.error);
        });
        
        // Play/Pause button
        playPauseBtn.addEventListener("click", function() {
            if (audio.paused) {
                checkAudioStatus(function(canPlay) {
                    if (canPlay) {
                        audio.play().catch(function(error) {
                            console.error("Play failed:", error);
                            updatePlayerState("error");
                        });
                    } else {
                        updatePlayPauseButton(false);
                    }
                });
            } else {
                audio.pause();
            }
        });
        // Waveform click for seeking (RTL-aware)
        function handleWaveformClick(e) {
            if (!isSeekingAllowed()) return;
            var rect = waveform.getBoundingClientRect();
            var clickPercent = getClickPercent(e.clientX, rect);
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
                var clickPercent = getClickPercent(e.clientX, rect);
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
                    var clickPercent = getClickPercent(e.clientX, rect);
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
        
        // Touch support for mobile (RTL-aware)
        var touchStartX = 0;
        waveform.addEventListener("touchstart", function(e) {
            if (!isSeekingAllowed()) return;
            e.preventDefault();
            isDragging = true;
            touchStartX = e.touches[0].clientX;
            var rect = waveform.getBoundingClientRect();
            var clickPercent = getClickPercentFromTouch(e.touches[0], rect);
            if (audio.duration) {
                audio.currentTime = clickPercent * audio.duration;
            }
        });
        waveform.addEventListener("touchmove", function(e) {
            if (isDragging && isSeekingAllowed()) {
                e.preventDefault();
                var rect = waveform.getBoundingClientRect();
                var clickPercent = getClickPercentFromTouch(e.touches[0], rect);
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
                    var clickPercent = getClickPercent(touchStartX, rect);
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
    }
};

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
