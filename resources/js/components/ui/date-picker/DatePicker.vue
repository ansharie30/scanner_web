<script setup lang="ts">
import {
    getLocalTimeZone,
    parseDate,
    today,
    type DateValue,
} from '@internationalized/date';
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, shallowRef, watch } from 'vue';

import { Button } from '@/components/ui/button';

const props = defineProps<{
    id: string;
    modelValue: string;
    label: string;
    minDate?: string;
    maxDate?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const isOpen = ref(false);
const selectedDate = computed<DateValue | undefined>(() =>
    props.modelValue ? parseDate(props.modelValue) : undefined,
);
const minValue = computed(() =>
    props.minDate ? parseDate(props.minDate) : undefined,
);
const maxValue = computed(() =>
    props.maxDate ? parseDate(props.maxDate) : undefined,
);
const placeholder = shallowRef<DateValue>(
    selectedDate.value?.copy() ?? today(getLocalTimeZone()),
);
const displayValue = computed(
    () =>
        selectedDate.value
            ?.toDate(getLocalTimeZone())
            .toLocaleDateString(undefined, { dateStyle: 'medium' }) ??
        'Select date',
);

watch(selectedDate, (value) => {
    if (value) {
        placeholder.value = value.copy();
    }
});

function selectDate(value: DateValue | DateValue[] | undefined) {
    if (!value || Array.isArray(value)) {
        return;
    }

    emit('update:modelValue', value.toString());
    isOpen.value = false;
}
</script>

<template>
    <PopoverRoot v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="w-full justify-start gap-2 text-left font-normal"
                :aria-label="`${label}: ${displayValue}`"
            >
                <CalendarDays class="size-4 text-muted-foreground" />
                <span :class="!selectedDate && 'text-muted-foreground'">
                    {{ displayValue }}
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="4"
                class="z-50 w-auto rounded-md border bg-popover p-3 text-popover-foreground shadow-md"
            >
                <CalendarRoot
                    :model-value="selectedDate"
                    :placeholder="placeholder"
                    :min-value="minValue"
                    :max-value="maxValue"
                    initial-focus
                    @update:model-value="selectDate"
                    @update:placeholder="placeholder = $event"
                    v-slot="{ grid, weekDays }"
                >
                    <CalendarHeader class="mb-3 flex items-center justify-between">
                        <CalendarPrev as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Previous month"
                            >
                                <ChevronLeft class="size-4" />
                            </Button>
                        </CalendarPrev>
                        <CalendarHeading class="text-sm font-medium" />
                        <CalendarNext as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Next month"
                            >
                                <ChevronRight class="size-4" />
                            </Button>
                        </CalendarNext>
                    </CalendarHeader>

                    <CalendarGrid
                        v-for="month in grid"
                        :key="month.value.toString()"
                        class="w-full border-collapse"
                    >
                        <CalendarGridHead>
                            <CalendarGridRow>
                                <CalendarHeadCell
                                    v-for="day in weekDays"
                                    :key="day"
                                    class="size-9 text-center text-xs font-normal text-muted-foreground"
                                >
                                    {{ day }}
                                </CalendarHeadCell>
                            </CalendarGridRow>
                        </CalendarGridHead>
                        <CalendarGridBody>
                            <CalendarGridRow
                                v-for="(week, index) in month.rows"
                                :key="index"
                            >
                                <CalendarCell
                                    v-for="date in week"
                                    :key="date.toString()"
                                    :date="date"
                                    class="p-0 text-center"
                                >
                                    <CalendarCellTrigger
                                        :day="date"
                                        :month="month.value"
                                        class="size-9 rounded-md text-sm font-normal transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[outside-view]:text-muted-foreground/50 data-[disabled]:pointer-events-none data-[disabled]:opacity-40 data-[today]:font-semibold"
                                    />
                                </CalendarCell>
                            </CalendarGridRow>
                        </CalendarGridBody>
                    </CalendarGrid>
                </CalendarRoot>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>