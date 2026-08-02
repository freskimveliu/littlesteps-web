<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ChildShell from '../../../../components/children/ChildShell.vue';
import UiCard from '../../../../components/ui/UiCard.vue';
import UiBadge from '../../../../components/ui/UiBadge.vue';
import UiButton from '../../../../components/ui/UiButton.vue';
import UiTable from '../../../../components/ui/UiTable.vue';
import UiTableRow from '../../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../../components/ui/UiTableHeader.vue';
import type { ChildSummary } from '../../../../types/admin';

defineProps<{
    summary: ChildSummary;
    members: {
        id: number;
        user: { id: number; name: string; email: string | null } | null;
        relation: string;
        role: string;
        is_creator: boolean;
    }[];
}>();
</script>

<template>
    <Head :title="`${summary.child.name} · Family`" />

    <ChildShell :summary="summary" tab="family">
        <UiCard title="Who can see this child" flush>
            <UiTable bare :empty="members.length === 0" empty-title="No members">
                <template #header>
                    <UiTableHeader>Name</UiTableHeader>
                    <UiTableHeader>Relation</UiTableHeader>
                    <UiTableHeader>Role</UiTableHeader>
                    <UiTableHeader align="right" />
                </template>
                <template #body>
                    <UiTableRow v-for="member in members" :key="member.id">
                        <UiTableCell>
                            <Link
                                v-if="member.user"
                                :href="`/admin/users/${member.user.id}`"
                                class="font-medium text-slate-900 hover:underline"
                            >
                                {{ member.user.name }}
                            </Link>
                            <p class="text-label text-slate-400">{{ member.user?.email ?? 'not signed up' }}</p>
                        </UiTableCell>
                        <UiTableCell cell-class="text-slate-600">{{ member.relation }}</UiTableCell>
                        <UiTableCell>
                            <UiBadge :tone="member.is_creator ? 'primary' : 'neutral'">
                                {{ member.is_creator ? 'creator' : member.role }}
                            </UiBadge>
                        </UiTableCell>
                        <UiTableCell align="right">
                            <UiButton v-if="member.user" variant="outline" :to="`/admin/users/${member.user.id}`">
                                Open
                            </UiButton>
                        </UiTableCell>
                    </UiTableRow>
                </template>
            </UiTable>
        </UiCard>
    </ChildShell>
</template>
