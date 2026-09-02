import React, { useCallback, useEffect, useRef, useState } from 'react';
import jsQR from 'jsqr';

/*
 * Real camera QR scanner used by the check-in desk.
 *
 * Extracted from the former VenueCheckInPage (qr-code-ticketing) so the
 * unified /check-in desk hosts the actual gate scanner instead of the old
 * simulated viewfinder. Deliberately dumb: it detects codes and reports them
 * through `onScan(payloadString)`; validation, beeps and queueing stay with
 * the parent so manual entry and camera scans share one code path.
 *
 * The camera never starts on its own — staff press "Start Camera" (avoids a
 * surprise permission prompt and saves battery at quiet gates).
 */
const CameraScanner = ({ onScan }) => {
  const [activeCamera, setActiveCamera] = useState(false);
  // null = not yet requested, true = granted, false = denied/unavailable
  const [hasPermission, setHasPermission] = useState(null);

  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const requestRef = useRef(null);

  const stopCamera = useCallback(() => {
    if (requestRef.current) {
      cancelAnimationFrame(requestRef.current);
      requestRef.current = null;
    }
    if (videoRef.current && videoRef.current.srcObject) {
      const tracks = videoRef.current.srcObject.getTracks();
      tracks.forEach((track) => track.stop());
      videoRef.current.srcObject = null;
    }
  }, []);

  // Capture a frame and look for a QR code with jsQR. On a hit: pause the
  // camera (prevents immediate rescans of the same badge) and report upward.
  const scanFrame = useCallback(() => {
    const video = videoRef.current;
    const canvas = canvasRef.current;

    if (video && canvas && video.readyState === video.HAVE_ENOUGH_DATA) {
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      if (ctx) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
          inversionAttempts: 'dontInvert',
        });

        if (code && code.data) {
          setActiveCamera(false);
          onScan(code.data);
          return;
        }
      }
    }

    requestRef.current = requestAnimationFrame(scanFrame);
  }, [onScan]);

  useEffect(() => {
    if (!activeCamera) {
      stopCamera();
      return;
    }

    let cancelled = false;

    if (!navigator.mediaDevices?.getUserMedia) {
      setHasPermission(false);
      return;
    }

    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: 'environment' } })
      .then((stream) => {
        if (cancelled) {
          stream.getTracks().forEach((track) => track.stop());
          return;
        }
        setHasPermission(true);
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.setAttribute('playsinline', 'true');
          videoRef.current.play().catch((err) => console.error(err));
          requestRef.current = requestAnimationFrame(scanFrame);
        }
      })
      .catch(() => {
        if (!cancelled) setHasPermission(false);
      });

    return () => {
      cancelled = true;
      stopCamera();
    };
  }, [activeCamera, scanFrame, stopCamera]);

  useEffect(() => stopCamera, [stopCamera]);

  return (
    <div>
      <div className="relative aspect-square w-full rounded-2xl bg-slate-900 overflow-hidden border border-slate-800 shadow-inner">
        <video
          ref={videoRef}
          className={`absolute inset-0 h-full w-full object-cover ${activeCamera && hasPermission ? '' : 'opacity-0'}`}
          muted
          playsInline
        />
        <canvas ref={canvasRef} className="hidden" aria-hidden="true" />

        {/* Viewfinder guides — visible whenever the preview is live */}
        {activeCamera && hasPermission && (
          <>
            <div className="absolute inset-4 border-2 border-dashed border-white/40 rounded-xl pointer-events-none" />
            <div className="absolute left-0 right-0 h-1 bg-rose-500 opacity-80 shadow-md shadow-rose-500/50 animate-bounce top-1/2 pointer-events-none" />
          </>
        )}

        {/* Idle: invite the staff member to start scanning */}
        {!activeCamera && hasPermission !== false && (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6">
            <span className="text-4xl opacity-70 select-none">📷</span>
            <p className="text-[10px] font-mono text-slate-400 uppercase tracking-widest text-center">
              {hasPermission === true ? 'Camera paused' : 'Camera ready'}
            </p>
            <button
              type="button"
              onClick={() => setActiveCamera(true)}
              className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg shadow transition-colors"
            >
              ▶ Start Camera
            </button>
          </div>
        )}

        {/* Denied / unavailable: point staff at manual entry instead of a dead end */}
        {hasPermission === false && (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
            <span className="text-4xl opacity-70 select-none">🚫</span>
            <p className="text-xs text-amber-300 font-medium leading-relaxed">
              Camera unavailable or permission denied.
            </p>
            <p className="text-[10px] text-slate-400">
              Use manual ticket entry below — it works exactly the same.
            </p>
            <button
              type="button"
              onClick={() => { setHasPermission(null); setActiveCamera(true); }}
              className="mt-1 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs font-bold rounded-lg border border-slate-600 transition-colors"
            >
              Try again
            </button>
          </div>
        )}

        {/* Live status strip */}
        {activeCamera && hasPermission && (
          <div className="absolute bottom-4 left-4 right-4 bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-lg border border-slate-800 flex items-center justify-between">
            <span className="text-[10px] text-slate-300 font-medium">Scanning for ticket QR codes…</span>
            <button
              type="button"
              onClick={() => setActiveCamera(false)}
              className="text-[10px] font-bold text-rose-300 hover:text-rose-200 uppercase tracking-wider"
            >
              Stop
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default CameraScanner;
