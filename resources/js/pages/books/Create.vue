<script setup lang="ts">
import { watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-vue-next';
import RecordsLayout from '@/layouts/records/Layout.vue';
import { Textarea } from '@/components/ui/textarea';
import AuthorsTagsInput from '@/components/AuthorsTagsInput.vue';
import EditorsTagsInput from '@/components/EditorsTagsInput.vue';
import SubjectTagsInput from '@/components/SubjectTagsInput.vue';

// Props from Inertia
const props = defineProps<{
    nextAccessionNumber: number;
    ddcClassifications: { id: number; code: string; name: string }[];
    lcClassifications: { id: number; code: string; name: string }[];
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
    authors: [],
    editors: [],
    publication_year: '',
    publisher: '',
    publication_place: '',
    isbn: '',
    call_number: '',
    ddc_class_id: '',
    lc_class_id: '',
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
    cover_type_id: '',
    table_of_contents: '',
    subject_headings: [],
    status: 'available',
});

// Create a mapping of call number prefixes to location symbols
const callNumberToLocationMapping: Record<string, string> = {
    'GR': 'Gr',      // General References
    'FIC': 'Fic',    // Fiction
    'FIL': 'Fil',    // Filipiniana
    'CIR': 'Cir',    // Circulation
    'RES': 'Res',    // Reserve
    'GS': 'Gs',      // Graduate School
    // Note: 'Gs/Fil' and PCARRD (null symbol) would need special handling if needed
};

// Watch for changes in call_number and auto-select location
watch(() => form.call_number, (newCallNumber: string) => {
    if (!newCallNumber) {
        // Clear location selection if call number is empty
        form.physical_location_id = '';
        return;
    }

    // Extract the first part (prefix) of the call number
    const callNumberPrefix = newCallNumber.trim().split(/[\s.]/)[0].toUpperCase();

    // Check if we have a mapping for this prefix
    if (callNumberToLocationMapping[callNumberPrefix]) {
        const locationSymbol = callNumberToLocationMapping[callNumberPrefix];

        // Find the matching physical location by symbol
        const matchingLocation = props.physicalLocations.find(
            loc => loc.symbol === locationSymbol
        );

        if (matchingLocation) {
            form.physical_location_id = matchingLocation.id.toString();
        }
    } else {
        // No mapping found - clear the location selection
        form.physical_location_id = '';
    }
});

const submit = () => {
    form.post(route('books.store'));
};
</script>

<template>
    <Head title="Add Book" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <RecordsLayout>
            <div class="flex flex-col gap-6 p-6 bg-white rounded-xl shadow-sm overflow-x-auto">
                <form @submit.prevent="submit" class="flex flex-col gap-8 max-w-5xl mx-auto">

                    <!-- Basic Information -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Basic Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="grid gap-2">
                                <Label for="accession_number">Accession Number</Label>
                                <Input placeholder="Accession number" id="accession_number" type="text" required v-model="form.accession_number" />
                                <InputError :message="form.errors.accession_number" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="call_number">
                                    Call Number
                                    <span class="text-xs text-muted-foreground block">Example: GR 808.8 El57h 1937</span>
                                </Label>
                                <Input id="call_number" type="text" v-model="form.call_number" />
                                <InputError :message="form.errors.call_number" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="isbn">ISBN</Label>
                                <Input id="isbn" type="text" required v-model="form.isbn" />
                                <InputError :message="form.errors.isbn" />
                            </div>
                            <div class="grid gap-2 col-span-2">
                                <Label for="title">Title</Label>
                                <Input id="title" type="text" required v-model="form.title" />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="volume">Volume</Label>
                                <Input id="volume" type="text" required v-model="form.volume" />
                                <InputError :message="form.errors.volume" />
                            </div>
                            <div class="grid gap-2 col-span-2">
                                <div class="flex gap-2 ">
                                    <Label for="authors">Author/s</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' for each author)</span>
                                </div>
                                <AuthorsTagsInput v-model="form.authors" />
                                <InputError :message="form.errors.authors" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edition">Edition</Label>
                                <Input id="edition" type="text" required v-model="form.edition" />
                                <InputError :message="form.errors.edition" />
                            </div>
                            <div class="grid gap-2 col-span-2">
                                <div class="flex gap-2">
                                    <Label for="editors">Editor/s</Label>
                                    <span class="text-sm text-gray-500">(Hit 'ENTER' for each editor)</span>
                                </div>
                                <EditorsTagsInput v-model="form.editors" />
                                <InputError :message="form.errors.editors" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publisher">Publisher</Label>
                                <Input id="publisher" type="text" required v-model="form.publisher" />
                                <InputError :message="form.errors.publisher" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publication_year">Copyright Date</Label>
                                <Input disabled id="publication_year" type="number" required v-model="form.publication_year" />
                                <InputError :message="form.errors.publication_year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="publication_place">Publication Place</Label>
                                <Input id="publication_place" type="text" required v-model="form.publication_place" />
                                <InputError :message="form.errors.publication_place" />
                            </div>
                        </div>
                    </section>

                    <!-- Classification & Location -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Classification & Location</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- DDC Classification -->
                            <div class="grid gap-2">
                                <Label for="ddc_class_id">DDC Classification</Label>
                                <Select v-model="form.ddc_class_id" disabled>
                                    <SelectTrigger id="ddc_class_id">
                                        <SelectValue placeholder="Select DDC classification" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="ddc in props.ddcClassifications"
                                            :key="ddc.id"
                                            :value="ddc.id.toString()"
                                        >
                                            {{ ddc.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.ddc_class_id" />
                            </div>

                            <!-- Physical Location -->
                            <div class="grid gap-2">
                                <Label for="physical_location_id">Location</Label>
                                <Select v-model="form.physical_location_id" required>
                                    <SelectTrigger id="physical_location_id">
                                        <SelectValue placeholder="Select location" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="loc in props.physicalLocations"
                                            :key="loc.id"
                                            :value="loc.id.toString()"
                                        >
                                            {{ loc.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.physical_location_id" />
                            </div>
                        </div>
                    </section>

                    <!-- Rest of the form sections remain the same -->
                    <!-- Physical Description -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-semibold">Physical Description</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Cover Type -->
                            <div class="grid gap-2">
                                <Label for="cover_type">Cover Type</Label>
                                <Select v-model="form.cover_type_id" required>
                                    <SelectTrigger id="cover_type">
                                        <SelectValue placeholder="Select cover type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in props.coverTypes"
                                            :key="type.id"
                                            :value="type.id"
                                        >
                                            {{ type.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.cover_type_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="cover_image">Cover Page</Label>
                                <Input id="cover_image" type="file" @change="e => form.cover_image = e.target.files[0]" />
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- ICS -->
                            <div class="grid gap-2">
                                <Label for="ics_number">ICS Number</Label>
                                <Input id="ics_number" type="number" v-model="form.ics_number" />
                                <InputError :message="form.errors.ics_number" />
                            </div>
                            <div v-if="form.ics_number" class="grid gap-2">
                                <Label for="ics_date">ICS Date</Label>
                                <Input id="ics_date" type="date" v-model="form.ics_date" />
                                <InputError :message="form.errors.ics_date" />
                            </div>

                            <!-- PR -->
                            <div class="grid gap-2">
                                <Label for="pr_number">PR Number</Label>
                                <Input id="pr_number" type="number" v-model="form.pr_number" />
                                <InputError :message="form.errors.pr_number" />
                            </div>
                            <div v-if="form.pr_number" class="grid gap-2">
                                <Label for="pr_date">PR Date</Label>
                                <Input id="pr_date" type="date" v-model="form.pr_date" />
                                <InputError :message="form.errors.pr_date" />
                            </div>

                            <!-- PO -->
                            <div class="grid gap-2">
                                <Label for="po_number">PO Number</Label>
                                <Input id="po_number" type="number" v-model="form.po_number" />
                                <InputError :message="form.errors.po_number" />
                            </div>
                            <div v-if="form.po_number" class="grid gap-2">
                                <Label for="po_date">PO Date</Label>
                                <Input id="po_date" type="date" v-model="form.po_date" />
                                <InputError :message="form.errors.po_date" />
                            </div>

                            <!-- Source -->
                            <div class="grid gap-2">
                                <Label for="source">Source</Label>
                                <Select v-model="form.source_id" required>
                                    <SelectTrigger id="source">
                                        <SelectValue placeholder="Select source" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="source in props.sources"
                                            :key="source.id"
                                            :value="source.id"
                                        >
                                            {{ source.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.source_id" />
                            </div>

                            <!-- Purchase-specific -->
                            <template v-if="form.source === 'purchase'">
                                <div class="grid gap-2">
                                    <Label for="purchase_amount">Purchase Amount</Label>
                                    <Input id="purchase_amount" type="number" step="0.01" v-model="form.purchase_amount" />
                                    <InputError :message="form.errors.purchase_amount" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="lot_cost">Lot Cost</Label>
                                    <Input id="lot_cost" type="number" step="0.01" v-model="form.lot_cost" />
                                    <InputError :message="form.errors.lot_cost" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="supplier">Supplier</Label>
                                    <Input id="supplier" type="text" v-model="form.supplier" />
                                    <InputError :message="form.errors.supplier" />
                                </div>
                            </template>

                            <!-- Donation-specific -->
                            <template v-if="form.source === 'donation'">
                                <div class="grid gap-2">
                                    <Label for="donated_by">Donated By</Label>
                                    <Input id="donated_by" type="text" v-model="form.donated_by" />
                                    <InputError :message="form.errors.donated_by" />
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
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Add Book Record
                        </Button>
                    </div>
                </form>
            </div>
        </RecordsLayout>
    </AppLayout>
</template>
