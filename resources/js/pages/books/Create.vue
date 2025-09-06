<script setup lang="ts">
import AuthorsTagsInput from '@/components/AuthorsTagsInput.vue';
import EditorsTagsInput from '@/components/EditorsTagsInput.vue';
import InputError from '@/components/InputError.vue';
import SubjectTagsInput from '@/components/SubjectTagsInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import RecordsLayout from '@/layouts/records/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { BookOpen, LoaderCircle } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import CoverTypeComboBox from '@/components/CoverTypeComboBox.vue';
import DatePicker from 'vue-datepicker-next';
import 'vue-datepicker-next/index.css';

// Props
const props = defineProps<{
    nextAccessionNumber: number;
    ddcClassifications: { id: number; number_range: string; title: string }[];
    physicalLocations: { id: number; name: string; symbol: string }[];
    coverTypes: { id: number; name: string }[];
    sources: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Records', href: '/records' },
    { title: 'Books', href: '/records/books' },
    { title: 'Add Book', href: '/records/books/create' },
];

const form = useForm({
    accession_number: props.nextAccessionNumber.toString(),
    title: '',
    volume: '',
    primary_author: '',
    co_authors: [],
    edition: '',
    editors: [],
    publication_year: '',
    publisher: '',
    publication_place: '',
    isbn: '',
    call_number: '',
    ddc_class_id: '',
    physical_location_id: '',
    cover_image: null,
    ics_number: '',
    ics_date: '',
    pr_number: '',
    pr_date: '',
    po_number: '',
    po_date: '',
    source_id: '',
    purchase_amount: '',
    lot_cost: '',
    supplier: '',
    donated_by: '',
    replaced_by: '',
    cover_type_id: '',
    table_of_contents: '',
    subject_headings: [],
    status: 'available',
});

// Date picker setup for ics_date
const icsDate = ref<Date | null>(form.ics_date ? new Date(form.ics_date) : null);

// Date picker setup for pr_date
const prDate = ref<Date | null>(form.pr_date ? new Date(form.pr_date) : null);

// Date picker setup for po_date
const poDate = ref<Date | null>(form.po_date ? new Date(form.po_date) : null);

// Sync icsDate with form.ics_date
watch(icsDate, (newValue) => {
    if (newValue) {
        const year = newValue.getFullYear();
        const month = String(newValue.getMonth() + 1).padStart(2, '0');
        const day = String(newValue.getDate()).padStart(2, '0');
        form.ics_date = `${year}-${month}-${day}`; // Format as YYYY-MM-DD
    } else {
        form.ics_date = '';
    }
});

// Sync prDate with form.pr_date
watch(prDate, (newValue) => {
    if (newValue) {
        const year = newValue.getFullYear();
        const month = String(newValue.getMonth() + 1).padStart(2, '0');
        const day = String(newValue.getDate()).padStart(2, '0');
        form.pr_date = `${year}-${month}-${day}`; // Format as YYYY-MM-DD
    } else {
        form.pr_date = '';
    }
});

// Sync poDate with form.po_date
watch(poDate, (newValue) => {
    if (newValue) {
        const year = newValue.getFullYear();
        const month = String(newValue.getMonth() + 1).padStart(2, '0');
        const day = String(newValue.getDate()).padStart(2, '0');
        form.po_date = `${year}-${month}-${day}`; // Format as YYYY-MM-DD
    } else {
        form.po_date = '';
    }
});

// Sync form.ics_date back to icsDate if changed externally
watch(() => form.ics_date, (newValue) => {
    if (newValue && newValue !== (icsDate.value ? icsDate.value.toISOString().split('T')[0] : '')) {
        icsDate.value = new Date(newValue);
    } else if (!newValue) {
        icsDate.value = null;
    }
});

// Sync form.pr_date back to prDate if changed externally
watch(() => form.pr_date, (newValue) => {
    if (newValue && newValue !== (prDate.value ? prDate.value.toISOString().split('T')[0] : '')) {
        prDate.value = new Date(newValue);
    } else if (!newValue) {
        prDate.value = null;
    }
});

// Sync form.po_date back to poDate if changed externally
watch(() => form.po_date, (newValue) => {
    if (newValue && newValue !== (poDate.value ? poDate.value.toISOString().split('T')[0] : '')) {
        poDate.value = new Date(newValue);
    } else if (!newValue) {
        poDate.value = null;
    }
});

// State to track auto-selection and override status
const isLocationAutoSelected = ref(false);
const isDDCAutoSelected = ref(false);
const isYearAutoSelected = ref(false);
const isLocationOverridden = ref(false);
const isDDCOverridden = ref(false);
const isYearOverridden = ref(false);
const isCallNumberValid = ref(true);
const cutterNumber = ref<string | null>(null);
const isAuthorNotFound = ref(false);
const isLoadingAuthor = ref(false);
const hasAutoSelectedAuthor = ref(false);

// Function to extract DDC number from call number
const extractDDCNumber = (callNumber: string): string | null => {
    if (!callNumber) return null;
    const parts = callNumber.trim().split(/[\s.]/);
    const ddcPart = parts.find((part) => /^\d+(\.\d+)?$/.test(part));
    if (ddcPart) {
        const ddcNumber = parseFloat(ddcPart);
        if (ddcNumber >= 0 && ddcNumber < 1000) {
            return ddcPart;
        }
    }
    return null;
};

// Function to check if DDC number falls within a range
const isDDCInRange = (ddcNumber: string, range: string): boolean => {
    const ddcValue = parseFloat(ddcNumber);
    const ranges = range.split(',').map((r) => r.trim());
    for (const singleRange of ranges) {
        const [start, end] = singleRange.split('-').map((s) => s.trim());
        const startValue = parseFloat(start);
        const endValue = end ? parseFloat(end) : startValue;
        if (ddcValue >= startValue && ddcValue <= endValue) {
            return true;
        }
    }
    return false;
};

// Function to extract year from call number
const extractYear = (callNumber: string): string | null => {
    if (!callNumber) return null;
    const parts = callNumber.trim().split(/[\s.]/);
    const yearPart = parts.reverse().find((part) => /^\d{4}$/.test(part));
    if (yearPart) {
        const year = parseInt(yearPart);
        const currentYear = new Date().getFullYear();
        if (year >= 1800 && year <= currentYear) {
            return year.toString();
        }
    }
    return null;
};

// Function to extract Cutter number from call number
const extractCutterNumber = (callNumber: string): string | null => {
    if (!callNumber) return null;
    const parts = callNumber.trim().split(/[\s.]/);
    const ddcPart = parts.find((part) => /^\d+(\.\d+)?$/.test(part));
    const ddcIndex = ddcPart ? parts.indexOf(ddcPart) : -1;
    if (ddcIndex !== -1 && ddcIndex + 1 < parts.length) {
        const cutterCandidate = parts[ddcIndex + 1];
        if (/^[A-Za-z]+\d+/.test(cutterCandidate)) {
            return cutterCandidate;
        }
    }
    return null;
};

// Auto-select author based on cutter number
const autoSelectFromCutter = async (cutterNumber: string) => {
    if (!cutterNumber || hasAutoSelectedAuthor.value) return;

    isLoadingAuthor.value = true;
    isAuthorNotFound.value = false;

    try {
        const url = new URL('/api/authors/search', window.location.origin);
        url.searchParams.append('cutter_number', cutterNumber);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            const authors = data.authors || data || [];

            const matchingAuthor = authors.find((author) => author.author_number?.toLowerCase() === cutterNumber.toLowerCase());

            if (matchingAuthor) {
                await nextTick();
                form.primary_author = matchingAuthor.name;
                handleAuthorSelected(matchingAuthor);
                hasAutoSelectedAuthor.value = true;
                isAuthorNotFound.value = false;
            } else {
                await nextTick();
                form.primary_author = '';
                handleAuthorSelected(null);
                hasAutoSelectedAuthor.value = false;
                isAuthorNotFound.value = true;
            }
        } else {
            console.error('Author search failed:', response.statusText);
            form.primary_author = '';
            handleAuthorSelected(null);
            isAuthorNotFound.value = true;
        }
    } catch (error) {
        console.error('Author search error:', error);
        form.primary_author = '';
        handleAuthorSelected(null);
        isAuthorNotFound.value = true;
    } finally {
        isLoadingAuthor.value = false;
    }
};

// Watch for changes in call_number and auto-select fields
watch(
    () => form.call_number,
    (newCallNumber: string, oldCallNumber: string) => {
        isLocationAutoSelected.value = false;
        isDDCAutoSelected.value = false;
        isYearAutoSelected.value = false;
        isCallNumberValid.value = true;
        isAuthorNotFound.value = false;

        const newCutter = extractCutterNumber(newCallNumber);
        const oldCutter = extractCutterNumber(oldCallNumber);

        if (newCutter !== oldCutter) {
            form.primary_author = '';
            cutterNumber.value = newCutter;
            hasAutoSelectedAuthor.value = false;
        }

        if (!newCallNumber) {
            form.physical_location_id = '';
            form.ddc_class_id = '';
            form.publication_year = '';
            form.primary_author = '';
            cutterNumber.value = null;
            hasAutoSelectedAuthor.value = false;
            isAuthorNotFound.value = false;
            return;
        }

        const callNumberPrefix = newCallNumber.trim().split(/[\s.]/)[0].toUpperCase();
        const matchingLocation = props.physicalLocations.find((loc) => loc.symbol?.toUpperCase() === callNumberPrefix);
        if (matchingLocation) {
            form.physical_location_id = matchingLocation.id.toString();
            isLocationAutoSelected.value = true;
            isLocationOverridden.value = false;
        } else {
            form.physical_location_id = '';
            isCallNumberValid.value = false;
        }

        const ddcNumber = extractDDCNumber(newCallNumber);
        if (ddcNumber) {
            const matchingDDC = props.ddcClassifications.find((ddc) => isDDCInRange(ddcNumber, ddc.number_range));
            if (matchingDDC) {
                form.ddc_class_id = matchingDDC.id.toString();
                isDDCAutoSelected.value = true;
                isDDCOverridden.value = false;
            } else {
                form.ddc_class_id = '';
                isCallNumberValid.value = false;
            }
        } else {
            form.ddc_class_id = '';
            isCallNumberValid.value = false;
        }

        const year = extractYear(newCallNumber);
        if (year) {
            form.publication_year = year;
            isYearAutoSelected.value = true;
            isYearOverridden.value = false;
        } else {
            form.publication_year = '';
            isCallNumberValid.value = false;
        }

        cutterNumber.value = newCutter;
        if (!newCutter) {
            form.primary_author = '';
            isCallNumberValid.value = false;
            isAuthorNotFound.value = false;
        }
    },
);

const handleAuthorSelected = (author: any) => {
    form.primary_author = author ? author.name : '';
    isAuthorNotFound.value = !author && !form.primary_author;
};

// Watch for cutterNumber changes
watch(
    () => cutterNumber.value,
    (newCutterNumber, oldCutterNumber) => {
        if (newCutterNumber && newCutterNumber !== oldCutterNumber) {
            hasAutoSelectedAuthor.value = false;
            isAuthorNotFound.value = false;
            autoSelectFromCutter(newCutterNumber);
        } else if (!newCutterNumber) {
            hasAutoSelectedAuthor.value = false;
            isAuthorNotFound.value = false;
            form.primary_author = '';
            handleAuthorSelected(null);
        }
    },
    { immediate: true },
);

// Watch for manual changes to primary_author
watch(
    () => form.primary_author,
    (newValue, oldValue) => {
        if (isAuthorNotFound.value && newValue !== oldValue) {
            hasAutoSelectedAuthor.value = false; // Reset auto-selection on manual input
            isAuthorNotFound.value = !newValue; // Update not found state based on input
        }
    },
);

// Watch for manual changes to detect overrides
watch(
    () => form.physical_location_id,
    (newValue, oldValue) => {
        if (isLocationAutoSelected.value && newValue !== oldValue && oldValue !== '') {
            isLocationOverridden.value = true;
        }
    },
);

watch(
    () => form.ddc_class_id,
    (newValue, oldValue) => {
        if (isDDCAutoSelected.value && newValue !== oldValue && oldValue !== '') {
            isDDCOverridden.value = true;
        }
    },
);

watch(
    () => form.publication_year,
    (newValue, oldValue) => {
        if (isYearAutoSelected.value && newValue !== oldValue && oldValue !== '') {
            isYearOverridden.value = true;
        }
    },
);

function useSourceChecker(form, sources) {
    return (targetNames: string[]) => {
        return computed(() => {
            const matchedSources = sources.filter((s) =>
                targetNames.includes(s.name.toLowerCase())
            )
            return matchedSources.some(
                (s) => form.source_id?.toString() === s.id?.toString()
            )
        })
    }
}

const checkSource = useSourceChecker(form, props.sources)

const isPurchased = checkSource(["purchased", "purchased-photocopy"])
const isDonated = checkSource(["donation", 'donation-photocopy'])
const isReplaced = checkSource(["replaced"])

const submit = () => {
    form.post(route('books.store'));
};
</script>

<template>
    <Head title="Add Book" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <RecordsLayout>
            <div class="flex flex-col gap-6 overflow-x-auto rounded-xl bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="mx-auto flex max-w-5xl flex-col gap-8">
                    <!-- Basic Information -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Basic Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="accession_number">Accession Number</Label>
                                <Input placeholder="Accession number" id="accession_number" type="text" required v-model="form.accession_number" />
                                <InputError :message="form.errors.accession_number" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="call_number">
                                    Call Number
                                    <span class="block text-xs text-muted-foreground">Example: GR 808.8 c967 1937</span>
                                </Label>
                                <Input id="call_number" placeholder="Call Number..." type="text" v-model="form.call_number" />
                                <span v-if="!isCallNumberValid && form.call_number" class="text-sm text-red-500">Invalid call number format</span>
                                <InputError :message="form.errors.call_number" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="isbn">ISBN</Label>
                                <Input id="isbn" placeholder="ISBN..." type="text" required v-model="form.isbn" />
                                <InputError :message="form.errors.isbn" />
                            </div>
                            <div class="col-span-2 grid gap-2">
                                <Label for="title">Title</Label>
                                <Input id="title" placeholder="Title..." type="text" required v-model="form.title" />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="volume">Volume</Label>
                                <Input id="volume" placeholder="Volume..." type="text" required v-model="form.volume" />
                                <InputError :message="form.errors.volume" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="primary_author">Primary Author</Label>
                                <div class="relative">
                                    <Input
                                        v-model="form.primary_author"
                                        :disabled="isLoadingAuthor || (hasAutoSelectedAuthor && !isAuthorNotFound)"
                                        :placeholder="
                                            isLoadingAuthor
                                                ? 'Loading...'
                                                : isAuthorNotFound
                                                  ? 'Enter author name'
                                                  : hasAutoSelectedAuthor
                                                    ? 'Auto-selected from Cutter number'
                                                    : 'Enter or auto-select from Cutter number'
                                        "
                                        :class="[
                                            isAuthorNotFound ? 'bg-white' : 'bg-gray-50',
                                            form.primary_author ? 'text-gray-900' : 'text-gray-500',
                                            isAuthorNotFound ? 'text-gray-900' : '',
                                            isLoadingAuthor || (hasAutoSelectedAuthor && !isAuthorNotFound) ? 'cursor-not-allowed' : 'cursor-text',
                                        ]"
                                    />
                                    <div v-if="isLoadingAuthor" class="absolute top-1/2 right-3 -translate-y-1/2 transform">
                                        <div class="size-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"></div>
                                    </div>
                                    <div
                                        v-else-if="!form.primary_author && !isLoadingAuthor"
                                        class="absolute top-1/2 right-3 -translate-y-1/2 transform"
                                    >
                                        <BookOpen class="size-4 text-muted-foreground" />
                                    </div>
                                </div>
                                <span
                                    v-if="cutterNumber && form.primary_author && hasAutoSelectedAuthor && !isAuthorNotFound"
                                    class="text-sm text-green-500"
                                >
                                    Auto selected from Cutter number
                                </span>
                                <span v-if="cutterNumber && isAuthorNotFound" class="text-sm text-red-500">
                                    No author found for Cutter number, please enter manually
                                </span>
                                <InputError :message="form.errors.primary_author" />
                            </div>
                            <div class="col-span-2 grid gap-2">
                                <div class="flex gap-2">
                                    <Label for="co_authors">Co-authors</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' for each co-author)</span>
                                </div>
                                <AuthorsTagsInput id="co_authors" v-model="form.co_authors" />
                                <InputError :message="form.errors.co_authors" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edition">Edition</Label>
                                <Input id="edition" placeholder="Edition..." type="text" required v-model="form.edition" />
                                <InputError :message="form.errors.edition" />
                            </div>
                            <div class="col-span-2 grid gap-2">
                                <div class="flex gap-2">
                                    <Label for="editors">Editor/s</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' for each editor)</span>
                                </div>
                                <EditorsTagsInput v-model="form.editors" />
                                <InputError :message="form.errors.editors" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publisher">Publisher</Label>
                                <Input id="publisher" placeholder="Publisher..." type="text" required v-model="form.publisher" />
                                <InputError :message="form.errors.publisher" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publication_year">Copyright Date</Label>
                                <Input id="publication_year" placeholder="Copyright Date..." type="number" required v-model="form.publication_year" />
                                <span v-if="isYearAutoSelected && !isYearOverridden" class="text-sm text-green-500">Auto selected</span>
                                <span v-if="isYearOverridden" class="text-sm text-blue-500">Overridden auto select</span>
                                <span v-if="!isCallNumberValid && !isYearAutoSelected && form.call_number" class="text-sm text-red-500"
                                >Invalid call number format</span
                                >
                                <InputError :message="form.errors.publication_year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publication_place">Publication Place</Label>
                                <Input id="publication_place" placeholder="Publication place..." type="text" required v-model="form.publication_place" />
                                <InputError :message="form.errors.publication_place" />
                            </div>
                        </div>
                    </section>

                    <!-- Classification & Location -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Classification & Location</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="ddc_class_id">DDC Classification</Label>
                                <Select v-model="form.ddc_class_id">
                                    <SelectTrigger id="ddc_class_id">
                                        <SelectValue placeholder="Select DDC classification" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="ddc in props.ddcClassifications" :key="ddc.id" :value="ddc.id.toString()">
                                            {{ ddc.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="isDDCAutoSelected && !isDDCOverridden" class="text-sm text-green-500">Auto selected</span>
                                <span v-if="isDDCOverridden" class="text-sm text-blue-500">Overridden auto select</span>
                                <span v-if="!isCallNumberValid && !isDDCAutoSelected && form.call_number" class="text-sm text-red-500"
                                >Invalid call number format</span
                                >
                                <InputError :message="form.errors.ddc_class_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="physical_location_id">Location</Label>
                                <Select v-model="form.physical_location_id" required>
                                    <SelectTrigger id="physical_location_id">
                                        <SelectValue placeholder="Select location" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="loc in props.physicalLocations" :key="loc.id" :value="loc.id.toString()">
                                            {{ loc.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="isLocationAutoSelected && !isLocationOverridden" class="text-sm text-green-500">Auto selected</span>
                                <span v-if="isLocationOverridden" class="text-sm text-blue-500">Overridden auto select</span>
                                <span v-if="!isCallNumberValid && !isLocationAutoSelected && form.call_number" class="text-sm text-red-500"
                                >Invalid call number format</span
                                >
                                <InputError :message="form.errors.physical_location_id" />
                            </div>
                        </div>
                    </section>

                    <!-- Physical Description -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Physical Description</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="cover_type">Cover Type</Label>
                                <CoverTypeComboBox />
                            </div>
                            <div class="grid gap-2">
                                <Label for="cover_image">Cover Page</Label>
                                <Input id="cover_image" type="file" @change="(e) => (form.cover_image = e.target.files[0])" />
                                <InputError :message="form.errors.cover_image" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status" required>
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="available">Available</SelectItem>
                                        <SelectItem value="damaged">Damaged</SelectItem>
                                        <SelectItem value="missing">Missing</SelectItem>
                                        <SelectItem value="borrowed">Borrowed</SelectItem>
                                        <SelectItem value="discarded">Discarded</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.status" />
                            </div>
                        </div>
                    </section>

                    <!-- Administrative Information -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Procurement Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="ics_number">ICS Number</Label>
                                <Input id="ics_number" placeholder="ICS number..." type="number" v-model="form.ics_number" />
                                <InputError :message="form.errors.ics_number" />
                            </div>
                            <div v-if="form.ics_number" class="grid gap-2">
                                <Label for="ics_date">ICS Date</Label>
                                <DatePicker class="min-w-full"
                                            v-model:value="icsDate"
                                            type="date"
                                            valueType="date"
                                            format="YYYY-MM-DD"
                                            placeholder="Select ICS date"
                                />
                                <InputError :message="form.errors.ics_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="pr_number">PR Number</Label>
                                <Input id="pr_number" placeholder="PR number..." type="number" v-model="form.pr_number" />
                                <InputError :message="form.errors.pr_number" />
                            </div>
                            <div v-if="form.pr_number" class="grid gap-2">
                                <Label for="pr_date">PR Date</Label>
                                <DatePicker class="min-w-full"
                                            v-model:value="prDate"
                                            type="date"
                                            valueType="date"
                                            format="YYYY-MM-DD"
                                            placeholder="Select PR date"
                                />
                                <InputError :message="form.errors.pr_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="po_number">PO Number</Label>
                                <Input id="po_number" type="number" v-model="form.po_number" />
                                <InputError :message="form.errors.po_number" />
                            </div>
                            <div v-if="form.po_number" class="grid gap-2">
                                <Label for="po_date">PO Date</Label>
                                <DatePicker class="min-w-full"
                                            v-model:value="poDate"
                                            type="date"
                                            valueType="date"
                                            format="YYYY-MM-DD"
                                            placeholder="Select PO date"
                                />
                                <InputError :message="form.errors.po_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="source">Source</Label>
                                <Select v-model="form.source_id" required >
                                    <SelectTrigger id="source" class="min-w-full">
                                        <SelectValue placeholder="Select source" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="source in props.sources" :key="source.id" :value="source.id">
                                            {{ source.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.source_id" />
                            </div>
                            <template v-if="isPurchased">
                                <div class="grid gap-2">
                                    <div class="flex items-center gap-2">
                                        <Label for="purchase_amount">Purchase Amount</Label>
                                        <span class="text-xs text-muted-foreground">(purchase related info)</span>
                                    </div>
                                    <Input id="purchase_amount" placeholder="Purchase amount..." type="number" step="0.01" v-model="form.purchase_amount" />
                                    <InputError :message="form.errors.purchase_amount" />
                                </div>

                                <div class="grid gap-2">
                                    <div class="flex items-center gap-2">
                                        <Label for="lot_cost">Lot Cost</Label>
                                        <span class="text-xs text-muted-foreground">(purchase related info)</span>
                                    </div>
                                    <Input id="lot_cost" placeholder="Lot cost..." type="number" step="0.01" v-model="form.lot_cost" />
                                    <InputError :message="form.errors.lot_cost" />
                                </div>

                                <div class="grid gap-2">
                                    <div class="flex items-center gap-2">
                                        <Label for="supplier">Supplier</Label>
                                        <span class="text-xs text-muted-foreground">(purchase related info)</span>
                                    </div>
                                    <Input id="supplier" placeholder="Supplier..." type="text" v-model="form.supplier" />
                                    <InputError :message="form.errors.supplier" />
                                </div>
                            </template>
                            <template v-if="isDonated">
                                <div class="grid gap-2">
                                    <div class="flex items-center gap-2">
                                        <Label for="donated_by">Donated By</Label>
                                        <span class="text-xs text-muted-foreground">(donation related info)</span>
                                    </div>
                                    <Input id="donated_by" placeholder="Donated by..." type="text" v-model="form.donated_by" />
                                    <InputError :message="form.errors.donated_by" />
                                </div>
                            </template>
                            <template v-if="isReplaced">
                                <div class="grid gap-2">
                                    <div class="flex items-center gap-2">
                                        <Label for="replaced_by">Replaced By</Label>
                                        <span class="text-xs text-muted-foreground">(replacement related info)</span>
                                    </div>
                                    <Input id="replaced_by" placeholder="Replaced by..." type="text" v-model="form.replaced_by" />
                                    <InputError :message="form.errors.replaced_by" />
                                </div>
                            </template>
                        </div>
                    </section>

                    <!-- Content Description -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Content Description</h2>
                        <div class="grid gap-6">
                            <div class="grid gap-2">
                                <Label for="table_of_contents">Table of Contents</Label>
                                <Textarea id="table_of_contents" rows="4" v-model="form.table_of_contents" />
                                <InputError :message="form.errors.table_of_contents" />
                            </div>
                            <div class="grid gap-2">
                                <div class="flex gap-2">
                                    <Label for="">Subject Heading/s</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' for each subject)</span>
                                </div>
                                <SubjectTagsInput v-model="form.subject_headings" />
                                <InputError :message="form.errors.subject_headings" />
                            </div>
                        </div>
                    </section>

                    <!-- Submit -->
                    <div class="flex justify-end pt-4">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Add Book Record
                        </Button>
                    </div>
                </form>
            </div>
        </RecordsLayout>
    </AppLayout>
</template>
