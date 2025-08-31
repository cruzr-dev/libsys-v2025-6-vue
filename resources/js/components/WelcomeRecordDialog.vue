<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    record: {
        title: string
        accession_number: string
        status: string
    }
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Card class="overflow-hidden p-0 hover:shadow-md transition">
                <div class="flex">
                    <!-- Main content -->
                    <div class="flex-1 p-6">
                        <CardHeader class="p-0 pb-4">
                            <CardTitle class="line-clamp-2 text-xl font-semibold">
                                {{ props.record.title }}
                            </CardTitle>
                        </CardHeader>

                        <CardContent class="p-0">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Accession Number</span>
                                    <div class="font-medium">{{ props.record.accession_number }}</div>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Status</span>
                                    <div>
                                        <Badge>{{ props.record.status }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </div>
                </div>
            </Card>
        </DialogTrigger>

        <DialogContent class="space-y-4">
            <h2 class="text-2xl font-bold">{{ props.record.title }}</h2>
            <p><strong>Accession Number:</strong> {{ props.record.accession_number }}</p>
            <p><strong>Status:</strong> {{ props.record.status }}</p>
        </DialogContent>
    </Dialog>
</template>
