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
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Statistics', href: '/statistics' },
    { title: 'Top Figures', href: '/statistics/topFigures' },
];

// UI State
const statType = ref<'searches' | 'borrowed' | 'visits' | 'programs'>('visits');
const quarter = ref<'Q1' | 'Q2' | 'Q3' | 'Q4'>('Q1');
const entries = ref<5 | 10 | 15>(5);
const isGenerated = ref(false);

// Loading state
const isLoading = ref(false);
const error = ref<string | null>(null);

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

async function renderChart() {
    const el = document.getElementById(canvasId) as HTMLCanvasElement | null;
    if (!el) return;
    destroyChart();

    let dataset: Item[] = [];

    // For visits, use real data from API
    if (statType.value === 'visits') {
        try {
            dataset = await fetchLibraryVisitsData();
            // If no data returned or an error occurred, show a message
            if (dataset.length === 0) {
                if (error.value) {
                    console.error(error.value);
                } else {
                    error.value = 'No data available for the selected filters';
                }
                // Use some placeholder data to avoid empty chart
                dataset = [{ label: 'No data available', value: 0 }];
            }
        } catch (err) {
            console.error('Error fetching data:', err);
            dataset = [{ label: 'Error loading data', value: 0 }];
        }
    } else {
        // For other statistics, continue using mock data
        dataset = makeData(statType.value, quarter.value, entries.value, year.value);
    }

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

// Function to fetch library visits data from API
async function fetchLibraryVisitsData(): Promise<Item[]> {
  isLoading.value = true;
  error.value = null;

  try {
    const params = {
      entries: entries.value,
      quarter: quarter.value,
      year: year.value,
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined
    };

    const response = await axios.get('/statistics/api/top-library-visits', { params });

    // Log debug information to console
    if (response.data.debug) {
      console.log('API Debug Info:', response.data.debug);
    }

    if (response.data.success && Array.isArray(response.data.data)) {
      if (response.data.data.length === 0) {
        // Show a user-friendly message when no data is available
        if (dateFrom.value && dateTo.value) {
          error.value = `No library visits data found for the date range: ${dateFrom.value} to ${dateTo.value}`;
        } else {
          error.value = `No library visits data found for ${quarter.value} ${year.value}`;
        }
        return [];
      }
      return response.data.data;
    } else {
      error.value = 'Invalid data received from server';
      return [];
    }
  } catch (err: any) {
    console.error('API Error:', err);
    error.value = err.response?.data?.message || err.message || 'Failed to fetch library visits data';
    return [];
  } finally {
    isLoading.value = false;
  }
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
                <h1 class="text-xl font-semibold text-gray-900 md:text-2xl">Top Figures</h1>
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

                <div class="h-[360px] md:h-[440px] relative">
                    <!-- Loading indicator -->
                    <div v-if="isLoading && statType === 'visits'" class="absolute inset-0 flex items-center justify-center bg-gray-50 bg-opacity-75 z-10">
                        <div class="text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
                            <p class="mt-2 text-sm text-gray-600">Loading library visit data...</p>
                        </div>
                    </div>

                    <!-- Error display -->
                    <div v-if="error && statType === 'visits'" class="absolute top-2 left-2 right-2 bg-red-50 border border-red-200 rounded-md p-3 z-20">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-red-700">{{ error }}</p>
                                <p class="mt-1 text-xs text-red-600">Try selecting a different time period or check your filter settings.</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <Button
                                  variant="ghost"
                                  class="h-7 w-7 p-0 rounded-full"
                                  @click="error = null"
                                >
                                  <span class="sr-only">Dismiss</span>
                                  <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                  </svg>
                                </Button>
                            </div>
                        </div>
                    </div>

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
