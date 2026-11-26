const theme = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        navy: { 800: '#071b49', 900: '#00236f', 950: '#030c22' },
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        };
module.exports = { ...theme, content: ["./resources/views/**/*.blade.php", "./app/**/*.php", "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php"] };
