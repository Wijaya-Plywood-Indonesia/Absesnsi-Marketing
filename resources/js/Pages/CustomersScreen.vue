<template>
  <div class="appbar"><h1>Customer Saya</h1></div>
  <div class="scroll" style="position:relative;">
    <div class="search-box">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input v-model="searchQuery" placeholder="Cari nama toko...">
    </div>
    <div class="filter-row">
      <div
        v-for="f in ['Semua','Baru','Lama']"
        :key="f"
        class="filter-chip"
        :class="{ active: activeFilter === f }"
        @click="activeFilter = f"
      >
        {{ f }}
      </div>
    </div>
    <div v-if="filteredCustomers.length">
      <div
        v-for="c in filteredCustomers"
        :key="c.id"
        class="cust-card"
        @click="openDetail(c.id)"
      >
        <div class="cname">{{ c.name }}</div>
        <div class="caddr">{{ c.addr }}</div>
        <div class="tags">
          <span class="chip" :class="c.status === 'Baru' ? 'baru' : 'lama'">{{ c.status }}</span>
          <span class="chip" :class="c.pola === 'Partai' ? 'partai' : 'ecer'">{{ c.pola }}</span>
          <span class="chip">{{ c.jenis }}</span>
        </div>
      </div>
    </div>
    <div v-else class="empty-note">Tidak ada customer yang cocok.</div>
  </div>
  <button class="fab" @click="nav('newCustomer')">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
  </button>
</template>

<script setup>
import { useCustomers } from "../composables/useCustomers";
import { useAppNav } from "../composables/useAppNav";

const { searchQuery, activeFilter, filteredCustomers } = useCustomers();
const { nav, openDetail } = useAppNav();
</script>
