import React, { useState, useCallback } from 'react';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { formatCurrency } from '../../../lib/currencyUtils';
import { useOrderContext } from '../../../checkout/context/OrderContext';

const InvoiceGenerator = ({ orderId }: { orderId?: string }) => {
  const { order, isLoading } = useOrderContext();
  const [pdfGenerated, setPdfGenerated] = useState(false);
  const [pdfError, setPdfError] = useState<string | null>(null);

  const generateInvoice = useCallback(async () => {
    if (!order) {
      setPdfError('Order not found');
      return;
    }

    try {
      const doc = new jsPDF();

      // Title
      doc.setFontSize(18);
      doc.setTextColor(0, 51, 102);
      doc.text('Eventiq - Tax Invoice', doc.internal.pageSize.getWidth() / 2, 20, { align: 'center' });

      // Order info table
      doc.setFontSize(10);
      doc.setTextColor(0, 0, 0);
      const invoiceInfo = [
        ['Invoice #', order.id || 'N/A'],
        ['Date', new Date(order.createdAt).toLocaleDateString()],
        ['Payment Method', order.paymentGateway || 'N/A'],
        ['Total Amount', formatCurrency(order.totalAmount, order.currency || 'NGN')],
      ];
      autoTable(doc, {
        startY: 35,
        head: [['Field', 'Value']],
        body: invoiceInfo,
        theme: 'grid',
        headStyles: { fill: ['0, 51, 102'], textColor: 255 },
        columnStyles: { 0: { cellWidth: 50 }, 1: { cellWidth: 'auto' } },
      });

      // Items table
      const items = order.items || [];
      const invoiceHeaders = [
        ['Item', 'Quantity', 'Price', 'Total'],
      ];
      const invoiceBody = items.map((item: any) => [
        item.tierName || 'Ticket',
        item.quantity || 1,
        formatCurrency(item.unitPrice, order.currency || 'NGN'),
        formatCurrency(item.unitPrice * (item.quantity || 1), order.currency || 'NGN'),
      ]);

      autoTable(doc, {
        startY: doc.lastAutoTable?.finalY || 50,
        head: invoiceHeaders,
        body: invoiceBody,
        theme: 'grid',
        headStyles: { fill: ['0, 51, 102'], textColor: 255 },
        columnStyles: {
          0: { cellWidth: 80 },
          1: { cellWidth: 30 },
          2: { cellWidth: 50 },
          3: { cellWidth: 50 },
        },
      });

      // Totals
      const subtotal = items.reduce((sum: number, item: any) => sum + (item.unitPrice || 0) * (item.quantity || 1), 0);
      const tax = subtotal * 0.05; // 5% tax
      const total = subtotal + tax;

      doc.setTextColor(0, 51, 102);
      doc.setFontSize(12);
      doc.text(`Subtotal: ${formatCurrency(subtotal, order.currency || 'NGN')}`, 10, doc.lastAutoTable?.finalY || 60 + 10);
      doc.text(`Tax (5%): ${formatCurrency(tax, order.currency || 'NGN')}`, 10, doc.lastAutoTable?.finalY || 0);
      doc.text(`Total: ${formatCurrency(total, order.currency || 'NGN')}`, 10, doc.lastAutoTable?.finalY || 0 + 10);

      // Save PDF
      doc.save(`invoice-${order.id}.pdf`);
      setPdfGenerated(true);
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to generate invoice';
      setPdfError(message);
      console.error('Invoice generation error:', err);
    }
  }, [order]);

  if (!order) {
    return <div>No order data available</div>;
  }

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl mx-auto">
      <h2 className="text-xl font-bold text-slate-900 mb-6">Invoice</h2>

      <div className="space-y-4">
        <p className="text-slate-500"><strong>Invoice #:</strong> {order.id}</p>
        <p className="text-slate-500"><strong>Date:</strong> {new Date(order.createdAt).toLocaleDateString()}</p>
        <p className="text-slate-500"><strong>Amount:</strong> {formatCurrency(order.totalAmount, order.currency || 'NGN')}</p>
      </div>

      {pdfError && (
        <div className="bg-red-50 border border-red-200 rounded p-3 mb-4">
          <p className="text-red-600 text-sm">{pdfError}</p>
        </div>
      )}

      {!pdfGenerated && (
        <button
          onClick={generateInvoice}
          className="w-full py-3 rounded-md font-medium text-indigo-600 border border-indigo-600 hover:bg-indigo-50 transition-colors"
          disabled={isLoading}
        >
          {isLoading ? 'Generating…' : 'Generate PDF Invoice'}
        </button>
      )}

      {pdfGenerated && (
        <div className="mt-4 p-3 bg-green-50 rounded border border-green-200">
          <p className="text-green-600 text-sm">Invoice PDF generated successfully!</p>
        </div>
      )}
    </div>
  );
};

export default InvoiceGenerator;
