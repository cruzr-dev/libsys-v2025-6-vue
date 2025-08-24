<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Activity, AlertCircle, CreditCard, DollarSign, Users, X } from 'lucide-vue-next';
import { ref, onMounted } from 'vue'
import WelcomeBookDialog from '@/components/WelcomeBookDialog.vue';
import WelcomeSearch from '@/components/WelcomeSearch.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

// for alert
const page = usePage()
const name = page.props.name;
const showAlert = ref(true)
onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false
        }, 5000)
    }
})

interface Flash {
    success?: string | null;
    error?: string | null;
}

interface Config {
    registration_enabled?: boolean | null
    login_enabled?: boolean | null
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
        config: Config;
    }
}

defineProps({
    records: Object,
    search_result: Object,
    search_term: String,
    search_button: Boolean,
    userCount: Number,
    recordCount: Number,
    transactionCount: Number,
});

const stats = [
    {
        title: 'Total Revenue',
        value: '$45,231.89',
        change: '+20.1% from last month',
        icon: DollarSign,
    },
    {
        title: 'Subscriptions',
        value: '+2350',
        change: '+180.1% from last month',
        icon: Users,
    },
    {
        title: 'Sales',
        value: '+12,234',
        change: '+19% from last month',
        icon: CreditCard,
    },
    {
        title: 'Active Now',
        value: '+573',
        change: '+201 since last hour',
        icon: Activity,
    },
]

</script>

<template>
    <Head title="Welcome">
    </Head>
    <div class="flex flex-col bg-background text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <Link :href="route('logger.create')" class="fixed top-0 left-0 opacity-0 bg-green-500">hi</Link>

        <Alert class="absolute top-5 right-5 w-fit pr-8" variant="destructive" v-if="page.props.flash.error && showAlert">
            <AlertCircle class="w-4 h-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 p-1 hover:bg-red-100 rounded-full transition-colors">
                <X class="w-4 h-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.error }}
            </AlertDescription>
        </Alert>

        <header class="flex justify-between items-center w-full p-2 px-8 text-sm not-has-[nav]:hidden">
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium dark:text-foreground">
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </Link>
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
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
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

        <!-- The content itself -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">

            <div class="p-8 h-[360px] bg-[url(/storage/system_images/eagle.jpg)] bg-cover min-w-full flex justify-center items-center">
                <div class="grid bg-background rounded-lg">
                    <WelcomeSearch :search_result="search_result" :search_term="search_term"
                                   :search_button="search_button"
                    />
                </div>
            </div>

            <div class="w-full p-16 pb-0 grid grid-cols-3 gap-16">
                <Card class="p-4 gap-2">
                    <h4 class="text-6xl">{{ userCount }}</h4>
                    <div>Patrons</div>
                </Card>
                <Card class="p-4 gap-2">
                    <h4 class="text-6xl">{{ recordCount }}</h4>
                    <div>Books, Multimedia, Magazine, Periodals Thesis, Dissertations</div>
                </Card>
                <Card class="p-4 gap-2">
                    <h4 class="text-6xl">{{ transactionCount }}</h4>
                    <div>Transactions</div>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="stat in stats"
                    :key="stat.title"
                    class="hover:shadow-md transition p-4"
                >
                    <CardHeader class="flex p-0 flex-row items-center justify-between pb-2 space-y-0">
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

            <div class="w-full p-16" v-if="Object.keys(records?.data).length">
                <h3 class="text-3xl text-center mb-8">Latest in Book Collections</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div v-for="record in records?.data" :key="record.id">
                        <WelcomeBookDialog :record="record" />
                    </div>
                </div>
            </div>

        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
