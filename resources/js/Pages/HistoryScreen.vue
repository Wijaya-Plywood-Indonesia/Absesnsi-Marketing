<template>
  <div class="appbar"><h1>Riwayat Kunjungan</h1></div>
  <div class="scroll">
    <div v-if="visitList.length" class="recent-list">
      <div
        v-for="v in visitList"
        :key="v.id"
        class="hist-row"
        @click="openDetail(v.custId)"
      >
        <div class="hist-top">
          <span class="hname">{{ getCustomerName(v.custId) }}</span>
          <span class="hdate">{{ v.date }}, {{ v.time }}</span>
        </div>
        <div class="hist-note">{{ v.hasil }} — {{ v.note }}</div>
      </div>
    </div>
    <div v-else class="empty-note">Belum ada riwayat kunjungan.</div>
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
