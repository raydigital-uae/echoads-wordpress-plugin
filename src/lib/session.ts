import FingerprintJS from '@fingerprintjs/fingerprintjs';

const SESSION_STORAGE_KEY = 'echoads_play_session_id';

export const getOrCreatePlaySessionId = (): string => {
  let playSessionId: string | null = null;
  
  try {
    playSessionId = sessionStorage.getItem(SESSION_STORAGE_KEY);
  } catch (e) {
    console.error('SessionStorage not available:', e);
  }
  
  if (!playSessionId) {
    playSessionId = typeof crypto !== 'undefined' && crypto.randomUUID
      ? crypto.randomUUID()
      : 'echoads-' + Date.now() + '-' + Math.random().toString(36).substring(2, 15);
    
    try {
      sessionStorage.setItem(SESSION_STORAGE_KEY, playSessionId);
    } catch (e) {
      console.error('Failed to save session ID:', e);
    }
  }
  
  return playSessionId;
};

let visitorIdPromise: Promise<string | null> | null = null;

export const getVisitorId = (): Promise<string | null> => {
  if (visitorIdPromise === null) {
    visitorIdPromise = FingerprintJS.load()
      .then((agent) => {
        return agent.get();
      })
      .then((result) => {
        return result.visitorId || null;
      })
      .catch((error) => {
        console.error('Failed to get visitor ID:', error);
        return null;
      });
  }
  
  return visitorIdPromise;
};
