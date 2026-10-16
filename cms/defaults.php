<?php
/**
 * Default site content — the approved wording from the brief.
 * Saved edits live in data/content.json and override these values.
 */
return [
    'site' => [
        'title'       => 'Zoha Aero Consulting — Aviation R&D Consulting, USA and Europe',
        'description' => 'Zoha Aero Consulting researches markets, parts and processes for aviation businesses, and helps them develop and grow in the USA and Europe.',
        'url'         => 'https://www.your-domain.com',
    ],
    'hero' => [
        'headline' => 'Aviation research & development consulting — USA and Europe.',
        'subline'  => 'We research markets, parts and processes for aviation businesses, and help them develop and grow on both sides of the Atlantic.',
        'button'   => 'Contact us',
        'highlights' => [
            ['title' => 'USA and Europe', 'text' => 'Research and support on both sides of the Atlantic.'],
            ['title' => 'Aircraft parts and components', 'text' => 'Markets, standards, certification and documentation.'],
            ['title' => 'Independent consultancy', 'text' => 'Based in Prishtinë, Kosovo.'],
        ],
    ],
    'services' => [
        'eyebrow' => 'What we do',
        'heading' => 'Research and development support for aviation businesses',
        'items'   => [
            ['title' => 'Market research', 'text' => 'Demand, pricing and supply trends for aircraft parts and components in the USA and Europe.'],
            ['title' => 'Technical & product research', 'text' => 'Parts, components, certification and documentation requirements across US and European standards.'],
            ['title' => 'Development support', 'text' => 'Helping aviation businesses plan new markets, build partner networks and set up working processes.'],
            ['title' => 'Research reports', 'text' => 'Clear findings and recommendations clients can act on.'],
        ],
    ],
    'process' => [
        'eyebrow' => 'How we work',
        'heading' => 'Three steps',
        'items'   => [
            ['title' => 'Brief', 'text' => 'We agree the question.'],
            ['title' => 'Research', 'text' => 'We gather and verify the information.'],
            ['title' => 'Report', 'text' => 'Findings and recommendations, with follow-up support if needed.'],
        ],
    ],
    'about' => [
        'eyebrow'     => 'Who we work with',
        'heading'     => 'About Zoha Aero Consulting',
        'who_title'   => 'Who we work with',
        'who_text'    => 'We work with aircraft parts suppliers and traders, operators, and aviation service providers.',
        'about_title' => 'About us',
        'about_text'  => 'Zoha Aero Consulting is an independent consultancy based in Prishtinë, Kosovo, working with clients in the USA and Europe.',
        'glance_title' => 'At a glance',
        'glance' => [
            ['label' => 'Based in', 'value' => 'Prishtinë, Kosovo'],
            ['label' => 'Markets', 'value' => 'USA and Europe'],
            ['label' => 'Focus', 'value' => 'Aircraft parts, components and processes'],
            ['label' => 'Clients', 'value' => 'Parts suppliers and traders, operators, aviation service providers'],
        ],
    ],
    'contact' => [
        'eyebrow' => 'Contact',
        'heading' => 'Get in touch',
        'intro'   => 'Tell us what you need to know. We will reply by email.',
        'address' => "Bekim Fehmiu 2\nPrishtinë, Kosovo",
        'email'   => 'info@your-domain.com',
        'phone'   => '+383 00 000 000',
    ],
    'company' => [
        'name'    => 'Zoha Aero Consulting L.L.C',
        'address' => 'Bekim Fehmiu 2, Prishtinë, Kosovo',
        'uid'     => '812329557',
    ],
    'form' => [
        'mail_to'   => 'info@your-domain.com',
        'mail_from' => 'website@your-domain.com',
        'success'   => 'Thank you. Your message has been sent — we will reply by email.',
        'error'     => 'Sorry, your message could not be sent. Please check the fields and try again, or email us directly.',
    ],
    'privacy' => [
        'updated' => '2026-10-16',
        'email'   => 'info@your-domain.com',
        'body'    => <<<'TXT'
This notice explains how {company} ("we", "us") handles personal data collected through this website. We process personal data in line with the Law No. 06/L-082 on Protection of Personal Data of the Republic of Kosovo and, where it applies, the EU General Data Protection Regulation (GDPR).

## Who is responsible

{company}
{address}
Unique Identification Number: {uid}
Privacy contact: {email}

## What we collect

When you use the contact form, we collect your name, email address and the message you write. When you email or call us, we receive the details you choose to share.

Like any website, our hosting server keeps short technical logs (such as IP address, date and time, and pages requested) to keep the site secure and working.

## Why we use it

- To reply to your enquiry and, if you ask, to discuss our services. The legal basis is the steps you ask us to take before a possible contract, and our legitimate interest in answering business enquiries.
- To protect the website against spam and misuse. The legal basis is our legitimate interest in running a secure website.

We do not use your data for marketing without your consent, and we do not sell it.

## Cookies and tracking

This website does not use cookies, analytics or tracking tools. Fonts are served from our own server, so no data is sent to third-party font services.

## Who receives your data

Your data is seen only by us and by the companies that host this website and our email, acting on our instructions. We do not share it with anyone else unless the law requires it.

## How long we keep it

We keep enquiries for as long as needed to deal with them and for up to 12 months afterwards, unless they lead to a business relationship, in which case we keep them for as long as the law requires. Server logs are deleted automatically after a short period.

## Your rights

You can ask us for access to your personal data, to correct or delete it, to restrict or object to how we use it, and to receive a copy of it. To use any of these rights, contact us at the address above. You also have the right to complain to the Information and Privacy Agency of the Republic of Kosovo (https://aip.rks-gov.net) or, if you are in the EU, to your local data protection authority.

## Changes

We may update this notice. The date at the top shows when it last changed.
TXT,
    ],
];
