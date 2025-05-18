// Add TailwindCSS via CDN
const tailwindScript = document.createElement('script');
tailwindScript.src = 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4';
document.head.appendChild(tailwindScript);

// Initialize Tailwind with custom config
document.addEventListener('DOMContentLoaded', function() {
    if (window.tailwind) {
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            '50': '#f0f9ff',
                            '100': '#e0f2fe',
                            '200': '#bae6fd',
                            '300': '#7dd3fc',
                            '400': '#38bdf8',
                            '500': '#0ea5e9',
                            '600': '#0284c7',
                            '700': '#0369a1',
                            '800': '#075985',
                            '900': '#0c4a6e',
                            '950': '#082f49',
                        },
                    }
                }
            }
        };
    }
}); 