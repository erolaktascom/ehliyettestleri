const theme = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        navy: { 800: '#071b49', 900: '#00236f', 950: '#041030' },
                        brand: { 500: '#2563eb', 600: '#1d4ed8' }
                    }
                }
            }
        };
module.exports = { ...theme, content: ["./resources/views/**/*.blade.php", "./app/**/*.php", "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php"] };
