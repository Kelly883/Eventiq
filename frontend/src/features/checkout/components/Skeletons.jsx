import React from 'react';

const skeletonBase = 'bg-slate-200 animate-pulse rounded';

export const CheckoutSkeleton = () => (
  <div className="spa-page">
    <div className="spa-container" style={{ maxWidth: 640 }}>
      <div className="spa-page__header">
        <div className={`${skeletonBase} h-8 w-48`} />
        <div className={`${skeletonBase} h-4 w-32`} style={{ marginTop: 8 }} />
      </div>

      <div className="spa-card spa-card--padded" style={{ marginTop: 16 }}>
        <div className={`${skeletonBase} h-6 w-32`} style={{ marginBottom: 8 }} />
        <div className={`${skeletonBase} h-4 w-full`} style={{ marginBottom: 16 }} />
        <div className={`${skeletonBase} h-10 w-full`} />
      </div>
    </div>
  </div>
);

export const CartSkeleton = () => (
  <div className="spa-page">
    <div className="spa-container">
      <div className="spa-page__header">
        <div className={`${skeletonBase} h-8 w-32`} />
      </div>
      <div className="spa-card spa-card--padded" style={{ marginTop: 16 }}>
        <div className={`${skeletonBase} h-6 w-32`} style={{ marginBottom: 16 }} />
        {[1, 2, 3].map((i) => (
          <div key={i} className="flex items-center gap-4 p-4 bg-white border border-slate-100 rounded-xl shadow-sm" style={{ marginBottom: 12 }}>
            <div className={`${skeletonBase} h-10 w-10 rounded-lg`} />
            <div className="flex-1 space-y-2">
              <div className={`${skeletonBase} h-4 w-1/3`} />
              <div className={`${skeletonBase} h-3 w-1/4`} />
            </div>
            <div className={`${skeletonBase} h-6 w-20`} />
          </div>
        ))}
        <div className="flex justify-between items-center" style={{ marginTop: 16, paddingTop: 16, borderTop: '1px solid #E3E4E6' }}>
          <div className={`${skeletonBase} h-5 w-32`} />
          <div className={`${skeletonBase} h-10 w-40`} />
        </div>
      </div>
    </div>
  </div>
);

export const OrderConfirmationSkeleton = () => (
  <div className="min-h-screen bg-slate-50 p-6 md:p-10">
    <div className="mx-auto max-w-3xl">
      <div className={`${skeletonBase} h-8 w-48`} style={{ marginBottom: 16 }} />
      <div className={`${skeletonBase} h-4 w-full`} style={{ marginBottom: 24 }} />
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <div className={`${skeletonBase} h-6 w-32`} style={{ marginBottom: 16 }} />
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="flex justify-between items-baseline" style={{ marginBottom: 12 }}>
            <div className={`${skeletonBase} h-4 w-24`} />
            <div className={`${skeletonBase} h-4 w-24`} />
          </div>
        ))}
        <div className={`${skeletonBase} h-8 w-48`} style={{ marginTop: 24, marginBottom: 16 }} />
      </div>
    </div>
  </div>
);

export const PaymentFormSkeleton = () => (
  <div className="space-y-4">
    <div className={`${skeletonBase} h-12 w-full rounded-md`} />
    <div className={`${skeletonBase} h-4 w-1/2`} />
  </div>
);

export const QRCodeSkeleton = ({ size = 200 }) => (
  <div className="relative" style={{ width: size, height: size }}>
    <div className={`${skeletonBase} w-full h-full rounded-lg`} style={{ border: '1px solid #e2e8f0' }} />
  </div>
);