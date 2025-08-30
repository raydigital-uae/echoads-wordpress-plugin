window.EchoAdsAudioController = {
    init: function(playerId) {
        var audioData = window.EchoAdsAudioPlayers[playerId];
        if (!audioData) return;
        
        var audio = document.getElementById(playerId + "-audio");
        var playPauseBtn = document.getElementById(playerId + "-play-pause");
        var progressBar = document.getElementById(playerId + "-progress");
        var progressFill = document.getElementById(playerId + "-fill");
        var currentTimeSpan = document.getElementById(playerId + "-current-time");
        var durationSpan = document.getElementById(playerId + "-duration");
        var trackDisplay = document.getElementById(playerId + "-track");
        
        if (!audio || !playPauseBtn || !progressBar) {
            console.error("Audio player elements not found for", playerId);
            return;
        }
        
        var currentTrack = 0;
        var tracks = [
            { url: audioData.preRoll, name: "Pre-Roll Ad", trackingUrl: audioData.prerollTrackingUrl },
            { url: audioData.article, name: "Article Audio", trackingUrl: null },
            { url: audioData.postRoll, name: "Post-Roll Ad", trackingUrl: audioData.postrollTrackingUrl }
        ];
        
        function loadTrack(index) {
            if (index >= tracks.length) return;
            
            currentTrack = index;
            audio.src = tracks[index].url;
            trackDisplay.textContent = tracks[index].name;
            audio.load();
        }
        
        function callTrackingEndpoint(url) {
            if (!url || typeof jQuery === "undefined") return;
            
            jQuery.ajax({
                url: url,
                type: "POST",
                success: function(response) {
                    console.log("Tracking call successful:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Tracking call failed:", error);
                }
            });
        }
        
        function formatTime(seconds) {
            var minutes = Math.floor(seconds / 60);
            var secs = Math.floor(seconds % 60);
            return minutes + ":" + (secs < 10 ? "0" : "") + secs;
        }
        
        audio.addEventListener("loadedmetadata", function() {
            durationSpan.textContent = formatTime(audio.duration);
        });
        
        audio.addEventListener("timeupdate", function() {
            var progress = (audio.currentTime / audio.duration) * 100;
            progressFill.style.width = progress + "%";
            currentTimeSpan.textContent = formatTime(audio.currentTime);
        });
        
        audio.addEventListener("ended", function() {
            if (currentTrack < tracks.length - 1) {
                loadTrack(currentTrack + 1);
                audio.play();
            } else {
                playPauseBtn.textContent = "▶";
                progressFill.style.width = "0%";
                currentTimeSpan.textContent = "0:00";
            }
        });
        
        audio.addEventListener("play", function() {
            var track = tracks[currentTrack];
            if (track.trackingUrl) {
                callTrackingEndpoint(track.trackingUrl);
            }
        });
        
        playPauseBtn.addEventListener("click", function() {
            if (audio.paused) {
                audio.play();
                playPauseBtn.textContent = "⏸";
            } else {
                audio.pause();
                playPauseBtn.textContent = "▶";
            }
        });
        
        progressBar.addEventListener("click", function(e) {
            var rect = progressBar.getBoundingClientRect();
            var clickX = e.clientX - rect.left;
            var width = rect.width;
            var clickPercent = clickX / width;
            audio.currentTime = clickPercent * audio.duration;
        });
        
        loadTrack(0);
    }
};