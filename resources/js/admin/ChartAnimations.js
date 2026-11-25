import AdminMotionEngine from './AdminMotionEngine.js';
import ThemeManager from './ThemeManager.js';

/**
 * ALERTO EOC — Animated Chart.js Visualization Suite
 * Lazy-loads Chart.js on demand, dynamically adapts to Dark/Light theme tokens,
 * and supports smooth dataset transitions without page reload.
 */
class ChartAnimationsManager {
    constructor() {
        this.instances = new Map();
        this.ChartLib = null;

        if (typeof window !== 'undefined') {
            window.addEventListener('eoc:theme-changed', () => {
                this.applyThemeToAll();
            });
        }
    }

    async ensureChartLib() {
        if (!this.ChartLib) {
            const mod = await import('chart.js/auto');
            this.ChartLib = mod.default || mod;
        }
        return this.ChartLib;
    }

    async renderDashboardCharts(datasets = {}) {
        await this.ensureChartLib();

        this.renderMonthlyTrends('chart-monthly-trends', datasets.monthly_trends);
        this.renderCategoryDoughnut('chart-by-category', datasets.by_category);
        this.renderHorizontalBar('chart-by-barangay', datasets.by_barangay, '#E87524', 'Incidents');
        this.renderBarChart('chart-by-status', datasets.by_status, ['#D99024', '#397BA8', '#E87524', '#27845B', '#77736E', '#C83F43'], 'Status');
        this.renderBarChart('chart-office-activity', datasets.office_activity, '#E87524', 'Assigned Incidents');
        this.renderBarChart('chart-personnel-activity', datasets.personnel_activity, '#27845B', 'Dispatched Tasks');
    }

    updateAllCharts(datasets = {}) {
        if (!this.ChartLib) {
            this.renderDashboardCharts(datasets);
            return;
        }

        const duration = AdminMotionEngine.isReducedMotion() ? 0 : 550;

        this.updateChartInstance('chart-monthly-trends', (chart) => {
            if (!datasets.monthly_trends) return;
            chart.data.labels = datasets.monthly_trends.labels || [];
            chart.data.datasets[0].data = datasets.monthly_trends.submitted || [];
            chart.data.datasets[1].data = datasets.monthly_trends.resolved || [];
            chart.update({ duration });
        });

        this.updateChartInstance('chart-by-category', (chart) => {
            if (!datasets.by_category) return;
            chart.data.labels = datasets.by_category.labels || [];
            chart.data.datasets[0].data = datasets.by_category.data || [];
            chart.data.datasets[0].backgroundColor = datasets.by_category.colors || ['#E87524'];
            chart.update({ duration });
        });

        this.updateChartInstance('chart-by-barangay', (chart) => {
            if (!datasets.by_barangay) return;
            chart.data.labels = datasets.by_barangay.labels || [];
            chart.data.datasets[0].data = datasets.by_barangay.data || [];
            chart.update({ duration });
        });

        this.updateChartInstance('chart-by-status', (chart) => {
            if (!datasets.by_status) return;
            chart.data.labels = datasets.by_status.labels || [];
            chart.data.datasets[0].data = datasets.by_status.data || [];
            chart.update({ duration });
        });

        this.updateChartInstance('chart-office-activity', (chart) => {
            if (!datasets.office_activity) return;
            chart.data.labels = datasets.office_activity.labels || [];
            chart.data.datasets[0].data = datasets.office_activity.data || [];
            chart.update({ duration });
        });

        this.updateChartInstance('chart-personnel-activity', (chart) => {
            if (!datasets.personnel_activity) return;
            chart.data.labels = datasets.personnel_activity.labels || [];
            chart.data.datasets[0].data = datasets.personnel_activity.data || [];
            chart.update({ duration });
        });
    }

    updateChartInstance(canvasId, fn) {
        const instance = this.instances.get(canvasId);
        if (instance) {
            fn(instance);
        }
    }

    destroyChart(canvasId) {
        const existing = this.instances.get(canvasId);
        if (existing) {
            existing.destroy();
            this.instances.delete(canvasId);
        }
    }

    destroyAll() {
        this.instances.forEach((chart) => {
            try { chart.destroy(); } catch { /* noop */ }
        });
        this.instances.clear();
    }

    baseOptions(tokens, extra = {}) {
        const animDuration = AdminMotionEngine.isReducedMotion() ? 0 : 750;
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: animDuration,
                easing: 'easeOutQuart',
            },
            plugins: {
                legend: {
                    labels: {
                        color: tokens.textColor,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                        boxWidth: 10,
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    backgroundColor: tokens.tooltipBg,
                    titleColor: '#EDF4FC',
                    bodyColor: '#CBD5E1',
                    borderColor: tokens.tooltipBorder,
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 10,
                },
            },
            ...extra,
        };
    }

    renderMonthlyTrends(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !data) return;
        this.destroyChart(canvasId);

        const tokens = ThemeManager.getChartTokens();
        const chart = new this.ChartLib(canvas, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Submitted',
                        data: data.submitted || [],
                        borderColor: '#E87524',
                        backgroundColor: 'rgba(232, 117, 36, 0.16)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#F29A56',
                    },
                    {
                        label: 'Resolved',
                        data: data.resolved || [],
                        borderColor: '#27845B',
                        backgroundColor: 'rgba(39, 132, 91, 0.14)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#27845B',
                    },
                ],
            },
            options: this.baseOptions(tokens, {
                scales: {
                    x: { ticks: { color: tokens.textColor, font: { size: 10 } }, grid: { color: tokens.gridColor } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: tokens.textColor, font: { size: 10 } }, grid: { color: tokens.gridColor } },
                },
            }),
        });

        this.instances.set(canvasId, chart);
    }

    renderCategoryDoughnut(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !data) return;
        this.destroyChart(canvasId);

        const tokens = ThemeManager.getChartTokens();
        const palette = (data.colors && data.colors.length)
            ? data.colors
            : ['#E87524', '#C83F43', '#D99024', '#27845B', '#397BA8', '#454A50'];

        const chart = new this.ChartLib(canvas, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.data || [],
                        backgroundColor: palette,
                        borderWidth: 2,
                        borderColor: ThemeManager.isDark() ? '#17191C' : '#FFFFFF',
                        hoverOffset: 6,
                    },
                ],
            },
            options: this.baseOptions(tokens, {
                cutout: '64%',
                plugins: {
                    ...this.baseOptions(tokens).plugins,
                    legend: {
                        position: 'bottom',
                        labels: { color: tokens.textColor, font: { size: 10, weight: '600' }, boxWidth: 10, padding: 12 },
                    },
                },
            }),
        });

        this.instances.set(canvasId, chart);
    }

    renderHorizontalBar(canvasId, data, color = '#E87524', label = 'Total') {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !data) return;
        this.destroyChart(canvasId);

        const tokens = ThemeManager.getChartTokens();
        const chart = new this.ChartLib(canvas, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: label,
                        data: data.data || [],
                        backgroundColor: color,
                        borderRadius: 6,
                        barThickness: 14,
                    },
                ],
            },
            options: this.baseOptions(tokens, {
                indexAxis: 'y',
                plugins: { ...this.baseOptions(tokens).plugins, legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: tokens.textColor, font: { size: 10 } }, grid: { color: tokens.gridColor } },
                    y: { ticks: { color: tokens.textColor, font: { size: 10 } }, grid: { display: false } },
                },
            }),
        });

        this.instances.set(canvasId, chart);
    }

    renderBarChart(canvasId, data, colors = '#E87524', label = 'Count') {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !data) return;
        this.destroyChart(canvasId);

        const tokens = ThemeManager.getChartTokens();
        const chart = new this.ChartLib(canvas, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: label,
                        data: data.data || [],
                        backgroundColor: colors,
                        borderRadius: 7,
                        maxBarThickness: 32,
                    },
                ],
            },
            options: this.baseOptions(tokens, {
                plugins: { ...this.baseOptions(tokens).plugins, legend: { display: false } },
                scales: {
                    x: { ticks: { color: tokens.textColor, font: { size: 10 } }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: tokens.textColor, font: { size: 10 } }, grid: { color: tokens.gridColor } },
                },
            }),
        });

        this.instances.set(canvasId, chart);
    }

    applyThemeToAll() {
        const tokens = ThemeManager.getChartTokens();
        this.instances.forEach((chart) => {
            if (chart.options?.plugins?.legend?.labels) {
                chart.options.plugins.legend.labels.color = tokens.textColor;
            }
            if (chart.options?.plugins?.tooltip) {
                chart.options.plugins.tooltip.backgroundColor = tokens.tooltipBg;
                chart.options.plugins.tooltip.borderColor = tokens.tooltipBorder;
            }
            if (chart.options?.scales?.x) {
                if (chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = tokens.textColor;
                if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = tokens.gridColor;
            }
            if (chart.options?.scales?.y) {
                if (chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = tokens.textColor;
                if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = tokens.gridColor;
            }
            if (chart.config?.type === 'doughnut' && chart.data?.datasets?.[0]) {
                chart.data.datasets[0].borderColor = ThemeManager.isDark() ? '#101E2E' : '#FFFFFF';
            }
            chart.update('none');
        });
    }
}

export const ChartAnimations = new ChartAnimationsManager();
export default ChartAnimations;

