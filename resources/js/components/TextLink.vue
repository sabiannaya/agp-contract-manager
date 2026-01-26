<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

type RouteDefinition = {
    url: string;
    method: string;
};

type Props = {
    href: string | RouteDefinition;
    tabindex?: number;
    method?: Method;
    as?: string;
};

const props = defineProps<Props>();

const resolvedHref = computed(() => {
    if (typeof props.href === 'string') {
        return props.href;
    }
    return props.href.url;
});

const resolvedMethod = computed<Method | undefined>(() => {
    if (props.method) {
        return props.method;
    }
    if (typeof props.href !== 'string' && props.href.method) {
        return props.href.method as Method;
    }
    return undefined;
});
</script>

<template>
    <Link
        :href="resolvedHref"
        :tabindex="tabindex"
        :method="resolvedMethod"
        :as="as"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
    >
        <slot />
    </Link>
</template>
