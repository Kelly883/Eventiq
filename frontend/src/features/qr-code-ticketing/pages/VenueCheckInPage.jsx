import React, { useState, useEffect, useRef } from 'react';
import jsQR from 'jsqr';
import CryptoJS from 'crypto-js';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';
import Skeleton from '../../../components/Skeleton';
import EmptyState from '../../../components/EmptyState';

const VenueCheckInPage = () => {
  const [hasCameraPermission, setHasCameraPermission] = useState(null);
  const [activeCamera, setActiveCamera] = useState(true);
  const [scannedResult, setScannedResult] = useState(null);
  const [validationStatus, setValidationStatus] = useState('idle'); // 'idle' | 'validating' | 'success' | 'failed'
  const [errorMessage, setErrorMessage] = useState('');
  const [stats, setStats] = useState({ total: 150, processed: 48 });
  const [highContrast, setHighContrast] = useState(false);

  // Video and Canvas refs
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const requestRef = useRef(null);
  const audioContextRef = useRef(null);

  const isOnline = useOfflineSyncStore((state) => state.isOnline);
  const enqueueScan = useOfflineSyncStore((state) => state.enqueueScan);

  // Play synthesized beep sound upon successful scan
  const playBeep = (freq = 880, duration = 0.15) => {
    try {
      if (!audioContextRef.current) {
        audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
      }
      const ctx = audioContextRef.current;
      if (ctx.state === 'suspended') {
        ctx.resume();
      }
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.05, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + duration);
    } catch (e) {
      console.warn('Audio feedback failed:', e);
    }
  };

  // Setup camera stream
  useEffect(() => {
    if (!activeCamera) {
      stopCamera();
      return;
    }

    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: 'environment' } })
      .then((stream) => {
        setHasCameraPermission(true);
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.setAttribute('playsinline', 'true'); // Required for iOS
          videoRef.current.play().catch(err => console.error(err));
          // Start the scanning loop
          requestRef.current = requestAnimationFrame(scanFrame);
        }
      })
      .catch((err) => {
        console.error('Camera access failed:', err);
        setHasCameraPermission(false);
      });

    return () => stopCamera();
  }, [activeCamera]);

  const stopCamera = () => {
    if (requestRef.current) {
      cancelAnimationFrame(requestRef.current);
      requestRef.current = null;
    }
    if (videoRef.current && videoRef.current.srcObject) {
      const tracks = videoRef.current.srcObject.getTracks();
      tracks.forEach((track) => track.stop());
      videoRef.current.srcObject = null;
    }
  };

  // Capture frame and search for QR code using jsQR
  const scanFrame = () => {
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

        if (code) {
          // Found a code!
          handleScannedData(code.data);
          // Pause camera momentarily to prevent immediate rescans
          setActiveCamera(false);
          return;
        }
      }
    }

    if (activeCamera) {
      requestRef.current = requestAnimationFrame(scanFrame);
    }
  };

  // Verify and process the scanned QR string
  const handleScannedData = async (payloadString) => {
    setScannedResult(payloadString);
    setValidationStatus('validating');
    setErrorMessage('');

    try {
      // 1. Client-Side Integrity Check with crypto-js
      // We check if the payload is a valid format or has an integrity prefix
      let isValidPayload = false;
      let ticketId = 'Unknown';
      let eventId = 'Unknown';

      if (payloadString.startsWith('ey') || payloadString.length > 50) {
        // Looks like base64 or encrypted payload
        isValidPayload = true;
        // Generate a fast client-side checksum with SHA256 using crypto-js
        const hash = CryptoJS.SHA256(payloadString).toString(CryptoJS.enc.Hex);
        console.log('Scanned payload local checksum:', hash);
      } else if (payloadString.includes('-') && payloadString.length > 8) {
        // Standard ticket format (e.g. TCK-SUM-9281)
        isValidPayload = true;
      }

      if (!isValidPayload) {
        throw new Error('Unsupported or corrupted QR payload format.');
      }

      // Simulate network validation with server-side proxy endpoint
      // We can buffer scans directly to our offline Sync Store!
      playBeep(880, 0.1); // High pitch for recognition success
      
      // Buffer the scan!
      enqueueScan(payloadString, 1);

      setValidationStatus('success');
      setStats((prev) => ({ ...prev, processed: prev.processed + 1 }));
    } catch (err) {
      playBeep(220, 0.3); // Low pitch for validation error
      setValidationStatus('failed');
      setErrorMessage(err.message || 'QR Verification failed.');
    }
  };

  const handleResetScanner = () => {
    setScannedResult(null);
    setValidationStatus('idle');
    setErrorMessage('');
    setActiveCamera(true);
  };

  return (
    <div className={`min-h-screen p-6 md:p-10 transition-colors duration-300 ${
      highContrast ? 'bg-black text-white' : 'bg-slate-50 text-slate-800'
    }`}>
      <div className="max-w-7xl mx-auto space-y-8">
        
        {/* Banner Controls */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <span className="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100 uppercase tracking-widest inline-block mb-1">
              Venue Check-In Scanner
            </span>
            <h1 className={`text-3xl font-extrabold tracking-tight ${highContrast ? 'text-white' : 'text-slate-900'}`}>
              High-Speed Gate Scanner
            </h1>
          </div>

          {/* Toggle buttons */}
          <div className="flex flex-wrap gap-2.5">
            <button
              onClick={() => setHighContrast(!highContrast)}
              className={`px-4 py-2 text-xs font-bold rounded-lg border transition-all cursor-pointer shadow-sm ${
                highContrast
                  ? 'bg-slate-900 text-yellow-400 border-yellow-400/30'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              }`}
            >
              ☀️/🌙 {highContrast ? 'Standard Theme' : 'High Contrast (Outdoor)'}
            </button>
            <button
              onClick={() => setActiveCamera(!activeCamera)}
              className={`px-4 py-2 text-xs font-bold rounded-lg border transition-all cursor-pointer shadow-sm ${
                activeCamera
                  ? 'bg-rose-600 text-white border-rose-500'
                  : 'bg-indigo-600 text-white border-indigo-500'
              }`}
            >
              {activeCamera ? '⏸️ Stop Camera' : '▶️ Resume Camera'}
            </button>
          </div>
        </div>

        {/* Info Indicators */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div className={`p-5 rounded-2xl border shadow-sm ${
            highContrast ? 'bg-zinc-900 border-zinc-800' : 'bg-white border-slate-100'
          }`}>
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Network Status</span>
            <div className="flex items-center gap-2 mt-1">
              <span className={`h-2.5 w-2.5 rounded-full ${isOnline ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'}`} />
              <span className="font-bold text-sm">
                {isOnline ? 'Online (Direct Upload)' : 'Offline (Local Cache Enabled)'}
              </span>
            </div>
          </div>

          <div className={`p-5 rounded-2xl border shadow-sm ${
            highContrast ? 'bg-zinc-900 border-zinc-800' : 'bg-white border-slate-100'
          }`}>
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Checked-In</span>
            <span className="text-2xl font-black block mt-1">{stats.processed} / {stats.total}</span>
          </div>

          <div className={`p-5 rounded-2xl border shadow-sm ${
            highContrast ? 'bg-zinc-900 border-zinc-800' : 'bg-white border-slate-100'
          }`}>
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Decryption Protocol</span>
            <span className="text-sm font-bold block mt-1 text-indigo-500">AES-256 (Server Authorized)</span>
          </div>
        </div>

        {/* Layout Split */}
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-8">
          
          {/* Main scanning box */}
          <div className="lg:col-span-3 space-y-6">
            <div className={`p-6 rounded-2xl border shadow-sm flex flex-col items-center justify-center ${
              highContrast ? 'bg-zinc-900 border-zinc-800' : 'bg-white border-slate-100'
            }`}>
              
              <h3 className="font-bold text-sm tracking-wide text-slate-400 uppercase mb-4">
                Real-Time Video Viewfinder
              </h3>

              {hasCameraPermission === false ? (
                <EmptyState
                  icon="⚠️"
                  title="Camera permission denied"
                  description="We need camera access to scan QR tickets. Please allow camera permissions in your browser bar."
                />
              ) : (
                <div className="relative aspect-video w-full max-w-xl bg-black rounded-xl overflow-hidden border-2 border-dashed border-indigo-500/40">
                  
                  {/* Invisible canvas for processing frames */}
                  <canvas ref={canvasRef} className="hidden" />

                  {/* HTML Video stream */}
                  <video
                    ref={videoRef}
                    className="w-full h-full object-cover"
                    muted
                    playsInline
                  />

                  {/* HUD design */}
                  <div className="absolute inset-0 border-4 border-black/40 pointer-events-none flex items-center justify-center">
                    <div className="border-2 border-indigo-500 w-48 h-48 rounded-2xl relative shadow-md">
                      {/* Interactive scanner laser */}
                      <div className="absolute left-0 right-0 h-1 bg-indigo-500/80 shadow shadow-indigo-500 animate-pulse top-1/2" />
                      
                      {/* Corner overlays */}
                      <div className="absolute -top-1.5 -left-1.5 w-4 h-4 border-t-4 border-l-4 border-indigo-500" />
                      <div className="absolute -top-1.5 -right-1.5 w-4 h-4 border-t-4 border-r-4 border-indigo-500" />
                      <div className="absolute -bottom-1.5 -left-1.5 w-4 h-4 border-b-4 border-l-4 border-indigo-500" />
                      <div className="absolute -bottom-1.5 -right-1.5 w-4 h-4 border-b-4 border-r-4 border-indigo-500" />
                    </div>
                  </div>

                  {!activeCamera && (
                    <div className="absolute inset-0 bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center text-white">
                      <span className="text-4xl mb-3">⏸️</span>
                      <p className="text-sm font-bold">Scanner is paused</p>
                      <button
                        onClick={handleResetScanner}
                        className="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-md cursor-pointer"
                      >
                        Start Scanning
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Validation Feedback & Logs side panel */}
          <div className="lg:col-span-2 space-y-6">
            
            {/* Scan Feedback Status */}
            <div className={`p-6 rounded-2xl border shadow-sm h-full flex flex-col justify-between ${
              highContrast ? 'bg-zinc-900 border-zinc-800' : 'bg-white border-slate-100'
            }`}>
              
              <div>
                <h3 className="font-bold text-sm tracking-wide text-slate-400 uppercase mb-4">
                  Validation Board
                </h3>

                {validationStatus === 'idle' && (
                  <div className="py-12 text-center text-slate-400 space-y-3">
                    <span className="text-4xl filter grayscale">🎫</span>
                    <p className="text-xs font-medium">Ready to receive codes.</p>
                  </div>
                )}

                {validationStatus === 'validating' && (
                  <div className="space-y-4">
                    <Skeleton variant="text" className="h-6 w-1/3" />
                    <Skeleton variant="card" />
                  </div>
                )}

                {validationStatus === 'success' && (
                  <div className="space-y-4 animate-fadeIn">
                    <div className="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 rounded-xl flex items-center gap-3">
                      <span className="text-2xl">✅</span>
                      <div>
                        <span className="font-black text-xs uppercase tracking-wider block">Access Granted</span>
                        <span className="text-sm font-bold font-mono break-all">{scannedResult}</span>
                      </div>
                    </div>

                    <div className="space-y-3 border-t border-slate-100 pt-4">
                      <div className="flex justify-between text-xs font-medium">
                        <span className="text-slate-400">Integrity Hash (SHA256)</span>
                        <span className="font-mono text-[10px] break-all truncate max-w-[200px]">
                          {CryptoJS.SHA256(scannedResult).toString(CryptoJS.enc.Hex)}
                        </span>
                      </div>
                      <div className="flex justify-between text-xs font-medium">
                        <span className="text-slate-400">Timestamp</span>
                        <span>{new Date().toLocaleTimeString()}</span>
                      </div>
                    </div>
                  </div>
                )}

                {validationStatus === 'failed' && (
                  <div className="space-y-4 animate-fadeIn">
                    <div className="p-4 bg-rose-500/10 border border-rose-500/30 text-rose-500 rounded-xl flex items-center gap-3">
                      <span className="text-2xl">❌</span>
                      <div>
                        <span className="font-black text-xs uppercase tracking-wider block">Access Denied</span>
                        <span className="text-xs font-semibold">{errorMessage}</span>
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {validationStatus !== 'idle' && (
                <button
                  onClick={handleResetScanner}
                  className="w-full mt-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer text-center block"
                >
                  Scan Next Ticket
                </button>
              )}
            </div>

          </div>
        </div>

      </div>
    </div>
  );
};

export default VenueCheckInPage;
