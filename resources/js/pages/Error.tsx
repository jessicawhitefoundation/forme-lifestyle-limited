import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface ErrorProps {
    status: number;
    message?: string;
}

const errorDetails: Record<number, { title: string; headline: string; description: string; primaryBtn: string; primaryHref: string }> = {
    404: {
        title: 'Page Not Found',
        headline: 'This piece seems out of reach.',
        description: 'The item, collection, or page you are looking for may have moved, been renamed, or is currently unavailable.',
        primaryBtn: 'Explore Collection',
        primaryHref: '/shop',
    },
    500: {
        title: 'Server Error',
        headline: 'A temporary pause in motion.',
        description: 'We encountered an unexpected issue while processing your request. Our team has been alerted, and service is being restored.',
        primaryBtn: 'Return to Store',
        primaryHref: '/',
    },
    403: {
        title: 'Access Restricted',
        headline: 'Restricted Area.',
        description: 'You do not have permission to access this page. Please sign in with an authorized account or return to the main store.',
        primaryBtn: 'Sign In',
        primaryHref: '/login',
    },
    419: {
        title: 'Session Expired',
        headline: 'Your session took a breather.',
        description: 'For your security, page sessions expire after a period of inactivity. Please refresh the page to continue.',
        primaryBtn: 'Refresh Page',
        primaryHref: '#',
    },
    503: {
        title: 'Under Maintenance',
        headline: 'Enhancing your active experience.',
        description: 'We are currently making brief enhancements to our store. We will be back online shortly. Thank you for your patience!',
        primaryBtn: 'Check Again',
        primaryHref: '#',
    },
};

export default function Error({ status, message }: ErrorProps) {
    const error = errorDetails[status] || {
        title: 'Error',
        headline: 'Something went wrong.',
        description: message || 'An unexpected error occurred. Please try again later.',
        primaryBtn: 'Return Home',
        primaryHref: '/',
    };

    const handlePrimaryClick = (e: React.MouseEvent) => {
        if (error.primaryHref === '#') {
            e.preventDefault();
            window.location.reload();
        }
    };

    return (
        <>
            <Head title={`${error.title} (${status})`} />

            <div style={{ minHeight: '75vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '60px 24px' }}>
                <div style={{
                    background: 'var(--forme-surface, #ffffff)',
                    border: '1px solid var(--forme-border, #EFEAE6)',
                    borderRadius: '4px',
                    maxWidth: '540px',
                    width: '100%',
                    padding: '48px 36px',
                    textAlign: 'center',
                    boxShadow: '0 10px 30px rgba(0, 0, 0, 0.03)',
                }}>
                    <span style={{
                        display: 'inline-block',
                        fontSize: '11px',
                        letterSpacing: '2.5px',
                        textTransform: 'uppercase',
                        color: 'var(--forme-accent, #6F1D2F)',
                        background: '#F8F1F2',
                        padding: '6px 14px',
                        borderRadius: '30px',
                        fontWeight: 600,
                        marginBottom: '20px',
                    }}>
                        {status} · {error.title}
                    </span>

                    <h1 style={{
                        fontFamily: "'Playfair Display', Georgia, serif",
                        fontSize: 'clamp(28px, 4vw, 36px)',
                        fontWeight: 400,
                        color: 'var(--forme-text, #1a1a1a)',
                        lineHeight: 1.25,
                        marginBottom: '16px',
                    }}>
                        {error.headline}
                    </h1>

                    <p style={{
                        fontSize: '15px',
                        color: 'var(--forme-muted, #736b63)',
                        lineHeight: 1.7,
                        marginBottom: '32px',
                    }}>
                        {error.description}
                    </p>

                    <div style={{ display: 'flex', gap: '12px', justifyContent: 'center', flexWrap: 'wrap' }}>
                        <Link
                            href={error.primaryHref}
                            onClick={handlePrimaryClick}
                            style={{
                                backgroundColor: 'var(--forme-accent, #6F1D2F)',
                                color: '#ffffff',
                                padding: '14px 28px',
                                textDecoration: 'none',
                                fontSize: '11px',
                                letterSpacing: '2px',
                                textTransform: 'uppercase',
                                fontWeight: 600,
                                borderRadius: '2px',
                                display: 'inline-block',
                            }}
                        >
                            {error.primaryBtn}
                        </Link>

                        <Link
                            href="/"
                            style={{
                                border: '1px solid var(--forme-border, #EFEAE6)',
                                color: 'var(--forme-text, #1a1a1a)',
                                padding: '14px 24px',
                                textDecoration: 'none',
                                fontSize: '11px',
                                letterSpacing: '2px',
                                textTransform: 'uppercase',
                                fontWeight: 500,
                                borderRadius: '2px',
                                display: 'inline-block',
                            }}
                        >
                            Back to Home
                        </Link>
                    </div>

                    <div style={{ marginTop: '36px', paddingTop: '24px', borderTop: '1px solid var(--forme-border, #EFEAE6)', fontSize: '12px', color: 'var(--forme-muted, #736b63)' }}>
                        Need assistance? WhatsApp <a href="https://wa.me/2347026126265" target="_blank" rel="noopener noreferrer" style={{ color: 'var(--forme-accent, #6F1D2F)', textDecoration: 'none', fontWeight: 500 }}>+234 702 612 6265</a>
                    </div>
                </div>
            </div>
        </>
    );
}

