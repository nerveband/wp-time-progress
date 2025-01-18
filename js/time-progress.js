// time-progress/js/time-progress.js

/**
 * Time Progress Calculator
 * Author: Ashraf Ali
 * Website: https://ashrafali.net
 * License: MIT
 */

function TimeProgress() {
    const calculateProgress = () => {
        const now = new Date();
        const container = document.getElementById('time-progress-container');
        if (!container) return;

        // Get settings from WordPress
        const settings = window.timeProgressSettings?.options || {};
        
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
            parts.push(`Right now it's <span class="wp-time-progress-time">${formattedHours}:${formattedMinutes} ${ampm}</span> in <span class="wp-time-progress-timezone">${timeZoneCity}</span>`);
        }

        if (settings.show_day) {
            const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999);
            const dayProgress = ((now - startOfDay) / (endOfDay - startOfDay)) * 100;
            const dayLeft = 100 - dayProgress;
            parts.push(`You have <span class="wp-time-progress-percent">${dayLeft.toFixed(1)}%</span> left in the day`);
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
            parts.push(`You have <span class="wp-time-progress-days">${daysLeft}</span> days left or <span class="wp-time-progress-percent">${(100 - monthProgress).toFixed(1)}%</span> of this month`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Month', monthProgress));
            }
        }

        if (settings.show_quarter) {
            const currentQuarter = Math.floor(now.getMonth() / 3);
            const startOfQuarter = new Date(now.getFullYear(), currentQuarter * 3, 1);
            const endOfQuarter = new Date(now.getFullYear(), (currentQuarter + 1) * 3, 0);
            const quarterProgress = ((now - startOfQuarter) / (endOfQuarter - startOfQuarter)) * 100;
            parts.push(`You have experienced <span class="wp-time-progress-percent">${quarterProgress.toFixed(1)}%</span> of the quarter`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Quarter', quarterProgress));
            }
        }

        if (settings.show_year) {
            const startOfYear = new Date(now.getFullYear(), 0, 1);
            const endOfYear = new Date(now.getFullYear(), 11, 31, 23, 59, 59, 999);
            const yearProgress = ((now - startOfYear) / (endOfYear - startOfYear)) * 100;
            const yearLeft = 100 - yearProgress;
            parts.push(`You have <span class="wp-time-progress-percent">${yearLeft.toFixed(1)}%</span> of the year left`);
            if (settings.show_wheels) {
                wheels.push(createProgressWheel('Year', yearProgress));
            }
        }

        // Apply styling
        container.style.color = settings.text_color;
        container.style.fontSize = `${settings.font_size}px`;
        container.style.fontFamily = settings.font_family;
        
        // Apply CSS variables for colors
        container.style.setProperty('--date-color', settings.date_color);
        container.style.setProperty('--time-color', settings.time_color);
        container.style.setProperty('--timezone-color', settings.timezone_color);
        container.style.setProperty('--days-color', settings.days_color);
        container.style.setProperty('--percent-color', settings.percent_color);
        
        // Create container structure
        let html = '';
        
        if (settings.show_wheels && wheels.length > 0) {
            html += '<div class="time-progress-wheels">' + wheels.join('') + '</div>';
        }
        
        if (settings.show_text && parts.length > 0) {
            html += '<div class="time-progress-text">' + parts.join('. ') + '.</div>';
        }
        
        container.innerHTML = html;
    };

    const createProgressWheel = (label, percentage) => {
        const radius = 40;
        const circumference = 2 * Math.PI * radius;
        const progress = ((100 - percentage) / 100) * circumference;
        const settings = window.timeProgressSettings?.options || {};
        
        return `
            <div class="progress-wheel">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    <circle
                        class="progress-wheel-bg"
                        cx="50"
                        cy="50"
                        r="${radius}"
                        fill="none"
                        stroke="${settings.wheel_bg_color || '#eee'}"
                        stroke-width="10"
                    />
                    <circle
                        class="progress-wheel-progress"
                        cx="50"
                        cy="50"
                        r="${radius}"
                        fill="none"
                        stroke="${settings.wheel_progress_color || '#4a90e2'}"
                        stroke-width="10"
                        stroke-dasharray="${circumference}"
                        stroke-dashoffset="${progress}"
                        transform="rotate(-90 50 50)"
                    />
                    <text x="50" y="50" text-anchor="middle" dominant-baseline="middle" class="progress-wheel-text" fill="${settings.wheel_text_color || '#333'}">
                        ${percentage.toFixed(0)}%
                    </text>
                </svg>
                <div class="progress-wheel-label" style="color: ${settings.wheel_text_color || '#333'}">${label}</div>
            </div>
        `;
    };

    // Initial update only - no interval
    calculateProgress();
}

// Initialize for both frontend and admin
document.addEventListener('DOMContentLoaded', () => {
    if (typeof TimeProgress === 'function') {
        TimeProgress();
    }
});

// Export for WordPress
if (typeof window !== 'undefined') {
    window.TimeProgress = TimeProgress;
}