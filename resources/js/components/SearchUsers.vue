<script setup lang="ts">
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { Combobox, ComboboxAnchor, ComboboxInput, ComboboxList } from '@/components/ui/combobox';
import BorrowBookDialog from '@/components/BorrowBookDialog.vue';

// Define props if needed
interface Props {
    initialUsers?: { id: number; first_name: string; last_name?: string; email: string }[];
    accessionNumber?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    initialUsers: () => [],
    accessionNumber: null, // default value for accessionNumber
});

// State for users and search query
const users = ref<{ value: number; label: string; email: string; fullName: string }[]>([]);
const searchQuery = ref('');

// Debounced search function
const fetchUsers = debounce(async (query: string) => {
    if (query.length < 2) {
        users.value = []; // Clear results if query is too short
        return;
    }

    try {
        // Using router.get with onSuccess callback
        router.get(route('borrowings.users.search'),
            { q: query },
            {
                preserveState: true,
                onSuccess: (page) => {
                    // Assuming your Laravel controller returns users in page.props.users
                    const userData = page.props.users as { id: number; first_name: string; last_name?: string; email: string }[];
                    users.value = userData.map((user) => {
                        const fullName = [user.first_name, user.last_name].filter(Boolean).join(' ');
                        return {
                            value: user.id,
                            label: fullName || user.first_name, // Fallback to first_name if last_name is empty
                            email: user.email,
                            fullName: fullName
                        };
                    });
                },
                onError: (errors) => {
                    console.error('Error fetching users:', errors);
                    users.value = [];
                }
            }
        );
    } catch (error) {
        console.error('Error fetching users:', error);
        users.value = [];
    }
}, 300);

// Watch for changes in searchQuery and trigger search
watch(searchQuery, (newQuery) => {
    fetchUsers(newQuery);
});

</script>

<template>
    <Combobox by="label" class="w-sm rounded-lg border">
        <ComboboxAnchor class="w-full">
            <div class="relative w-full items-center">
                <ComboboxInput
                    v-model="searchQuery"
                    class="pl-2"
                    :display-value="(val) => val?.label ?? ''"
                    placeholder="Search users by name or email..."
                />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                    <Search class="size-4 text-muted-foreground" />
                </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList class="w-full">
            <div v-if="!users || users.length === 0">
                No User Found
            </div>
            <div
                v-for="user in users"
                :key="user.value"
            >
                <BorrowBookDialog :user="user" :book_accession="accessionNumber"/>
            </div>
        </ComboboxList>
    </Combobox>
</template>
