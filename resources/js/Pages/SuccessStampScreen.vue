<template>
  <div class="stamp-wrap flex-1 flex flex-col items-center justify-center p-[30px] text-center">
    <div
      class="stamp border-[2.5px] border-dashed rounded-[16px] px-[26px] py-[18px] -rotate-[4deg] font-['Space_Grotesk'] font-bold text-[22px] tracking-[0.06em] mb-[22px]"
      :class="[
        successStamp.isOrder ? 'order border-[var(--accent)] text-[var(--accent)]' : 'border-[var(--good)] text-[var(--good)]'
      ]"
    >
      {{ successStamp.title }}
    </div>
    <div class="stamp-detail card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px] w-full text-left">
      <div v-for="d in successStamp.details" :key="d.k" class="flex justify-between py-[10px] border-b border-[var(--border)] text-[13.5px] last:border-b-0">
        <span class="text-[var(--text-muted)]">{{ d.k }}</span>
        <span class="font-mono text-[12.5px]">{{ d.v }}</span>
      </div>
    </div>
    <button class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed" style="margin-top:20px;" @click="successStamp.action">
      Selesai
    </button>
  </div>
</template>

<script setup>
import { useSuccessStamp } from "../composables/useSuccessStamp";

const { successStamp } = useSuccessStamp();
</script>
