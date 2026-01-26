<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { useLocale } from '@/composables/useLocale';
import { Languages, Monitor, Moon, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();
const { locale, updateLocale } = useLocale();

const themeTabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const localeTabs = [
    { value: 'en', label: 'English', flag: '🇺🇸' },
    { value: 'id', label: 'Bahasa', flag: '🇮🇩' },
] as const;
</script>

<template>
    <div class="space-y-6">
        <!-- Theme Switcher -->
        <div class="space-y-2">
            <label class="text-sm font-medium text-foreground">Theme</label>
            <div
                class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
            >
                <button
                    v-for="{ value, Icon, label } in themeTabs"
                    :key="value"
                    @click="updateAppearance(value)"
                    :class="[
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors cursor-pointer',
                        appearance === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    ]"
                >
                    <component :is="Icon" class="-ml-1 h-4 w-4" />
                    <span class="ml-1.5 text-sm">{{ label }}</span>
                </button>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="space-y-2">
            <label class="text-sm font-medium text-foreground flex items-center gap-2">
                <Languages class="h-4 w-4" />
                Language
            </label>
            <div
                class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
            >
                <button
                    v-for="{ value, label, flag } in localeTabs"
                    :key="value"
                    @click="updateLocale(value)"
                    :class="[
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors cursor-pointer',
                        locale === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    ]"
                >
                    <span class="-ml-1 text-base">{{ flag }}</span>
                    <span class="ml-1.5 text-sm">{{ label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
