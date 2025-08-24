<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import WelcomeBookDialog from '@/components/WelcomeBookDialog.vue';
import WelcomeSearch from '@/components/WelcomeSearch.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Activity, AlertCircle, CreditCard, DollarSign, Users, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

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

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
        config: Config;
    }
}

// Component props
defineProps<{
    records: object;
    search_result: object;
    search_term: string;
    search_button: boolean;
}>();

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
});
</script>

<template>
    <Head title="Welcome" />

    <div class="flex flex-col bg-background text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden logger link -->
        <Link :href="route('logger.create')" class="fixed top-0 left-0 bg-green-500 opacity-0">hi</Link>

        <!-- Error alert -->
        <Alert v-if="page.props.flash.error && showAlert" class="absolute top-5 right-5 w-fit pr-8" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ page.props.flash.error }}</AlertDescription>
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
                <div class="flex h-[360px] min-w-full items-center justify-center rounded-2xl bg-[url(/storage/system_images/eagle.jpg)] bg-cover">
                    <div class="grid rounded-lg bg-background">
                        <WelcomeSearch :search_result="search_result" :search_term="search_term" :search_button="search_button" />
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
            <div class="w-full px-16 py-12" v-if="Object.keys(records?.data).length">
                <CardTitle class="pb-12 text-center text-2xl font-medium text-foreground"> Latest in Collections </CardTitle>
                <div class="grid grid-cols-3 gap-4">
                    <div v-for="record in records?.data" :key="record.id">
                        <WelcomeBookDialog :record="record" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer spacing -->
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
