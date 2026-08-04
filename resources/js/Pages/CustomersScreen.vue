<template>
  <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
    <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Customer Saya</h1>
  </div>
  <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px] relative">
    <div class="search-box flex items-center gap-[8px] bg-[var(--surface-2)] border border-[var(--border)] rounded-[12px] px-[13px] py-[10px] mt-[14px] mb-[12px]">
      <svg class="w-[16px] h-[16px] stroke-[var(--text-faint)] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input class="bg-transparent border-none outline-none text-[var(--text)] text-[14px] flex-1 font-sans" v-model="searchQuery" placeholder="Cari nama toko...">
    </div>
    <div class="filter-row flex gap-[7px] mb-[14px] overflow-x-auto">
      <div
        v-for="f in ['Semua','Baru','Lama']"
        :key="f"
        class="filter-chip flex-shrink-0 px-[13px] py-[7px] rounded-full border text-[12.5px] font-semibold cursor-pointer"
        :class="activeFilter === f ? 'active bg-[var(--accent)] text-[var(--accent-ink)] border-[var(--accent)]' : 'border-[var(--border)] text-[var(--text-muted)] bg-[var(--surface)]'"
        @click="activeFilter = f"
      >
        {{ f }}
      </div>
    </div>
    <div v-if="filteredCustomers.length">
      <div
        v-for="c in filteredCustomers"
        :key="c.id"
        class="cust-card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[14px] mb-[10px] cursor-pointer"
        @click="openDetail(c.id)"
      >
        <div class="font-['Space_Grotesk'] font-semibold text-[15px]">{{ c.name }}</div>
        <div class="text-[12.5px] text-[var(--text-muted)] mt-[3px] mb-[10px]">{{ c.addr }}</div>
        <div class="flex gap-[6px] flex-wrap">
          <span
            class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
            :class="c.status === 'Baru' ? 'baru text-[var(--good)] border-[#3c4d33] bg-[var(--good-soft)]' : 'lama border-[var(--border)] text-[var(--text-muted)]'"
          >
            {{ c.status }}
          </span>
          <span
            class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
            :class="c.pola === 'Partai' ? 'partai text-[var(--accent)] border-[var(--accent-soft)] bg-[var(--accent-soft)]' : 'ecer border-[var(--border)] text-[var(--text-muted)]'"
          >
            {{ c.pola }}
          </span>
          <span class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border border-[var(--border)] text-[var(--text-muted)] font-mono tracking-[0.01em]">{{ c.jenis }}</span>
        </div>
      </div>
    </div>
    <div v-else class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">Tidak ada customer yang cocok.</div>
  </div>
  <button class="fab absolute right-[18px] bottom-[96px] w-[52px] h-[52px] rounded-[16px] bg-[var(--accent)] text-[var(--accent-ink)] border-none flex items-center justify-center shadow-[0_10px_24px_-8px_rgba(242,169,59,0.5)] cursor-pointer z-40" @click="nav('newCustomer')">
    <svg class="w-[22px] h-[22px] stroke-[var(--accent-ink)]" viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
  </button>
</template>

<script setup>
import { useCustomers } from "../composables/useCustomers";
import { useAppNav } from "../composables/useAppNav";

const { searchQuery, activeFilter, filteredCustomers } = useCustomers();
const { nav, openDetail } = useAppNav();
</script>
