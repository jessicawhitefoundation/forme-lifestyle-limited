import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface OrderItem {
    product_name: string;
    variant_description: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
}

interface Order {
    order_number: string;
    status: string;
    payment_status: string;
    total_amount: number;
    shipping_fee?: number;
    customer_name: string;
    customer_email: string;
    shipping_address?: string;
    shipping_city: string;
    shipping_state: string;
    routely_tracking_number?: string | null;
    routely_status?: string | null;
    items: OrderItem[];
}

export default function OrderConfirmation({ order }: { order: Order }) {
    return (
        <>
            <Head title="Order Confirmed" />

            <div style={{ maxWidth: '680px', margin: '0 auto', padding: '64px 24px 80px', textAlign: 'center' }}>

                {/* Success icon */}
                <div style={{ width: '80px', height: '80px', borderRadius: '50%', background: 'rgba(45, 122, 79, 0.1)', border: '2px solid #2d7a4f', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 32px' }}>
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2d7a4f" strokeWidth="2">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </div>

                <p style={{ fontSize: '11px', letterSpacing: '4px', textTransform: 'uppercase', color: 'var(--forme-accent)', marginBottom: '12px' }}>Thank You</p>
                <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '42px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '16px' }}>Order Confirmed!</h1>
                <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: '1.8', marginBottom: '8px' }}>
                    Hi <strong style={{ color: 'var(--forme-text)' }}>{order.customer_name}</strong>, your order has been placed successfully.
                </p>
                <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: '1.8', marginBottom: '40px' }}>
                    A confirmation email has been sent to <strong style={{ color: 'var(--forme-text)' }}>{order.customer_email}</strong>
                </p>

                {/* Order reference */}
                <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '24px', borderRadius: '4px', marginBottom: '32px' }}>
                    <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '8px' }}>Order Reference</p>
                    <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '28px', color: 'var(--forme-text)', letterSpacing: '2px' }}>{order.order_number}</p>
                </div>

                {/* Order items */}
                <div style={{ textAlign: 'left', marginBottom: '32px' }}>
                    <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '22px', color: 'var(--forme-text)', marginBottom: '16px' }}>Your Items</p>
                    {order.items.map((item, i) => (
                        <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '12px 0', borderBottom: '1px solid var(--forme-border)' }}>
                            <div>
                                <p style={{ fontSize: '14px', color: 'var(--forme-text)' }}>{item.product_name} × {item.quantity}</p>
                                {item.variant_description && <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginTop: '2px' }}>{item.variant_description}</p>}
                            </div>
                            <p style={{ fontSize: '14px', color: 'var(--forme-text)', fontWeight: 500 }}>₦{Number(item.total_price).toLocaleString()}</p>
                        </div>
                    ))}
                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '16px 0', marginTop: '8px' }}>
                        <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-text)' }}>Total Paid</span>
                        <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-accent)' }}>₦{Number(order.total_amount).toLocaleString()}</span>
                    </div>
                </div>

                {/* Routely Logistics & Delivery Info */}
                <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '24px', borderRadius: '4px', textAlign: 'left', marginBottom: '40px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <span style={{ fontSize: '20px' }}>🚚</span>
                            <div>
                                <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-muted)' }}>Fulfillment Partner</p>
                                <p style={{ fontSize: '14px', fontWeight: 500, color: 'var(--forme-text)' }}>Routely Logistics</p>
                            </div>
                        </div>
                        {order.routely_status && (
                            <span style={{ fontSize: '11px', padding: '4px 12px', borderRadius: '20px', background: '#f0fdf4', color: '#166534', border: '1px solid #bbf7d0', textTransform: 'capitalize' }}>
                                {order.routely_status}
                            </span>
                        )}
                    </div>

                    {order.routely_tracking_number ? (
                        <div style={{ background: 'rgba(0,0,0,0.03)', padding: '16px', borderRadius: '4px', marginBottom: '16px' }}>
                            <p style={{ fontSize: '11px', letterSpacing: '1px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '4px' }}>Routely Tracking ID</p>
                            <p style={{ fontFamily: 'monospace', fontSize: '16px', fontWeight: 600, color: 'var(--forme-accent)', letterSpacing: '1px' }}>
                                {order.routely_tracking_number}
                            </p>
                        </div>
                    ) : (
                        <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginBottom: '16px', fontStyle: 'italic' }}>
                            Your parcel is being packaged. Once handed over to our Routely courier, live tracking updates will appear here.
                        </p>
                    )}

                    <div style={{ borderTop: '1px solid var(--forme-border)', paddingTop: '16px' }}>
                        <p style={{ fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '6px' }}>Destination</p>
                        <p style={{ fontSize: '14px', color: 'var(--forme-text)' }}>
                            {order.shipping_address ? `${order.shipping_address}, ` : ''}{order.shipping_city}, {order.shipping_state}
                        </p>
                        <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginTop: '6px' }}>
                            Estimated delivery: {order.shipping_state?.toLowerCase() === 'lagos' ? '1 - 2 business days' : '2 - 5 business days'}
                        </p>
                    </div>
                </div>

                {/* Actions */}
                <div style={{ display: 'flex', gap: '16px', justifyContent: 'center', flexWrap: 'wrap' }}>
                    <Link href="/account/orders" style={{ background: 'var(--forme-text)', color: 'var(--forme-bg)', padding: '14px 32px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase' }}>
                        Track Order
                    </Link>
                    <Link href="/shop" style={{ border: '1px solid var(--forme-text)', color: 'var(--forme-text)', padding: '14px 32px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase' }}>
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </>
    );
}
