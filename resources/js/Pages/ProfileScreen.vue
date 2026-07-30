<template>
  <div class="appbar"><h1>Profil</h1></div>
  <div class="scroll">
    <div class="profile-top">
      <div class="avatar">{{ getInitials(user.name) }}</div>
      <div class="pname">{{ user.name }}</div>
      <div class="parea">Sales Motoris · Wijaya Plywood</div>
    </div>
    <div class="stat-row">
      <div class="stat-box">
        <div class="num">{{ totalVisitsThisMonth }}</div>
        <div class="cap">Kunjungan Bulan Ini</div>
      </div>
      <div class="stat-box">
        <div class="num">{{ customerList.length }}</div>
        <div class="cap">Customer Dipegang</div>
      </div>
    </div>
    <div class="section-title">Pengaturan</div>
    <div class="card">
      <div class="info-line"><span class="k">Notifikasi</span><span class="v">Aktif</span></div>
      <div class="info-line"><span class="k">Versi Aplikasi</span><span class="v">Production Vue 2.0</span></div>
    </div>
    <button class="btn btn-outline" style="margin-top:16px;" @click="logout">Keluar</button>
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
