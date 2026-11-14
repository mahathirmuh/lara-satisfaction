{{-- ApexCharts helpers for survey analytics (dashboard and survey report). Include once via @pushOnce. --}}
<script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}?v=3.40.0"></script>
<script>
    const mdkaPalette = {
        blue: '#3b82f6',
        green: '#22c55e',
        purple: '#a855f7',
        orange: '#f97316',
        red: '#ef4444',
        yellow: '#eab308',
        pink: '#ec4899',
        lime: '#84cc16',
        brand: '#3b82f6',
        gray: '#6b7280',
    };

    const ratingColors = ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e'];

    const chartBase = {
        fontFamily: 'inherit',
        toolbar: {
            show: false
        },
        animations: {
            enabled: true
        },
    };

    /**
     * Horizontal bar chart of the average score (1-5) per department.
     */
    function renderDepartmentScoreChart(selector, rows) {
        const element = document.querySelector(selector);

        if (!element || !rows.length) {
            return;
        }

        new ApexCharts(element, {
            chart: {
                ...chartBase,
                type: 'bar',
                height: Math.max(220, rows.length * 56),
            },
            series: [{
                name: 'Average score',
                data: rows.map((row) => row.average ?? 0),
            }],
            xaxis: {
                categories: rows.map((row) => row.name),
                min: 0,
                max: 5,
                tickAmount: 5,
            },
            yaxis: {
                labels: {
                    maxWidth: 220,
                    style: {
                        fontSize: '12px'
                    },
                },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true,
                    borderRadius: 4,
                    barHeight: '60%',
                },
            },
            colors: rows.map((row) => mdkaPalette[row.color] ?? mdkaPalette.blue),
            dataLabels: {
                enabled: true,
                formatter: (value) => Number(value).toFixed(2),
            },
            legend: {
                show: false
            },
            grid: {
                strokeDashArray: 4,
                padding: {
                    left: 32
                },
            },
            tooltip: {
                y: {
                    formatter: (value, {
                        dataPointIndex
                    }) => `${Number(value).toFixed(2)} / 5 (CSAT ${rows[dataPointIndex].csat ?? 0}%, ${rows[dataPointIndex].ratings} ratings)`,
                },
            },
        }).render();
    }

    /**
     * Donut chart of all ratings by value (1-5).
     */
    function renderRatingDistributionChart(selector, rows, scale) {
        const element = document.querySelector(selector);

        if (!element || !rows.length) {
            return;
        }

        const totals = [1, 2, 3, 4, 5].map((rating) => rows.reduce((sum, row) => sum + (row.distribution[rating] ?? 0), 0));

        new ApexCharts(element, {
            chart: {
                ...chartBase,
                type: 'donut',
                height: 300,
            },
            series: totals,
            labels: [1, 2, 3, 4, 5].map((rating) => `${rating} - ${scale[rating]}`),
            colors: ratingColors,
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true,
                formatter: (value) => `${Math.round(value)}%`,
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Ratings',
                            },
                        },
                    },
                },
            },
        }).render();
    }

    /**
     * Daily completions (columns) and cumulative completions (line).
     */
    function renderParticipationTrendChart(selector, trend) {
        const element = document.querySelector(selector);

        if (!element) {
            return;
        }

        if (!trend.labels.length) {
            element.innerHTML = '<div class="text-center text-muted py-5">The survey period has not started yet.</div>';

            return;
        }

        new ApexCharts(element, {
            chart: {
                ...chartBase,
                type: 'line',
                height: 280,
            },
            series: [{
                    name: 'Completed per day',
                    type: 'column',
                    data: trend.daily
                },
                {
                    name: 'Cumulative',
                    type: 'area',
                    data: trend.cumulative
                },
            ],
            labels: trend.labels,
            colors: [mdkaPalette.blue, mdkaPalette.green],
            stroke: {
                width: [0, 2],
                curve: 'smooth'
            },
            fill: {
                opacity: [0.85, 0.15]
            },
            dataLabels: {
                enabled: false
            },
            yaxis: [{
                    title: {
                        text: 'Per day'
                    },
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        formatter: (value) => Math.round(value)
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Cumulative'
                    },
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        formatter: (value) => Math.round(value)
                    }
                },
            ],
            xaxis: {
                tickAmount: Math.min(trend.labels.length, 12)
            },
            legend: {
                position: 'top'
            },
            grid: {
                strokeDashArray: 4
            },
        }).render();
    }
</script>
