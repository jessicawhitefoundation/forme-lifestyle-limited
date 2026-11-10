import React, { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import ScrollReveal from '@/components/ScrollReveal';
import HalftoneWord from '@/components/ui/halftone-word';

interface Product {
    id: number; name: string; slug: string;
    base_price: number; compare_price: number | null;
    primary_image: string | null; hover_image: string | null;
    category: string | null; is_featured: boolean;
}
interface Category {
    id: number; name: string; slug: string;
    image_path: string | null; products_count: number;
}
interface HomeProps {
    featuredProducts: Product[];
    categories: Category[];
    newArrivals: Product[];
    heroSlides: { type: 'image' | 'video'; src: string; poster: string | null; label: string; sub: string | null }[];
}

const C = {
    bg: 'var(--forme-bg)',
    surface: 'var(--forme-surface)',
    border: 'var(--forme-border)',
    accent: 'var(--forme-accent)',
    accentLight: 'var(--forme-accent)',
    text: 'var(--forme-text)',
    muted: 'var(--forme-muted)',
    dim: 'var(--forme-dim)',
};

function ProductCard({ product }: { product: Product }) {
    const [hovered, setHovered] = useState(false);

    return (
        <Link href={`/products/${product.slug}`} style={{ textDecoration: 'none', display: 'block' }}>
            <div onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}>
                <div style={{ position: 'relative', aspectRatio: '3/4', background: C.surface, overflow: 'hidden', marginBottom: '14px' }}>
                    {product.primary_image ? (
                        <>
                            <img src={product.primary_image} alt={product.name}
                                style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.6s ease, opacity 0.4s', opacity: hovered && product.hover_image ? 0 : 1, transform: hovered && !product.hover_image ? 'scale(1.05)' : 'scale(1)' }} />
                            {product.hover_image && (
                                <img src={product.hover_image} alt=""
                                    style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', transition: 'opacity 0.4s', opacity: hovered ? 1 : 0 }} />
                            )}
                        </>
                    ) : (
                        <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt={product.name}
                            style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.6s ease', transform: hovered ? 'scale(1.05)' : 'scale(1)' }} />
                    )}
                    {/* Choose options: the card links to the product page, where colour and size are picked */}
                    <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, padding: '14px 16px', background: 'linear-gradient(to top, var(--forme-overlay-heavy), transparent)', transform: hovered ? 'translateY(0)' : 'translateY(100%)', transition: 'transform 0.3s ease' }}>
                        <span
                            style={{ display: 'block', width: '100%', border: '1px solid var(--forme-text-dim)', color: C.text, fontSize: '10px', letterSpacing: '2px', textTransform: 'uppercase', padding: '10px', textAlign: 'center', fontFamily: "'DM Sans', sans-serif" }}>
                            Choose Options
                        </span>
                    </div>
                </div>
                <div>
                    {product.category && <p style={{ fontSize: '9px', color: C.muted, letterSpacing: '2px', textTransform: 'uppercase', marginBottom: '5px' }}>{product.category}</p>}
                    <p style={{ fontFamily: "'Playfair Display', serif", fontSize: '15px', color: C.text, marginBottom: '6px', lineHeight: 1.3 }}>{product.name}</p>
                    <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                        <span style={{ fontSize: '14px', color: C.text, fontWeight: 500 }}>₦{Number(product.base_price).toLocaleString()}</span>
                        {product.compare_price && <span style={{ fontSize: '13px', color: C.muted, textDecoration: 'line-through' }}>₦{Number(product.compare_price).toLocaleString()}</span>}
                    </div>
                </div>
            </div>
        </Link>
    );
}

// Marquee ticker
function Ticker() {
    const items = ['Made for Every Form of Movement', 'Move · Perform · Live', 'Attainable Luxury Activewear', 'Designed in Lagos', 'Activewear for Everyone'];
    return (
        <div style={{ overflow: 'hidden', background: C.surface, borderTop: `1px solid ${C.border}`, borderBottom: `1px solid ${C.border}`, padding: '14px 0' }}>
            <style>{`
                @keyframes ticker { from{transform:translateX(0)} to{transform:translateX(-50%)} }
                .ticker-inner { display:flex; width:max-content; animation: ticker 22s linear infinite; }
                .ticker-inner:hover { animation-play-state: paused; }
            `}</style>
            <div className="ticker-inner">
                {[...items, ...items].map((item, i) => (
                    <span key={i} style={{ fontSize: '11px', letterSpacing: '3px', textTransform: 'uppercase', color: i % 2 === 1 ? C.accent : C.text, padding: '0 32px', whiteSpace: 'nowrap', fontWeight: 500 }}>
                        {item} {i % 2 === 0 ? '·' : '✦'}
                    </span>
                ))}
            </div>
        </div>
    );
}

const DEFAULT_SLIDES = [
    { type: 'image' as const, src: 'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=900&q=85', poster: null, label: 'Made for Movement', sub: 'Everyday Activewear Essentials' },
    { type: 'image' as const, src: 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=85', poster: null, label: 'Attainable Luxury', sub: 'Designed for Court, Gym & Life' },
    { type: 'video' as const, src: 'https://assets.mixkit.co/videos/preview/mixkit-fashion-model-walking-on-catwalk-19609-large.mp4', poster: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=900&q=80', label: 'Movement is for Everyone', sub: 'Women, Men & Youth Collections' },
    { type: 'image' as const, src: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=900&q=85', poster: null, label: 'Designed in Lagos', sub: 'Crafted with Purpose & Form' },
];

export default function Home({ featuredProducts, categories, newArrivals, heroSlides: serverSlides }: HomeProps) {
    const heroSlides = (serverSlides && serverSlides.length > 0 ? serverSlides : DEFAULT_SLIDES) as typeof DEFAULT_SLIDES;
    const [heroVisible, setHeroVisible] = useState(false);
    const [heroSlide, setHeroSlide] = useState(0);
    const videoRef = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        const t = setTimeout(() => setHeroVisible(true), 100);
        return () => clearTimeout(t);
    }, []);

    // Auto-advance slides
    useEffect(() => {
        const t = setInterval(() => setHeroSlide(p => (p + 1) % heroSlides.length), 5500);
        return () => clearInterval(t);
    }, [heroSlide]);

    // Play video when its slide is active
    useEffect(() => {
        if (heroSlides[heroSlide].type === 'video' && videoRef.current) {
            videoRef.current.play().catch(() => {});
        }
    }, [heroSlide]);

    const goTo = (i: number) => setHeroSlide(i);
    const prev = () => setHeroSlide(p => (p - 1 + heroSlides.length) % heroSlides.length);
    const next = () => setHeroSlide(p => (p + 1) % heroSlides.length);

    return (
        <>
            <Head title="Made for Every Form of Movement" />

            <style>{`
                .hero-section-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    background: ${C.bg};
                    min-height: calc(100vh - 105px);
                }
                .hero-slideshow-container {
                    position: relative;
                    overflow: hidden;
                    background: ${C.bg};
                    min-height: 480px;
                }
                /* Line the hero text up with the header and sections, which sit in a
                   centred 1400px column with 24px padding. The text column is half the
                   page, so its left edge is 100% - 700px + 24px from the column start. */
                .hero-text {
                    padding: clamp(32px,5vw,80px) clamp(20px,4vw,60px);
                    padding-left: max(24px, calc(100% - 676px));
                }
                .products-responsive-grid,
                .category-grid {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 32px;
                }
                .category-grid { gap: 16px; }
                @media (max-width: 1100px) {
                    .products-responsive-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                }
                @media (max-width: 900px) {
                    .hero-section-grid {
                        grid-template-columns: 1fr;
                        min-height: auto;
                    }
                    .category-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                    .hero-text { padding-left: 24px; padding-right: 24px; }
                }
                @media (max-width: 768px) {
                    .hero-text { padding-left: 16px; padding-right: 16px; }
                    .home-section { padding-left: 16px !important; padding-right: 16px !important; }
                    .hero-slideshow-container {
                        min-height: 420px;
                        height: 420px;
                    }
                    .quick-add-container {
                        transform: translateY(0) !important;
                    }
                }
                @media (max-width: 640px) {
                    .products-responsive-grid {
                        grid-template-columns: repeat(2, 1fr);
                        gap: 14px;
                    }
                    /* Phones: the two buttons share one row as equal halves. */
                    .hero-cta-container {
                        width: 100%;
                        flex-wrap: nowrap !important;
                    }
                    .hero-cta-container a {
                        flex: 1 1 0;
                        min-width: 0;
                        box-sizing: border-box;
                        padding: 14px 8px !important;
                        letter-spacing: 2px !important;
                        white-space: nowrap;
                    }
                }
            `}</style>

            {/* ── Hero ── */}
            <section className="hero-section-grid">
                {/* Left: Text */}
                <div className="hero-text" style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center', position: 'relative' }}>
                    <h1 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(36px,4.5vw,78px)', fontWeight: 400, color: C.text, lineHeight: 1.1, marginBottom: '20px', opacity: heroVisible ? 1 : 0, transform: heroVisible ? 'translateY(0)' : 'translateY(20px)', transition: 'all 0.8s ease 0.2s' }}>
                        Made for<br />
                        <em style={{ color: C.accent }}>every form of</em><br />
                        {/* Bigger and semi-bold so the dotted word reads solid and anchors the heading. */}
                        <HalftoneWord text="movement." style={{ fontSize: '1.3em', fontWeight: 600, letterSpacing: '-0.01em' }} />
                    </h1>
                    <div style={{ maxWidth: '420px', marginBottom: '32px', opacity: heroVisible ? 1 : 0, transition: 'all 0.8s ease 0.4s' }}>
                        <p style={{ fontSize: '15px', color: C.text, lineHeight: '1.8', fontWeight: 500, marginBottom: '12px' }}>
                            Elevated, functional activewear at attainable prices. Designed for training, sport, travel and everyday life.
                        </p>
                        <p style={{ fontSize: '14px', color: C.accentLight || C.accent, fontWeight: 700, letterSpacing: '1px' }}>
                            Move. Perform. Live. In your own form.
                        </p>
                    </div>
                    <div className="hero-cta-container" style={{ display: 'flex', flexWrap: 'wrap', gap: '12px', alignItems: 'stretch', opacity: heroVisible ? 1 : 0, transition: 'all 0.8s ease 0.55s' }}>
                        <Link href="/shop" className="shimmer-button" style={{ background: C.accent, color: '#fff', padding: '14px 32px', textDecoration: 'none', fontSize: '11px', letterSpacing: '2.5px', textTransform: 'uppercase', fontWeight: 500, display: 'inline-block', transition: 'background .3s', textAlign: 'center' }}
                            onMouseEnter={e => (e.currentTarget.style.background = '#B86878')}
                            onMouseLeave={e => (e.currentTarget.style.background = C.accent)}>
                            Shop Now
                        </Link>
                        <Link href="/collections/new-arrivals" style={{ border: `1px solid ${C.border}`, color: C.muted, padding: '14px 32px', textDecoration: 'none', fontSize: '11px', letterSpacing: '2.5px', textTransform: 'uppercase', display: 'inline-block', transition: 'all .3s', textAlign: 'center' }}
                            onMouseEnter={e => { e.currentTarget.style.borderColor = C.accent; e.currentTarget.style.color = C.accent; }}
                            onMouseLeave={e => { e.currentTarget.style.borderColor = C.border; e.currentTarget.style.color = C.muted; }}>
                            New Arrivals
                        </Link>
                    </div>
                </div>

                {/* Right: Hero Slideshow */}
                <div className="hero-slideshow-container">

                    {/* Slides */}
                    {heroSlides.map((slide, i) => (
                        <div key={i} style={{ position: 'absolute', inset: 0, opacity: heroSlide === i ? 1 : 0, transition: 'opacity 0.9s ease', zIndex: heroSlide === i ? 1 : 0 }}>
                            {slide.type === 'video' ? (
                                <video
                                    ref={videoRef}
                                    src={slide.src}
                                    poster={'poster' in slide ? slide.poster : undefined}
                                    autoPlay muted loop playsInline
                                    style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }}
                                />
                            ) : (
                                <img src={slide.src} alt={slide.label}
                                    style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center top', display: 'block' }}
                                />
                            )}
                            {/* Gradient overlay */}
                            <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to top, var(--forme-overlay-medium) 0%, var(--forme-overlay-faint) 55%, transparent 100%)' }} />
                            {/* Video badge */}
                            {slide.type === 'video' && heroSlide === i && (
                                <div style={{ position: 'absolute', top: '24px', left: '24px', display: 'flex', alignItems: 'center', gap: '7px', background: 'var(--forme-overlay-light)', backdropFilter: 'blur(6px)', border: `1px solid ${C.border}`, padding: '6px 12px', borderRadius: '2px' }}>
                                    <span style={{ width: '7px', height: '7px', borderRadius: '50%', background: C.accent, display: 'inline-block', animation: 'pulse 1.4s ease-in-out infinite' }} />
                                    <span style={{ fontSize: '9px', letterSpacing: '2px', textTransform: 'uppercase', color: C.text }}>Live Edit</span>
                                </div>
                            )}
                        </div>
                    ))}

                    {/* Slide label */}
                    <div style={{ position: 'absolute', bottom: '48px', left: '40px', zIndex: 10 }}>
                        <p style={{ fontSize: '9px', letterSpacing: '3px', textTransform: 'uppercase', color: C.accent, marginBottom: '8px', transition: 'all 0.5s' }}>
                            {heroSlides[heroSlide].sub}
                        </p>
                        <p style={{ fontFamily: "'Playfair Display', serif", fontSize: '26px', color: C.text, transition: 'all 0.5s' }}>
                            {heroSlides[heroSlide].label}
                        </p>
                    </div>

                    {/* Slide counter */}
                    <div style={{ position: 'absolute', top: '28px', right: '28px', zIndex: 10 }}>
                        <span style={{ fontSize: '11px', color: 'var(--forme-text-dim)', fontFamily: "'DM Sans', sans-serif", letterSpacing: '1px' }}>
                            {String(heroSlide + 1).padStart(2, '0')} / {String(heroSlides.length).padStart(2, '0')}
                        </span>
                    </div>

                    {/* Vertical dots */}
                    <div style={{ position: 'absolute', bottom: '44px', right: '32px', display: 'flex', flexDirection: 'column', gap: '8px', zIndex: 10 }}>
                        {heroSlides.map((_, i) => (
                            <button key={i} onClick={() => goTo(i)}
                                style={{ width: '2px', height: i === heroSlide ? '32px' : '12px', background: i === heroSlide ? C.accent : 'var(--forme-text-faint)', border: 'none', cursor: 'pointer', borderRadius: '2px', padding: 0, transition: 'all 0.35s ease' }}
                                aria-label={`Slide ${i + 1}`}
                            />
                        ))}
                    </div>

                    {/* Prev / Next arrows */}
                    {(['prev', 'next'] as const).map(dir => (
                        <button key={dir} onClick={dir === 'prev' ? prev : next}
                            style={{ position: 'absolute', [dir === 'prev' ? 'left' : 'right']: '16px', top: '50%', transform: 'translateY(-50%)', zIndex: 10, background: 'var(--forme-overlay-light)', border: `1px solid var(--forme-text-border)`, color: C.text, width: '42px', height: '42px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', backdropFilter: 'blur(6px)', fontSize: '22px', transition: 'all 0.2s', lineHeight: 1 }}
                            onMouseEnter={e => { e.currentTarget.style.background = C.accent; e.currentTarget.style.borderColor = C.accent; }}
                            onMouseLeave={e => { e.currentTarget.style.background = 'var(--forme-overlay-light)'; e.currentTarget.style.borderColor = 'var(--forme-text-border)'; }}
                            aria-label={dir}>
                            {dir === 'prev' ? '‹' : '›'}
                        </button>
                    ))}

                    <style>{`@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}`}</style>
                </div>
            </section>

            {/* ── Ticker ── */}
            <Ticker />

            {/* ── Categories ── */}
            {categories.length > 0 && (
                <section className="home-section" style={{ padding: '96px 24px', maxWidth: '1400px', margin: '0 auto' }}>
                    <ScrollReveal>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '48px', flexWrap: 'wrap', gap: '16px' }}>
                        <div>
                            <p style={{ fontSize: '9px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, marginBottom: '12px' }}>Browse</p>
                            <h2 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(28px,3.5vw,48px)', fontWeight: 400, color: C.text, lineHeight: 1.1 }}>
                                Shop by<br /><em>Category</em>
                            </h2>
                        </div>
                        <Link href="/shop" style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted, textDecoration: 'none', borderBottom: `1px solid ${C.border}`, paddingBottom: '4px', transition: 'color .2s, border-color .2s' }}
                            onMouseEnter={e => { e.currentTarget.style.color = C.accent; e.currentTarget.style.borderColor = C.accent; }}
                            onMouseLeave={e => { e.currentTarget.style.color = C.muted; e.currentTarget.style.borderColor = C.border; }}>
                            View All
                        </Link>
                    </div>
                    </ScrollReveal>
                    <div className="category-grid">
                        {categories.filter(cat => !['new-arrivals', 'tops'].includes(cat.slug)).map(cat => (
                            <Link key={cat.id} href={`/collections/${cat.slug}`} style={{ textDecoration: 'none', display: 'block', position: 'relative', aspectRatio: '4/5', overflow: 'hidden', background: C.surface }}>
                                <div style={{ position: 'absolute', inset: 0 }}>
                                    {cat.image_path ? (
                                        <img src={`/storage/${cat.image_path}`} alt={cat.name} style={{ width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.6s ease' }}
                                            onMouseEnter={e => (e.currentTarget.style.transform = 'scale(1.06)')}
                                            onMouseLeave={e => (e.currentTarget.style.transform = 'scale(1)')} />
                                    ) : (
                                        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=600&q=80" alt={cat.name} style={{ width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.6s ease' }}
                                            onMouseEnter={e => (e.currentTarget.style.transform = 'scale(1.06)')}
                                            onMouseLeave={e => (e.currentTarget.style.transform = 'scale(1)')} />
                                    )}
                                    <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to top, var(--forme-overlay-heavy) 0%, var(--forme-overlay-faint) 50%, transparent 100%)' }} />
                                </div>
                                <div style={{ position: 'absolute', bottom: '24px', left: '24px', right: '24px' }}>
                                    <p style={{ fontFamily: "'Playfair Display', serif", fontSize: '22px', color: C.text, marginBottom: '4px' }}>{cat.name}</p>
                                    <p style={{ fontSize: '11px', color: C.accent, letterSpacing: '1px' }}>{cat.products_count} pieces</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* ── Featured Products (Temporarily Hidden) ── */}
            {/*
            {featuredProducts.length > 0 && (
                <section style={{ background: C.surface, padding: '96px 24px' }}>
                    <div style={{ maxWidth: '1400px', margin: '0 auto' }}>
                        <ScrollReveal>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '56px', flexWrap: 'wrap', gap: '16px' }}>
                            <div>
                                <p style={{ fontSize: '9px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, marginBottom: '12px' }}>Curated</p>
                                <h2 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(28px,3.5vw,48px)', fontWeight: 400, color: C.text }}>Featured Pieces</h2>
                            </div>
                            <Link href="/shop" style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted, textDecoration: 'none', borderBottom: `1px solid ${C.border}`, paddingBottom: '4px', transition: 'color .2s, border-color .2s' }}
                                onMouseEnter={e => { e.currentTarget.style.color = C.accent; e.currentTarget.style.borderColor = C.accent; }}
                                onMouseLeave={e => { e.currentTarget.style.color = C.muted; e.currentTarget.style.borderColor = C.border; }}>
                                View All
                            </Link>
                        </div>
                        </ScrollReveal>
                        <div className="products-responsive-grid">
                            {featuredProducts.map(p => <ProductCard key={p.id} product={p} />)}
                        </div>
                    </div>
                </section>
            )}
            */}

            {/* ── Brand Story ── */}
            <section style={{ padding: '120px 24px', background: C.bg, textAlign: 'center', position: 'relative', overflow: 'hidden' }}>
                <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', pointerEvents: 'none' }}>
                    <span style={{ fontFamily: "'Syne', sans-serif", fontSize: 'clamp(120px,20vw,280px)', fontWeight: 800, color: 'var(--forme-text-border)', opacity: 0.3, userSelect: 'none', letterSpacing: '12px' }}>FORME</span>
                </div>
                <ScrollReveal style={{ position: 'relative', maxWidth: '800px', margin: '0 auto' }}>
                    <p style={{ fontSize: '11px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accentLight || C.accent, marginBottom: '20px', fontWeight: 600 }}>OUR PHILOSOPHY</p>
                    <h2 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(32px,4.5vw,56px)', fontWeight: 400, color: C.text, lineHeight: 1.25, marginBottom: '18px' }}>
                        Made for every form of movement.
                    </h2>
                    <p style={{ fontFamily: "'Syne', sans-serif", fontSize: '18px', color: C.accentLight || C.accent, fontWeight: 600, letterSpacing: '2.5px', textTransform: 'uppercase', marginBottom: '24px' }}>
                        MOVE. PERFORM. LIVE.
                    </p>
                    <p style={{ fontSize: '16px', color: C.text, lineHeight: '1.85', marginBottom: '40px', maxWidth: '680px', margin: '0 auto 40px', fontWeight: 400, opacity: 0.95 }}>
                        Movement is part of taking care of ourselves. We created FORMÉ to bridge beautiful design, quality, function, inclusivity, and attainable pricing — because affordable should never mean forgettable.
                    </p>
                    <div style={{ display: 'flex', gap: '16px', justifyContent: 'center', flexWrap: 'wrap' }}>
                        <Link href="/about" className="shimmer-button" style={{ background: C.accent, color: '#fff', padding: '16px 36px', textDecoration: 'none', fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', display: 'inline-block', fontWeight: 500, borderRadius: '2px', transition: 'all .3s' }}>
                            Discover Our Story
                        </Link>
                        <Link href="/shop" style={{ border: `1px solid ${C.text}`, color: C.text, padding: '16px 36px', textDecoration: 'none', fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', display: 'inline-block', fontWeight: 500, borderRadius: '2px', transition: 'all .3s' }}
                            onMouseEnter={e => { e.currentTarget.style.borderColor = C.accent; e.currentTarget.style.color = C.accent; }}
                            onMouseLeave={e => { e.currentTarget.style.borderColor = C.text; e.currentTarget.style.color = C.text; }}>
                            Shop Collection
                        </Link>
                    </div>
                </ScrollReveal>
            </section>

            {/* ── New Arrivals ── */}
            {newArrivals.length > 0 && (
                <section className="home-section" style={{ padding: '96px 24px', maxWidth: '1400px', margin: '0 auto' }}>
                    <ScrollReveal>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '56px', flexWrap: 'wrap', gap: '16px' }}>
                        <div>
                            <p style={{ fontSize: '9px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, marginBottom: '12px' }}>Just In</p>
                            <h2 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(28px,3.5vw,48px)', fontWeight: 400, color: C.text }}>New Arrivals</h2>
                        </div>
                        <Link href="/collections/new-arrivals" style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.muted, textDecoration: 'none', borderBottom: `1px solid ${C.border}`, paddingBottom: '4px', transition: 'color .2s, border-color .2s' }}
                            onMouseEnter={e => { e.currentTarget.style.color = C.accent; e.currentTarget.style.borderColor = C.accent; }}
                            onMouseLeave={e => { e.currentTarget.style.color = C.muted; e.currentTarget.style.borderColor = C.border; }}>
                            See All
                        </Link>
                    </div>
                    </ScrollReveal>
                    <div className="products-responsive-grid">
                        {newArrivals.map(p => <ProductCard key={p.id} product={p} />)}
                    </div>
                </section>
            )}

            {/* ── Newsletter strip ── */}
            <section style={{ background: C.surface, borderTop: `1px solid ${C.border}`, padding: '72px 24px', textAlign: 'center' }}>
                <p style={{ fontSize: '9px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, marginBottom: '16px' }}>Stay Close</p>
                <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(22px,3vw,36px)', color: C.text, marginBottom: '12px', fontWeight: 400 }}>Get First Access</h3>
                <p style={{ fontSize: '13px', color: C.muted, marginBottom: '32px' }}>New arrivals, exclusive offers and style notes — straight to your inbox.</p>
                <form onSubmit={e => e.preventDefault()} style={{ display: 'flex', gap: '0', maxWidth: '440px', margin: '0 auto' }}>
                    <input type="email" placeholder="Your email address"
                        style={{ flex: 1, background: C.bg, border: `1px solid ${C.border}`, borderRight: 'none', padding: '14px 18px', fontSize: '13px', color: C.text, outline: 'none', fontFamily: "'DM Sans', sans-serif" }} />
                    <button type="submit" style={{ background: C.accent, border: 'none', color: '#fff', padding: '14px 24px', fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', cursor: 'pointer', fontWeight: 500, whiteSpace: 'nowrap', transition: 'background .2s' }}
                        onMouseEnter={e => (e.currentTarget.style.background = '#B86878')}
                        onMouseLeave={e => (e.currentTarget.style.background = C.accent)}>
                        Subscribe
                    </button>
                </form>
            </section>
        </>
    );
}
