// WP Time Progress
// Author: Ashraf Ali
// Website: https://ashrafali.net
// License: MIT

function TimeProgress() {
    const calculateProgress = () => {
        const now = new Date();
        const container = document.getElementById('wp-time-progress-container');
        if (!container) return;

        // Get settings from WordPress
        const settings = window.wpTimeProgressSettings?.options || {};
        
        let parts = [];
        let wheels = [];
        
        // Date and time
        if (settings.show_date) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const monthName = months[now.getMonth()];
            const day = now.getDate();
            const year = now.getFullYear();
            parts.push(`Today is <span class="wp-time-progress-date">${monthName} ${day}, ${year}</span>`);
        }

        if (settings.show_time) {
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = hours % 12 || 12;
            const formattedMinutes = minutes.toString().padStart(2, '0');
            const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const timeZoneCity = timeZone.split('/')[1]?.replace('_', ' ') || timeZone;
            parts.push(`Right now it's <span class="wp-time-progress-time">${formattedHours}:${formattedMinutes} ${ampm}</span> in ${timeZoneCity}`);
        }

        if (settings.show_day) {
            const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999);
            const dayProgress = ((now - startOfDay) / (endOfDay - startOfDay)) * 100;
            const dayLeft = 100 - dayProgress;
            parts.push(`You have <span class="wp-time-progress-number">${dayLeft.toFixed(1)}%</span> left in the day`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Day', dayProgress));
            }
        }

        if (settings.show_month) {
            const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            const daysInMonth = endOfMonth.getDate();
            const daysLeft = daysInMonth - now.getDate();
            const monthProgress = ((now - startOfMonth) / (endOfMonth - startOfMonth)) * 100;
            parts.push(`You have <span class="wp-time-progress-number">${daysLeft}</span> days left or <span class="wp-time-progress-number">${(100 - monthProgress).toFixed(1)}%</span> of this month`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Month', monthProgress));
            }
        }

        if (settings.show_quarter) {
            const currentQuarter = Math.floor(now.getMonth() / 3);
            const startOfQuarter = new Date(now.getFullYear(), currentQuarter * 3, 1);
            const endOfQuarter = new Date(now.getFullYear(), (currentQuarter + 1) * 3, 0);
            const quarterProgress = ((now - startOfQuarter) / (endOfQuarter - startOfQuarter)) * 100;
            parts.push(`You have experienced <span class="wp-time-progress-number">${quarterProgress.toFixed(1)}%</span> of the quarter`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Quarter', quarterProgress));
            }
        }

        if (settings.show_year) {
            const startOfYear = new Date(now.getFullYear(), 0, 1);
            const endOfYear = new Date(now.getFullYear(), 11, 31, 23, 59, 59, 999);
            const yearProgress = ((now - startOfYear) / (endOfYear - startOfYear)) * 100;
            const yearLeft = 100 - yearProgress;
            parts.push(`You have <span class="wp-time-progress-number">${yearLeft.toFixed(1)}%</span> of the year left`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Year', yearProgress));
            }
        }

        // Apply styling
        container.style.color = settings.text_color;
        container.style.fontSize = `${settings.font_size}px`;
        container.style.fontFamily = settings.font_family;

        // Apply custom styles to elements
        const style = document.createElement('style');
        style.textContent = `
            .wp-time-progress-date {
                color: ${settings.date_color};
                font-family: ${settings.date_font_family};
            }
            .wp-time-progress-time {
                color: ${settings.time_color};
                font-family: ${settings.time_font_family};
            }
            .wp-time-progress-number {
                color: ${settings.number_color};
                font-family: ${settings.number_font_family};
            }
            .wp-time-progress-wheel-bg {
                stroke: ${settings.wheel_bg_color};
            }
            .wp-time-progress-wheel-progress {
                stroke: ${settings.wheel_progress_color};
            }
            ${settings.custom_css || ''}
        `;
        container.appendChild(style);

        // Create container structure
        let html = '';
        
        if (settings.show_wheels && wheels.length > 0) {
            html += '<div class="wp-time-progress-wheels">' + wheels.join('') + '</div>';
        }
        
        if (settings.show_text && parts.length > 0) {
            html += '<div class="wp-time-progress-text">' + parts.join('. ') + '.</div>';
        }
        
        container.innerHTML = html;
    };

    const createProgressWheel = (label, percentage) => {
        const radius = 40;
        const circumference = 2 * Math.PI * radius;
        const progress = ((100 - percentage) / 100) * circumference;
        
        return `
            <div class="wp-time-progress-wheel">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    <circle
                        class="wp-time-progress-wheel-bg"
                        cx="50"
                        cy="50"
                        r="${radius}"
                        fill="none"
                        stroke-width="10"
                    />
                    <circle
                        class="wp-time-progress-wheel-progress"
                        cx="50"
                        cy="50"
                        r="${radius}"
                        fill="none"
                        stroke-width="10"
                        stroke-dasharray="${circumference}"
                        stroke-dashoffset="${progress}"
                        transform="rotate(-90 50 50)"
                    />
                    <text x="50" y="50" text-anchor="middle" dominant-baseline="middle" class="wp-time-progress-wheel-text">
                        ${percentage.toFixed(0)}%
                    </text>
                </svg>
                <div class="wp-time-progress-wheel-label">${label}</div>
            </div>
        `;
    };

    // Calculate progress once when initialized
    calculateProgress();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof TimeProgress === 'function') {
        TimeProgress();
    }
});