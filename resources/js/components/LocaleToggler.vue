<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Languages } from 'lucide-vue-next';

const { locale } = useI18n();

const locales = [
    { code: 'en', name: 'English', flag: '🇺🇸' },
    { code: 'id', name: 'Bahasa Indonesia', flag: '🇮🇩' },
];

function changeLocale(code: string) {
    locale.value = code as 'en' | 'id';
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="gap-2">
                <Languages class="size-4" />
                <span class="hidden sm:inline">{{ locale.toUpperCase() }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="loc in locales"
                :key="loc.code"
                :class="{ 'bg-accent': locale === loc.code }"
                @click="changeLocale(loc.code)"
            >
                <span class="mr-2">{{ loc.flag }}</span>
                {{ loc.name }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
