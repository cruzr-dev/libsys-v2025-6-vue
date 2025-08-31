<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import WelcomeRecordDialog from '@/components/WelcomeRecordDialog.vue';
import WelcomeSearch from '@/components/WelcomeSearch.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Activity, AlertCircle, CreditCard, DollarSign, Users, X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import WelcomeFooter from '@/components/WelcomeFooter.vue';
import CollectionSearchComboBox from '@/components/CollectionSearchComboBox.vue';

// Page and alert handling
const page = usePage();
const name = page.props.name;
const showAlert = ref(true);

// Types definition
interface Flash {
    success?: string | null;
    error?: string | null;
}

interface Config {
    registration_enabled?: boolean | null;
    login_enabled?: boolean | null;
}

interface CollectionRecord {
    id: number;
    [key: string]: any;
}

// API Response type for collections
interface ApiResponse {
    data: CollectionRecord[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
        config: Config;
    }
}

// Reactive state for collections data
const collections = ref<CollectionRecord[]>([]);
const isLoadingCollections = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const collectionsError = ref<string | null>(null);

// Collection search state
const selectedCollection = ref(null);

// Pagination state for collections
const pagination = ref({
    pageIndex: 0,
    pageSize: 6, // Show 6 latest collections
});

// Fetch latest collections data
const fetchLatestCollections = async () => {
    isLoadingCollections.value = true;
    collectionsError.value = null;

    try {
        // Build query parameters for latest collections
        const params = new URLSearchParams();

        // Pagination - get first page with limited results for latest items
        params.append('page', '1');
        params.append('per_page', pagination.value.pageSize.toString());

        // Sort by latest (assuming created_at or id for newest first)
        params.append('sort_field', 'created_at');
        params.append('sort_direction', 'desc');

        // Make API request for collections
        const response = await fetch(`/api/welcome_records?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result: ApiResponse = await response.json();

        // Update reactive data
        collections.value = result.data || [];
        currentPage.value = result.current_page || 1;
        lastPage.value = result.last_page || 1;
        total.value = result.total || 0;

        // Update pagination state to match API response
        pagination.value.pageIndex = (result.current_page || 1) - 1;

    } catch (err) {
        console.error('Collections API fetch error:', err);
        collectionsError.value = err instanceof Error ? err.message : 'An error occurred while fetching collections';
        collections.value = [];
    } finally {
        isLoadingCollections.value = false;
    }
};

// Handle collection selection
const handleCollectionSelected = (collection: any) => {
    selectedCollection.value = collection;
    // Handle navigation or other logic here
};

// Statistics data
const stats = [
    {
        title: 'Collections',
        value: '15,821',
        change: 'Book, CDs, Magazines, Thesis, etc.',
        icon: DollarSign,
    },
    {
        title: 'Clients',
        value: '2,350',
        change: 'Students, Staffs, Faculties',
        icon: Users,
    },
    {
        title: 'New Arrivals',
        value: '+234',
        change: '+19% from last month',
        icon: CreditCard,
    },
    {
        title: 'Borrowing Transactions',
        value: '+73',
        change: '+20 since last week',
        icon: Activity,
    },
];

// Alert timeout logic
onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false;
        }, 5000);
    }

    // Fetch latest collections on component mount
    fetchLatestCollections();
});

// Watch for URL changes and refetch data if needed
watch(() => window.location.search, () => {
    fetchLatestCollections();
});
</script>

<template>
    <Head title="Welcome" />

    <div class="flex flex-col bg-background text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden logger link -->
        <Link :href="route('logger.create')" class="fixed top-0 left-0 bg-green-500 opacity-0">hi</Link>

        <!-- Error alert -->
        <Alert v-if="page.props.flash.error && showAlert" class="absolute top-5 right-5 w-fit pr-8 z-50" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ page.props.flash.error }}</AlertDescription>
        </Alert>

        <!-- Collections API Error alert -->
        <Alert v-if="collectionsError && showAlert" class="absolute top-20 right-5 w-fit pr-8 z-50" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="collectionsError = null" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Collections Error</AlertTitle>
            <AlertDescription>{{ collectionsError }}</AlertDescription>
        </Alert>

        <!-- Header section -->
        <header class="flex w-full items-center justify-between p-4 px-16 text-sm not-has-[nav]:hidden">
            <div class="relative z-20 flex items-center text-lg font-medium dark:text-foreground">
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </div>
            <nav class="flex items-center justify-end gap-4">
                <AppearanceTabs />
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        v-if="$page.props.config.login_enabled"
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        <Button class="w-[120px]">Log in</Button>
                    </Link>
                    <Link
                        v-if="$page.props.config.registration_enabled"
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        <Button class="w-[120px]">Register</Button>
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main content -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">

            <!-- Search section -->
            <div class="px-4">
                <div class="relative flex h-[360px] min-w-full items-center justify-center rounded-2xl bg-[url(/storage/system_images/eagle.jpg)] bg-cover">
                    <!-- Heading + Sub-heading -->
                    <div class="absolute top-10 left-1/2 transform -translate-x-1/2 text-center text-primary-foreground  dark:text-muted-foreground">
                        <h1 class="text-3xl font-bold">USeP Tagum-Mabini Library</h1>
                        <p class="text-lg">Your gateway to knowledge and discovery</p>
                    </div>

                    <!-- Search Box -->
                    <div class="w-full max-w-md p-1 rounded-xl bg-background">
                        <CollectionSearchComboBox
                            v-model:selectedUser="selectedCollection"
                            @user-selected="handleCollectionSelected"
                            class="w-full rounded-lg"
                        />
                    </div>
                </div>
            </div>

            <!-- Statistics section -->
            <div class="grid gap-4 px-16 py-12 pb-0 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.title" class="p-4 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 p-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </CardTitle>
                        <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <p class="text-xs text-muted-foreground">{{ stat.change }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Latest collections section -->
            <div class="w-full px-16 py-12" v-if="collections.length > 0">
                <CardTitle class="pb-12 text-center text-2xl font-medium text-foreground"> Latest in Collections </CardTitle>

                <!-- Loading state -->
                <div v-if="isLoadingCollections" class="text-center py-8">
                    <p class="text-muted-foreground">Loading latest collections...</p>
                </div>

                <!-- Collections grid -->
                <div v-else class="grid grid-cols-3 gap-4">
                    <div v-for="record in collections" :key="record.id">
                        <WelcomeRecordDialog :record="record" />
                    </div>
                </div>
            </div>

            <!-- Empty state for collections -->
            <div v-else-if="!isLoadingCollections && collections.length === 0" class="w-full px-16 py-12">
                <CardTitle class="pb-12 text-center text-2xl font-medium text-foreground"> Latest in Collections </CardTitle>
                <div class="text-center py-8">
                    <p class="text-muted-foreground">No collections available at the moment.</p>
                </div>
            </div>
        </div>

        <!-- Footer spacing -->
        <div class="w-full p-4">
            <WelcomeFooter :appName="name" class="rounded-2xl"/>
        </div>
    </div>
</template>
