<template>
  <div class="stamp-wrap">
    <div class="stamp" :class="{ order: successStamp.isOrder }">{{ successStamp.title }}</div>
    <div class="card stamp-detail">
      <div v-for="d in successStamp.details" :key="d.k" class="info-line">
        <span class="k">{{ d.k }}</span>
        <span class="v">{{ d.v }}</span>
      </div>
    </div>
    <button class="btn btn-primary" style="margin-top:20px;" @click="successStamp.action">
      Selesai
    </button>
  </div>
</template>

<script setup>
import { useSuccessStamp } from "../composables/useSuccessStamp";

const { successStamp } = useSuccessStamp();
</script>
