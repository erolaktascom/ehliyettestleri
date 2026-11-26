const theme = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f4ff',
                            100: '#e0ebff',
                            200: '#c7d9ff',
                            300: '#9ebeff',
                            400: '#6d97fb',
                            500: '#4871f4',
                            600: '#2d4ee7',
                            700: '#2339d2',
                            800: '#1e30aa',
                            900: '#1e2d85',
                            950: '#141c52',
                        },
                        canvas: '#f8faff',
                        ink: '#111827',
                        muted: '#64748b',
                        success: '#10b981',
                    }
                }
            }
        };
module.exports = { ...theme, content: ["./resources/views/**/*.blade.php", "./app/**/*.php", "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php"] };
