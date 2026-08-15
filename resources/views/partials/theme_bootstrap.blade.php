<script>
    (function () {
        function resolveDark(preference) {
            if (preference === 'dark') return true;
            if (preference === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        window.applyAttendanceTheme = function (val) {
            try {
                var preference = val || localStorage.getItem('attendance-theme') || 'system';
                if (val) {
                    localStorage.setItem('attendance-theme', val);
                }
                var isDark = resolveDark(preference);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                try {
                    if (val === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else if (val === 'light') {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (err) {}
            }
        };

        // Run synchronously before render
        window.applyAttendanceTheme();

        // Listen to OS system theme changes
        try {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var handleSystemChange = function (e) {
                var preference = localStorage.getItem('attendance-theme') || 'system';
                if (preference === 'system') {
                    if (e.matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            };
            if (mq.addEventListener) {
                mq.addEventListener('change', handleSystemChange);
            } else if (mq.addListener) {
                mq.addListener(handleSystemChange);
            }
        } catch (e) {}
    })();
</script>
