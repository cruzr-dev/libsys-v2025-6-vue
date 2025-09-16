<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Layout from '@/layouts/statistics/TopFiguresLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import { onMounted, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Statistics', href: '/statistics' },
    { title: 'Top Figures', href: '/statistics/topFigures' },
];

// UI State
const statType = ref<'searches' | 'borrowed' | 'visits' | 'programs'>('visits');
const quarter = ref<'Q1' | 'Q2' | 'Q3' | 'Q4'>('Q1');
const entries = ref<5 | 10 | 15>(5);
const isGenerated = ref(false);

// New filters: Year and Date Range
const currentYear = new Date().getFullYear();
const years = ref<number[]>([currentYear, currentYear - 1, currentYear - 2, currentYear - 3, currentYear - 4]);
const year = ref<number | null>(currentYear);
const dateFrom = ref<string>('');
const dateTo = ref<string>('');

// Chart refs
let chartInstance: Chart | null = null;
const canvasId = 'topStatsChartRoot';

// Mock data factory (front-end only)
interface Item {
    label: string;
    value: number;
}
function makeData(kind: 'searches' | 'borrowed' | 'visits' | 'programs', q: 'Q1' | 'Q2' | 'Q3' | 'Q4', count: number, y?: number | null): Item[] {
    const base = {
        searches: [
            'Intro to AI',
            'Data Structures',
            'Discrete Math',
            'Operating Systems',
            'Database Systems',
            'Networking',
            'Linear Algebra',
            'Web Dev',
            'Algorithms',
            'Statistics',
            'Compilers',
            'Software Eng',
            'ML Basics',
            'Cybersecurity',
            'Cloud Computing',
        ],
        borrowed: [
            '1984',
            'To Kill a Mockingbird',
            'The Great Gatsby',
            'Moby Dick',
            'War and Peace',
            'Pride & Prejudice',
            'Hamlet',
            'The Hobbit',
            'Dune',
            'Fahrenheit 451',
            'Brave New World',
            'The Catcher in the Rye',
            'Animal Farm',
            'The Odyssey',
            'Crime and Punishment',
        ],
        visits: [
            'Week 1',
            'Week 2',
            'Week 3',
            'Week 4',
            'Week 5',
            'Week 6',
            'Week 7',
            'Week 8',
            'Week 9',
            'Week 10',
            'Week 11',
            'Week 12',
            'Week 13',
            'Week 14',
            'Week 15',
        ],
        programs: [
            'BSCS',
            'BSIT',
            'BSEd Math',
            'BSEd English',
            'BSME',
            'BSEE',
            'BSCE',
            'BSBA',
            'BSA',
            'BSN',
            'BSPsych',
            'BSChem',
            'BSBio',
            'BSStat',
            'BAComm',
        ],
    }[kind];
    const qOffset = { Q1: 0, Q2: 3, Q3: 6, Q4: 9 }[q];
    const yearOffset = y ? y % 7 : 0;
    const multiplier = kind === 'visits' ? 7 : 4;
    const items = base.map((label, idx) => ({ label, value: Math.max(1, Math.round((((idx + 1 + qOffset + yearOffset) * multiplier) % 31) + 5)) }));
    items.sort((a, b) => b.value - a.value);
    return items.slice(0, count);
}

function destroyChart() {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
}

const valueLabelPlugin = {
    id: 'valueLabel',
    afterDatasetsDraw(chart: Chart) {
        const { ctx, data, chartArea } = chart as any;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillStyle = '#1f2937';
        ctx.font = '10px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial';
        const meta = chart.getDatasetMeta(0);
        meta.data.forEach((bar: any, i: number) => {
            const val = (data.datasets[0].data[i] as number) ?? 0;
            const { x, y } = bar.tooltipPosition();
            const topY = Math.min(y, chartArea.top) + (y < chartArea.top ? 0 : -4);
            ctx.fillText(String(val), x, topY);
        });
        ctx.restore();
    },
};

function renderChart() {
    const el = document.getElementById(canvasId) as HTMLCanvasElement | null;
    if (!el) return;
    destroyChart();

    const dataset = makeData(statType.value, quarter.value, entries.value, year.value);
    const labels = dataset.map((d) => d.label);
    const values = dataset.map((d) => d.value);

    chartInstance = new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Count',
                    data: values,
                    backgroundColor: 'rgba(153, 27, 27, 0.7)',
                    borderColor: 'rgba(153, 27, 27, 1)',
                    borderWidth: 1,
                    maxBarThickness: 38,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 18 } },
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: {
                x: { ticks: { color: '#374151', maxRotation: 45, minRotation: 0, autoSkip: false }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: '#6b7280', precision: 0 }, grid: { color: 'rgba(156, 163, 175, 0.2)' } },
            },
            animation: { duration: 300 },
        },
        plugins: [valueLabelPlugin],
    });

    isGenerated.value = true;
}

function handleGenerate() {
    renderChart();
}

function downloadPNG() {
    const el = document.getElementById(canvasId) as HTMLCanvasElement | null;
    if (!el) return;
    const link = document.createElement('a');
    link.href = el.toDataURL('image/png');
    link.download = `${statType.value}-${quarter.value}-top${entries.value}.png`;
    link.click();
}

function downloadPDF() {
    const el = document.getElementById(canvasId) as HTMLCanvasElement | null;
    if (!el) return;
    const dataUrl = el.toDataURL('image/png');
    const w = window.open('', '_blank', 'width=1000,height=800');
    if (!w) return;
    const title = `${statType.value.toUpperCase()} • ${quarter.value} • Top ${entries.value}${year.value ? ` • ${year.value}` : ''}`;
    w.document.write(
        `<!doctype html><html lang="en"><head><title>${title}</title></head><body style="margin:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;background:#fff;">`,
    );
    w.document.write(`<h3 style="font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,Arial;margin:16px;">${title}</h3>`);
    w.document.write(`<img src="${dataUrl}" alt="Chart" style="max-width:94vw;max-height:80vh;" />`);
    w.document.write('</body></html>');
    w.document.close();
    setTimeout(() => {
        try {
            w.focus();
            w.print();
        } catch (e) {}
    }, 300);
}

onMounted(() => {
    renderChart();
});
watch([statType, quarter, entries, year, dateFrom, dateTo], () => {
    renderChart();
});
</script>

<template>
    <Head title="Top Figures" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-6 md:p-8">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 md:text-2xl">Top 5 Statistics</h1>
            </div>

            <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:p-4">
                <div class="flex flex-col gap-2 md:grid md:grid-cols-12 md:items-end md:gap-2">
                    <!-- Statistic selector -->
                    <div class="md:col-span-3">
                        <label class="text-xs font-medium text-gray-700">Select statistic</label>
                        <Select v-model="statType">
                            <SelectTrigger class="mt-1 h-9 px-2">
                                <SelectValue placeholder="Choose" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="visits">Top Library Visits</SelectItem>
                                <SelectItem value="searches">Top Most Searched Books</SelectItem>
                                <SelectItem value="borrowed">Top Borrowed Books</SelectItem>
                                <SelectItem value="programs">Top Borrowers (Programs)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Quarter selector -->
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-700">Quarter</label>
                        <Select v-model="quarter">
                            <SelectTrigger class="mt-1 h-9 px-2">
                                <SelectValue placeholder="Select quarter" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Q1">Q1</SelectItem>
                                <SelectItem value="Q2">Q2</SelectItem>
                                <SelectItem value="Q3">Q3</SelectItem>
                                <SelectItem value="Q4">Q4</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Year dropdown -->
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-700">Year</label>
                        <Select v-model="year">
                            <SelectTrigger class="mt-1 h-9 px-2">
                                <SelectValue :placeholder="String(currentYear)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="y in years" :key="y" :value="y">{{ y }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date range: From / To -->
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-700">From</label>
                        <Input type="date" class="mt-1 h-9 px-2" v-model="dateFrom" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-700">To</label>
                        <Input type="date" class="mt-1 h-9 px-2" v-model="dateTo" />
                    </div>

                    <!-- Entries selector (5/10/15) -->
                    <div class="md:col-span-1">
                        <label class="text-xs font-medium text-gray-700">Entries</label>
                        <Select v-model="entries">
                            <SelectTrigger class="mt-1 h-9 px-2">
                                <SelectValue placeholder="Top N" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="5">Top 5</SelectItem>
                                <SelectItem :value="10">Top 10</SelectItem>
                                <SelectItem :value="15">Top 15</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Actions -->
                    <div class="mt-0.5 flex justify-end gap-2 md:col-span-12">
                        <Button class="h-9 px-3" @click="handleGenerate">Generate</Button>
                        <Button variant="secondary" class="h-9 px-3" @click="downloadPNG">Download PNG</Button>
                        <Button variant="outline" class="h-9 px-3" @click="downloadPDF">PDF</Button>
                    </div>
                </div>

                <div class="h-[360px] md:h-[440px]">
                    <canvas :id="canvasId" class="h-full w-full"></canvas>
                </div>

                <p v-if="isGenerated" class="text-xs text-gray-500">
                    Tip: Change Entries to 15 to display 15 bars and see each value on top of its bar.
                </p>
            </div>
        </div>

        <!--        <Layout>-->
        <!--            This is the Top 5 Figures-->
        <!--        </Layout>-->
    </AppLayout>
</template>
