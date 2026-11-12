import React, { useState, useRef, useCallback } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import SizeGuideModal from '@/components/SizeGuideModal';
import FitFinder from '@/components/FitFinder';
import { savedRecommendation } from '@/lib/fit-finder';

interface Variant {
    id: number;
    color: string | null;
    color_hex: string | null;
    size: string | null;
    price_modifier: number;
    final_price: number;
    stock: number;
    sku: string | null;
}

interface ProductImage {
    id: number;
    url: string;
    alt: string;
    color?: string | null;
    is_primary: boolean;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    gender?: string | null;
    /** Chart the "Find my size" advisor uses; null for accessories. */
    size_chart?: 'women' | 'men' | 'kids' | null;
    description: string | null;
    short_description: string | null;
    base_price: number;
    compare_price: number | null;
    material: string | null;
    care_instructions: string | null;
    category: string | null;
    images: ProductImage[];
    variants: Variant[];
}

interface RelatedProduct {
    id: number;
    name: string;
    slug: string;
    base_price: number;
    primary_image: string | null;
}

interface ProductPageProps {
    product: Product;
    related: RelatedProduct[];
}

const C = {
    bg:      'var(--forme-bg)',
    surface: 'var(--forme-surface)',
    border:  'var(--forme-border)',
    accent:  'var(--forme-accent)',
    text:    'var(--forme-text)',
    muted:   'var(--forme-muted)',
    dim:     'var(--forme-dim)',
};

export default function ProductPage({ product, related }: ProductPageProps) {
    const colors = [...new Set(product.variants.filter(v => v.color).map(v => v.color!))];
    const sizes  = [...new Set(product.variants.filter(v => v.size).map(v => v.size!))];

    const [selectedColor, setSelectedColor] = useState<string | null>(() => {
        return colors.length > 0 ? colors[0] : null;
    });
    const [selectedSize, setSelectedSize] = useState<string | null>(null);

    const [selectedImage, setSelectedImage] = useState<number>(() => {
        if (colors.length > 0) {
            const firstColor = colors[0].toLowerCase().trim();
            const idx = product.images.findIndex(img => 
                (img.color && img.color.toLowerCase().trim() === firstColor) ||
                (img.alt && img.alt.toLowerCase().includes(firstColor))
            );
            if (idx !== -1) return idx;
        }
        const primaryIdx = product.images.findIndex(img => img.is_primary);
        return primaryIdx !== -1 ? primaryIdx : 0;
    });

    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);
    const [addedMsg, setAddedMsg] = useState('');
    const [error, setError] = useState('');
    const [activeTab, setActiveTab] = useState<'details' | 'sizing' | 'care' | 'shipping' | null>('details');
    const [sizeGuideOpen, setSizeGuideOpen] = useState(false);
    const [fitOpen, setFitOpen] = useState(false);
    // Size this device was recommended before (kept in localStorage by the advisor).
    const [fitSize, setFitSize] = useState<string | null>(() => (product.size_chart ? savedRecommendation(product.size_chart) : null));
    const setting =(usePage().props as any).store_setting as { free_shipping_enabled?: boolean; free_shipping_threshold?: number } | undefined;
    const freeDeliveryLine = setting?.free_shipping_enabled
        ? ` Delivery is free within Lagos on orders of ₦${Number(setting.free_shipping_threshold).toLocaleString()} or more.`
        : '';
    // Touch screens fire mouseenter on tap, which would leave the photo stuck zoomed in.
    const canHover = typeof window !== 'undefined' && window.matchMedia('(hover: hover)').matches;

    const genderKey = (product.gender === 'male' || product.gender === 'men') 
        ? 'men' 
        : (product.gender === 'kids' || (product.category && product.category.toLowerCase().includes('kid'))) 
            ? 'kids' 
            : 'women';
    const sizeChartImg = `/images/size-charts/size-chart-${genderKey}.png`;
    const sizeChartTitle = genderKey === 'men' ? "Men's Size Guide" : genderKey === 'kids' ? "Kids' Size Guide" : "Women's Size Guide";

    // Zoom state
    const [zoomed, setZoomed] = useState(false);
    const [zoomPos, setZoomPos] = useState({ x: 50, y: 50 });
    const imgContainerRef = useRef<HTMLDivElement>(null);

    const handleMouseMove = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
        const rect = imgContainerRef.current?.getBoundingClientRect();
        if (!rect) return;
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        setZoomPos({ x: Math.min(100, Math.max(0, x)), y: Math.min(100, Math.max(0, y)) });
    }, []);

    const selectedVariant = product.variants.find(v => {
        if (colors.length && sizes.length) return v.color === selectedColor && v.size === selectedSize;
        if (colors.length) return v.color === selectedColor;
        if (sizes.length) return v.size === selectedSize;
        return true;
    }) ?? (product.variants.length === 1 ? product.variants[0] : null);

    const isSizeAvailable = (size: string) => {
        return product.variants.some(v =>
            v.size === size &&
            v.stock > 0 &&
            (!selectedColor || v.color === selectedColor)
        );
    };

    const handleColorSelect = (color: string | null) => {
        setSelectedColor(color);
        if (color) {
            const normalized = color.toLowerCase().trim();

            // 1. Exact match on image.color
            let idx = product.images.findIndex(img =>
                img.color && img.color.toLowerCase().trim() === normalized
            );

            // 2. Partial match on image.color
            if (idx === -1) {
                idx = product.images.findIndex(img =>
                    img.color && (
                        img.color.toLowerCase().includes(normalized) ||
                        normalized.includes(img.color.toLowerCase())
                    )
                );
            }

            // 3. Fallback: match on image.alt
            if (idx === -1) {
                idx = product.images.findIndex(img =>
                    img.alt && img.alt.toLowerCase().includes(normalized)
                );
            }

            if (idx !== -1) {
                setSelectedImage(idx);
            }
        }
    };

    const handleImageSelect = (index: number) => {
        setSelectedImage(index);
        const img = product.images[index];
        if (!img) return;

        // 1. Direct match on image.color
        if (img.color) {
            const targetColor = img.color.toLowerCase().trim();
            const matchingColor = colors.find(c => {
                const norm = c.toLowerCase().trim();
                return norm === targetColor || norm.includes(targetColor) || targetColor.includes(norm);
            });
            if (matchingColor) {
                setSelectedColor(matchingColor);
                return;
            }
        }

        // 2. Fallback: match image.alt against available colors
        if (img.alt) {
            const matchingColor = colors.find(c =>
                img.alt.toLowerCase().includes(c.toLowerCase().trim())
            );
            if (matchingColor) {
                setSelectedColor(matchingColor);
            }
        }
    };

    const currentPrice = selectedVariant ? selectedVariant.final_price : product.base_price;

    const handleAddToCart = async () => {
        setError('');
        if (!selectedVariant) {
            const missing = [];
            if (colors.length > 0 && !selectedColor) missing.push('color');
            if (sizes.length > 0 && !selectedSize) missing.push('size');
            setError(`Please select a ${missing.join(' and ')}.`);
            return;
        }
        if (selectedVariant.stock === 0) {
            setError('This variant is out of stock.');
            return;
        }
        setAdding(true);
        try {
            const res = await axios.post('/cart', { variant_id: selectedVariant.id, quantity });
            setAddedMsg('Added to bag!');
            setTimeout(() => setAddedMsg(''), 3000);
            window.dispatchEvent(new CustomEvent('cart-updated', { detail: res.data.count }));
        } catch (e: any) {
            setError(e.response?.data?.error || 'Could not add to cart.');
        } finally {
            setAdding(false);
        }
    };

    return (
        <>
            <Head title={product.name} />

            <style>{`
                .product-main-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                    gap: 64px;
                    align-items: start;
                }
                /* Grid and flex children may shrink below their content, so a long
                   row of thumbnails scrolls instead of widening the page. */
                .product-main-grid > *, .product-images-wrap > * { min-width: 0; }
                .product-images-wrap {
                    display: flex;
                    gap: 16px;
                }
                .related-grid {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 32px;
                }
                @media (max-width: 900px) {
                    .product-main-grid {
                        grid-template-columns: minmax(0, 1fr);
                        gap: 32px;
                    }
                    .product-images-wrap {
                        flex-direction: column-reverse;
                    }
                    .product-thumbnails-list {
                        flex-direction: row !important;
                        width: 100% !important;
                        overflow-x: auto;
                        scrollbar-width: none;
                    }
                    .product-thumbnails-list::-webkit-scrollbar { display: none; }
                    .product-thumbnails-list button {
                        width: 64px !important;
                        flex-shrink: 0;
                    }
                    .related-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
                }
                @media (max-width: 768px) {
                    .product-page, .product-breadcrumb { padding-left: 16px !important; padding-right: 16px !important; }
                }
                /* Details, care and shipping text: may contain paragraphs and lists (admin rich text). */
                .product-rich-text p { margin: 0 0 10px; }
                .product-rich-text p:last-child { margin-bottom: 0; }
                .product-rich-text ul, .product-rich-text ol { margin: 0 0 10px; padding-left: 18px; }
                .product-rich-text ul { list-style: disc; }
                .product-rich-text ol { list-style: decimal; }
                .product-rich-text li { margin-bottom: 4px; }
                .product-rich-text strong { color: var(--forme-text); font-weight: 500; }
                .product-rich-text a { color: var(--forme-accent); }
                .fit-cta {
                    width: 100%; display: flex; align-items: center; gap: 14px; margin-bottom: 24px;
                    padding: 14px 16px; background: var(--forme-surface); color: var(--forme-text);
                    border: 1px solid var(--forme-border); border-radius: 4px; cursor: pointer; font-family: inherit;
                    transition: border-color 0.2s;
                }
                .fit-cta:hover { border-color: var(--forme-text); }
                .fit-cta:focus-visible { outline: 2px solid var(--forme-accent); outline-offset: 2px; }
                /* Phones and tablets can't hover: no zoom hint there. */
                @media (hover: none) {
                    .zoom-hint { display: none; }
                }
            `}</style>

            {/* Breadcrumb */}
            <div className="product-breadcrumb" style={{ padding: '16px 24px', borderBottom: `1px solid ${C.border}` }}>
                <div style={{ maxWidth: '1400px', margin: '0 auto', display: 'flex', gap: '8px', fontSize: '12px', color: C.muted }}>
                    <Link href="/" style={{ color: C.muted, textDecoration: 'none' }}>Home</Link>
                    <span>/</span>
                    <Link href="/shop" style={{ color: C.muted, textDecoration: 'none' }}>Shop</Link>
                    <span>/</span>
                    <span style={{ color: C.text }}>{product.name}</span>
                </div>
            </div>

            <div className="product-page" style={{ maxWidth: '1400px', margin: '0 auto', padding: '32px 24px 80px' }}>
                <div className="product-main-grid">

                    {/* ── Images ────────────────────────────────────────── */}
                    <div className="product-images-wrap">
                        {/* Thumbnails */}
                        {product.images.length > 1 && (
                            <div className="product-thumbnails-list" style={{ display: 'flex', flexDirection: 'column', gap: '8px', width: '80px', flexShrink: 0 }}>
                                {product.images.map((img, i) => (
                                    <button
                                        key={img.id}
                                        onClick={() => handleImageSelect(i)}
                                        title={img.color ? `Color: ${img.color}` : (img.alt || product.name)}
                                        style={{ padding: 0, border: `2px solid ${selectedImage === i ? C.text : 'transparent'}`, cursor: 'pointer', aspectRatio: '3/4', overflow: 'hidden', borderRadius: '2px' }}
                                    >
                                        <img src={img.url} alt={img.alt || product.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                    </button>
                                ))}
                            </div>
                        )}
                        {/* Main image with zoom */}
                        <div
                            ref={imgContainerRef}
                            onMouseEnter={() => { if (canHover) setZoomed(true); }}
                            onMouseLeave={() => setZoomed(false)}
                            onMouseMove={handleMouseMove}
                            style={{ flex: 1, aspectRatio: '3/4', background: C.surface, borderRadius: '2px', overflow: 'hidden', position: 'relative', cursor: zoomed ? 'crosshair' : 'default' }}
                        >
                            {product.images[selectedImage] ? (
                                <img
                                    src={product.images[selectedImage].url}
                                    alt={product.images[selectedImage].alt}
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        transformOrigin: `${zoomPos.x}% ${zoomPos.y}%`,
                                        transform: zoomed ? 'scale(2.5)' : 'scale(1)',
                                        transition: zoomed ? 'transform 0.1s ease-out' : 'transform 0.3s ease',
                                        display: 'block',
                                        pointerEvents: 'none',
                                    }}
                                />
                            ) : (
                                <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt={product.name}
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        transformOrigin: `${zoomPos.x}% ${zoomPos.y}%`,
                                        transform: zoomed ? 'scale(2.5)' : 'scale(1)',
                                        transition: zoomed ? 'transform 0.1s ease-out' : 'transform 0.3s ease',
                                        display: 'block',
                                        pointerEvents: 'none',
                                    }} />
                            )}
                            {/* Zoom hint */}
                            {!zoomed && product.images[selectedImage] && (
                                <div className="zoom-hint" style={{ position: 'absolute', bottom: '12px', right: '12px', background: C.surface, backdropFilter: 'blur(4px)', padding: '6px 12px', fontSize: '11px', letterSpacing: '1px', color: C.muted, borderRadius: '2px', pointerEvents: 'none', border: `1px solid ${C.border}` }}>
                                    Hover to zoom
                                </div>
                            )}
                        </div>
                    </div>

                    {/* ── Product info ──────────────────────────────────── */}
                    <div style={{ position: 'sticky', top: '100px' }}>
                        {product.category && <p style={{ fontSize: '10px', letterSpacing: '3px', textTransform: 'uppercase', color: C.accent, marginBottom: '12px' }}>{product.category}</p>}
                        <h1 style={{ fontFamily: "'Cormorant Garamond',serif", fontSize: '36px', fontWeight: 400, color: C.text, marginBottom: '16px', lineHeight: 1.2 }}>{product.name}</h1>

                        {/* Price */}
                        <div style={{ display: 'flex', gap: '12px', alignItems: 'center', marginBottom: '24px' }}>
                            <span style={{ fontFamily: "'Cormorant Garamond',serif", fontSize: '24px', color: C.text }}>₦{Number(currentPrice).toLocaleString()}</span>
                            {product.compare_price && <span style={{ fontSize: '18px', color: C.muted, textDecoration: 'line-through' }}>₦{Number(product.compare_price).toLocaleString()}</span>}
                        </div>

                        {product.short_description && <p style={{ fontSize: '14px', color: C.muted, lineHeight: '1.8', marginBottom: '32px' }}>{product.short_description}</p>}

                        {/* Color picker */}
                        {colors.length > 0 && (
                            <div style={{ marginBottom: '24px' }}>
                                <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted, marginBottom: '12px' }}>
                                    Color {selectedColor && <span style={{ color: C.text, fontWeight: 600 }}>— {selectedColor}</span>}
                                </p>
                                <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
                                    {product.variants.filter((v, i, arr) => arr.findIndex(x => x.color === v.color) === i).map(v => (
                                        <button key={v.id} onClick={() => handleColorSelect(v.color)}
                                            title={v.color ?? ''}
                                            style={{ width: '36px', height: '36px', borderRadius: '50%', background: v.color_hex || C.dim, border: `3px solid ${selectedColor === v.color ? C.text : 'transparent'}`, outline: `1px solid ${v.color_hex ? 'rgba(0,0,0,0.15)' : C.dim}`, cursor: 'pointer', transition: 'border-color 0.2s', boxSizing: 'border-box' }} />
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Size picker */}
                        {sizes.length > 0 && (
                            <div style={{ marginBottom: '24px' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '12px' }}>
                                    <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted }}>Size</p>
                                    <button 
                                        type="button" 
                                        onClick={() => setSizeGuideOpen(true)}
                                        style={{ fontSize: '12px', color: C.accent, background: 'none', border: 'none', cursor: 'pointer', textDecoration: 'underline', padding: 0 }}
                                    >
                                        Size Guide
                                    </button>
                                </div>
                                <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                                    {sizes.map(size => {
                                        const available = isSizeAvailable(size);
                                        return (
                                            <button key={size} onClick={() => available && setSelectedSize(size)} disabled={!available}
                                                style={{ padding: '10px 16px', border: `1px solid ${selectedSize === size ? C.text : C.border}`, background: selectedSize === size ? C.text : 'transparent', color: !available ? C.dim : selectedSize === size ? C.bg : C.text, fontSize: '12px', letterSpacing: '1px', cursor: available ? 'pointer' : 'not-allowed', transition: 'all 0.2s', textDecoration: !available ? 'line-through' : 'none' }}>
                                                {size}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Find my size */}
                        {product.size_chart && (
                            <button type="button" className="fit-cta" onClick={() => setFitOpen(true)}>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" style={{ flexShrink: 0 }}>
                                    <rect x="2.5" y="7.5" width="19" height="9" rx="1.5" stroke="currentColor" strokeWidth="1.3" />
                                    <path d="M6 7.5v3M9.5 7.5v4.5M13 7.5v3M16.5 7.5v4.5M20 7.5v3" stroke="currentColor" strokeWidth="1.3" />
                                </svg>
                                <span style={{ flex: 1, minWidth: 0, textAlign: 'left' }}>
                                    <span style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: C.text }}>
                                        {fitSize ? <>Your size: {fitSize}</> : 'Find my size'}
                                    </span>
                                    <span style={{ display: 'block', fontSize: '12px', color: C.muted, marginTop: '2px' }}>
                                        {fitSize ? 'From your saved answers. Tap to check or change.' : 'Height, weight and two quick questions.'}
                                    </span>
                                </span>
                                <span aria-hidden="true" style={{ fontSize: '16px', color: C.text }}>→</span>
                            </button>
                        )}

                        {/* Quantity */}
                        <div style={{ marginBottom: '24px' }}>
                            <p style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted, marginBottom: '12px' }}>Quantity</p>
                            <div style={{ display: 'flex', alignItems: 'center', border: `1px solid ${C.border}`, width: 'fit-content' }}>
                                <button onClick={() => setQuantity(q => Math.max(1, q - 1))} style={{ width: '40px', height: '40px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '18px', color: C.text }}>−</button>
                                <span style={{ width: '40px', textAlign: 'center', fontSize: '14px', color: C.text }}>{quantity}</span>
                                <button onClick={() => setQuantity(q => q + 1)} style={{ width: '40px', height: '40px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '18px', color: C.text }}>+</button>
                            </div>
                        </div>

                        {/* Errors / success */}
                        {error && <p style={{ color: C.accent, fontSize: '13px', marginBottom: '12px' }}>{error}</p>}
                        {addedMsg && <p style={{ color: '#2d7a4f', fontSize: '13px', marginBottom: '12px' }}>✓ {addedMsg}</p>}

                        {/* Add to bag button */}
                        <div style={{ display: 'flex', gap: '12px', marginBottom: '24px' }}>
                            <button onClick={handleAddToCart} disabled={adding} style={{ flex: 1, background: C.text, color: C.bg, border: 'none', padding: '18px', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', cursor: 'pointer', transition: 'opacity 0.2s', opacity: adding ? 0.7 : 1 }}>
                                {adding ? 'Adding...' : selectedVariant?.stock === 0 ? 'Out of Stock' : 'Add to Bag'}
                            </button>
                        </div>

                        {/* Accordion tabs */}
                        {[
                            { key: 'details' as const, label: 'Details', isSizing: false, content: product.description || 'No additional details.' },
                            { key: 'sizing' as const, label: 'Size & Fit Guide', isSizing: true, content: '' },
                            { key: 'care' as const, label: 'Care Instructions', isSizing: false, content: product.care_instructions || 'Follow the care label inside your garment.' },
                            { key: 'shipping' as const, label: 'Shipping & Returns', isSizing: false, content: `<p>Orders are processed in 1–2 business days. Delivery within Lagos takes 2–5 business days, and 3–7 business days to other states.${freeDeliveryLine}</p><p>Unused items in their original condition, with tags attached, can be returned or exchanged within 3 days of delivery. <a href="/policy">Read the full policy</a>.</p>` },
                        ].map(tab => (
                            <div key={tab.key} style={{ borderBottom: `1px solid ${C.border}` }}>
                                <button onClick={() => setActiveTab(activeTab === tab.key ? null : tab.key)} style={{ width: '100%', display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '16px 0', background: 'none', border: 'none', cursor: 'pointer' }}>
                                    <span style={{ fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', color: C.text, fontWeight: 500 }}>{tab.label}</span>
                                    <span style={{ fontSize: '18px', color: C.muted, transition: 'transform 0.2s', transform: activeTab === tab.key ? 'rotate(45deg)' : 'rotate(0)' }}>+</span>
                                </button>
                                {activeTab === tab.key && (
                                    <div style={{ paddingBottom: '20px' }}>
                                        {tab.isSizing ? (
                                            <div>
                                                <p style={{ fontSize: '13px', color: C.muted, lineHeight: '1.7', marginBottom: '14px' }}>
                                                    Formé garments are engineered for true-to-fit athletic performance and comfort. Tap below to enlarge the official brand chart or open our interactive measurement table.
                                                </p>
                                                
                                                {/* Embedded Authentic Picture */}
                                                <div 
                                                    onClick={() => setSizeGuideOpen(true)}
                                                    style={{ 
                                                        position: 'relative', 
                                                        cursor: 'zoom-in', 
                                                        borderRadius: '8px', 
                                                        overflow: 'hidden', 
                                                        border: `1px solid ${C.border}`,
                                                        background: C.surface,
                                                        boxShadow: '0 4px 18px rgba(0,0,0,0.06)',
                                                        maxWidth: '480px',
                                                        marginBottom: '14px',
                                                    }}
                                                >
                                                    <img 
                                                        src={sizeChartImg} 
                                                        alt={sizeChartTitle} 
                                                        style={{ width: '100%', height: 'auto', display: 'block' }} 
                                                    />
                                                    <div style={{
                                                        position: 'absolute',
                                                        bottom: '12px',
                                                        right: '12px',
                                                        background: 'rgba(0,0,0,0.75)',
                                                        color: '#fff',
                                                        padding: '6px 14px',
                                                        borderRadius: '20px',
                                                        fontSize: '11px',
                                                        fontWeight: 600,
                                                        backdropFilter: 'blur(4px)',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: '6px',
                                                    }}>
                                                        <span>🔍</span> Tap to Enlarge / Full Table
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap', alignItems: 'center' }}>
                                                    {product.size_chart && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setFitOpen(true)}
                                                            style={{ background: C.text, color: C.bg, border: 'none', padding: '10px 18px', fontSize: '11px', letterSpacing: '1px', textTransform: 'uppercase', fontWeight: 700, borderRadius: '24px', cursor: 'pointer' }}
                                                        >
                                                            Find my size
                                                        </button>
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => setSizeGuideOpen(true)}
                                                        style={{
                                                            background: C.accent,
                                                            color: '#fff',
                                                            border: 'none',
                                                            padding: '10px 18px',
                                                            fontSize: '11px',
                                                            letterSpacing: '1px',
                                                            textTransform: 'uppercase',
                                                            fontWeight: 700,
                                                            borderRadius: '24px',
                                                            cursor: 'pointer',
                                                            transition: 'opacity 0.2s',
                                                        }}
                                                    >
                                                        📋 Open Interactive Size Guide
                                                    </button>
                                                    <a
                                                        href={sizeChartImg}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        style={{
                                                            fontSize: '12px',
                                                            color: C.accent,
                                                            textDecoration: 'underline',
                                                            fontWeight: 600,
                                                        }}
                                                    >
                                                        View Raw High-Res Poster ↗
                                                    </a>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="product-rich-text" style={{ fontSize: '13px', color: C.muted, lineHeight: '1.8' }} dangerouslySetInnerHTML={{ __html: tab.content }} />
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Related products */}
                {related.length > 0 && (
                    <div style={{ marginTop: '96px' }}>
                        <div style={{ textAlign: 'center', marginBottom: '48px' }}>
                            <p style={{ fontSize: '10px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, marginBottom: '12px' }}>You May Also Like</p>
                            <h2 style={{ fontFamily: "'Cormorant Garamond',serif", fontSize: '36px', fontWeight: 400, color: C.text }}>Complete the Look</h2>
                        </div>
                        <div className="related-grid">
                            {related.map(p => (
                                <Link key={p.id} href={`/products/${p.slug}`} style={{ textDecoration: 'none' }}>
                                    <div>
                                        <div style={{ aspectRatio: '3/4', background: C.surface, overflow: 'hidden', borderRadius: '2px', marginBottom: '12px' }}>
                                            {p.primary_image ? <img src={p.primary_image} alt={p.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} /> : <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt={p.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />}
                                        </div>
                                        <p style={{ fontFamily: "'Cormorant Garamond',serif", fontSize: '16px', color: C.text, marginBottom: '6px' }}>{p.name}</p>
                                        <p style={{ fontSize: '14px', color: C.muted }}>₦{Number(p.base_price).toLocaleString()}</p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <SizeGuideModal 
                isOpen={sizeGuideOpen} 
                onClose={() => setSizeGuideOpen(false)} 
                defaultTab={product.gender === 'male' ? 'men' : product.gender === 'kids' ? 'kids' : 'women'}
            />

            {product.size_chart && (
                <FitFinder
                    isOpen={fitOpen}
                    onClose={() => setFitOpen(false)}
                    chart={product.size_chart}
                    product={{ name: product.name, image: product.images[selectedImage]?.url ?? product.images[0]?.url, price: currentPrice }}
                    sizes={sizes}
                    isSizeAvailable={isSizeAvailable}
                    onSelectSize={(size) => {
                        setSelectedSize(size);
                        setError('');
                    }}
                    onRecommend={(label, chart) => chart === product.size_chart && setFitSize(label)}
                />
            )}
        </>
    );
}
