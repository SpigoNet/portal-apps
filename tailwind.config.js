import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Modules/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                headline: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                label: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                background: '#0d141d',
                'on-background': '#dce3f0',
                surface: '#0d141d',
                'surface-dim': '#0d141d',
                'surface-bright': '#333a44',
                'surface-container-lowest': '#080f17',
                'surface-container-low': '#151c25',
                'surface-container': '#192029',
                'surface-container-high': '#232a34',
                'surface-container-highest': '#2e353f',
                'on-surface': '#dce3f0',
                'on-surface-variant': '#c5c8b7',
                'outline': '#8f9282',
                'outline-variant': '#44483b',
                'surface-tint': '#b3d17a',
                primary: '#ffffff',
                'on-primary': '#243600',
                'primary-container': '#ceee93',
                'primary-fixed': '#ceee93',
                'primary-fixed-dim': '#b3d17a',
                'inverse-primary': '#4d661c',
                secondary: '#cbc3d8',
                'on-secondary': '#322e3e',
                'secondary-container': '#4e495a',
                tertiary: '#ffffff',
                'on-tertiary': '#322f37',
                'tertiary-container': '#e7e0eb',
                error: '#ffb4ab',
                'on-error': '#690005',
                'error-container': '#93000a',
                lime: '#D9F99D',
            },
            borderRadius: {
                'sm': '0.25rem',
                'DEFAULT': '0.5rem',
                'md': '0.75rem',
                'lg': '1rem',
                'xl': '1.5rem',
                'full': '9999px',
            },
            spacing: {
                'container-margin': '24px',
                'gutter': '16px',
                'card-padding': '24px',
                'stack-gap': '12px',
                'section-gap': '48px',
            },
            typography: {
                'headline-xl': {
                    fontFamily: 'Plus Jakarta Sans',
                    fontSize: '40px',
                    fontWeight: '700',
                    lineHeight: '1.2',
                },
                'headline-lg': {
                    fontFamily: 'Plus Jakarta Sans',
                    fontSize: '28px',
                    fontWeight: '600',
                    lineHeight: '1.3',
                },
                'headline-md': {
                    fontFamily: 'Plus Jakarta Sans',
                    fontSize: '20px',
                    fontWeight: '600',
                    lineHeight: '1.4',
                    letterSpacing: '0.05em',
                },
                'body-lg': {
                    fontFamily: 'Inter',
                    fontSize: '18px',
                    fontWeight: '400',
                    lineHeight: '1.6',
                },
                'body-md': {
                    fontFamily: 'Inter',
                    fontSize: '16px',
                    fontWeight: '400',
                    lineHeight: '1.5',
                },
                'body-sm': {
                    fontFamily: 'Inter',
                    fontSize: '14px',
                    fontWeight: '400',
                    lineHeight: '1.5',
                },
                'label-caps': {
                    fontFamily: 'Inter',
                    fontSize: '12px',
                    fontWeight: '700',
                    lineHeight: '1',
                    letterSpacing: '0.1em',
                },
            },
        },
    },

    plugins: [forms],
};
