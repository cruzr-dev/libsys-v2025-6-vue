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
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CoverTypeComboBox from '@/components/CoverTypeComboBox.vue';
import DatePicker from 'vue-datepicker-next';
import 'vue-datepicker-next/index.css';

// Props
const props = defineProps<{
    record: {
        id: number;
        accession_number: string;
        title: string;
        status: string;
        subject: string[];

        // Add this for the pivot relationship:
        authors: Array<{
            id: number;
            name: string;
            pivot: {
                role: 'primary author' | 'co-author';
            };
        }>;

        editors: Array<{
            id: number;
            name: string;
        }>;

        // Related book model
        book: {
            id: number;
            call_number: string;
            ddc_class_id: number | string;
            physical_location_id: number | string;
            cover_type_id: number | null;
            cover_image: string | null;
            volume: string;
            edition: string;
            editors: string[];
            publication_year: string;
            publisher: string;
            publication_place: string;
            isbn: string;
            table_of_contents: string;
            ics_number: string;
            ics_date: string;
            pr_number: string;
            pr_date: string;
            po_number: string;
            po_date: string;
            source_id: number | string;
            purchase_amount: string;
            lot_cost: string;
            supplier: string;
            donated_by: string;
            replaced_by: string;
        };
    };
    ddcClassifications: { id: number; number_range: string; title: string }[];
    lcClassifications: { id: number; code: string; name: string }[];
    physicalLocations: { id: number; name: string; symbol: string }[];
    coverTypes: { id: number; name: string }[];
    sources: { id: number; name: string }[];
    // Add available authors for the tags input
    authors: { id: number; name: string }[];
}>();

const getPrimaryAuthor = () => {
    const primaryAuthor = props.record.authors?.find(
        author => author.pivot.role === 'primary author'
    );
    return primaryAuthor ? primaryAuthor.name : '';
};

const getCoAuthors = () => {
    return props.record.authors
        ?.filter(author => author.pivot.role === 'co-author')
        ?.map(author => author.name) || [];
};

const getEditors = () => {
    return props.record.editors
        ?.map(author => author.name) || [];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Records', href: '/records' },
    { title: 'Books', href: '/records/books' },
    { title: 'Edit Book', href: `/records/books/${props.record.id}/edit` },
];

// Add this ref for handling the cover image file separately
const coverImageFile = ref<File | null>(null);

// Handle cover image file input change
const handleCoverImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;
    coverImageFile.value = file;
    form.clearErrors('cover_image');
};

const form = useForm({
    accession_number: props.record.accession_number || '',
    title: props.record.title || '',
    volume: props.record.book.volume || '',
    primary_author: getPrimaryAuthor(),
    co_authors: getCoAuthors(),
    edition: props.record.book.edition || '',
    editors: getEditors(),
    publication_year: props.record.book.publication_year || '',
    publisher: props.record.book.publisher || '',
    publication_place: props.record.book.publication_place || '',
    isbn: props.record.book.isbn || '',
    call_number: props.record.book.call_number || '',
    ddc_class_id: props.record.book.ddc_class_id?.toString() || '',
    physical_location_id: props.record.book.physical_location_id?.toString() || '',
    ics_number: props.record.book.ics_number || '',
    ics_date: props.record.book.ics_date || '',
    pr_number: props.record.book.pr_number || '',
    pr_date: props.record.book.pr_date || '',
    po_number: props.record.book.po_number || '',
    po_date: props.record.book.po_date || '',
    source_id: props.record.book.source_id?.toString() || '',
    purchase_amount: props.record.book.purchase_amount || '',
    lot_cost: props.record.book.lot_cost || '',
    supplier: props.record.book.supplier || '',
    donated_by: props.record.book.donated_by || '',
    replaced_by: props.record.book.replaced_by || '',
    cover_type_id: props.record.book.cover_type_id || '',
    table_of_contents: props.record.book.table_of_contents || '',
    subject_headings: props.record.subject || [],
    status: props.record.status || 'available',
});
// Auto-clear errors when editing fields
watch(
    () => ({ ...form }), // watch whole form
    (newForm, oldForm) => {
        if (!oldForm) return
        for (const key in newForm) {
            if (
                form.errors[key] && // has an error
                newForm[key] !== oldForm[key] // value actually changed
            ) {
                form.clearErrors(key)
            }
        }
    },
    { deep: true }
)

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

// Watch for changes in call_number and auto-select fields
watch(
    () => form.call_number,
    (newCallNumber: string) => {
        isLocationAutoSelected.value = false;
        isDDCAutoSelected.value = false;
        isYearAutoSelected.value = false;
        isCallNumberValid.value = true;

        if (!newCallNumber) {
            // Don't auto-clear in edit mode - user might intentionally clear
            return;
        }

        const callNumberPrefix = newCallNumber.trim().split(/[\s.]/)[0].toUpperCase();
        const matchingLocation = props.physicalLocations.find((loc) => loc.symbol?.toUpperCase() === callNumberPrefix);
        if (matchingLocation) {
            form.physical_location_id = matchingLocation.id.toString();
            isLocationAutoSelected.value = true;
            isLocationOverridden.value = false;
        } else {
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
                isCallNumberValid.value = false;
            }
        } else {
            isCallNumberValid.value = false;
        }

        const year = extractYear(newCallNumber);
        if (year) {
            form.publication_year = year;
            isYearAutoSelected.value = true;
            isYearOverridden.value = false;
        } else {
            isCallNumberValid.value = false;
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
    // Create FormData to handle file upload
    const formData = new FormData();

    // Add all form fields
    Object.keys(form.data()).forEach(key => {
        const value = form.data()[key];

        // Handle arrays (like co_authors, editors, subject_headings)
        if (Array.isArray(value)) {
            if (value.length > 0) {
                value.forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item);
                });
            } else {
                // Send empty array indicator
                formData.append(`${key}[]`, '');
            }
        } else if (value !== null && value !== undefined && value !== '') {
            formData.append(key, value);
        }
    });

    // Add cover image if selected
    if (coverImageFile.value) {
        formData.append('cover_image', coverImageFile.value);
    }

    // Add _method field for PUT request (Laravel method spoofing)
    formData.append('_method', 'PATCH');

    // Send the FormData using Inertia's router
    router.post(route('books.update', props.record.id), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onBefore: () => {
            form.processing = true; // Set processing state manually
        },
        onFinish: () => {
            form.processing = false; // Reset processing state
        },
        onError: (errors) => {
            // Clear the file input on error
            coverImageFile.value = null;
            // Reset the file input element
            const fileInput = document.getElementById('cover_image') as HTMLInputElement;
            if (fileInput) fileInput.value = '';

            // Set errors using Inertia's built-in method
            Object.keys(errors).forEach(key => {
                form.setError(key, errors[key]);
            });
        },
    });
};

const goBack = () => {
    router.visit(route('books.index'))
}

</script>

<template>
    <Head title="Edit Book" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <RecordsLayout>
            <div class="flex flex-col gap-6 overflow-x-auto rounded-xl bg-white p-6 shadow-sm relative">

                <h2 class="text-xl text-center font-semibold text-gray-900">Edit Book</h2>

                <div class="absolute right-4 top-4">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="w-4 h-4" /> Back
                    </Button>
                </div>

                <form @submit.prevent="submit" enctype="multipart/form-data" class="mx-auto flex max-w-5xl flex-col gap-8">
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
                                <Input
                                    v-model="form.primary_author"
                                    placeholder="Enter author name"
                                    id="primary_author"
                                    type="text"
                                />
                                <InputError :message="form.errors.primary_author" />
                            </div>
                            <div class="col-span-2 grid gap-2">
                                <div class="flex gap-2">
                                    <Label for="co_authors">Co-authors</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' or ';' for each co-author)</span>
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
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' or ';' for each editor)</span>
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
                                <CoverTypeComboBox v-model:coverTypeId="form.cover_type_id"/>
                                <InputError :message="form.errors.cover_type_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="cover_image">Cover Page</Label>
                                <Input id="cover_image" type="file" accept="image/*" @change="handleCoverImageChange" />
                                <div v-if="props.record.book.cover_image && !coverImageFile" class="text-sm text-gray-500">
                                    Current file: {{ props.record.book.cover_image }}
                                </div>
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
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' or ';' for each subject)</span>
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
                            Update Book Record
                        </Button>
                    </div>
                </form>
            </div>
        </RecordsLayout>
    </AppLayout>
</template>
