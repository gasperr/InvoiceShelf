<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-9 xl:gap-8">
    <!-- Amount Due -->
    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      :icon-component="DollarIcon"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/invoices"
      :large="true"
      :label="$t('Net this month')"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.totalAmountDue"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      :icon-component="PaymentIcon"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/invoices"
      :large="false"
      :label="$t('Net previous month')"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.netPreviousMonth"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      :icon-component="PaymentIcon"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/invoices"
      :large="false"
      :label="$t('All net income')"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.totalNetIncome"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <!-- Customers -->
    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_CUSTOMER)"
      :icon-component="InvoiceIcon"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/recurring-invoices"
      :label="$t('Recurring income')"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.totalCustomerCount"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <!--    &lt;!&ndash; Invoices &ndash;&gt;-->
    <!--    <DashboardStatsItem-->
    <!--      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"-->
    <!--      :icon-component="InvoiceIcon"-->
    <!--      :loading="!dashboardStore.isDashboardDataLoaded"-->
    <!--      route="/admin/invoices"-->
    <!--      :label="(dashboardStore.stats.totalInvoiceCount <= 1 ? $t('dashboard.cards.invoices', 1) : $t('dashboard.cards.invoices', 2))"-->
    <!--    >-->
    <!--      {{ dashboardStore.stats.totalInvoiceCount }}-->
    <!--    </DashboardStatsItem>-->
  </div>
</template>

<script setup>
import DollarIcon from '@/scripts/components/icons/dashboard/DollarIcon.vue'
import CustomerIcon from '@/scripts/components/icons/dashboard/CustomerIcon.vue'
import InvoiceIcon from '@/scripts/components/icons/dashboard/InvoiceIcon.vue'
import EstimateIcon from '@/scripts/components/icons/dashboard/EstimateIcon.vue'
import abilities from '@/scripts/admin/stub/abilities'
import DashboardStatsItem from './DashboardStatsItem.vue'

import { inject } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import PaymentIcon from '@/scripts/components/icons/dashboard/PaymentIcon.vue'

const utils = inject('utils')

const dashboardStore = useDashboardStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()
</script>
