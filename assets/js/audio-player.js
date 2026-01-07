window.EchoAdsAudioController = {
    init: function(playerId) {
        var audioData = window.EchoAdsAudioPlayers[playerId];
        if (!audioData) return;
        
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
                                updatePlayerState("Error");
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
        
        function updatePlayerState(state) {
            if (statusDisplay) {
                statusDisplay.textContent = state;
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
            updatePlayerState("Loading...");
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
                    playPauseBtn.setAttribute("aria-label", "Pause");
                } else {
                    playIcon.style.display = "block";
                    pauseIcon.style.display = "none";
                    playPauseBtn.setAttribute("aria-label", "Play");
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
                callTrackingEndpoint(track.trackingUrl, audioData.apiKey, track.campaignAudioId);
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
    }
};
