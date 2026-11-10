import { Head, Link, usePage } from '@inertiajs/react';
import React from 'react';

type PageKey = 'faq' | 'contact' | 'privacy' | 'terms';

interface Section {
    title: string;
    body: React.ReactNode;
}

interface StoreContact {
    email: string;
    whatsapp_display: string;
    whatsapp_number: string;
}

function buildPages(contact: StoreContact, threshold: string, freeDelivery: boolean): Record<PageKey, { title: string; intro: string; sections: Section[] }> {
    const emailLink = <a href={`mailto:${contact.email}`} style={{ color: 'var(--forme-accent)' }}>{contact.email}</a>;
    const whatsappLink = <a href={`https://wa.me/${contact.whatsapp_number}`} target="_blank" rel="noopener noreferrer" style={{ color: 'var(--forme-accent)' }}>{contact.whatsapp_display}</a>;

    return {
        faq: {
            title: 'Frequently Asked Questions',
            intro: 'Quick answers about ordering, delivery and returns.',
            sections: [
                { title: 'Do I need an account to order?', body: 'No. You can check out as a guest with your email and delivery details. An account is optional and lets you see past orders and save a card for faster checkout.' },
                { title: 'How can I pay?', body: 'We accept debit and credit cards, bank transfer and USSD. Payments are processed securely by Paystack.' },
                { title: 'How much is delivery?', body: 'Delivery depends on your location. You can estimate it in your bag, and you will see the exact cost at checkout before you pay.' },
                ...(freeDelivery ? [{ title: 'Do you offer free delivery?', body: `Yes, on orders of ₦${threshold} or more delivered within Lagos. It does not apply to deliveries outside Lagos.` }] : []),
                { title: 'How long does delivery take?', body: 'Orders take 1–2 business days to process. Delivery then takes 2–5 business days within Lagos and 3–7 business days to other states.' },
                { title: 'Do you ship outside Nigeria?', body: <>We currently deliver within Nigeria. For an international order, please contact us at {emailLink}.</> },
                { title: 'How do I track my order?', body: <>Use the <Link href="/track-order" style={{ color: 'var(--forme-accent)' }}>Track Your Order</Link> page with your order reference and email address.</> },
                { title: 'Can I return or exchange an item?', body: <>Yes, unused items in their original condition with tags attached can be returned or exchanged within 3 days of delivery. See our <Link href="/policy" style={{ color: 'var(--forme-accent)' }}>Shipping &amp; Returns policy</Link>.</> },
            ],
        },
        contact: {
            title: 'Contact Us',
            intro: 'Questions about an order, sizing or delivery? We are happy to help.',
            sections: [
                { title: 'Email', body: emailLink },
                { title: 'WhatsApp', body: whatsappLink },
                { title: 'About an existing order', body: 'Please include your order reference so we can help you faster.' },
            ],
        },
        privacy: {
            title: 'Privacy Policy',
            intro: 'How FORMÉ collects and uses your personal information when you shop with us.',
            sections: [
                { title: 'Information we collect', body: 'Your name, email address, phone number and delivery address when you order or create an account, along with the items you buy. We also keep basic technical information such as your IP address to keep the site secure.' },
                { title: 'How we use it', body: 'To process and deliver your orders, send order and delivery updates, respond to your questions, prevent fraud, and meet our legal and accounting obligations.' },
                { title: 'Card payments', body: 'Payments are handled by Paystack. We do not store your card number or security code. If you choose to save a card, Paystack holds the card details and we keep only a reference to it, the card type and its last four digits. You can remove a saved card from your account at any time.' },
                { title: 'Who we share it with', body: 'Only the service providers needed to run the store: Paystack for payments, our delivery partner for your name, phone number and address, and our email and hosting providers. We do not sell your personal information.' },
                { title: 'Cookies', body: 'We use essential cookies to keep your bag and sign-in working, and to remember your display preference.' },
                { title: 'How long we keep it', body: 'We keep your information for as long as needed to provide our services and meet our legal and accounting obligations.' },
                { title: 'Your rights', body: <>Under the Nigeria Data Protection Act 2023, you may ask to access, correct or delete your personal information, or object to how we use it. To do so, email {emailLink}. You may also complain to the Nigeria Data Protection Commission.</> },
            ],
        },
        terms: {
            title: 'Terms of Service',
            intro: 'These terms apply when you shop on the FORMÉ website.',
            sections: [
                { title: 'Orders', body: 'Your order is confirmed once we receive full payment. We may cancel and fully refund an order if an item is unavailable or a price was shown incorrectly.' },
                { title: 'Prices and payment', body: 'Prices are in Nigerian Naira. Delivery charges are shown at checkout before you pay.' },
                { title: 'Delivery', body: <>Delivery times are estimates. Please check that your delivery details are correct, because extra costs caused by incorrect details are your responsibility. See our <Link href="/policy" style={{ color: 'var(--forme-accent)' }}>Shipping &amp; Returns policy</Link>.</> },
                ...(freeDelivery ? [{ title: 'Free delivery', body: `Free delivery applies to orders of ₦${threshold} or more delivered to an address within Lagos. If the address is outside Lagos, we will contact you about the delivery charge before dispatch.` }] : []),
                { title: 'Cancellations and returns', body: <>Orders can be cancelled or changed within 2 hours of payment if they have not been dispatched. Returns and exchanges are covered by our <Link href="/policy" style={{ color: 'var(--forme-accent)' }}>Shipping &amp; Returns policy</Link>.</> },
                { title: 'Your account', body: 'If you create an account, please keep your password safe. You are responsible for activity on your account.' },
                { title: 'Governing law', body: 'These terms are governed by the laws of the Federal Republic of Nigeria.' },
                { title: 'Contact', body: <>Questions about these terms? Email {emailLink}.</> },
            ],
        },
    };
}

export default function Info({ page }: { page: PageKey }) {
    const { props } = usePage();
    const contact = (props as any).store_contact as StoreContact;
    const setting = (props as any).store_setting as { free_shipping_enabled: boolean; free_shipping_threshold: number };
    const threshold = Number(setting?.free_shipping_threshold ?? 0).toLocaleString();
    const content = buildPages(contact, threshold, !!setting?.free_shipping_enabled)[page];

    return (
        <>
            <Head title={content.title} />
            <div className="page-pad" style={{ maxWidth: '760px', margin: '0 auto', padding: '64px 24px 80px' }}>
                <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: 'clamp(32px, 5vw, 46px)', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '12px' }}>{content.title}</h1>
                <p style={{ fontSize: '15px', color: 'var(--forme-muted)', lineHeight: 1.7, marginBottom: '40px' }}>{content.intro}</p>

                {content.sections.map((section) => (
                    <section key={section.title} style={{ borderTop: '1px solid var(--forme-border)', padding: '24px 0' }}>
                        <h2 style={{ fontSize: '16px', fontWeight: 600, color: 'var(--forme-text)', marginBottom: '8px' }}>{section.title}</h2>
                        <div style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: 1.8 }}>{section.body}</div>
                    </section>
                ))}
            </div>
        </>
    );
}
