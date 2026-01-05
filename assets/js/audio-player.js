window.EchoAdsAudioController = {
    init: function(playerId) {
        var audioData = window.EchoAdsAudioPlayers[playerId];
        if (!audioData) return;
        
        // Get all elements
        var audio = document.getElementById(playerId + "-audio");
        var playPauseBtn = document.getElementById(playerId + "-play-pause");
        var previousBtn = document.getElementById(playerId + "-previous");
        var nextBtn = document.getElementById(playerId + "-next");
        var progressBar = document.getElementById(playerId + "-progress");
        var progressFill = document.getElementById(playerId + "-fill");
        var progressHandle = document.getElementById(playerId + "-handle");
        var currentTimeSpan = document.getElementById(playerId + "-current-time");
        var durationSpan = document.getElementById(playerId + "-duration");
        var trackDisplay = document.getElementById(playerId + "-track");
        var statusDisplay = document.getElementById(playerId + "-status");
        var volumeBtn = document.getElementById(playerId + "-volume-btn");
        var volumeInput = document.getElementById(playerId + "-volume-input");
        var playIcon = playPauseBtn ? playPauseBtn.querySelector(".play-icon") : null;
        var pauseIcon = playPauseBtn ? playPauseBtn.querySelector(".pause-icon") : null;
        var playerContainer = document.getElementById(playerId);
        
        if (!playerContainer) {
            console.error("Audio player container not found for", playerId);
            return;
        }
        
        // Define updatePlayerState function early so it can be used in status checking
        function updatePlayerState(state) {
            if (statusDisplay) {
                statusDisplay.textContent = state;
            }
            if (playerContainer) {
                playerContainer.className = playerContainer.className.replace(/\s*(loading|playing|paused)/g, '');
                if (state === "Loading...") {
                    playerContainer.classList.add('loading');
                } else if (state === "Playing") {
                    playerContainer.classList.add('playing');
                } else if (state === "Paused") {
                    playerContainer.classList.add('paused');
                }
            }
        }
        
        // Check audio status before initializing player
        if (audioData.statusEndpoint && audioData.externalId) {
            checkAudioStatusAndInit();
        } else {
            // If no status endpoint, proceed with initialization (backward compatibility)
            initializePlayer();
        }
        
        function checkAudioStatusAndInit() {
            updatePlayerState("Checking status...");
            
            // Make request to check status
            var xhr = new XMLHttpRequest();
            xhr.open('GET', audioData.statusEndpoint, true);
            xhr.setRequestHeader('x-api-key', audioData.apiKey || '');
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data && response.data.audioStatus) {
                            var audioStatus = response.data.audioStatus;
                            
                            if (audioStatus === 'COMPLETED') {
                                // Status is COMPLETED, proceed with initialization
                                initializePlayer();
                            } else if (audioStatus === 'PENDING' || audioStatus === 'PROCESSING') {
                                // Show generating message
                                updatePlayerState("Generating audio...");
                                if (statusDisplay) {
                                    statusDisplay.textContent = "Audio is being generated. Please refresh the page to check status again.";
                                }
                                if (playPauseBtn) {
                                    playPauseBtn.disabled = true;
                                }
                            } else if (audioStatus === 'FAILED' || audioStatus === 'SKIPPED') {
                                // Show error message
                                updatePlayerState("Error");
                                if (statusDisplay) {
                                    statusDisplay.textContent = "Audio generation " + audioStatus.toLowerCase() + ".";
                                }
                                if (playPauseBtn) {
                                    playPauseBtn.disabled = true;
                                }
                                playerContainer.style.opacity = '0.6';
                            } else {
                                // Unknown status, proceed with initialization anyway
                                console.warn("Unknown audio status:", audioStatus);
                                initializePlayer();
                            }
                        } else {
                            // Invalid response format, proceed with initialization
                            console.warn("Invalid status response format");
                            initializePlayer();
                        }
                    } catch (e) {
                        console.error("Error parsing status response:", e);
                        // On error, proceed with initialization
                        initializePlayer();
                    }
                } else {
                    // HTTP error, proceed with initialization (backward compatibility)
                    console.warn("Status check failed with HTTP", xhr.status);
                    initializePlayer();
                }
            };
            
            xhr.onerror = function() {
                console.error("Error checking audio status");
                // On error, proceed with initialization (backward compatibility)
                initializePlayer();
            };
            
            xhr.send();
        }
        
        function initializePlayer() {
            if (!audio || !playPauseBtn || !progressBar) {
                console.error("Audio player elements not found for", playerId);
                return;
            }
        
        var currentTrack = 0;
        var isPlaying = false;
        var isDragging = false;
        var tracks = [
            { url: audioData.preRoll, name: "Pre-Roll Ad", trackingUrl: audioData.prerollTrackingUrl, campaignAudioId: audioData.preRollAudioId, allowSeeking: false },
            { url: audioData.article, name: "Article Audio", trackingUrl: null, campaignAudioId: audioData.articleAudioId, allowSeeking: true },
            { url: audioData.postRoll, name: "Post-Roll Ad", trackingUrl: audioData.postrollTrackingUrl, campaignAudioId: audioData.postRollAudioId, allowSeeking: false }
        ].filter(track => track.url); // Filter out empty URLs
        
        // Initialize volume
        if (volumeInput) {
            audio.volume = volumeInput.value / 100;
        }
        
        function loadTrack(index) {
            if (index >= tracks.length || index < 0) return;
            
            currentTrack = index;
            updatePlayerState("Loading...");
            updateProgressBarState();
            
            audio.src = tracks[index].url;
            trackDisplay.textContent = tracks[index].name;
            audio.load();
            
            // Update navigation buttons
            if (previousBtn) {
                previousBtn.disabled = currentTrack === 0;
                previousBtn.style.opacity = currentTrack === 0 ? '0.5' : '1';
            }
            if (nextBtn) {
                nextBtn.disabled = currentTrack === tracks.length - 1;
                nextBtn.style.opacity = currentTrack === tracks.length - 1 ? '0.5' : '1';
            }
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
                } else {
                    playIcon.style.display = "block";
                    pauseIcon.style.display = "none";
                }
            }
        }
        
        function isSeekingAllowed() {
            return tracks[currentTrack] && tracks[currentTrack].allowSeeking;
        }
        
        function updateProgressBarState() {
            var seekingAllowed = isSeekingAllowed();
            if (progressBar) {
                progressBar.style.cursor = seekingAllowed ? 'pointer' : 'default';
                progressBar.style.opacity = seekingAllowed ? '1' : '0.7';
                progressBar.setAttribute('data-seeking-disabled', seekingAllowed ? 'false' : 'true');
            }
        }
        
        function updateProgress() {
            if (isDragging) return;
            
            var progress = (audio.currentTime / audio.duration) * 100;
            if (isNaN(progress)) progress = 0;
            
            progressFill.style.width = progress + "%";
            if (progressHandle) {
                progressHandle.style.right = (100 - progress) + "%";
            }
            currentTimeSpan.textContent = formatTime(audio.currentTime);
        }
        
        // Audio event listeners
        audio.addEventListener("loadedmetadata", function() {
            durationSpan.textContent = formatTime(audio.duration);
            updatePlayerState("Ready");
        });
        
        audio.addEventListener("timeupdate", updateProgress);
        
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
                progressFill.style.width = "0%";
                if (progressHandle) {
                    progressHandle.style.right = "100%";
                }
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
        
        // Control event listeners
        playPauseBtn.addEventListener("click", function() {
            if (audio.paused) {
                audio.play().catch(function(error) {
                    console.error("Play failed:", error);
                    updatePlayerState("Error");
                });
            } else {
                audio.pause();
            }
        });
        
        if (previousBtn) {
            previousBtn.addEventListener("click", function() {
                if (currentTrack > 0) {
                    loadTrack(currentTrack - 1);
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener("click", function() {
                if (currentTrack < tracks.length - 1) {
                    loadTrack(currentTrack + 1);
                }
            });
        }
        
        // Progress bar interaction
        function handleProgressClick(e) {
            if (!isSeekingAllowed()) return;
            
            var rect = progressBar.getBoundingClientRect();
            var clickX = e.clientX - rect.left;
            var width = rect.width;
            var clickPercent = Math.max(0, Math.min(1, clickX / width));
            
            if (audio.duration) {
                audio.currentTime = clickPercent * audio.duration;
            }
        }
        
        progressBar.addEventListener("click", handleProgressClick);
        
        // Progress bar dragging
        progressBar.addEventListener("mousedown", function(e) {
            if (!isSeekingAllowed()) return;
            isDragging = true;
            handleProgressClick(e);
        });
        
        document.addEventListener("mousemove", function(e) {
            if (isDragging && isSeekingAllowed()) {
                var rect = progressBar.getBoundingClientRect();
                var clickX = e.clientX - rect.left;
                var width = rect.width;
                var clickPercent = Math.max(0, Math.min(1, clickX / width));
                
                var progress = clickPercent * 100;
                progressFill.style.width = progress + "%";
                if (progressHandle) {
                    progressHandle.style.right = (100 - progress) + "%";
                }
                
                if (audio.duration) {
                    currentTimeSpan.textContent = formatTime(clickPercent * audio.duration);
                }
            }
        });
        
        document.addEventListener("mouseup", function(e) {
            if (isDragging) {
                isDragging = false;
                if (isSeekingAllowed()) {
                    var rect = progressBar.getBoundingClientRect();
                    var clickX = e.clientX - rect.left;
                    var width = rect.width;
                    var clickPercent = Math.max(0, Math.min(1, clickX / width));
                    
                    if (audio.duration) {
                        audio.currentTime = clickPercent * audio.duration;
                    }
                }
            }
        });
        
        // Volume control
        if (volumeInput) {
            volumeInput.addEventListener("input", function() {
                audio.volume = volumeInput.value / 100;
            });
        }
        
        // Keyboard support
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
                        volumeInput.value = Math.min(100, parseInt(volumeInput.value) + 10);
                        audio.volume = volumeInput.value / 100;
                    }
                    break;
                case "ArrowDown":
                    e.preventDefault();
                    if (volumeInput) {
                        volumeInput.value = Math.max(0, parseInt(volumeInput.value) - 10);
                        audio.volume = volumeInput.value / 100;
                    }
                    break;
            }
        });
        
        // Make player focusable for keyboard navigation
        playerContainer.setAttribute("tabindex", "0");
        
        // Touch support for mobile
        var touchStartX = 0;
        progressBar.addEventListener("touchstart", function(e) {
            if (!isSeekingAllowed()) return;
            e.preventDefault();
            isDragging = true;
            touchStartX = e.touches[0].clientX;
            var rect = progressBar.getBoundingClientRect();
            var clickX = touchStartX - rect.left;
            var width = rect.width;
            var clickPercent = Math.max(0, Math.min(1, clickX / width));
            
            if (audio.duration) {
                audio.currentTime = clickPercent * audio.duration;
            }
        });
        
        progressBar.addEventListener("touchmove", function(e) {
            if (isDragging && isSeekingAllowed()) {
                e.preventDefault();
                var rect = progressBar.getBoundingClientRect();
                var clickX = e.touches[0].clientX - rect.left;
                var width = rect.width;
                var clickPercent = Math.max(0, Math.min(1, clickX / width));
                
                var progress = clickPercent * 100;
                progressFill.style.width = progress + "%";
                if (progressHandle) {
                    progressHandle.style.right = (100 - progress) + "%";
                }
                
                if (audio.duration) {
                    currentTimeSpan.textContent = formatTime(clickPercent * audio.duration);
                }
            }
        });
        
        progressBar.addEventListener("touchend", function(e) {
            if (isDragging) {
                e.preventDefault();
                isDragging = false;
                if (isSeekingAllowed()) {
                    var rect = progressBar.getBoundingClientRect();
                    var clickX = touchStartX - rect.left;
                    var width = rect.width;
                    var clickPercent = Math.max(0, Math.min(1, clickX / width));
                    
                    if (audio.duration) {
                        audio.currentTime = clickPercent * audio.duration;
                    }
                }
            }
        });
        
        // Initialize the first track
        if (tracks.length > 0) {
            loadTrack(0);
        } else {
            updatePlayerState("No audio available");
            playPauseBtn.disabled = true;
        }
        }
    }
};