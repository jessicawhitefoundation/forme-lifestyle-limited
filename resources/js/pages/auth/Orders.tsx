import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    total_amount: number;
    items_count: number;
    routely_tracking_number?: string | null;
    routely_status?: string | null;
    created_at: string;
}

interface OrdersProps {
    orders: Order[];
}

const statusColors: Record<string, { bg: string; color: string }> = {
    pending:    { bg: '#fff8e6', color: '#b07a00' },
    processing: { bg: '#e8f0fe', color: '#1a56db' },
    shipped:    { bg: '#e8f7f4', color: '#0a7c6a' },
    delivered:  { bg: '#f0faf4', color: '#2d7a4f' },
    cancelled:  { bg: 'var(--forme-surface-alt)', color: 'var(--forme-accent)' },
};

const paymentColors: Record<string, { bg: string; color: string }> = {
    unpaid:   { bg: 'var(--forme-surface-alt)', color: 'var(--forme-accent)' },
    paid:     { bg: '#f0faf4', color: '#2d7a4f' },
    refunded: { bg: '#fff8e6', color: '#b07a00' },
};

export default function Orders({ orders }: OrdersProps) {
    return (
        <>
            <Head title="My Orders" />

            <div className="page-pad" style={{ maxWidth: '960px', margin: '0 auto', padding: '48px 24px 80px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '48px', flexWrap: 'wrap', gap: '16px' }}>
                    <div>
                        <p style={{ fontSize: '11px', letterSpacing: '3px', textTransform: 'uppercase', color: 'var(--forme-accent)', marginBottom: '8px' }}>Order History</p>
                        <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '40px', fontWeight: 400, color: 'var(--forme-text)' }}>My Orders</h1>
                    </div>
                    <Link href="/account" style={{ fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', textDecoration: 'none', border: '1px solid var(--forme-border)', padding: '10px 20px' }}>
                        ← Back to Account
                    </Link>
                </div>

                {orders.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '80px 0' }}>
                        <div style={{ width: '80px', height: '80px', borderRadius: '50%', background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 24px' }}>
                            <span style={{ fontSize: '32px' }}>📦</span>
                        </div>
                        <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '26px', color: 'var(--forme-muted)', marginBottom: '8px' }}>No orders yet</p>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)', marginBottom: '32px' }}>Your orders will appear here once you've made a purchase.</p>
                        <Link href="/shop" style={{ background: 'var(--forme-accent)', color: '#fff', padding: '14px 40px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', display: 'inline-block' }}>
                            Start Shopping
                        </Link>
                    </div>
                ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                        {orders.map(order => {
                            const sc = statusColors[order.status] ?? { bg: '#f5f5f5', color: 'var(--forme-muted)' };
                            const pc = paymentColors[order.payment_status] ?? { bg: '#f5f5f5', color: 'var(--forme-muted)' };
                            return (
                                <div key={order.id} style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '28px 32px', borderRadius: '4px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
                                    <div>
                                        <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '20px', color: 'var(--forme-text)', marginBottom: '6px', letterSpacing: '1px' }}>{order.order_number}</p>
                                        <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginBottom: '12px' }}>{order.created_at} · {order.items_count} {order.items_count === 1 ? 'item' : 'items'}</p>
                                        <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
                                            <span style={{ fontSize: '11px', padding: '4px 12px', borderRadius: '20px', background: sc.bg, color: sc.color, letterSpacing: '0.5px', textTransform: 'capitalize' }}>
                                                {order.status}
                                            </span>
                                            <span style={{ fontSize: '11px', padding: '4px 12px', borderRadius: '20px', background: pc.bg, color: pc.color, letterSpacing: '0.5px', textTransform: 'capitalize' }}>
                                                {order.payment_status}
                                            </span>
                                            {order.routely_tracking_number && (
                                                <span style={{ fontSize: '11px', padding: '4px 12px', borderRadius: '20px', background: '#f0fdf4', color: '#166534', border: '1px solid #bbf7d0', letterSpacing: '0.5px', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                                                    🚚 Routely: {order.routely_tracking_number}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div style={{ textAlign: 'right' }}>
                                        <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '22px', color: 'var(--forme-text)', marginBottom: '8px' }}>
                                            ₦{Number(order.total_amount).toLocaleString()}
                                        </p>
                                        <Link href={`/order/${order.order_number}/confirmation`} style={{ fontSize: '12px', color: 'var(--forme-accent)', textDecoration: 'underline', letterSpacing: '0.5px' }}>
                                            View Details →
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
