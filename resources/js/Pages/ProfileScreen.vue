<template>
  <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
    <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Profil</h1>
  </div>
  <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]">
    <div class="profile-top flex flex-col items-center pt-[10px] pb-[20px]">
      <div class="avatar w-[64px] h-[64px] rounded-full bg-[var(--accent-soft)] border border-[var(--accent)] flex items-center justify-center font-['Space_Grotesk'] text-[22px] font-bold text-[var(--accent)]">{{ getInitials(user.name) }}</div>
      <div class="font-['Space_Grotesk'] font-bold text-[17px] mt-[10px]">{{ user.name }}</div>
      <div class="text-[12.5px] text-[var(--text-muted)] mt-[2px]">Sales Motoris · Wijaya Plywood</div>
    </div>
    <div class="flex gap-[10px] mt-[16px]">
      <div class="stat-box flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[13px]">
        <div class="font-['Space_Grotesk'] text-[22px] font-bold">{{ totalVisitsThisMonth }}</div>
        <div class="text-[11px] text-[var(--text-muted)] mt-[2px]">Kunjungan Bulan Ini</div>
      </div>
      <div class="stat-box flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[13px]">
        <div class="font-['Space_Grotesk'] text-[22px] font-bold">{{ customerList.length }}</div>
        <div class="text-[11px] text-[var(--text-muted)] mt-[2px]">Customer Dipegang</div>
      </div>
    </div>
    <div class="font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]">Pengaturan</div>
    <div class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px]">
      <div class="flex justify-between py-[10px] border-b border-[var(--border)] text-[13.5px]"><span class="text-[var(--text-muted)]">Notifikasi</span><span class="font-mono text-[12.5px]">Aktif</span></div>
      <div class="flex justify-between py-[10px] text-[13.5px]"><span class="text-[var(--text-muted)]">Versi Aplikasi</span><span class="font-mono text-[12.5px]">Production Vue 2.0</span></div>
    </div>
    <button class="btn btn-outline font-sans font-semibold text-[14.5px] rounded-[12px] bg-transparent text-[var(--text)] border border-[var(--border)] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full" style="margin-top:16px;" @click="logout">Keluar</button>
  </div>
</template>

<script setup>
import { useAuth } from "../composables/useAuth";
import { useCustomers } from "../composables/useCustomers";
import { useVisits } from "../composables/useVisits";

const { user, getInitials, logout } = useAuth();
const { customerList } = useCustomers();
const { totalVisitsThisMonth } = useVisits();
</script>
