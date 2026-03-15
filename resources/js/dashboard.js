const getDashboardData = () => {
    const payloadNode = document.getElementById('dashboard-data');
    if (!payloadNode) {
        return null;
    }

    try {
        const payload = JSON.parse(payloadNode.dataset.dashboard || '{}');
        window.dashboardData = payload;
        return payload;
    } catch (_error) {
        console.error(_error);
        window.dashboardData = {};
        return null;
    }
};

const escapeHtml = (value) =>
    String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

const fetchChartData = async (endpoint) => {
    if (!endpoint) {
        return null;
    }

    const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to load chart data (${response.status})`);
    }

    const payload = await response.json();
    return payload?.data ?? null;
};

const renderLeadsBySource = (payload) => {
    const container = document.getElementById('dashboard-leads-source');
    if (!container) {
        return;
    }

    const leadsBySource = payload?.leadsBySource ?? [];
    const totalLeadsMonth = Number(payload?.totalLeadsMonth ?? 0);
    const monthLabel = String(payload?.monthLabel ?? '');
    let offset = 0;

    const circles = leadsBySource
        .map((row) => {
            const segment = Math.max(Number(row?.percent ?? 0), 0);
            const circle = `<circle cx="18" cy="18" r="14" fill="none" class="crm-pie-segment" stroke="${escapeHtml(row?.color)}" stroke-width="8" data-target-segment="${segment}" data-target-offset="-${offset}" pathLength="100" stroke-dasharray="0 100" stroke-dashoffset="-${offset}" stroke-linecap="butt"></circle>`;
            offset += segment;
            return circle;
        })
        .join('');

    const list = leadsBySource.length
        ? leadsBySource
              .map(
                  (row) => `
            <div class="flex items-center justify-between px-3 py-1 text-sm">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: ${escapeHtml(row?.color)};"></span>
                    <span class="font-medium text-slate-700">${escapeHtml(row?.source)}</span>
                </div>
                <div class="flex items-center justify-end gap-2 text-right">
                    <span class="font-semibold text-slate-900">${Number(row?.count ?? 0).toLocaleString('en-US')}</span>
                    <span class="min-w-[50px] text-right text-xs text-slate-500">(${Number(
                        row?.percent ?? 0,
                    ).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    })}%)</span>
                </div>
            </div>`,
              )
              .join('')
        : `<p class="text-center text-sm text-slate-500"><em>No leads in ${escapeHtml(monthLabel)}</em></p>`;

    container.innerHTML = `
        <div class="relative mx-auto my-3 h-48 w-48">
            <svg viewBox="0 0 36 36" class="h-48 w-48 -rotate-90">
                <circle cx="18" cy="18" r="14" fill="none" stroke="gray-200" pathLength="100" stroke-width="6"></circle>
                ${circles}
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">Total</span>
                <span class="text-2xl font-semibold text-slate-900 crm-countup">${totalLeadsMonth.toLocaleString('en-US')}</span>
            </div>
        </div>
        <div class="space-y-2">${list}</div>
    `;
};

const renderPipelineOverview = (payload) => {
    const container = document.getElementById('dashboard-pipeline-overview');
    if (!container) {
        return;
    }

    const pipelineStages = payload?.pipelineStages ?? {};
    const entries = Object.entries(pipelineStages);
    if (!entries.length) {
        container.innerHTML =
            '<p class="text-sm text-slate-500">No pipeline data.</p>';
        return;
    }

    const maxStage = Math.max(
        ...entries.map(([, count]) => Number(count ?? 0)),
        1,
    );
    container.innerHTML = entries
        .map(([stage, count]) => {
            const normalizedCount = Number(count ?? 0);
            const percent =
                maxStage > 0 ? (normalizedCount / maxStage) * 100 : 0;
            return `
                <button
                    type="button"
                    class="group w-full cursor-pointer rounded-md px-1 py-1"
                    data-pipeline-stage-trigger="${escapeHtml(stage)}"
                >
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-semibold text-slate-800 transition-all duration-150 group-hover:pl-2 group-hover:text-blue-500">${escapeHtml(stage)}</span>
                        <span class="crm-countup font-semibold text-slate-900 transition-colors duration-150">${normalizedCount.toLocaleString('en-US')}</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200 transition-colors duration-150">
                        <div class="crm-pipeline-bar h-2 w-full rounded-full filter transition-filter duration-250 group-hover:bg-yellow-400" data-target-scale="${percent / 100}" style="background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 35%, #77b4ff 100%); transform-origin: left center; transform: scaleX(0); transition: transform 900ms cubic-bezier(0.22, 1, 0.36, 1); will-change: transform;"></div>
                    </div>
                </button>
            `;
        })
        .join('');
};

const initPipelineStageModal = (payload) => {
    const endpoint = payload?.pipelineDetailsEndpoint ?? null;
    if (!endpoint) {
        return;
    }

    const titleNode = document.getElementById('pipeline-stage-modal-title');
    const subtitleNode = document.getElementById(
        'pipeline-stage-modal-subtitle',
    );
    const loadingNode = document.getElementById('pipeline-stage-modal-loading');
    const emptyNode = document.getElementById('pipeline-stage-modal-empty');
    const errorNode = document.getElementById('pipeline-stage-modal-error');
    const tableWrapNode = document.getElementById(
        'pipeline-stage-modal-table-wrap',
    );
    const tableBodyNode = document.getElementById('pipeline-stage-modal-tbody');
    const stageDateHeaderNode = document.getElementById(
        'pipeline-stage-modal-stage-date-header',
    );

    if (
        !titleNode ||
        !subtitleNode ||
        !loadingNode ||
        !emptyNode ||
        !errorNode ||
        !tableWrapNode ||
        !tableBodyNode ||
        !stageDateHeaderNode
    ) {
        return;
    }

    let latestRequestToken = 0;

    const setModalState = (state) => {
        loadingNode.classList.toggle('hidden', state !== 'loading');
        emptyNode.classList.toggle('hidden', state !== 'empty');
        errorNode.classList.toggle('hidden', state !== 'error');
        tableWrapNode.classList.toggle('hidden', state !== 'table');
    };

    const openModal = () => {
        window.dispatchEvent(
            new CustomEvent('open-modal', {
                detail: 'dashboard-pipeline-stage-modal',
            }),
        );
    };

    const buildRowsHtml = (rows) =>
        rows
            .map(
                (row, index) => `
                <tr>
                    <td class="whitespace-nowrap px-3 py-2 text-center">${index + 1}</td>
                    <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-800">${escapeHtml(row?.deal_id ?? '-')}</td>
                    <td class="px-3 py-2">${escapeHtml(row?.project_name ?? '-')}</td>
                    <td class="px-3 py-2">${escapeHtml(row?.salesperson ?? '-')}</td>
                    <td class="px-3 py-2">${escapeHtml(row?.leader ?? '-')}</td>
                    <td class="whitespace-nowrap px-3 py-2">${escapeHtml(row?.created_date ?? '-')}</td>
                    <td class="whitespace-nowrap px-3 py-2">${escapeHtml(row?.stage_date ?? '-')}</td>
                </tr>`,
            )
            .join('');

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-pipeline-stage-trigger]');
        if (!trigger) {
            return;
        }

        const stage = String(
            trigger.getAttribute('data-pipeline-stage-trigger') || '',
        ).trim();
        if (!stage) {
            return;
        }

        latestRequestToken += 1;
        const currentToken = latestRequestToken;

        titleNode.textContent = `${stage} Deals`;
        subtitleNode.textContent = `Loading records for ${stage} (${payload?.monthLabel ?? ''})`;
        stageDateHeaderNode.textContent = `${stage} Date`;
        tableBodyNode.innerHTML = '';
        setModalState('loading');
        openModal();

        try {
            const detailUrl = new URL(endpoint, window.location.origin);
            detailUrl.searchParams.set('stage', stage);

            const detailPayload = await fetchChartData(detailUrl.toString());
            if (currentToken !== latestRequestToken) {
                return;
            }

            const rows = Array.isArray(detailPayload?.rows)
                ? detailPayload.rows
                : [];
            titleNode.textContent = `${String(detailPayload?.stage ?? stage)} Deals`;
            subtitleNode.textContent = `${rows.length.toLocaleString('en-US')} record(s) in ${payload?.monthLabel ?? 'selected month'}`;
            stageDateHeaderNode.textContent = String(
                detailPayload?.stageDateLabel ?? `${stage} Date`,
            );

            if (!rows.length) {
                setModalState('empty');
                return;
            }

            tableBodyNode.innerHTML = buildRowsHtml(rows);
            setModalState('table');
        } catch {
            if (currentToken !== latestRequestToken) {
                return;
            }
            setModalState('error');
        }
    });
};

const initDashboardAnimations = (payload) => {
    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
    const shouldAnimate = !prefersReducedMotion;
    const performanceCharts = payload?.salesPerformance ?? null;
    const performanceChartInstances = {};
    const performanceChartPayloads = {};

    const createGroupedBarChart = (canvasId, chartPayload) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !chartPayload) {
            return;
        }

        const labels = chartPayload.labels ?? [];
        const normalizedLabels = labels.map((label) => {
            const text = String(label ?? '');
            return text.length > 14 ? `${text.slice(0, 14)}...` : text;
        });
        const dealsData = chartPayload.deals ?? [];
        const commissionData = chartPayload.commission ?? [];

        if (!labels.length) {
            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.save();
                ctx.fillStyle = '#64748b';
                ctx.font = '13px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(
                    'No performance data for this month.',
                    canvas.width / 2,
                    canvas.height / 2,
                );
                ctx.restore();
            }
            return;
        }

        return new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: normalizedLabels,
                datasets: [
                    {
                        label: 'Completed Deals',
                        data: dealsData,
                        backgroundColor: '#2563eb',
                        borderRadius: 3,
                        maxBarThickness: 20,
                        barPercentage: 0.7,
                        categoryPercentage: 0.7,
                        yAxisID: 'yDeals',
                    },
                    {
                        label: 'Total Commission',
                        data: commissionData,
                        backgroundColor: '#32cd32',
                        borderRadius: 3,
                        maxBarThickness: 20,
                        barPercentage: 0.7,
                        categoryPercentage: 0.7,
                        yAxisID: 'yCommission',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1100,
                    easing: 'easeOutCubic',
                },
                animations: {
                    y: {
                        from: (ctx) => {
                            const axisId = ctx?.dataset?.yAxisID ?? 'y';
                            const scale = ctx?.chart?.scales?.[axisId];
                            if (
                                !scale ||
                                typeof scale.getPixelForValue !== 'function'
                            ) {
                                return 0;
                            }
                            return scale.getPixelForValue(0);
                        },
                    },
                    base: {
                        from: (ctx) => {
                            const axisId = ctx?.dataset?.yAxisID ?? 'y';
                            const scale = ctx?.chart?.scales?.[axisId];
                            if (
                                !scale ||
                                typeof scale.getPixelForValue !== 'function'
                            ) {
                                return 0;
                            }
                            return scale.getPixelForValue(0);
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 10,
                            font: {
                                size: 11,
                                weight: '500',
                            },
                        },
                    },
                    tooltip: {
                        titleFont: {
                            size: 11,
                        },
                        bodyFont: {
                            size: 11,
                        },
                        callbacks: {
                            title: (items) => {
                                if (!items.length) {
                                    return '';
                                }
                                return String(labels[items[0].dataIndex] ?? '');
                            },
                            label: (context) => {
                                const value = Number(context.parsed.y || 0);
                                if (context.dataset.yAxisID === 'yCommission') {
                                    return `${context.dataset.label}: RM ${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                }
                                return `${context.dataset.label}: ${value.toLocaleString('en-US')}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 30,
                            minRotation: 30,
                            padding: 8,
                            font: {
                                size: 10,
                            },
                            color: '#64748b',
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                            lineWidth: 1,
                            drawBorder: false,
                        },
                    },
                    yDeals: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Completed Deals',
                            font: {
                                size: 11,
                                weight: '600',
                            },
                            color: '#334155',
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 10,
                            },
                            color: '#64748b',
                            callback: (value) =>
                                Number(value || 0).toLocaleString('en-US'),
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                            lineWidth: 1,
                            drawBorder: false,
                        },
                    },
                    yCommission: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false,
                        },
                        title: {
                            display: true,
                            text: 'Total Commission (RM)',
                            font: {
                                size: 11,
                                weight: '600',
                            },
                            color: '#334155',
                        },
                        ticks: {
                            font: {
                                size: 10,
                            },
                            color: '#64748b',
                            callback: (value) =>
                                `RM ${Number(value || 0).toLocaleString('en-US')}`,
                        },
                    },
                },
                layout: {
                    padding: {
                        top: 6,
                        bottom: 6,
                    },
                },
            },
        });
    };

    const initPerformanceCharts = () => {
        if (!window.Chart || !performanceCharts) {
            return;
        }

        performanceChartPayloads.salespersonPerformanceChart =
            performanceCharts.salesperson_performance;
        performanceChartPayloads.leaderPerformanceChart =
            performanceCharts.leader_performance;

        const renderPerformanceChart = (canvasId) => {
            if (performanceChartInstances[canvasId]) {
                return;
            }
            const chartPayload = performanceChartPayloads[canvasId];
            if (!chartPayload) {
                return;
            }
            const chart = createGroupedBarChart(canvasId, chartPayload);
            if (chart) {
                performanceChartInstances[canvasId] = chart;
            }
        };

        window.__crmRenderPerformanceChart = renderPerformanceChart;

        if (!shouldAnimate) {
            renderPerformanceChart('salespersonPerformanceChart');
            renderPerformanceChart('leaderPerformanceChart');
        }
    };

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const animatePieSegment = (circle, duration = 900) => {
        const targetSegment = clamp(
            Number(circle.dataset.targetSegment || 0),
            0,
            100,
        );
        const targetOffset = Number(circle.dataset.targetOffset || 0);

        if (duration <= 0) {
            circle.setAttribute(
                'stroke-dasharray',
                `${targetSegment} ${Math.max(100 - targetSegment, 0)}`,
            );
            circle.setAttribute('stroke-dashoffset', `${targetOffset}`);
            return;
        }

        const startTime = performance.now();

        const step = (now) => {
            const progress = clamp((now - startTime) / duration, 0, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const currentSegment = targetSegment * eased;

            circle.setAttribute(
                'stroke-dasharray',
                `${currentSegment} ${Math.max(100 - currentSegment, 0)}`,
            );
            circle.setAttribute('stroke-dashoffset', `${targetOffset}`);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    const initDashboardPieSegments = (container = document, duration = 900) => {
        container.querySelectorAll('.crm-pie-segment').forEach((el) => {
            if (el.dataset.crmAnimated === '1' && duration > 0) {
                return;
            }
            el.dataset.crmAnimated = '1';
            animatePieSegment(el, duration);
        });
    };

    const parseNumberDisplay = (text) => {
        const raw = (text || '').trim();
        if (!raw || raw === '-') {
            return null;
        }

        const hasPercent = raw.includes('%');
        const startsWithRm = raw.toUpperCase().includes('RM');
        const numeric = Number(raw.replace(/[^0-9.-]/g, ''));

        if (!Number.isFinite(numeric)) {
            return null;
        }

        const decimalMatch = raw.match(/\.(\d+)/);
        const decimals = decimalMatch ? decimalMatch[1].length : 0;

        return {
            value: numeric,
            decimals,
            prefix: startsWithRm ? 'RM ' : '',
            suffix: hasPercent ? '%' : '',
        };
    };

    const formatNumber = (value, decimals) =>
        Number(value).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });

    const animateValue = (element, target, duration = 1000) => {
        const parsed = parseNumberDisplay(element.textContent);
        if (!parsed) {
            return;
        }

        const startTime = performance.now();
        const endValue = target ?? parsed.value;

        const step = (now) => {
            const progress = clamp((now - startTime) / duration, 0, 1);
            const eased = 1 - Math.pow(1 - progress, 2.2);
            const current = endValue * eased;
            element.textContent = `${parsed.prefix}${formatNumber(current, parsed.decimals)}${parsed.suffix}`;

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    const animateWidth = (bar) => {
        const target = clamp(Number(bar.dataset.targetScale || 0), 0, 1);
        window.requestAnimationFrame(() => {
            bar.style.transform = `scaleX(${target})`;
        });
    };

    const animateWithin = (container) => {
        container.querySelectorAll('canvas').forEach((canvas) => {
            if (canvas.dataset.crmAnimatedChart === '1') {
                return;
            }
            canvas.dataset.crmAnimatedChart = '1';
            if (typeof window.__crmRenderPerformanceChart === 'function') {
                window.__crmRenderPerformanceChart(canvas.id);
            }
        });

        container.querySelectorAll('.crm-countup').forEach((el) => {
            if (el.dataset.crmAnimated === '1') {
                return;
            }
            el.dataset.crmAnimated = '1';
            animateValue(el);
        });
        initDashboardPieSegments(container);
        container.querySelectorAll('.crm-pipeline-bar').forEach((el) => {
            if (el.dataset.crmAnimated === '1') {
                return;
            }
            el.dataset.crmAnimated = '1';
            animateWidth(el);
        });
    };

    const initScrollAnimations = () => {
        const root = document.querySelector('.crm-dashboard-scroll');
        if (!root) {
            return;
        }

        const animatedBlocks = root.querySelectorAll(
            '.crm-anim-fade-up, .crm-anim-pop, .crm-anim-stagger',
        );
        if (!animatedBlocks.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            animatedBlocks.forEach((el) => {
                el.classList.add('crm-inview');
                animateWithin(el);
            });
            return;
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    const target = entry.target;
                    target.classList.add('crm-inview');
                    animateWithin(target);
                    obs.unobserve(target);
                });
            },
            {
                root: null,
                threshold: 0.15,
                rootMargin: '0px 0px -8% 0px',
            },
        );

        animatedBlocks.forEach((el) => observer.observe(el));
    };

    initPerformanceCharts();

    if (shouldAnimate) {
        initScrollAnimations();
    } else {
        document
            .querySelectorAll(
                '.crm-anim-fade-up, .crm-anim-pop, .crm-anim-stagger',
            )
            .forEach((el) => el.classList.add('crm-inview'));
        document
            .querySelectorAll('.crm-countup')
            .forEach((el) => animateValue(el));
        initDashboardPieSegments(document, 0);
        document
            .querySelectorAll('.crm-pipeline-bar')
            .forEach((el) => animateWidth(el));
    }
};

const renderCommissionTrend = (payload) => {
    const canvas = document.getElementById('dashboard-total-commission-line');
    if (!canvas || !window.Chart) {
        return;
    }

    const trendPayload = payload?.commissionTrend ?? {};
    const labels = Array.isArray(trendPayload?.labels)
        ? trendPayload.labels
        : [];
    const values = Array.isArray(trendPayload?.values)
        ? trendPayload.values
        : [];

    if (window.__crmCommissionTrendChart) {
        window.__crmCommissionTrendChart.destroy();
        window.__crmCommissionTrendChart = null;
    }

    if (!labels.length) {
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.save();
            ctx.fillStyle = '#64748b';
            ctx.font = '13px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(
                'No commission data for the selected period.',
                canvas.width / 2,
                canvas.height / 2,
            );
            ctx.restore();
        }

        return;
    }

    window.__crmCommissionTrendChart = new window.Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Total Commission (RM)',
                    data: values,
                    borderColor: 'sandybrown',
                    backgroundColor: 'sandybrown',
                    tension: 0.15,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBorderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutCubic',
            },
            animations: {
                y: {
                    type: 'number',
                    easing: 'easeOutQuad',
                    from: (ctx) => {
                        const scale = ctx?.chart?.scales?.y;
                        return scale ? scale.getPixelForValue(scale.min) : 0;
                    },
                    duration: 1000,
                },
                x: {
                    type: 'number',
                    easing: 'easeOutQuad',
                    from: (ctx) => {
                        const scale = ctx?.chart?.scales?.x;
                        return scale ? scale.getPixelForValue(scale.min) : 0;
                    },
                    duration: 1000,
                },
            },

            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = Number(context.parsed?.y ?? 0);
                            return `RM ${value.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: '#1f2937',
                        font: {
                            size: 10,
                        },
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.28)',
                        borderDash: [4, 4],
                        drawBorder: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#374151',
                        font: {
                            size: 10,
                            weight: '300',
                        },
                        callback: (value) =>
                            Number(value ?? 0).toLocaleString('en-US'),
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.28)',
                        borderDash: [4, 4],
                        drawBorder: false,
                    },
                },
            },
        },
    });
};

const bootDashboard = async () => {
    const payload = getDashboardData();
    if (!payload) {
        return;
    }

    const loadingNode = document.getElementById('dashboard-loading');
    if (loadingNode) {
        loadingNode.classList.remove('hidden');
    }

    const chartEndpoint = payload?.chartEndpoint ?? null;
    if (chartEndpoint) {
        try {
            const chartData = await fetchChartData(chartEndpoint);
            if (chartData) {
                payload.leadsBySource = chartData.leadsBySource ?? [];
                payload.pipelineStages = chartData.pipelineStages ?? {};
                payload.salesPerformance = chartData.salesPerformance ?? null;
                payload.commissionTrend = chartData.commissionTrend ?? null;
                renderLeadsBySource(payload);
                renderPipelineOverview(payload);
            }
        } catch (_error) {
            console.error(_error);
            // Keep the initial markup if chart loading fails.
        }
    }

    renderCommissionTrend(payload);
    initPipelineStageModal(payload);
    initDashboardAnimations(payload);

    if (loadingNode) {
        loadingNode.classList.add('hidden');
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootDashboard, {
        once: true,
    });
} else {
    bootDashboard();
}
