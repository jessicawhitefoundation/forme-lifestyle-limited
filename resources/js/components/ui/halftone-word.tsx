// HalftoneWord: one word printed as a field of halftone dots, in the font, size
// and colour of the text around it. The dots part around the pointer on springs
// and warm toward the accent colour as they are pushed off their place; a tap or
// click sends a shock ring through the word; one ring plays as it appears and
// a gentle one runs through it every few seconds after that.
// Adapted from the "Halftone Wordmark Footer" component for a single inline word.
//
// The real text stays in the DOM (transparent) underneath the canvas, so screen
// readers, search engines, copy and line layout all see a normal word. Visitors
// who prefer reduced motion get the plain word with no canvas.

import * as React from 'react';

/** Dot radius for a cell coverage in [0, 1]: area-true, so tone stays even. */
export function halftoneRadius(coverage: number, gap: number) {
    const c = coverage < 0 ? 0 : coverage > 1 ? 1 : coverage;
    return Math.sqrt(c) * gap * 0.56;
}

/**
 * One step of a dot's spring back home, plus the push from a pointer at (px, py)
 * within radius r. Returns [x, y, vx, vy].
 */
export function halftoneStep(x: number, y: number, vx: number, vy: number, hx: number, hy: number, px: number, py: number, r: number) {
    const dx = x - px;
    const dy = y - py;
    const d2 = dx * dx + dy * dy;
    if (r > 0 && d2 < r * r) {
        const d = Math.sqrt(d2) + 0.001;
        const f = (1 - d / r) * 2.6;
        vx += (dx / d) * f;
        vy += (dy / d) * f;
    }
    vx = (vx + (hx - x) * 0.05) * 0.84;
    vy = (vy + (hy - y) * 0.05) * 0.84;
    return [x + vx, y + vy, vx, vy];
}

/** Any CSS colour → [r, g, b], by letting a 1×1 canvas parse it. */
function toRgb(color: string, fallback: [number, number, number]): [number, number, number] {
    const c = document.createElement('canvas');
    c.width = c.height = 1;
    const g = c.getContext('2d', { willReadFrequently: true });
    if (!g || !color) return fallback;
    g.fillStyle = color;
    g.fillRect(0, 0, 1, 1);
    const d = g.getImageData(0, 0, 1, 1).data;
    return [d[0], d[1], d[2]];
}

interface HalftoneWordProps {
    text: string;
    /** CSS colour (or var()) of the dots at rest. Defaults to the site text colour. */
    inkColor?: string;
    /** CSS colour (or var()) the dots warm toward when pushed. Defaults to the site accent. */
    warmColor?: string;
    /** A gentle ripple runs through the word this often (ms) while it's on screen; 0 turns it off. */
    idleEvery?: number;
    className?: string;
    style?: React.CSSProperties;
}

/** Resolve `var(--name)` from the root (theme variables don't transition, unlike computed colours). */
function resolveColor(color: string) {
    const m = /^var\((--[^),\s]+)\)$/.exec(color.trim());
    return m ? getComputedStyle(document.documentElement).getPropertyValue(m[1]).trim() : color;
}

export default function HalftoneWord({ text, inkColor = 'var(--forme-text)', warmColor = 'var(--forme-accent)', idleEvery = 6000, className, style }: HalftoneWordProps) {
    const wrapRef = React.useRef<HTMLSpanElement | null>(null);
    const canvasRef = React.useRef<HTMLCanvasElement | null>(null);
    const probeRef = React.useRef<HTMLSpanElement | null>(null);
    const [reduced, setReduced] = React.useState(false);

    React.useEffect(() => {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        setReduced(mq.matches);
        const on = () => setReduced(mq.matches);
        mq.addEventListener('change', on);
        return () => mq.removeEventListener('change', on);
    }, []);

    React.useEffect(() => {
        if (reduced) return;
        const wrap = wrapRef.current;
        const canvas = canvasRef.current;
        const probe = probeRef.current;
        if (!wrap || !canvas || !probe) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let w = 0, h = 0, bleed = 0, dpr = 1, gap = 3, push = 60;
        let px = -1e5, py = -1e5;
        let raf = 0;
        let visible = true;
        let awake = true;
        let ring = { x: 0, y: 0, t: -1 };
        let introPlayed = false;
        let dots = new Float32Array(0); // x, y, vx, vy, hx, hy, r per dot
        let count = 0;
        let ink: [number, number, number] = [0, 0, 0];
        let warm: [number, number, number] = [107, 114, 48];

        const layout = () => {
            const cs = getComputedStyle(wrap);
            const rect = wrap.getBoundingClientRect();
            const fontSize = parseFloat(cs.fontSize) || 48;
            bleed = Math.round(fontSize * 0.6); // room for dots pushed past the letters
            w = Math.max(1, Math.round(rect.width)) + bleed * 2;
            h = Math.max(1, Math.round(rect.height)) + bleed * 2;
            gap = Math.max(2, Math.round((fontSize / 26) * 2) / 2);
            push = fontSize * 0.9;
            dpr = Math.min(window.devicePixelRatio || 1, 2);
            canvas.style.left = canvas.style.top = -bleed + 'px';
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            canvas.width = Math.round(w * dpr);
            canvas.height = Math.round(h * dpr);

            ink = toRgb(resolveColor(inkColor), [0, 0, 0]);
            warm = toRgb(resolveColor(warmColor), [107, 114, 48]);

            // Draw the word on a 1x mask exactly where the DOM puts it: the probe
            // is an empty inline-block whose bottom edge sits on the baseline.
            const mask = document.createElement('canvas');
            mask.width = w;
            mask.height = h;
            const m = mask.getContext('2d', { willReadFrequently: true });
            if (!m) return;
            m.font = `${cs.fontStyle} ${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
            if ('letterSpacing' in m) (m as CanvasRenderingContext2D & { letterSpacing: string }).letterSpacing = cs.letterSpacing === 'normal' ? '0px' : cs.letterSpacing;
            m.textBaseline = 'alphabetic';
            m.fillStyle = '#000';
            const baseline = probe.getBoundingClientRect().bottom - rect.top;
            m.fillText(text, bleed, bleed + baseline);
            const img = m.getImageData(0, 0, w, h).data;

            const cols = Math.ceil(w / gap);
            const rows = Math.ceil(h / gap);
            const tmp: number[] = [];
            for (let gy = 0; gy < rows; gy++) {
                for (let gx = 0; gx < cols; gx++) {
                    let sum = 0;
                    for (let sy = 0; sy < 3; sy++) {
                        for (let sx = 0; sx < 3; sx++) {
                            const x = Math.min(w - 1, Math.floor(gx * gap + ((sx + 0.5) * gap) / 3));
                            const y = Math.min(h - 1, Math.floor(gy * gap + ((sy + 0.5) * gap) / 3));
                            sum += img[(y * w + x) * 4 + 3];
                        }
                    }
                    const cov = sum / (9 * 255);
                    if (cov < 0.06) continue;
                    const hx = gx * gap + gap / 2;
                    const hy = gy * gap + gap / 2;
                    tmp.push(hx, hy, 0, 0, hx, hy, halftoneRadius(cov, gap));
                }
            }
            dots = new Float32Array(tmp);
            count = tmp.length / 7;
            awake = true;
        };

        const draw = (now: number) => {
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.clearRect(0, 0, w, h);
            const ringAge = ring.t < 0 ? 1e9 : now - ring.t;
            const ringR = ringAge * 0.45;
            let moving = false;
            for (let i = 0; i < count; i++) {
                const j = i * 7;
                let x = dots[j], y = dots[j + 1], vx = dots[j + 2], vy = dots[j + 3];
                const hx = dots[j + 4], hy = dots[j + 5];
                if (ringAge < 900) {
                    const dx = hx - ring.x;
                    const dy = hy - ring.y;
                    const d = Math.sqrt(dx * dx + dy * dy) + 0.001;
                    const band = 1 - Math.min(1, Math.abs(d - ringR) / 18);
                    if (band > 0) {
                        const f = band * 2.4 * (1 - ringAge / 900);
                        vx += (dx / d) * f;
                        vy += (dy / d) * f;
                    }
                }
                const s = halftoneStep(x, y, vx, vy, hx, hy, px, py, push);
                x = s[0]; y = s[1]; vx = s[2]; vy = s[3];
                if (Math.abs(vx) + Math.abs(vy) > 0.02 || Math.abs(x - hx) + Math.abs(y - hy) > 0.05) moving = true;
                else { x = hx; y = hy; vx = 0; vy = 0; }
                dots[j] = x; dots[j + 1] = y; dots[j + 2] = vx; dots[j + 3] = vy;

                const k = Math.min(1, Math.hypot(x - hx, y - hy) / 18);
                ctx.fillStyle = `rgb(${Math.round(ink[0] + (warm[0] - ink[0]) * k)},${Math.round(ink[1] + (warm[1] - ink[1]) * k)},${Math.round(ink[2] + (warm[2] - ink[2]) * k)})`;
                ctx.beginPath();
                ctx.arc(x, y, dots[j + 6], 0, 6.2832);
                ctx.fill();
            }
            awake = moving || ringAge < 900 || px > -1e4;
        };

        const loop = (now: number) => {
            raf = 0;
            if (!visible) return;
            draw(now);
            if (awake) raf = requestAnimationFrame(loop);
        };
        const wake = () => {
            awake = true;
            if (!raf && visible) raf = requestAnimationFrame(loop);
        };

        // Track the pointer page-wide so the canvas never blocks clicks on
        // anything around it; only pointers near the word have any effect.
        const onMove = (e: PointerEvent) => {
            const b = canvas.getBoundingClientRect();
            const x = e.clientX - b.left;
            const y = e.clientY - b.top;
            const near = x > -push && y > -push && x < b.width + push && y < b.height + push;
            if (near) { px = x; py = y; lastPointer = performance.now(); wake(); }
            else if (px > -1e4) { px = -1e5; py = -1e5; wake(); }
        };
        const onLeave = () => { px = -1e5; py = -1e5; wake(); };
        const onDown = (e: PointerEvent) => {
            const b = canvas.getBoundingClientRect();
            ring = { x: e.clientX - b.left, y: e.clientY - b.top, t: performance.now() };
            wake();
        };
        // Idle ripple: travels from a random point along the word, so it keeps
        // moving for visitors who can't hover (phones). Skipped while a pointer
        // is near or a ring is still running.
        let lastPointer = 0;
        const idle = idleEvery > 0 ? window.setInterval(() => {
            const now = performance.now();
            if (!visible || document.hidden || now - lastPointer < 2500 || (ring.t >= 0 && now - ring.t < 900)) return;
            ring = { x: bleed + Math.random() * (w - bleed * 2), y: h / 2, t: now };
            wake();
        }, idleEvery) : 0;

        const playIntro = () => {
            if (introPlayed) return;
            introPlayed = true;
            // One ripple from the left edge once the heading has faded in.
            window.setTimeout(() => { ring = { x: bleed, y: h / 2, t: performance.now() }; wake(); }, 900);
        };

        layout();
        draw(performance.now());
        wake();

        window.addEventListener('pointermove', onMove, { passive: true });
        document.documentElement.addEventListener('pointerleave', onLeave);
        wrap.addEventListener('pointerdown', onDown);
        const ro = new ResizeObserver(() => { layout(); wake(); });
        ro.observe(wrap);
        const io = new IntersectionObserver((entries) => {
            visible = entries[0] ? entries[0].isIntersecting : true;
            if (visible) { wake(); playIntro(); }
        });
        io.observe(wrap);
        // Light/dark switch: the site toggles a `dark` class on <html>.
        const mo = new MutationObserver(() => { layout(); wake(); });
        mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        // Re-set the word once the web font has loaded.
        let alive = true;
        document.fonts?.ready.then(() => { if (alive) { layout(); wake(); } });

        return () => {
            alive = false;
            cancelAnimationFrame(raf);
            window.clearInterval(idle);
            ro.disconnect();
            io.disconnect();
            mo.disconnect();
            window.removeEventListener('pointermove', onMove);
            document.documentElement.removeEventListener('pointerleave', onLeave);
            wrap.removeEventListener('pointerdown', onDown);
        };
    }, [reduced, text, inkColor, warmColor, idleEvery]);

    if (reduced) {
        return <span className={className} style={style}>{text}</span>;
    }

    return (
        <span ref={wrapRef} className={className} style={{ position: 'relative', display: 'inline-block', cursor: 'default', ...style }}>
            {/* The real word: invisible but in place for layout, selection and screen readers. */}
            <span style={{ color: 'transparent' }}>{text}</span>
            <span ref={probeRef} aria-hidden="true" style={{ display: 'inline-block', width: 0, height: 0, verticalAlign: 'baseline' }} />
            <canvas ref={canvasRef} aria-hidden="true" style={{ position: 'absolute', pointerEvents: 'none', display: 'block', maxWidth: 'none' }} />
        </span>
    );
}
