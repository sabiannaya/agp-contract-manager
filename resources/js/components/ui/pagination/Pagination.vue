<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';

const props = defineProps<{
    links: PaginationLink[];
    from?: number;
    to?: number;
    total?: number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'navigate', url: string): void;
}>();

// Filter out prev/next from links array (they're first and last)
const pageLinks = computed(() => {
    return props.links.slice(1, -1);
});

const prevLink = computed(() => props.links[0]);
const nextLink = computed(() => props.links[props.links.length - 1]);

function navigate(url: string | null) {
    if (url) {
        emits('navigate', url);
    }
}
</script>

<template>
    <div
        :class="
            cn(
                'flex items-center justify-between gap-4 px-2',
                props.class,
            )
        "
    >
        <!-- Results info -->
        <p v-if="from && to && total" class="text-sm text-muted-foreground">
            Showing <span class="font-medium">{{ from }}</span> to
            <span class="font-medium">{{ to }}</span> of
            <span class="font-medium">{{ total }}</span> results
        </p>
        <div v-else />

        <!-- Pagination controls -->
        <nav class="flex items-center gap-1">
            <!-- Previous button -->
            <Button
                variant="outline"
                size="sm"
                :disabled="!prevLink?.url"
                @click="navigate(prevLink?.url)"
            >
                <ChevronLeft class="size-4" />
                <span class="sr-only">Previous</span>
            </Button>

            <!-- Page numbers -->
            <template v-for="(link, index) in pageLinks" :key="index">
                <Button
                    v-if="link.label === '...'"
                    variant="ghost"
                    size="sm"
                    disabled
                >
                    <MoreHorizontal class="size-4" />
                </Button>
                <Button
                    v-else
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="navigate(link.url)"
                >
                    {{ link.label }}
                </Button>
            </template>

            <!-- Next button -->
            <Button
                variant="outline"
                size="sm"
                :disabled="!nextLink?.url"
                @click="navigate(nextLink?.url)"
            >
                <ChevronRight class="size-4" />
                <span class="sr-only">Next</span>
            </Button>
        </nav>
    </div>
</template>
