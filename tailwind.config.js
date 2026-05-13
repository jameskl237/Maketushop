import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
    	extend: {
            backgroundImage: {
                'mesh-gradient': 'var(--mesh-gradient)',
                'glass-gradient': 'linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%)',
                'glass-gradient-dark': 'linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.02) 100%)',
            },
    		fontFamily: {
    			sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
    			display: ['Bricolage Grotesque', ...defaultTheme.fontFamily.sans],
    		},
    		borderRadius: {
    			lg: 'var(--radius)',
    			md: 'calc(var(--radius) - 2px)',
    			sm: 'calc(var(--radius) - 4px)'
    		},
    		colors: {
    			background: 'rgb(var(--bg-rgb) / <alpha-value>)',
    			foreground: 'rgb(var(--text-rgb) / <alpha-value>)',
                glass: {
                    DEFAULT: 'var(--glass-bg)',
                    border: 'var(--glass-border)',
                },
    			card: {
    				DEFAULT: 'rgb(var(--card-rgb) / <alpha-value>)',
    				foreground: 'rgb(var(--text-rgb) / <alpha-value>)'
    			},
    			popover: {
    				DEFAULT: 'rgb(var(--card-rgb) / <alpha-value>)',
    				foreground: 'rgb(var(--text-rgb) / <alpha-value>)'
    			},
    			primary: {
    				DEFAULT: 'rgb(var(--primary-rgb) / <alpha-value>)',
    				hover: 'rgb(var(--primary-hover-rgb) / <alpha-value>)',
    				foreground: '#FFFFFF'
    			},
    			secondary: {
    				DEFAULT: 'rgb(var(--bg-rgb) / <alpha-value>)',
    				foreground: 'rgb(var(--text-rgb) / <alpha-value>)'
    			},
    			muted: {
    				DEFAULT: 'rgb(var(--bg-rgb) / <alpha-value>)',
    				foreground: 'rgb(var(--text-muted-rgb) / <alpha-value>)'
    			},
    			accent: {
    				DEFAULT: 'rgb(var(--bg-rgb) / <alpha-value>)',
    				foreground: 'rgb(var(--text-rgb) / <alpha-value>)'
    			},
    			destructive: {
    				DEFAULT: 'rgb(var(--orange-rgb) / <alpha-value>)',
    				foreground: '#FFFFFF'
    			},
    			border: 'var(--border)',
    			input: 'var(--border)',
    			ring: 'var(--primary)',
    			orange: 'rgb(var(--orange-rgb) / <alpha-value>)',
    			shop: {
    				bg: 'rgb(var(--bg-rgb) / <alpha-value>)',
    				text: 'rgb(var(--text-rgb) / <alpha-value>)',
    				muted: 'rgb(var(--text-muted-rgb) / <alpha-value>)',
    				light: 'rgb(var(--text-light-rgb) / <alpha-value>)',
    				green: 'rgb(var(--green-rgb) / <alpha-value>)',
    				amber: 'rgb(var(--amber-rgb) / <alpha-value>)'
    			},
    			chart: {
    				'1': 'var(--primary)',
    				'2': 'var(--orange)',
    				'3': 'var(--green)',
    				'4': 'var(--amber)',
    				'5': 'var(--text-muted)'
    			}
    		}
    	}
    },

    plugins: [forms, require("tailwindcss-animate")],
};
