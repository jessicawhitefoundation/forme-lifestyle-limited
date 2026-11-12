import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';
import DeliveryEstimator from '@/components/DeliveryEstimator';

interface CartItem {
    id: number;
    quantity: number;
    variant: { id: number; color: string | null; size: string | null; final_price: number; stock: number };
    product: { name: string; slug: string; image: string | null };
    line_total: number;
}

interface CartProps {
    cartItems: CartItem[];
    total: number;
    count: number;
    states: string[];
    lagosAreas: string[];
    countries?: { code: string; name: string }[];
}

export default function Cart({ cartItems: initialItems, states, lagosAreas, countries = [] }: CartProps) {
    const [items, setItems] = useState(initialItems);

    const total = items.reduce((sum, i) => sum + i.variant.final_price * i.quantity, 0);

    const notifyLayout = () => window.dispatchEvent(new CustomEvent('cart-updated'));

    const updateQty = async (item: CartItem, qty: number) => {
        if (qty < 1 || qty > item.variant.stock) {
            return;
        }

        try {
            await axios.patch(`/cart/${item.id}`, { quantity: qty });
            setItems((prev) => prev.map((i) => (i.id === item.id ? { ...i, quantity: qty, line_total: i.variant.final_price * qty } : i)));
            notifyLayout();
        } catch {
            // The quantity on screen stays as it was.
        }
    };

    const removeItem = async (id: number) => {
        try {
            await axios.delete(`/cart/${id}`);
            setItems((prev) => prev.filter((i) => i.id !== id));
            notifyLayout();
        } catch {
            // The item stays in the list.
        }
    };

    return (
        <>
            <Head title="Your Bag" />

            <style>{`
                .cart-main-grid {
                    display: grid;
                    grid-template-columns: 1fr 360px;
                    gap: 64px;
                    align-items: start;
                }
                .cart-item-image {
                    width: 120px;
                    height: 160px;
                    flex-shrink: 0;
                    overflow: hidden;
                    border-radius: 2px;
                    background: var(--forme-surface-alt);
                    display: block;
                }
                @media (max-width: 900px) {
                    .cart-main-grid {
                        grid-template-columns: 1fr;
                        gap: 32px;
                    }
                    .cart-item-image {
                        width: 84px !important;
                        height: 112px !important;
                    }
                }
            `}</style>

            <div className="page-pad" style={{ maxWidth: '1200px', margin: '0 auto', padding: '32px 20px 80px' }}>
                <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: 'clamp(28px, 4vw, 42px)', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '32px' }}>Shopping Bag ({items.length})</h1>

                {items.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '80px 0' }}>
                        <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '28px', color: 'var(--forme-muted)', marginBottom: '8px' }}>Your bag is empty</p>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)', marginBottom: '32px' }}>Discover pieces made just for you.</p>
                        <Link href="/shop" style={{ background: 'var(--forme-accent)', color: '#fff', padding: '14px 40px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', display: 'inline-block' }}>
                            Explore the Shop
                        </Link>
                    </div>
                ) : (
                    <div className="cart-main-grid">
                        {/* Cart items */}
                        <div>
                            {items.map((item) => (
                                <div key={item.id} style={{ display: 'flex', gap: '16px', padding: '20px 0', borderBottom: '1px solid var(--forme-border)' }}>
                                    <Link href={`/products/${item.product.slug}`} className="cart-item-image">
                                        {item.product.image && <img src={item.product.image} alt={item.product.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />}
                                    </Link>

                                    <div style={{ flex: 1 }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '8px' }}>
                                            <Link href={`/products/${item.product.slug}`} style={{ textDecoration: 'none' }}>
                                                <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '20px', color: 'var(--forme-text)', lineHeight: 1.2 }}>{item.product.name}</p>
                                            </Link>
                                            <button onClick={() => removeItem(item.id)} aria-label={`Remove ${item.product.name}`} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--forme-muted)', fontSize: '18px', flexShrink: 0 }}>✕</button>
                                        </div>
                                        {(item.variant.color || item.variant.size) && (
                                            <p style={{ fontSize: '13px', color: 'var(--forme-muted)', marginBottom: '16px' }}>
                                                {[item.variant.color, item.variant.size].filter(Boolean).join(' / ')}
                                            </p>
                                        )}
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', border: '1px solid var(--forme-border)' }}>
                                                <button onClick={() => updateQty(item, item.quantity - 1)} aria-label="Decrease quantity" style={{ width: '36px', height: '36px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '16px', color: 'var(--forme-text)' }}>−</button>
                                                <span style={{ width: '36px', textAlign: 'center', fontSize: '13px', color: 'var(--forme-text)' }}>{item.quantity}</span>
                                                <button onClick={() => updateQty(item, item.quantity + 1)} aria-label="Increase quantity" style={{ width: '36px', height: '36px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '16px', color: 'var(--forme-text)' }}>+</button>
                                            </div>
                                            <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-text)' }}>₦{(item.variant.final_price * item.quantity).toLocaleString()}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Order summary */}
                        <div style={{ position: 'sticky', top: '100px', background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '32px', borderRadius: '4px' }}>
                            <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '22px', marginBottom: '24px', color: 'var(--forme-text)' }}>Order Summary</p>

                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                                <span style={{ fontSize: '14px', color: 'var(--forme-muted)' }}>Subtotal</span>
                                <span style={{ fontSize: '14px', color: 'var(--forme-text)' }}>₦{total.toLocaleString()}</span>
                            </div>

                            <DeliveryEstimator states={states} lagosAreas={lagosAreas} countries={countries} subtotal={total} />

                            <Link href="/checkout" style={{ display: 'block', background: 'var(--forme-accent)', color: '#fff', textAlign: 'center', padding: '18px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: '8px' }}>
                                Proceed to Checkout
                            </Link>
                            <p style={{ fontSize: '11px', color: 'var(--forme-muted)', textAlign: 'center', marginBottom: '14px' }}>No account needed.</p>
                            <Link href="/shop" style={{ display: 'block', border: '1px solid var(--forme-text)', color: 'var(--forme-text)', textAlign: 'center', padding: '16px', textDecoration: 'none', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase' }}>
                                Continue Shopping
                            </Link>

                            <div style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px', flexWrap: 'wrap' }}>
                                {['Visa', 'Mastercard', 'Verve', 'Bank Transfer'].map((b) => (
                                    <span key={b} style={{ fontSize: '10px', color: 'var(--forme-muted)', background: 'var(--forme-bg)', border: '1px solid var(--forme-border)', padding: '4px 8px', borderRadius: '3px' }}>{b}</span>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
