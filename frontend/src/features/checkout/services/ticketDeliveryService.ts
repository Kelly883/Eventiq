/**
 * Client-side ticket delivery helpers.
 *
 * PDF generation is done in-browser via a print-friendly ticket page that the
 * user can "Save as PDF" through the browser's native print dialog. This avoids
 * a heavy server-side PDF dependency while still giving customers a real,
 * downloadable ticket file.
 *
 * Wallet provisioning (Apple Wallet / Google Wallet) requires a server-side
 * pass endpoint; this module calls that endpoint and surfaces loading/error
 * states so the UI can respond gracefully.
 */

const TICKET_PRINT_STYLE = `
  <style>
    @page { size: A4; margin: 12mm; }
    body { font-family: system-ui, -apple-system, sans-serif; color: #111827; padding: 0; margin: 0; }
    .ticket { border: 2px solid #111827; border-radius: 12px; padding: 20px; max-width: 720px; margin: 20px auto; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    .ticket__header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .ticket__title { font-size: 20px; font-weight: 800; letter-spacing: -0.02em; margin: 0; }
    .ticket__tier { display: inline-block; background: #eef2ff; color: #4338ca; border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
    .ticket__meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; font-size: 14px; color: #374151; margin-bottom: 16px; }
    .ticket__meta b { color: #111827; }
    .ticket__qr { width: 160px; height: 160px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; margin-top: 8px; }
    .ticket__footer { margin-top: 16px; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    .no-print { display: none; }
    @media print { body { background: #fff; } .no-print { display: none !important; } }
  </style>
`;

/**
 * Builds a print-friendly ticket HTML document. Returns a string.
 */
export function buildTicketHtml({ ticket, event, ticketTier, qrDataUrl }) {
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[c]));

  return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Ticket - ${esc(ticket.eventTitle)}</title>${TICKET_PRINT_STYLE}</head>
<body>
  <div class="ticket">
    <div class="ticket__header">
      <div>
        <p class="ticket__tier">${esc(ticketTier?.name || 'Ticket')}</p>
        <h1 class="ticket__title">${esc(ticket.eventTitle)}</h1>
      </div>
      <div style="text-align:right;">
        <div style="font-size:11px;color:#6b7280;">TICKET ID</div>
        <div style="font-weight:700;">${esc(ticket.id)}</div>
      </div>
    </div>
    <div class="ticket__meta">
      <div><b>Date</b> ${esc(event?.date || ticket.eventDate)}</div>
      <div><b>Venue</b> ${esc(event?.venue || ticket.eventVenue)}</div>
      <div><b>Status</b> ${esc(ticket.status || 'valid')}</div>
      <div><b>Order</b> ${esc(ticket.orderId || ticket.id)}</div>
    </div>
    <div style="display:flex;align-items:center;gap:20px;">
      ${qrDataUrl ? `<img class="ticket__qr" src="${esc(qrDataUrl)}" alt="Ticket QR code" />` : '<div class="ticket__qr">QR unavailable</div>'}
      <div>
        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">Scan to validate</div>
        <div style="font-family:monospace;font-size:11px;word-break:break-all;">${esc(ticket.qrCodeData || '')}</div>
      </div>
    </div>
    <div class="ticket__footer">
      ${esc(ticket.eventTitle)} &bull; ${esc(ticketTier?.name || 'General Admission')} &bull;
      Valid for one admission. Present this ticket (printed or on your phone) at entry.
    </div>
  </div>
  <button class="no-print" onclick="window.print();return false;" style="margin:20px auto;display:block;padding:10px 20px;background:#111827;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;">Download / Print as PDF</button>
</body>
</html>`;
}

/**
 * Opens the ticket in a new print-friendly window so the user can save as PDF.
 * Returns a Promise that resolves when the print dialog is triggered.
 */
export function downloadTicketPdf({ ticket, event, ticketTier, qrDataUrl }) {
  return new Promise((resolve, reject) => {
    try {
      const html = buildTicketHtml({ ticket, event, ticketTier, qrDataUrl });
      const win = window.open('', '_blank', 'width=800,height=900');
      if (!win) {
        reject(new Error('Popup blocked. Please allow popups for this site.'));
        return;
      }
      win.document.write(html);
      win.document.close();
      // Trigger print after a short delay so fonts render.
      const timer = setTimeout(() => {
        try { win.print(); } catch {}
        resolve();
        win.close();
      }, 250);
      // Safety fallback so the promise never hangs forever.
      setTimeout(() => resolve(), 5000);
    } catch (err) {
      reject(err);
    }
  });
}

/**
 * Provisions a mobile wallet pass (Apple Wallet / Google Wallet).
 * Expects a server-side endpoint at /api/tickets/:id/wallet-pass.
 * Returns the pass download URL on success.
 */
export async function addToWallet({ ticketId, baseUrl = '' }) {
  const response = await fetch(`${baseUrl}/api/tickets/${encodeURIComponent(ticketId)}/wallet-pass`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `Wallet provisioning failed (${response.status})`);
  }

  const data = await response.json();
  const passUrl = data.data?.passUrl || data.passUrl || data.data?.url || data.url;
  
  // If we got a pass URL, navigate to it to trigger the download
  if (passUrl) {
    window.location.href = passUrl;
  }
  
  return passUrl;
}
