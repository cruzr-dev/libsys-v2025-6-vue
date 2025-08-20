<script setup lang="ts">
    import { Head, router, useForm } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { BreadcrumbItem } from '@/types';
    import { Input } from '@/components/ui/input';
    import { LoaderCircle, Search } from 'lucide-vue-next';
    import { Button } from '@/components/ui/button';
    import { computed, ref, watchEffect } from 'vue';
    import BookScannerDialog from '@/components/BookScannerDialog.vue';
    import SearchUsers from '@/components/SearchUsers.vue';

    const breadcrumbs: BreadcrumbItem[] = [
      {
          title: 'Borrowings',
          href: '/borrowings',
      },
      {
          title: 'Borrow',
          href: '/borrowings/create',
      },
    ];

    const props = defineProps({
      search_ac_result: Object,
    });

    const form = useForm({
        searchAcc: "",
    });

    const searchAcc = () => {
        router.get(route('borrowings.create'), {searchAcc: form.searchAcc})
    }

    const clearSearch = () => {
        form.searchAcc = "";
        // Optional: Also clear any search results by navigating back to the base route
        router.get(route('borrowings.create'));
    }

    const searchedTerm = computed(() => {
        return new URLSearchParams(window.location.search).get('searchAcc') || ''
    })

    const searchAttempted = computed(() => {
        return searchedTerm.value !== ''
    })

    interface BorrowFormData {
        accession_number: number | null
        borrow_type: 'inside' | 'take-home' | null
    }

    type BorrowType = 'inside' | 'take-home'

    const borrowForm = useForm<BorrowFormData>({
        accession_number: null,
        borrow_type: null
    })

    const borrow = (type: BorrowType): void => {
        if (!props.search_ac_result) return

        borrowForm.accession_number = props.search_ac_result.accession_number
        borrowForm.borrow_type = type
        borrowForm.post(route('borrowings.store'))
    }

    // Create persistent accession number variable
    const selectedAccessionNumber = ref<number | null>(null);

    // Update when search result changes
    watchEffect(() => {
        if (props.search_ac_result?.accession_number) {
            selectedAccessionNumber.value = props.search_ac_result.accession_number;
        }
    });


</script>

<template>
    <Head title="Borrow/Return Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                <!-- Book Search and Result Section -->
                <div class="flex flex-col gap-4 w-full">
                    <!-- Book Result Placeholder -->
                    <div class="search-result rounded-lg border p-4">
                        <h3 class="text-lg font-semibold mb-2">Book Information</h3>
                        <div class="space-y-2">
                            <p>
                                <strong>Accession Number:</strong>
                                <span v-if="search_ac_result" class="text-muted-foreground">
                                    {{ search_ac_result.accession_number }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Enter accession number below
                                </span>
                            </p>
                            <p>
                                <strong>Title:</strong>
                                <span v-if="search_ac_result" class="text-muted-foreground">
                                    {{ search_ac_result.title || 'N/A' }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Book title will appear here
                                </span>
                            </p>
                            <p>
                                <strong>Status:</strong>
                                <span v-if="search_ac_result" class="text-muted-foreground">
                                    {{ search_ac_result.status || 'N/A' }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Availability status will appear here
                                </span>
                            </p>
                        </div>
                        <Button v-if="search_ac_result"
                            @click="borrow('inside')"
                            :disabled="borrowForm.processing"
                            class="flex-1"
                        >
                            Borrow (Inside)
                        </Button>
                    </div>
                    <!-- Search Form -->
                    <BookScannerDialog />
                    <form @submit.prevent="searchAcc" class="flex flex-col gap-3">
                        <div class="relative">
                            <Input
                                required
                                id="search"
                                placeholder="Search Accession Number..."
                                type="number"
                                class="pl-10"
                                v-model="form.searchAcc"
                            />
                            <span class="absolute left-0 inset-y-0 flex items-center justify-center px-2">
                                <Search class="size-6 text-muted-foreground" />
                            </span>
                        </div>
                        <div class="flex gap-3">
                            <Button
                                variant="outline"
                                class="flex-1"
                                tabindex="6"
                                :disabled="form.processing"
                                @click="clearSearch"
                            >
                                Clear
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1"
                                tabindex="5"
                                :disabled="form.processing"
                            >
                                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <span v-else>Find Book</span>
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Patron Search and Result Section -->
                <div class="flex flex-col gap-4">

                    <SearchUsers :accessionNumber="selectedAccessionNumber" />

                </div>
            </div>
        </div>
    </AppLayout>
</template>
