import React, { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    public state: State = {
        hasError: false,
        error: null,
    };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('Uncaught error caught by ErrorBoundary:', error, errorInfo);

        // Send the details to the server log, since this only happens in visitors' browsers.
        try {
            const report = new Blob([JSON.stringify({
                message: String(error?.message ?? error).slice(0, 300),
                stack: String(error?.stack ?? '').slice(0, 2000),
                componentStack: String(errorInfo?.componentStack ?? '').slice(0, 1500),
                url: window.location.href.slice(0, 300),
            })], { type: 'application/json' });
            navigator.sendBeacon?.('/client-error', report);
        } catch {
            // Reporting must never cause a second error.
        }
    }

    public render() {
        if (this.state.hasError) {
            return (
                <div style={{
                    minHeight: '60vh',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '40px 20px',
                    fontFamily: "'DM Sans', sans-serif",
                    backgroundColor: '#FAF8F5',
                    color: '#1a1a1a',
                }}>
                    <div style={{
                        maxWidth: '480px',
                        width: '100%',
                        backgroundColor: '#ffffff',
                        border: '1px solid #EFEAE6',
                        borderRadius: '4px',
                        padding: '40px 28px',
                        textAlign: 'center',
                        boxShadow: '0 8px 24px rgba(0,0,0,0.04)',
                    }}>
                        <span style={{
                            display: 'inline-block',
                            fontSize: '11px',
                            letterSpacing: '2px',
                            textTransform: 'uppercase',
                            color: '#6F1D2F',
                            background: '#F8F1F2',
                            padding: '4px 12px',
                            borderRadius: '30px',
                            fontWeight: 600,
                            marginBottom: '16px',
                        }}>
                            Display Notice
                        </span>
                        <h2 style={{
                            fontFamily: "'Playfair Display', serif",
                            fontSize: '26px',
                            fontWeight: 400,
                            margin: '0 0 12px 0',
                        }}>
                            Something paused our display.
                        </h2>
                        <p style={{
                            fontSize: '14px',
                            color: '#736b63',
                            lineHeight: 1.6,
                            marginBottom: '28px',
                        }}>
                            We encountered an issue rendering this section. Reloading the page usually resolves this immediately.
                        </p>
                        <div style={{ display: 'flex', gap: '12px', justifyContent: 'center' }}>
                            <button
                                onClick={() => window.location.reload()}
                                style={{
                                    backgroundColor: '#6F1D2F',
                                    color: '#ffffff',
                                    border: 'none',
                                    padding: '12px 24px',
                                    fontSize: '11px',
                                    letterSpacing: '1.5px',
                                    textTransform: 'uppercase',
                                    fontWeight: 600,
                                    borderRadius: '2px',
                                    cursor: 'pointer',
                                }}
                            >
                                Reload Page
                            </button>
                            <a
                                href="/"
                                style={{
                                    border: '1px solid #EFEAE6',
                                    color: '#1a1a1a',
                                    textDecoration: 'none',
                                    padding: '12px 20px',
                                    fontSize: '11px',
                                    letterSpacing: '1.5px',
                                    textTransform: 'uppercase',
                                    fontWeight: 500,
                                    borderRadius: '2px',
                                    display: 'inline-block',
                                }}
                            >
                                Return Home
                            </a>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

