import { Head, Link, usePage } from '@inertiajs/react';

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
    subtotal: number;
    total_amount: number;
    shipping_fee?: number;
    vat_amount: number;
    vat_inclusive: boolean;
    free_shipping: boolean;
    customer_name: string;
    customer_email: string;
    shipping_address?: string;
    shipping_city: string;
    shipping_state: string;
    routely_tracking_number?: string | null;
    routely_status?: string | null;
    items: OrderItem[];
}

const rowStyle = { display: 'flex', justifyContent: 'space-between', padding: '6px 0', fontSize: '13px', color: 'var(--forme-muted)' };

export default function OrderConfirmation({ order }: { order: Order }) {
    const { props } = usePage();
    const isLoggedIn = !!(props as any)?.auth?.user;
    const contact = (props as any).store_contact as { email: string; whatsapp_display: string; whatsapp_number: string };
    const isPaid = order.payment_status === 'paid';
    const isRefunded = order.payment_status === 'refunded';

    const statusColor = isPaid ? '#2d7a4f' : '#b45309';

    return (
        <>
            <Head title={isPaid ? 'Order Confirmed' : 'Payment Not Completed'} />

            <div className="page-pad" style={{ maxWidth: '680px', margin: '0 auto', padding: '64px 24px 80px', textAlign: 'center' }}>
                <div style={{ width: '80px', height: '80px', borderRadius: '50%', border: `2px solid ${statusColor}`, display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 32px' }}>
                    {isPaid ? (
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke={statusColor} strokeWidth="2" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                    ) : (
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke={statusColor} strokeWidth="2" aria-hidden="true"><line x1="12" y1="7" x2="12" y2="13" /><line x1="12" y1="17" x2="12" y2="17.01" /></svg>
                    )}
                </div>

                {isPaid ? (
                    <>
                        <p style={{ fontSize: '11px', letterSpacing: '4px', textTransform: 'uppercase', color: 'var(--forme-accent)', marginBottom: '12px' }}>Thank You</p>
                        <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '42px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '16px' }}>Order Confirmed!</h1>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: '1.8', marginBottom: '40px' }}>
                            Hi <strong style={{ color: 'var(--forme-text)' }}>{order.customer_name}</strong>, we've received your payment. Your confirmation will be sent to <strong style={{ color: 'var(--forme-text)' }}>{order.customer_email}</strong>.
                        </p>
                    </>
                ) : isRefunded ? (
                    <>
                        <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '42px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '16px' }}>Order Refunded</h1>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: '1.8', marginBottom: '40px' }}>
                            This order has been refunded. If you have any questions, email <a href={`mailto:${contact.email}`} style={{ color: 'var(--forme-accent)' }}>{contact.email}</a>.
                        </p>
                    </>
                ) : (
                    <>
                        <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '42px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '16px' }}>Payment Not Completed</h1>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: '1.8', marginBottom: '24px' }}>
                            We haven't received payment for this order, so it hasn't been placed. If money has left your account, please don't pay again. Contact us on <a href={`https://wa.me/${contact.whatsapp_number}`} style={{ color: 'var(--forme-accent)' }}>WhatsApp</a> or at <a href={`mailto:${contact.email}`} style={{ color: 'var(--forme-accent)' }}>{contact.email}</a> with your order reference.
                        </p>
                        <Link href="/checkout" style={{ display: 'inline-block', background: 'var(--forme-accent)', color: '#fff', padding: '14px 32px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: '40px' }}>
                            Try Payment Again
                        </Link>
                    </>
                )}

                <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '24px', borderRadius: '4px', marginBottom: '32px' }}>
                    <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '8px' }}>Order Reference</p>
                    <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '28px', color: 'var(--forme-text)', letterSpacing: '2px', wordBreak: 'break-word' }}>{order.order_number}</p>
                </div>

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

                    <div style={{ marginTop: '12px' }}>
                        <div style={rowStyle}><span>Subtotal</span><span>₦{Number(order.subtotal).toLocaleString()}</span></div>
                        <div style={rowStyle}><span>Delivery</span><span>{order.free_shipping ? 'Free' : `₦${Number(order.shipping_fee ?? 0).toLocaleString()}`}</span></div>
                        {Number(order.vat_amount) > 0 && (
                            <div style={rowStyle}><span>VAT{order.vat_inclusive ? ' (included)' : ''}</span><span>₦{Number(order.vat_amount).toLocaleString()}</span></div>
                        )}
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '16px 0', marginTop: '8px', borderTop: '1px solid var(--forme-border)' }}>
                        <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-text)' }}>{isPaid ? 'Total Paid' : 'Total'}</span>
                        <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-accent)' }}>₦{Number(order.total_amount).toLocaleString()}</span>
                    </div>
                </div>

                {isPaid && (
                    <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '24px', borderRadius: '4px', textAlign: 'left', marginBottom: '40px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', gap: '12px' }}>
                            <div>
                                <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-muted)' }}>Delivery Partner</p>
                                <p style={{ fontSize: '14px', fontWeight: 500, color: 'var(--forme-text)' }}>Routely Logistics</p>
                            </div>
                            {order.routely_status && (
                                <span style={{ fontSize: '11px', padding: '4px 12px', borderRadius: '20px', border: '1px solid #2d7a4f', color: '#2d7a4f', textTransform: 'capitalize' }}>{order.routely_status}</span>
                            )}
                        </div>

                        {order.routely_tracking_number ? (
                            <div style={{ background: 'var(--forme-surface-alt)', padding: '16px', borderRadius: '4px', marginBottom: '16px' }}>
                                <p style={{ fontSize: '11px', letterSpacing: '1px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '4px' }}>Tracking ID</p>
                                <p style={{ fontFamily: 'monospace', fontSize: '16px', fontWeight: 600, color: 'var(--forme-accent)', letterSpacing: '1px', wordBreak: 'break-all' }}>{order.routely_tracking_number}</p>
                            </div>
                        ) : (
                            <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginBottom: '16px', fontStyle: 'italic' }}>
                                Your parcel is being prepared. Your tracking ID will appear here once the courier has it.
                            </p>
                        )}

                        <div style={{ borderTop: '1px solid var(--forme-border)', paddingTop: '16px' }}>
                            <p style={{ fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '6px' }}>Destination</p>
                            <p style={{ fontSize: '14px', color: 'var(--forme-text)' }}>
                                {order.shipping_address ? `${order.shipping_address}, ` : ''}{order.shipping_city}, {order.shipping_state}
                            </p>
                        </div>
                    </div>
                )}

                <div style={{ display: 'flex', gap: '16px', justifyContent: 'center', flexWrap: 'wrap' }}>
                    {isPaid && (
                        <Link href={isLoggedIn ? '/account/orders' : '/track-order'} style={{ background: 'var(--forme-text)', color: 'var(--forme-bg)', padding: '14px 32px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase' }}>
                            {isLoggedIn ? 'My Orders' : 'Track an Order'}
                        </Link>
                    )}
                    <Link href="/shop" style={{ border: '1px solid var(--forme-text)', color: 'var(--forme-text)', padding: '14px 32px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase' }}>
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </>
    );
}
