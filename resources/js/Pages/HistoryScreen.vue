<template>
  <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
    <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Riwayat Kunjungan</h1>
  </div>
  <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]">
    <div v-if="visitList.length" class="flex flex-col">
      <div
        v-for="v in visitList"
        :key="v.id"
        class="hist-row py-[12px] border-b border-[var(--border)] cursor-pointer last:border-b-0"
        @click="openDetail(v.custId)"
      >
        <div class="flex justify-between items-baseline">
          <span class="font-semibold text-[14px]">{{ getCustomerName(v.custId) }}</span>
          <span class="text-[11px] text-[var(--text-faint)] font-mono">{{ v.date }}, {{ v.time }}</span>
        </div>
        <div class="text-[12.5px] text-[var(--text-muted)] mt-[3px]">{{ v.hasil }} — {{ v.note }}</div>
      </div>
    </div>
    <div v-else class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">Belum ada riwayat kunjungan.</div>
  </div>
</template>

<script setup>
import { useVisits } from "../composables/useVisits";
import { useCustomers } from "../composables/useCustomers";
import { useAppNav } from "../composables/useAppNav";

const { visitList } = useVisits();
const { getCustomerName } = useCustomers();
const { openDetail } = useAppNav();
</script>
